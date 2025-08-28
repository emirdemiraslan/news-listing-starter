=== News Listing Shortcode ===
Contributors: you
Tags: news, posts, shortcode, carousel, grid
Requires at least: 4.9.8
Tested up to: 4.9.8
Requires PHP: 7.2.1
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Configurable news listings via [news_listing] shortcode with grid or carousel layouts, tag badges, and ACF-powered category icons.

== Description ==
This plugin provides a single shortcode:

`[news_listing layout="grid" category="business,technology" count="9" category_icon="true" tags_badges="true"]`

- **layout**: `grid` (default) or `carousel`
- **category**: comma-separated category slugs (optional)
- **count**: number of posts to show (default 9)
- **category_icon**: `true`/`false` (default true)
- **tags_badges**: `true`/`false` (default true)

For grid layout, pagination is automatically handled when used on pages and archives via the standard `paged`/`page` query vars, and links render below the grid.

For carousel layout, items scroll horizontally with scroll-snap. Previous/Next buttons are included and keyboard arrows navigate one card at a time; touch swipe is supported by the browser.

Parameters:
- layout: grid | carousel (default grid)
- category: CSV category slugs (optional)
- count: integer (default 9)
- category_icon: true | false (default true)
- tags_badges: true | false (default true)

Category icons are an optional, soft dependency: if Advanced Custom Fields is active and a term field named category_icon (image URL) exists for the post categories, icons are shown. Otherwise, the plugin will attempt to read a category_icon URL from term meta; if none, icons are omitted.

Tag badges: up to 3 tags are shown as small badges over the thumbnail; long labels are truncated with ellipsis and hidden from screen readers as they duplicate visible text elsewhere.

== Installation ==
1. Upload the `news-listing` folder to `/wp-content/plugins/`.
2. Activate the plugin.
3. Add `[news_listing]` to a page or post.

== Development ==
- Coding standard: WPCS + PSR-12 via phpcs.xml. Run `composer install` then `composer phpcs`.
- Tests: PHPUnit unit tests in `/tests`. Requires phpunit; run `phpunit` from project root.

== Changelog ==
= 0.1.0 =
* Initial scaffold.