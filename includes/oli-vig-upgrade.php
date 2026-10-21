<?php
/**
 * Versioned option upgrades.
 *
 * @package Oli_Variation_Images_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Legacy option key map (old => prefixed).
 *
 * Early drafts never shipped public unprefixed options. The map stays so a
 * future rename can copy values to `oli_vig_*` keys and delete the old ones
 * without data loss.
 *
 * @return array<string,string>
 */
function oli_vig_legacy_option_map() {
	return array();
}

/**
 * Setting keys stored as real booleans.
 *
 * @return string[]
 */
function oli_vig_boolean_setting_keys() {
	return array(
		'enable_zoom',
		'enable_lightbox',
		'preload_images',
		'reset_on_clear',
	);
}

/**
 * Convert a stored setting value to a real boolean.
 *
 * Accepts the legacy yes/no strings plus common truthy/falsey forms.
 *
 * @param mixed $value Raw value.
 * @return bool
 */
function oli_vig_to_bool( $value ) {
	if ( is_bool( $value ) ) {
		return $value;
	}

	if ( is_int( $value ) || is_float( $value ) ) {
		return 0 !== (int) $value;
	}

	if ( ! is_string( $value ) ) {
		return ! empty( $value );
	}

	$value = strtolower( trim( $value ) );

	return in_array( $value, array( '1', 'yes', 'true', 'on' ), true );
}

/**
 * Copy leftover unprefixed options to oli_vig_* keys, then delete the old keys.
 *
 * @param array<string,string>|null $map Optional map override (for tests).
 */
function oli_vig_migrate_legacy_options( $map = null ) {
	if ( ! is_array( $map ) ) {
		$map = oli_vig_legacy_option_map();
	}

	foreach ( $map as $old_key => $new_key ) {
		if ( $old_key === $new_key ) {
			continue;
		}

		$old = get_option( $old_key, null );
		if ( false === $old || null === $old ) {
			continue;
		}

		$new = get_option( $new_key, null );
		if ( false === $new || null === $new || ( is_array( $new ) && array() === $new ) ) {
			update_option( $new_key, $old );
		}

		delete_option( $old_key );
	}
}

/**
 * Convert yes/no boolean settings in oli_vig_settings to real booleans.
 *
 * The option key stays `oli_vig_settings`. Only the stored value type changes.
 */
function oli_vig_migrate_boolean_settings() {
	$settings = get_option( 'oli_vig_settings', null );
	if ( ! is_array( $settings ) ) {
		return;
	}

	$changed = false;

	foreach ( oli_vig_boolean_setting_keys() as $key ) {
		if ( ! array_key_exists( $key, $settings ) ) {
			continue;
		}

		if ( is_bool( $settings[ $key ] ) ) {
			continue;
		}

		$settings[ $key ] = oli_vig_to_bool( $settings[ $key ] );
		$changed          = true;
	}

	if ( $changed ) {
		update_option( 'oli_vig_settings', $settings );
	}
}

/**
 * Run versioned upgrades once per installed version.
 */
function oli_vig_maybe_upgrade() {
	$stored = get_option( 'oli_vig_version', '0' );
	if ( version_compare( (string) $stored, OLI_VIG_VERSION, '>=' ) ) {
		return;
	}

	oli_vig_migrate_legacy_options();
	oli_vig_migrate_boolean_settings();
	update_option( 'oli_vig_version', OLI_VIG_VERSION );
}
