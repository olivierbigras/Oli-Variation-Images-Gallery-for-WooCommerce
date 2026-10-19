# Testing Guide for Oli Variation Images Gallery

## Quick Start Testing

### Prerequisites
- WordPress 6.5+ installed
- WooCommerce 9.0+ active
- At least one variable product with 2+ variations

## Test Checklist

### ✅ Installation & Activation
- [ ] Plugin activates without errors
- [ ] WooCommerce dependency check works (try activating without WooCommerce)
- [ ] Menu items appear: WooCommerce → Variation Gallery, Import Gallery Data

### ✅ Admin - Add Gallery Images
1. Edit a variable product
2. Expand a variation
3. Find "Variation Gallery Images" section
4. Click "Add Images"
   - [ ] Media modal opens with correct title
   - [ ] Can select multiple images
   - [ ] Selected images appear as thumbnails
5. Test drag & drop
   - [ ] Can reorder images by dragging
   - [ ] Order persists after reordering
6. Test removal
   - [ ] Click X button on an image
   - [ ] Image is removed from gallery
7. Save product
   - [ ] Gallery images persist after save
   - [ ] Images appear correctly after page reload

### ✅ Admin - Settings Page
1. Go to WooCommerce → Variation Gallery
2. Test each setting:
   - [ ] Thumbnail Position: bottom/left/right
   - [ ] Thumbnail Columns: 1-8
   - [ ] Enable Zoom checkbox
   - [ ] Enable Lightbox checkbox
   - [ ] Gallery Width: 50-100%
   - [ ] Preload Images checkbox
   - [ ] Reset on Clear checkbox
3. Save settings
   - [ ] "Settings saved successfully" message appears
   - [ ] Settings persist after reload

### ✅ Frontend - Gallery Swap
1. View variable product on frontend
2. Select first variation
   - [ ] Gallery swaps to variation images
   - [ ] Main image changes
   - [ ] Thumbnails update
3. Select second variation
   - [ ] Gallery swaps to different variation images
4. Click "Clear" or reset variation
   - [ ] Gallery returns to parent images (if reset_on_clear enabled)
   - [ ] Gallery stays on variation images (if reset_on_clear disabled)

### ✅ Frontend - Thumbnail Gallery
1. Check thumbnail display
   - [ ] Thumbnails appear in configured position (bottom/left/right)
   - [ ] Number of columns matches setting
   - [ ] Gap/spacing looks correct
2. Click thumbnails
   - [ ] Main image changes when clicking thumbnail
   - [ ] Active thumbnail is highlighted

### ✅ Frontend - Zoom
If zoom is enabled:
1. Hover over main image
   - [ ] Cursor changes to zoom-in
2. Click main image
   - [ ] Image zooms (1.5x scale)
   - [ ] Can zoom in/out by clicking

### ✅ Frontend - Lightbox
If lightbox is enabled:
1. Click main image
   - [ ] Lightbox opens in fullscreen
   - [ ] Image displays correctly
2. Test navigation
   - [ ] Click right arrow → next image
   - [ ] Click left arrow → previous image
   - [ ] Press → key → next image
   - [ ] Press ← key → previous image
   - [ ] First image: left arrow disabled
   - [ ] Last image: right arrow disabled
3. Close lightbox
   - [ ] Click X button → closes
   - [ ] Press Escape → closes
   - [ ] Click outside image → closes

### ✅ Frontend - Mobile
Test on mobile device or responsive mode:
1. Touch gestures
   - [ ] Swipe left → shows next image
   - [ ] Swipe right → shows previous image
   - [ ] Swipe works smoothly
2. Responsive layout
   - [ ] Gallery adjusts to screen width
   - [ ] Thumbnails resize appropriately
   - [ ] Buttons are touch-friendly

### ✅ Import Tool (if RadiusTheme data available)
1. Go to WooCommerce → Import Gallery Data
2. If data exists:
   - [ ] "RadiusTheme data found" message shows
3. Test dry run
   - [ ] Check "Dry run" checkbox
   - [ ] Click "Run Import"
   - [ ] Preview shows correct count
4. Test actual import
   - [ ] Uncheck "Dry run"
   - [ ] Click "Run Import"
   - [ ] Success message shows imported count
5. Verify migration
   - [ ] Edit a variation that was in RadiusTheme
   - [ ] Gallery images appear in our plugin
   - [ ] Original RadiusTheme meta still exists in database

### ✅ WP-CLI Commands
If WP-CLI is available:
```bash
# Test dry run
wp oli-vig import --dry-run
# Expected: "Dry run complete. Found X variations..."

# Test actual import
wp oli-vig import
# Expected: "Import complete. Imported gallery data for X variations."
```

### ✅ Performance
1. Product with many variations (10+)
   - [ ] Page loads without delay
   - [ ] Variation selection is fast
   - [ ] No console errors
2. Large images
   - [ ] Images load correctly
   - [ ] Srcset/sizes work for responsive images
   - [ ] No broken image icons

### ✅ Compatibility
Test with common themes:
- [ ] Storefront (default WooCommerce theme)
- [ ] Astra
- [ ] OceanWP
- [ ] Your current theme

### ✅ Edge Cases
1. Variation with no gallery images
   - [ ] Uses parent gallery or variation featured image
   - [ ] No JavaScript errors
2. Product with no variations
   - [ ] Plugin doesn't affect simple products
   - [ ] No errors on simple product pages
3. Deactivate and reactivate
   - [ ] Data persists after deactivation
   - [ ] Settings remain after reactivation

## Browser Testing

Test in multiple browsers:
- [ ] Chrome/Chromium
- [ ] Firefox
- [ ] Safari (if available)
- [ ] Edge
- [ ] Mobile Safari (iOS)
- [ ] Mobile Chrome (Android)

## Expected Results

### Success Criteria
- ✅ No PHP errors in debug.log
- ✅ No JavaScript console errors
- ✅ Gallery swaps correctly on variation selection
- ✅ All frontend features work (zoom, lightbox, swipe)
- ✅ Settings save and apply correctly
- ✅ Import tool migrates RadiusTheme data successfully
- ✅ Responsive on all screen sizes
- ✅ Compatible with major themes

### Known Limitations
- Video support not included in v0.1.0
- Advanced carousel animations not included
- Global settings only (no per-product overrides)

## Reporting Issues

If you find bugs, please report with:
1. WordPress version
2. WooCommerce version
3. PHP version
4. Active theme name
5. Steps to reproduce
6. Expected vs actual behavior
7. Browser console errors (if any)
8. PHP error logs (if any)

## Automated Tests

The GitHub Actions workflow automatically tests:
- PHP syntax on PHP 7.4, 8.0, 8.1, 8.2
- WordPress Coding Standards (PHPCS)

Check: https://github.com/bigrat95/Oli-Variation-Images-Gallery-for-WooCommerce/actions
