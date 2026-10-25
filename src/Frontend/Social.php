<?php

namespace PSource\SEO\Frontend;

use PSource\SEO\PostMeta;
use PSource\SEO\Settings;

final class Social {

	public static function register(): void {
		if ( Settings::enabled( 'social' ) ) {
			add_action( 'wp_head', array( self::class, 'output' ), 20 );
		}
	}

	public static function output(): void {
		$post_id     = is_singular() ? get_queried_object_id() : 0;
		$social      = $post_id ? (array) PostMeta::get( $post_id, PostMeta::SOCIAL, array() ) : array();
		$title       = $social['title'] ?? wp_get_document_title();
		$description = $social['description'] ?? '';

		if ( '' === $description && $post_id ) {
			$description = (string) PostMeta::get( $post_id, PostMeta::DESCRIPTION );
		}
		if ( '' === $description && $post_id ) {
			$description = get_the_excerpt( $post_id );
		}
		if ( '' === $description ) {
			$description = get_bloginfo( 'description' );
		}

		$image_id = absint( $social['image_id'] ?? 0 );
		if ( ! $image_id && $post_id ) {
			$image_id = get_post_thumbnail_id( $post_id );
		}
		if ( ! $image_id ) {
			$image_id = absint( Settings::get( 'social.default_image_id', 0 ) );
		}
		if ( ! $image_id ) {
			$image_id = absint( Settings::get( 'schema.logo_id', get_theme_mod( 'custom_logo' ) ) );
		}

		$url  = is_singular() ? get_permalink( $post_id ) : self::current_url();
		$type = is_singular( 'post' ) ? 'article' : 'website';

		self::meta( 'og:locale', get_locale() );
		self::meta( 'og:type', $type );
		self::meta( 'og:title', $title );
		self::meta( 'og:description', wp_strip_all_tags( $description ) );
		self::meta( 'og:url', $url );
		self::meta( 'og:site_name', get_bloginfo( 'name' ) );

		$app_id = (string) Settings::get( 'social.facebook_app_id', '' );
		if ( '' !== $app_id ) {
			self::meta( 'fb:app_id', $app_id );
		}

		$image = $image_id ? wp_get_attachment_image_src( $image_id, 'full' ) : false;
		if ( $image ) {
			self::meta( 'og:image', $image[0] );
			self::meta( 'og:image:width', (string) $image[1] );
			self::meta( 'og:image:height', (string) $image[2] );
			self::meta( 'og:image:alt', (string) get_post_meta( $image_id, '_wp_attachment_image_alt', true ) );
		}

		if ( $post_id ) {
			self::meta( 'article:published_time', get_the_date( DATE_W3C, $post_id ) );
			self::meta( 'article:modified_time', get_the_modified_date( DATE_W3C, $post_id ) );
		}

		self::twitter_meta( 'twitter:card', (string) Settings::get( 'social.twitter_card', 'summary_large_image' ) );
		self::twitter_meta( 'twitter:title', $title );
		self::twitter_meta( 'twitter:description', wp_strip_all_tags( $description ) );

		$twitter = ltrim( (string) Settings::get( 'social.twitter_username', '' ), '@' );
		if ( '' !== $twitter ) {
			self::twitter_meta( 'twitter:site', '@' . $twitter );
		}
		if ( $image ) {
			self::twitter_meta( 'twitter:image', $image[0] );
		}
	}

	private static function meta( string $property, string $content ): void {
		if ( '' !== trim( $content ) ) {
			printf( "<meta property=\"%s\" content=\"%s\">\n", esc_attr( $property ), esc_attr( $content ) );
		}
	}

	private static function twitter_meta( string $name, string $content ): void {
		if ( '' !== trim( $content ) ) {
			printf( "<meta name=\"%s\" content=\"%s\">\n", esc_attr( $name ), esc_attr( $content ) );
		}
	}

	private static function current_url(): string {
		global $wp;

		return home_url( isset( $wp->request ) ? trailingslashit( $wp->request ) : '/' );
	}
}
