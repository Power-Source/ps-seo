<?php

namespace PSource\SEO\Frontend;

use PSource\SEO\PostMeta;
use PSource\SEO\Settings;

final class Redirect {

	public static function register(): void {
		if ( Settings::enabled( 'redirects' ) ) {
			add_action( 'template_redirect', array( self::class, 'maybe_redirect' ), 1 );
		}
	}

	public static function maybe_redirect(): void {
		if ( ! is_singular() || is_preview() ) {
			return;
		}

		$post_id = get_queried_object_id();
		$target  = (string) PostMeta::get( $post_id, PostMeta::REDIRECT );

		if ( '' === $target || untrailingslashit( $target ) === untrailingslashit( get_permalink( $post_id ) ) ) {
			return;
		}

		$target = wp_validate_redirect( $target, '' );
		if ( '' === $target ) {
			return;
		}

		wp_redirect( $target, 301, 'PS SEO' );
		exit;
	}
}
