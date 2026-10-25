<?php

namespace PSource\SEO\Frontend;

use PSource\SEO\PostMeta;
use PSource\SEO\Settings;
use PSource\SEO\Template;
use PSource\SEO\TermMeta;

final class Metadata {

	public static function register(): void {
		if ( ! Settings::enabled( 'metadata' ) ) {
			return;
		}

		add_filter( 'pre_get_document_title', array( self::class, 'filter_title' ), 20 );
		add_filter( 'get_canonical_url', array( self::class, 'filter_canonical' ), 20, 2 );
		add_filter( 'wp_robots', array( self::class, 'filter_robots' ), 20 );
		add_action( 'wp_head', array( self::class, 'output_description' ), 2 );
		add_action( 'wp_head', array( self::class, 'output_archive_canonical' ), 10 );
	}

	public static function filter_title( string $title ): string {
		if ( is_feed() ) {
			return $title;
		}

		if ( is_singular() ) {
			$post_id = get_queried_object_id();
			$custom  = (string) PostMeta::get( $post_id, PostMeta::TITLE );

			if ( '' !== $custom ) {
				return Template::render( $custom, array( 'post' => get_post( $post_id ) ) );
			}

			if ( is_front_page() ) {
				return Template::render( (string) Settings::get( 'titles.home', '%site_name%' ) );
			}

			$post_type = get_post_type( $post_id );
			$template  = (string) Settings::get( 'titles.' . $post_type, Settings::get( 'titles.post', '%title%' ) );

			return Template::render( $template, array( 'post' => get_post( $post_id ) ) );
		}

		$term = self::queried_term();
		if ( $term ) {
			$custom = (string) TermMeta::get( $term->term_id, TermMeta::TITLE );

			if ( '' !== $custom ) {
				return Template::render( $custom );
			}
		}

		$context = self::title_context();
		if ( null === $context ) {
			return $title;
		}

		return Template::render( (string) Settings::get( 'titles.' . $context, $title ) );
	}

	public static function output_description(): void {
		$description = '';

		if ( is_singular() ) {
			$post_id     = get_queried_object_id();
			$description = (string) PostMeta::get( $post_id, PostMeta::DESCRIPTION );

			if ( '' === $description ) {
				$description = get_the_excerpt( $post_id );
			}
		} elseif ( is_front_page() ) {
			$description = get_bloginfo( 'description' );
		} elseif ( is_category() || is_tag() || is_tax() ) {
			$term        = self::queried_term();
			$description = $term ? (string) TermMeta::get( $term->term_id, TermMeta::DESCRIPTION ) : '';

			if ( '' === $description ) {
				$description = term_description();
			}
		}

		$description = trim( wp_strip_all_tags( $description ) );
		if ( '' === $description ) {
			return;
		}

		printf( "<meta name=\"description\" content=\"%s\">\n", esc_attr( $description ) );
	}

	public static function filter_canonical( string $canonical, \WP_Post $post ): string {
		$custom = (string) PostMeta::get( $post->ID, PostMeta::CANONICAL );

		return '' !== $custom ? $custom : $canonical;
	}

	public static function output_archive_canonical(): void {
		if ( ! is_front_page() && ! is_home() && ! is_archive() ) {
			return;
		}

		global $wp;

		$term      = self::queried_term();
		$canonical = $term ? (string) TermMeta::get( $term->term_id, TermMeta::CANONICAL ) : '';

		if ( '' === $canonical ) {
			$path      = isset( $wp->request ) ? trailingslashit( $wp->request ) : '/';
			$canonical = home_url( $path );
		}

		printf( "<link rel=\"canonical\" href=\"%s\">\n", esc_url( $canonical ) );
	}

	public static function filter_robots( array $robots ): array {
		if ( is_singular() ) {
			$settings = (array) PostMeta::get( get_queried_object_id(), PostMeta::ROBOTS, array() );
			foreach ( array( 'noindex', 'nofollow', 'noarchive', 'nosnippet' ) as $directive ) {
				if ( ! empty( $settings[ $directive ] ) ) {
					$robots[ $directive ] = true;
				}
			}

			return $robots;
		}

		$term = self::queried_term();
		if ( $term && '1' === TermMeta::get( $term->term_id, TermMeta::NOINDEX ) ) {
			$robots['noindex'] = true;
		}

		return $robots;
	}

	private static function queried_term(): ?\WP_Term {
		$term = get_queried_object();

		return $term instanceof \WP_Term ? $term : null;
	}

	private static function title_context(): ?string {
		if ( is_front_page() || is_home() ) {
			return 'home';
		}

		if ( is_search() ) {
			return 'search';
		}

		if ( is_404() ) {
			return '404';
		}

		if ( is_archive() ) {
			return 'archive';
		}

		return null;
	}
}
