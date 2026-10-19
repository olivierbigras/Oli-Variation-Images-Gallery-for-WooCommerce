/**
 * Admin scripts
 *
 * @package Oli_Variation_Images_Gallery
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		// Initialize gallery for existing variations
		$('.oli-vig-gallery-images').each(function() {
			initGallery($(this));
		});

		// Initialize gallery for newly added variations
		$(document).on('woocommerce_variations_loaded', function() {
			$('.oli-vig-gallery-images').each(function() {
				if (!$(this).data('sortable-initialized')) {
					initGallery($(this));
				}
			});
		});

		// Add images button
		$(document).on('click', '.oli-vig-add-images', function(e) {
			e.preventDefault();

			var button = $(this);
			var galleryList = button.closest('.oli-vig-gallery-container').find('.oli-vig-gallery-images');
			var input = button.closest('.oli-vig-variation-gallery-wrapper').find('.oli-vig-gallery-images-input');

			var frame = wp.media({
				title: oliVigAdmin.mediaTitle,
				button: {
					text: oliVigAdmin.mediaButton
				},
				multiple: true
			});

			frame.on('select', function() {
				var selection = frame.state().get('selection');
				
				selection.each(function(attachment) {
					attachment = attachment.toJSON();
					
					var imageHtml = '<li class="oli-vig-gallery-image" data-attachment-id="' + attachment.id + '">' +
						'<img src="' + (attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url) + '" alt="" />' +
						'<button type="button" class="oli-vig-remove-image">' +
						'<span class="dashicons dashicons-no-alt"></span>' +
						'</button>' +
						'</li>';
					
					galleryList.append(imageHtml);
				});

				updateGalleryInput(galleryList, input);
			});

			frame.open();
		});

		// Remove image button
		$(document).on('click', '.oli-vig-remove-image', function(e) {
			e.preventDefault();
			
			var imageItem = $(this).closest('.oli-vig-gallery-image');
			var galleryList = imageItem.closest('.oli-vig-gallery-images');
			var input = imageItem.closest('.oli-vig-variation-gallery-wrapper').find('.oli-vig-gallery-images-input');
			
			imageItem.remove();
			updateGalleryInput(galleryList, input);
		});

		/**
		 * Initialize sortable gallery
		 */
		function initGallery(galleryList) {
			if (galleryList.data('sortable-initialized')) {
				return;
			}

			galleryList.sortable({
				items: '.oli-vig-gallery-image',
				cursor: 'move',
				scrollSensitivity: 40,
				forcePlaceholderSize: true,
				forceHelperSize: false,
				helper: 'clone',
				opacity: 0.65,
				placeholder: 'oli-vig-gallery-image ui-sortable-placeholder',
				start: function(event, ui) {
					ui.item.css('background-color', '#f6f6f6');
				},
				stop: function(event, ui) {
					ui.item.removeAttr('style');
				},
				update: function() {
					var input = galleryList.closest('.oli-vig-variation-gallery-wrapper').find('.oli-vig-gallery-images-input');
					updateGalleryInput(galleryList, input);
				}
			});

			galleryList.data('sortable-initialized', true);
		}

		/**
		 * Update gallery input with current image IDs
		 */
		function updateGalleryInput(galleryList, input) {
			var imageIds = [];
			
			galleryList.find('.oli-vig-gallery-image').each(function() {
				imageIds.push($(this).data('attachment-id'));
			});
			
			input.val(imageIds.join(','));
		}
	});

})(jQuery);
