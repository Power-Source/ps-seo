<?php

namespace PSource\SEO\Admin;

use PSource\SEO\Settings;

final class SettingsPage {

	public static function register(): void {
		add_action( 'admin_menu', array( self::class, 'add_menu' ) );
		add_action( 'admin_init', array( self::class, 'register_settings' ) );
		add_action( 'admin_init', array( self::class, 'redirect_legacy_url' ), 1 );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
	}

	public static function redirect_legacy_url(): void {
		global $pagenow;

		$page = isset( $_GET['page'] ) && is_string( $_GET['page'] )
			? sanitize_key( wp_unslash( $_GET['page'] ) )
			: '';

		if ( 'options-general.php' !== $pagenow || 'ps-seo' !== $page ) {
			return;
		}

		wp_safe_redirect( add_query_arg( 'page', 'ps-seo', admin_url( 'admin.php' ) ) );
		exit;
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

		$settings          = Settings::all();
		$sitemap_available = ! empty( $settings['features']['sitemap'] ) && (bool) get_option( 'blog_public' );
		?>
		<div class="wrap ps-seo-settings">
			<header class="ps-seo-page-header">
				<div>
					<span class="ps-seo-eyebrow"><?php esc_html_e( 'Suchmaschinenoptimierung', 'ps-seo' ); ?></span>
					<h1><?php esc_html_e( 'PS SEO', 'ps-seo' ); ?> <span>v<?php echo esc_html( PS_SEO_VERSION ); ?></span></h1>
					<p><?php esc_html_e( 'Steuere Suchdarstellung, Social Sharing, strukturierte Daten und Indexierung zentral an einem Ort.', 'ps-seo' ); ?></p>
				</div>
				<?php if ( $sitemap_available ) : ?>
					<a class="button button-secondary" href="<?php echo esc_url( home_url( '/ps-sitemap.xml' ) ); ?>" target="_blank" rel="noopener">
						<span class="dashicons dashicons-external"></span>
						<?php esc_html_e( 'Sitemap öffnen', 'ps-seo' ); ?>
					</a>
				<?php else : ?>
					<span class="button button-secondary disabled" aria-disabled="true">
						<span class="dashicons dashicons-hidden"></span>
						<?php esc_html_e( 'Sitemap nicht verfügbar', 'ps-seo' ); ?>
					</span>
				<?php endif; ?>
			</header>

			<form action="options.php" method="post" class="ps-seo-settings-form">
				<?php settings_fields( 'ps_seo' ); ?>

				<nav class="ps-seo-settings-nav" aria-label="<?php esc_attr_e( 'PS SEO Bereiche', 'ps-seo' ); ?>">
					<?php
					$tabs = array(
						'overview' => array( 'dashicons-dashboard', __( 'Übersicht', 'ps-seo' ) ),
						'titles'   => array( 'dashicons-editor-textcolor', __( 'Titel & Meta', 'ps-seo' ) ),
						'social'   => array( 'dashicons-share', __( 'Social Media', 'ps-seo' ) ),
						'schema'   => array( 'dashicons-networking', __( 'Schema', 'ps-seo' ) ),
						'sitemap'  => array( 'dashicons-networking', __( 'Sitemap', 'ps-seo' ) ),
						'advanced' => array( 'dashicons-admin-tools', __( 'Erweitert', 'ps-seo' ) ),
					);

					foreach ( $tabs as $key => $tab ) :
						?>
						<button type="button" class="<?php echo 'overview' === $key ? 'is-active' : ''; ?>" data-ps-seo-settings-tab="<?php echo esc_attr( $key ); ?>">
							<span class="dashicons <?php echo esc_attr( $tab[0] ); ?>"></span>
							<?php echo esc_html( $tab[1] ); ?>
						</button>
					<?php endforeach; ?>
				</nav>

				<main class="ps-seo-settings-content">
					<section data-ps-seo-settings-panel="overview">
						<?php self::render_overview( $settings ); ?>
					</section>

					<section data-ps-seo-settings-panel="titles" hidden>
						<?php self::render_titles( $settings ); ?>
					</section>

					<section data-ps-seo-settings-panel="social" hidden>
						<?php self::render_social( $settings ); ?>
					</section>

					<section data-ps-seo-settings-panel="schema" hidden>
						<?php self::render_schema( $settings ); ?>
					</section>

					<section data-ps-seo-settings-panel="sitemap" hidden>
						<?php self::render_sitemap( $settings ); ?>
					</section>

					<section data-ps-seo-settings-panel="advanced" hidden>
						<?php self::render_advanced(); ?>
					</section>
				</main>

				<footer class="ps-seo-settings-footer">
					<span><?php esc_html_e( 'Änderungen gelten unmittelbar nach dem Speichern.', 'ps-seo' ); ?></span>
					<?php submit_button( __( 'SEO-Einstellungen speichern', 'ps-seo' ), 'primary', 'submit', false ); ?>
				</footer>
			</form>
		</div>
		<?php
	}

