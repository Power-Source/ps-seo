<?php

namespace PSource\SEO\Schema;

use PSource\SEO\Integration\MarketPress;
use PSource\SEO\PostMeta;
use PSource\SEO\Settings;

final class SchemaGraph {

	public static function register(): void {
		if ( Settings::enabled( 'schema' ) ) {
			add_action( 'wp_head', array( self::class, 'output' ), 30 );
		}
	}

	public static function output(): void {
		$graph = array(
			self::website(),
			self::owner(),
		);

		if ( is_singular() ) {
			$post_id = get_queried_object_id();
			$schema  = (array) PostMeta::get( $post_id, PostMeta::SCHEMA, array() );

			if ( 'none' !== ( $schema['type'] ?? '' ) ) {
				$requested_type = $schema['type'] ?? '';
				$graph[]        = MarketPress::is_product( $post_id ) && in_array( $requested_type, array( '', 'Product' ), true )
					? MarketPress::schema( $post_id )
					: self::page( $post_id, $requested_type );
				$graph[] = self::breadcrumbs( $post_id );
			}
		}

		$graph = array_values( array_filter( apply_filters( 'ps_seo_schema_graph', $graph ) ) );
		if ( empty( $graph ) ) {
			return;
		}

		echo '<script type="application/ld+json">' . wp_json_encode(
			array(
				'@context' => 'https://schema.org',
				'@graph'   => $graph,
			),
			JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
		) . "</script>\n";
	}

	private static function website(): array {
		return array(
			'@type'       => 'WebSite',
			'@id'         => home_url( '/#website' ),
			'url'         => home_url( '/' ),
			'name'        => get_bloginfo( 'name' ),
			'description' => get_bloginfo( 'description' ),
			'inLanguage'  => get_bloginfo( 'language' ),
			'publisher'   => array( '@id' => home_url( '/#owner' ) ),
		);
	}

	private static function owner(): array {
		$owner = array(
			'@type' => Settings::get( 'schema.owner_type', 'Organization' ),
			'@id'   => home_url( '/#owner' ),
			'name'  => Settings::get( 'schema.organization', get_bloginfo( 'name' ) ),
			'url'   => home_url( '/' ),
		);

		$logo_id = absint( Settings::get( 'schema.logo_id', get_theme_mod( 'custom_logo' ) ) );
		$logo    = $logo_id ? wp_get_attachment_image_src( $logo_id, 'full' ) : false;
		if ( $logo ) {
			$owner['logo'] = array(
				'@type'  => 'ImageObject',
				'url'    => $logo[0],
				'width'  => $logo[1],
				'height' => $logo[2],
			);
		}

		$profiles = preg_split( '/\R/', (string) Settings::get( 'schema.social_profiles', '' ) ) ?: array();
		$profiles = array_values( array_filter( array_map( 'esc_url_raw', $profiles ) ) );
		if ( $profiles ) {
			$owner['sameAs'] = $profiles;
		}

		$phone = (string) Settings::get( 'schema.contact_phone', '' );
		$email = (string) Settings::get( 'schema.contact_email', '' );
		if ( '' !== $phone || '' !== $email ) {
			$owner['contactPoint'] = array_filter(
				array(
					'@type'       => 'ContactPoint',
					'contactType' => 'customer service',
					'telephone'   => $phone,
					'email'       => $email,
				)
			);
		}

		return $owner;
	}

	private static function page( int $post_id, string $requested_type ): array {
		$post_type = get_post_type( $post_id );
		$type      = in_array( $requested_type, array( 'Article', 'WebPage' ), true )
			? $requested_type
			: ( 'post' === $post_type ? 'Article' : 'WebPage' );

		$page = array(
			'@type'            => $type,
			'@id'              => get_permalink( $post_id ) . '#primary',
			'url'              => get_permalink( $post_id ),
			'name'             => get_the_title( $post_id ),
			'headline'         => get_the_title( $post_id ),
			'description'      => (string) PostMeta::get( $post_id, PostMeta::DESCRIPTION, get_the_excerpt( $post_id ) ),
			'datePublished'    => get_the_date( DATE_W3C, $post_id ),
			'dateModified'     => get_the_modified_date( DATE_W3C, $post_id ),
			'inLanguage'       => get_bloginfo( 'language' ),
			'isPartOf'         => array( '@id' => home_url( '/#website' ) ),
			'mainEntityOfPage' => get_permalink( $post_id ),
		);

		if ( 'Article' === $type ) {
			$page['author']    = array(
				'@type' => 'Person',
				'name'  => get_the_author_meta( 'display_name', (int) get_post_field( 'post_author', $post_id ) ),
			);
			$page['publisher'] = array( '@id' => home_url( '/#owner' ) );
		}

		$image_id = get_post_thumbnail_id( $post_id );
		$image    = $image_id ? wp_get_attachment_image_src( $image_id, 'full' ) : false;
		if ( $image ) {
			$page['image'] = array(
				'@type'  => 'ImageObject',
				'url'    => $image[0],
				'width'  => $image[1],
				'height' => $image[2],
			);
		}

		return $page;
	}

	private static function breadcrumbs( int $post_id ): array {
		$current_title = get_the_title( $post_id );
		if ( '' === trim( $current_title ) ) {
			$current_title = (string) get_post_field( 'post_title', $post_id );
		}

		$items = array(
			array(
				'@type'   => 'ListItem',
				'position' => 1,
				'name'     => __( 'Startseite', 'ps-seo' ),
				'item'     => home_url( '/' ),
			),
		);

		if ( 'post' === get_post_type( $post_id ) ) {
			$categories = get_the_category( $post_id );
			if ( $categories ) {
				$items[] = array(
					'@type'   => 'ListItem',
					'position' => count( $items ) + 1,
					'name'     => $categories[0]->name,
					'item'     => get_category_link( $categories[0] ),
				);
			}
		} else {
			$ancestors = array_reverse( get_post_ancestors( $post_id ) );
			foreach ( $ancestors as $ancestor_id ) {
				$items[] = array(
					'@type'   => 'ListItem',
					'position' => count( $items ) + 1,
					'name'     => get_the_title( $ancestor_id ),
					'item'     => get_permalink( $ancestor_id ),
				);
			}
		}

		$items[] = array(
			'@type'   => 'ListItem',
			'position' => count( $items ) + 1,
			'name'     => $current_title,
			'item'     => get_permalink( $post_id ),
		);

		return array(
			'@type'           => 'BreadcrumbList',
			'@id'             => get_permalink( $post_id ) . '#breadcrumb',
			'itemListElement' => $items,
		);
	}
}
