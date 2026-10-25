<?php

namespace PSource\SEO;

final class Template {

	public static function render( string $template, array $context = array() ): string {
		$post = $context['post'] ?? get_queried_object();

		$values = array(
			'%title%'      => $post instanceof \WP_Post ? get_the_title( $post ) : '',
			'%site_name%'  => get_bloginfo( 'name' ),
			'%tagline%'    => get_bloginfo( 'description' ),
			'%separator%'  => apply_filters( 'document_title_separator', '-' ),
			'%search%'     => get_search_query(),
			'%archive%'    => wp_strip_all_tags( get_the_archive_title() ),
			'%author%'     => is_author() ? get_the_author_meta( 'display_name', get_queried_object_id() ) : '',
			'%current_year%' => wp_date( 'Y' ),
		);

		$output = strtr( $template, $values );
		$output = preg_replace( '/\s+/', ' ', $output ) ?? $output;

		return trim( wp_strip_all_tags( $output ) );
	}
}
