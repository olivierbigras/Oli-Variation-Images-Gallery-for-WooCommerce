<?php
/**
 * Settings page.
 *
 * @package Oli_Variation_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings class.
 */
class Oli_VIG_Settings {

	/**
	 * Single instance.
	 *
	 * @var Oli_VIG_Settings|null
	 */
	private static $instance = null;

	/**
	 * Get instance.
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
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ), 99 );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_filter( 'option_page_capability_oli_vig_settings_group', array( $this, 'settings_capability' ) );
	}

	/**
	 * Capability required to save the settings page.
	 *
	 * @return string
	 */
	public function settings_capability() {
		return 'manage_woocommerce';
	}

	/**
	 * Add settings page.
	 */
	public function add_settings_page() {
		add_submenu_page(
			'woocommerce',
			__( 'Variation Gallery Settings', 'oli-variation-gallery' ),
			__( 'Variation Gallery', 'oli-variation-gallery' ),
			'manage_woocommerce',
			'oli-variation-gallery-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings.
	 */
	public function register_settings() {
		register_setting(
			'oli_vig_settings_group',
			'oli_vig_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => Oli_VIG_Main::get_default_settings(),
				'show_in_rest'      => false,
			)
		);

		add_settings_section(
			'oli_vig_gallery_section',
			__( 'Gallery Settings', 'oli-variation-gallery' ),
			array( $this, 'render_gallery_section' ),
			'oli-variation-gallery-settings'
		);

		add_settings_field(
			'thumbnail_position',
			__( 'Thumbnail Position', 'oli-variation-gallery' ),
			array( $this, 'render_thumbnail_position_field' ),
			'oli-variation-gallery-settings',
			'oli_vig_gallery_section'
		);

		add_settings_field(
			'thumbnail_columns',
			__( 'Thumbnail Columns', 'oli-variation-gallery' ),
			array( $this, 'render_thumbnail_columns_field' ),
			'oli-variation-gallery-settings',
			'oli_vig_gallery_section'
		);

		add_settings_field(
			'enable_zoom',
			__( 'Enable Zoom', 'oli-variation-gallery' ),
			array( $this, 'render_enable_zoom_field' ),
			'oli-variation-gallery-settings',
			'oli_vig_gallery_section'
		);

		add_settings_field(
			'enable_lightbox',
			__( 'Enable Lightbox', 'oli-variation-gallery' ),
			array( $this, 'render_enable_lightbox_field' ),
			'oli-variation-gallery-settings',
			'oli_vig_gallery_section'
		);

		add_settings_field(
			'gallery_width',
			__( 'Gallery Width (%)', 'oli-variation-gallery' ),
			array( $this, 'render_gallery_width_field' ),
			'oli-variation-gallery-settings',
			'oli_vig_gallery_section'
		);

		add_settings_field(
			'preload_images',
			__( 'Preload Images', 'oli-variation-gallery' ),
			array( $this, 'render_preload_images_field' ),
			'oli-variation-gallery-settings',
			'oli_vig_gallery_section'
		);

		add_settings_field(
			'reset_on_clear',
			__( 'Reset Gallery on Clear', 'oli-variation-gallery' ),
			array( $this, 'render_reset_on_clear_field' ),
			'oli-variation-gallery-settings',
			'oli_vig_gallery_section'
		);
	}

	/**
	 * Sanitize settings.
	 *
	 * @param mixed $input Raw input data.
	 * @return array<string,mixed>
	 */
	public function sanitize_settings( $input ) {
		return self::sanitize( $input );
	}

	/**
	 * Sanitize settings without requiring an instance.
	 *
	 * @param mixed $input Raw input data.
	 * @return array<string,mixed>
	 */
	public static function sanitize( $input ) {
		if ( ! is_array( $input ) ) {
			$input = array();
		}

		$defaults  = Oli_VIG_Main::get_default_settings();
		$sanitized = array();

		$sanitized['thumbnail_position'] = isset( $input['thumbnail_position'] ) && in_array( $input['thumbnail_position'], array( 'bottom', 'left', 'right' ), true )
			? $input['thumbnail_position']
			: $defaults['thumbnail_position'];

		$sanitized['thumbnail_columns'] = isset( $input['thumbnail_columns'] )
			? max( 1, min( 8, absint( $input['thumbnail_columns'] ) ) )
			: $defaults['thumbnail_columns'];

		$sanitized['enable_zoom'] = ! empty( $input['enable_zoom'] ) ? 'yes' : 'no';

		$sanitized['enable_lightbox'] = ! empty( $input['enable_lightbox'] ) ? 'yes' : 'no';

		$sanitized['gallery_width'] = isset( $input['gallery_width'] )
			? max( 50, min( 100, absint( $input['gallery_width'] ) ) )
			: $defaults['gallery_width'];

		$sanitized['preload_images'] = ! empty( $input['preload_images'] ) ? 'yes' : 'no';

		$sanitized['reset_on_clear'] = ! empty( $input['reset_on_clear'] ) ? 'yes' : 'no';

		return $sanitized;
	}

	/**
	 * Render settings page.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only flag set by the Settings API after options.php.
		if ( isset( $_GET['settings-updated'] ) ) {
			add_settings_error(
				'oli_vig_messages',
				'oli_vig_message',
				__( 'Settings saved successfully.', 'oli-variation-gallery' ),
				'updated'
			);
		}

		settings_errors( 'oli_vig_messages' );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'oli_vig_settings_group' );
				do_settings_sections( 'oli-variation-gallery-settings' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render gallery section description.
	 */
	public function render_gallery_section() {
		echo '<p>' . esc_html__( 'Configure how variation galleries are displayed on product pages. Zoom, lightbox, and the image slider use the default gallery.', 'oli-variation-gallery' ) . '</p>';
	}

	/**
	 * Render thumbnail position field.
	 */
	public function render_thumbnail_position_field() {
		$settings = Oli_VIG_Main::get_settings();
		$value    = $settings['thumbnail_position'];
		?>
		<select name="oli_vig_settings[thumbnail_position]" id="oli_vig_thumbnail_position">
			<option value="bottom" <?php selected( $value, 'bottom' ); ?>><?php esc_html_e( 'Bottom', 'oli-variation-gallery' ); ?></option>
			<option value="left" <?php selected( $value, 'left' ); ?>><?php esc_html_e( 'Left', 'oli-variation-gallery' ); ?></option>
			<option value="right" <?php selected( $value, 'right' ); ?>><?php esc_html_e( 'Right', 'oli-variation-gallery' ); ?></option>
		</select>
		<?php
	}

	/**
	 * Render thumbnail columns field.
	 */
	public function render_thumbnail_columns_field() {
		$settings = Oli_VIG_Main::get_settings();
		$value    = absint( $settings['thumbnail_columns'] );
		?>
		<input type="number" name="oli_vig_settings[thumbnail_columns]" id="oli_vig_thumbnail_columns"
			value="<?php echo esc_attr( (string) $value ); ?>" min="1" max="8" />
		<p class="description"><?php esc_html_e( 'Number of thumbnail columns (1-8).', 'oli-variation-gallery' ); ?></p>
		<?php
	}

	/**
	 * Render enable zoom field.
	 */
	public function render_enable_zoom_field() {
		$settings = Oli_VIG_Main::get_settings();
		$checked  = ( 'yes' === $settings['enable_zoom'] );
		?>
		<label>
			<input type="checkbox" name="oli_vig_settings[enable_zoom]" value="yes" <?php checked( $checked ); ?> />
			<?php esc_html_e( 'Use the default product image zoom.', 'oli-variation-gallery' ); ?>
		</label>
		<?php
	}

	/**
	 * Render enable lightbox field.
	 */
	public function render_enable_lightbox_field() {
		$settings = Oli_VIG_Main::get_settings();
		$checked  = ( 'yes' === $settings['enable_lightbox'] );
		?>
		<label>
			<input type="checkbox" name="oli_vig_settings[enable_lightbox]" value="yes" <?php checked( $checked ); ?> />
			<?php esc_html_e( 'Use the default product image lightbox.', 'oli-variation-gallery' ); ?>
		</label>
		<?php
	}

	/**
	 * Render gallery width field.
	 */
	public function render_gallery_width_field() {
		$settings = Oli_VIG_Main::get_settings();
		$value    = absint( $settings['gallery_width'] );
		?>
		<input type="number" name="oli_vig_settings[gallery_width]" id="oli_vig_gallery_width"
			value="<?php echo esc_attr( (string) $value ); ?>" min="50" max="100" />
		<p class="description"><?php esc_html_e( 'Gallery width as a percentage (50-100%).', 'oli-variation-gallery' ); ?></p>
		<?php
	}

	/**
	 * Render preload images field.
	 */
	public function render_preload_images_field() {
		$settings = Oli_VIG_Main::get_settings();
		$checked  = ( 'yes' === $settings['preload_images'] );
		?>
		<label>
			<input type="checkbox" name="oli_vig_settings[preload_images]" value="yes" <?php checked( $checked ); ?> />
			<?php esc_html_e( 'Preload variation images for faster switching.', 'oli-variation-gallery' ); ?>
		</label>
		<?php
	}

	/**
	 * Render reset on clear field.
	 */
	public function render_reset_on_clear_field() {
		$settings = Oli_VIG_Main::get_settings();
		$checked  = ( 'yes' === $settings['reset_on_clear'] );
		?>
		<label>
			<input type="checkbox" name="oli_vig_settings[reset_on_clear]" value="yes" <?php checked( $checked ); ?> />
			<?php esc_html_e( 'Reset to the main gallery when the variation is cleared.', 'oli-variation-gallery' ); ?>
		</label>
		<?php
	}
}