	private static function render_overview( array $settings ): void {
		$is_public = (bool) get_option( 'blog_public' );
		$features = array(
			'metadata'  => array(
				'dashicons-editor-textcolor',
				__( 'Titel und Metadaten', 'ps-seo' ),
				__( 'SEO-Titel, Beschreibungen, Canonicals und Robots-Direktiven.', 'ps-seo' ),
			),
			'social'    => array(
				'dashicons-share',
				__( 'Social Sharing', 'ps-seo' ),
				__( 'Open Graph und Twitter Cards mit intelligenten Bild-Fallbacks.', 'ps-seo' ),
			),
			'schema'    => array(
				'dashicons-networking',
				__( 'Strukturierte Daten', 'ps-seo' ),
				__( 'Ein zentraler JSON-LD-Graph für Website, Betreiber, Inhalte und Breadcrumbs.', 'ps-seo' ),
			),
			'sitemap'   => array(
				'dashicons-media-spreadsheet',
				__( 'XML-Sitemap', 'ps-seo' ),
				__( 'ClassicPress-Core-Sitemap ohne noindex- und weitergeleitete Inhalte.', 'ps-seo' ),
			),
			'redirects' => array(
				'dashicons-randomize',
				__( 'Weiterleitungen', 'ps-seo' ),
				__( 'Sichere permanente Weiterleitungen direkt am jeweiligen Inhalt.', 'ps-seo' ),
			),
			'analysis'  => array(
				'dashicons-chart-line',
				__( 'Inhaltsanalyse', 'ps-seo' ),
				__( 'Direktes Feedback zu Titel, Beschreibung und Fokus-Keyword.', 'ps-seo' ),
			),
		);
		?>
		<div class="ps-seo-section-heading">
			<div>
				<h2><?php esc_html_e( 'SEO-Module', 'ps-seo' ); ?></h2>
				<p><?php esc_html_e( 'Aktiviere nur die Funktionen, die PS SEO für diese Website übernehmen soll.', 'ps-seo' ); ?></p>
			</div>
			<span class="ps-seo-health <?php echo $is_public ? 'is-good' : ''; ?>"><span></span>
				<?php echo $is_public ? esc_html__( 'System bereit', 'ps-seo' ) : esc_html__( 'Indexierung blockiert', 'ps-seo' ); ?>
			</span>
		</div>

		<div class="ps-seo-module-grid">
			<?php foreach ( $features as $key => $feature ) : ?>
				<article class="ps-seo-module-card <?php echo ! empty( $settings['features'][ $key ] ) ? 'is-enabled' : ''; ?>">
					<div class="ps-seo-module-icon"><span class="dashicons <?php echo esc_attr( $feature[0] ); ?>"></span></div>
					<div class="ps-seo-module-copy">
						<h3><?php echo esc_html( $feature[1] ); ?></h3>
						<p><?php echo esc_html( $feature[2] ); ?></p>
					</div>
					<label class="ps-seo-switch">
						<input type="checkbox" name="<?php echo esc_attr( Settings::OPTION ); ?>[features][<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( ! empty( $settings['features'][ $key ] ) ); ?>>
						<span aria-hidden="true"></span>
						<span class="screen-reader-text"><?php echo esc_html( $feature[1] ); ?></span>
					</label>
				</article>
			<?php endforeach; ?>
		</div>

		<div class="ps-seo-insight-grid">
			<div class="ps-seo-insight">
				<strong><?php esc_html_e( 'ClassicPress-native Ausgabe', 'ps-seo' ); ?></strong>
				<p><?php esc_html_e( 'Keine Titel-Pufferung und keine parallele Sitemap-Engine. PS SEO erweitert die aktuellen ClassicPress-APIs.', 'ps-seo' ); ?></p>
			</div>
			<div class="ps-seo-insight">
				<strong><?php esc_html_e( 'Pro Inhalt steuerbar', 'ps-seo' ); ?></strong>
				<p><?php esc_html_e( 'Individuelle Such-, Social-, Robots-, Canonical-, Schema- und Redirect-Einstellungen findest du direkt im Editor.', 'ps-seo' ); ?></p>
			</div>
		</div>
		<?php
	}

