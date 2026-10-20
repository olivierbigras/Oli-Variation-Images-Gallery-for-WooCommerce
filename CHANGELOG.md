# Changelog

All notable changes to Oli Variation Images Gallery for WooCommerce will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.1.1] - 2026-10-20

### Added
- Thumbnail slider/carousel mode (scrollable strip with prev/next arrows)
- Fade and slide transitions for the main gallery image
- Standalone unit test for the RadiusTheme importer using the real serialized `rtwpvg_images` format

### Fixed
- Importer now parses RadiusTheme `rtwpvg_images` as a PHP-serialized array of attachment IDs (the format `update_post_meta()` actually stores)
- Importer also reads WooCommerce native `_product_image_gallery` on variations (RadiusTheme copies IDs there on WC 11.1+)
- Source RadiusTheme/WooCommerce meta is never deleted
- Yoda conditions and other WordPress PHPCS violations
- GitHub Actions PHP lint no longer fails when every file is clean
- GitHub URLs updated after the owner rename to olivierbigras

## [0.1.0] - 2024-10-19

### Added
- Initial release of the plugin
- Unlimited gallery images per product variation
- Drag and drop interface for reordering images in admin
- Automatic gallery swap when variation is selected
- Gallery reset when variation is cleared (configurable)
- Thumbnail gallery with configurable position (bottom, left, right)
- Configurable thumbnail columns (1-8)
- Image zoom on hover (optional)
- Lightbox for full-screen image viewing
- Keyboard navigation in lightbox (arrow keys, Escape)
- Mobile touch swipe support for image navigation
- Slider arrows for gallery navigation
- Settings page under WooCommerce menu
- Configurable gallery width (50-100%)
- Image preloading option for faster switching
- REST/AJAX lazy loading for products with many variations
- Import tool for migrating from RadiusTheme Variation Images Gallery plugin
- Dry run option for import preview
- WP-CLI import command: `wp oli-vig import [--dry-run]`
- HPOS (High-Performance Order Storage) compatibility declared
- Full internationalization support
- French (fr_FR) translation included
- Plugin Check (PCP) compliant code
- No external API calls or CDN dependencies
- No tracking or upsells
- Proper sanitization, escaping, and nonce verification
- Prefix oli_vig_ for functions and Oli_VIG for classes

### Security
- All user inputs properly sanitized
- All outputs properly escaped
- Nonce verification on all forms
- Capability checks for admin actions
- No external dependencies or CDN calls

### Performance
- Lazy loading of variation images via AJAX
- Image preloading option for instant switching
- Optimized for products with many variations
- No performance impact on non-variable products

### Compatibility
- WordPress 6.5+
- WooCommerce 9.0+
- PHP 7.4+
- HPOS compatible
- Works with classic WooCommerce product templates
- Compatible with most popular themes

[0.1.1]: https://github.com/olivierbigras/Oli-Variation-Images-Gallery-for-WooCommerce/releases/tag/0.1.1
[0.1.0]: https://github.com/olivierbigras/Oli-Variation-Images-Gallery-for-WooCommerce/releases/tag/0.1.0
