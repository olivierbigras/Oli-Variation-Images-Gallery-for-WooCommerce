<?php
/**
 * AJAX handlers
 *
 * @package Oli_Variation_Images_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AJAX class
 */
class Oli_VIG_Ajax {

	/**
	 * Single instance
	 *
	 * @var Oli_VIG_Ajax
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return Oli_VIG_Ajax
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		add_action( 'wp_ajax_oli_vig_get_variation_gallery', array( $this, 'get_variation_gallery' ) );
		add_action( 'wp_ajax_nopriv_oli_vig_get_variation_gallery', array( $this, 'get_variation_gallery' ) );
	}

	/**
	 * Get variation gallery via AJAX
	 */
	public function get_variation_gallery() {
		check_ajax_referer( 'oli_vig_nonce', 'nonce' );

		if ( ! isset( $_POST['variation_id'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid variation ID', 'oli-variation-images-gallery' ) ) );
		}

		$variation_id = intval( $_POST['variation_id'] );

		if ( $variation_id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'Invalid variation ID', 'oli-variation-images-gallery' ) ) );
		}

		$image_ids      = Oli_VIG_Main::get_variation_gallery_images( $variation_id );
		$gallery_images = array();

		if ( ! empty( $image_ids ) ) {
			foreach ( $image_ids as $image_id ) {
				$image_url = wp_get_attachment_image_url( $image_id, 'woocommerce_single' );
				$thumb_url = wp_get_attachment_image_url( $image_id, 'woocommerce_thumbnail' );
				$full_url  = wp_get_attachment_image_url( $image_id, 'full' );

				if ( $image_url ) {
					$gallery_images[] = array(
						'id'     => $image_id,
						'url'    => $image_url,
						'thumb'  => $thumb_url ? $thumb_url : $image_url,
						'full'   => $full_url ? $full_url : $image_url,
						'alt'    => get_post_meta( $image_id, '_wp_attachment_image_alt', true ),
						'title'  => get_the_title( $image_id ),
						'srcset' => wp_get_attachment_image_srcset( $image_id, 'woocommerce_single' ),
						'sizes'  => wp_get_attachment_image_sizes( $image_id, 'woocommerce_single' ),
					);
				}
			}
		}

		wp_send_json_success( array( 'gallery' => $gallery_images ) );
	}
}
