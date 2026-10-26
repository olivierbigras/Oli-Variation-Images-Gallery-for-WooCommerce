# Changelog

All notable changes to Oli Variation Gallery for WooCommerce are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.2] - 2026-10-26

### Fixed
- Left/right thumbnail column stays 72–88px beside the main image (nowrap flex, Storefront-specific width rules overridden without `!important`)
- Below 768px, left/right positions fall back to a bottom thumbnail row

### Changed
- Importer reports “skipped (existing gallery, use --force)” separately from “already matched”

[1.0.2]: https://github.com/olivierbigras/Oli-Variation-Images-Gallery-for-WooCommerce/releases/tag/1.0.2

## [1.0.1] - 2026-10-26

### Fixed
- Variation image changes now trigger `change` and mark the variation as needing an update so Save changes works
- A variation with its own image and no extra gallery shows that image plus the main gallery thumbs
- Gallery destroy/re-init removes zoom triggers and PhotoSwipe handlers so one magnifier remains and the lightbox can close
- WP-CLI `wp oli-vig import` is registered when `WP_CLI` is defined, not only in wp-admin
- Horizontal drag reorders admin thumbnails
- Left/right thumbnails keep a readable minimum width

### Changed
- Importer skips existing non-empty galleries unless `--force` or the overwrite checkbox is used
- Adding an image that is already in the variation gallery does not duplicate it in the admin UI
- Default thumbnail columns follow the theme or WooCommerce default (0) instead of hardcoding 4

[1.0.1]: https://github.com/olivierbigras/Oli-Variation-Images-Gallery-for-WooCommerce/releases/tag/1.0.1

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
