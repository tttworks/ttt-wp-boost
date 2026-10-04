=== TTT WP Boost ===
Contributors: aloysius
Tags: performance, caching, speed, optimization, page cache, object cache
Requires at least: 5.0
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Lightweight page cache and dashboard accelerator for Elementor-powered WordPress sites. Boosts PageSpeed by 20-30 points with minimal configuration.

== Description ==

TTT WP Boost provides three core performance optimizations:

* **Page Cache** — Static HTML caching for non-logged-in visitors. Caches are served instantly from the filesystem, reducing server load and improving response times.

* **Object Cache** — Three-tier fallback architecture (Redis → Memcached → WordPress Transients). Compatible with any hosting environment, even without Redis or Memcached installed.

* **Dashboard Accelerator** — WordPress native optimizations for the admin area. Disables Gutenberg block editor, slows Heartbeat API, removes unused resources, and reduces database bloat (post revisions).

Each optimization has its own admin settings page with real-time statistics and status indicators.

== Installation ==

1. Upload the `ttt-wp-boost` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to *Settings → TTT WP Boost* to configure
4. Enable the features you need from the three sub-pages

== Frequently Asked Questions ==

= Does this work with all hosting providers? =

Yes. The object cache automatically falls back to WordPress Transients if Redis or Memcached are not available. Page cache uses standard filesystem operations compatible with all hosts.

= Will this conflict with other caching plugins? =

Yes. Deactivate any other caching plugins (W3 Total Cache, WP Rocket, etc.) before using TTT WP Boost.

= Can I use this on a multisite network? =

Yes. Activate the plugin network-wide or per-site. Note that cache directories are per-site.

= What about Elementor? =

Page cache works with Elementor sites out of the box. Object cache can optionally cache Elementor dynamic CSS to reduce TTFB. Dashboard Accelerator's Gutenberg optimizations do not affect Elementor pages (they use their own editor).

= How do I clear the cache? =

Go to *Settings → TTT WP Boost* and click "Clear All Cache". The plugin also automatically clears cache when posts are updated or published.

== Changelog ==

= 1.1.0 =
* Added Object Cache with three-tier backend support (Redis → Memcached → Transients)
* Added Dashboard Accelerator with real-time status indicators
* Added Speed Badge (floating frontend performance indicator)
* Restructured admin menu with three sub-pages
* Fixed memory usage calculation in Speed Badge
* Fixed object cache statistics persistence across requests

= 1.0.0 =
* Initial release with Page Cache engine

== Upgrade Notice ==

= 1.1.0 =
If you are upgrading from 1.0.0, please review the new Object Cache and Dashboard Accelerator settings. The admin menu structure has changed — three sub-pages are now available under Settings → TTT WP Boost.

== Screenshots ==

1. Page Cache settings with real-time statistics
2. Dashboard Accelerator status and optimization options
3. Object Cache backend detection and performance metrics

== Other Notes ==

Cache files are stored in `/wp-content/cache/ttt-wp-boost/` with `.htaccess` protection (Apache). For Nginx, ensure this directory is not accessible directly.

== Credits ==

Plugin developed by Aloysius Luo / TTTWorks.