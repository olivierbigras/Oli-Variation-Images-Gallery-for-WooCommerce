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

		$settings = self::get_settings();

		wp_localize_script(
			'oli-vig-frontend',
			'oliVigSettings',
			array(
				'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
				'nonce'             => wp_create_nonce( 'oli_vig_nonce' ),
				'thumbnailPosition' => $settings['thumbnail_position'],
				'thumbnailColumns'  => intval( $settings['thumbnail_columns'] ),
				'galleryWidth'      => max( 50, min( 100, intval( $settings['gallery_width'] ) ) ),
				'enableZoom'        => (bool) $settings['enable_zoom'],
				'enableLightbox'    => (bool) $settings['enable_lightbox'],
				'resetOnClear'      => (bool) $settings['reset_on_clear'],
				'preloadImages'     => (bool) $settings['preload_images'],
				'thumbnailMode'     => $settings['thumbnail_mode'],
				'transitionEffect'  => $settings['transition_effect'],
				'i18n'              => array(
					'previous'  => __( 'Previous', 'oli-variation-images-gallery' ),
					'next'      => __( 'Next', 'oli-variation-images-gallery' ),
					'close'     => __( 'Close', 'oli-variation-images-gallery' ),
					'lightbox'  => __( 'Image lightbox', 'oli-variation-images-gallery' ),
					/* translators: 1: current image number, 2: total images */
					'showImage' => __( 'Show image %1$d of %2$d', 'oli-variation-images-gallery' ),
				),
			)
		);
	}

	/**
	 * Default thumbnail column count.
	 *
	 * Uses the WooCommerce `woocommerce_product_thumbnails_columns` filter, else 4.
	 *
	 * @return int
	 */
	public static function get_default_thumbnail_columns() {
		$columns = 4;

		if ( function_exists( 'apply_filters' ) ) {
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Read the core WooCommerce thumbnail columns filter.
			$columns = absint( apply_filters( 'woocommerce_product_thumbnails_columns', 4 ) );
		}

		if ( $columns < 1 ) {
			$columns = 4;
		}

		return max( 1, min( 8, $columns ) );
	}

	/**
	 * Default plugin settings.
	 *
	 * Zoom and lightbox follow the active theme's `wc-product-gallery-*` support.
	 *
	 * @return array
	 */
	public static function get_default_settings() {
		$enable_zoom     = false;
		$enable_lightbox = false;

		if ( function_exists( 'current_theme_supports' ) ) {
			$enable_zoom     = (bool) current_theme_supports( 'wc-product-gallery-zoom' );
			$enable_lightbox = (bool) current_theme_supports( 'wc-product-gallery-lightbox' );
		}

		return array(
			'thumbnail_position' => 'bottom',
			'thumbnail_columns'  => self::get_default_thumbnail_columns(),
			'enable_zoom'        => $enable_zoom,
			'enable_lightbox'    => $enable_lightbox,
			'gallery_width'      => 100,
			'preload_images'     => true,
			'reset_on_clear'     => true,
			'thumbnail_mode'     => 'grid',
			'transition_effect'  => 'none',
		);
	}

	/**
	 * Normalize stored settings (legacy yes/no becomes a real boolean).
	 *
	 * @param array $settings Raw merged settings.
	 * @return array
	 */
	public static function normalize_settings( $settings ) {
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		foreach ( oli_vig_boolean_setting_keys() as $key ) {
			if ( array_key_exists( $key, $settings ) ) {
				$settings[ $key ] = oli_vig_to_bool( $settings[ $key ] );
			}
		}

		if ( isset( $settings['thumbnail_columns'] ) ) {
			$settings['thumbnail_columns'] = max( 1, min( 8, absint( $settings['thumbnail_columns'] ) ) );
		}

		if ( isset( $settings['gallery_width'] ) ) {
			$settings['gallery_width'] = max( 50, min( 100, absint( $settings['gallery_width'] ) ) );
		}

		if ( isset( $settings['thumbnail_position'] ) && ! in_array( $settings['thumbnail_position'], array( 'bottom', 'left', 'right' ), true ) ) {
			$settings['thumbnail_position'] = 'bottom';
		}

		if ( isset( $settings['thumbnail_mode'] ) && ! in_array( $settings['thumbnail_mode'], array( 'grid', 'slider' ), true ) ) {
			$settings['thumbnail_mode'] = 'grid';
		}

		if ( isset( $settings['transition_effect'] ) && ! in_array( $settings['transition_effect'], array( 'none', 'fade', 'slide' ), true ) ) {
			$settings['transition_effect'] = 'none';
		}

		return $settings;
	}

	/**
	 * Get merged plugin settings.
	 *
	 * @return array
	 */
	public static function get_settings() {
		$saved = get_option( 'oli_vig_settings', array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		return self::normalize_settings( wp_parse_args( $saved, self::get_default_settings() ) );
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
