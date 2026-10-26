/**
 * Admin scripts.
 *
 * @package Oli_Variation_Gallery
 */

(function($) {
	'use strict';

	$(function() {
		$('.oli-vig-gallery-images').each(function() {
			initGallery($(this));
		});

		$(document).on('woocommerce_variations_loaded woocommerce_variations_added', function() {
			$('.oli-vig-gallery-images').each(function() {
				if (!$(this).data('sortable-initialized')) {
					initGallery($(this));
				}
				updateMoveButtons($(this));
			});
		});

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
				library: {
					type: 'image'
				},
				multiple: true
			});

			frame.on('select', function() {
				var selection = frame.state().get('selection');

				selection.each(function(attachment) {
					attachment = attachment.toJSON();
					if (!attachment || !attachment.id) {
						return;
					}
					if (galleryList.find('.oli-vig-gallery-image[data-attachment-id="' + attachment.id + '"]').length) {
						return;
					}
					var thumbSrc = (attachment.sizes && attachment.sizes.thumbnail) ? attachment.sizes.thumbnail.url : attachment.url;
					var filename = attachment.filename || attachment.name || String(attachment.id);
					galleryList.append(buildGalleryItem(attachment.id, thumbSrc, filename));
				});

				updateGalleryInput(galleryList, input);
			});

			frame.open();
		});

		$(document).on('click', '.oli-vig-remove-image', function(e) {
			e.preventDefault();

			var imageItem = $(this).closest('.oli-vig-gallery-image');
			var galleryList = imageItem.closest('.oli-vig-gallery-images');
			var input = imageItem.closest('.oli-vig-variation-gallery-wrapper').find('.oli-vig-gallery-images-input');

			imageItem.remove();
			updateGalleryInput(galleryList, input);
		});

		$(document).on('click', '.oli-vig-move-left, .oli-vig-move-right', function(e) {
			e.preventDefault();

			var button = $(this);
			if (button.is(':disabled')) {
				return;
			}

			var imageItem = button.closest('.oli-vig-gallery-image');
			var galleryList = imageItem.closest('.oli-vig-gallery-images');
			var input = imageItem.closest('.oli-vig-variation-gallery-wrapper').find('.oli-vig-gallery-images-input');

			if (button.hasClass('oli-vig-move-left')) {
				imageItem.prev('.oli-vig-gallery-image').before(imageItem);
			} else {
				imageItem.next('.oli-vig-gallery-image').after(imageItem);
			}

			updateGalleryInput(galleryList, input);
			button.trigger('focus');
		});

		function buildGalleryItem(id, thumbSrc, filename) {
			var removeLabel = (oliVigAdmin.removeImage || 'Remove image %s').replace('%s', filename);
			var $item = $('<li/>', {
				'class': 'oli-vig-gallery-image',
				'data-attachment-id': id,
				'data-filename': filename
			});
			$item.append($('<img/>', { src: thumbSrc, alt: '' }));

			var $actions = $('<div/>', { 'class': 'oli-vig-image-actions' });
			$actions.append(
				$('<button/>', {
					type: 'button',
					'class': 'oli-vig-move-image oli-vig-move-left',
					'aria-label': oliVigAdmin.moveLeft || 'Move image left'
				}).append($('<span/>', { 'class': 'dashicons dashicons-arrow-left-alt2' }))
			);
			$actions.append(
				$('<button/>', {
					type: 'button',
					'class': 'oli-vig-move-image oli-vig-move-right',
					'aria-label': oliVigAdmin.moveRight || 'Move image right'
				}).append($('<span/>', { 'class': 'dashicons dashicons-arrow-right-alt2' }))
			);
			$actions.append(
				$('<button/>', {
					type: 'button',
					'class': 'oli-vig-remove-image',
					'aria-label': removeLabel
				}).append($('<span/>', { 'class': 'dashicons dashicons-no-alt' }))
			);
			$item.append($actions);

			return $item;
		}

		function updateMoveButtons(galleryList) {
			var items = galleryList.find('.oli-vig-gallery-image');
			items.each(function(index) {
				var $item = $(this);
				$item.find('.oli-vig-move-left').prop('disabled', 0 === index);
				$item.find('.oli-vig-move-right').prop('disabled', index === items.length - 1);
			});
		}

		function initGallery(galleryList) {
			if (galleryList.data('sortable-initialized')) {
				return;
			}

			galleryList.sortable({
				items: 'li.oli-vig-gallery-image',
				cursor: 'move',
				scrollSensitivity: 40,
				forcePlaceholderSize: true,
				forceHelperSize: false,
				helper: 'clone',
				opacity: 0.65,
				tolerance: 'pointer',
				placeholder: 'oli-vig-gallery-image ui-sortable-placeholder',
				start: function(event, ui) {
					ui.item.addClass('oli-vig-gallery-image-dragging');
					ui.placeholder.width(ui.item.width());
					ui.placeholder.height(ui.item.height());
				},
				stop: function(event, ui) {
					ui.item.removeClass('oli-vig-gallery-image-dragging');
				},
				update: function() {
					var input = galleryList.closest('.oli-vig-variation-gallery-wrapper').find('.oli-vig-gallery-images-input');
					updateGalleryInput(galleryList, input);
				}
			});

			galleryList.data('sortable-initialized', true);
			updateMoveButtons(galleryList);
		}

		function markVariationNeedsUpdate(input) {
			var $variation = input.closest('.woocommerce_variation');
			if ($variation.length) {
				$variation.addClass('variation-needs-update');
			}
			$('button.cancel-variation-changes, button.save-variation-changes').prop('disabled', false);
			$('#variable_product_options').trigger('woocommerce_variations_input_changed');
		}

		function updateGalleryInput(galleryList, input) {
			var imageIds = [];
			var seen = {};

			galleryList.find('.oli-vig-gallery-image').each(function() {
				var id = parseInt($(this).attr('data-attachment-id'), 10);
				if (id > 0 && !seen[id]) {
					seen[id] = true;
					imageIds.push(id);
				}
			});

			input.val(imageIds.join(',')).trigger('change');
			markVariationNeedsUpdate(input);
			updateMoveButtons(galleryList);
		}
	});

})(jQuery);
