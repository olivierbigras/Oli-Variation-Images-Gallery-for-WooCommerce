<?php
/**
 * Importer for Variation Images Gallery meta (rtwpvg_images).
 *
 * @package Oli_Variation_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Importer class.
 */
class Oli_VIG_Importer {

	/**
	 * Legacy gallery meta key used by Variation Images Gallery for WooCommerce.
	 *
	 * Public docs describe each variation storing an array of attachment IDs
	 * under `rtwpvg_images`. WordPress serializes that array in postmeta.
	 *
	 * @var string
	 */
	const RTWPVG_LEGACY_META_KEY = 'rtwpvg_images';

	/**
	 * Native variation gallery meta key (comma-separated attachment IDs).
	 *
	 * @var string
	 */
	const WC_GALLERY_META_KEY = '_product_image_gallery';

	/**
	 * Single instance.
	 *
	 * @var Oli_VIG_Importer|null
	 */
	private static $instance = null;

	/**
	 * Get instance.
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
	 * Constructor.
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
	 * Accepts a PHP-serialized array of IDs, an already-unserialized array,
	 * a comma-separated ID list, a JSON array, or a single numeric ID.
	 *
	 * @param mixed $raw Raw meta value.
	 * @return int[]
	 */
	public static function parse_gallery_meta_value( $raw ) {
		if ( is_array( $raw ) ) {
			return Oli_VIG_Images::sanitize_ids( $raw );
		}

		if ( is_int( $raw ) || is_float( $raw ) ) {
			return Oli_VIG_Images::sanitize_ids( array( $raw ) );
		}

		if ( ! is_string( $raw ) ) {
			return array();
		}

		$raw = trim( $raw );

		if ( '' === $raw || 'a:0:{}' === $raw || 'N;' === $raw ) {
			return array();
		}

		if ( self::is_php_serialized( $raw ) ) {
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize, WordPress.PHP.NoSilencedErrors.Discouraged -- Parsing locally stored serialized gallery meta.
			$unserialized = @unserialize( $raw, array( 'allowed_classes' => false ) );
			if ( is_array( $unserialized ) ) {
				return Oli_VIG_Images::sanitize_ids( $unserialized );
			}
			if ( is_numeric( $unserialized ) ) {
				return Oli_VIG_Images::sanitize_ids( array( $unserialized ) );
			}
		}

		$first = substr( $raw, 0, 1 );
		if ( '[' === $first || '{' === $first ) {
			$decoded = json_decode( $raw, true );
			if ( is_array( $decoded ) ) {
				return Oli_VIG_Images::sanitize_ids( $decoded );
			}
		}

		if ( false !== strpos( $raw, ',' ) || is_numeric( $raw ) ) {
			return Oli_VIG_Images::sanitize_ids( explode( ',', $raw ) );
		}

		return array();
	}

	/**
	 * Whether a string looks like a PHP serialized value.
	 *
	 * @param mixed $data Raw value.
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
	 * Add importer page.
	 */
	public function add_importer_page() {
		add_submenu_page(
			'woocommerce',
			__( 'Import Variation Gallery Data', 'oli-variation-gallery' ),
			__( 'Import Gallery Data', 'oli-variation-gallery' ),
			'manage_woocommerce',
			'oli-variation-gallery-importer',
			array( $this, 'render_importer_page' )
		);
	}

	/**
	 * Render importer page.
	 */
	public function render_importer_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$import_status  = '';
		$imported_count = 0;
		$found_count    = 0;
		$skipped_count  = 0;

		if ( isset( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'oli_vig_import_result' ) ) {
			$import_status  = isset( $_GET['import_status'] ) ? sanitize_key( wp_unslash( $_GET['import_status'] ) ) : '';
			$imported_count = isset( $_GET['imported_count'] ) ? absint( wp_unslash( $_GET['imported_count'] ) ) : 0;
			$found_count    = isset( $_GET['found_count'] ) ? absint( wp_unslash( $_GET['found_count'] ) ) : 0;
			$skipped_count  = isset( $_GET['skipped_count'] ) ? absint( wp_unslash( $_GET['skipped_count'] ) ) : 0;
		}

