/**
 * Admin scripts
 *
 * @package Oli_Variation_Images_Gallery
 */

(function($) {
	'use strict';

	function imageLabel(attachment) {
		if (attachment.title) {
			return attachment.title;
		}
		if (attachment.filename) {
			return attachment.filename;
		}
		return String(attachment.id);
	}

	function removeLabel(title) {
		var template = (oliVigAdmin && oliVigAdmin.removeImage) ? oliVigAdmin.removeImage : 'Remove image %s';
		return template.replace('%s', title);
	}

	function createImageItem(attachment) {
		var thumbSrc = (attachment.sizes && attachment.sizes.thumbnail) ? attachment.sizes.thumbnail.url : attachment.url;
		var $item = $('<li/>', {
			'class': 'oli-vig-gallery-image',
			'data-attachment-id': attachment.id
		});

		$item.append($('<img/>', { src: thumbSrc, alt: '' }));

		var $actions = $('<div/>', { 'class': 'oli-vig-image-actions' });
		$actions.append(
			$('<button/>', {
				type: 'button',
				'class': 'oli-vig-move-image oli-vig-move-left',
				'aria-label': (oliVigAdmin && oliVigAdmin.moveLeft) ? oliVigAdmin.moveLeft : 'Move image left'
			}).append($('<span/>', { 'class': 'dashicons dashicons-arrow-left-alt2', 'aria-hidden': 'true' }))
		);
		$actions.append(
			$('<button/>', {
				type: 'button',
				'class': 'oli-vig-remove-image',
				'aria-label': removeLabel(imageLabel(attachment))
			}).append($('<span/>', { 'class': 'dashicons dashicons-no-alt', 'aria-hidden': 'true' }))
		);
		$actions.append(
			$('<button/>', {
				type: 'button',
				'class': 'oli-vig-move-image oli-vig-move-right',
				'aria-label': (oliVigAdmin && oliVigAdmin.moveRight) ? oliVigAdmin.moveRight : 'Move image right'
			}).append($('<span/>', { 'class': 'dashicons dashicons-arrow-right-alt2', 'aria-hidden': 'true' }))
		);

		$item.append($actions);
		return $item;
	}

	function updateMoveButtons(galleryList) {
		var $items = galleryList.find('.oli-vig-gallery-image');
		$items.each(function(index) {
			$(this).find('.oli-vig-move-left').prop('disabled', index === 0);
			$(this).find('.oli-vig-move-right').prop('disabled', index === $items.length - 1);
		});
	}

	function syncEmptyState(wrapper) {
		var hasImages = wrapper.find('.oli-vig-gallery-image').length > 0;
		wrapper.find('.oli-vig-empty-state').prop('hidden', hasImages);

		var $input = wrapper.find('.oli-vig-gallery-images-input');
		var helpId = $input.attr('data-help-id');
		var emptyId = $input.attr('data-empty-id');
		var describedBy = helpId || '';
		if (!hasImages && emptyId) {
			describedBy = describedBy ? (describedBy + ' ' + emptyId) : emptyId;
		}
		if (describedBy) {
			$input.attr('aria-describedby', describedBy);
		}
	}

	function updateGalleryInput(galleryList, input) {
		var imageIds = [];

		galleryList.find('.oli-vig-gallery-image').each(function() {
			imageIds.push($(this).data('attachment-id'));
		});

		input.val(imageIds.join(','));
		updateMoveButtons(galleryList);
		syncEmptyState(galleryList.closest('.oli-vig-variation-gallery-wrapper'));
	}

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
				ui.item.addClass('oli-vig-gallery-image-dragging');
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
		syncEmptyState(galleryList.closest('.oli-vig-variation-gallery-wrapper'));
	}

	$(document).ready(function() {
		$('.oli-vig-gallery-images').each(function() {
			initGallery($(this));
		});

		$(document).on('woocommerce_variations_loaded', function() {
			$('.oli-vig-gallery-images').each(function() {
				if (!$(this).data('sortable-initialized')) {
					initGallery($(this));
				}
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
				multiple: true
			});

			frame.on('select', function() {
				var selection = frame.state().get('selection');

				selection.each(function(attachment) {
					galleryList.append(createImageItem(attachment.toJSON()));
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

		$(document).on('click', '.oli-vig-move-image', function(e) {
			e.preventDefault();

			var $button = $(this);
			var $item = $button.closest('.oli-vig-gallery-image');
			var $list = $item.closest('.oli-vig-gallery-images');
			var $input = $item.closest('.oli-vig-variation-gallery-wrapper').find('.oli-vig-gallery-images-input');
			var moveLeft = $button.hasClass('oli-vig-move-left');

			if (moveLeft) {
				$item.prev('.oli-vig-gallery-image').before($item);
			} else {
				$item.next('.oli-vig-gallery-image').after($item);
			}

			updateGalleryInput($list, $input);
			$item.find(moveLeft ? '.oli-vig-move-left' : '.oli-vig-move-right').trigger('focus');
		});
	});

})(jQuery);
