<?php
/**
 * Unit tests for REST / Store API schema helpers.
 *
 * @package Oli_Variation_Gallery
 */

require_once __DIR__ . '/bootstrap.php';
require_once dirname( __DIR__ ) . '/includes/class-oli-vig-images.php';
require_once dirname( __DIR__ ) . '/includes/class-oli-vig-rest.php';

$schema = Oli_VIG_Rest::ids_schema();
oli_vig_test_assert( 'array' === $schema['type'], 'IDs schema type is array' );
oli_vig_test_assert( isset( $schema['items']['type'] ) && 'integer' === $schema['items']['type'], 'IDs schema items are integers' );

$empty = Oli_VIG_Rest::empty_store_payload();
oli_vig_test_assert_same( array(), $empty['gallery_ids'], 'empty payload gallery_ids' );
oli_vig_test_assert_same( array(), $empty['variation_gallery_ids'], 'empty payload variation_gallery_ids' );

echo 'All REST schema tests passed.' . PHP_EOL;
exit( 0 );
