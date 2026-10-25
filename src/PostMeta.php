<?php

namespace PSource\SEO;

final class PostMeta {

	public const TITLE         = '_ps_seo_title';
	public const DESCRIPTION   = '_ps_seo_description';
	public const FOCUS_KEYWORD = '_ps_seo_focus_keyword';
	public const CANONICAL     = '_ps_seo_canonical';
	public const ROBOTS        = '_ps_seo_robots';
	public const SOCIAL        = '_ps_seo_social';
	public const SCHEMA        = '_ps_seo_schema';
	public const REDIRECT      = '_ps_seo_redirect';
	public const NOINDEX       = '_ps_seo_noindex';

	public static function register(): void {
		add_action( 'init', array( self::class, 'register_meta' ) );
	}

	public static function register_meta(): void {
		$string_meta = array(
			self::TITLE,
			self::DESCRIPTION,
			self::FOCUS_KEYWORD,
			self::CANONICAL,
			self::REDIRECT,
			self::NOINDEX,
		);

		foreach ( $string_meta as $key ) {
			register_post_meta(
				'',
				$key,
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => self::REDIRECT === $key || self::CANONICAL === $key ? 'esc_url_raw' : 'sanitize_text_field',
					'auth_callback'     => static fn(): bool => current_user_can( 'edit_posts' ),
				)
			);
		}

		foreach ( array( self::ROBOTS, self::SOCIAL, self::SCHEMA ) as $key ) {
			register_post_meta(
				'',
				$key,
				array(
					'type'          => 'object',
					'single'        => true,
					'show_in_rest'  => false,
					'auth_callback' => static fn(): bool => current_user_can( 'edit_posts' ),
				)
			);
		}
	}

	public static function get( int $post_id, string $key, mixed $default = '' ): mixed {
		$value = get_post_meta( $post_id, $key, true );

		return '' === $value ? $default : $value;
	}
}
