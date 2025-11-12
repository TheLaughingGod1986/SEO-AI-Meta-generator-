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
		add_action( 'wp_ajax_seo_ai_meta_get_posts', array( $this, 'ajax_get_posts' ) );
		add_action( 'wp_ajax_seo_ai_meta_generate_single', array( $this, 'ajax_generate_single' ) );
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

		$data = $this->process_post_ids( $post_ids );
		wp_send_json_success( $data );
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

		$data = $this->process_post_ids( $post_ids );
		wp_send_json_success( $data );
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

	/**
	 * Process post IDs for generation/optimization.
	 *
	 * @param array $post_ids Post IDs to process.
	 * @return array
	 */
	private function process_post_ids( array $post_ids ) {
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-generator.php';
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-usage-governance.php';

		$generator = new SEO_AI_Meta_Generator();
		$results   = array();
		$errors    = array();

		foreach ( $post_ids as $post_id ) {
			$can_generate = SEO_AI_Meta_Usage_Governance::can_generate();
			if ( is_wp_error( $can_generate ) ) {
				$errors[] = array(
					'post_id' => $post_id,
					'message' => $can_generate->get_error_message(),
				);
				break;
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

			usleep( 500000 ); // 0.5 seconds
		}

		return array(
			'processed'     => count( $results ),
			'errors'        => count( $errors ),
			'results'       => $results,
			'error_details' => $errors,
		);
	}

	/**
	 * AJAX handler to get posts with meta status
	 */
	public function ajax_get_posts() {
		check_ajax_referer( 'seo_ai_meta_bulk_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_seo_ai_meta' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'seo-ai-meta-generator' ) ) );
		}

		$post_type   = isset( $_POST['post_type'] ) ? sanitize_text_field( $_POST['post_type'] ) : 'post';
		$status      = isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : 'all';
		$search      = isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '';
		$per_page    = isset( $_POST['per_page'] ) ? intval( $_POST['per_page'] ) : 20;
		$page        = isset( $_POST['page'] ) ? intval( $_POST['page'] ) : 1;

		$posts_data = self::get_all_posts_with_status( $post_type, $status, $search, $per_page, $page );
		wp_send_json_success( $posts_data );
	}

	/**
	 * AJAX handler to generate meta for a single post
	 */
	public function ajax_generate_single() {
		check_ajax_referer( 'seo_ai_meta_bulk_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_seo_ai_meta' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'seo-ai-meta-generator' ) ) );
		}

		$post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
		if ( ! $post_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid post ID.', 'seo-ai-meta-generator' ) ) );
		}

		// Check usage limits
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-usage-governance.php';
		$can_generate = SEO_AI_Meta_Usage_Governance::can_generate();
		if ( is_wp_error( $can_generate ) ) {
			wp_send_json_error( array( 'message' => $can_generate->get_error_message() ) );
		}

		// Generate meta
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-generator.php';
		$generator = new SEO_AI_Meta_Generator();
		$result    = $generator->generate( $post_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		// Get updated post data
		$post_data = self::get_post_meta_data( $post_id );
		wp_send_json_success( $post_data );
	}

	/**
	 * Get all posts with meta status
	 *
	 * @param string $post_type Post type.
	 * @param string $status    Status filter (all, missing, complete, short).
	 * @param string $search    Search term.
	 * @param int    $per_page  Posts per page.
	 * @param int    $page      Current page.
	 * @return array
	 */
	public static function get_all_posts_with_status( $post_type = 'post', $status = 'all', $search = '', $per_page = 20, $page = 1 ) {
		$args = array(
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		// Add search
		if ( ! empty( $search ) ) {
			$args['s'] = $search;
		}

		// Add meta query based on status
		if ( 'missing' === $status ) {
			$args['meta_query'] = array(
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
			);
		} elseif ( 'complete' === $status ) {
			$args['meta_query'] = array(
				array(
					'key'     => '_seo_ai_meta_title',
					'compare' => 'EXISTS',
				),
				array(
					'key'     => '_seo_ai_meta_title',
					'value'   => '',
					'compare' => '!=',
				),
			);
		}

		$query = new WP_Query( $args );
		$posts = array();

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$post_id = get_the_ID();
				$posts[] = self::get_post_meta_data( $post_id );
			}
			wp_reset_postdata();
		}

		// Filter by "short" status in PHP if needed
		if ( 'short' === $status ) {
			$posts = array_filter(
				$posts,
				function( $post ) {
					return 'short' === $post['status'];
				}
			);
			$posts = array_values( $posts ); // Re-index
		}

		return array(
			'posts'       => $posts,
			'total'       => $query->found_posts,
			'total_pages' => $query->max_num_pages,
			'current_page' => $page,
		);
	}

	/**
	 * Get post meta data with status
	 *
	 * @param int $post_id Post ID.
	 * @return array
	 */
	public static function get_post_meta_data( $post_id ) {
		$post  = get_post( $post_id );
		$title = get_post_meta( $post_id, '_seo_ai_meta_title', true );
		$desc  = get_post_meta( $post_id, '_seo_ai_meta_description', true );

		// Determine status
		$status = 'complete';
		if ( empty( $title ) || empty( $desc ) ) {
			$status = 'missing';
		} elseif ( strlen( $title ) < 30 || strlen( $desc ) < 120 ) {
			$status = 'short';
		}

		return array(
			'id'          => $post_id,
			'title'       => $post->post_title,
			'edit_url'    => get_edit_post_link( $post_id ),
			'permalink'   => get_permalink( $post_id ),
			'date'        => get_the_date( 'M j, Y', $post_id ),
			'meta_title'  => $title,
			'meta_desc'   => $desc,
			'title_length' => strlen( $title ),
			'desc_length'  => strlen( $desc ),
			'status'      => $status,
			'post_type'   => $post->post_type,
		);
	}
}
