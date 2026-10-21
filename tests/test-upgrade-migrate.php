<?php
/**
 * Unit tests for prefixed option migration.
 *
 * @package Oli_Variation_Images_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

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

/**
 * Assert helper.
 *
 * @param bool   $condition Condition.
 * @param string $message   Message.
 */
function oli_vig_upgrade_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, 'FAIL: ' . $message . PHP_EOL );
		exit( 1 );
	}
	echo 'PASS: ' . $message . PHP_EOL;
}

$GLOBALS['oli_vig_test_options'] = array(
	'legacy_vig_settings' => array( 'enable_zoom' => 'no' ),
);

oli_vig_migrate_legacy_options(
	array(
		'legacy_vig_settings' => 'oli_vig_settings',
	)
);

oli_vig_upgrade_assert(
	isset( $GLOBALS['oli_vig_test_options']['oli_vig_settings'] )
	&& 'no' === $GLOBALS['oli_vig_test_options']['oli_vig_settings']['enable_zoom'],
	'migration copies leftover options to the prefixed key'
);

oli_vig_upgrade_assert(
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

oli_vig_upgrade_assert(
	'yes' === $GLOBALS['oli_vig_test_options']['oli_vig_settings']['enable_zoom'],
	'migration does not overwrite an existing prefixed option'
);

oli_vig_upgrade_assert(
	! array_key_exists( 'legacy_vig_settings', $GLOBALS['oli_vig_test_options'] ),
	'migration still deletes the leftover option when the prefixed key already exists'
);

echo 'All leftover-option migration tests passed.' . PHP_EOL;

oli_vig_upgrade_assert( true === oli_vig_to_bool( 'yes' ), 'oli_vig_to_bool maps yes to true' );
oli_vig_upgrade_assert( false === oli_vig_to_bool( 'no' ), 'oli_vig_to_bool maps no to false' );
oli_vig_upgrade_assert( true === oli_vig_to_bool( true ), 'oli_vig_to_bool keeps true' );
oli_vig_upgrade_assert( false === oli_vig_to_bool( false ), 'oli_vig_to_bool keeps false' );
oli_vig_upgrade_assert( true === oli_vig_to_bool( '1' ), 'oli_vig_to_bool maps 1 to true' );
oli_vig_upgrade_assert( false === oli_vig_to_bool( '0' ), 'oli_vig_to_bool maps 0 to false' );
oli_vig_upgrade_assert(
	array( 'enable_zoom', 'enable_lightbox', 'preload_images', 'reset_on_clear' ) === oli_vig_boolean_setting_keys(),
	'boolean setting keys stay inside oli_vig_settings'
);

$GLOBALS['oli_vig_test_options'] = array(
	'oli_vig_settings' => array(
		'thumbnail_position' => 'bottom',
		'enable_zoom'        => 'yes',
		'enable_lightbox'    => 'no',
		'preload_images'     => 'yes',
		'reset_on_clear'     => 'no',
	),
);

oli_vig_migrate_boolean_settings();

$migrated = $GLOBALS['oli_vig_test_options']['oli_vig_settings'];
oli_vig_upgrade_assert( true === $migrated['enable_zoom'], 'yes becomes boolean true' );
oli_vig_upgrade_assert( false === $migrated['enable_lightbox'], 'no becomes boolean false' );
oli_vig_upgrade_assert( true === $migrated['preload_images'], 'preload yes becomes true' );
oli_vig_upgrade_assert( false === $migrated['reset_on_clear'], 'reset no becomes false' );
oli_vig_upgrade_assert( 'bottom' === $migrated['thumbnail_position'], 'non-boolean keys are unchanged' );

$GLOBALS['oli_vig_test_options'] = array(
	'oli_vig_settings' => array(
		'enable_zoom' => true,
	),
);
oli_vig_migrate_boolean_settings();
oli_vig_upgrade_assert(
	true === $GLOBALS['oli_vig_test_options']['oli_vig_settings']['enable_zoom'],
	'already-boolean values are left as booleans'
);

echo 'All boolean migration tests passed.' . PHP_EOL;
exit( 0 );
