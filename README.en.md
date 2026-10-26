# PS SEO

[Deutsch](README.md) | **English**

[![Version](https://img.shields.io/badge/Version-1.0.0-2271b1?style=flat-square)](readme.txt)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-777bb4?style=flat-square&logo=php&logoColor=white)
![ClassicPress](https://img.shields.io/badge/ClassicPress-2.7.4-03768e?style=flat-square)
[![License](https://img.shields.io/badge/License-GPL--2.0--or--later-2ea44f?style=flat-square)](https://www.gnu.org/licenses/gpl-2.0.html)

Modern SEO tools for ClassicPress: PS SEO manages metadata, social media previews, structured data, redirects, and sitemaps in one lean, modular plugin.

## Features

- SEO titles and meta descriptions for content and taxonomy archives
- custom canonical URLs plus `noindex`, `nofollow`, `noarchive`, and `nosnippet` controls
- configurable title templates for the homepage, posts, pages, archives, search results, and 404 pages
- Open Graph metadata and Twitter Cards with image fallbacks
- a central JSON-LD graph for the website, publisher, breadcrumbs, and articles
- Product, Offer, and ProductGroup schema for MarketPress products and variations
- prices, currencies, availability, and SKUs sourced from MarketPress
- optional product performance data from PS Stats
- exclusion of noindex content, noindex taxonomies, and redirected content from sitemaps
- sitemap at `/ps-sitemap.xml` with an automatic `robots.txt` entry
- an SEO meta box for every public content type and SEO fields for taxonomy archives
- simple live content analysis in the editor
- English translations for the administration interface and content analysis

## Requirements

- ClassicPress 2.7 or later
- PHP 8.0 or later

MarketPress and PS Stats are optional. Their respective schema and performance data are used only when the corresponding plugins are active.

## Installation

1. Upload the plugin directory to `wp-content/plugins/`, or install it through the ClassicPress plugin manager.
2. Activate **PS SEO** on the **Plugins** screen.
3. Open **Settings → PS SEO** to configure features, title templates, social media details, and schema data.
4. Edit content or taxonomy archives to add individual SEO details.

After activation, the sitemap is available at `https://your-domain.tld/ps-sitemap.xml`.

## Configuration

### Global settings

Under **Settings → PS SEO**, you can enable or disable individual feature areas and configure:

- title templates using placeholders such as `%site_name%`, `%separator%`, `%title%`, `%archive%`, and `%search%`
- a default image, Twitter username, Twitter Card type, and Facebook App ID
- the publisher as an organization or person, plus its logo, contact details, and social profiles

### SEO meta box

The meta box is available for public content types. It provides individual controls for title, description, canonical URL, robots directives, social media previews, and redirects.

### Sitemap

PS SEO extends the ClassicPress core sitemap and publishes it at `/ps-sitemap.xml`. Content, taxonomy terms, and redirects marked `noindex` are excluded from it.

## Development

Run the JavaScript tests with:

```bash
npm run test:js
```

## License

PS SEO is released under the [GNU General Public License, version 2 or later](https://www.gnu.org/licenses/gpl-2.0.html).
