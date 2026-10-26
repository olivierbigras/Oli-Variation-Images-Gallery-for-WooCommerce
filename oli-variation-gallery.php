<?php
/**
 * Plugin Name: Oli Variation Gallery for WooCommerce
 * Plugin URI: https://github.com/olivierbigras/Oli-Variation-Images-Gallery-for-WooCommerce
 * Description: Add several gallery images per product variation. The product gallery swaps to the selected variation and falls back to the main gallery.
 * Version: 1.0.6
 * Author: Olivier Bigras
 * Author URI: https://github.com/olivierbigras
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: oli-variation-gallery
 * Domain Path: /languages
 * Requires at least: 6.5
 * Tested up to: 7.1
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * WC requires at least: 9.0
 * WC tested up to: 11.2
 *
 * @package Oli_Variation_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'OLI_VIG_VERSION', '1.0.6' );
define( 'OLI_VIG_PLUGIN_FILE', __FILE__ );
define( 'OLI_VIG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'OLI_VIG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'OLI_VIG_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Show a WooCommerce-missing notice only on the Plugins screen.
 */
function oli_vig_woocommerce_missing_notice() {
	if ( ! function_exists( 'get_current_screen' ) ) {
		return;
	}

	$screen = get_current_screen();
	if ( ! $screen || 'plugins' !== $screen->id ) {
		return;
	}
	?>
	<div class="notice notice-error">
		<p><?php esc_html_e( 'Oli Variation Gallery requires WooCommerce to be installed and active.', 'oli-variation-gallery' ); ?></p>
	</div>
	<?php
}

/**
 * Check whether WooCommerce is active.
 *
 * @return bool
 */
function oli_vig_check_woocommerce() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'oli_vig_woocommerce_missing_notice' );
		return false;
	}

	return true;
}

/**
 * Declare High-Performance Order Storage compatibility.
 */
function oli_vig_declare_hpos_compatibility() {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', OLI_VIG_PLUGIN_FILE, true );
	}
}
add_action( 'before_woocommerce_init', 'oli_vig_declare_hpos_compatibility' );

/**
 * Load plugin classes and start the plugin.
 */
function oli_vig_init() {
	if ( ! oli_vig_check_woocommerce() ) {
		return;
	}

	require_once OLI_VIG_PLUGIN_DIR . 'includes/oli-vig-upgrade.php';
	require_once OLI_VIG_PLUGIN_DIR . 'includes/class-oli-vig-images.php';
	require_once OLI_VIG_PLUGIN_DIR . 'includes/class-oli-vig-main.php';
	require_once OLI_VIG_PLUGIN_DIR . 'includes/class-oli-vig-admin.php';
	require_once OLI_VIG_PLUGIN_DIR . 'includes/class-oli-vig-frontend.php';
	require_once OLI_VIG_PLUGIN_DIR . 'includes/class-oli-vig-settings.php';
	require_once OLI_VIG_PLUGIN_DIR . 'includes/class-oli-vig-importer.php';
	require_once OLI_VIG_PLUGIN_DIR . 'includes/class-oli-vig-ajax.php';
	require_once OLI_VIG_PLUGIN_DIR . 'includes/class-oli-vig-rest.php';

	oli_vig_maybe_upgrade();

	Oli_VIG_Main::instance();
}
add_action( 'plugins_loaded', 'oli_vig_init' );

/**
 * Activation callback.
 */
function oli_vig_activate() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die( esc_html__( 'This plugin requires WooCommerce to be installed and active.', 'oli-variation-gallery' ) );
	}

	require_once OLI_VIG_PLUGIN_DIR . 'includes/oli-vig-upgrade.php';
	require_once OLI_VIG_PLUGIN_DIR . 'includes/class-oli-vig-images.php';
	require_once OLI_VIG_PLUGIN_DIR . 'includes/class-oli-vig-main.php';

	add_option( 'oli_vig_settings', Oli_VIG_Main::get_default_settings() );
	add_option( 'oli_vig_version', OLI_VIG_VERSION );
	oli_vig_migrate_legacy_options();
}
register_activation_hook( __FILE__, 'oli_vig_activate' );
