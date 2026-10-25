<?php

namespace PSource\SEO\Admin;

use PSource\SEO\TermMeta;

final class TaxonomyFields {

	private const NONCE_ACTION = 'ps_seo_save_term_meta';
	private const NONCE_NAME   = 'ps_seo_term_nonce';
	private static array $registered_taxonomies = array();

	public static function register(): void {
		add_action( 'init', array( self::class, 'register_existing_taxonomies' ), 99 );
		add_action( 'registered_taxonomy', array( self::class, 'register_taxonomy' ), 10, 3 );
	}

	public static function register_existing_taxonomies(): void {
		foreach ( get_taxonomies( array( 'public' => true ), 'names' ) as $taxonomy ) {
			self::register_taxonomy( $taxonomy, array(), array() );
		}
	}

	public static function register_taxonomy( string $taxonomy, mixed $object_type, mixed $args ): void {
		unset( $object_type, $args );

		$taxonomy_object = get_taxonomy( $taxonomy );
		if ( ! $taxonomy_object || ! $taxonomy_object->public || isset( self::$registered_taxonomies[ $taxonomy ] ) ) {
			return;
		}

		self::$registered_taxonomies[ $taxonomy ] = true;

		add_action( "{$taxonomy}_add_form_fields", array( self::class, 'render_add' ) );
		add_action( "{$taxonomy}_edit_form_fields", array( self::class, 'render_edit' ) );
		add_action( "created_{$taxonomy}", array( self::class, 'save' ) );
		add_action( "edited_{$taxonomy}", array( self::class, 'save' ) );
	}

	public static function render_add( string $taxonomy ): void {
		unset( $taxonomy );
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		?>
		<div class="form-field">
			<label for="ps-seo-term-title"><?php esc_html_e( 'SEO-Titel', 'ps-seo' ); ?></label>
			<input id="ps-seo-term-title" name="ps_seo_term[title]" type="text" value="">
		</div>
		<div class="form-field">
			<label for="ps-seo-term-description"><?php esc_html_e( 'Meta-Beschreibung', 'ps-seo' ); ?></label>
			<textarea id="ps-seo-term-description" name="ps_seo_term[description]" rows="5"></textarea>
		</div>
		<div class="form-field">
			<label for="ps-seo-term-canonical"><?php esc_html_e( 'Canonical URL', 'ps-seo' ); ?></label>
			<input id="ps-seo-term-canonical" name="ps_seo_term[canonical]" type="url" value="">
		</div>
		<div class="form-field">
			<label for="ps-seo-term-noindex">
				<input id="ps-seo-term-noindex" name="ps_seo_term[noindex]" type="checkbox" value="1">
				<?php esc_html_e( 'Diese Archivseite nicht indexieren', 'ps-seo' ); ?>
			</label>
		</div>
		<?php
	}

	public static function render_edit( \WP_Term $term ): void {
		$title       = TermMeta::get( $term->term_id, TermMeta::TITLE );
		$description = TermMeta::get( $term->term_id, TermMeta::DESCRIPTION );
		$canonical   = TermMeta::get( $term->term_id, TermMeta::CANONICAL );
		$noindex     = '1' === TermMeta::get( $term->term_id, TermMeta::NOINDEX );

		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		?>
		<tr class="form-field">
			<th scope="row"><label for="ps-seo-term-title"><?php esc_html_e( 'SEO-Titel', 'ps-seo' ); ?></label></th>
			<td><input id="ps-seo-term-title" name="ps_seo_term[title]" type="text" value="<?php echo esc_attr( $title ); ?>" class="regular-text"></td>
		</tr>
		<tr class="form-field">
			<th scope="row"><label for="ps-seo-term-description"><?php esc_html_e( 'Meta-Beschreibung', 'ps-seo' ); ?></label></th>
			<td><textarea id="ps-seo-term-description" name="ps_seo_term[description]" rows="5" class="large-text"><?php echo esc_textarea( $description ); ?></textarea></td>
		</tr>
		<tr class="form-field">
			<th scope="row"><label for="ps-seo-term-canonical"><?php esc_html_e( 'Canonical URL', 'ps-seo' ); ?></label></th>
			<td><input id="ps-seo-term-canonical" name="ps_seo_term[canonical]" type="url" value="<?php echo esc_attr( $canonical ); ?>" class="regular-text"></td>
		</tr>
		<tr class="form-field">
			<th scope="row"><?php esc_html_e( 'Indexierung', 'ps-seo' ); ?></th>
			<td>
				<label for="ps-seo-term-noindex">
					<input id="ps-seo-term-noindex" name="ps_seo_term[noindex]" type="checkbox" value="1" <?php checked( $noindex ); ?>>
					<?php esc_html_e( 'Diese Archivseite nicht indexieren', 'ps-seo' ); ?>
				</label>
			</td>
		</tr>
		<?php
	}

	public static function save( int $term_id ): void {
		if (
			! isset( $_POST[ self::NONCE_NAME ], $_POST['ps_seo_term'] )
			|| ! is_array( $_POST['ps_seo_term'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION )
		) {
			return;
		}

		$term = get_term( $term_id );
		if ( ! $term instanceof \WP_Term ) {
			return;
		}

		$taxonomy = get_taxonomy( $term->taxonomy );
		if ( ! $taxonomy || ! current_user_can( $taxonomy->cap->manage_terms ) ) {
			return;
		}

		$input = wp_unslash( $_POST['ps_seo_term'] );

		self::save_string( $term_id, TermMeta::TITLE, sanitize_text_field( $input['title'] ?? '' ) );
		self::save_string( $term_id, TermMeta::DESCRIPTION, sanitize_textarea_field( $input['description'] ?? '' ) );
		self::save_string( $term_id, TermMeta::CANONICAL, esc_url_raw( $input['canonical'] ?? '' ) );

		if ( ! empty( $input['noindex'] ) ) {
			update_term_meta( $term_id, TermMeta::NOINDEX, '1' );
		} else {
			delete_term_meta( $term_id, TermMeta::NOINDEX );
		}
	}

	private static function save_string( int $term_id, string $key, string $value ): void {
		if ( '' === $value ) {
			delete_term_meta( $term_id, $key );
			return;
		}

		update_term_meta( $term_id, $key, $value );
	}
}
