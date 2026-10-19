<?php
/**
 * Main plugin class
 *
 * @package Oli_Variation_Images_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class
 */
class Oli_VIG_Main {

	/**
	 * Single instance
	 *
	 * @var Oli_VIG_Main
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return Oli_VIG_Main
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
		$this->init_hooks();
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		if ( is_admin() ) {
			Oli_VIG_Admin::instance();
			Oli_VIG_Settings::instance();
			Oli_VIG_Importer::instance();
		} else {
			Oli_VIG_Frontend::instance();
		}

		Oli_VIG_Ajax::instance();

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
	}

	/**
	 * Enqueue frontend assets
	 */
	public function enqueue_frontend_assets() {
		if ( ! is_product() ) {
			return;
		}

		wp_enqueue_style(
			'oli-vig-frontend',
			OLI_VIG_PLUGIN_URL . 'assets/css/frontend.css',
			array(),
			OLI_VIG_VERSION
		);

		wp_enqueue_script(
			'oli-vig-frontend',
			OLI_VIG_PLUGIN_URL . 'assets/js/frontend.js',
			array( 'jquery', 'wc-add-to-cart-variation' ),
			OLI_VIG_VERSION,
			true
		);

		$settings = get_option(
			'oli_vig_settings',
			array(
				'thumbnail_position' => 'bottom',
				'thumbnail_columns'  => 4,
				'enable_zoom'        => 'yes',
				'enable_lightbox'    => 'yes',
				'gallery_width'      => 100,
				'preload_images'     => 'yes',
				'reset_on_clear'     => 'yes',
			)
		);

		wp_localize_script(
			'oli-vig-frontend',
			'oliVigSettings',
			array(
				'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
				'nonce'             => wp_create_nonce( 'oli_vig_nonce' ),
				'thumbnailPosition' => $settings['thumbnail_position'],
				'thumbnailColumns'  => intval( $settings['thumbnail_columns'] ),
				'enableZoom'        => $settings['enable_zoom'] === 'yes',
				'enableLightbox'    => $settings['enable_lightbox'] === 'yes',
				'resetOnClear'      => $settings['reset_on_clear'] === 'yes',
				'preloadImages'     => $settings['preload_images'] === 'yes',
			)
		);
	}

	/**
	 * Get variation gallery images
	 *
	 * @param int $variation_id Variation ID.
	 * @return array
	 */
	public static function get_variation_gallery_images( $variation_id ) {
		$image_ids = get_post_meta( $variation_id, '_oli_vig_gallery_images', true );
		
		if ( empty( $image_ids ) || ! is_array( $image_ids ) ) {
			return array();
		}

		return array_map( 'intval', $image_ids );
	}

	/**
	 * Save variation gallery images
	 *
	 * @param int   $variation_id Variation ID.
	 * @param array $image_ids Image IDs.
	 */
	public static function save_variation_gallery_images( $variation_id, $image_ids ) {
		if ( ! is_array( $image_ids ) ) {
			$image_ids = array();
		}

		$image_ids = array_filter( array_map( 'intval', $image_ids ) );
		update_post_meta( $variation_id, '_oli_vig_gallery_images', $image_ids );
	}
}
