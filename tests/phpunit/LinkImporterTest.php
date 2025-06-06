<?php
/**
 * Class LinkImporterTest
 *
 * @package GeminiLinkImporter
 */

use WP_UnitTestCase;
use GeminiLinkImporter\Link_Importer;
use WP_Error;

/**
 * Test case for the Link_Importer class.
 */
class LinkImporterTest extends WP_UnitTestCase {

	protected $link_importer;
	protected static $admin_user_id;
	protected static $subscriber_user_id;

	public static function wpSetUpBeforeClass( $factory ) {
		self::$admin_user_id = $factory->user->create(
			array(
				'role'       => 'administrator',
				'user_login' => 'test_admin_importer',
			)
		);
		$user = new WP_User( self::$admin_user_id );
		$user->add_cap( 'manage_links' );

		self::$subscriber_user_id = $factory->user->create(
			array(
				'role'       => 'subscriber',
				'user_login' => 'test_subscriber_importer',
			)
		);
	}

	public static function wpTearDownAfterClass() {
		self::delete_user( self::$admin_user_id );
		self::delete_user( self::$subscriber_user_id );
	}

	public function setUp(): void {
		parent::setUp();
		update_option( 'link_manager_enabled', 1 );
		$this->link_importer = new Link_Importer();
		wp_set_current_user( self::$admin_user_id );
		$_POST = array();
		$_GET  = array();
	}

	public function tearDown(): void {
		delete_option( 'link_manager_enabled' );
		wp_set_current_user( 0 );
		remove_all_filters('pre_insert_term');
		remove_all_filters('pre_insert_link');
		parent::tearDown();
	}

	private function simulate_ajax_call( array $post_data ): ?array {
		$_POST = $post_data;
		if ( ! isset( $_POST['nonce'] ) ) {
			$_POST['nonce'] = wp_create_nonce( 'gemini_link_importer_nonce' );
		}

		$output = null;
		try {
			ob_start();
			$this->link_importer->handle_import_links_ajax();
			$output = ob_get_clean();
		} catch ( \WPDieException $e ) {
			$output = $e->getMessage();
		} finally {
			if ( ob_get_level() > 0 ) {
				ob_end_clean();
			}
		}

		if ( $output === null ) {
			return null;
		}

		return json_decode( $output, true );
	}

	public function test_class_instantiation() {
		$this->assertInstanceOf( Link_Importer::class, $this->link_importer );
	}

	public function test_ajax_hook_is_added() {
		$this->assertGreaterThan(
			0,
			has_action( 'wp_ajax_gemini_import_links', array( $this->link_importer, 'handle_import_links_ajax' ) )
		);
	}

	public function test_handle_import_links_ajax_no_permission() {
		wp_set_current_user( self::$subscriber_user_id );
		$response = $this->simulate_ajax_call( array( 'links' => 'https://example.com' ) );
		$this->assertFalse( $response['success'] );
		$this->assertEquals( 'You do not have permission to import links.', $response['data']['message'] );
	}

	// --- Advanced Tests: Simulating Core Function Failures ---
	public function test_import_when_category_creation_fails() {
		$category_name = 'Failing Category';
		$error_message = 'Simulated term creation failure.';

		add_filter(
			'pre_insert_term',
			function ( $term, $taxonomy ) use ( $category_name, $error_message ) {
				if ( $taxonomy === 'link_category' && $term === $category_name ) {
					return new WP_Error( 'term_creation_failed', $error_message );
				}
				return $term;
			},
			10,
			2
		);

		$links_input = "https://linkforfailingcat.com,Link Title,{$category_name}";
		$response    = $this->simulate_ajax_call( array( 'links' => $links_input ) );

		$this->assertTrue( $response['success'] );
		$this->assertCount( 1, $response['data']['failed'] );
		$failed_item = $response['data']['failed'][0];
		$expected_reason = sprintf( 'Error creating category "%1$s": %2$s', $category_name, $error_message );
		$this->assertEquals( $expected_reason, $failed_item['reason'] );
	}

