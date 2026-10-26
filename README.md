# Oli Variation Gallery for WooCommerce

Add several gallery images per product variation. The default product gallery swaps to the selected variation and falls back to the main gallery.

**Author:** Olivier Bigras
**Author URI:** https://github.com/olivierbigras
**Plugin URI:** https://github.com/olivierbigras/Oli-Variation-Images-Gallery-for-WooCommerce
**Contact:** oli@olivierbigras.com
**License:** GPLv2 or later

## Features

- Several gallery images per variation from the variation edit panel (media modal, drag to reorder)
- Product page gallery swap with fallback to the main gallery
- Works with the default gallery: zoom, lightbox, and slider
- Thumbnail position (bottom / left / right) and columns (1-8)
- REST API and Store API exposure
- HPOS compatible
- One-click, idempotent importer for `rtwpvg_images` (dry-run count + WP-CLI)

## Requirements

- WordPress 6.5+
- WooCommerce 9.0+
- PHP 7.4+

## Installation

1. Upload `oli-variation-gallery` to `/wp-content/plugins/`
2. Activate the plugin
3. Configure WooCommerce → Variation Gallery
4. Edit a variable product and add images to each variation

## Importer

Existing `rtwpvg_images` meta (array of attachment IDs) can be copied without deleting the source.

```bash
wp oli-vig import --dry-run
wp oli-vig import
```

The import screen also shows a dry-run count before you click Run Import.

## Development

```bash
composer install
composer lint
composer phpcs
composer test
```

PHPCS uses WordPress-Extra. Plugin Check should be run on the distribution ZIP (no `.github` or `tests`).

## License

GPL v2 or later — see [license.txt](license.txt)