	private static function render_titles( array $settings ): void {
		$contexts = array(
			'home'    => array( __( 'Startseite', 'ps-seo' ), __( 'Titel der Website oder statischen Startseite.', 'ps-seo' ) ),
			'post'    => array( __( 'Beiträge', 'ps-seo' ), __( 'Standardvorlage für einzelne Beiträge.', 'ps-seo' ) ),
			'page'    => array( __( 'Seiten', 'ps-seo' ), __( 'Standardvorlage für statische Seiten.', 'ps-seo' ) ),
			'archive' => array( __( 'Archive', 'ps-seo' ), __( 'Kategorien, Schlagwörter, Autoren und weitere Archive.', 'ps-seo' ) ),
			'search'  => array( __( 'Suche', 'ps-seo' ), __( 'Titel für interne Suchergebnisse.', 'ps-seo' ) ),
			'404'     => array( __( '404-Seite', 'ps-seo' ), __( 'Titel für nicht gefundene Inhalte.', 'ps-seo' ) ),
		);
		?>
		<div class="ps-seo-section-heading">
			<div>
				<h2><?php esc_html_e( 'Titelvorlagen', 'ps-seo' ); ?></h2>
				<p><?php esc_html_e( 'Definiere konsistente Standardtitel. Individuelle SEO-Titel überschreiben diese Vorlagen.', 'ps-seo' ); ?></p>
			</div>
		</div>

		<div class="ps-seo-token-bar">
			<strong><?php esc_html_e( 'Platzhalter', 'ps-seo' ); ?></strong>
			<?php foreach ( array( '%title%', '%site_name%', '%tagline%', '%separator%', '%search%', '%archive%', '%author%', '%current_year%' ) as $token ) : ?>
				<code><?php echo esc_html( $token ); ?></code>
			<?php endforeach; ?>
		</div>

		<div class="ps-seo-card">
			<?php foreach ( $contexts as $key => $context ) : ?>
				<label class="ps-seo-template-row">
					<span>
						<strong><?php echo esc_html( $context[0] ); ?></strong>
						<small><?php echo esc_html( $context[1] ); ?></small>
					</span>
					<input type="text" name="<?php echo esc_attr( Settings::OPTION ); ?>[titles][<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $settings['titles'][ $key ] ); ?>">
				</label>
			<?php endforeach; ?>
		</div>
		<?php
	}

