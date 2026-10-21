<?php
/**
 * Uninstall cleanup.
 *
 * @package Oli_Variation_Images_Gallery
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'oli_vig_settings' );
delete_option( 'oli_vig_version' );

// Variation gallery image IDs (_oli_vig_gallery_images) are product content and are left in place.
