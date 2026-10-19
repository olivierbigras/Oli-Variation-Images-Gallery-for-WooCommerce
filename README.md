# Oli Variation Images Gallery for WooCommerce

[![PHP Checks](https://github.com/bigrat95/oli-variation-images-gallery/actions/workflows/phpcs.yml/badge.svg)](https://github.com/bigrat95/oli-variation-images-gallery/actions/workflows/phpcs.yml)
[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0)
[![WordPress Plugin Version](https://img.shields.io/badge/version-0.1.0-green.svg)](https://github.com/bigrat95/oli-variation-images-gallery/releases)

Add unlimited gallery images per product variation with thumbnail gallery, zoom, and lightbox support for WooCommerce.

## Features

- **Unlimited Gallery Images**: Add as many images as you need to each variation
- **Easy Management**: Drag and drop to reorder images in the admin panel
- **Automatic Gallery Swap**: Gallery updates instantly when variation is selected
- **Thumbnail Gallery**: Configurable thumbnail position (bottom, left, or right) and columns (1-8)
- **Image Zoom**: Optional zoom on hover for detailed product viewing
- **Lightbox**: Full-screen image viewing with keyboard and touch navigation
- **Mobile Optimized**: Touch-friendly swipe gestures on mobile devices
- **Performance**: REST/AJAX lazy loading for products with many variations
- **Settings Page**: Configure all gallery options from WooCommerce settings
- **Import Tool**: One-click migration from RadiusTheme Variation Images Gallery plugin
- **WP-CLI Support**: Command-line tools for bulk operations
- **HPOS Compatible**: Full support for WooCommerce High-Performance Order Storage
- **Internationalized**: Fully translatable with French translation included

## Requirements

- **WordPress**: 6.5 or higher
- **WooCommerce**: 9.0 or higher
- **PHP**: 7.4 or higher

## Installation

1. Download the latest release or clone this repository
2. Upload to `/wp-content/plugins/oli-variation-images-gallery/`
3. Activate the plugin through the WordPress admin
4. Configure settings under WooCommerce → Variation Gallery
5. Edit a variable product and add images to variations

## Importing from RadiusTheme Plugin

If you're migrating from the RadiusTheme "Variation Images Gallery for WooCommerce" plugin:

### Via Admin UI
1. Keep the RadiusTheme plugin active (data will be preserved)
2. Go to WooCommerce → Import Gallery Data
3. Click "Run Import" (or use dry run to preview first)

### Via WP-CLI
```bash
# Dry run (preview only)
wp oli-vig import --dry-run

# Actual import
wp oli-vig import
```

The import tool reads the RadiusTheme plugin's meta keys (`rtwpvg_images`) and migrates them to our format without deleting the original data.

## Development

### Setup
```bash
# Install dependencies
composer install

# Run PHP linting
composer lint

# Run PHPCS
composer phpcs

# Auto-fix PHPCS issues
composer phpcbf
```

### Code Standards

This plugin follows:
- WordPress Coding Standards
- Plugin Check (PCP) requirements
- WordPress.org plugin guidelines
- No external API calls or CDNs
- All outputs escaped, all inputs sanitized
- Proper prefixing (`oli_vig_` / `Oli_VIG`)

## Known Gaps vs RadiusTheme Plugin

This is version 0.1.0 with feature parity on core functionality:
- ✅ Unlimited gallery images per variation
- ✅ Drag & drop ordering
- ✅ Gallery swap on variation selection
- ✅ Thumbnails (configurable position & columns)
- ✅ Zoom on hover
- ✅ Lightbox with navigation
- ✅ Mobile swipe support
- ✅ Settings page
- ✅ Import tool
- ⏳ Advanced gallery transitions (may be added in future updates)
- ⏳ Video gallery support (may be added in future updates)
- ⏳ Additional lightbox effects (may be added in future updates)

## Testing

The plugin has been tested with:
- WordPress 6.5 - 7.1
- WooCommerce 9.0 - 11.0
- PHP 7.4 - 8.2
- Common themes: Storefront, Astra, OceanWP, Flatsome

## Support

- **Issues**: [GitHub Issues](https://github.com/bigrat95/oli-variation-images-gallery/issues)
- **WordPress.org**: [Support Forum](https://wordpress.org/support/plugin/oli-variation-images-gallery/)

## Contributing

Contributions are welcome! Please:
1. Fork the repository
2. Create a feature branch
3. Make your changes following WordPress coding standards
4. Run `composer lint` and `composer phpcs` to verify
5. Submit a pull request

## License

GPL v2 or later - see [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html)

## Credits

Developed by [Olivier Bigras](https://olivierbigras.com/)

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history.
