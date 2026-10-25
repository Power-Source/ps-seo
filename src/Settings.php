<?php

namespace PSource\SEO;

final class Settings {

	public const OPTION = 'ps_seo_settings';

	public static function defaults(): array {
		return array(
			'features' => array(
				'metadata'  => true,
				'social'    => true,
				'schema'    => true,
				'sitemap'   => true,
				'redirects' => true,
				'analysis'  => true,
			),
			'titles'   => array(
				'home'    => '%site_name% %separator% %tagline%',
				'post'    => '%title% %separator% %site_name%',
				'page'    => '%title% %separator% %site_name%',
				'archive' => '%archive% %separator% %site_name%',
				'search'  => 'Suche nach %search% %separator% %site_name%',
				'404'     => 'Seite nicht gefunden %separator% %site_name%',
			),
			'social'   => array(
				'default_image_id' => 0,
				'twitter_username' => '',
				'twitter_card'     => 'summary_large_image',
				'facebook_app_id'  => '',
			),
			'schema'   => array(
				'owner_type'       => 'Organization',
				'organization'     => get_bloginfo( 'name' ),
				'logo_id'          => 0,
				'contact_phone'    => '',
				'contact_email'    => '',
				'social_profiles'  => '',
			),
		);
	}

	public static function install_defaults(): void {
		if ( false === get_option( self::OPTION, false ) ) {
			add_option( self::OPTION, self::defaults() );
		}
	}

	public static function all(): array {
		return self::merge( self::defaults(), (array) get_option( self::OPTION, array() ) );
	}

	public static function get( string $path, mixed $default = null ): mixed {
		$value = self::all();

		foreach ( explode( '.', $path ) as $segment ) {
			if ( ! is_array( $value ) || ! array_key_exists( $segment, $value ) ) {
				return $default;
			}

			$value = $value[ $segment ];
		}

		return $value;
	}

	public static function enabled( string $feature ): bool {
		return (bool) self::get( 'features.' . $feature, false );
	}

	public static function sanitize( mixed $input ): array {
		$input    = is_array( $input ) ? $input : array();
		$defaults = self::defaults();
		$output   = $defaults;

		foreach ( array_keys( $defaults['features'] ) as $feature ) {
			$output['features'][ $feature ] = ! empty( $input['features'][ $feature ] );
		}

		foreach ( array_keys( $defaults['titles'] ) as $context ) {
			$output['titles'][ $context ] = sanitize_text_field( $input['titles'][ $context ] ?? $defaults['titles'][ $context ] );
		}

		$output['social']['default_image_id'] = absint( $input['social']['default_image_id'] ?? 0 );
		$output['social']['twitter_username'] = sanitize_text_field( $input['social']['twitter_username'] ?? '' );
		$output['social']['twitter_card']     = in_array( $input['social']['twitter_card'] ?? '', array( 'summary', 'summary_large_image' ), true )
			? $input['social']['twitter_card']
			: 'summary_large_image';
		$output['social']['facebook_app_id']  = sanitize_text_field( $input['social']['facebook_app_id'] ?? '' );

		$output['schema']['owner_type']      = 'Person' === ( $input['schema']['owner_type'] ?? '' ) ? 'Person' : 'Organization';
		$output['schema']['organization']    = sanitize_text_field( $input['schema']['organization'] ?? '' );
		$output['schema']['logo_id']         = absint( $input['schema']['logo_id'] ?? 0 );
		$output['schema']['contact_phone']   = sanitize_text_field( $input['schema']['contact_phone'] ?? '' );
		$output['schema']['contact_email']   = sanitize_email( $input['schema']['contact_email'] ?? '' );
		$output['schema']['social_profiles'] = implode(
			"\n",
			array_filter(
				array_map(
					'esc_url_raw',
					preg_split( '/\R/', (string) ( $input['schema']['social_profiles'] ?? '' ) ) ?: array()
				)
			)
		);

		return $output;
	}

	private static function merge( array $defaults, array $values ): array {
		foreach ( $values as $key => $value ) {
			if ( isset( $defaults[ $key ] ) && is_array( $defaults[ $key ] ) && is_array( $value ) ) {
				$defaults[ $key ] = self::merge( $defaults[ $key ], $value );
			} else {
				$defaults[ $key ] = $value;
			}
		}

		return $defaults;
	}
}
