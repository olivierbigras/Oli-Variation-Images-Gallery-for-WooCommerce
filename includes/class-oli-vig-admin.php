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
				/* translators: %s: image title or filename */
				'removeImage' => __( 'Remove image %s', 'oli-variation-images-gallery' ),
				'moveLeft'    => __( 'Move image left', 'oli-variation-images-gallery' ),
				'moveRight'   => __( 'Move image right', 'oli-variation-images-gallery' ),
			)
		);
	}

	/**
	 * Human-readable label for an attachment (title, else filename, else ID).
	 *
	 * @param int $image_id Attachment ID.
	 * @return string
	 */
	private function get_image_label( $image_id ) {
		$title = get_the_title( $image_id );
		if ( is_string( $title ) && '' !== $title ) {
			return $title;
		}

		$file = get_attached_file( $image_id );
		if ( is_string( $file ) && '' !== $file ) {
			return wp_basename( $file );
		}

		return (string) $image_id;
	}

	/**
	 * Render one variation gallery thumbnail.
	 *
	 * @param int $image_id Attachment ID.
	 * @param int $index    Zero-based index in the list.
	 * @param int $total    Total images.
	 */
	private function render_gallery_image_item( $image_id, $index, $total ) {
		$image_url = wp_get_attachment_image_url( $image_id, 'thumbnail' );
		if ( ! $image_url ) {
			return;
		}

		$label        = $this->get_image_label( $image_id );
		$remove_label = sprintf(
			/* translators: %s: image title or filename */
			__( 'Remove image %s', 'oli-variation-images-gallery' ),
			$label
		);
		?>
		<li class="oli-vig-gallery-image" data-attachment-id="<?php echo esc_attr( $image_id ); ?>">
			<img src="<?php echo esc_url( $image_url ); ?>" alt="" />
			<div class="oli-vig-image-actions">
				<button type="button" class="oli-vig-move-image oli-vig-move-left" <?php disabled( 0 === $index ); ?> aria-label="<?php esc_attr_e( 'Move image left', 'oli-variation-images-gallery' ); ?>">
					<span class="dashicons dashicons-arrow-left-alt2" aria-hidden="true"></span>
				</button>
				<button type="button" class="oli-vig-remove-image" aria-label="<?php echo esc_attr( $remove_label ); ?>">
					<span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
				</button>
				<button type="button" class="oli-vig-move-image oli-vig-move-right" <?php disabled( $index >= ( $total - 1 ) ); ?> aria-label="<?php esc_attr_e( 'Move image right', 'oli-variation-images-gallery' ); ?>">
					<span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>
				</button>
			</div>
		</li>
		<?php
	}

	/**
	 * Add variation gallery field
	 *
	 * @param int     $loop Variation loop index.
	 * @param array   $variation_data Variation data.
	 * @param WP_Post $variation Variation post object.
	 */
	public function add_variation_gallery_field( $loop, $variation_data, $variation ) {
		unset( $variation_data );

		$variation_id = $variation->ID;
		$image_ids    = Oli_VIG_Main::get_variation_gallery_images( $variation_id );
		$field_id     = 'oli_vig_gallery_images_' . absint( $loop );
		$help_id      = $field_id . '_description';
		$empty_id     = $field_id . '_empty';
		$total        = count( $image_ids );
		$described_by = $help_id . ( empty( $image_ids ) ? ' ' . $empty_id : '' );

		?>
		<div class="form-row form-row-full oli-vig-variation-gallery-wrapper">
			<label for="<?php echo esc_attr( $field_id ); ?>"><?php esc_html_e( 'Variation gallery images', 'oli-variation-images-gallery' ); ?></label>
			<p id="<?php echo esc_attr( $help_id ); ?>" class="description">
				<?php esc_html_e( 'The first image of the variation remains the main image.', 'oli-variation-images-gallery' ); ?>
			</p>
			<div class="oli-vig-gallery-container">
				<ul class="oli-vig-gallery-images" data-variation-id="<?php echo esc_attr( $variation_id ); ?>">
					<?php
					if ( ! empty( $image_ids ) ) {
						foreach ( $image_ids as $index => $image_id ) {
							$this->render_gallery_image_item( $image_id, (int) $index, $total );
						}
					}
					?>
				</ul>
				<p id="<?php echo esc_attr( $empty_id ); ?>" class="oli-vig-empty-state" <?php echo empty( $image_ids ) ? '' : 'hidden'; ?>>
					<?php esc_html_e( 'No images: the product gallery will be used.', 'oli-variation-images-gallery' ); ?>
				</p>
				<button type="button" class="button oli-vig-add-images">
					<?php esc_html_e( 'Add images', 'oli-variation-images-gallery' ); ?>
				</button>
			</div>
			<input type="hidden"
				id="<?php echo esc_attr( $field_id ); ?>"
				name="oli_vig_gallery_images[<?php echo esc_attr( $loop ); ?>]"
				class="oli-vig-gallery-images-input"
				value="<?php echo esc_attr( implode( ',', $image_ids ) ); ?>"
				data-help-id="<?php echo esc_attr( $help_id ); ?>"
				data-empty-id="<?php echo esc_attr( $empty_id ); ?>"
				aria-describedby="<?php echo esc_attr( $described_by ); ?>" />
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