	public function test_import_when_link_insertion_fails_with_wp_error() {
		add_filter(
			'pre_insert_link',
			function ( $value, $linkdata, $wp_error_on_failure ) {
				if ( isset($linkdata['link_url']) && $linkdata['link_url'] === 'https://forcewperror.com' ) {
					return new WP_Error( 'link_insert_simulated_error', 'Simulated WP_Error during link insertion.' );
				}
				return $value;
			},
			10,
			3
		);

		$links_input = "https://forcewperror.com,A Title,A Category";
		$response    = $this->simulate_ajax_call( array( 'links' => $links_input ) );

		$this->assertTrue( $response['success'] );
		$this->assertCount( 1, $response['data']['failed'] );
		$failed_item = $response['data']['failed'][0];
		$expected_reason = 'Error inserting link: Simulated WP_Error during link insertion.';
		$this->assertEquals( $expected_reason, $failed_item['reason'] );
	}

	// --- Keep all other tests from the previous comprehensive test file ---
	public function test_handle_import_links_ajax_no_links_data() {
		$response = $this->simulate_ajax_call( array() );
		$this->assertFalse( $response['success'] );
		$this->assertEquals( 'No links provided for import.', $response['data']['message'] );
	}

	public function test_handle_import_links_ajax_empty_links_string() {
		$response = $this->simulate_ajax_call( array( 'links' => '   ' ) );
		$this->assertFalse( $response['success'] );
		$this->assertEquals( 'No valid lines found to process.', $response['data']['message'] );
	}

	public function test_import_single_link_full_data() {
		$links_input = "https://validurl.com,My Valid Title,My New Category";
		$response    = $this->simulate_ajax_call( array( 'links' => $links_input ) );

		$this->assertTrue( $response['success'] );
		$this->assertCount( 1, $response['data']['imported'] );
		$this->assertEquals( 'https://validurl.com', $response['data']['imported'][0] );
		$this->assertCount( 0, $response['data']['failed'] );
		$this->assertCount( 1, $response['data']['new_categories'] );
		$this->assertEquals( 'My New Category', $response['data']['new_categories'][0] );

		$bookmarks = get_bookmarks( array( 'search' => 'My Valid Title', 'category_name' => 'My New Category' ) );
		$this->assertCount( 1, $bookmarks );
		$this->assertEquals( 'https://validurl.com', $bookmarks[0]->link_url );
		$this->assertEquals( 'My Valid Title', $bookmarks[0]->link_name );
	}

	public function test_import_single_link_no_category() {
		$links_input = "https://site.com,Site Title";
		$response    = $this->simulate_ajax_call( array( 'links' => $links_input ) );

		$this->assertTrue( $response['success'] );
		$this->assertCount( 1, $response['data']['imported'] );
		$this->assertEquals( 'https://site.com', $response['data']['imported'][0] );
		$this->assertCount( 0, $response['data']['failed'] );
		$term = get_term_by('name', 'Uncategorized', 'link_category');
		if ($term && !in_array('Uncategorized', $response['data']['new_categories'])) {
			// It existed
		} else {
			$this->assertContains( 'Uncategorized', $response['data']['new_categories'] );
		}


		$bookmarks = get_bookmarks( array( 'search' => 'Site Title', 'category_name' => 'Uncategorized' ) );
		$this->assertCount( 1, $bookmarks );
		$this->assertEquals( 'https://site.com', $bookmarks[0]->link_url );
	}

	public function test_import_single_link_url_only() {
		$links_input = "https://justurl.net/";
		$response    = $this->simulate_ajax_call( array( 'links' => $links_input ) );

		$this->assertTrue( $response['success'] );
		$this->assertCount( 1, $response['data']['imported'] );
		$this->assertEquals( 'https://justurl.net/', $response['data']['imported'][0] );

		$bookmarks = get_bookmarks( array( 'search' => 'https://justurl.net/', 'category_name' => 'Uncategorized' ) );
		$this->assertCount( 1, $bookmarks );
		$this->assertEquals( 'https://justurl.net/', $bookmarks[0]->link_name );
	}

	public function test_import_quoted_fields() {
		$links_input = "\"https://quoted.org\",\"Quoted Title\",\"Quoted Category\"";
		$response    = $this->simulate_ajax_call( array( 'links' => $links_input ) );

		$this->assertTrue( $response['success'] );
		$this->assertCount( 1, $response['data']['imported'] );
		$this->assertEquals( 'https://quoted.org', $response['data']['imported'][0] );
		$this->assertContains( 'Quoted Category', $response['data']['new_categories'] );

		$bookmarks = get_bookmarks( array( 'search' => 'Quoted Title', 'category_name' => 'Quoted Category' ) );
		$this->assertCount( 1, $bookmarks );
		$this->assertEquals( 'https://quoted.org', $bookmarks[0]->link_url );
	}

