<?php
/**
 * Versioned option upgrades.
 *
 * @package Oli_Variation_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Legacy option key map (old => prefixed).
 *
 * @return array<string,string>
 */
function oli_vig_legacy_option_map() {
	return array();
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
 * Run versioned upgrades once per installed version.
 */
function oli_vig_maybe_upgrade() {
	$stored = get_option( 'oli_vig_version', '0' );
	if ( version_compare( (string) $stored, OLI_VIG_VERSION, '>=' ) ) {
		return;
	}

	oli_vig_migrate_legacy_options();
	update_option( 'oli_vig_version', OLI_VIG_VERSION );
}
