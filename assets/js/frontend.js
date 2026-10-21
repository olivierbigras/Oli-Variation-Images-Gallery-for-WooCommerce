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
		touchStartX: 0,
		touchEndX: 0,

		init: function() {
			if (typeof oliVigSettings === 'undefined') {
				return;
			}

			this.bindEvents();
			this.storeOriginalGallery();
			this.applyLayout();
			this.initTouchSupport();
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
			$gallery.addClass('oli-vig-transition-' + (oliVigSettings.transitionEffect || 'none'));

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
			var labels = oliVigSettings.i18n || {};
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

			// Variation change
			$('.variations_form').on('found_variation', function(event, variation) {
				self.onVariationChange(variation);
			});

			// Variation reset
			$('.variations_form').on('reset_data', function() {
				if (oliVigSettings.resetOnClear) {
					self.resetToOriginalGallery();
				}
			});

			// Zoom
			if (oliVigSettings.enableZoom) {
				$(document).on('click', '.woocommerce-product-gallery__image', function(e) {
					if ($(this).hasClass('zoomed')) {
						$(this).removeClass('zoomed');
					} else {
						$('.woocommerce-product-gallery__image').removeClass('zoomed');
						$(this).addClass('zoomed');
					}
				});
			}

			// Lightbox
			if (oliVigSettings.enableLightbox) {
				this.initLightbox();
			}
		},

		storeOriginalGallery: function() {
			var self = this;
			this.originalGallery = [];

			$('.woocommerce-product-gallery__wrapper .woocommerce-product-gallery__image').each(function() {
				var $img = $(this).find('img');
				var imgData = {
					url: $img.attr('src'),
					full: $(this).find('a').attr('href') || $img.attr('src'),
					alt: $img.attr('alt'),
					title: $img.attr('title'),
					srcset: $img.attr('srcset'),
					sizes: $img.attr('sizes')
				};
				self.originalGallery.push(imgData);
			});
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

			// Replace main image
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

			// Update thumbnails if they exist
			this.updateThumbnails(images);

			// Store current gallery for lightbox
			this.currentGallery = images;
			this.currentIndex = 0;

			// Trigger zoom update if enabled
			if (typeof $.fn.zoom !== 'undefined' && oliVigSettings.enableZoom) {
				$gallery.trigger('zoom.destroy');
				$gallery.zoom();
			}
		},

		updateThumbnails: function(images) {
			var $thumbsContainer = $('.flex-control-thumbs, .woocommerce-product-gallery__thumbnails');
			
			if ($thumbsContainer.length === 0) {
				return;
			}

			$thumbsContainer.empty();

			// Add slider/grid mode class
			if (oliVigSettings.thumbnailMode === 'slider') {
				$thumbsContainer.addClass('mode-slider');
			} else {
				$thumbsContainer.removeClass('mode-slider');
			}

			// Add transition effect class
			var $gallery = $('.woocommerce-product-gallery');
			$gallery.removeClass('oli-vig-transition-fade oli-vig-transition-slide');
			if (oliVigSettings.transitionEffect === 'fade') {
				$gallery.addClass('oli-vig-transition-fade');
			} else if (oliVigSettings.transitionEffect === 'slide') {
				$gallery.addClass('oli-vig-transition-slide');
			}

			$.each(images, function(index, image) {
				var $thumb = $('<li/>').append($('<img/>', {
					src: image.thumb,
					alt: image.alt || ''
				}));
				$thumbsContainer.append($thumb);
			});

			// Bind thumbnail clicks
			var self = this;
			$thumbsContainer.find('img').on('click', function() {
				var index = $(this).parent().index();
				self.showImage(index);
			});
		},

		showImage: function(index) {
			if (!this.currentGallery[index]) {
				return;
			}

			var image = this.currentGallery[index];
			var $mainImage = $('.woocommerce-product-gallery__image').first();
			var $img = $mainImage.find('img');
			var $link = $mainImage.find('a');
			var self = this;

			// Apply transition effect
			var transitionEffect = oliVigSettings.transitionEffect || 'none';

			if (transitionEffect === 'fade') {
				// Fade out, change image, fade in
				$img.addClass('oli-vig-fading-out');
				setTimeout(function() {
					$img.attr('src', image.url);
					$img.attr('alt', image.alt || '');

					if (image.srcset) {
						$img.attr('srcset', image.srcset);
					}
					if (image.sizes) {
						$img.attr('sizes', image.sizes);
					}

					if ($link.length) {
						$link.attr('href', image.full);
					}

					$img.removeClass('oli-vig-fading-out').addClass('oli-vig-fading-in');
					setTimeout(function() {
						$img.removeClass('oli-vig-fading-in');
					}, 300);
				}, 300);
			} else if (transitionEffect === 'slide') {
				// Determine slide direction
				var direction = index > self.currentIndex ? 'left' : 'right';
				
				// Slide out current image
				$img.addClass('oli-vig-slide-out-' + direction);
				setTimeout(function() {
					$img.attr('src', image.url);
					$img.attr('alt', image.alt || '');

					if (image.srcset) {
						$img.attr('srcset', image.srcset);
					}
					if (image.sizes) {
						$img.attr('sizes', image.sizes);
					}

					if ($link.length) {
						$link.attr('href', image.full);
					}

					// Slide in new image from opposite direction
					var slideInClass = 'oli-vig-slide-in-' + (direction === 'left' ? 'right' : 'left');
					$img.removeClass('oli-vig-slide-out-left oli-vig-slide-out-right').addClass(slideInClass);
					setTimeout(function() {
						$img.removeClass('oli-vig-slide-in-left oli-vig-slide-in-right');
					}, 400);
				}, 400);
			} else {
				// No transition - instant change
				$img.attr('src', image.url);
				$img.attr('alt', image.alt || '');

				if (image.srcset) {
					$img.attr('srcset', image.srcset);
				}
				if (image.sizes) {
					$img.attr('sizes', image.sizes);
				}

				if ($link.length) {
					$link.attr('href', image.full);
				}
			}

			this.currentIndex = index;

			// Update active thumbnail
			var $thumbs = $('.flex-control-thumbs, .woocommerce-product-gallery__thumbnails, ol.flex-control-nav');
			$thumbs.find('li')
				.removeClass('active')
				.eq(index)
				.addClass('active');

			var $active = $thumbs.find('li').eq(index);
			if ($active.length && $thumbs.hasClass('mode-slider')) {
				var vertical = $thumbs.hasClass('position-left') || $thumbs.hasClass('position-right');
				if (vertical) {
					$thumbs.stop().animate({
						scrollTop: $thumbs.scrollTop() + $active.position().top - 8
					}, 200);
				} else {
					$thumbs.stop().animate({
						scrollLeft: $thumbs.scrollLeft() + $active.position().left - 8
					}, 200);
				}
			}
		},

		resetToOriginalGallery: function() {
			if (this.originalGallery.length === 0) {
				return;
			}

			var $gallery = $('.woocommerce-product-gallery__wrapper');
			var $mainImage = $('.woocommerce-product-gallery__image').first();
			var firstImage = this.originalGallery[0];

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

			this.updateThumbnails(this.originalGallery);
			this.currentGallery = this.originalGallery;
			this.currentIndex = 0;
		},

		initLightbox: function() {
			var self = this;

			// Create lightbox markup if it doesn't exist
			if ($('.oli-vig-lightbox').length === 0) {
				var labels = oliVigSettings.i18n || {};
				var $lightbox = $('<div/>', { 'class': 'oli-vig-lightbox' });
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

			// Open lightbox on image click
			$(document).on('click', '.woocommerce-product-gallery__image a', function(e) {
				e.preventDefault();
				var index = $(this).closest('.woocommerce-product-gallery__image').index();
				self.openLightbox(index);
			});

			// Close lightbox
			$(document).on('click', '.oli-vig-lightbox-close, .oli-vig-lightbox', function(e) {
				if ($(e.target).hasClass('oli-vig-lightbox') || $(e.target).hasClass('oli-vig-lightbox-close')) {
					self.closeLightbox();
				}
			});

			// Previous/Next buttons
			$(document).on('click', '.oli-vig-lightbox-prev', function(e) {
				e.stopPropagation();
				self.lightboxPrev();
			});

			$(document).on('click', '.oli-vig-lightbox-next', function(e) {
				e.stopPropagation();
				self.lightboxNext();
			});

			// Keyboard navigation
			$(document).on('keydown', function(e) {
				if (!self.lightboxOpen) {
					return;
				}

				if (e.key === 'Escape') {
					self.closeLightbox();
				} else if (e.key === 'ArrowLeft') {
					self.lightboxPrev();
				} else if (e.key === 'ArrowRight') {
					self.lightboxNext();
				}
			});
		},

		openLightbox: function(index) {
			var gallery = this.currentGallery.length > 0 ? this.currentGallery : this.originalGallery;
			
			if (!gallery[index]) {
				return;
			}

			this.currentIndex = index;
			this.lightboxOpen = true;

			var image = gallery[index];
			$('.oli-vig-lightbox-image').attr('src', image.full).attr('alt', image.alt || '');
			$('.oli-vig-lightbox').addClass('active');

			this.updateLightboxButtons(gallery);
		},

		closeLightbox: function() {
			$('.oli-vig-lightbox').removeClass('active');
			this.lightboxOpen = false;
		},

		lightboxPrev: function() {
			var gallery = this.currentGallery.length > 0 ? this.currentGallery : this.originalGallery;
			
			if (this.currentIndex > 0) {
				this.currentIndex--;
				var image = gallery[this.currentIndex];
				$('.oli-vig-lightbox-image').attr('src', image.full).attr('alt', image.alt || '');
				this.updateLightboxButtons(gallery);
			}
		},

		lightboxNext: function() {
			var gallery = this.currentGallery.length > 0 ? this.currentGallery : this.originalGallery;
			
			if (this.currentIndex < gallery.length - 1) {
				this.currentIndex++;
				var image = gallery[this.currentIndex];
				$('.oli-vig-lightbox-image').attr('src', image.full).attr('alt', image.alt || '');
				this.updateLightboxButtons(gallery);
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

			var gallery = this.currentGallery.length > 0 ? this.currentGallery : this.originalGallery;

			if (diff > 0 && this.currentIndex < gallery.length - 1) {
				// Swipe left - next image
				this.showImage(this.currentIndex + 1);
			} else if (diff < 0 && this.currentIndex > 0) {
				// Swipe right - previous image
				this.showImage(this.currentIndex - 1);
			}
		}
	};

	$(document).ready(function() {
		OliVIG.init();
	});

})(jQuery);