	private static function render_social( array $settings ): void {
		$image_id  = absint( $settings['social']['default_image_id'] );
		$image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : '';
		?>
		<div class="ps-seo-section-heading">
			<div>
				<h2><?php esc_html_e( 'Social Media', 'ps-seo' ); ?></h2>
				<p><?php esc_html_e( 'Lege globale Fallbacks für Facebook, LinkedIn, WhatsApp, X und weitere Dienste fest.', 'ps-seo' ); ?></p>
			</div>
		</div>

		<div class="ps-seo-two-column">
			<div class="ps-seo-card">
				<h3><?php esc_html_e( 'Globale Vorgaben', 'ps-seo' ); ?></h3>
				<label class="ps-seo-field">
					<span><?php esc_html_e( 'Standardbild', 'ps-seo' ); ?></span>
					<small><?php esc_html_e( 'Wird verwendet, wenn weder ein individuelles Social-Bild noch ein Beitragsbild existiert.', 'ps-seo' ); ?></small>
					<span class="ps-seo-media-field">
						<input type="number" min="0" name="<?php echo esc_attr( Settings::OPTION ); ?>[social][default_image_id]" value="<?php echo esc_attr( $image_id ); ?>">
						<button type="button" class="button ps-seo-select-media"><?php esc_html_e( 'Bild wählen', 'ps-seo' ); ?></button>
					</span>
				</label>
				<label class="ps-seo-field">
					<span><?php esc_html_e( 'Twitter-/X-Benutzername', 'ps-seo' ); ?></span>
					<input type="text" name="<?php echo esc_attr( Settings::OPTION ); ?>[social][twitter_username]" value="<?php echo esc_attr( $settings['social']['twitter_username'] ); ?>" placeholder="@psource">
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
					<input type="text" name="<?php echo esc_attr( Settings::OPTION ); ?>[social][facebook_app_id]" value="<?php echo esc_attr( $settings['social']['facebook_app_id'] ); ?>">
				</label>
			</div>

			<div class="ps-seo-social-preview">
				<span class="ps-seo-preview-label"><?php esc_html_e( 'Beispielvorschau', 'ps-seo' ); ?></span>
				<div class="ps-seo-preview-image" data-ps-seo-media-preview <?php echo $image_url ? 'style="background-image:url(' . esc_url( $image_url ) . ')"' : ''; ?>>
					<?php if ( ! $image_url ) : ?><span class="dashicons dashicons-format-image"></span><?php endif; ?>
				</div>
				<div class="ps-seo-preview-copy">
					<small><?php echo esc_html( wp_parse_url( home_url(), PHP_URL_HOST ) ); ?></small>
					<strong><?php echo esc_html( get_bloginfo( 'name' ) ); ?></strong>
					<p><?php echo esc_html( get_bloginfo( 'description' ) ); ?></p>
				</div>
			</div>
		</div>
		<?php
	}

