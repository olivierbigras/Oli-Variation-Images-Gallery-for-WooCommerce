<?php
/**
 * Frontend gallery integration.
 *
 * @package Oli_Variation_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Frontend class.
 */
class Oli_VIG_Frontend {

	/**
	 * Single instance.
	 *
	 * @var Oli_VIG_Frontend|null
	 */
	private static $instance = null;

	/**
	 * Get instance.
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
	 * Constructor.
	 */
	private function __construct() {
		add_filter( 'woocommerce_available_variation', array( $this, 'add_variation_gallery_data' ), 10, 3 );
		add_filter( 'woocommerce_single_product_image_gallery_classes', array( $this, 'gallery_classes' ) );
		add_filter( 'woocommerce_product_thumbnails_columns', array( $this, 'thumbnail_columns' ) );
		add_filter( 'woocommerce_single_product_zoom_enabled', array( $this, 'filter_zoom_enabled' ) );
		add_filter( 'woocommerce_single_product_photoswipe_enabled', array( $this, 'filter_lightbox_enabled' ) );
	}

	/**
	 * Add layout classes to the default gallery wrapper.
	 *
	 * @param array $classes Gallery classes.
	 * @return array
	 */
	public function gallery_classes( $classes ) {
		$settings = Oli_VIG_Main::get_settings();
		$columns  = absint( $settings['thumbnail_columns'] );

		$classes[] = 'oli-vig-gallery';
		$classes[] = 'oli-vig-thumbs-' . sanitize_html_class( $settings['thumbnail_position'] );

		if ( $columns >= 1 ) {
			$classes[] = 'oli-vig-columns-' . max( 1, min( 8, $columns ) );
		}

		return $classes;
	}

	/**
	 * Thumbnail column count for the default gallery.
	 *
	 * @param int $columns Current columns.
	 * @return int
	 */
	public function thumbnail_columns( $columns ) {
		$settings = Oli_VIG_Main::get_settings();
		$set      = absint( $settings['thumbnail_columns'] );

		if ( $set < 1 ) {
			return $columns;
		}

		return max( 1, min( 8, $set ) );
	}

	/**
	 * Map the zoom setting onto the default gallery.
	 *
	 * @param bool $enabled Whether zoom is enabled.
	 * @return bool
	 */
	public function filter_zoom_enabled( $enabled ) {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return $enabled;
		}

		$settings = Oli_VIG_Main::get_settings();
		return ( 'yes' === $settings['enable_zoom'] );
	}

	/**
	 * Map the lightbox setting onto the default Photoswipe gallery.
	 *
	 * @param bool $enabled Whether Photoswipe is enabled.
	 * @return bool
	 */
	public function filter_lightbox_enabled( $enabled ) {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return $enabled;
		}

		$settings = Oli_VIG_Main::get_settings();
		return ( 'yes' === $settings['enable_lightbox'] );
	}

	/**
	 * Add variation gallery data to available variations.
	 *
	 * @param array                $data      Variation data.
	 * @param WC_Product           $product   Product object.
	 * @param WC_Product_Variation $variation Variation object.
	 * @return array
	 */
	public function add_variation_gallery_data( $data, $product, $variation ) {
		unset( $product );

		$data['oli_vig_gallery'] = Oli_VIG_Images::get_variation_gallery( $variation );

		return $data;
	}
}
