/**
 * Frontend scripts
 *
 * @package Oli_Variation_Images_Gallery
 */

(function($) {
	'use strict';

	var OliVIG = {
		currentGallery: [],
		currentIndex: 0,
		originalGallery: [],
		lightboxOpen: false,
		lastFocus: null,
		touchStartX: 0,
		touchEndX: 0,

		init: function() {
			if (typeof oliVigSettings === 'undefined') {
				return;
			}

			this.bindEvents();
			this.storeOriginalGallery();
			this.ensureLiveRegion();
			this.applyLayout();
			this.enhanceThumbnails();
			this.initTouchSupport();
		},

		labels: function() {
			return (oliVigSettings && oliVigSettings.i18n) ? oliVigSettings.i18n : {};
		},

		formatShowImage: function(index, total) {
			var template = this.labels().showImage || 'Show image %1$d of %2$d';
			return template.replace('%1$d', index).replace('%2$d', total).replace('%d', index).replace('%d', total);
		},

		prefersReducedMotion: function() {
			return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
		},

		transitionEffect: function() {
			if (this.prefersReducedMotion()) {
				return 'none';
			}
			return oliVigSettings.transitionEffect || 'none';
		},

		galleryImages: function() {
			return this.currentGallery.length > 0 ? this.currentGallery : this.originalGallery;
		},

		ensureLiveRegion: function() {
			var $gallery = $('.woocommerce-product-gallery').first();
			if (!$gallery.length || $('#oli-vig-live-region').length) {
				return;
			}

			$('<div/>', {
				id: 'oli-vig-live-region',
				'class': 'screen-reader-text',
				'aria-live': 'polite',
				'aria-atomic': 'true'
			}).appendTo($gallery);
		},

		announce: function(index) {
			var gallery = this.galleryImages();
			var total = gallery.length;
			if (!total) {
				return;
			}

			var text = this.formatShowImage(index + 1, total);
			var image = gallery[index];
			if (image && image.alt) {
				text += ': ' + image.alt;
			}
			$('#oli-vig-live-region').text(text);
		},

		applyLayout: function() {
			var $gallery = $('.woocommerce-product-gallery');
			if (!$gallery.length) {
				return;
			}

			var width = parseInt(oliVigSettings.galleryWidth, 10);
			if (isNaN(width)) {
				width = 100;
			}
			width = Math.max(50, Math.min(100, width));
			document.documentElement.style.setProperty('--oli-vig-gallery-width', width + '%');

			var columns = parseInt(oliVigSettings.thumbnailColumns, 10);
			if (isNaN(columns)) {
				columns = 4;
			}
			columns = Math.max(1, Math.min(8, columns));
			$gallery.removeClass(function(index, className) {
				return (className.match(/(^|\s)oli-vig-columns-\S+/g) || []).join(' ');
			});
			$gallery.addClass('oli-vig-columns-' + columns);

			$gallery.removeClass('oli-vig-transition-fade oli-vig-transition-slide oli-vig-transition-none');
			$gallery.addClass('oli-vig-transition-' + this.transitionEffect());

			if (oliVigSettings.enableZoom) {
				$gallery.addClass('oli-vig-zoom-enabled');
			}

			var $thumbs = $gallery.find('.flex-control-thumbs, ol.flex-control-nav');
			var position = oliVigSettings.thumbnailPosition || 'bottom';
			var useSlider = oliVigSettings.thumbnailMode === 'slider' || position === 'left' || position === 'right';

			$thumbs.removeClass('mode-slider mode-grid position-bottom position-left position-right');
			$thumbs.addClass('position-' + position);
			$thumbs.addClass(useSlider ? 'mode-slider' : 'mode-grid');

			if (useSlider) {
				this.initThumbnailSlider($thumbs);
			}
		},

		initThumbnailSlider: function($thumbs) {
			if (!$thumbs.length || $thumbs.parent().hasClass('oli-vig-thumbs-slider-wrap')) {
				return;
			}

			$thumbs.wrap('<div class="oli-vig-thumbs-slider-wrap"></div>');
			var $wrap = $thumbs.parent();
			var labels = this.labels();
			$wrap.prepend($('<button/>', {
				type: 'button',
				'class': 'oli-vig-slider-arrow prev',
				'aria-label': labels.previous || 'Previous'
			}).html('&lsaquo;'));
			$wrap.append($('<button/>', {
				type: 'button',
				'class': 'oli-vig-slider-arrow next',
				'aria-label': labels.next || 'Next'
			}).html('&rsaquo;'));

			$wrap.on('click', '.oli-vig-slider-arrow.prev', function(e) {
				e.preventDefault();
				OliVIG.scrollThumbnails($thumbs, -1);
			});
			$wrap.on('click', '.oli-vig-slider-arrow.next', function(e) {
				e.preventDefault();
				OliVIG.scrollThumbnails($thumbs, 1);
			});

			$wrap.on('keydown', function(e) {
				if (e.key !== 'ArrowLeft' && e.key !== 'ArrowRight' && e.key !== 'ArrowUp' && e.key !== 'ArrowDown') {
					return;
				}
				e.preventDefault();
				var gallery = OliVIG.galleryImages();
				if (!gallery.length) {
					return;
				}
				var next = OliVIG.currentIndex;
				if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
					next = Math.min(OliVIG.currentIndex + 1, gallery.length - 1);
				} else {
					next = Math.max(OliVIG.currentIndex - 1, 0);
				}
				OliVIG.showImage(next);
				OliVIG.focusThumb(next);
			});
		},

		scrollThumbnails: function($thumbs, direction) {
			var vertical = $thumbs.hasClass('position-left') || $thumbs.hasClass('position-right');
			var amount = 120 * direction;
			if (vertical) {
				$thumbs.stop().animate({ scrollTop: $thumbs.scrollTop() + amount }, 200);
			} else {
				$thumbs.stop().animate({ scrollLeft: $thumbs.scrollLeft() + amount }, 200);
			}
		},

		bindEvents: function() {
			var self = this;

			$('.variations_form').on('found_variation', function(event, variation) {
				self.onVariationChange(variation);
			});

			$('.variations_form').on('reset_data', function() {
				if (oliVigSettings.resetOnClear) {
					self.resetToOriginalGallery();
				}
			});

			if (oliVigSettings.enableZoom) {
				$(document).on('click', '.woocommerce-product-gallery__image', function() {
					if ($(this).hasClass('zoomed')) {
						$(this).removeClass('zoomed');
					} else {
						$('.woocommerce-product-gallery__image').removeClass('zoomed');
						$(this).addClass('zoomed');
					}
				});
			}

			if (oliVigSettings.enableLightbox) {
				this.initLightbox();
			}

			$(document).on('click', '.oli-vig-thumb-button', function(e) {
				e.preventDefault();
				var index = parseInt($(this).attr('data-oli-vig-index'), 10);
				if (isNaN(index)) {
					index = $(this).closest('li').index();
				}
				self.showImage(index);
			});
		},

		storeOriginalGallery: function() {
			var self = this;
			this.originalGallery = [];

			$('.woocommerce-product-gallery__wrapper .woocommerce-product-gallery__image').each(function() {
				var $img = $(this).find('img');
				var imgData = {
					url: $img.attr('src'),
					thumb: $img.attr('src'),
					full: $(this).find('a').attr('href') || $img.attr('src'),
					alt: $img.attr('alt'),
					title: $img.attr('title'),
					srcset: $img.attr('srcset'),
					sizes: $img.attr('sizes')
				};
				self.originalGallery.push(imgData);
			});

			this.currentGallery = this.originalGallery.slice();
			this.currentIndex = 0;
		},

		onVariationChange: function(variation) {
			if (!variation.oli_vig_gallery || variation.oli_vig_gallery.length === 0) {
				if (oliVigSettings.resetOnClear) {
					this.resetToOriginalGallery();
				}
				return;
			}

			this.replaceGallery(variation.oli_vig_gallery);
		},

		replaceGallery: function(images) {
			if (!images || images.length === 0) {
				return;
			}

			var $gallery = $('.woocommerce-product-gallery__wrapper');
			var $mainImage = $('.woocommerce-product-gallery__image').first();
			var firstImage = images[0];
			var $img = $mainImage.find('img');
			var $link = $mainImage.find('a');

			$img.attr('src', firstImage.url);
			$img.attr('alt', firstImage.alt || '');
			$img.attr('title', firstImage.title || '');

			if (firstImage.srcset) {
				$img.attr('srcset', firstImage.srcset);
			}
			if (firstImage.sizes) {
				$img.attr('sizes', firstImage.sizes);
			}

			if ($link.length) {
				$link.attr('href', firstImage.full);
			}

			this.updateThumbnails(images);

			this.currentGallery = images;
			this.currentIndex = 0;
			this.announce(0);

			if (typeof $.fn.zoom !== 'undefined' && oliVigSettings.enableZoom) {
				$gallery.trigger('zoom.destroy');
				$gallery.zoom();
			}
		},

		updateThumbnails: function(images) {
			var $thumbsContainer = $('.flex-control-thumbs, .woocommerce-product-gallery__thumbnails, ol.flex-control-nav');

			if ($thumbsContainer.length === 0) {
				return;
			}

			$thumbsContainer.empty();

			if (oliVigSettings.thumbnailMode === 'slider') {
				$thumbsContainer.addClass('mode-slider');
			} else {
				$thumbsContainer.removeClass('mode-slider');
			}

			var $gallery = $('.woocommerce-product-gallery');
			$gallery.removeClass('oli-vig-transition-fade oli-vig-transition-slide oli-vig-transition-none');
			$gallery.addClass('oli-vig-transition-' + this.transitionEffect());

			var self = this;
			var total = images.length;
			$.each(images, function(index, image) {
				var $thumb = $('<li/>').append(self.createThumbButton(image, index, total, index === 0));
				$thumbsContainer.append($thumb);
			});
		},

		createThumbButton: function(image, index, total, isActive) {
			var $button = $('<button/>', {
				type: 'button',
				'class': 'oli-vig-thumb-button',
				'data-oli-vig-index': index,
				'aria-label': this.formatShowImage(index + 1, total)
			});
			if (isActive) {
				$button.attr('aria-current', 'true');
			}
			$button.append($('<img/>', {
				src: image.thumb || image.url,
				alt: ''
			}));
			return $button;
		},

		enhanceThumbnails: function() {
			var $thumbs = $('.flex-control-thumbs, .woocommerce-product-gallery__thumbnails, ol.flex-control-nav');
			if (!$thumbs.length) {
				return;
			}

			var self = this;
			var $items = $thumbs.find('li');
			var total = $items.length;

			$items.each(function(index) {
				var $li = $(this);
				var $existing = $li.find('button.oli-vig-thumb-button');
				if ($existing.length) {
					$existing.attr('data-oli-vig-index', index);
					$existing.attr('aria-label', self.formatShowImage(index + 1, total));
					if (index === self.currentIndex) {
						$existing.attr('aria-current', 'true');
					} else {
						$existing.removeAttr('aria-current');
					}
					return;
				}

				var $img = $li.find('img').first();
				if (!$img.length) {
					return;
				}

				var image = {
					thumb: $img.attr('src'),
					url: $img.attr('src'),
					alt: $img.attr('alt') || ''
				};
				$li.empty().append(self.createThumbButton(image, index, total, index === self.currentIndex));
			});
		},

		setActiveThumb: function(index) {
			var $thumbs = $('.flex-control-thumbs, .woocommerce-product-gallery__thumbnails, ol.flex-control-nav');
			$thumbs.find('li').removeClass('active');
			$thumbs.find('.oli-vig-thumb-button').removeAttr('aria-current');

			var $activeLi = $thumbs.find('li').eq(index);
			$activeLi.addClass('active');
			$activeLi.find('.oli-vig-thumb-button').attr('aria-current', 'true');

			if ($activeLi.length && $thumbs.hasClass('mode-slider')) {
				var vertical = $thumbs.hasClass('position-left') || $thumbs.hasClass('position-right');
				if (vertical) {
					$thumbs.stop().animate({
						scrollTop: $thumbs.scrollTop() + $activeLi.position().top - 8
					}, 200);
				} else {
					$thumbs.stop().animate({
						scrollLeft: $thumbs.scrollLeft() + $activeLi.position().left - 8
					}, 200);
				}
			}
		},

		focusThumb: function(index) {
			var $button = $('.flex-control-thumbs, .woocommerce-product-gallery__thumbnails, ol.flex-control-nav')
				.find('li')
				.eq(index)
				.find('.oli-vig-thumb-button');
			if ($button.length) {
				$button.trigger('focus');
			}
		},

		applyImage: function(image, $img, $link) {
			$img.attr('src', image.url);
			$img.attr('alt', image.alt || '');

			if (image.srcset) {
				$img.attr('srcset', image.srcset);
			}
			if (image.sizes) {
				$img.attr('sizes', image.sizes);
			}
			if (image.title) {
				$img.attr('title', image.title);
			}

			if ($link.length) {
				$link.attr('href', image.full);
			}
		},

		showImage: function(index) {
			var gallery = this.galleryImages();
			if (!gallery[index]) {
				return;
			}

			var image = gallery[index];
			var $mainImage = $('.woocommerce-product-gallery__image').first();
			var $img = $mainImage.find('img');
			var $link = $mainImage.find('a');
			var self = this;
			var transitionEffect = this.transitionEffect();

			if (transitionEffect === 'fade') {
				$img.addClass('oli-vig-fading-out');
				setTimeout(function() {
					self.applyImage(image, $img, $link);
					$img.removeClass('oli-vig-fading-out').addClass('oli-vig-fading-in');
					setTimeout(function() {
						$img.removeClass('oli-vig-fading-in');
					}, 300);
				}, 300);
			} else if (transitionEffect === 'slide') {
				var direction = index > self.currentIndex ? 'left' : 'right';
				$img.addClass('oli-vig-slide-out-' + direction);
				setTimeout(function() {
					self.applyImage(image, $img, $link);
					var slideInClass = 'oli-vig-slide-in-' + (direction === 'left' ? 'right' : 'left');
					$img.removeClass('oli-vig-slide-out-left oli-vig-slide-out-right').addClass(slideInClass);
					setTimeout(function() {
						$img.removeClass('oli-vig-slide-in-left oli-vig-slide-in-right');
					}, 400);
				}, 400);
			} else {
				this.applyImage(image, $img, $link);
			}

			this.currentIndex = index;
			this.setActiveThumb(index);
			this.announce(index);
		},

		resetToOriginalGallery: function() {
			if (this.originalGallery.length === 0) {
				return;
			}

			var $mainImage = $('.woocommerce-product-gallery__image').first();
			var firstImage = this.originalGallery[0];
			var $img = $mainImage.find('img');
			var $link = $mainImage.find('a');

			this.applyImage(firstImage, $img, $link);
			this.updateThumbnails(this.originalGallery);
			this.currentGallery = this.originalGallery.slice();
			this.currentIndex = 0;
			this.announce(0);
		},

		lightboxFocusable: function() {
			return $('.oli-vig-lightbox.active').find('button:not(:disabled)').filter(':visible');
		},

		initLightbox: function() {
			var self = this;

			if ($('.oli-vig-lightbox').length === 0) {
				var labels = this.labels();
				var $lightbox = $('<div/>', {
					'class': 'oli-vig-lightbox',
					role: 'dialog',
					'aria-modal': 'true',
					'aria-label': labels.lightbox || 'Image lightbox'
				});
				var $content = $('<div/>', { 'class': 'oli-vig-lightbox-content' });
				$content.append($('<button/>', {
					type: 'button',
					'class': 'oli-vig-lightbox-close',
					'aria-label': labels.close || 'Close'
				}).html('&times;'));
				$content.append($('<button/>', {
					type: 'button',
					'class': 'oli-vig-lightbox-prev',
					'aria-label': labels.previous || 'Previous'
				}).html('&lsaquo;'));
				$content.append($('<button/>', {
					type: 'button',
					'class': 'oli-vig-lightbox-next',
					'aria-label': labels.next || 'Next'
				}).html('&rsaquo;'));
				$content.append($('<img/>', {
					'class': 'oli-vig-lightbox-image',
					src: '',
					alt: ''
				}));
				$lightbox.append($content);
				$('body').append($lightbox);
			}

			$(document).on('click', '.woocommerce-product-gallery__image a', function(e) {
				e.preventDefault();
				var index = $(this).closest('.woocommerce-product-gallery__image').index();
				if (self.currentGallery.length) {
					index = self.currentIndex;
				}
				self.openLightbox(index);
			});

			$(document).on('click', '.oli-vig-lightbox-close, .oli-vig-lightbox', function(e) {
				if ($(e.target).hasClass('oli-vig-lightbox') || $(e.target).hasClass('oli-vig-lightbox-close')) {
					self.closeLightbox();
				}
			});

			$(document).on('click', '.oli-vig-lightbox-prev', function(e) {
				e.stopPropagation();
				self.lightboxPrev();
			});

			$(document).on('click', '.oli-vig-lightbox-next', function(e) {
				e.stopPropagation();
				self.lightboxNext();
			});

			$(document).on('keydown', function(e) {
				if (!self.lightboxOpen) {
					return;
				}

				if (e.key === 'Escape') {
					e.preventDefault();
					self.closeLightbox();
					return;
				}

				if (e.key === 'ArrowLeft') {
					e.preventDefault();
					self.lightboxPrev();
					return;
				}

				if (e.key === 'ArrowRight') {
					e.preventDefault();
					self.lightboxNext();
					return;
				}

				if (e.key === 'Tab') {
					var $focusable = self.lightboxFocusable();
					if (!$focusable.length) {
						return;
					}
					var first = $focusable.get(0);
					var last = $focusable.get($focusable.length - 1);
					if (e.shiftKey && document.activeElement === first) {
						e.preventDefault();
						last.focus();
					} else if (!e.shiftKey && document.activeElement === last) {
						e.preventDefault();
						first.focus();
					} else if (!$('.oli-vig-lightbox').has(document.activeElement).length) {
						e.preventDefault();
						first.focus();
					}
				}
			});
		},

		openLightbox: function(index) {
			var gallery = this.galleryImages();

			if (!gallery[index]) {
				return;
			}

			this.currentIndex = index;
			this.lightboxOpen = true;
			this.lastFocus = document.activeElement;

			var image = gallery[index];
			$('.oli-vig-lightbox-image').attr('src', image.full).attr('alt', image.alt || '');
			$('.oli-vig-lightbox').addClass('active');

			this.updateLightboxButtons(gallery);
			this.announce(index);

			window.setTimeout(function() {
				$('.oli-vig-lightbox-close').trigger('focus');
			}, 0);
		},

		closeLightbox: function() {
			$('.oli-vig-lightbox').removeClass('active');
			this.lightboxOpen = false;
			if (this.lastFocus && typeof this.lastFocus.focus === 'function') {
				this.lastFocus.focus();
			}
			this.lastFocus = null;
		},

		lightboxPrev: function() {
			var gallery = this.galleryImages();

			if (this.currentIndex > 0) {
				this.currentIndex--;
				var image = gallery[this.currentIndex];
				$('.oli-vig-lightbox-image').attr('src', image.full).attr('alt', image.alt || '');
				this.updateLightboxButtons(gallery);
				this.announce(this.currentIndex);
			}
		},

		lightboxNext: function() {
			var gallery = this.galleryImages();

			if (this.currentIndex < gallery.length - 1) {
				this.currentIndex++;
				var image = gallery[this.currentIndex];
				$('.oli-vig-lightbox-image').attr('src', image.full).attr('alt', image.alt || '');
				this.updateLightboxButtons(gallery);
				this.announce(this.currentIndex);
			}
		},

		updateLightboxButtons: function(gallery) {
			$('.oli-vig-lightbox-prev').prop('disabled', this.currentIndex === 0);
			$('.oli-vig-lightbox-next').prop('disabled', this.currentIndex === gallery.length - 1);
		},

		initTouchSupport: function() {
			var self = this;
			var $gallery = $('.woocommerce-product-gallery');

			if ($gallery.length === 0) {
				return;
			}

			$gallery.addClass('touch-enabled');

			$gallery.on('touchstart', function(e) {
				self.touchStartX = e.changedTouches[0].screenX;
			});

			$gallery.on('touchend', function(e) {
				self.touchEndX = e.changedTouches[0].screenX;
				self.handleSwipe();
			});
		},

		handleSwipe: function() {
			var swipeThreshold = 50;
			var diff = this.touchStartX - this.touchEndX;

			if (Math.abs(diff) < swipeThreshold) {
				return;
			}

			var gallery = this.galleryImages();

			if (diff > 0 && this.currentIndex < gallery.length - 1) {
				this.showImage(this.currentIndex + 1);
			} else if (diff < 0 && this.currentIndex > 0) {
				this.showImage(this.currentIndex - 1);
			}
		}
	};

	$(document).ready(function() {
		OliVIG.init();
	});

})(jQuery);
