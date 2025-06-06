<?php
/**
 * Class SampleTest
 *
 * @package GeminiLinkImporter
 */

use WP_UnitTestCase;

/**
 * Sample test case.
 */
class SampleTest extends WP_UnitTestCase {

	/**
	 * A single example test.
	 */
	public function test_sample() {
		$this->assertTrue( true );
	}

	public function test_wordpress_is_loaded() {
		$this->assertTrue( function_exists( 'get_option' ) );
	}
}