		$preview = $this->count_source_variations();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Import Variation Gallery Data', 'oli-variation-gallery' ); ?></h1>

			<?php if ( 'success' === $import_status ) : ?>
				<div class="notice notice-success">
					<p>
						<?php
						echo esc_html(
							sprintf(
								/* translators: 1: imported count, 2: already-matching count */
								__( 'Import complete. Imported gallery data for %1$d variations. %2$d already matched and were skipped.', 'oli-variation-gallery' ),
								$imported_count,
								$skipped_count
							)
						);
						?>
					</p>
				</div>
			<?php elseif ( 'dry_run' === $import_status ) : ?>
				<div class="notice notice-info">
					<p>
						<?php
						echo esc_html(
							sprintf(
								/* translators: 1: found count, 2: already-matching count */
								__( 'Dry run complete. Found %1$d variations with gallery data. %2$d already match and would be skipped.', 'oli-variation-gallery' ),
								$found_count,
								$skipped_count
							)
						);
						?>
					</p>
				</div>
			<?php endif; ?>

			<div class="card">
				<h2><?php esc_html_e( 'Import existing variation gallery meta', 'oli-variation-gallery' ); ?></h2>
				<p><?php esc_html_e( 'This tool copies variation gallery images stored under the rtwpvg_images meta key (and native variation gallery IDs when present) into this plugin. Source meta is never deleted. Running the import more than once is safe: existing non-empty galleries are left unchanged unless you force an overwrite.', 'oli-variation-gallery' ); ?></p>
				<p>
					<?php
					echo esc_html(
						sprintf(
							/* translators: %d: number of variations with importable source meta */
							__( 'Dry-run count: %d variations currently have importable gallery data.', 'oli-variation-gallery' ),
							$preview['found']
						)
					);
					?>
				</p>

