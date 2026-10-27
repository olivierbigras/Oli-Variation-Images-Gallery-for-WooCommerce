<?php
/**
 * Unit tests for prefixed option migration.
 *
 * @package Oli_Variation_Gallery
 */

require_once __DIR__ . '/bootstrap.php';

$GLOBALS['oli_vig_test_options'] = array();

if ( ! function_exists( 'get_option' ) ) {
	/**
	 * Stub get_option.
	 *
	 * @param string $key     Option key.
	 * @param mixed  $default Default.
	 * @return mixed
	 */
	function get_option( $key, $default = false ) {
		if ( array_key_exists( $key, $GLOBALS['oli_vig_test_options'] ) ) {
			return $GLOBALS['oli_vig_test_options'][ $key ];
		}
		return $default;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	/**
	 * Stub update_option.
	 *
	 * @param string $key   Option key.
	 * @param mixed  $value Value.
	 * @return bool
	 */
	function update_option( $key, $value ) {
		$GLOBALS['oli_vig_test_options'][ $key ] = $value;
		return true;
	}
}

if ( ! function_exists( 'delete_option' ) ) {
	/**
	 * Stub delete_option.
	 *
	 * @param string $key Option key.
	 * @return bool
	 */
	function delete_option( $key ) {
		unset( $GLOBALS['oli_vig_test_options'][ $key ] );
		return true;
	}
}

require_once dirname( __DIR__ ) . '/includes/oli-vig-upgrade.php';

$GLOBALS['oli_vig_test_options'] = array(
	'legacy_vig_settings' => array( 'enable_zoom' => 'no' ),
);

oli_vig_migrate_legacy_options(
	array(
		'legacy_vig_settings' => 'oli_vig_settings',
	)
);

oli_vig_test_assert(
	isset( $GLOBALS['oli_vig_test_options']['oli_vig_settings'] )
	&& 'no' === $GLOBALS['oli_vig_test_options']['oli_vig_settings']['enable_zoom'],
	'migration copies leftover options to the prefixed key'
);

oli_vig_test_assert(
	! array_key_exists( 'legacy_vig_settings', $GLOBALS['oli_vig_test_options'] ),
	'migration deletes the leftover option after copy'
);

$GLOBALS['oli_vig_test_options'] = array(
	'legacy_vig_settings' => array( 'enable_zoom' => 'no' ),
	'oli_vig_settings'    => array( 'enable_zoom' => 'yes' ),
);

oli_vig_migrate_legacy_options(
	array(
		'legacy_vig_settings' => 'oli_vig_settings',
	)
);

oli_vig_test_assert(
	'yes' === $GLOBALS['oli_vig_test_options']['oli_vig_settings']['enable_zoom'],
	'migration does not overwrite an existing prefixed option'
);

oli_vig_test_assert(
	! array_key_exists( 'legacy_vig_settings', $GLOBALS['oli_vig_test_options'] ),
	'migration still deletes the leftover option when the prefixed key already exists'
);

oli_vig_test_assert(
	array() === oli_vig_legacy_option_map(),
	'legacy option map is an array'
);

echo 'All upgrade migration tests passed.' . PHP_EOL;
exit( 0 );
