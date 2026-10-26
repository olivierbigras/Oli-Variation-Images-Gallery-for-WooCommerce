=== Oli Variation Gallery for WooCommerce ===
Contributors: bigrat95
Tags: variations, gallery, product images, variable products, thumbnails
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add several gallery images per product variation. The product gallery swaps to the selected variation and falls back to the main gallery.

== Description ==

Oli Variation Gallery for WooCommerce lets you attach several extra images to each product variation. On the product page, the default gallery updates when a shopper selects a variation. If that variation has no extra images, the main product gallery is shown again.

= Key Features =

* Several gallery images per variation, managed from the variation edit panel
* WordPress media modal and drag-to-reorder
* Product gallery swap on variation selection, with fallback to the main gallery
* Works with the default gallery: zoom, lightbox, and slider
* Thumbnail position (bottom, left, or right) and column count (1-8)
* REST API and Store API exposure of variation gallery IDs
* High-Performance Order Storage (HPOS) compatible
* One-click, idempotent importer for existing `rtwpvg_images` meta

= Import existing gallery meta =

If another plugin stored variation galleries under the `rtwpvg_images` meta key, you can copy that data in one click. Source meta is never deleted. A dry-run count is shown on the import screen and via WP-CLI. Existing non-empty galleries are not overwritten unless you pass `--force` or check the overwrite box.

	wp oli-vig import --dry-run
	wp oli-vig import
	wp oli-vig import --force

= Requirements =

* WordPress 6.5 or higher
* WooCommerce 9.0 or higher
* PHP 7.4 or higher

= HPOS Compatible =

This plugin declares compatibility with High-Performance Order Storage.

Contact: oli@olivierbigras.com

== External services ==

This plugin does not connect to any external service at runtime.

* Scripts and styles are loaded from the plugin folder.
* Gallery images come from the local Media Library.
* The optional importer reads existing variation meta (`rtwpvg_images` and `_product_image_gallery`) from this site's database only.
* Author and support links in the plugin header and readme are documentation only. The plugin does not phone home.

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/oli-variation-gallery/`, or install the plugin through the WordPress plugins screen
2. Activate the plugin through the 'Plugins' screen
3. Go to WooCommerce → Variation Gallery to configure thumbnail position and columns
4. Edit a variable product and add images in the Variation Gallery Images section of each variation

= Importing existing meta =

1. Go to WooCommerce → Import Gallery Data
2. Review the dry-run count
3. Optionally check Dry run, then click Run Import
4. Or use WP-CLI: `wp oli-vig import --dry-run` then `wp oli-vig import`. Add `--force` only if you want to overwrite galleries edited after a previous import.

== Frequently Asked Questions ==

= Does this work with the default product gallery? =

Yes. The plugin rebuilds the default gallery slides and re-initializes zoom, lightbox, and the thumbnail slider.

= What happens when a variation has no extra images? =

The variation’s own image replaces the main image and the rest of the main gallery is kept. If the variation has no image either, the main gallery is shown.

= Is the importer safe to run more than once? =

Yes. Already-matching variations are skipped, existing non-empty galleries are left unchanged unless you force an overwrite, and source meta is never deleted.

= Does it support video galleries? =

This version focuses on image galleries.

= Where can I get support? =

Email oli@olivierbigras.com or open an issue on the plugin's GitHub repository.

== Screenshots ==

1. Variation edit panel: add and reorder gallery images
2. Product page: gallery swaps when a variation is selected
3. Settings: thumbnail position, columns, zoom, and lightbox
4. Import tool with dry-run count

== Changelog ==

= 1.0.3 - 2026-10-26 =
* Dry-run shows how many variations would be imported and how many would be skipped as an existing gallery

= 1.0.2 - 2026-10-26 =
* Left and right thumbnails stay beside the main image at desktop widths
* Mobile left/right positions fall back to bottom thumbnails
* Keyboard-accessible thumbnails and named image links
* Move left/right buttons and specific Remove labels in the variation panel
* Importer reports existing galleries separately: skipped (existing gallery, use --force)

= 1.0.1 - 2026-10-26 =
* Save variation galleries when only images change (Save changes enables correctly)
* Variation-only images replace the main image like the default gallery
* Clean gallery re-init so zoom has one trigger and the lightbox can close
* WP-CLI `wp oli-vig import` is registered outside wp-admin
* Horizontal drag-and-drop in the variation panel
* Usable left/right thumbnail size
* Importer does not overwrite edited galleries unless `--force` is used
* Default thumbnail columns follow the theme or WooCommerce default

= 1.0.0 - 2026-10-26 =
* First release under the oli-variation-gallery slug and text domain
* Default gallery integration (zoom, lightbox, slider)
* REST API and Store API exposure
* Idempotent rtwpvg_images importer with dry-run count and WP-CLI
* WordPress.org compliance: no bundled translation files, notices only on plugin screens

= 0.1.2 - 2026-10-21 =
* Earlier preview release under a previous slug

= 0.1.1 - 2026-10-20 =
* Importer coverage for serialized rtwpvg_images values

= 0.1.0 - 2024-10-19 =
* Initial preview

== Upgrade Notice ==

= 1.0.3 =
Dry-run reports would-import and existing-gallery skip counts.

= 1.0.2 =
Fixes left/right thumbnail layout, mobile thumbs, and gallery keyboard access.

= 1.0.1 =
Fixes variation saving, gallery fallback, lightbox re-init, and WP-CLI import.

= 1.0.0 =
Renamed to Oli Variation Gallery for WooCommerce (slug oli-variation-gallery). Re-activate after updating. Existing `_oli_vig_gallery_images` meta is kept.

== Support ==

Author: Olivier Bigras
Author URI: https://github.com/olivierbigras
Plugin URI: https://github.com/olivierbigras/Oli-Variation-Images-Gallery-for-WooCommerce
Contact email: oli@olivierbigras.com
