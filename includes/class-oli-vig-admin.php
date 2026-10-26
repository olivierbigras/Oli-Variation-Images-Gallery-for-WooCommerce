<?php
/**
 * Admin variation gallery field.
 *
 * @package Oli_Variation_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin class.
 */
class Oli_VIG_Admin {

	/**
	 * Single instance.
	 *
	 * @var Oli_VIG_Admin|null
	 */
	private static $instance = null;

	/**
	 * Get instance.
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
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'add_variation_gallery_field' ), 10, 3 );
		add_action( 'woocommerce_save_product_variation', array( $this, 'save_variation_gallery_field' ), 10, 2 );
	}

	/**
	 * Enqueue admin assets on product edit screens.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'product' !== $screen->post_type ) {
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
				'mediaTitle'  => __( 'Add Images to Variation Gallery', 'oli-variation-gallery' ),
				'mediaButton' => __( 'Add to gallery', 'oli-variation-gallery' ),
				'removeText'  => __( 'Remove', 'oli-variation-gallery' ),
				/* translators: %s: image file name */
				'removeImage' => __( 'Remove image %s', 'oli-variation-gallery' ),
				'moveLeft'    => __( 'Move image left', 'oli-variation-gallery' ),
				'moveRight'   => __( 'Move image right', 'oli-variation-gallery' ),
			)
		);
	}

	/**
	 * Add the variation gallery field.
	 *
	 * @param int     $loop           Variation loop index.
	 * @param array   $variation_data Variation data.
	 * @param WP_Post $variation      Variation post object.
	 */
	public function add_variation_gallery_field( $loop, $variation_data, $variation ) {
		unset( $variation_data );

		$variation_id = isset( $variation->ID ) ? absint( $variation->ID ) : 0;
		$image_ids    = Oli_VIG_Images::get_ids( $variation_id );
		$label_id     = 'oli-vig-label-' . $variation_id;
		$count        = count( $image_ids );
		$index        = 0;
		?>
		<div class="form-row form-row-full oli-vig-variation-gallery-wrapper">
			<label id="<?php echo esc_attr( $label_id ); ?>"><?php esc_html_e( 'Variation Gallery Images', 'oli-variation-gallery' ); ?></label>
			<div class="oli-vig-gallery-container">
				<ul class="oli-vig-gallery-images" data-variation-id="<?php echo esc_attr( (string) $variation_id ); ?>" aria-labelledby="<?php echo esc_attr( $label_id ); ?>">
					<?php
					foreach ( $image_ids as $image_id ) {
						$image_url = wp_get_attachment_image_url( $image_id, 'thumbnail' );
						if ( ! $image_url ) {
							continue;
						}

						$file     = get_attached_file( $image_id );
						$filename = $file ? wp_basename( $file ) : (string) get_the_title( $image_id );
						if ( '' === $filename ) {
							$filename = (string) $image_id;
						}

						$remove_label = sprintf(
							/* translators: %s: image file name */
							__( 'Remove image %s', 'oli-variation-gallery' ),
							$filename
						);
						$is_first = ( 0 === $index );
						$is_last  = ( $index === $count - 1 );
						++$index;
						?>
						<li class="oli-vig-gallery-image" data-attachment-id="<?php echo esc_attr( (string) $image_id ); ?>" data-filename="<?php echo esc_attr( $filename ); ?>">
							<img src="<?php echo esc_url( $image_url ); ?>" alt="" />
							<div class="oli-vig-image-actions">
								<button type="button" class="oli-vig-move-image oli-vig-move-left" aria-label="<?php esc_attr_e( 'Move image left', 'oli-variation-gallery' ); ?>"<?php disabled( $is_first ); ?>>
									<span class="dashicons dashicons-arrow-left-alt2"></span>
								</button>
								<button type="button" class="oli-vig-move-image oli-vig-move-right" aria-label="<?php esc_attr_e( 'Move image right', 'oli-variation-gallery' ); ?>"<?php disabled( $is_last ); ?>>
									<span class="dashicons dashicons-arrow-right-alt2"></span>
								</button>
								<button type="button" class="oli-vig-remove-image" aria-label="<?php echo esc_attr( $remove_label ); ?>">
									<span class="dashicons dashicons-no-alt"></span>
								</button>
							</div>
						</li>
						<?php
					}
					?>
				</ul>
				<button type="button" class="button oli-vig-add-images">
					<?php esc_html_e( 'Add Images', 'oli-variation-gallery' ); ?>
				</button>
			</div>
			<input type="hidden"
				name="oli_vig_gallery_images[<?php echo esc_attr( (string) $loop ); ?>]"
				class="oli-vig-gallery-images-input"
				value="<?php echo esc_attr( implode( ',', $image_ids ) ); ?>" />
		</div>
		<?php
	}

	/**
	 * Save the variation gallery field.
	 *
	 * @param int $variation_id Variation ID.
	 * @param int $loop         Variation loop index.
	 */
	public function save_variation_gallery_field( $variation_id, $loop ) {
		if ( ! $this->user_can_edit_variation( $variation_id ) ) {
			return;
		}

		$nonce_ok   = false;
		$candidates = array(
			array( 'woocommerce-save-variations-nonce', 'save-variations' ),
			array( 'security', 'save-variations' ),
			array( 'woocommerce_meta_nonce', 'woocommerce_save_data' ),
		);

		foreach ( $candidates as $pair ) {
			$field  = $pair[0];
			$action = $pair[1];
			if ( isset( $_POST[ $field ] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ $field ] ) ), $action ) ) {
				$nonce_ok = true;
				break;
			}
		}

		if ( ! $nonce_ok ) {
			return;
		}

		if ( ! isset( $_POST['oli_vig_gallery_images'][ $loop ] ) ) {
			return;
		}

		$image_ids_raw = sanitize_text_field( wp_unslash( $_POST['oli_vig_gallery_images'][ $loop ] ) );
		$image_ids     = array();

		if ( '' !== $image_ids_raw ) {
			$image_ids = Oli_VIG_Images::sanitize_ids( explode( ',', $image_ids_raw ) );
		}

		Oli_VIG_Images::save_ids( $variation_id, $image_ids );
	}

	/**
	 * Whether the current user can edit the variation.
	 *
	 * @param int $variation_id Variation ID.
	 * @return bool
	 */
	private function user_can_edit_variation( $variation_id ) {
		$variation_id = absint( $variation_id );
		if ( $variation_id <= 0 || ! current_user_can( 'edit_products' ) ) {
			return false;
		}

		if ( current_user_can( 'edit_post', $variation_id ) ) {
			return true;
		}

		$parent_id = wp_get_post_parent_id( $variation_id );
		return $parent_id && current_user_can( 'edit_post', $parent_id );
	}
}
