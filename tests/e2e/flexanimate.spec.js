const { test, expect } = require('@playwright/test');
const { WC_VERSIONS, ensureAssets, writePage } = require('./helpers/assets');
const { buildProductPage, buildCatalog } = require('./helpers/product-page');

function collectPageErrors(page) {
	const errors = [];
	page.on('pageerror', (error) => {
		errors.push(error.message);
	});
	page.on('console', (message) => {
		if (message.type() !== 'error') {
			return;
		}
		const text = message.text();
		if (/favicon\.ico|Failed to load resource/i.test(text)) {
			return;
		}
		errors.push(text);
	});
	return errors;
}

async function openFixture(page, { version, flexsliderEnabled, parentMode }) {
	const assets = await ensureAssets(version);
	const catalog = buildCatalog();
	const parentImages = parentMode === 'wide' ? catalog.parentWide : catalog.parent;
	const html = buildProductPage({
		assets,
		flexsliderEnabled,
		parentImages,
	});
	const file = await writePage(
		`${version}-${flexsliderEnabled ? 'flex' : 'noflex'}-${parentMode}.html`,
		html
	);
	const errors = collectPageErrors(page);
	await page.goto(file);
	await page.waitForFunction(() => window.jQuery && window.oliVigSettings);
	const galleryCount = await page.evaluate(() => {
		const variations = window.jQuery('.variations_form').data('product_variations');
		return variations && variations[0] && variations[0].oli_vig_gallery
			? variations[0].oli_vig_gallery.length
			: 0;
	});
	expect(galleryCount, 'variation JSON should expose oli_vig_gallery').toBe(3);
	await page.waitForTimeout(150);
	return { errors, catalog, parentImages };
}

async function galleryAlts(page) {
	return page.locator('.woocommerce-product-gallery__image img').evaluateAll((images) =>
		images.map((image) => image.getAttribute('alt') || '')
	);
}

async function selectColor(page, color) {
	await page.locator('#pa_color').selectOption(color);
	await page.waitForTimeout(80);
}

async function clearVariation(page) {
	await page.locator('.reset_variations').click();
	await page.waitForTimeout(80);
}

function flexAnimateErrors(errors) {
	return errors.filter((message) => /flexAnimate/i.test(message));
}

const cases = [];
for (const version of WC_VERSIONS) {
	for (const flexsliderEnabled of [true, false]) {
		cases.push({ version, flexsliderEnabled, parentMode: 'single' });
		cases.push({ version, flexsliderEnabled, parentMode: 'wide' });
	}
}

for (const fixture of cases) {
	const sliderLabel = fixture.flexsliderEnabled ? 'flexslider on' : 'flexslider off';
	const parentLabel = fixture.parentMode === 'wide' ? 'parent gallery 2 images' : 'parent gallery 1 image';

	test(`WC ${fixture.version} ${sliderLabel} ${parentLabel}: select, change, clear`, async ({ page }) => {
		const { errors, catalog } = await openFixture(page, fixture);

		await expect.poll(() => galleryAlts(page)).toEqual(fixture.parentMode === 'wide' ? ['Parent', 'Parent extra'] : ['Parent']);

		await selectColor(page, 'red');
		await expect.poll(() => galleryAlts(page)).toEqual(catalog.red.map((image) => image.alt));

		await selectColor(page, 'blue');
		await expect.poll(() => galleryAlts(page)).toEqual(catalog.blue.map((image) => image.alt));

		await clearVariation(page);
		await expect
			.poll(() => galleryAlts(page))
			.toEqual(fixture.parentMode === 'wide' ? ['Parent', 'Parent extra'] : ['Parent']);

		expect(flexAnimateErrors(errors), errors.join('\n')).toEqual([]);
		expect(errors, errors.join('\n')).toEqual([]);
	});
}

test('unpatched destroy still throws flexAnimate on WC 11.2 reset', async ({ page }) => {
	const assets = await ensureAssets('11.2.1');
	const html = `<!doctype html>
<html>
<body>
	<div class="product">
		<div id="gallery" class="woocommerce-product-gallery images">
			<div class="woocommerce-product-gallery__wrapper">
				<div class="woocommerce-product-gallery__image"><img src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==" alt="A"></div>
				<div class="woocommerce-product-gallery__image"><img src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==" alt="B"></div>
			</div>
		</div>
	</div>
	<script src="${assets.jquery}"></script>
	<script src="${assets.flexslider}"></script>
	<script>
		window.__errors = [];
		window.addEventListener('error', function(event) {
			window.__errors.push(String(event.message || event.error));
		});
		jQuery(function($) {
			var $gallery = $('#gallery');
			$gallery.flexslider({
				selector: '.woocommerce-product-gallery__wrapper > .woocommerce-product-gallery__image',
				animation: 'slide',
				slideshow: false,
				controlNav: false,
				directionNav: false
			});
			$gallery.on('woocommerce_gallery_reset_slide_position', function() {
				$gallery.flexslider(0);
			});
			if ($gallery.data('flexslider')) {
				try { $gallery.flexslider('destroy'); } catch (e) {}
				$gallery.removeData('flexslider');
			}
			$gallery.trigger('woocommerce_gallery_reset_slide_position');
		});
	</script>
</body>
</html>`;
	const file = await writePage('unpatched-11.2.1.html', html);
	await page.goto(file);
	const messages = await page.evaluate(() => window.__errors);
	expect(messages.join('\n')).toMatch(/flexAnimate/i);
});
