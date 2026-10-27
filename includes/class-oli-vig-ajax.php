<?php
/**
 * AJAX handlers.
 *
 * @package Oli_Variation_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AJAX class.
 */
class Oli_VIG_Ajax {

	/**
	 * Single instance.
	 *
	 * @var Oli_VIG_Ajax|null
	 */
	private static $instance = null;

	/**
	 * Get instance.
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
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'wp_ajax_oli_vig_get_variation_gallery', array( $this, 'get_variation_gallery' ) );
		add_action( 'wp_ajax_nopriv_oli_vig_get_variation_gallery', array( $this, 'get_variation_gallery' ) );
	}

	/**
	 * Get variation gallery via AJAX.
	 */
	public function get_variation_gallery() {
		check_ajax_referer( 'oli_vig_nonce', 'nonce' );

		if ( ! isset( $_POST['variation_id'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid variation ID', 'oli-variation-gallery' ) ) );
		}

		$variation_id = absint( wp_unslash( $_POST['variation_id'] ) );

		if ( $variation_id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'Invalid variation ID', 'oli-variation-gallery' ) ) );
		}

		$variation = wc_get_product( $variation_id );
		if ( ! $variation || ! $variation->is_type( 'variation' ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid variation ID', 'oli-variation-gallery' ) ) );
		}

		$parent_id = $variation->get_parent_id();
		if ( 'publish' !== get_post_status( $parent_id ) && ! current_user_can( 'edit_post', $variation_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid variation ID', 'oli-variation-gallery' ) ) );
		}

		wp_send_json_success(
			array(
				'gallery' => Oli_VIG_Images::get_variation_gallery( $variation ),
			)
		);
	}
}