	private static function render_schema( array $settings ): void {
		$logo_id  = absint( $settings['schema']['logo_id'] );
		$logo_url = $logo_id ? wp_get_attachment_image_url( $logo_id, 'thumbnail' ) : '';
		?>
		<div class="ps-seo-section-heading">
			<div>
				<h2><?php esc_html_e( 'Organisation und Schema', 'ps-seo' ); ?></h2>
				<p><?php esc_html_e( 'Diese Angaben verbinden Website, Betreiber und Inhalte im zentralen JSON-LD-Graph.', 'ps-seo' ); ?></p>
			</div>
		</div>

		<div class="ps-seo-two-column">
			<div class="ps-seo-card">
				<label class="ps-seo-field">
					<span><?php esc_html_e( 'Betreibertyp', 'ps-seo' ); ?></span>
					<select name="<?php echo esc_attr( Settings::OPTION ); ?>[schema][owner_type]">
						<option value="Organization" <?php selected( $settings['schema']['owner_type'], 'Organization' ); ?>>Organization</option>
						<option value="Person" <?php selected( $settings['schema']['owner_type'], 'Person' ); ?>>Person</option>
					</select>
				</label>
				<label class="ps-seo-field">
					<span><?php esc_html_e( 'Name', 'ps-seo' ); ?></span>
					<input type="text" name="<?php echo esc_attr( Settings::OPTION ); ?>[schema][organization]" value="<?php echo esc_attr( $settings['schema']['organization'] ); ?>">
				</label>
				<label class="ps-seo-field">
					<span><?php esc_html_e( 'Logo', 'ps-seo' ); ?></span>
					<span class="ps-seo-media-field">
						<input type="number" min="0" name="<?php echo esc_attr( Settings::OPTION ); ?>[schema][logo_id]" value="<?php echo esc_attr( $logo_id ); ?>">
						<button type="button" class="button ps-seo-select-media"><?php esc_html_e( 'Logo wählen', 'ps-seo' ); ?></button>
					</span>
				</label>
				<label class="ps-seo-field">
					<span><?php esc_html_e( 'Telefon', 'ps-seo' ); ?></span>
					<input type="text" name="<?php echo esc_attr( Settings::OPTION ); ?>[schema][contact_phone]" value="<?php echo esc_attr( $settings['schema']['contact_phone'] ); ?>">
				</label>
				<label class="ps-seo-field">
					<span><?php esc_html_e( 'E-Mail', 'ps-seo' ); ?></span>
					<input type="email" name="<?php echo esc_attr( Settings::OPTION ); ?>[schema][contact_email]" value="<?php echo esc_attr( $settings['schema']['contact_email'] ); ?>">
				</label>
				<label class="ps-seo-field">
					<span><?php esc_html_e( 'Social-Profile', 'ps-seo' ); ?></span>
					<small><?php esc_html_e( 'Eine vollständige URL pro Zeile.', 'ps-seo' ); ?></small>
					<textarea rows="5" name="<?php echo esc_attr( Settings::OPTION ); ?>[schema][social_profiles]"><?php echo esc_textarea( $settings['schema']['social_profiles'] ); ?></textarea>
				</label>
			</div>

			<div class="ps-seo-schema-summary">
				<div class="ps-seo-owner-logo" data-ps-seo-media-preview <?php echo $logo_url ? 'style="background-image:url(' . esc_url( $logo_url ) . ')"' : ''; ?>>
					<?php if ( ! $logo_url ) : ?><span class="dashicons dashicons-building"></span><?php endif; ?>
				</div>
				<h3><?php echo esc_html( $settings['schema']['organization'] ?: get_bloginfo( 'name' ) ); ?></h3>
				<p><?php echo esc_html( $settings['schema']['owner_type'] ); ?></p>
				<ul>
					<li><span class="dashicons dashicons-yes-alt"></span> WebSite</li>
					<li><span class="dashicons dashicons-yes-alt"></span> <?php echo esc_html( $settings['schema']['owner_type'] ); ?></li>
					<li><span class="dashicons dashicons-yes-alt"></span> WebPage / Article</li>
					<li><span class="dashicons dashicons-yes-alt"></span> BreadcrumbList</li>
				</ul>
			</div>
		</div>
		<?php
	}

