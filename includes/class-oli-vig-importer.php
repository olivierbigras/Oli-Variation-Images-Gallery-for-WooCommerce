<?php
/**
 * Importer from RadiusTheme Variation Images Gallery
 *
 * @package Oli_Variation_Images_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Importer class
 */
class Oli_VIG_Importer {

	/**
	 * Single instance
	 *
	 * @var Oli_VIG_Importer
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return Oli_VIG_Importer
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
		add_action( 'admin_menu', array( $this, 'add_importer_page' ), 100 );
		add_action( 'admin_post_oli_vig_run_import', array( $this, 'handle_import' ) );

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			$this->register_cli_command();
		}
	}

	/**
	 * Add importer page
	 */
	public function add_importer_page() {
		add_submenu_page(
			'woocommerce',
			__( 'Import Variation Gallery Data', 'oli-variation-images-gallery' ),
			__( 'Import Gallery Data', 'oli-variation-images-gallery' ),
			'manage_woocommerce',
			'oli-variation-gallery-importer',
			array( $this, 'render_importer_page' )
		);
	}

	/**
	 * Render importer page
	 */
	public function render_importer_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$has_data = $this->check_radiustheme_data();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Import Variation Gallery Data', 'oli-variation-images-gallery' ); ?></h1>
			
			<?php if ( isset( $_GET['import_status'] ) ) : ?>
				<?php if ( 'success' === $_GET['import_status'] ) : ?>
					<div class="notice notice-success">
						<p>
							<?php
							printf(
								/* translators: 1: number of variations imported */
								esc_html__( 'Successfully imported gallery data for %d variations.', 'oli-variation-images-gallery' ),
								isset( $_GET['imported_count'] ) ? intval( $_GET['imported_count'] ) : 0
							);
							?>
						</p>
					</div>
				<?php elseif ( 'dry_run' === $_GET['import_status'] ) : ?>
					<div class="notice notice-info">
						<p>
							<?php
							printf(
								/* translators: 1: number of variations that would be imported */
								esc_html__( 'Dry run complete. Found %d variations with gallery data that can be imported.', 'oli-variation-images-gallery' ),
								isset( $_GET['found_count'] ) ? intval( $_GET['found_count'] ) : 0
							);
							?>
						</p>
					</div>
				<?php endif; ?>
			<?php endif; ?>

			<div class="card">
				<h2><?php esc_html_e( 'Import from RadiusTheme Variation Images Gallery', 'oli-variation-images-gallery' ); ?></h2>
				
				<?php if ( $has_data ) : ?>
					<p><?php esc_html_e( 'This tool will import variation gallery images from the RadiusTheme "Variation Images Gallery for WooCommerce" plugin.', 'oli-variation-images-gallery' ); ?></p>
					<p><strong><?php esc_html_e( 'Important:', 'oli-variation-images-gallery' ); ?></strong> <?php esc_html_e( 'The original data will NOT be deleted. You can safely run this import and keep both plugins active during migration.', 'oli-variation-images-gallery' ); ?></p>
					
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'oli_vig_import', 'oli_vig_import_nonce' ); ?>
						<input type="hidden" name="action" value="oli_vig_run_import" />
						
						<p>
							<label>
								<input type="checkbox" name="dry_run" value="1" />
								<?php esc_html_e( 'Dry run (preview only, do not import)', 'oli-variation-images-gallery' ); ?>
							</label>
						</p>
						
						<p>
							<?php submit_button( __( 'Run Import', 'oli-variation-images-gallery' ), 'primary', 'submit', false ); ?>
						</p>
					</form>
				<?php else : ?>
					<p><?php esc_html_e( 'No RadiusTheme variation gallery data found. Either the plugin is not installed, or no variations have gallery images.', 'oli-variation-images-gallery' ); ?></p>
				<?php endif; ?>
			</div>

			<div class="card">
				<h2><?php esc_html_e( 'WP-CLI Command', 'oli-variation-images-gallery' ); ?></h2>
				<p><?php esc_html_e( 'You can also run the import via WP-CLI:', 'oli-variation-images-gallery' ); ?></p>
				<pre>wp oli-vig import [--dry-run]</pre>
			</div>
		</div>
		<?php
	}

	/**
	 * Check if RadiusTheme data exists
	 *
	 * @return bool
	 */
	private function check_radiustheme_data() {
		global $wpdb;

		$count = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->postmeta} 
			WHERE meta_key = 'rtwpvg_images' 
			AND meta_value != ''"
		);

		return $count > 0;
	}

	/**
	 * Handle import
	 */
	public function handle_import() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'oli-variation-images-gallery' ) );
		}

		if ( ! isset( $_POST['oli_vig_import_nonce'] ) || 
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['oli_vig_import_nonce'] ) ), 'oli_vig_import' ) ) {
			wp_die( esc_html__( 'Invalid nonce.', 'oli-variation-images-gallery' ) );
		}

		$dry_run = ! empty( $_POST['dry_run'] );
		$result  = $this->run_import( $dry_run );

		$redirect_url = add_query_arg(
			array(
				'page'           => 'oli-variation-gallery-importer',
				'import_status'  => $dry_run ? 'dry_run' : 'success',
				'imported_count' => $result['imported'],
				'found_count'    => $result['found'],
			),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Run import
	 *
	 * @param bool $dry_run Whether to run in dry-run mode.
	 * @return array
	 */
	public function run_import( $dry_run = false ) {
		global $wpdb;

		$variations = $wpdb->get_results(
			"SELECT post_id, meta_value FROM {$wpdb->postmeta} 
			WHERE meta_key = 'rtwpvg_images' 
			AND meta_value != ''",
			ARRAY_A
		);

		$found    = 0;
		$imported = 0;

		foreach ( $variations as $row ) {
			$variation_id = intval( $row['post_id'] );
			$meta_value   = $row['meta_value'];

			$image_ids = maybe_unserialize( $meta_value );

			if ( empty( $image_ids ) || ! is_array( $image_ids ) ) {
				continue;
			}

			$image_ids = array_filter( array_map( 'intval', $image_ids ) );

			if ( empty( $image_ids ) ) {
				continue;
			}

			$found++;

			if ( ! $dry_run ) {
				Oli_VIG_Main::save_variation_gallery_images( $variation_id, $image_ids );
				$imported++;
			}
		}

		return array(
			'found'    => $found,
			'imported' => $imported,
		);
	}

	/**
	 * Register WP-CLI command
	 */
	private function register_cli_command() {
		WP_CLI::add_command(
			'oli-vig import',
			function( $args, $assoc_args ) {
				$dry_run = isset( $assoc_args['dry-run'] );

				WP_CLI::log( 'Starting import from RadiusTheme Variation Images Gallery...' );

				$result = $this->run_import( $dry_run );

				if ( $dry_run ) {
					WP_CLI::success(
						sprintf(
							'Dry run complete. Found %d variations with gallery data.',
							$result['found']
						)
					);
				} else {
					WP_CLI::success(
						sprintf(
							'Import complete. Imported gallery data for %d variations.',
							$result['imported']
						)
					);
				}
			},
			array(
				'shortdesc' => 'Import variation gallery data from RadiusTheme plugin',
				'synopsis'  => array(
					array(
						'type'        => 'flag',
						'name'        => 'dry-run',
						'description' => 'Preview import without making changes',
						'optional'    => true,
					),
				),
			)
		);
	}
}
