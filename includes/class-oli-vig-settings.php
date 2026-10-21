<?php
/**
 * WooCommerce product settings section.
 *
 * @package Oli_Variation_Images_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings class
 */
class Oli_VIG_Settings {

	/**
	 * Products tab section slug.
	 *
	 * @var string
	 */
	const SECTION_ID = 'variation-galleries';

	/**
	 * Legacy standalone settings page slug.
	 *
	 * @var string
	 */
	const LEGACY_PAGE_SLUG = 'oli-variation-gallery-settings';

	/**
	 * Single instance
	 *
	 * @var Oli_VIG_Settings
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return Oli_VIG_Settings
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Settings screen URL (WooCommerce → Settings → Products → Variation galleries).
	 *
	 * @return string
	 */
	public static function get_settings_url() {
		return admin_url( 'admin.php?page=wc-settings&tab=products&section=' . self::SECTION_ID );
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		add_filter( 'woocommerce_get_sections_products', array( $this, 'add_products_section' ) );
		add_filter( 'woocommerce_get_settings_products', array( $this, 'get_products_settings' ), 10, 2 );
		add_filter( 'woocommerce_admin_settings_sanitize_option_oli_vig_settings', array( $this, 'sanitize_settings_option' ), 10, 3 );
		add_action( 'woocommerce_admin_field_oli_vig_checkbox', array( $this, 'render_checkbox_field' ) );
		add_action( 'woocommerce_admin_field_oli_vig_details_start', array( $this, 'render_details_start' ) );
		add_action( 'woocommerce_admin_field_oli_vig_details_end', array( $this, 'render_details_end' ) );
		add_action( 'admin_menu', array( $this, 'add_legacy_settings_page' ), 99 );
		add_action( 'admin_init', array( $this, 'redirect_legacy_pages' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_settings_assets' ) );
		add_filter( 'plugin_action_links_' . OLI_VIG_PLUGIN_BASENAME, array( $this, 'plugin_action_links' ) );
	}

	/**
	 * Add the Variation galleries section on the Products settings tab.
	 *
	 * @param array $sections Product setting sections.
	 * @return array
	 */
	public function add_products_section( $sections ) {
		$sections[ self::SECTION_ID ] = __( 'Variation galleries', 'oli-variation-images-gallery' );
		return $sections;
	}

	/**
	 * Settings fields for the Variation galleries section.
	 *
	 * Field IDs use `oli_vig_settings[key]` so WooCommerce writes the existing
	 * `oli_vig_settings` array option. Keys do not change.
	 *
	 * @param array  $settings        Settings for other sections.
	 * @param string $current_section Current products section.
	 * @return array
	 */
	public function get_products_settings( $settings, $current_section ) {
		if ( self::SECTION_ID !== $current_section ) {
			return $settings;
		}

		$defaults = Oli_VIG_Main::get_default_settings();

		return array(
			array(
				'title' => __( 'Variation galleries', 'oli-variation-images-gallery' ),
				'type'  => 'title',
				'id'    => 'oli_vig_settings_title',
				'desc'  => __( 'Configure how variation galleries are displayed on product pages.', 'oli-variation-images-gallery' ),
			),
			array(
				'title'   => __( 'Thumbnail position', 'oli-variation-images-gallery' ),
				'id'      => 'oli_vig_settings[thumbnail_position]',
				'type'    => 'select',
				'class'   => 'wc-enhanced-select',
				'default' => $defaults['thumbnail_position'],
				'options' => array(
					'bottom' => __( 'Bottom', 'oli-variation-images-gallery' ),
					'left'   => __( 'Left', 'oli-variation-images-gallery' ),
					'right'  => __( 'Right', 'oli-variation-images-gallery' ),
				),
			),
			array(
				'title'   => __( 'Thumbnail mode', 'oli-variation-images-gallery' ),
				'id'      => 'oli_vig_settings[thumbnail_mode]',
				'type'    => 'select',
				'class'   => 'wc-enhanced-select',
				'default' => $defaults['thumbnail_mode'],
				'options' => array(
					'grid'   => __( 'Grid', 'oli-variation-images-gallery' ),
					'slider' => __( 'Carousel', 'oli-variation-images-gallery' ),
				),
			),
			array(
				'title'             => __( 'Thumbnail columns', 'oli-variation-images-gallery' ),
				'id'                => 'oli_vig_settings[thumbnail_columns]',
				'type'              => 'number',
				'class'             => 'oli-vig-show-if-grid',
				'default'           => $defaults['thumbnail_columns'],
				'css'               => 'width:80px;',
				'custom_attributes' => array(
					'min'  => 1,
					'max'  => 8,
					'step' => 1,
				),
				'desc'              => __( 'Number of thumbnail columns (1-8).', 'oli-variation-images-gallery' ),
				'desc_tip'          => true,
			),
			array(
				'title'   => __( 'Enable zoom', 'oli-variation-images-gallery' ),
				'id'      => 'oli_vig_settings[enable_zoom]',
				'type'    => 'oli_vig_checkbox',
				'default' => $defaults['enable_zoom'],
				'desc'    => __( 'Enable image zoom on hover.', 'oli-variation-images-gallery' ),
			),
			array(
				'title'   => __( 'Enable lightbox', 'oli-variation-images-gallery' ),
				'id'      => 'oli_vig_settings[enable_lightbox]',
				'type'    => 'oli_vig_checkbox',
				'default' => $defaults['enable_lightbox'],
				'desc'    => __( 'Enable lightbox when clicking images.', 'oli-variation-images-gallery' ),
			),
			array(
				'type' => 'sectionend',
				'id'   => 'oli_vig_settings_title',
			),
			array(
				'title' => __( 'Advanced', 'oli-variation-images-gallery' ),
				'type'  => 'oli_vig_details_start',
			),
			array(
				'title'   => __( 'Reset gallery on clear', 'oli-variation-images-gallery' ),
				'id'      => 'oli_vig_settings[reset_on_clear]',
				'type'    => 'oli_vig_checkbox',
				'default' => $defaults['reset_on_clear'],
				'desc'    => __( 'Reset to the product gallery when the variation is cleared.', 'oli-variation-images-gallery' ),
			),
			array(
				'title'   => __( 'Preload images', 'oli-variation-images-gallery' ),
				'id'      => 'oli_vig_settings[preload_images]',
				'type'    => 'oli_vig_checkbox',
				'default' => $defaults['preload_images'],
				'desc'    => __( 'Preload variation images for faster switching. This can increase the page weight on products with many variation images.', 'oli-variation-images-gallery' ),
			),
			array(
				'title'   => __( 'Transition effect', 'oli-variation-images-gallery' ),
				'id'      => 'oli_vig_settings[transition_effect]',
				'type'    => 'select',
				'class'   => 'wc-enhanced-select oli-vig-show-if-carousel',
				'default' => $defaults['transition_effect'],
				'options' => array(
					'none'  => __( 'None', 'oli-variation-images-gallery' ),
					'fade'  => __( 'Fade', 'oli-variation-images-gallery' ),
					'slide' => __( 'Slide', 'oli-variation-images-gallery' ),
				),
				'desc'    => __( 'Animation when switching images in carousel mode. Honors prefers-reduced-motion.', 'oli-variation-images-gallery' ),
			),
			array(
				'title'             => __( 'Gallery width', 'oli-variation-images-gallery' ),
				'id'                => 'oli_vig_settings[gallery_width]',
				'type'              => 'number',
				'default'           => $defaults['gallery_width'],
				'css'               => 'width:80px;',
				'custom_attributes' => array(
					'min'  => 50,
					'max'  => 100,
					'step' => 1,
				),
				'desc'              => __( 'Leave 100% to follow the theme.', 'oli-variation-images-gallery' ),
			),
			array(
				'type' => 'oli_vig_details_end',
			),
		);
	}

	/**
	 * Sanitize one `oli_vig_settings[key]` value written by WooCommerce.
	 *
	 * @param mixed $value     Value after WooCommerce's default sanitizing.
	 * @param array $option    Field definition.
	 * @param mixed $raw_value Raw posted value (null when a checkbox is unchecked).
	 * @return mixed
	 */
	public function sanitize_settings_option( $value, $option, $raw_value ) {
		$key = $this->setting_key_from_option( $option );
		if ( '' === $key ) {
			return $value;
		}

		if ( in_array( $key, oli_vig_boolean_setting_keys(), true ) ) {
			return oli_vig_to_bool( $raw_value );
		}

		if ( 'thumbnail_position' === $key ) {
			return in_array( $value, array( 'bottom', 'left', 'right' ), true ) ? $value : 'bottom';
		}

		if ( 'thumbnail_mode' === $key ) {
			return in_array( $value, array( 'grid', 'slider' ), true ) ? $value : 'grid';
		}

		if ( 'transition_effect' === $key ) {
			return in_array( $value, array( 'none', 'fade', 'slide' ), true ) ? $value : 'none';
		}

		if ( 'thumbnail_columns' === $key ) {
			return max( 1, min( 8, absint( $value ) ) );
		}

		if ( 'gallery_width' === $key ) {
			return max( 50, min( 100, absint( $value ) ) );
		}

		return $value;
	}

	/**
	 * Extract the array key from an `oli_vig_settings[key]` field id.
	 *
	 * @param array $option Field definition.
	 * @return string
	 */
	private function setting_key_from_option( $option ) {
		$id = isset( $option['id'] ) ? (string) $option['id'] : '';
		if ( '' === $id || false === strpos( $id, '[' ) ) {
			return '';
		}

		parse_str( $id, $parsed );
		if ( empty( $parsed['oli_vig_settings'] ) || ! is_array( $parsed['oli_vig_settings'] ) ) {
			return '';
		}

		return (string) key( $parsed['oli_vig_settings'] );
	}

	/**
	 * Render a checkbox that stores a real boolean.
	 *
	 * @param array $value Field definition.
	 */
	public function render_checkbox_field( $value ) {
		$option_value = class_exists( 'WC_Admin_Settings' )
			? WC_Admin_Settings::get_option( $value['id'], isset( $value['default'] ) ? $value['default'] : false )
			: false;
		$checked      = oli_vig_to_bool( $option_value );
		$field_id     = isset( $value['id'] ) ? $value['id'] : '';
		$title        = isset( $value['title'] ) ? $value['title'] : '';
		$desc         = isset( $value['desc'] ) ? $value['desc'] : '';
		$class        = isset( $value['class'] ) ? $value['class'] : '';
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $title ); ?></label>
			</th>
			<td class="forminp forminp-checkbox <?php echo esc_attr( $class ); ?>">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo esc_html( $title ); ?></span></legend>
					<label for="<?php echo esc_attr( $field_id ); ?>">
						<input
							name="<?php echo esc_attr( $field_id ); ?>"
							id="<?php echo esc_attr( $field_id ); ?>"
							type="checkbox"
							value="1"
							class="<?php echo esc_attr( $class ); ?>"
							<?php checked( $checked ); ?>
						/>
						<?php echo wp_kses_post( $desc ); ?>
					</label>
				</fieldset>
			</td>
		</tr>
		<?php
	}

	/**
	 * Open the collapsed Advanced block.
	 *
	 * @param array $value Field definition.
	 */
	public function render_details_start( $value ) {
		$title = isset( $value['title'] ) ? $value['title'] : __( 'Advanced', 'oli-variation-images-gallery' );
		echo '<details class="oli-vig-advanced" id="oli-vig-advanced-settings">';
		echo '<summary class="oli-vig-advanced-summary">' . esc_html( $title ) . '</summary>';
		echo '<table class="form-table">';
	}

	/**
	 * Close the collapsed Advanced block.
	 *
	 * @param array $value Field definition.
	 */
	public function render_details_end( $value ) {
		unset( $value );
		echo '</table>';
		echo '</details>';
	}

	/**
	 * Keep the old WooCommerce submenu so bookmarks still resolve, then redirect.
	 */
	public function add_legacy_settings_page() {
		add_submenu_page(
			'woocommerce',
			__( 'Variation Gallery Settings', 'oli-variation-images-gallery' ),
			__( 'Variation Gallery', 'oli-variation-images-gallery' ),
			'manage_woocommerce',
			self::LEGACY_PAGE_SLUG,
			array( $this, 'render_legacy_redirect' )
		);
	}

	/**
	 * Callback for the legacy submenu (admin_init usually redirects first).
	 */
	public function render_legacy_redirect() {
		wp_safe_redirect( self::get_settings_url() );
		exit;
	}

	/**
	 * Redirect the old settings and import slugs to the Products section.
	 */
	public function redirect_legacy_pages() {
		if ( ! is_admin() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only page slug used to redirect bookmarks.
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		if ( self::LEGACY_PAGE_SLUG === $page || 'oli-variation-gallery-importer' === $page ) {
			wp_safe_redirect( self::get_settings_url() );
			exit;
		}
	}

	/**
	 * Enqueue the settings script that shows Grid/Carousel-only fields.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_settings_assets( $hook ) {
		if ( 'woocommerce_page_wc-settings' !== $hook ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only query args decide whether to enqueue.
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only query args decide whether to enqueue.
		$section = isset( $_GET['section'] ) ? sanitize_key( wp_unslash( $_GET['section'] ) ) : '';
		if ( 'products' !== $tab || self::SECTION_ID !== $section ) {
			return;
		}

		wp_enqueue_style(
			'oli-vig-admin',
			OLI_VIG_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			OLI_VIG_VERSION
		);

		wp_enqueue_script(
			'oli-vig-settings',
			OLI_VIG_PLUGIN_URL . 'assets/js/settings.js',
			array( 'jquery' ),
			OLI_VIG_VERSION,
			true
		);
	}

	/**
	 * Point the plugin list Settings link at the Products section.
	 *
	 * @param array $links Existing action links.
	 * @return array
	 */
	public function plugin_action_links( $links ) {
		$settings_link = '<a href="' . esc_url( self::get_settings_url() ) . '">' . esc_html__( 'Settings', 'oli-variation-images-gallery' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}
}
