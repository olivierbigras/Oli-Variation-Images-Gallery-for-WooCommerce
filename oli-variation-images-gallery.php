<?php
/**
 * Plugin Name: Oli Variation Images Gallery for WooCommerce
 * Plugin URI: https://github.com/bigrat95/oli-variation-images-gallery
 * Description: Add unlimited gallery images per product variation with thumbnail gallery, zoom, and lightbox support. Includes one-click importer from RadiusTheme Variation Images Gallery.
 * Version: 0.1.0
 * Author: Olivier Bigras
 * Author URI: https://olivierbigras.com/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: oli-variation-images-gallery
 * Domain Path: /languages
 * Requires at least: 6.5
 * Tested up to: 7.1
 * Requires PHP: 7.4
 * WC requires at least: 9.0
 * WC tested up to: 11.0
 *
 * @package Oli_Variation_Images_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'OLI_VIG_VERSION', '0.1.0' );
define( 'OLI_VIG_PLUGIN_FILE', __FILE__ );
define( 'OLI_VIG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'OLI_VIG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'OLI_VIG_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Check if WooCommerce is active
 */
function oli_vig_check_woocommerce() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action(
			'admin_notices',
			function() {
				?>
				<div class="notice notice-error">
					<p><?php esc_html_e( 'Oli Variation Images Gallery requires WooCommerce to be installed and active.', 'oli-variation-images-gallery' ); ?></p>
				</div>
				<?php
			}
		);
		return false;
	}
	return true;
}

/**
 * Initialize the plugin
 */
function oli_vig_init() {
	if ( ! oli_vig_check_woocommerce() ) {
		return;
	}

	load_plugin_textdomain( 'oli-variation-images-gallery', false, dirname( OLI_VIG_PLUGIN_BASENAME ) . '/languages' );

	require_once OLI_VIG_PLUGIN_DIR . 'includes/class-oli-vig-main.php';
	require_once OLI_VIG_PLUGIN_DIR . 'includes/class-oli-vig-admin.php';
	require_once OLI_VIG_PLUGIN_DIR . 'includes/class-oli-vig-frontend.php';
	require_once OLI_VIG_PLUGIN_DIR . 'includes/class-oli-vig-settings.php';
	require_once OLI_VIG_PLUGIN_DIR . 'includes/class-oli-vig-importer.php';
	require_once OLI_VIG_PLUGIN_DIR . 'includes/class-oli-vig-ajax.php';

	Oli_VIG_Main::instance();
}
add_action( 'plugins_loaded', 'oli_vig_init' );

/**
 * Activation hook
 */
function oli_vig_activate() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die( esc_html__( 'This plugin requires WooCommerce to be installed and active.', 'oli-variation-images-gallery' ) );
	}

	add_option(
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
}
register_activation_hook( __FILE__, 'oli_vig_activate' );

/**
 * Declare HPOS compatibility
 */
add_action(
	'before_woocommerce_init',
	function() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);
