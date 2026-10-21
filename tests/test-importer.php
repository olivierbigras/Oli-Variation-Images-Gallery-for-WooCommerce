<?php
/**
 * Unit tests for RadiusTheme gallery meta parsing.
 *
 * These tests do not require WordPress. They replay the exact value format
 * stored by woo-product-variation-gallery (RadiusTheme).
 *
 * Source (woo-product-variation-gallery app/Controllers/Hooks.php):
 *   $rtwpvg_ids = array_map( 'absint', (array) $_POST['rtwpvg'][ $variation_id ] );
 *   $rtwpvg_ids = array_values( array_unique( array_filter( $rtwpvg_ids ) ) );
 *   update_post_meta( $variation_id, 'rtwpvg_images', $rtwpvg_ids );
 *
 * WordPress serializes that array in postmeta. Raw DB value example:
 *   a:3:{i:0;i:101;i:1;i:202;i:2;i:303;}
 *
 * On WooCommerce 11.1+ RadiusTheme also copies IDs into
 * `_product_image_gallery` as a comma-separated list: "101,202,303".
 *
 * @package Oli_Variation_Images_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

require_once dirname( __DIR__ ) . '/includes/class-oli-vig-importer.php';

/**
 * Assert helper.
 *
 * @param bool   $condition Condition.
 * @param string $message   Message.
 */
function oli_vig_test_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, 'FAIL: ' . $message . PHP_EOL );
		exit( 1 );
	}
	echo 'PASS: ' . $message . PHP_EOL;
}

/**
 * Assert two arrays of IDs match.
 *
 * @param array  $expected Expected IDs.
 * @param array  $actual   Actual IDs.
 * @param string $message  Message.
 */
function oli_vig_test_assert_ids( $expected, $actual, $message ) {
	oli_vig_test_assert( $expected === $actual, $message . ' (expected ' . wp_json_encode_safe( $expected ) . ', got ' . wp_json_encode_safe( $actual ) . ')' );
}

/**
 * JSON encode without requiring WordPress.
 *
 * @param mixed $value Value.
 * @return string
 */
function wp_json_encode_safe( $value ) {
	$encoded = json_encode( $value );
	return false === $encoded ? 'null' : $encoded;
}

$ids = array( 101, 202, 303 );

// Real RadiusTheme format: PHP-serialized array of attachment IDs (what sits in wp_postmeta).
$serialized = serialize( $ids );
oli_vig_test_assert(
	'a:3:{i:0;i:101;i:1;i:202;i:2;i:303;}' === $serialized,
	'fixture matches RadiusTheme serialized rtwpvg_images value'
);
oli_vig_test_assert_ids(
	$ids,
	Oli_VIG_Importer::parse_gallery_meta_value( $serialized ),
	'parse serialized rtwpvg_images array of attachment IDs'
);

// Already-unserialized array (get_post_meta( ..., true ) return value).
oli_vig_test_assert_ids(
	$ids,
	Oli_VIG_Importer::parse_gallery_meta_value( $ids ),
	'parse unserialized rtwpvg_images array'
);

// WooCommerce native / RadiusTheme-migrated _product_image_gallery.
oli_vig_test_assert_ids(
	$ids,
	Oli_VIG_Importer::parse_gallery_meta_value( '101,202,303' ),
	'parse comma-separated _product_image_gallery IDs'
);

oli_vig_test_assert_ids(
	array( 101 ),
	Oli_VIG_Importer::parse_gallery_meta_value( '101' ),
	'parse single comma-list ID'
);

oli_vig_test_assert_ids(
	$ids,
	Oli_VIG_Importer::parse_gallery_meta_value( '101, 202, 303' ),
	'parse comma-separated IDs with spaces'
);

// JSON array (defensive).
oli_vig_test_assert_ids(
	$ids,
	Oli_VIG_Importer::parse_gallery_meta_value( '[101,202,303]' ),
	'parse JSON array of IDs'
);

// Empty / invalid.
oli_vig_test_assert_ids(
	array(),
	Oli_VIG_Importer::parse_gallery_meta_value( '' ),
	'skip empty string'
);
oli_vig_test_assert_ids(
	array(),
	Oli_VIG_Importer::parse_gallery_meta_value( 'a:0:{}' ),
	'skip empty serialized array'
);
oli_vig_test_assert_ids(
	array(),
	Oli_VIG_Importer::parse_gallery_meta_value( array() ),
	'skip empty array'
);
oli_vig_test_assert_ids(
	array(),
	Oli_VIG_Importer::parse_gallery_meta_value( 'not-an-array' ),
	'skip non-numeric string'
);
oli_vig_test_assert_ids(
	array(),
	Oli_VIG_Importer::parse_gallery_meta_value( null ),
	'skip null'
);

// Duplicates and zeroes stripped.
oli_vig_test_assert_ids(
	array( 10, 20, 5 ),
	Oli_VIG_Importer::parse_gallery_meta_value( array( 10, 0, 20, 10, -5 ) ),
	'normalize unique positive IDs (0 dropped, negatives absint, duplicates unique)'
);

// Meta key constants match the real plugin.
oli_vig_test_assert(
	'rtwpvg_images' === Oli_VIG_Importer::RTWPVG_LEGACY_META_KEY,
	'legacy meta key is rtwpvg_images'
);
oli_vig_test_assert(
	'_product_image_gallery' === Oli_VIG_Importer::WC_GALLERY_META_KEY,
	'native meta key is _product_image_gallery'
);

echo 'All importer tests passed.' . PHP_EOL;

require_once dirname( __DIR__ ) . '/includes/oli-vig-upgrade.php';

oli_vig_test_assert(
	array() === oli_vig_legacy_option_map(),
	'legacy option map is an array (empty until a prefix rename exists)'
);

echo 'All upgrade tests passed.' . PHP_EOL;
exit( 0 );
