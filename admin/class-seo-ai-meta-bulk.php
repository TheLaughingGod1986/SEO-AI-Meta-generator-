<?php
/**
 * Bulk Generate Class for SEO AI Meta Generator
 *
 * @package    SEO_AI_Meta
 * @subpackage SEO_AI_Meta/admin
 */
class SEO_AI_Meta_Bulk {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'wp_ajax_seo_ai_meta_bulk_generate', array( $this, 'ajax_bulk_generate' ) );
		add_action( 'wp_ajax_seo_ai_meta_bulk_optimize', array( $this, 'ajax_bulk_optimize' ) );
		add_action( 'admin_post_seo_ai_meta_bulk_process', array( $this, 'process_bulk_generate' ) );
	}

	/**
	 * AJAX handler for bulk generation
	 */
	public function ajax_bulk_generate() {
		check_ajax_referer( 'seo_ai_meta_bulk_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_seo_ai_meta' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'seo-ai-meta-generator' ) ) );
		}

		$post_ids = isset( $_POST['post_ids'] ) ? array_map( 'intval', $_POST['post_ids'] ) : array();
		if ( empty( $post_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No posts selected.', 'seo-ai-meta-generator' ) ) );
		}

		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-generator.php';
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-usage-governance.php';

		$generator = new SEO_AI_Meta_Generator();
		$results = array();
		$errors = array();

		foreach ( $post_ids as $post_id ) {
			// Check if limit reached
			$can_generate = SEO_AI_Meta_Usage_Governance::can_generate();
			if ( is_wp_error( $can_generate ) ) {
				$errors[] = array(
					'post_id' => $post_id,
					'message' => $can_generate->get_error_message(),
				);
				break; // Stop processing if limit reached
			}

			$result = $generator->generate( $post_id );
			if ( is_wp_error( $result ) ) {
				$errors[] = array(
					'post_id' => $post_id,
					'message' => $result->get_error_message(),
				);
			} else {
				$results[] = array(
					'post_id' => $post_id,
					'success' => true,
				);
			}

			// Add small delay to avoid rate limiting
			usleep( 500000 ); // 0.5 seconds
		}

		wp_send_json_success( array(
			'processed' => count( $results ),
			'errors'    => count( $errors ),
			'results'   => $results,
			'error_details' => $errors,
		) );
	}

	/**
	 * AJAX handler for bulk optimization (regenerate existing meta)
	 */
	public function ajax_bulk_optimize() {
		check_ajax_referer( 'seo_ai_meta_bulk_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_seo_ai_meta' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'seo-ai-meta-generator' ) ) );
		}

		$post_ids = isset( $_POST['post_ids'] ) ? array_map( 'intval', $_POST['post_ids'] ) : array();
		if ( empty( $post_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No posts selected.', 'seo-ai-meta-generator' ) ) );
		}

		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-generator.php';
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-usage-governance.php';

		$generator = new SEO_AI_Meta_Generator();
		$results = array();
		$errors = array();

		foreach ( $post_ids as $post_id ) {
			// Check if limit reached
			$can_generate = SEO_AI_Meta_Usage_Governance::can_generate();
			if ( is_wp_error( $can_generate ) ) {
				$errors[] = array(
					'post_id' => $post_id,
					'message' => $can_generate->get_error_message(),
				);
				break; // Stop processing if limit reached
			}

			$result = $generator->generate( $post_id );
			if ( is_wp_error( $result ) ) {
				$errors[] = array(
					'post_id' => $post_id,
					'message' => $result->get_error_message(),
				);
			} else {
				$results[] = array(
					'post_id' => $post_id,
					'success' => true,
				);
			}

			// Add small delay to avoid rate limiting
			usleep( 500000 ); // 0.5 seconds
		}

		wp_send_json_success( array(
			'processed' => count( $results ),
			'errors'    => count( $errors ),
			'results'   => $results,
			'error_details' => $errors,
		) );
	}

	/**
	 * Get posts without meta
	 *
	 * @param int $per_page Posts per page.
	 * @param int $paged     Current page.
	 * @return array
	 */
	public static function get_posts_without_meta( $per_page = 20, $paged = 1 ) {
		$args = array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => $paged,
			'meta_query'     => array(
				'relation' => 'OR',
				array(
					'key'     => '_seo_ai_meta_title',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => '_seo_ai_meta_title',
					'value'   => '',
					'compare' => '=',
				),
			),
		);

		$query = new WP_Query( $args );
		return $query;
	}

	/**
	 * Get posts with meta tags (for optimization)
	 *
	 * @param int $per_page Posts per page.
	 * @param int $paged     Current page.
	 * @return WP_Query
	 */
	public static function get_posts_with_meta( $per_page = 20, $paged = 1 ) {
		$args = array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => $paged,
			'meta_query'     => array(
				array(
					'key'     => '_seo_ai_meta_title',
					'compare' => 'EXISTS',
				),
				array(
					'key'     => '_seo_ai_meta_title',
					'value'   => '',
					'compare' => '!=',
				),
			),
		);

		$query = new WP_Query( $args );
		return $query;
	}
}

