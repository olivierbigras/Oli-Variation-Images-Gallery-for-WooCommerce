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
		renderedSignature: '',

		init: function() {
			if (typeof oliVigSettings === 'undefined') {
				return;
			}

			this.storeOriginalGallery();
			this.renderedSignature = this.gallerySignature(this.originalGallery);
			this.applyLayout();
			this.bindEvents();
			this.bindViewport();
			this.preloadVariationImages();
			this.enhanceWhenReady();
		},

		gallery: function() {
			return $('.woocommerce-product-gallery').first();
		},

		prefersReducedMotion: function() {
			return !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
		},

		isNarrowViewport: function() {
			return !!(window.matchMedia && window.matchMedia('(max-width: 767px)').matches);
		},

		resolvedPosition: function() {
			var position = oliVigSettings.thumbnailPosition || 'bottom';
			if (this.isNarrowViewport() && (position === 'left' || position === 'right')) {
				return 'bottom';
			}
			return position;
		},

		viewImageLabel: function(index, total) {
			var template = (oliVigSettings.i18n && oliVigSettings.i18n.viewImage) ? oliVigSettings.i18n.viewImage : 'View image %1$d of %2$d';
			return template.replace('%1$d', String(index)).replace('%2$d', String(total));
		},

		gallerySignature: function(images) {
			if (!images || !images.length) {
				return '';
			}
			return $.map(images, function(image) {
				return String(image.id || 0) + ':' + (image.full_src || image.src || '');
			}).join('|');
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
				columns = 0;
			}

			if (columns >= 1) {
				columns = Math.max(1, Math.min(8, columns));
				$gallery.removeClass(function(index, className) {
					return (className.match(/(^|\s)(oli-vig-columns-|columns-|woocommerce-product-gallery--columns-)\S+/g) || []).join(' ');
				});
				$gallery.addClass('oli-vig-columns-' + columns);
				$gallery.addClass('columns-' + columns);
				$gallery.addClass('woocommerce-product-gallery--columns-' + columns);
				$gallery.attr('data-columns', columns);
			}

			$gallery.addClass('oli-vig-gallery');
			$gallery.removeClass('oli-vig-thumbs-bottom oli-vig-thumbs-left oli-vig-thumbs-right');
			$gallery.addClass('oli-vig-thumbs-' + this.resolvedPosition());
		},

		bindEvents: function() {
			var self = this;

			$(document.body).on('found_variation.oli-vig', '.variations_form', function(event, variation) {
				self.onVariationChange(variation);
			});

			$(document.body).on('reset_data.oli-vig hide_variation.oli-vig', '.variations_form', function() {
				if (oliVigSettings.resetOnClear) {
					self.resetToOriginalGallery();
				}
			});
		},

		bindViewport: function() {
			var self = this;
			if (!window.matchMedia) {
				return;
			}

			var media = window.matchMedia('(max-width: 767px)');
			var onChange = function() {
				self.applyLayout();
				if (self.currentGallery.length) {
					self.renderedSignature = '';
					self.renderGallery(self.currentGallery);
				}
			};

			if (typeof media.addEventListener === 'function') {
				media.addEventListener('change', onChange);
			} else if (typeof media.addListener === 'function') {
				media.addListener(onChange);
			}
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

		normalizeVariationImage: function(image) {
			if (!image || typeof image !== 'object') {
				return null;
			}

			var src = image.src || image.url || '';
			var full = image.full_src || image.full || src;
			if (!src && !full) {
				return null;
			}

			return {
				id: parseInt(image.id || image.image_id || '0', 10) || 0,
				src: src || full,
				src_w: parseInt(image.src_w || '0', 10) || 0,
				src_h: parseInt(image.src_h || '0', 10) || 0,
				srcset: image.srcset || '',
				sizes: image.sizes || '',
				full_src: full || src,
				full_src_w: parseInt(image.full_src_w || '0', 10) || 0,
				full_src_h: parseInt(image.full_src_h || '0', 10) || 0,
				thumb_src: image.gallery_thumbnail_src || image.thumb_src || image.thumb || src || full,
				thumb_src_w: parseInt(image.gallery_thumbnail_src_w || image.thumb_src_w || '0', 10) || 0,
				thumb_src_h: parseInt(image.gallery_thumbnail_src_h || image.thumb_src_h || '0', 10) || 0,
				alt: image.alt || '',
				title: image.title || '',
				thumb_alt: image.alt || ''
			};
		},

		galleryFromVariationImage: function(variation) {
			var images = this.originalGallery.slice();
			var image = this.normalizeVariationImage(variation && variation.image);

			if (!image) {
				return images;
			}

			if (images.length) {
				images[0] = image;
				return images;
			}

			return [image];
		},

		onVariationChange: function(variation) {
			if (!variation) {
				return;
			}

			if (variation.oli_vig_gallery && variation.oli_vig_gallery.length) {
				this.replaceGallery(variation.oli_vig_gallery);
				return;
			}

			// Extra gallery is empty or omitted: keep native behaviour (variation image + parent thumbs).
			// found_variation already includes image + oli_vig_gallery, so do not AJAX.
			this.replaceGallery(this.galleryFromVariationImage(variation));
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

		closePhotoswipe: function() {
			var $pswp = $('.pswp');
			if (!$pswp.length) {
				return;
			}

			$pswp.removeClass('pswp--open pswp--animate_opacity pswp--visible pswp--zoom-allowed pswp--notouch');
			$pswp.attr('aria-hidden', 'true');
			$('body').removeClass('pswp-open');
		},

		destroyGallery: function($gallery) {
			this.closePhotoswipe();

			$gallery.off('.wc-product-gallery');
			$gallery.off('.oli-vig');
			$gallery.off('click');
			$gallery.find('.woocommerce-product-gallery__image a').off('click');
			$gallery.find('.woocommerce-product-gallery__trigger').off('click').remove();

			$gallery.find('.woocommerce-product-gallery__image').each(function() {
				var $image = $(this);
				$image.trigger('zoom.destroy');
				$image.removeData('zoom');
			});
			$gallery.find('.zoomImg').remove();

			if ($gallery.data('flexslider')) {
				try {
					$gallery.flexslider('destroy');
				} catch (e) {
					// Theme galleries may not expose a destroy method.
				}
				$gallery.removeData('flexslider');
			}

			$gallery.find('.flex-viewport').children().unwrap();
			$gallery.find('ol.flex-control-nav, ul.flex-direction-nav').remove();
			$gallery.removeClass('flexslider');
			$gallery.removeData('wc_product_gallery');
		},

		fallbackAlt: function(image, index, total) {
			if (image && image.alt) {
				return image.alt;
			}
			if (image && image.thumb_alt) {
				return image.thumb_alt;
			}
			if (oliVigSettings.productName) {
				return oliVigSettings.productName + ', image ' + String(index);
			}
			return this.viewImageLabel(index, total);
		},

		buildSlide: function(image, isFirst, index, total) {
			var alt = this.fallbackAlt(image, index, total);
			var label = this.viewImageLabel(index, total);
			var $img = $('<img/>', {
				src: image.src || image.url || '',
				alt: alt,
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
				href: image.full_src || image.full || image.src || '',
				'aria-label': label
			}).append($img);

			return $('<div/>', {
				'class': 'woocommerce-product-gallery__image',
				'data-thumb': image.thumb_src || image.thumb || image.src || '',
				'data-thumb-alt': alt
			}).append($anchor);
		},

		enhanceWhenReady: function() {
			var self = this;
			var attempts = 0;
			var tick = function() {
				var $gallery = self.gallery();
				if ($gallery.length && $gallery.find('.flex-control-thumbs li, .woocommerce-product-gallery__image').length) {
					self.enhanceAccessibility($gallery);
					return;
				}
				attempts += 1;
				if (attempts < 20) {
					window.setTimeout(tick, 50);
				}
			};
			tick();
		},

		enhanceAccessibility: function($gallery) {
			var self = this;
			var $slides = $gallery.find('.woocommerce-product-gallery__image');
			var slideTotal = $slides.length;

			$slides.each(function(index) {
				var $slide = $(this);
				var $img = $slide.find('img').first();
				var $link = $slide.find('a').first();
				var number = index + 1;
				var label = self.viewImageLabel(number, slideTotal);
				var alt = $img.attr('alt') || '';

				if (!alt) {
					alt = oliVigSettings.productName ? (oliVigSettings.productName + ', image ' + String(number)) : label;
					$img.attr('alt', alt);
					if (!$slide.attr('data-thumb-alt')) {
						$slide.attr('data-thumb-alt', alt);
					}
				}

				if ($link.length) {
					$link.attr('aria-label', label);
				}
			});

			var $thumbs = $gallery.find('.flex-control-thumbs li');
			$thumbs.each(function(index) {
				var $item = $(this);
				var $img = $item.find('img').first();
				var $button = $item.children('button.oli-vig-thumb');
				var number = index + 1;
				var total = $thumbs.length;
				var label = self.viewImageLabel(number, total);

				$item.removeAttr('tabindex');
				$img.removeAttr('tabindex');

				if (!$button.length) {
					$img.wrap('<button type="button" class="oli-vig-thumb"></button>');
					$button = $item.children('button.oli-vig-thumb');
				}

				$img.attr('alt', '');
				$button.attr({
					type: 'button',
					'aria-label': label,
					'aria-current': $img.hasClass('flex-active') ? 'true' : 'false'
				});
				$button.removeAttr('tabindex');

				$button.off('click.oli-vig-thumb keydown.oli-vig-thumb').on('click.oli-vig-thumb', function(event) {
					event.preventDefault();
					event.stopPropagation();
					self.goToSlide($gallery, index);
				});
			});

			this.applyReducedMotion($gallery);
			this.hookSliderAfter($gallery);
		},

		goToSlide: function($gallery, index) {
			if ($gallery.data('flexslider') && typeof $gallery.flexslider === 'function') {
				$gallery.flexslider(index);
				return;
			}

			var $img = $gallery.find('.flex-control-thumbs li').eq(index).find('img').first();
			if ($img.length) {
				$img.trigger('click');
			}
		},

		updateThumbState: function($gallery) {
			$gallery.find('.flex-control-thumbs li').each(function() {
				var $item = $(this);
				var $button = $item.children('button.oli-vig-thumb');
				var $img = $item.find('img').first();
				if ($button.length) {
					$button.attr('aria-current', $img.hasClass('flex-active') ? 'true' : 'false');
				}
				$item.removeAttr('tabindex');
				$img.removeAttr('tabindex');
				$button.removeAttr('tabindex');
			});
		},

		applyReducedMotion: function($gallery) {
			if (!this.prefersReducedMotion()) {
				return;
			}

			var slider = $gallery.data('flexslider');
			if (slider && slider.vars) {
				slider.vars.animationSpeed = 0;
				slider.vars.slideshowSpeed = 0;
			}
		},

		hookSliderAfter: function($gallery) {
			var self = this;
			var slider = $gallery.data('flexslider');
			if (!slider || !slider.vars || slider.vars.oliVigAfterHooked) {
				this.updateThumbState($gallery);
				return;
			}

			var previous = slider.vars.after;
			slider.vars.after = function(instance) {
				if (typeof previous === 'function') {
					previous(instance);
				}
				self.updateThumbState($gallery);
			};
			slider.vars.oliVigAfterHooked = true;
			this.updateThumbState($gallery);
		},

		renderGallery: function(images) {
			var signature = this.gallerySignature(images);
			if (signature && signature === this.renderedSignature) {
				return;
			}

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

			var total = images.length;
			$.each(images, function(index, image) {
				$wrapper.append(OliVIG.buildSlide(image, 0 === index, index + 1, total));
			});

			this.applyLayout();
			$gallery.find('.woocommerce-product-gallery__trigger').remove();

			if (typeof $.fn.wc_product_gallery === 'function') {
				$gallery.wc_product_gallery();
			}

			$gallery.find('.woocommerce-product-gallery__trigger').slice(1).remove();
			this.enhanceAccessibility($gallery);
			this.renderedSignature = signature;
			$gallery.trigger('woocommerce_gallery_init.oli-vig', [images]);
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
