<?php
/**
 * Frontend functionality
 *
 * @package Oli_Variation_Images_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Frontend class
 */
class Oli_VIG_Frontend {

	/**
	 * Single instance
	 *
	 * @var Oli_VIG_Frontend
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return Oli_VIG_Frontend
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
		add_filter( 'woocommerce_available_variation', array( $this, 'add_variation_gallery_data' ), 10, 3 );
		add_filter( 'woocommerce_single_product_image_gallery_classes', array( $this, 'gallery_classes' ) );
	}

	/**
	 * Add layout classes to the WooCommerce gallery wrapper.
	 *
	 * @param array $classes Gallery classes.
	 * @return array
	 */
	public function gallery_classes( $classes ) {
		$settings = Oli_VIG_Main::get_settings();

		$columns = max( 1, min( 8, intval( $settings['thumbnail_columns'] ) ) );

		$classes[] = 'oli-vig-gallery';
		$classes[] = 'oli-vig-thumbs-' . sanitize_html_class( $settings['thumbnail_position'] );
		$classes[] = 'oli-vig-thumbs-mode-' . sanitize_html_class( $settings['thumbnail_mode'] );
		$classes[] = 'oli-vig-transition-' . sanitize_html_class( $settings['transition_effect'] );
		$classes[] = 'oli-vig-columns-' . $columns;

		if ( 'yes' === $settings['enable_zoom'] ) {
			$classes[] = 'oli-vig-zoom-enabled';
		}

		return $classes;
	}

	/**
	 * Add variation gallery data to available variations
	 *
	 * @param array                $data Variation data.
	 * @param WC_Product           $product Product object.
	 * @param WC_Product_Variation $variation Variation object.
	 * @return array
	 */
	public function add_variation_gallery_data( $data, $product, $variation ) {
		$variation_id = $variation->get_id();
		$image_ids    = Oli_VIG_Main::get_variation_gallery_images( $variation_id );

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
						'alt'    => sanitize_text_field( (string) get_post_meta( $image_id, '_wp_attachment_image_alt', true ) ),
						'title'  => sanitize_text_field( get_the_title( $image_id ) ),
						'srcset' => wp_get_attachment_image_srcset( $image_id, 'woocommerce_single' ),
						'sizes'  => wp_get_attachment_image_sizes( $image_id, 'woocommerce_single' ),
					);
				}
			}
		}

		$data['oli_vig_gallery'] = $gallery_images;

		return $data;
	}
}