	private static function render_sitemap( array $settings ): void {
		$module_enabled = ! empty( $settings['features']['sitemap'] );
		$is_public      = (bool) get_option( 'blog_public' );
		$is_available   = $module_enabled && $is_public;
		$post_types     = get_post_types( array( 'public' => true ), 'objects' );
		unset( $post_types['attachment'] );
		?>
		<div class="ps-seo-section-heading">
			<div>
				<h2><?php esc_html_e( 'XML-Sitemap', 'ps-seo' ); ?></h2>
				<p><?php esc_html_e( 'PS SEO erweitert die ClassicPress-Core-Sitemap, statt eine konkurrierende Sitemap-Engine zu betreiben.', 'ps-seo' ); ?></p>
			</div>
			<span class="ps-seo-health <?php echo $is_available ? 'is-good' : ''; ?>"><span></span>
				<?php
				if ( ! $module_enabled ) {
					esc_html_e( 'Deaktiviert', 'ps-seo' );
				} elseif ( ! $is_public ) {
					esc_html_e( 'Durch Sichtbarkeit blockiert', 'ps-seo' );
				} else {
					esc_html_e( 'Aktiv', 'ps-seo' );
				}
				?>
			</span>
		</div>

		<?php if ( $module_enabled && ! $is_public ) : ?>
			<div class="notice notice-warning inline">
				<p><?php esc_html_e( 'ClassicPress blockiert die Sitemap, solange Suchmaschinen vom Besuch der Website abgehalten werden. Ändere bei Bedarf die Sichtbarkeit unter Einstellungen > Lesen.', 'ps-seo' ); ?></p>
			</div>
		<?php endif; ?>

		<div class="ps-seo-card">
			<div class="ps-seo-sitemap-url">
				<div>
					<small><?php esc_html_e( 'Sitemap-Index', 'ps-seo' ); ?></small>
					<code><?php echo esc_html( home_url( '/ps-sitemap.xml' ) ); ?></code>
				</div>
				<?php if ( $is_available ) : ?>
					<a class="button" href="<?php echo esc_url( home_url( '/ps-sitemap.xml' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Öffnen', 'ps-seo' ); ?></a>
				<?php else : ?>
					<span class="button disabled" aria-disabled="true"><?php esc_html_e( 'Nicht verfügbar', 'ps-seo' ); ?></span>
				<?php endif; ?>
			</div>

			<h3><?php esc_html_e( 'Enthaltene Inhaltstypen', 'ps-seo' ); ?></h3>
			<div class="ps-seo-content-types">
				<?php foreach ( $post_types as $post_type ) : ?>
					<span><span class="dashicons dashicons-yes"></span><?php echo esc_html( $post_type->labels->name ); ?></span>
				<?php endforeach; ?>
			</div>

			<div class="ps-seo-notice">
				<span class="dashicons dashicons-filter"></span>
				<p><strong><?php esc_html_e( 'Automatische Filterung', 'ps-seo' ); ?></strong><br><?php esc_html_e( 'Inhalte mit noindex oder einem Weiterleitungsziel werden automatisch aus der Sitemap ausgeschlossen.', 'ps-seo' ); ?></p>
			</div>
		</div>
		<?php
	}

	private static function render_advanced(): void {
		?>
		<div class="ps-seo-section-heading">
			<div>
				<h2><?php esc_html_e( 'Erweiterte Werkzeuge', 'ps-seo' ); ?></h2>
				<p><?php esc_html_e( 'Technische Funktionen und Hinweise für besondere SEO-Anforderungen.', 'ps-seo' ); ?></p>
			</div>
		</div>

		<div class="ps-seo-tool-grid">
			<div class="ps-seo-card">
				<span class="dashicons dashicons-randomize"></span>
				<h3><?php esc_html_e( '301-Weiterleitungen', 'ps-seo' ); ?></h3>
				<p><?php esc_html_e( 'Ein Weiterleitungsziel kann in der PS-SEO-Metabox jedes öffentlichen Inhalts hinterlegt werden. Identische und ungültige Ziele werden ignoriert.', 'ps-seo' ); ?></p>
			</div>
			<div class="ps-seo-card">
				<span class="dashicons dashicons-privacy"></span>
				<h3><?php esc_html_e( 'Robots-Steuerung', 'ps-seo' ); ?></h3>
				<p><?php esc_html_e( 'noindex, nofollow, noarchive und nosnippet werden über die ClassicPress Robots API ausgegeben.', 'ps-seo' ); ?></p>
			</div>
			<div class="ps-seo-card">
				<span class="dashicons dashicons-editor-code"></span>
				<h3><?php esc_html_e( 'Saubere Ausgabe', 'ps-seo' ); ?></h3>
				<p><?php esc_html_e( 'PS SEO verwendet keine Output-Pufferung und überschreibt keine Theme-Dateien. Alle Ausgaben laufen über dokumentierte ClassicPress-Hooks.', 'ps-seo' ); ?></p>
			</div>
		</div>
		<?php
	}
}
