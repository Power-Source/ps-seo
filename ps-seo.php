<?php
/**
 * Plugin Name: PS SEO
 * Plugin URI: https://github.com/Power-Source/ps-seo
 * Description: Moderne SEO-Werkzeuge fuer Metadaten, Social Sharing, Schema und Sitemaps.
 * Version: 3.0.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Network: true
 * Text Domain: ps-seo
 * Author: PSOURCE
 * License: GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PS_SEO_VERSION', '3.0.0' );
define( 'PS_SEO_FILE', __FILE__ );
define( 'PS_SEO_DIR', plugin_dir_path( __FILE__ ) );
define( 'PS_SEO_URL', plugin_dir_url( __FILE__ ) );

spl_autoload_register(
	static function ( string $class ): void {
		$prefix = 'PSource\\SEO\\';

		if ( ! str_starts_with( $class, $prefix ) ) {
			return;
		}

		$relative = substr( $class, strlen( $prefix ) );
		$file     = PS_SEO_DIR . 'src/' . str_replace( '\\', '/', $relative ) . '.php';

		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
);

register_activation_hook(
	__FILE__,
	static function (): void {
		\PSource\SEO\Settings::install_defaults();
	}
);

add_action(
	'plugins_loaded',
	static function (): void {
		load_plugin_textdomain( 'ps-seo', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		\PSource\SEO\Plugin::boot();
	}
);
