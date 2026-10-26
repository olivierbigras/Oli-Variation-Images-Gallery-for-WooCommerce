# Changelog

All notable changes to Oli Variation Gallery for WooCommerce are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-10-26

### Added
- Plugin slug and text domain `oli-variation-gallery`
- Default gallery integration: rebuild slides and re-init zoom, lightbox, and slider
- REST API field `oli_vig_gallery_ids` on product variations
- Store API namespace `oli-variation-gallery` with variation gallery IDs
- Idempotent one-click importer for `rtwpvg_images` with an on-screen dry-run count
- WP-CLI: `wp oli-vig import [--dry-run]`
- Standalone tests for importer, image helpers, settings, REST schema, and upgrades

### Changed
- Author URI is https://github.com/olivierbigras
- Plugin URI remains the GitHub repository (not identical to the Author URI)
- Thumbnail position and columns apply to the default gallery
- Zoom and lightbox settings map to the default gallery filters
- Admin notices only appear on this plugin's screens (WooCommerce-missing notice on Plugins)

### Removed
- Bundled `.po` / `.pot` files and `load_plugin_textdomain()`
- Custom lightbox, click-to-zoom, and replacement slider that overrode the default gallery

### Security
- Sanitization, escaping, nonces, and capability checks on admin and AJAX paths
- Variation-save nonce accepts both product-update and AJAX variation-save fields

[1.0.0]: https://github.com/olivierbigras/Oli-Variation-Images-Gallery-for-WooCommerce/releases/tag/1.0.0
