<?php
/**
 * Unit tests for gallery ID helpers.
 *
 * @package Oli_Variation_Gallery
 */

require_once __DIR__ . '/bootstrap.php';
require_once dirname( __DIR__ ) . '/includes/class-oli-vig-images.php';

oli_vig_test_assert_same(
	array( 10, 20, 5 ),
	Oli_VIG_Images::sanitize_ids( array( 10, 0, '20', 10, -5, array( 1 ) ) ),
	'sanitize unique positive attachment IDs'
);

oli_vig_test_assert_same(
	array(),
	Oli_VIG_Images::sanitize_ids( 'nope' ),
	'sanitize non-array string to empty'
);

oli_vig_test_assert_same(
	array(),
	Oli_VIG_Images::compose_gallery_ids( 99, array() ),
	'empty extra images fall back to the main gallery'
);

oli_vig_test_assert_same(
	array( 99, 10, 20 ),
	Oli_VIG_Images::compose_gallery_ids( 99, array( 10, 20 ) ),
	'prepend featured image when it is not already in the list'
);

oli_vig_test_assert_same(
	array( 10, 20 ),
	Oli_VIG_Images::compose_gallery_ids( 10, array( 10, 20 ) ),
	'do not duplicate the featured image'
);

oli_vig_test_assert_same(
	array( 10, 20 ),
	Oli_VIG_Images::compose_gallery_ids( 0, array( 10, 20 ) ),
	'keep extra images when there is no featured image'
);

oli_vig_test_assert(
	'_oli_vig_gallery_images' === Oli_VIG_Images::META_KEY,
	'meta key is prefixed'
);

echo 'All image helper tests passed.' . PHP_EOL;
exit( 0 );
