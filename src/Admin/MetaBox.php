<?php

namespace PSource\SEO\Admin;

use PSource\SEO\PostMeta;
use PSource\SEO\Settings;

final class MetaBox {

	private const NONCE_ACTION = 'ps_seo_save_meta';
	private const NONCE_NAME   = 'ps_seo_nonce';

	public static function register(): void {
		add_action( 'add_meta_boxes', array( self::class, 'add' ) );
		add_action( 'save_post', array( self::class, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
	}

	public static function add(): void {
		foreach ( get_post_types( array( 'public' => true ), 'names' ) as $post_type ) {
			if ( 'attachment' === $post_type ) {
				continue;
			}

			add_meta_box(
				'ps-seo',
				__( 'PS SEO', 'ps-seo' ),
				array( self::class, 'render' ),
				$post_type,
				'normal',
				'high'
			);
		}
	}

	public static function enqueue_assets( string $hook ): void {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_style( 'ps-seo-admin', PS_SEO_URL . 'assets/css/admin.css', array(), PS_SEO_VERSION );
		wp_enqueue_script( 'ps-seo-admin', PS_SEO_URL . 'assets/js/admin.js', array(), PS_SEO_VERSION, true );
		wp_localize_script(
			'ps-seo-admin',
			'psSeoAdmin',
			array(
				'titleLengthGood'       => __( 'Der SEO-Titel hat eine gute Länge.', 'ps-seo' ),
				'titleLengthImprove'    => __( 'Der SEO-Titel sollte 30 bis 60 Zeichen lang sein.', 'ps-seo' ),
				'descriptionLengthGood' => __( 'Die Meta-Beschreibung hat eine gute Länge.', 'ps-seo' ),
				'descriptionImprove'    => __( 'Die Meta-Beschreibung sollte 120 bis 160 Zeichen lang sein.', 'ps-seo' ),
				'keywordInTitle'        => __( 'Das Fokus-Keyword ist im SEO-Titel enthalten.', 'ps-seo' ),
				'keywordInDescription'  => __( 'Das Fokus-Keyword ist in der Meta-Beschreibung enthalten.', 'ps-seo' ),
				'keywordMissing'        => __( 'Es wurde kein Fokus-Keyword gesetzt.', 'ps-seo' ),
			)
		);
	}

	public static function render( \WP_Post $post ): void {
		$title       = PostMeta::get( $post->ID, PostMeta::TITLE );
		$description = PostMeta::get( $post->ID, PostMeta::DESCRIPTION );
		$keyword     = PostMeta::get( $post->ID, PostMeta::FOCUS_KEYWORD );
		$canonical   = PostMeta::get( $post->ID, PostMeta::CANONICAL );
		$redirect    = PostMeta::get( $post->ID, PostMeta::REDIRECT );
		$robots      = (array) PostMeta::get( $post->ID, PostMeta::ROBOTS, array() );
		$social      = (array) PostMeta::get( $post->ID, PostMeta::SOCIAL, array() );
		$schema      = (array) PostMeta::get( $post->ID, PostMeta::SCHEMA, array() );

		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		?>
		<div class="ps-seo-metabox">
			<div class="ps-seo-tabs" role="tablist">
				<button type="button" class="button is-active" data-ps-seo-tab="search"><?php esc_html_e( 'Suchergebnis', 'ps-seo' ); ?></button>
				<button type="button" class="button" data-ps-seo-tab="social"><?php esc_html_e( 'Social Media', 'ps-seo' ); ?></button>
				<button type="button" class="button" data-ps-seo-tab="advanced"><?php esc_html_e( 'Erweitert', 'ps-seo' ); ?></button>
			</div>

			<section data-ps-seo-panel="search">
				<label class="ps-seo-field">
					<span><?php esc_html_e( 'Fokus-Keyword', 'ps-seo' ); ?></span>
					<input type="text" class="widefat" name="ps_seo[focus_keyword]" value="<?php echo esc_attr( $keyword ); ?>" data-ps-seo-keyword>
				</label>
				<label class="ps-seo-field">
					<span><?php esc_html_e( 'SEO-Titel', 'ps-seo' ); ?></span>
					<input type="text" class="widefat" name="ps_seo[title]" value="<?php echo esc_attr( $title ); ?>" data-ps-seo-title>
					<small><span data-ps-seo-title-count><?php echo esc_html( self::length( $title ) ); ?></span>/60</small>
				</label>
				<label class="ps-seo-field">
					<span><?php esc_html_e( 'Meta-Beschreibung', 'ps-seo' ); ?></span>
					<textarea class="widefat" rows="3" name="ps_seo[description]" data-ps-seo-description><?php echo esc_textarea( $description ); ?></textarea>
					<small><span data-ps-seo-description-count><?php echo esc_html( self::length( $description ) ); ?></span>/160</small>
				</label>
				<?php if ( Settings::enabled( 'analysis' ) ) : ?>
					<div class="ps-seo-analysis" data-ps-seo-analysis>
						<strong><?php esc_html_e( 'SEO-Analyse', 'ps-seo' ); ?></strong>
						<span class="ps-seo-score" data-ps-seo-score>0</span>
						<ul data-ps-seo-analysis-list></ul>
					</div>
				<?php endif; ?>
			</section>

			<section data-ps-seo-panel="social" hidden>
				<label class="ps-seo-field">
					<span><?php esc_html_e( 'Social-Media-Titel', 'ps-seo' ); ?></span>
					<input type="text" class="widefat" name="ps_seo[social][title]" value="<?php echo esc_attr( $social['title'] ?? '' ); ?>">
				</label>
				<label class="ps-seo-field">
					<span><?php esc_html_e( 'Social-Media-Beschreibung', 'ps-seo' ); ?></span>
					<textarea class="widefat" rows="3" name="ps_seo[social][description]"><?php echo esc_textarea( $social['description'] ?? '' ); ?></textarea>
				</label>
				<label class="ps-seo-field">
					<span><?php esc_html_e( 'Social-Media-Bild', 'ps-seo' ); ?></span>
					<span class="ps-seo-media-field">
						<input type="number" min="0" name="ps_seo[social][image_id]" value="<?php echo esc_attr( $social['image_id'] ?? 0 ); ?>">
						<button type="button" class="button ps-seo-select-media"><?php esc_html_e( 'Bild wählen', 'ps-seo' ); ?></button>
					</span>
				</label>
			</section>

			<section data-ps-seo-panel="advanced" hidden>
				<label class="ps-seo-field">
					<span><?php esc_html_e( 'Canonical URL', 'ps-seo' ); ?></span>
					<input type="url" class="widefat" name="ps_seo[canonical]" value="<?php echo esc_attr( $canonical ); ?>" placeholder="<?php echo esc_attr( get_permalink( $post ) ); ?>">
				</label>
				<label class="ps-seo-field">
					<span><?php esc_html_e( 'Weiterleitungsziel', 'ps-seo' ); ?></span>
					<input type="url" class="widefat" name="ps_seo[redirect]" value="<?php echo esc_attr( $redirect ); ?>">
				</label>
				<div class="ps-seo-field">
					<span><?php esc_html_e( 'Robots', 'ps-seo' ); ?></span>
					<?php foreach ( array( 'noindex', 'nofollow', 'noarchive', 'nosnippet' ) as $directive ) : ?>
						<label><input type="checkbox" name="ps_seo[robots][<?php echo esc_attr( $directive ); ?>]" value="1" <?php checked( ! empty( $robots[ $directive ] ) ); ?>> <?php echo esc_html( $directive ); ?></label>
					<?php endforeach; ?>
				</div>
				<label class="ps-seo-field">
					<span><?php esc_html_e( 'Schema-Typ', 'ps-seo' ); ?></span>
					<select name="ps_seo[schema][type]">
						<option value=""><?php esc_html_e( 'Automatisch', 'ps-seo' ); ?></option>
						<option value="Article" <?php selected( $schema['type'] ?? '', 'Article' ); ?>>Article</option>
						<option value="WebPage" <?php selected( $schema['type'] ?? '', 'WebPage' ); ?>>WebPage</option>
						<option value="none" <?php selected( $schema['type'] ?? '', 'none' ); ?>><?php esc_html_e( 'Kein Seitenschema', 'ps-seo' ); ?></option>
					</select>
				</label>
			</section>
		</div>
		<?php
	}

	public static function save( int $post_id ): void {
		if (
			defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE
			|| wp_is_post_revision( $post_id )
			|| ! isset( $_POST[ self::NONCE_NAME ] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION )
			|| ! current_user_can( 'edit_post', $post_id )
			|| ! isset( $_POST['ps_seo'] )
			|| ! is_array( $_POST['ps_seo'] )
		) {
			return;
		}

		$input = wp_unslash( $_POST['ps_seo'] );

		self::save_string( $post_id, PostMeta::TITLE, sanitize_text_field( $input['title'] ?? '' ) );
		self::save_string( $post_id, PostMeta::DESCRIPTION, sanitize_textarea_field( $input['description'] ?? '' ) );
		self::save_string( $post_id, PostMeta::FOCUS_KEYWORD, sanitize_text_field( $input['focus_keyword'] ?? '' ) );
		self::save_string( $post_id, PostMeta::CANONICAL, esc_url_raw( $input['canonical'] ?? '' ) );
		self::save_string( $post_id, PostMeta::REDIRECT, esc_url_raw( $input['redirect'] ?? '' ) );

		$robots = array();
		foreach ( array( 'noindex', 'nofollow', 'noarchive', 'nosnippet' ) as $directive ) {
			$robots[ $directive ] = ! empty( $input['robots'][ $directive ] );
		}
		update_post_meta( $post_id, PostMeta::ROBOTS, $robots );
		if ( $robots['noindex'] ) {
			update_post_meta( $post_id, PostMeta::NOINDEX, '1' );
		} else {
			delete_post_meta( $post_id, PostMeta::NOINDEX );
		}

		$social = array(
			'title'       => sanitize_text_field( $input['social']['title'] ?? '' ),
			'description' => sanitize_textarea_field( $input['social']['description'] ?? '' ),
			'image_id'    => absint( $input['social']['image_id'] ?? 0 ),
		);
		update_post_meta( $post_id, PostMeta::SOCIAL, $social );

		$allowed_schema = array( '', 'Article', 'WebPage', 'none' );
		$schema_type    = sanitize_text_field( $input['schema']['type'] ?? '' );
		update_post_meta(
			$post_id,
			PostMeta::SCHEMA,
			array( 'type' => in_array( $schema_type, $allowed_schema, true ) ? $schema_type : '' )
		);
	}

	private static function save_string( int $post_id, string $key, string $value ): void {
		if ( '' === $value ) {
			delete_post_meta( $post_id, $key );
			return;
		}

		update_post_meta( $post_id, $key, $value );
	}

	private static function length( string $value ): int {
		return function_exists( 'mb_strlen' ) ? mb_strlen( $value ) : strlen( $value );
	}
}
