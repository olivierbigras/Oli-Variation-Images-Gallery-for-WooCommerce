<?php
/**
 * Unit tests for settings sanitization.
 *
 * @package Oli_Variation_Gallery
 */

require_once __DIR__ . '/bootstrap.php';

if ( ! function_exists( 'wp_parse_args' ) ) {
	/**
	 * Stub wp_parse_args.
	 *
	 * @param array $args     Args.
	 * @param array $defaults Defaults.
	 * @return array
	 */
	function wp_parse_args( $args, $defaults ) {
		return array_merge( $defaults, $args );
	}
}

require_once dirname( __DIR__ ) . '/includes/class-oli-vig-main.php';
require_once dirname( __DIR__ ) . '/includes/class-oli-vig-settings.php';

$defaults = Oli_VIG_Main::get_default_settings();
oli_vig_test_assert( isset( $defaults['thumbnail_position'] ), 'defaults include thumbnail_position' );
oli_vig_test_assert( 4 === $defaults['thumbnail_columns'], 'default columns is 4' );

$clean = Oli_VIG_Settings::sanitize(
	array(
		'thumbnail_position' => 'left',
		'thumbnail_columns'  => 9,
		'enable_zoom'        => 'yes',
		'enable_lightbox'    => '',
		'gallery_width'      => 10,
		'preload_images'     => '1',
		'reset_on_clear'     => 'yes',
	)
);

oli_vig_test_assert_same( 'left', $clean['thumbnail_position'], 'accept left thumbnail position' );
oli_vig_test_assert_same( 8, $clean['thumbnail_columns'], 'cap columns at 8' );
oli_vig_test_assert_same( 'yes', $clean['enable_zoom'], 'keep zoom enabled' );
oli_vig_test_assert_same( 'no', $clean['enable_lightbox'], 'empty lightbox becomes no' );
oli_vig_test_assert_same( 50, $clean['gallery_width'], 'floor gallery width at 50' );
oli_vig_test_assert_same( 'yes', $clean['preload_images'], 'preload stays yes' );
oli_vig_test_assert_same( 'yes', $clean['reset_on_clear'], 'reset stays yes' );

$invalid = Oli_VIG_Settings::sanitize(
	array(
		'thumbnail_position' => 'top',
		'thumbnail_columns'  => 0,
		'gallery_width'      => 200,
	)
);

oli_vig_test_assert_same( 'bottom', $invalid['thumbnail_position'], 'invalid position falls back' );
oli_vig_test_assert_same( 1, $invalid['thumbnail_columns'], 'columns floor at 1' );
oli_vig_test_assert_same( 100, $invalid['gallery_width'], 'width cap at 100' );
oli_vig_test_assert_same( 'no', $invalid['enable_zoom'], 'missing checkbox is no' );

$empty = Oli_VIG_Settings::sanitize( null );
oli_vig_test_assert_same( 'bottom', $empty['thumbnail_position'], 'null input uses default position' );

echo 'All settings tests passed.' . PHP_EOL;
exit( 0 );
