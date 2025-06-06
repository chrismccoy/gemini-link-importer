<?php
/**
 * PHPUnit bootstrap file for Gemini Link Importer.
 *
 * @package GeminiLinkImporter
 */

// Define a constant for the plugin's main file.
define( 'GEMINI_LINK_IMPORTER_MAIN_FILE', dirname( __DIR__ ) . '/gemini-link-importer.php' );

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find $_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require GEMINI_LINK_IMPORTER_MAIN_FILE;
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';

echo "Gemini Link Importer: WordPress testing environment bootstrapped." . PHP_EOL;
echo "Plugin main file: " . GEMINI_LINK_IMPORTER_MAIN_FILE . PHP_EOL;
