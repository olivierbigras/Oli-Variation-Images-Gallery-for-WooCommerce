<?php
/**
 * Main plugin class.
 *
 * @package Oli_Variation_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin orchestrator.
 */
class Oli_VIG_Main {

	/**
	 * Single instance.
	 *
	 * @var Oli_VIG_Main|null
	 */
	private static $instance = null;

	/**
	 * Get instance.
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
	 * Constructor.
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		if ( is_admin() ) {
			Oli_VIG_Admin::instance();
			Oli_VIG_Settings::instance();
		} else {
			Oli_VIG_Frontend::instance();
		}

		if ( is_admin() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			Oli_VIG_Importer::instance();
		}

		Oli_VIG_Ajax::instance();
		Oli_VIG_Rest::instance();

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
	}

	/**
	 * Enqueue frontend assets on product pages.
	 */
	public function enqueue_frontend_assets() {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
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
			array( 'jquery', 'wc-add-to-cart-variation', 'wc-single-product' ),
			OLI_VIG_VERSION,
			true
		);

		$settings = self::get_settings();

		wp_localize_script(
			'oli-vig-frontend',
			'oliVigSettings',
			array(
				'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
				'nonce'             => wp_create_nonce( 'oli_vig_nonce' ),
				'thumbnailPosition' => $settings['thumbnail_position'],
				'thumbnailColumns'  => absint( $settings['thumbnail_columns'] ),
				'galleryWidth'      => max( 50, min( 100, absint( $settings['gallery_width'] ) ) ),
				'enableZoom'        => ( 'yes' === $settings['enable_zoom'] ),
				'enableLightbox'    => ( 'yes' === $settings['enable_lightbox'] ),
				'resetOnClear'      => ( 'yes' === $settings['reset_on_clear'] ),
				'preloadImages'     => ( 'yes' === $settings['preload_images'] ),
			)
		);
	}

	/**
	 * Default plugin settings.
	 *
	 * @return array<string,mixed>
	 */
	public static function get_default_settings() {
		return array(
			'thumbnail_position' => 'bottom',
			'thumbnail_columns'  => 0,
			'enable_zoom'        => 'yes',
			'enable_lightbox'    => 'yes',
			'gallery_width'      => 100,
			'preload_images'     => 'yes',
			'reset_on_clear'     => 'yes',
		);
	}

	/**
	 * Get merged plugin settings.
	 *
	 * @return array<string,mixed>
	 */
	public static function get_settings() {
		$saved = get_option( 'oli_vig_settings', array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}

		return wp_parse_args( $saved, self::get_default_settings() );
	}

	/**
	 * Get variation gallery images.
	 *
	 * @param int $variation_id Variation ID.
	 * @return int[]
	 */
	public static function get_variation_gallery_images( $variation_id ) {
		return Oli_VIG_Images::get_ids( $variation_id );
	}

	/**
	 * Save variation gallery images.
	 *
	 * @param int   $variation_id Variation ID.
	 * @param mixed $image_ids    Image IDs.
	 */
	public static function save_variation_gallery_images( $variation_id, $image_ids ) {
		Oli_VIG_Images::save_ids( $variation_id, $image_ids );
	}
}
