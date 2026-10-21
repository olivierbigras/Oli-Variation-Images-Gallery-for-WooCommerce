<?php
/**
 * Admin functionality
 *
 * @package Oli_Variation_Images_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin class
 */
class Oli_VIG_Admin {

	/**
	 * Single instance
	 *
	 * @var Oli_VIG_Admin
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return Oli_VIG_Admin
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
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'add_variation_gallery_field' ), 10, 3 );
		add_action( 'woocommerce_save_product_variation', array( $this, 'save_variation_gallery_field' ), 10, 2 );
	}

	/**
	 * Enqueue admin assets
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}

		global $post;
		if ( ! $post || 'product' !== $post->post_type ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_script( 'jquery-ui-sortable' );

		wp_enqueue_style(
			'oli-vig-admin',
			OLI_VIG_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			OLI_VIG_VERSION
		);

		wp_enqueue_script(
			'oli-vig-admin',
			OLI_VIG_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery', 'jquery-ui-sortable' ),
			OLI_VIG_VERSION,
			true
		);

		wp_localize_script(
			'oli-vig-admin',
			'oliVigAdmin',
			array(
				'mediaTitle'  => __( 'Add Images to Variation Gallery', 'oli-variation-images-gallery' ),
				'mediaButton' => __( 'Add to gallery', 'oli-variation-images-gallery' ),
				'removeText'  => __( 'Remove', 'oli-variation-images-gallery' ),
			)
		);
	}

	/**
	 * Add variation gallery field
	 *
	 * @param int     $loop Variation loop index.
	 * @param array   $variation_data Variation data.
	 * @param WP_Post $variation Variation post object.
	 */
	public function add_variation_gallery_field( $loop, $variation_data, $variation ) {
		$variation_id = $variation->ID;
		$image_ids    = Oli_VIG_Main::get_variation_gallery_images( $variation_id );

		?>
		<div class="form-row form-row-full oli-vig-variation-gallery-wrapper">
			<label><?php esc_html_e( 'Variation Gallery Images', 'oli-variation-images-gallery' ); ?></label>
			<div class="oli-vig-gallery-container">
				<ul class="oli-vig-gallery-images" data-variation-id="<?php echo esc_attr( $variation_id ); ?>">
					<?php
					if ( ! empty( $image_ids ) ) {
						foreach ( $image_ids as $image_id ) {
							$image_url = wp_get_attachment_image_url( $image_id, 'thumbnail' );
							if ( $image_url ) {
								?>
								<li class="oli-vig-gallery-image" data-attachment-id="<?php echo esc_attr( $image_id ); ?>">
									<img src="<?php echo esc_url( $image_url ); ?>" alt="" />
									<button type="button" class="oli-vig-remove-image">
										<span class="dashicons dashicons-no-alt"></span>
									</button>
								</li>
								<?php
							}
						}
					}
					?>
				</ul>
				<button type="button" class="button oli-vig-add-images">
					<?php esc_html_e( 'Add Images', 'oli-variation-images-gallery' ); ?>
				</button>
			</div>
			<input type="hidden"
				name="oli_vig_gallery_images[<?php echo esc_attr( $loop ); ?>]"
				class="oli-vig-gallery-images-input"
				value="<?php echo esc_attr( implode( ',', $image_ids ) ); ?>" />
		</div>
		<?php
	}

	/**
	 * Save variation gallery field
	 *
	 * @param int $variation_id Variation ID.
	 * @param int $loop Variation loop index.
	 */
	public function save_variation_gallery_field( $variation_id, $loop ) {
		if ( ! isset( $_POST['oli_vig_gallery_images'][ $loop ] ) ) {
			return;
		}

		$nonce_field = 'woocommerce_save_data';
		if ( ! isset( $_POST['woocommerce_meta_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ), $nonce_field ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $variation_id ) ) {
			return;
		}

		$image_ids_raw = sanitize_text_field( wp_unslash( $_POST['oli_vig_gallery_images'][ $loop ] ) );
		$image_ids     = array();

		if ( ! empty( $image_ids_raw ) ) {
			$image_ids = array_filter( array_map( 'intval', explode( ',', $image_ids_raw ) ) );
		}

		Oli_VIG_Main::save_variation_gallery_images( $variation_id, $image_ids );
	}
}
