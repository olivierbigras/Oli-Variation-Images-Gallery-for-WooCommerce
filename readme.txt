=== Oli Variation Images Gallery for WooCommerce ===
Contributors: bigrat95
Tags: woocommerce, variations, gallery, product images, variable products
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.2.0
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
* **Settings**: Configure thumbnail position, columns, carousel mode, fade/slide transitions, zoom, lightbox, and more under WooCommerce → Settings → Products → Variation galleries
* **Import**: Preview and import variation galleries from another plugin when that data is detected

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

== External services ==

This plugin does not connect to any external service at runtime.

* Scripts and styles are loaded from the plugin folder. There are no calls to olivierbigras.com, PayPal, Google APIs, Cloudflare Turnstile, Carto, Leaflet, Google Sheets, Google Fonts, or Adobe Typekit.
* Gallery images come from the local WordPress Media Library.
* The optional RadiusTheme importer reads existing variation meta (`rtwpvg_images` and `_product_image_gallery`) from this site's database only. It does not contact RadiusTheme or any remote host.
* Author and support links in the plugin header and readme (olivierbigras.com, github.com/olivierbigras, wordpress.org) are documentation only. The plugin does not phone home.

If a future release adds an optional remote feature, it will be opt-in and documented here with the service terms and privacy policy.

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/oli-variation-images-gallery/`, or install through the WordPress plugin repository
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to WooCommerce → Settings → Products → Variation galleries to configure settings
4. Edit a variable product and add images to variations in the "Variation gallery images" section

= Importing from RadiusTheme Plugin =

1. Keep the RadiusTheme plugin active (data will be preserved)
2. Open WooCommerce → Settings → Products → Variation galleries
3. If other plugin data is found, use Preview or Import (original data is not modified)
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

Yes. The lightbox traps focus, closes with Escape, and restores focus. Thumbnails are buttons with an accessible name, the active image is marked with aria-current, and image changes are announced politely.

== Screenshots ==

1. Admin panel: Add and reorder variation gallery images with drag & drop
2. Frontend: Gallery automatically swaps when variation is selected
3. Settings page: Configure thumbnail position, columns, zoom, and lightbox
4. Import tool: One-click migration from RadiusTheme plugin
5. Lightbox: Full-screen image viewing with navigation

== Changelog ==

= 0.2.0 - 2026-10-21 =
* Settings moved to WooCommerce → Settings → Products → Variation galleries
* Visible fields: position, mode, columns (Grid only), zoom and lightbox (theme defaults)
* Advanced (collapsed): reset on clear, preload (page-weight help), transition (Carousel only, reduced motion), gallery width
* Import submenu removed; notice with Preview / Import only when other-plugin data is found
* Variation field: linked label/help, Add images, keyboard move, remove aria-label, empty state
* Storefront: thumbnail buttons, live announcements, carousel arrow keys, lightbox focus trap
* Boolean settings stored as real booleans (yes/no migrated)
* .distignore / export-ignore packaging

= 0.1.2 - 2026-10-21 =
* WordPress.org readiness: External services section, no inline CSS, versioned option upgrade, uninstall cleanup
* Shop managers can save settings (`manage_woocommerce` capability on the options page)
* Frontend thumbnail column setting now applies to the WooCommerce gallery
* Localized previous/next/close labels; safer admin/frontend markup construction
* Requires Plugins: woocommerce; WC tested up to 11.2

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

= 0.2.0 =
Settings move to WooCommerce → Settings → Products → Variation galleries. Existing `oli_vig_settings` keys are kept; yes/no values become booleans.

= 0.1.2 =
WordPress.org guideline polish. Existing settings and variation galleries are kept.

= 0.1.1 =
Importer now matches RadiusTheme's real meta format. Adds thumbnail carousel and fade/slide transitions.

= 0.1.0 =
Initial release of Oli Variation Images Gallery for WooCommerce.

== Credits ==

Developed by [Olivier Bigras](https://olivierbigras.com/)

== Support ==

For support, please visit the [plugin support forum](https://wordpress.org/support/plugin/oli-variation-images-gallery/) or [GitHub repository](https://github.com/olivierbigras/Oli-Variation-Images-Gallery-for-WooCommerce).
