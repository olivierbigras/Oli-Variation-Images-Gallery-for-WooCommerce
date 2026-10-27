<?php
/**
 * Run all standalone plugin tests.
 *
 * @package Oli_Variation_Gallery
 */

$tests = array(
	__DIR__ . '/test-importer.php',
	__DIR__ . '/test-upgrade-migrate.php',
	__DIR__ . '/test-images.php',
	__DIR__ . '/test-settings.php',
	__DIR__ . '/test-rest.php',
);

foreach ( $tests as $test ) {
	$command = escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( $test );
	passthru( $command, $code );
	if ( 0 !== (int) $code ) {
		fwrite( STDERR, 'Test failed: ' . $test . PHP_EOL );
		exit( $code );
	}
}

echo 'All test files passed.' . PHP_EOL;
exit( 0 );
