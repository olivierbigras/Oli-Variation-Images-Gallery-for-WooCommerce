<?php
/**
 * Unit tests for rtwpvg_images gallery meta parsing.
 *
 * These tests do not require WordPress. They replay the public meta format:
 * an array of attachment IDs stored under rtwpvg_images (serialized in
 * postmeta as a:3:{i:0;i:101;i:1;i:202;i:2;i:303;}).
 *
 * @package Oli_Variation_Gallery
 */

require_once __DIR__ . '/bootstrap.php';
require_once dirname( __DIR__ ) . '/includes/class-oli-vig-images.php';
require_once dirname( __DIR__ ) . '/includes/class-oli-vig-importer.php';

$ids = array( 101, 202, 303 );

$serialized = serialize( $ids );
oli_vig_test_assert(
	'a:3:{i:0;i:101;i:1;i:202;i:2;i:303;}' === $serialized,
	'fixture matches serialized rtwpvg_images value'
);
oli_vig_test_assert_same(
	$ids,
	Oli_VIG_Importer::parse_gallery_meta_value( $serialized ),
	'parse serialized rtwpvg_images array of attachment IDs'
);

oli_vig_test_assert_same(
	$ids,
	Oli_VIG_Importer::parse_gallery_meta_value( $ids ),
	'parse unserialized rtwpvg_images array'
);

oli_vig_test_assert_same(
	$ids,
	Oli_VIG_Importer::parse_gallery_meta_value( '101,202,303' ),
	'parse comma-separated _product_image_gallery IDs'
);

oli_vig_test_assert_same(
	array( 101 ),
	Oli_VIG_Importer::parse_gallery_meta_value( '101' ),
	'parse single comma-list ID'
);

oli_vig_test_assert_same(
	$ids,
	Oli_VIG_Importer::parse_gallery_meta_value( '101, 202, 303' ),
	'parse comma-separated IDs with spaces'
);

oli_vig_test_assert_same(
	$ids,
	Oli_VIG_Importer::parse_gallery_meta_value( '[101,202,303]' ),
	'parse JSON array of IDs'
);

oli_vig_test_assert_same( array(), Oli_VIG_Importer::parse_gallery_meta_value( '' ), 'skip empty string' );
oli_vig_test_assert_same( array(), Oli_VIG_Importer::parse_gallery_meta_value( 'a:0:{}' ), 'skip empty serialized array' );
oli_vig_test_assert_same( array(), Oli_VIG_Importer::parse_gallery_meta_value( array() ), 'skip empty array' );
oli_vig_test_assert_same( array(), Oli_VIG_Importer::parse_gallery_meta_value( 'not-an-array' ), 'skip non-numeric string' );
oli_vig_test_assert_same( array(), Oli_VIG_Importer::parse_gallery_meta_value( null ), 'skip null' );

oli_vig_test_assert_same(
	array( 10, 20, 5 ),
	Oli_VIG_Importer::parse_gallery_meta_value( array( 10, 0, 20, 10, -5 ) ),
	'normalize unique positive IDs'
);

oli_vig_test_assert(
	'rtwpvg_images' === Oli_VIG_Importer::RTWPVG_LEGACY_META_KEY,
	'legacy meta key is rtwpvg_images'
);
oli_vig_test_assert(
	'_product_image_gallery' === Oli_VIG_Importer::WC_GALLERY_META_KEY,
	'native meta key is _product_image_gallery'
);

oli_vig_test_assert(
	Oli_VIG_Images::should_import( array(), array( 101, 202 ) ),
	'import when destination is empty'
);
oli_vig_test_assert(
	! Oli_VIG_Images::should_import( array( 101, 202 ), array( 101, 202 ) ),
	'idempotent skip when IDs already match'
);
oli_vig_test_assert(
	! Oli_VIG_Images::should_import( array( 101 ), array( 101, 202 ) ),
	'do not overwrite a non-empty destination without force'
);
oli_vig_test_assert(
	Oli_VIG_Images::should_import( array( 101 ), array( 101, 202 ), true ),
	'overwrite a non-empty destination with force'
);
oli_vig_test_assert(
	! Oli_VIG_Images::should_import( array( 5 ), array() ),
	'do not import empty source'
);

oli_vig_test_assert_same(
	'matched',
	Oli_VIG_Images::import_skip_reason( array( 101, 202 ), array( 101, 202 ) ),
	'skip reason is matched when IDs are identical'
);
oli_vig_test_assert_same(
	'existing',
	Oli_VIG_Images::import_skip_reason( array( 101 ), array( 101, 202 ) ),
	'skip reason is existing when a non-empty gallery would be overwritten'
);
oli_vig_test_assert_same(
	'',
	Oli_VIG_Images::import_skip_reason( array( 101 ), array( 101, 202 ), true ),
	'no skip reason when force overwrites an existing gallery'
);
oli_vig_test_assert_same(
	'',
	Oli_VIG_Images::import_skip_reason( array(), array( 101, 202 ) ),
	'no skip reason when the destination is empty'
);

echo 'All importer tests passed.' . PHP_EOL;
exit( 0 );
