<?php
/**
 * Shared test helpers (no WordPress required).
 *
 * @package Oli_Variation_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

if ( ! defined( 'OLI_VIG_VERSION' ) ) {
	define( 'OLI_VIG_VERSION', '1.0.1' );
}

if ( ! function_exists( 'absint' ) ) {
	/**
	 * Stub absint.
	 *
	 * @param mixed $value Value.
	 * @return int
	 */
	function absint( $value ) {
		return abs( (int) $value );
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	/**
	 * Stub sanitize_text_field.
	 *
	 * @param string $value Value.
	 * @return string
	 */
	function sanitize_text_field( $value ) {
		return trim( (string) $value );
	}
}

if ( ! function_exists( '__' ) ) {
	/**
	 * Stub translation.
	 *
	 * @param string $text   Text.
	 * @param string $domain Text domain.
	 * @return string
	 */
	function __( $text, $domain = '' ) {
		unset( $domain );
		return $text;
	}
}

/**
 * Assert a condition.
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
 * Assert two values are identical.
 *
 * @param mixed  $expected Expected.
 * @param mixed  $actual   Actual.
 * @param string $message  Message.
 */
function oli_vig_test_assert_same( $expected, $actual, $message ) {
	$ok = $expected === $actual;
	$detail = $message . ' (expected ' . oli_vig_test_export( $expected ) . ', got ' . oli_vig_test_export( $actual ) . ')';
	oli_vig_test_assert( $ok, $detail );
}

/**
 * Export a value for assertion messages.
 *
 * @param mixed $value Value.
 * @return string
 */
function oli_vig_test_export( $value ) {
	$encoded = function_exists( 'wp_json_encode' ) ? wp_json_encode( $value ) : json_encode( $value );
	return false === $encoded ? 'null' : $encoded;
}
