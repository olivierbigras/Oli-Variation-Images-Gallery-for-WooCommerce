/**
 * Frontend scripts — swap the default product gallery for a variation.
 *
 * @package Oli_Variation_Gallery
 */

(function($) {
	'use strict';

	var OliVIG = {
		originalGallery: [],
		currentGallery: [],
		requestToken: 0,

		init: function() {
			if (typeof oliVigSettings === 'undefined') {
				return;
			}

			this.storeOriginalGallery();
			this.applyLayout();
			this.bindEvents();
			this.preloadVariationImages();
		},

		gallery: function() {
			return $('.woocommerce-product-gallery').first();
		},

		applyLayout: function() {
			var $gallery = this.gallery();
			if (!$gallery.length) {
				return;
			}

			var width = parseInt(oliVigSettings.galleryWidth, 10);
			if (isNaN(width)) {
				width = 100;
			}
			width = Math.max(50, Math.min(100, width));
			$gallery[0].style.setProperty('--oli-vig-gallery-width', width + '%');

			var columns = parseInt(oliVigSettings.thumbnailColumns, 10);
			if (isNaN(columns)) {
				columns = 4;
			}
			columns = Math.max(1, Math.min(8, columns));

			$gallery.removeClass(function(index, className) {
				return (className.match(/(^|\s)(oli-vig-columns-|columns-|woocommerce-product-gallery--columns-)\S+/g) || []).join(' ');
			});
			$gallery.addClass('oli-vig-gallery');
			$gallery.addClass('oli-vig-columns-' + columns);
			$gallery.addClass('columns-' + columns);
			$gallery.addClass('woocommerce-product-gallery--columns-' + columns);
			$gallery.attr('data-columns', columns);

			$gallery.removeClass('oli-vig-thumbs-bottom oli-vig-thumbs-left oli-vig-thumbs-right');
			$gallery.addClass('oli-vig-thumbs-' + (oliVigSettings.thumbnailPosition || 'bottom'));
		},

		bindEvents: function() {
			var self = this;

			$(document.body).on('found_variation', '.variations_form', function(event, variation) {
				self.onVariationChange(variation);
			});

			$(document.body).on('reset_data hide_variation', '.variations_form', function() {
				if (oliVigSettings.resetOnClear) {
					self.resetToOriginalGallery();
				}
			});
		},

		readSlide: function($slide) {
			var $img = $slide.find('img').first();
			var $link = $slide.find('a').first();
			var full = $img.attr('data-large_image') || $link.attr('href') || $img.attr('src') || '';

			return {
				id: parseInt($img.attr('data-id') || $slide.attr('data-attachment-id') || '0', 10) || 0,
				src: $img.attr('src') || '',
				src_w: parseInt($img.attr('width') || '0', 10) || 0,
				src_h: parseInt($img.attr('height') || '0', 10) || 0,
				srcset: $img.attr('srcset') || '',
				sizes: $img.attr('sizes') || '',
				full_src: full,
				full_src_w: parseInt($img.attr('data-large_image_width') || '0', 10) || 0,
				full_src_h: parseInt($img.attr('data-large_image_height') || '0', 10) || 0,
				thumb_src: $slide.attr('data-thumb') || $img.attr('src') || '',
				thumb_src_w: 0,
				thumb_src_h: 0,
				alt: $img.attr('alt') || '',
				title: $img.attr('title') || '',
				thumb_alt: $slide.attr('data-thumb-alt') || $img.attr('alt') || ''
			};
		},

		storeOriginalGallery: function() {
			var self = this;
			this.originalGallery = [];

			this.gallery().find('.woocommerce-product-gallery__image').each(function() {
				self.originalGallery.push(self.readSlide($(this)));
			});

			this.currentGallery = this.originalGallery.slice();
		},

		onVariationChange: function(variation) {
			if (!variation) {
				return;
			}

			if (variation.oli_vig_gallery && variation.oli_vig_gallery.length) {
				this.replaceGallery(variation.oli_vig_gallery);
				return;
			}

			if (variation.variation_id) {
				this.fetchGallery(variation.variation_id);
				return;
			}

			if (oliVigSettings.resetOnClear) {
				this.resetToOriginalGallery();
			}
		},

		fetchGallery: function(variationId) {
			var self = this;
			var token = ++this.requestToken;

			$.post(oliVigSettings.ajaxUrl, {
				action: 'oli_vig_get_variation_gallery',
				nonce: oliVigSettings.nonce,
				variation_id: variationId
			}).done(function(response) {
				if (token !== self.requestToken) {
					return;
				}
				if (response && response.success && response.data && response.data.gallery && response.data.gallery.length) {
					self.replaceGallery(response.data.gallery);
					return;
				}
				if (oliVigSettings.resetOnClear) {
					self.resetToOriginalGallery();
				}
			});
		},

		replaceGallery: function(images) {
			if (!images || !images.length) {
				return;
			}

			this.currentGallery = images;
			this.renderGallery(images);
		},

		resetToOriginalGallery: function() {
			if (!this.originalGallery.length) {
				return;
			}

			this.currentGallery = this.originalGallery.slice();
			this.renderGallery(this.currentGallery);
		},

		destroyGallery: function($gallery) {
			if ($gallery.data('flexslider')) {
				try {
					$gallery.flexslider('destroy');
				} catch (e) {
					// Theme galleries may not expose a destroy method.
				}
				$gallery.removeData('flexslider');
			}

			$gallery.find('.zoomImg').remove();
			$gallery.find('.flex-viewport').children().unwrap();
			$gallery.find('ol.flex-control-nav, ul.flex-direction-nav').remove();
			$gallery.removeClass('flexslider');
			$gallery.off('.wc-product-gallery');
			$gallery.removeData('wc_product_gallery');
		},

		buildSlide: function(image, isFirst) {
			var $img = $('<img/>', {
				src: image.src || image.url || '',
				alt: image.alt || '',
				title: image.title || '',
				'class': isFirst ? 'wp-post-image' : '',
				'data-src': image.src || image.url || '',
				'data-large_image': image.full_src || image.full || image.src || '',
				'data-large_image_width': image.full_src_w || '',
				'data-large_image_height': image.full_src_h || ''
			});

			if (image.src_w) {
				$img.attr('width', image.src_w);
			}
			if (image.src_h) {
				$img.attr('height', image.src_h);
			}
			if (image.srcset) {
				$img.attr('srcset', image.srcset);
			}
			if (image.sizes) {
				$img.attr('sizes', image.sizes);
			}

			var $anchor = $('<a/>', {
				href: image.full_src || image.full || image.src || ''
			}).append($img);

			return $('<div/>', {
				'class': 'woocommerce-product-gallery__image',
				'data-thumb': image.thumb_src || image.thumb || image.src || '',
				'data-thumb-alt': image.thumb_alt || image.alt || ''
			}).append($anchor);
		},

		renderGallery: function(images) {
			var $gallery = this.gallery();
			if (!$gallery.length) {
				return;
			}

			var $wrapper = $gallery.find('.woocommerce-product-gallery__wrapper');
			if (!$wrapper.length) {
				$wrapper = $('<div/>', { 'class': 'woocommerce-product-gallery__wrapper' });
				$gallery.prepend($wrapper);
			}

			this.destroyGallery($gallery);
			$wrapper.empty();

			$.each(images, function(index, image) {
				$wrapper.append(OliVIG.buildSlide(image, 0 === index));
			});

			this.applyLayout();

			if (typeof $.fn.wc_product_gallery === 'function') {
				$gallery.wc_product_gallery();
			}

			$gallery.trigger('woocommerce_gallery_init', [images]);
		},

		preloadVariationImages: function() {
			if (!oliVigSettings.preloadImages) {
				return;
			}

			$('.variations_form').each(function() {
				var variations = $(this).data('product_variations');
				if (!variations || !variations.length) {
					return;
				}

				$.each(variations, function(index, variation) {
					if (!variation.oli_vig_gallery) {
						return;
					}
					$.each(variation.oli_vig_gallery, function(imageIndex, image) {
						if (image.src) {
							var full = new Image();
							full.src = image.src;
						}
						if (image.thumb_src) {
							var thumb = new Image();
							thumb.src = image.thumb_src;
						}
					});
				});
			});
		}
	};

	$(function() {
		OliVIG.init();
	});

})(jQuery);
