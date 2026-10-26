<?php
/**
 * REST and Store API exposure.
 *
 * @package Oli_Variation_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST class.
 */
class Oli_VIG_Rest {

	/**
	 * Single instance.
	 *
	 * @var Oli_VIG_Rest|null
	 */
	private static $instance = null;

	/**
	 * Get instance.
	 *
	 * @return Oli_VIG_Rest
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
		add_action( 'init', array( $this, 'register_variation_meta' ) );
		add_filter( 'woocommerce_rest_prepare_product_variation_object', array( $this, 'prepare_variation' ), 10, 2 );
		add_filter( 'woocommerce_rest_product_variation_schema', array( $this, 'variation_schema' ) );
		add_action( 'woocommerce_blocks_loaded', array( $this, 'register_store_api' ) );
	}

	/**
	 * Register variation gallery post meta for the REST API.
	 */
	public function register_variation_meta() {
		register_post_meta(
			'product_variation',
			Oli_VIG_Images::META_KEY,
			array(
				'type'              => 'array',
				'description'       => __( 'Additional gallery image IDs for this variation.', 'oli-variation-gallery' ),
				'single'            => true,
				'sanitize_callback' => array( 'Oli_VIG_Images', 'sanitize_ids' ),
				'auth_callback'     => array( $this, 'meta_auth_callback' ),
				'show_in_rest'      => array(
					'schema' => self::ids_schema(),
				),
			)
		);
	}

	/**
	 * Auth callback for variation gallery meta writes.
	 *
	 * @param bool   $allowed   Whether the user can edit the meta.
	 * @param string $meta_key  Meta key.
	 * @param int    $object_id Object ID.
	 * @return bool
	 */
	public function meta_auth_callback( $allowed, $meta_key, $object_id ) {
		unset( $allowed, $meta_key );

		$object_id = absint( $object_id );
		if ( $object_id <= 0 ) {
			return current_user_can( 'edit_products' );
		}

		if ( current_user_can( 'edit_post', $object_id ) ) {
			return true;
		}

		$parent_id = wp_get_post_parent_id( $object_id );
		return $parent_id && current_user_can( 'edit_post', $parent_id );
	}

	/**
	 * Add gallery IDs to the wc/v3 variation response.
	 *
	 * @param WP_REST_Response $response Response.
	 * @param WC_Product       $variation Variation product.
	 * @return WP_REST_Response
	 */
	public function prepare_variation( $response, $variation ) {
		if ( ! $response instanceof WP_REST_Response ) {
			return $response;
		}

		$data = $response->get_data();
		if ( ! is_array( $data ) ) {
			$data = array();
		}

		$variation_id = 0;
		if ( is_object( $variation ) && method_exists( $variation, 'get_id' ) ) {
			$variation_id = absint( $variation->get_id() );
		}

		$data['oli_vig_gallery_ids'] = Oli_VIG_Images::get_ids( $variation_id );
		$response->set_data( $data );

		return $response;
	}

	/**
	 * Document the variation gallery field on the REST schema.
	 *
	 * @param array $schema Variation schema.
	 * @return array
	 */
	public function variation_schema( $schema ) {
		if ( ! is_array( $schema ) ) {
			$schema = array();
		}

		$schema['oli_vig_gallery_ids']             = self::ids_schema();
		$schema['oli_vig_gallery_ids']['context']  = array( 'view', 'edit' );
		$schema['oli_vig_gallery_ids']['readonly'] = true;

		return $schema;
	}

	/**
	 * Register Store API extension data.
	 */
	public function register_store_api() {
		if ( ! function_exists( 'woocommerce_store_api_register_endpoint_data' ) ) {
			return;
		}

		$endpoint = 'product';
		if ( class_exists( '\Automattic\WooCommerce\StoreApi\Schemas\V1\ProductSchema' ) ) {
			$endpoint = \Automattic\WooCommerce\StoreApi\Schemas\V1\ProductSchema::IDENTIFIER;
		}

		woocommerce_store_api_register_endpoint_data(
			array(
				'endpoint'        => $endpoint,
				'namespace'       => 'oli-variation-gallery',
				'data_callback'   => array( $this, 'store_api_data' ),
				'schema_callback' => array( $this, 'store_api_schema' ),
				'schema_type'     => ARRAY_A,
			)
		);
	}

	/**
	 * Store API product data.
	 *
	 * @param WC_Product $product Product.
	 * @return array<string,mixed>
	 */
	public function store_api_data( $product ) {
		if ( ! is_object( $product ) || ! method_exists( $product, 'get_id' ) ) {
			return self::empty_store_payload();
		}

		if ( method_exists( $product, 'is_type' ) && $product->is_type( 'variation' ) ) {
			return array(
				'gallery_ids'           => Oli_VIG_Images::get_ids( $product->get_id() ),
				'variation_gallery_ids' => array(),
			);
		}

		$map = array();
		if ( method_exists( $product, 'is_type' ) && $product->is_type( 'variable' ) && method_exists( $product, 'get_children' ) ) {
			foreach ( (array) $product->get_children() as $variation_id ) {
				$variation_id = absint( $variation_id );
				$ids          = Oli_VIG_Images::get_ids( $variation_id );
				if ( ! empty( $ids ) ) {
					$map[ (string) $variation_id ] = $ids;
				}
			}
		}

		return array(
			'gallery_ids'           => array(),
			'variation_gallery_ids' => $map,
		);
	}

	/**
	 * Store API schema.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function store_api_schema() {
		return array(
			'gallery_ids'           => self::ids_schema(),
			'variation_gallery_ids' => array(
				'description'          => __( 'Map of variation ID to gallery attachment IDs.', 'oli-variation-gallery' ),
				'type'                 => 'object',
				'context'              => array( 'view', 'edit' ),
				'readonly'             => true,
				'additionalProperties' => self::ids_schema(),
			),
		);
	}

	/**
	 * Shared integer-ID list schema.
	 *
	 * @return array<string,mixed>
	 */
	public static function ids_schema() {
		return array(
			'description' => __( 'Gallery attachment IDs.', 'oli-variation-gallery' ),
			'type'        => 'array',
			'items'       => array(
				'type' => 'integer',
			),
			'context'     => array( 'view', 'edit' ),
			'readonly'    => true,
		);
	}

	/**
	 * Empty Store API payload.
	 *
	 * @return array<string,mixed>
	 */
	public static function empty_store_payload() {
		return array(
			'gallery_ids'           => array(),
			'variation_gallery_ids' => array(),
		);
	}
}
