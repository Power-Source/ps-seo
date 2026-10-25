<?php

namespace PSource\SEO;

use PSource\SEO\Admin\MetaBox;
use PSource\SEO\Admin\SettingsPage;
use PSource\SEO\Frontend\Metadata;
use PSource\SEO\Frontend\Redirect;
use PSource\SEO\Frontend\Social;
use PSource\SEO\Schema\SchemaGraph;
use PSource\SEO\Sitemap\Sitemap;

final class Plugin {

	private static bool $booted = false;

	public static function boot(): void {
		if ( self::$booted ) {
			return;
		}

		self::$booted = true;

		PostMeta::register();
		Metadata::register();
		Redirect::register();
		Social::register();
		SchemaGraph::register();
		Sitemap::register();

		if ( is_admin() ) {
			MetaBox::register();
			SettingsPage::register();
		}
	}
}