	public function test_import_mixed_quoted_fields() {
		$links_input = "https://mixedquote.com,\"Mixed Title\",Unquoted Cat";
		$response    = $this->simulate_ajax_call( array( 'links' => $links_input ) );

		$this->assertTrue( $response['success'] );
		$this->assertCount( 1, $response['data']['imported'] );
		$this->assertEquals( 'https://mixedquote.com', $response['data']['imported'][0] );
		$this->assertContains( 'Unquoted Cat', $response['data']['new_categories'] );
	}

	public function test_import_line_with_extra_commas() {
		$links_input = "https://extracomma.com,Title,Category,extra,data";
		$response    = $this->simulate_ajax_call( array( 'links' => $links_input ) );

		$this->assertTrue( $response['success'] );
		$this->assertCount( 1, $response['data']['imported'] );
		$this->assertEquals( 'https://extracomma.com', $response['data']['imported'][0] );
		$this->assertContains( 'Category', $response['data']['new_categories'] );

		$bookmarks = get_bookmarks( array( 'search' => 'Title', 'category_name' => 'Category' ) );
		$this->assertCount( 1, $bookmarks );
	}

	public function test_import_invalid_url() {
		$links_input = "not-a-url,Invalid URL Link,Some Category";
		$response    = $this->simulate_ajax_call( array( 'links' => $links_input ) );

		$this->assertTrue( $response['success'] );
		$this->assertCount( 0, $response['data']['imported'] );
		$this->assertCount( 1, $response['data']['failed'] );
		$this->assertEquals( 'not-a-url', $response['data']['failed'][0]['url'] );
		$this->assertEquals( 'Invalid URL format.', $response['data']['failed'][0]['reason'] );
	}

	public function test_import_with_existing_category() {
		$term = $this->factory->term->create_and_get( array( 'taxonomy' => 'link_category', 'name' => 'Existing Cat' ) );
		$this->assertNotWPError( $term );
		$this->assertIsObject( $term );

		$links_input = "https://existingcat.com,Link In Existing,Existing Cat";
		$response    = $this->simulate_ajax_call( array( 'links' => $links_input ) );

		$this->assertTrue( $response['success'] );
		$this->assertCount( 1, $response['data']['imported'] );
		$this->assertCount( 0, $response['data']['new_categories'] );

		$bookmarks = get_bookmarks( array( 'category_name' => 'Existing Cat' ) );
		$this->assertCount( 1, $bookmarks );
		$this->assertEquals( 'https://existingcat.com', $bookmarks[0]->link_url );
	}

	public function test_import_duplicate_link_in_same_category() {
		$category_name = 'Duplicate Test Cat';
		$links_input1 = "https://duplicate.com,First Link,{$category_name}";
		$response1    = $this->simulate_ajax_call( array( 'links' => $links_input1 ) );
		$this->assertTrue( $response1['success'] );
		$this->assertCount( 1, $response1['data']['imported'] );

		$links_input2 = "https://duplicate.com,Second Link (Same URL),{$category_name}";
		$response2    = $this->simulate_ajax_call( array( 'links' => $links_input2 ) );

		$this->assertTrue( $response2['success'] );
		$this->assertCount( 0, $response2['data']['imported'] );
		$this->assertCount( 1, $response2['data']['failed'] );
		$this->assertEquals( 'https://duplicate.com', $response2['data']['failed'][0]['url'] );
		$this->assertEquals( "Duplicate link found in category \"{$category_name}\".", $response2['data']['failed'][0]['reason'] );

		$bookmarks = get_bookmarks( array( 'category_name' => $category_name ) );
		$this->assertCount( 1, $bookmarks );
		$this->assertEquals( 'First Link', $bookmarks[0]->link_name );
	}

