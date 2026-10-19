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
			this.initTouchSupport();
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

			$.each(images, function(index, image) {
				var $thumb = $('<li><img src="' + image.thumb + '" alt="' + (image.alt || '') + '"></li>');
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

			this.currentIndex = index;

			// Update active thumbnail
			$('.flex-control-thumbs li, .woocommerce-product-gallery__thumbnails li')
				.removeClass('active')
				.eq(index)
				.addClass('active');
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

			// Create lightbox HTML if it doesn't exist
			if ($('.oli-vig-lightbox').length === 0) {
				var lightboxHtml = '<div class="oli-vig-lightbox">' +
					'<div class="oli-vig-lightbox-content">' +
					'<button class="oli-vig-lightbox-close">&times;</button>' +
					'<button class="oli-vig-lightbox-prev">&lsaquo;</button>' +
					'<button class="oli-vig-lightbox-next">&rsaquo;</button>' +
					'<img class="oli-vig-lightbox-image" src="" alt="">' +
					'</div>' +
					'</div>';
				$('body').append(lightboxHtml);
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
