<?php

namespace PSource\SEO;

final class TermMeta {

	public const TITLE       = '_ps_seo_term_title';
	public const DESCRIPTION = '_ps_seo_term_description';
	public const CANONICAL   = '_ps_seo_term_canonical';
	public const NOINDEX     = '_ps_seo_term_noindex';

	public static function register(): void {
		add_action( 'init', array( self::class, 'register_meta' ) );
	}

	public static function register_meta(): void {
		foreach ( array( self::TITLE, self::DESCRIPTION, self::CANONICAL, self::NOINDEX ) as $key ) {
			$sanitize_callback = self::CANONICAL === $key
				? 'esc_url_raw'
				: ( self::DESCRIPTION === $key ? 'sanitize_textarea_field' : 'sanitize_text_field' );

			register_term_meta(
				'',
				$key,
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => false,
					'sanitize_callback' => $sanitize_callback,
				)
			);
		}
	}

	public static function get( int $term_id, string $key, mixed $default = '' ): mixed {
		$value = get_term_meta( $term_id, $key, true );

		return '' === $value ? $default : $value;
	}
}
