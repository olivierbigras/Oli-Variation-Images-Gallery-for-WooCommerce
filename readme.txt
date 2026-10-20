=== Oli Variation Images Gallery for WooCommerce ===
Contributors: bigrat95
Tags: woocommerce, variations, gallery, product images, variable products
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add unlimited gallery images per product variation with thumbnail gallery, zoom, and lightbox support.

== Description ==

Oli Variation Images Gallery for WooCommerce allows you to add unlimited additional gallery images to each product variation. When customers select a variation, the product gallery automatically updates to show the variation-specific images.

= Key Features =

* **Unlimited Gallery Images**: Add as many images as you need to each variation
* **Easy Management**: Drag and drop to reorder images in the admin panel
* **Automatic Gallery Swap**: Gallery updates instantly when variation is selected
* **Thumbnail Gallery**: Configurable thumbnail position (bottom, left, or right), columns, and slider/carousel mode
* **Image Zoom**: Optional zoom on hover for detailed product viewing
* **Lightbox**: Full-screen image viewing with keyboard and touch navigation
* **Mobile Optimized**: Touch-friendly swipe gestures on mobile devices
* **Performance**: REST/AJAX lazy loading for products with many variations
* **Settings Page**: Configure thumbnail position, columns, carousel mode, fade/slide transitions, zoom, lightbox, and more
* **Import Tool**: One-click migration from RadiusTheme Variation Images Gallery plugin

= Perfect For =

* Clothing stores showing color variations with different product photos
* Furniture shops displaying variations in different room settings
* Any store needing unique gallery images per variation

= Import from RadiusTheme Plugin =

If you're currently using the RadiusTheme "Variation Images Gallery for WooCommerce" plugin, we've got you covered! Our built-in importer will migrate all your existing variation gallery data with a single click.

* **One-Click Import**: Easy migration tool in WooCommerce settings
* **WP-CLI Support**: Command-line import for large stores
* **Dry Run Option**: Preview what will be imported before running
* **Safe Migration**: Original data is preserved (never deleted)

= Requirements =

* WordPress 6.5 or higher
* WooCommerce 9.0 or higher
* PHP 7.4 or higher

= HPOS Compatible =

This plugin is fully compatible with WooCommerce High-Performance Order Storage (HPOS).

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/oli-variation-images-gallery/`, or install through the WordPress plugin repository
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to WooCommerce → Variation Gallery to configure settings
4. Edit a variable product and add images to variations in the "Variation Gallery Images" section

= Importing from RadiusTheme Plugin =

1. Keep the RadiusTheme plugin active (data will be preserved)
2. Go to WooCommerce → Import Gallery Data
3. Click "Run Import" (or use dry run to preview)
4. Alternatively, use WP-CLI: `wp oli-vig import`

== Frequently Asked Questions ==

= Does this work with all WooCommerce themes? =

Yes, the plugin is designed to work with the classic WooCommerce product template and most popular themes. It includes fallback hooks to ensure compatibility.

= Can I keep both the RadiusTheme plugin and this plugin active? =

Yes, during migration you can keep both active. The import tool reads RadiusTheme data without deleting it, so you can safely test before fully switching.

= Will this slow down my site? =

No, the plugin uses lazy loading for variation images and only loads images when needed. It's optimized for performance, especially for products with many variations.

= Does it support video galleries? =

This version focuses on image galleries. Video support may be added in future updates.

= Is the lightbox accessible? =

Yes, the lightbox includes keyboard navigation (arrow keys, Escape) and proper focus management.

== Screenshots ==

1. Admin panel: Add and reorder variation gallery images with drag & drop
2. Frontend: Gallery automatically swaps when variation is selected
3. Settings page: Configure thumbnail position, columns, zoom, and lightbox
4. Import tool: One-click migration from RadiusTheme plugin
5. Lightbox: Full-screen image viewing with navigation

== Changelog ==

= 0.1.1 - 2026-10-20 =
* Importer reads the real RadiusTheme `rtwpvg_images` format (PHP-serialized attachment IDs) and WooCommerce `_product_image_gallery` on variations
* Never deletes source RadiusTheme/WooCommerce meta
* Thumbnail slider/carousel mode with prev/next arrows
* Fade and slide main-image transition options
* Unit test covering the real RadiusTheme meta format
* PHPCS WordPress standard and PHP lint CI fixes

= 0.1.0 - 2024-10-19 =
* Initial release
* Unlimited gallery images per variation
* Thumbnail gallery with configurable position and columns
* Image zoom on hover
* Lightbox with keyboard and touch navigation
* Mobile swipe support
* Settings page for customization
* Import tool from RadiusTheme Variation Images Gallery
* WP-CLI import command
* French translation included
* HPOS compatibility

== Upgrade Notice ==

= 0.1.1 =
Importer now matches RadiusTheme's real meta format. Adds thumbnail carousel and fade/slide transitions.

= 0.1.0 =
Initial release of Oli Variation Images Gallery for WooCommerce.

== Credits ==

Developed by [Olivier Bigras](https://olivierbigras.com/)

== Support ==

For support, please visit the [plugin support forum](https://wordpress.org/support/plugin/oli-variation-images-gallery/) or [GitHub repository](https://github.com/olivierbigras/Oli-Variation-Images-Gallery-for-WooCommerce).
