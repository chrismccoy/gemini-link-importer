<?php
/**
 * Admin_Page class for Gemini Link Importer.
 *
 * @package GeminiLinkImporter
 */

namespace GeminiLinkImporter;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Admin_Page {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	public function add_admin_menu(): void {
		add_submenu_page(
			'link-manager.php',
			esc_html__( 'Import Links', 'gemini-link-importer' ),
			esc_html__( 'Import Links', 'gemini-link-importer' ),
			'manage_links',
			'gemini-link-importer',
			array( $this, 'render_admin_page' )
		);
	}

	public function enqueue_scripts( string $hook ): void {
		if ( 'links_page_gemini-link-importer' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'gemini-link-importer-admin-style',
			plugin_dir_url( __FILE__ ) . '../assets/css/admin.min.css',
			array(),
			GEMINI_LINK_IMPORTER_VERSION
		);

		wp_enqueue_script(
			'gemini-link-importer-admin-script',
			plugin_dir_url( __FILE__ ) . '../assets/js/admin.min.js',
			array( 'jquery' ),
			GEMINI_LINK_IMPORTER_VERSION,
			true
		);

		wp_localize_script(
			'gemini-link-importer-admin-script',
			'geminiLinkImporterAjax',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'gemini_link_importer_nonce' ),
				'strings' => array(
					'empty_textarea'     => esc_html__( 'Please enter links to import.', 'gemini-link-importer' ),
					'importing_links'    => esc_html__( 'Importing links...', 'gemini-link-importer' ),
					'import_links'       => esc_html__( 'Import Links', 'gemini-link-importer' ),
					'overall_success'    => esc_html__( 'Successfully imported %d links.', 'gemini-link-importer' ),
					'partial_success'    => esc_html__( 'Import completed with some issues. Successfully imported %d links, %d failed, and %d new categories were created.', 'gemini-link-importer' ),
					'overall_failure'    => esc_html__( 'Failed to import any links. %d links had issues.', 'gemini-link-importer' ),
					'no_valid_links'     => esc_html__( 'No valid links found to process.', 'gemini-link-importer' ),
					'successfully_added' => esc_html__( 'Successfully Imported (%d):', 'gemini-link-importer' ),
					'failed_to_add'      => esc_html__( 'Failed to Import (%d):', 'gemini-link-importer' ),
					'new_categories'     => esc_html__( 'New Categories Created (%d):', 'gemini-link-importer' ),
				),
			)
		);
	}

	public function render_admin_page(): void {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Gemini Link Importer', 'gemini-link-importer' ); ?></h1>

			<div id="gemini-link-importer-admin-notices">
				<!-- Admin notices will be displayed here via JavaScript -->
			</div>

			<p><?php esc_html_e( 'Paste your links below, one per line. Format: URL, Title, Category (fields can be optionally quoted)', 'gemini-link-importer' ); ?></p>
			<p>
				<small>
					<?php esc_html_e( 'Examples:', 'gemini-link-importer' ); ?><br>
					<code>https://example.com/page1, Example Title, My Category</code><br>
					<code>"https://another.com/blog", "Another Blog Post"</code><br>
					<code>https://third.net/</code>
				</small>
			</p>

			<form id="gemini-link-importer-form" method="post" action="">
				<textarea
					id="gemini-link-importer-textarea"
					name="gemini_links"
					rows="15"
					class="large-text code"
					placeholder="<?php esc_attr_e( 'Paste your links here...', 'gemini-link-importer' ); ?>"
				></textarea>
				<p class="submit">
					<button
						type="submit"
						name="submit"
						id="gemini-link-importer-submit"
						class="button button-primary gemini-button-with-spinner"
					>
						<?php esc_html_e( 'Import Links', 'gemini-link-importer' ); ?>
						<span class="spinner"></span>
					</button>
				</p>
			</form>
		</div>
		<?php
	}
}

if ( ! defined( 'GEMINI_LINK_IMPORTER_VERSION' ) ) {
	define( 'GEMINI_LINK_IMPORTER_VERSION', '1.0.0' );
}
