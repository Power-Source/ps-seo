<?php

namespace PSource\SEO\Sitemap;

use PSource\SEO\PostMeta;
use PSource\SEO\Settings;

final class Sitemap {

	public static function register(): void {
		add_filter( 'wp_sitemaps_enabled', array( self::class, 'is_enabled' ) );

		if ( Settings::enabled( 'sitemap' ) ) {
			add_filter( 'wp_sitemaps_posts_query_args', array( self::class, 'filter_query_args' ), 10, 2 );
		}
	}

	public static function is_enabled(): bool {
		return Settings::enabled( 'sitemap' );
	}

	public static function filter_query_args( array $args, string $post_type ): array {
		if ( 'attachment' === $post_type ) {
			return $args;
		}

		$existing   = isset( $args['meta_query'] ) && is_array( $args['meta_query'] ) ? $args['meta_query'] : array();
		$exclusions = array(
			'relation' => 'AND',
			array(
				'key'     => PostMeta::NOINDEX,
				'compare' => 'NOT EXISTS',
			),
			array(
				'key'     => PostMeta::REDIRECT,
				'compare' => 'NOT EXISTS',
			),
		);

		$args['meta_query'] = $existing
			? array(
				'relation' => 'AND',
				$existing,
				$exclusions,
			)
			: $exclusions;

		return $args;
	}
}
