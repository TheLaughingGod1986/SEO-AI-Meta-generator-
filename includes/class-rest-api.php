<?php
/**
 * REST API for SEO AI Meta Generator
 *
 * @package    SEO_AI_Meta
 * @subpackage SEO_AI_Meta/includes
 */
class SEO_AI_Meta_REST_API {

	/**
	 * Namespace
	 */
	const NAMESPACE = 'seo-ai-meta/v1';

	/**
	 * Register REST API routes
	 */
	public static function register_routes() {
		register_rest_route(
			self::NAMESPACE,
			'/generate',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'generate_meta' ),
				'permission_callback' => array( __CLASS__, 'check_permission' ),
				'args'                => array(
					'post_id' => array(
						'required' => true,
						'type'     => 'integer',
						'sanitize_callback' => 'absint',
					),
					'model' => array(
						'required' => false,
						'type'     => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/meta/(?P<post_id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'get_meta' ),
				'permission_callback' => array( __CLASS__, 'check_permission' ),
				'args'                => array(
					'post_id' => array(
						'required' => true,
						'type'     => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/meta/(?P<post_id>\d+)',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'update_meta' ),
				'permission_callback' => array( __CLASS__, 'check_permission' ),
				'args'                => array(
					'post_id' => array(
						'required' => true,
						'type'     => 'integer',
						'sanitize_callback' => 'absint',
					),
					'meta_title' => array(
						'required' => false,
						'type'     => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'meta_description' => array(
						'required' => false,
						'type'     => 'string',
						'sanitize_callback' => 'sanitize_textarea_field',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/stats',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'get_stats' ),
				'permission_callback' => array( __CLASS__, 'check_permission' ),
			)
		);
	}

	/**
	 * Check permissions
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool
	 */
	public static function check_permission( $request ) {
		return current_user_can( 'manage_seo_ai_meta' );
	}

	/**
	 * Generate meta tags
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function generate_meta( $request ) {
		$post_id = $request->get_param( 'post_id' );
		$model = $request->get_param( 'model' );

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new WP_Error(
				'permission_denied',
				__( 'You do not have permission to edit this post.', 'seo-ai-meta-generator' ),
				array( 'status' => 403 )
			);
		}

		// Check rate limit
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-rate-limiter.php';
		$rate_limit_check = SEO_AI_Meta_Rate_Limiter::check_rate_limit( 'generate' );
		if ( is_wp_error( $rate_limit_check ) ) {
			return new WP_Error(
				$rate_limit_check->get_error_code(),
				$rate_limit_check->get_error_message(),
				array( 'status' => 429 )
			);
		}

		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-generator.php';
		$generator = new SEO_AI_Meta_Generator();

		$result = $generator->generate( $post_id, $model );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( $result, 200 );
	}

	/**
	 * Get meta tags
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function get_meta( $request ) {
		$post_id = $request->get_param( 'post_id' );

		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-database.php';
		$meta_data = SEO_AI_Meta_Database::get_post_meta( $post_id );

		if ( ! $meta_data ) {
			return new WP_Error(
				'not_found',
				__( 'Meta tags not found for this post.', 'seo-ai-meta-generator' ),
				array( 'status' => 404 )
			);
		}

		return new WP_REST_Response( $meta_data, 200 );
	}

	/**
	 * Update meta tags
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function update_meta( $request ) {
		$post_id = $request->get_param( 'post_id' );
		$meta_title = $request->get_param( 'meta_title' );
		$meta_description = $request->get_param( 'meta_description' );

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new WP_Error(
				'permission_denied',
				__( 'You do not have permission to edit this post.', 'seo-ai-meta-generator' ),
				array( 'status' => 403 )
			);
		}

		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-database.php';
		
		$meta_data = SEO_AI_Meta_Database::get_post_meta( $post_id );
		$meta_data = $meta_data ? $meta_data : array();
		
		if ( $meta_title !== null ) {
			$meta_data['meta_title'] = $meta_title;
		}
		if ( $meta_description !== null ) {
			$meta_data['meta_description'] = $meta_description;
		}

		if ( ! empty( $meta_data ) ) {
			SEO_AI_Meta_Database::update_post_meta( $post_id, $meta_data );
		}

		$updated = SEO_AI_Meta_Database::get_post_meta( $post_id );
		return new WP_REST_Response( $updated, 200 );
	}

	/**
	 * Get statistics
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public static function get_stats( $request ) {
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-usage-tracker.php';
		$stats = SEO_AI_Meta_Usage_Tracker::get_stats_display();

		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-database.php';
		global $wpdb;
		$table = SEO_AI_Meta_Database::get_table_name( 'post_meta' );
		$total_meta = $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE meta_title IS NOT NULL AND meta_title != ''" );

		$stats['total_meta_generated'] = intval( $total_meta );
		return new WP_REST_Response( $stats, 200 );
	}
}

