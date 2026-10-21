/**
 * Settings screen helpers
 *
 * @package Oli_Variation_Images_Gallery
 */

(function($) {
	'use strict';

	function syncConditionalFields() {
		var $mode = $('select[name="oli_vig_settings[thumbnail_mode]"]');
		if (!$mode.length) {
			return;
		}

		var mode = $mode.val();
		$('.oli-vig-show-if-grid').closest('tr').toggle(mode === 'grid');
		$('.oli-vig-show-if-carousel').closest('tr').toggle(mode === 'slider');
	}

	$(function() {
		syncConditionalFields();
		$(document).on('change', 'select[name="oli_vig_settings[thumbnail_mode]"]', syncConditionalFields);
	});
})(jQuery);
