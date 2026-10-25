<?php

namespace PSource\SEO\Sitemap;

use PSource\SEO\PostMeta;
use PSource\SEO\Settings;

final class Sitemap {

	private const REWRITE_VERSION_OPTION = 'ps_seo_rewrite_version';
	private const REWRITE_VERSION        = '3';
	private const ALIAS                   = 'ps-sitemap.xml';

	public static function register(): void {
		add_filter( 'wp_sitemaps_enabled', array( self::class, 'is_enabled' ) );
		add_action( 'init', array( self::class, 'add_alias' ) );
		add_action( 'init', array( self::class, 'maybe_flush_rewrite_rules' ), 99 );
		add_filter( 'request', array( self::class, 'filter_alias_query_vars' ), 0 );
		add_action( 'parse_request', array( self::class, 'route_alias' ) );
		add_filter( 'pre_handle_404', array( self::class, 'prevent_alias_redirect' ), -1, 2 );
		add_filter( 'redirect_canonical', array( self::class, 'prevent_canonical_redirect' ) );
		add_filter( 'robots_txt', array( self::class, 'filter_robots_txt' ), 20, 2 );
		add_filter( 'wp_sitemaps_index_entry', array( self::class, 'filter_index_entry' ) );
		add_filter( 'wp_sitemaps_stylesheet_index_url', array( self::class, 'filter_index_stylesheet_url' ) );
		add_filter( 'wp_sitemaps_stylesheet_url', array( self::class, 'filter_sitemap_stylesheet_url' ) );

		if ( Settings::enabled( 'sitemap' ) ) {
			add_filter( 'wp_sitemaps_posts_query_args', array( self::class, 'filter_query_args' ), 10, 2 );
		}
	}

	public static function activate(): void {
		self::add_alias();
		flush_rewrite_rules();
		update_option( self::REWRITE_VERSION_OPTION, self::REWRITE_VERSION );
	}

	public static function deactivate(): void {
		delete_option( self::REWRITE_VERSION_OPTION );
		flush_rewrite_rules();
	}

	public static function add_alias(): void {
		add_rewrite_rule( '^ps-sitemap\.xml$', 'index.php?sitemap=index', 'top' );
		add_rewrite_rule( '^ps-sitemap\.xsl$', 'index.php?sitemap-stylesheet=sitemap', 'top' );
		add_rewrite_rule( '^ps-sitemap-index\.xsl$', 'index.php?sitemap-stylesheet=index', 'top' );
		add_rewrite_rule(
			'^ps-sitemap-([a-z]+?)-([a-z\d_-]+?)-(\d+?)\.xml$',
			'index.php?sitemap=$matches[1]&sitemap-subtype=$matches[2]&paged=$matches[3]',
			'top'
		);
		add_rewrite_rule(
			'^ps-sitemap-([a-z]+?)-(\d+?)\.xml$',
			'index.php?sitemap=$matches[1]&paged=$matches[2]',
			'top'
		);
	}

	public static function maybe_flush_rewrite_rules(): void {
		if ( self::REWRITE_VERSION === get_option( self::REWRITE_VERSION_OPTION ) ) {
			return;
		}

		flush_rewrite_rules();
		update_option( self::REWRITE_VERSION_OPTION, self::REWRITE_VERSION );
	}

	public static function route_alias( \WP $request ): void {
		if ( ! self::is_alias_request() ) {
			return;
		}

		unset( $request->query_vars['name'], $request->query_vars['pagename'] );
		$request->query_vars['sitemap'] = 'index';
	}

	public static function filter_alias_query_vars( array $query_vars ): array {
		if ( ! self::is_alias_request() ) {
			return $query_vars;
		}

		unset( $query_vars['name'], $query_vars['pagename'] );
		$query_vars['sitemap'] = 'index';

		return $query_vars;
	}

	public static function prevent_alias_redirect( bool $bypass, \WP_Query $query ): bool {
		if ( ! self::is_alias_request() ) {
			return $bypass;
		}

		$query->set( 'sitemap', 'index' );
		$query->set( 'name', '' );
		$query->set( 'pagename', '' );

		return true;
	}

	public static function prevent_canonical_redirect( string|false $redirect_url ): string|false {
		return self::is_alias_request() ? false : $redirect_url;
	}

	public static function filter_index_entry( array $entry ): array {
		if ( isset( $entry['loc'] ) && is_string( $entry['loc'] ) ) {
			$entry['loc'] = str_replace( '/wp-sitemap-', '/ps-sitemap-', $entry['loc'] );
		}

		return $entry;
	}

	public static function filter_index_stylesheet_url(): string {
		return home_url( '/ps-sitemap-index.xsl' );
	}

	public static function filter_sitemap_stylesheet_url(): string {
		return home_url( '/ps-sitemap.xsl' );
	}

	public static function filter_robots_txt( string $output, bool $public ): string {
		if ( ! $public || ! self::is_enabled() ) {
			return $output;
		}

		$core_line = 'Sitemap: ' . home_url( '/wp-sitemap.xml' );
		$output    = str_replace( array( $core_line . "\r\n", $core_line . "\n", $core_line ), '', $output );
		$line      = 'Sitemap: ' . home_url( '/' . self::ALIAS );

		return str_contains( $output, $line ) ? $output : rtrim( $output ) . "\n" . $line . "\n";
	}

	public static function is_enabled( bool $enabled = true ): bool {
		return $enabled && Settings::enabled( 'sitemap' );
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

	private static function is_alias_request(): bool {
		$request_path = wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH );
		$site_path    = wp_parse_url( home_url( '/' ), PHP_URL_PATH );

		if ( ! is_string( $request_path ) || ! is_string( $site_path ) || ! str_starts_with( $request_path, $site_path ) ) {
			return false;
		}

		$relative_path = ltrim( substr( $request_path, strlen( $site_path ) ), '/' );

		return 1 === preg_match( '/^ps-sitemap(?:-[a-z\d_-]+)*\.(?:xml|xsl)$/i', $relative_path );
	}
}