	public function test_import_duplicate_link_in_different_category_is_not_duplicate() {
		$category_name1 = 'Cat Alpha';
		$category_name2 = 'Cat Beta';

		$links_input1 = "https://notsodup.com,Link Alpha,{$category_name1}";
		$response1    = $this->simulate_ajax_call( array( 'links' => $links_input1 ) );
		$this->assertTrue( $response1['success'] );
		$this->assertCount( 1, $response1['data']['imported'] );
		$this->assertContains( $category_name1, $response1['data']['new_categories'] );


		$links_input2 = "https://notsodup.com,Link Beta,{$category_name2}";
		$response2    = $this->simulate_ajax_call( array( 'links' => $links_input2 ) );

		$this->assertTrue( $response2['success'] );
		$this->assertCount( 1, $response2['data']['imported'] );
		$this->assertEquals( 'https://notsodup.com', $response2['data']['imported'][0] );
		$this->assertContains( $category_name2, $response2['data']['new_categories'] );
		$this->assertCount( 0, $response2['data']['failed'] );

		$bookmarks_alpha = get_bookmarks( array( 'category_name' => $category_name1 ) );
		$this->assertCount( 1, $bookmarks_alpha );
		$bookmarks_beta = get_bookmarks( array( 'category_name' => $category_name2 ) );
		$this->assertCount( 1, $bookmarks_beta );
	}

	public function test_batch_import_mixed_results() {
		$existing_cat = 'PreExisting Category';
		$this->factory->term->create( array( 'taxonomy' => 'link_category', 'name' => $existing_cat ) );
		$this->factory->link->create( array(
			'link_url' => 'https://pre.existing.com',
			'link_name' => 'Pre-existing Link',
			'link_category' => array( get_term_by('name', $existing_cat, 'link_category')->term_id )
		));


		$links_input = <<<LINKS
https://success1.com,Success One,NewCat1
invalid-url,Failed Link,NewCat2
https://pre.existing.com,Duplicate Attempt,{$existing_cat}
https://success2.com,Success Two
"https://success3.com","Success Three Quoted","NewCat1"
LINKS;

		$response = $this->simulate_ajax_call( array( 'links' => $links_input ) );

		$this->assertTrue( $response['success'] );

		$imported_urls = $response['data']['imported'];
		$failed_items  = $response['data']['failed'];
		$new_cats      = $response['data']['new_categories'];

		$this->assertCount( 3, $imported_urls, 'Should have 3 successful imports.' );
		$this->assertContains( 'https://success1.com', $imported_urls );
		$this->assertContains( 'https://success2.com', $imported_urls );
		$this->assertContains( 'https://success3.com', $imported_urls );

		$this->assertCount( 2, $failed_items, 'Should have 2 failed imports.' );
		$failed_urls = array_column( $failed_items, 'url' );
		$this->assertContains( 'invalid-url', $failed_urls );
		$this->assertContains( 'https://pre.existing.com', $failed_urls );

		foreach ( $failed_items as $item ) {
			if ( $item['url'] === 'invalid-url' ) {
				$this->assertEquals( 'Invalid URL format.', $item['reason'] );
			}
			if ( $item['url'] === 'https://pre.existing.com' ) {
				$this->assertEquals( "Duplicate link found in category \"{$existing_cat}\".", $item['reason'] );
			}
		}
		$this->assertContains( 'NewCat1', $new_cats );
		$this->assertContains( 'NewCat2', $new_cats );

		$uncategorized_term = get_term_by('name', 'Uncategorized', 'link_category');
		if ($uncategorized_term && !in_array('Uncategorized', $new_cats)) {
			// It existed
		} else {
			$this->assertContains( 'Uncategorized', $new_cats, "Uncategorized should be listed if created." );
		}
		$this->assertGreaterThanOrEqual(2, count($new_cats));
	}

	public function test_import_empty_lines_are_skipped() {
		$links_input = <<<LINKS
https://link1.com,Link One

https://link2.com,Link Two

LINKS;
		$response = $this->simulate_ajax_call( array( 'links' => $links_input ) );
		$this->assertTrue( $response['success'] );
		$this->assertCount( 2, $response['data']['imported'] );
		$this->assertCount( 0, $response['data']['failed'] );
		$this->assertEquals( 2, $response['data']['total_processed'] );
	}

	public function test_import_lines_with_only_whitespace_are_skipped() {
		$links_input = <<<LINKS
https://link1.com,Link One
   
https://link2.com,Link Two
		
LINKS;
		$response = $this->simulate_ajax_call( array( 'links' => $links_input ) );
		$this->assertTrue( $response['success'] );
		$this->assertCount( 2, $response['data']['imported'] );
		$this->assertCount( 0, $response['data']['failed'] );
		$this->assertEquals( 2, $response['data']['total_processed'] );
	}
}