				<?php if ( $preview['found'] > 0 ) : ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'oli_vig_import', 'oli_vig_import_nonce' ); ?>
						<input type="hidden" name="action" value="oli_vig_run_import" />

						<p>
							<label>
								<input type="checkbox" name="dry_run" value="1" />
								<?php esc_html_e( 'Dry run (preview only, do not import)', 'oli-variation-gallery' ); ?>
							</label>
						</p>

						<p>
							<label>
								<input type="checkbox" name="force" value="1" />
								<?php esc_html_e( 'Force overwrite of existing galleries that were edited after a previous import', 'oli-variation-gallery' ); ?>
							</label>
						</p>

						<p>
							<?php submit_button( __( 'Run Import', 'oli-variation-gallery' ), 'primary', 'submit', false ); ?>
						</p>
					</form>
				<?php else : ?>
					<p><?php esc_html_e( 'No importable variation gallery data was found.', 'oli-variation-gallery' ); ?></p>
				<?php endif; ?>
			</div>

			<div class="card">
				<h2><?php esc_html_e( 'WP-CLI Command', 'oli-variation-gallery' ); ?></h2>
				<p><?php esc_html_e( 'Preview the dry-run count:', 'oli-variation-gallery' ); ?></p>
				<pre>wp oli-vig import --dry-run</pre>
				<p><?php esc_html_e( 'Import (idempotent; does not overwrite edited galleries):', 'oli-variation-gallery' ); ?></p>
				<pre>wp oli-vig import</pre>
				<p><?php esc_html_e( 'Overwrite existing non-empty galleries:', 'oli-variation-gallery' ); ?></p>
				<pre>wp oli-vig import --force</pre>
			</div>
		</div>
		<?php
	}

	/**
	 * Count variations that have importable source meta.
	 *
	 * @return array{found:int,imported:int,skipped:int}
	 */
	public function count_source_variations() {
		return $this->run_import( true );
	}

	/**
	 * Handle import form submission.
	 */
	public function handle_import() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'oli-variation-gallery' ) );
		}

		if ( ! isset( $_POST['oli_vig_import_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['oli_vig_import_nonce'] ) ), 'oli_vig_import' ) ) {
			wp_die( esc_html__( 'Invalid nonce.', 'oli-variation-gallery' ) );
		}

		$dry_run = ! empty( $_POST['dry_run'] );
		$force   = ! empty( $_POST['force'] );
		$result  = $this->run_import( $dry_run, $force );

		$redirect_url = add_query_arg(
			array(
				'page'           => 'oli-variation-gallery-importer',
				'import_status'  => $dry_run ? 'dry_run' : 'success',
				'imported_count' => $result['imported'],
				'found_count'    => $result['found'],
				'skipped_count'  => $result['skipped'],
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
	 * Reads rtwpvg_images (serialized attachment-ID arrays) and, when present,
	 * native `_product_image_gallery` on variations. Never deletes source meta.
	 * Already-matching destinations are skipped. Non-empty destinations are
	 * skipped unless $force is true.
	 *
	 * @param bool $dry_run Whether to run in dry-run mode.
	 * @param bool $force   Overwrite existing non-empty galleries.
	 * @return array{found:int,imported:int,skipped:int}
	 */
	public function run_import( $dry_run = false, $force = false ) {
		$found    = 0;
		$imported = 0;
		$skipped  = 0;
		$seen     = array();

		foreach ( $this->query_source_rows() as $row ) {
			$variation_id = isset( $row['post_id'] ) ? absint( $row['post_id'] ) : 0;
			$image_ids    = self::parse_gallery_meta_value( isset( $row['meta_value'] ) ? $row['meta_value'] : '' );

			if ( $variation_id <= 0 || empty( $image_ids ) || isset( $seen[ $variation_id ] ) ) {
				continue;
			}

			$seen[ $variation_id ] = true;
			++$found;

			$existing = Oli_VIG_Images::get_ids( $variation_id );
			if ( ! Oli_VIG_Images::should_import( $existing, $image_ids, $force ) ) {
				++$skipped;
				continue;
			}

			if ( ! $dry_run ) {
				Oli_VIG_Images::save_ids( $variation_id, $image_ids );
				++$imported;
			}
		}

		return array(
			'found'    => $found,
			'imported' => $imported,
			'skipped'  => $skipped,
		);
	}

	/**
	 * Fetch source gallery rows from postmeta.
	 *
	 * Prefer `rtwpvg_images`. Also include `_product_image_gallery` on
	 * product_variation posts that do not already have the legacy key.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function query_source_rows() {
		global $wpdb;

		if ( ! isset( $wpdb ) || ! is_object( $wpdb ) ) {
			return array();
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Bulk import of locally stored gallery meta.
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
			$legacy_ids[ absint( $row['post_id'] ) ] = true;
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
			if ( isset( $legacy_ids[ absint( $row['post_id'] ) ] ) ) {
				continue;
			}
			$combined[] = $row;
		}

		return $combined;
	}

	/**
	 * Register WP-CLI command.
	 */
	private function register_cli_command() {
		WP_CLI::add_command(
			'oli-vig import',
			array( $this, 'cli_import' ),
			array(
				'shortdesc' => 'Import variation gallery data from rtwpvg_images meta.',
				'synopsis'  => array(
					array(
						'type'        => 'flag',
						'name'        => 'dry-run',
						'description' => 'Preview import without making changes',
						'optional'    => true,
					),
					array(
						'type'        => 'flag',
						'name'        => 'force',
						'description' => 'Overwrite existing non-empty galleries',
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
		$force   = isset( $assoc_args['force'] );

		WP_CLI::log( 'Starting import from rtwpvg_images meta...' );

		$result = $this->run_import( $dry_run, $force );

		if ( $dry_run ) {
			WP_CLI::success(
				sprintf(
					'Dry run complete. Found %d variations with gallery data. %d already match and would be skipped.',
					$result['found'],
					$result['skipped']
				)
			);
			return;
		}

		WP_CLI::success(
			sprintf(
				'Import complete. Imported gallery data for %d variations. %d already matched and were skipped.',
				$result['imported'],
				$result['skipped']
			)
		);
	}
}
