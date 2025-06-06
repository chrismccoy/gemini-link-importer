<?php
/**
 * Plugin Name: Gemini Link Importer
 * Plugin URI:  https://github.com/chrismccoy/gemini-link-importer
 * Description: A WordPress plugin to import links into the Link Manager from a textarea.
 * Version:     1.0.0
 * Author:      Google
 * Author URI:  https://google.com
 * License:     GPL-2.0-or-later
 * Text Domain: gemini-link-importer
 * Domain Path: /languages
 * Requires PHP: 7.4
 * Requires at least: 5.0
 * Tested up to: 6.5
 *
 * @package GeminiLinkImporter
 */

namespace GeminiLinkImporter;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Explicitly include the necessary class files.
require_once plugin_dir_path( __FILE__ ) . 'inc/class-admin-page.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/class-link-importer.php';

/**
 * Loads the plugin text domain for translation.
 */
function gemini_link_importer_load_textdomain(): void {
	load_plugin_textdomain(
		'gemini-link-importer',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages/'
	);
}
add_action( 'plugins_loaded', 'GeminiLinkImporter\\gemini_link_importer_load_textdomain' );


/**
 * Main plugin initialization function.
 */
function gemini_link_importer_run(): void {
	// This check now runs on 'init', which is after the theme's functions.php is loaded.
	if ( ! get_option( 'link_manager_enabled' ) ) {
		add_action(
			'admin_notices',
			function () {
				echo '<div class="notice notice-error is-dismissible">';
				echo '<p><strong>' . esc_html__( 'Gemini Link Importer:', 'gemini-link-importer' ) . '</strong> ' .
					wp_kses(
						sprintf(
							/* translators: %s: URL to Link Manager plugin page. */
							__( 'The "Link Manager" functionality is not enabled on this site. Please enable it (e.g., by activating the <a href="%s" target="_blank" rel="noopener noreferrer">Link Manager plugin</a>) to use this plugin.', 'gemini-link-importer' ),
							'https://wordpress.org/plugins/link-manager/'
						),
						array(
							'a' => array(
								'href'   => array(),
								'target' => array(),
								'rel'    => array(),
							),
						)
					) . '</p>';
				echo '</div>';
			}
		);
		return;
	}

	// Initialize the admin page and link importer.
	new Admin_Page();
	new Link_Importer();
}

add_action( 'init', 'GeminiLinkImporter\\gemini_link_importer_run' );
