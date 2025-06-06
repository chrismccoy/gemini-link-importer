<?php
/**
 * Link_Importer class for Gemini Link Importer.
 *
 * @package GeminiLinkImporter
 */

namespace GeminiLinkImporter;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Link_Importer {

	public function __construct() {
		add_action( 'wp_ajax_gemini_import_links', array( $this, 'handle_import_links_ajax' ) );
	}

	public function handle_import_links_ajax(): void {
		check_ajax_referer( 'gemini_link_importer_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_links' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'You do not have permission to import links.', 'gemini-link-importer' ) ) );
		}

		$links_raw = filter_input( INPUT_POST, 'links', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR );
		if ( empty( $links_raw ) || ! is_string( $links_raw ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'No links provided for import.', 'gemini-link-importer' ) ) );
		}

		$lines = array_filter( array_map( 'trim', explode( "\n", $links_raw ) ) );

		if ( empty( $lines ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'No valid lines found to process.', 'gemini-link-importer' ) ) );
		}

		$results = array(
			'imported'        => array(),
			'failed'          => array(),
			'new_categories'  => array(),
			'total_processed' => 0,
		);

		foreach ( $lines as $line ) {
			$results['total_processed']++;
			$data     = str_getcsv( $line, ',', '"' );
			$url      = isset( $data[0] ) ? trim( $data[0] ) : '';
			$title    = isset( $data[1] ) ? trim( $data[1] ) : '';
			$category = isset( $data[2] ) ? trim( $data[2] ) : '';

			if ( empty( $category ) ) {
				$category = esc_html__( 'Uncategorized', 'gemini-link-importer' );
			}

			if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
				$results['failed'][] = array(
					'url'    => $url,
					'reason' => esc_html__( 'Invalid URL format.', 'gemini-link-importer' ),
				);
				continue;
			}

			if ( empty( $title ) ) {
				$title = $url;
			}

			$category_info = $this->get_or_create_category( $category );

			if ( $category_info instanceof WP_Error ) {
				$results['failed'][] = array(
					'url'    => $url,
					'reason' => sprintf(
						/* translators: %1$s: category name, %2$s: error message */
						esc_html__( 'Error creating category "%1$s": %2$s', 'gemini-link-importer' ),
						$category,
						$category_info->get_error_message()
					),
				);
				continue;
			} elseif ( ! in_array( $category, $results['new_categories'], true ) && isset( $category_info['new'] ) && $category_info['new'] ) {
				$results['new_categories'][] = $category;
			}

			$category_term_id = $category_info['term_id'] ?? 0;
			if ( 0 === $category_term_id ) {
				$results['failed'][] = array(
					'url'    => $url,
					'reason' => sprintf(
						/* translators: %s: category name */
						esc_html__( 'Could not determine category ID for "%s".', 'gemini-link-importer' ),
						$category
					),
				);
				continue;
			}

			if ( $this->is_duplicate_link( $url, (int) $category_term_id ) ) {
				$results['failed'][] = array(
					'url'    => $url,
					'reason' => sprintf(
						/* translators: %s: category name */
						esc_html__( 'Duplicate link found in category "%s".', 'gemini-link-importer' ),
						$category
					),
				);
				continue;
			}

			$link_data = array(
				'link_url'     => esc_url_raw( $url ),
				'link_name'    => sanitize_text_field( $title ),
				'link_rss'     => '',
				'link_notes'   => '',
				'link_visible' => 'Y',
			);

			$inserted_id = wp_insert_link( $link_data );

			if ( $inserted_id === 0 || is_wp_error( $inserted_id ) ) {
				$reason = esc_html__( 'Failed to insert link (unknown reason).', 'gemini-link-importer' );
				if ( is_wp_error( $inserted_id ) ) {
					$reason = sprintf(
						/* translators: %s: WordPress error message */
						esc_html__( 'Error inserting link: %s', 'gemini-link-importer' ),
						$inserted_id->get_error_message()
					);
				}
				$results['failed'][] = array(
					'url'    => $url,
					'reason' => $reason,
				);
			} else {
				wp_set_object_terms( (int) $inserted_id, array( (int) $category_term_id ), 'link_category', false );
				$results['imported'][] = $url;
			}
		}

		wp_send_json_success( $results );
	}

	private function get_or_create_category( string $category_name ): array|WP_Error {
		$term = get_term_by( 'name', $category_name, 'link_category' );

		if ( $term ) {
			return array(
				'term_id' => $term->term_id,
				'new'     => false,
			);
		} else {
			$result = wp_insert_term(
				$category_name,
				'link_category',
				array(
					'slug' => sanitize_title( $category_name ),
				)
			);

			if ( $result instanceof WP_Error ) {
				return $result;
			}

			return array(
				'term_id' => $result['term_id'],
				'new'     => true,
			);
		}
	}

	private function is_duplicate_link( string $url, int $category_id ): bool {
		$args = array(
			'category'       => $category_id,
			'orderby'        => 'id',
			'order'          => 'ASC',
			'hide_invisible' => 0,
			'limit'          => -1,
			'echo'           => 0,
		);

		$bookmarks = get_bookmarks( $args );

		if ( empty( $bookmarks ) ) {
			return false;
		}

		foreach ( $bookmarks as $bookmark ) {
			if ( $bookmark->link_url === $url ) {
				return true;
			}
		}

		return false;
	}
}
