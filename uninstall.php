<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'ps_seo_settings' );
delete_option( 'ps_seo_rewrite_version' );

$meta_keys = array(
	'_ps_seo_title',
	'_ps_seo_description',
	'_ps_seo_focus_keyword',
	'_ps_seo_canonical',
	'_ps_seo_robots',
	'_ps_seo_social',
	'_ps_seo_schema',
	'_ps_seo_redirect',
	'_ps_seo_noindex',
);

foreach ( $meta_keys as $meta_key ) {
	delete_post_meta_by_key( $meta_key );
}

$term_meta_keys = array(
	'_ps_seo_term_title',
	'_ps_seo_term_description',
	'_ps_seo_term_canonical',
	'_ps_seo_term_noindex',
);

foreach ( $term_meta_keys as $meta_key ) {
	delete_term_meta_by_key( $meta_key );
}
