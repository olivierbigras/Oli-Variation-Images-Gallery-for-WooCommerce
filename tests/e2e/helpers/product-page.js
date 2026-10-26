function image(id, label, color) {
	const src = `/tests/e2e/helpers/pixel.svg?id=${id}&label=${encodeURIComponent(label)}&color=${encodeURIComponent(color)}`;
	return {
		id,
		src,
		src_w: 120,
		src_h: 120,
		srcset: '',
		sizes: '',
		full_src: src,
		full_src_w: 120,
		full_src_h: 120,
		gallery_thumbnail_src: src,
		thumb_src: src,
		thumb_src_w: 120,
		thumb_src_h: 120,
		alt: label,
		title: label,
		caption: label,
		thumb_alt: label,
		url: src,
	};
}

function escapeAttr(value) {
	return String(value)
		.replace(/&/g, '&amp;')
		.replace(/"/g, '&quot;')
		.replace(/</g, '&lt;');
}

function slideMarkup(img, isFirst) {
	return `<div class="woocommerce-product-gallery__image" data-thumb="${escapeAttr(img.thumb_src)}" data-thumb-alt="${escapeAttr(img.alt)}" data-attachment-id="${img.id}">
		<a href="${escapeAttr(img.full_src)}">
			<img ${isFirst ? 'class="wp-post-image"' : ''} src="${escapeAttr(img.src)}" alt="${escapeAttr(img.alt)}" title="${escapeAttr(img.title)}" width="${img.src_w}" height="${img.src_h}" data-id="${img.id}" data-src="${escapeAttr(img.src)}" data-large_image="${escapeAttr(img.full_src)}" data-large_image_width="${img.full_src_w}" data-large_image_height="${img.full_src_h}">
		</a>
	</div>`;
}

const IMAGES = {
	parent: image(1, 'Parent', '#1d4ed8'),
	parentExtra: image(2, 'Parent extra', '#0f766e'),
	redA: image(11, 'Red A', '#b91c1c'),
	redB: image(12, 'Red B', '#dc2626'),
	redC: image(13, 'Red C', '#ef4444'),
	blueA: image(21, 'Blue A', '#1e3a8a'),
	blueB: image(22, 'Blue B', '#2563eb'),
	blueC: image(23, 'Blue C', '#3b82f6'),
};

function buildCatalog() {
	return {
		parent: [IMAGES.parent],
		parentWide: [IMAGES.parent, IMAGES.parentExtra],
		red: [IMAGES.redA, IMAGES.redB, IMAGES.redC],
		blue: [IMAGES.blueA, IMAGES.blueB, IMAGES.blueC],
	};
}

function variationPayload(id, color, images) {
	const featured = images[0];
	return {
		variation_id: id,
		variation_is_visible: true,
		variation_is_active: true,
		is_purchasable: true,
		is_in_stock: true,
		is_sold_individually: 'no',
		max_qty: 10,
		min_qty: 1,
		sku: color,
		weight: '',
		weight_html: '',
		dimensions: '',
		dimensions_html: '',
		price_html: '$10',
		availability_html: '',
		variation_description: `${color} variation`,
		image_id: featured.id,
		image: featured,
		oli_vig_gallery: images,
		attributes: {
			attribute_pa_color: color,
		},
	};
}

function buildProductPage({ assets, flexsliderEnabled, parentImages }) {
	const catalog = buildCatalog();
	const slides = parentImages || catalog.parent;
	const variations = [
		variationPayload(101, 'red', catalog.red),
		variationPayload(102, 'blue', catalog.blue),
	];

	const params = {
		flexslider_enabled: !!flexsliderEnabled,
		zoom_enabled: false,
		photoswipe_enabled: false,
		flexslider: {
			rtl: false,
			animation: 'slide',
			smoothHeight: true,
			directionNav: false,
			controlNav: 'thumbnails',
			slideshow: false,
			animationSpeed: 0,
			animationLoop: false,
			allowOneSlide: false,
		},
	};

	const settings = {
		ajaxUrl: '/admin-ajax.php',
		nonce: 'test',
		thumbnailPosition: 'bottom',
		thumbnailColumns: 4,
		galleryWidth: 100,
		enableZoom: false,
		enableLightbox: false,
		resetOnClear: true,
		preloadImages: false,
		productName: 'Test Product',
		i18n: {
			viewImage: 'View image %1$d of %2$d',
		},
	};

	const flexsliderScript = flexsliderEnabled
		? `<script src="${assets.flexslider}"></script>`
		: '';

	return `<!doctype html>
<html>
<head>
	<meta charset="utf-8">
	<title>Oli Variation Gallery flexAnimate fixture</title>
	<style>
		body { font-family: sans-serif; }
		.woocommerce-product-gallery { width: 360px; }
		.woocommerce-product-gallery img { max-width: 100%; height: auto; }
		.reset_variations { display: none; }
	</style>
</head>
<body>
	<div class="product">
		<div class="woocommerce-product-gallery woocommerce-product-gallery--with-images woocommerce-product-gallery--columns-4 images oli-vig-gallery oli-vig-thumbs-bottom" data-columns="4" style="opacity: 1;">
			<div class="woocommerce-product-gallery__wrapper">
				${slides.map((img, index) => slideMarkup(img, 0 === index)).join('\n')}
			</div>
		</div>
		<form class="variations_form cart" action="/" method="post" data-product_id="99" data-product_variations="${escapeAttr(JSON.stringify(variations))}">
			<table class="variations">
				<tbody>
					<tr>
						<th class="label"><label for="pa_color">Color</label></th>
						<td class="value">
							<select id="pa_color" name="attribute_pa_color" data-attribute_name="attribute_pa_color" data-show_option_none="yes">
								<option value="">Choose an option</option>
								<option value="red">Red</option>
								<option value="blue">Blue</option>
							</select>
							<a class="reset_variations" href="#" role="button">Clear</a>
							<div class="reset_variations_alert" role="alert"></div>
						</td>
					</tr>
				</tbody>
			</table>
			<div class="single_variation_wrap">
				<div class="woocommerce-variation single_variation"></div>
				<div class="woocommerce-variation-add-to-cart variations_button">
					<div class="quantity">
						<input type="number" class="input-text qty text" name="quantity" value="1" min="1">
					</div>
					<input type="hidden" name="variation_id" class="variation_id" value="0">
					<button type="submit" class="single_add_to_cart_button button">Add to cart</button>
				</div>
			</div>
		</form>
	</div>
	<script type="text/template" id="tmpl-variation-template">
		<div class="woocommerce-variation-description">{{{ data.variation.variation_description }}}</div>
		<div class="woocommerce-variation-price">{{{ data.variation.price_html }}}</div>
		<div class="woocommerce-variation-availability">{{{ data.variation.availability_html }}}</div>
	</script>
	<script type="text/template" id="tmpl-unavailable-variation-template">
		<p>Sorry, this product is unavailable. Please choose a different combination.</p>
	</script>
	<script>
		window.wp = window.wp || {
			template: function() {
				return function() {
					return '';
				};
			}
		};
		window.wc_single_product_params = ${JSON.stringify(params)};
		window.wc_add_to_cart_variation_params = {
			wc_ajax_url: '/?wc-ajax=%%endpoint%%',
			i18n_no_matching_variations_text: 'No matches',
			i18n_make_a_selection_text: 'Select options',
			i18n_unavailable_text: 'Unavailable',
			i18n_reset_alert_text: 'Selection cleared'
		};
		window.oliVigSettings = ${JSON.stringify(settings)};
	</script>
	<script src="${assets.jquery}"></script>
	${flexsliderScript}
	<script src="${assets.singleProduct}"></script>
	<script src="${assets.variation}"></script>
	<script src="${assets.frontend}"></script>
</body>
</html>`;
}

module.exports = {
	IMAGES,
	buildCatalog,
	buildProductPage,
};
