<?php

namespace PSource\SEO\Admin;

use PSource\SEO\Settings;

final class SettingsPage {

	public static function register(): void {
		add_action( 'admin_menu', array( self::class, 'add_menu' ) );
		add_action( 'admin_init', array( self::class, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
	}

	public static function add_menu(): void {
		add_menu_page(
			__( 'PS SEO', 'ps-seo' ),
			__( 'PS SEO', 'ps-seo' ),
			'manage_options',
			'ps-seo',
			array( self::class, 'render' ),
			'dashicons-chart-area',
			58
		);
	}

	public static function register_settings(): void {
		register_setting(
			'ps_seo',
			Settings::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( Settings::class, 'sanitize' ),
				'default'           => Settings::defaults(),
			)
		);
	}

	public static function enqueue_assets( string $hook ): void {
		if ( 'toplevel_page_ps-seo' !== $hook ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_style( 'ps-seo-admin', PS_SEO_URL . 'assets/css/admin.css', array(), PS_SEO_VERSION );
		wp_enqueue_script( 'ps-seo-admin', PS_SEO_URL . 'assets/js/admin.js', array(), PS_SEO_VERSION, true );
	}

	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = Settings::all();
		?>
		<div class="wrap ps-seo-settings">
			<h1><?php esc_html_e( 'PS SEO', 'ps-seo' ); ?></h1>
			<p><?php esc_html_e( 'Zentrale Einstellungen für Metadaten, Social Sharing, strukturierte Daten und Sitemaps.', 'ps-seo' ); ?></p>

			<form action="options.php" method="post">
				<?php settings_fields( 'ps_seo' ); ?>

				<div class="ps-seo-card">
					<h2><?php esc_html_e( 'Module', 'ps-seo' ); ?></h2>
					<div class="ps-seo-feature-grid">
						<?php
						$features = array(
							'metadata'  => __( 'Titel und Metadaten', 'ps-seo' ),
							'social'    => __( 'Open Graph und Twitter Cards', 'ps-seo' ),
							'schema'    => __( 'Schema.org', 'ps-seo' ),
							'sitemap'   => __( 'XML-Sitemaps', 'ps-seo' ),
							'redirects' => __( 'Weiterleitungen', 'ps-seo' ),
							'analysis'  => __( 'Inhaltsanalyse', 'ps-seo' ),
						);

						foreach ( $features as $key => $label ) :
							?>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( Settings::OPTION ); ?>[features][<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( ! empty( $settings['features'][ $key ] ) ); ?>>
								<?php echo esc_html( $label ); ?>
							</label>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="ps-seo-card">
					<h2><?php esc_html_e( 'Titelvorlagen', 'ps-seo' ); ?></h2>
					<p class="description">
						<?php esc_html_e( 'Verfügbare Platzhalter:', 'ps-seo' ); ?>
						<code>%title%, %site_name%, %tagline%, %separator%, %search%, %archive%, %author%, %current_year%</code>
					</p>
					<?php
					$contexts = array(
						'home'    => __( 'Startseite', 'ps-seo' ),
						'post'    => __( 'Beiträge', 'ps-seo' ),
						'page'    => __( 'Seiten', 'ps-seo' ),
						'archive' => __( 'Archive', 'ps-seo' ),
						'search'  => __( 'Suche', 'ps-seo' ),
						'404'     => __( '404-Seite', 'ps-seo' ),
					);

					foreach ( $contexts as $key => $label ) :
						?>
						<label class="ps-seo-field">
							<span><?php echo esc_html( $label ); ?></span>
							<input type="text" class="regular-text" name="<?php echo esc_attr( Settings::OPTION ); ?>[titles][<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $settings['titles'][ $key ] ); ?>">
						</label>
					<?php endforeach; ?>
				</div>

				<div class="ps-seo-card">
					<h2><?php esc_html_e( 'Social Media', 'ps-seo' ); ?></h2>
					<label class="ps-seo-field">
						<span><?php esc_html_e( 'Standardbild', 'ps-seo' ); ?></span>
						<span class="ps-seo-media-field">
							<input type="number" min="0" name="<?php echo esc_attr( Settings::OPTION ); ?>[social][default_image_id]" value="<?php echo esc_attr( $settings['social']['default_image_id'] ); ?>">
							<button type="button" class="button ps-seo-select-media"><?php esc_html_e( 'Bild wählen', 'ps-seo' ); ?></button>
						</span>
					</label>
					<label class="ps-seo-field">
						<span><?php esc_html_e( 'Twitter-Benutzername', 'ps-seo' ); ?></span>
						<input type="text" class="regular-text" name="<?php echo esc_attr( Settings::OPTION ); ?>[social][twitter_username]" value="<?php echo esc_attr( $settings['social']['twitter_username'] ); ?>" placeholder="@psource">
					</label>
					<label class="ps-seo-field">
						<span><?php esc_html_e( 'Twitter-Card', 'ps-seo' ); ?></span>
						<select name="<?php echo esc_attr( Settings::OPTION ); ?>[social][twitter_card]">
							<option value="summary_large_image" <?php selected( $settings['social']['twitter_card'], 'summary_large_image' ); ?>>summary_large_image</option>
							<option value="summary" <?php selected( $settings['social']['twitter_card'], 'summary' ); ?>>summary</option>
						</select>
					</label>
					<label class="ps-seo-field">
						<span><?php esc_html_e( 'Facebook App-ID', 'ps-seo' ); ?></span>
						<input type="text" class="regular-text" name="<?php echo esc_attr( Settings::OPTION ); ?>[social][facebook_app_id]" value="<?php echo esc_attr( $settings['social']['facebook_app_id'] ); ?>">
					</label>
				</div>

				<div class="ps-seo-card">
					<h2><?php esc_html_e( 'Organisation und Schema', 'ps-seo' ); ?></h2>
					<label class="ps-seo-field">
						<span><?php esc_html_e( 'Betreibertyp', 'ps-seo' ); ?></span>
						<select name="<?php echo esc_attr( Settings::OPTION ); ?>[schema][owner_type]">
							<option value="Organization" <?php selected( $settings['schema']['owner_type'], 'Organization' ); ?>>Organization</option>
							<option value="Person" <?php selected( $settings['schema']['owner_type'], 'Person' ); ?>>Person</option>
						</select>
					</label>
					<label class="ps-seo-field">
						<span><?php esc_html_e( 'Name', 'ps-seo' ); ?></span>
						<input type="text" class="regular-text" name="<?php echo esc_attr( Settings::OPTION ); ?>[schema][organization]" value="<?php echo esc_attr( $settings['schema']['organization'] ); ?>">
					</label>
					<label class="ps-seo-field">
						<span><?php esc_html_e( 'Logo', 'ps-seo' ); ?></span>
						<span class="ps-seo-media-field">
							<input type="number" min="0" name="<?php echo esc_attr( Settings::OPTION ); ?>[schema][logo_id]" value="<?php echo esc_attr( $settings['schema']['logo_id'] ); ?>">
							<button type="button" class="button ps-seo-select-media"><?php esc_html_e( 'Logo wählen', 'ps-seo' ); ?></button>
						</span>
					</label>
					<label class="ps-seo-field">
						<span><?php esc_html_e( 'Telefon', 'ps-seo' ); ?></span>
						<input type="text" class="regular-text" name="<?php echo esc_attr( Settings::OPTION ); ?>[schema][contact_phone]" value="<?php echo esc_attr( $settings['schema']['contact_phone'] ); ?>">
					</label>
					<label class="ps-seo-field">
						<span><?php esc_html_e( 'E-Mail', 'ps-seo' ); ?></span>
						<input type="email" class="regular-text" name="<?php echo esc_attr( Settings::OPTION ); ?>[schema][contact_email]" value="<?php echo esc_attr( $settings['schema']['contact_email'] ); ?>">
					</label>
					<label class="ps-seo-field">
						<span><?php esc_html_e( 'Social-Profile', 'ps-seo' ); ?></span>
						<textarea class="large-text" rows="4" name="<?php echo esc_attr( Settings::OPTION ); ?>[schema][social_profiles]"><?php echo esc_textarea( $settings['schema']['social_profiles'] ); ?></textarea>
						<small><?php esc_html_e( 'Eine vollständige URL pro Zeile.', 'ps-seo' ); ?></small>
					</label>
				</div>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
