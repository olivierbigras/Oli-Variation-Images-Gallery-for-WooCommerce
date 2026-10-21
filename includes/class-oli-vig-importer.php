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
	 * RadiusTheme legacy gallery meta key.
	 *
	 * Stored by woo-product-variation-gallery via update_post_meta() as a PHP
	 * serialized array of integer attachment IDs, e.g.
	 * a:3:{i:0;i:101;i:1;i:202;i:2;i:303;}
	 *
	 * @var string
	 */
	const RTWPVG_LEGACY_META_KEY = 'rtwpvg_images';

	/**
	 * WooCommerce native variation gallery meta key (WC 11.1+ / RadiusTheme migration target).
	 *
	 * Stored as a comma-separated list of attachment IDs.
	 *
	 * @var string
	 */
	const WC_GALLERY_META_KEY = '_product_image_gallery';

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
	 * Parse a raw gallery meta value into attachment IDs.
	 *
	 * Accepts the real RadiusTheme format (PHP-serialized array of IDs), an
	 * already-unserialized array, a comma-separated ID list (WooCommerce native
	 * `_product_image_gallery`), JSON arrays, or a single numeric ID.
	 *
	 * This method is WordPress-independent so it can be unit-tested without WP.
	 *
	 * @param mixed $raw Raw meta value from get_post_meta() or the database.
	 * @return int[] Attachment IDs in stored order.
	 */
	public static function parse_gallery_meta_value( $raw ) {
		if ( is_array( $raw ) ) {
			return self::normalize_attachment_ids( $raw );
		}

		if ( is_int( $raw ) || is_float( $raw ) ) {
			return self::normalize_attachment_ids( array( $raw ) );
		}

		if ( ! is_string( $raw ) ) {
			return array();
		}

		$raw = trim( $raw );

		if ( '' === $raw || 'a:0:{}' === $raw || 'N;' === $raw ) {
			return array();
		}

		if ( self::is_php_serialized( $raw ) ) {
			$unserialized = @unserialize( $raw, array( 'allowed_classes' => false ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize, WordPress.PHP.NoSilencedErrors.Discouraged -- Parsing RadiusTheme serialized gallery meta.
			if ( is_array( $unserialized ) ) {
				return self::normalize_attachment_ids( $unserialized );
			}
			if ( is_numeric( $unserialized ) ) {
				return self::normalize_attachment_ids( array( $unserialized ) );
			}
		}

		$first = substr( $raw, 0, 1 );
		if ( '[' === $first || '{' === $first ) {
			$decoded = json_decode( $raw, true );
			if ( is_array( $decoded ) ) {
				return self::normalize_attachment_ids( $decoded );
			}
		}

		if ( false !== strpos( $raw, ',' ) || is_numeric( $raw ) ) {
			return self::normalize_attachment_ids( explode( ',', $raw ) );
		}

		return array();
	}

	/**
	 * Normalize a list of values to unique positive integer attachment IDs.
	 *
	 * @param array $ids Raw ID list.
	 * @return int[]
	 */
	public static function normalize_attachment_ids( $ids ) {
		$normalized = array();

		foreach ( (array) $ids as $id ) {
			if ( is_array( $id ) ) {
				continue;
			}
			$id = abs( (int) $id );
			if ( $id > 0 ) {
				$normalized[] = $id;
			}
		}

		return array_values( array_unique( $normalized ) );
	}

	/**
	 * Whether a string looks like a PHP serialized value.
	 *
	 * Mirrors WordPress is_serialized() enough to identify RadiusTheme arrays.
	 *
	 * @param string $data Raw string.
	 * @return bool
	 */
	public static function is_php_serialized( $data ) {
		if ( ! is_string( $data ) ) {
			return false;
		}

		$data = trim( $data );
		if ( 'N;' === $data ) {
			return true;
		}
		if ( strlen( $data ) < 4 || ':' !== $data[1] ) {
			return false;
		}

		$last = substr( $data, -1 );
		if ( ';' !== $last && '}' !== $last ) {
			return false;
		}

		$token = $data[0];
		if ( 'a' === $token || 'O' === $token || 's' === $token ) {
			return (bool) preg_match( '/^' . $token . ':[0-9]+:/s', $data );
		}
		if ( 'b' === $token || 'i' === $token || 'd' === $token ) {
			return (bool) preg_match( '/^' . $token . ':[0-9.E+-]+;$/', $data );
		}

		return false;
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

		$import_status  = '';
		$imported_count = 0;
		$found_count    = 0;

		if ( isset( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'oli_vig_import_result' ) ) {
			$import_status  = isset( $_GET['import_status'] ) ? sanitize_key( wp_unslash( $_GET['import_status'] ) ) : '';
			$imported_count = isset( $_GET['imported_count'] ) ? absint( wp_unslash( $_GET['imported_count'] ) ) : 0;
			$found_count    = isset( $_GET['found_count'] ) ? absint( wp_unslash( $_GET['found_count'] ) ) : 0;
		}

		$has_data = $this->check_radiustheme_data();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Import Variation Gallery Data', 'oli-variation-images-gallery' ); ?></h1>

			<?php if ( 'success' === $import_status ) : ?>
				<div class="notice notice-success">
					<p>
						<?php
						printf(
							/* translators: 1: number of variations imported */
							esc_html__( 'Successfully imported gallery data for %d variations.', 'oli-variation-images-gallery' ),
							absint( $imported_count )
						);
						?>
					</p>
				</div>
			<?php elseif ( 'dry_run' === $import_status ) : ?>
				<div class="notice notice-info">
					<p>
						<?php
						printf(
							/* translators: 1: number of variations that would be imported */
							esc_html__( 'Dry run complete. Found %d variations with gallery data that can be imported.', 'oli-variation-images-gallery' ),
							absint( $found_count )
						);
						?>
					</p>
				</div>
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
	 * Check if RadiusTheme (or migrated native) gallery data exists.
	 *
	 * @return bool
	 */
	private function check_radiustheme_data() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-off admin existence check.
		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value != '' AND meta_value != 'a:0:{}'",
				self::RTWPVG_LEGACY_META_KEY
			)
		);

		if ( $count > 0 ) {
			return true;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-off admin existence check.
		$native_count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->postmeta} pm
				INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				WHERE pm.meta_key = %s
				AND p.post_type = %s
				AND pm.meta_value != ''",
				self::WC_GALLERY_META_KEY,
				'product_variation'
			)
		);

		return $native_count > 0;
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
				'_wpnonce'       => wp_create_nonce( 'oli_vig_import_result' ),
			),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Run import.
	 *
	 * Reads RadiusTheme `rtwpvg_images` (serialized attachment-ID arrays) and,
	 * when present, WooCommerce native `_product_image_gallery` on variations.
	 * Never deletes source meta.
	 *
	 * @param bool $dry_run Whether to run in dry-run mode.
	 * @return array{found:int,imported:int}
	 */
	public function run_import( $dry_run = false ) {
		$found    = 0;
		$imported = 0;
		$seen     = array();

		$rows = $this->query_source_rows();

		foreach ( $rows as $row ) {
			$variation_id = (int) $row['post_id'];
			$image_ids    = self::parse_gallery_meta_value( $row['meta_value'] );

			if ( empty( $image_ids ) ) {
				continue;
			}

			if ( isset( $seen[ $variation_id ] ) ) {
				continue;
			}
			$seen[ $variation_id ] = true;

			++$found;

			if ( ! $dry_run ) {
				Oli_VIG_Main::save_variation_gallery_images( $variation_id, $image_ids );
				++$imported;
			}
		}

		return array(
			'found'    => $found,
			'imported' => $imported,
		);
	}

	/**
	 * Fetch source gallery rows from postmeta.
	 *
	 * Prefer `rtwpvg_images`. Fall back to `_product_image_gallery` on
	 * product_variation posts when a store has already been migrated to the
	 * native WooCommerce gallery (RadiusTheme copies IDs there on WC 11.1+).
	 *
	 * @return array<int,array{post_id:int,meta_value:string,meta_key:string}>
	 */
	private function query_source_rows() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Bulk import of RadiusTheme gallery meta.
		$legacy = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_id, meta_value, meta_key FROM {$wpdb->postmeta}
				WHERE meta_key = %s AND meta_value != '' AND meta_value != 'a:0:{}'",
				self::RTWPVG_LEGACY_META_KEY
			),
			ARRAY_A
		);

		if ( ! is_array( $legacy ) ) {
			$legacy = array();
		}

		$legacy_ids = array();
		foreach ( $legacy as $row ) {
			$legacy_ids[ (int) $row['post_id'] ] = true;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Bulk import of native variation gallery meta.
		$native = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT pm.post_id, pm.meta_value, pm.meta_key FROM {$wpdb->postmeta} pm
				INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				WHERE pm.meta_key = %s
				AND p.post_type = %s
				AND p.post_status != %s
				AND pm.meta_value != ''",
				self::WC_GALLERY_META_KEY,
				'product_variation',
				'trash'
			),
			ARRAY_A
		);

		if ( ! is_array( $native ) ) {
			$native = array();
		}

		$combined = $legacy;
		foreach ( $native as $row ) {
			if ( isset( $legacy_ids[ (int) $row['post_id'] ] ) ) {
				continue;
			}
			$combined[] = $row;
		}

		return $combined;
	}

	/**
	 * Register WP-CLI command
	 */
	private function register_cli_command() {
		WP_CLI::add_command(
			'oli-vig import',
			array( $this, 'cli_import' ),
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

	/**
	 * WP-CLI import callback.
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function cli_import( $args, $assoc_args ) {
		unset( $args );
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
	}
}
