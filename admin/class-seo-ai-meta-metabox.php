<?php
/**
 * Meta Box for SEO AI Meta Generator
 *
 * @package    SEO_AI_Meta
 * @subpackage SEO_AI_Meta/admin
 */
class SEO_AI_Meta_Metabox {

	/**
	 * Register meta box
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'admin_init', array( $this, 'register_ajax_handlers' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_metabox_assets' ) );
	}

	/**
	 * Enqueue metabox assets
	 */
	public function enqueue_metabox_assets( $hook ) {
		// Only load on post editor screens
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		// Enqueue metabox CSS
		wp_enqueue_style(
			'seo-ai-meta-metabox',
			SEO_AI_META_PLUGIN_URL . 'assets/seo-ai-meta-metabox.css',
			array(),
			'1.0.0',
			'all'
		);

		// Enqueue metabox JS
		wp_enqueue_script(
			'seo-ai-meta-metabox',
			SEO_AI_META_PLUGIN_URL . 'assets/seo-ai-meta-metabox.js',
			array( 'jquery' ),
			'1.0.0',
			false
		);

		// Localize script for AJAX
		wp_localize_script(
			'seo-ai-meta-metabox',
			'seoAiMetaAjax',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'seo_ai_meta_nonce' ),
				'debug'   => defined( 'WP_DEBUG' ) && WP_DEBUG,
			)
		);
	}

	/**
	 * Add meta box to post editor
	 */
	public function add_meta_box() {
		add_meta_box(
			'seo_ai_meta_generator',
			__( 'SEO AI Meta Generator', 'seo-ai-meta-generator' ),
			array( $this, 'render_meta_box' ),
			'post',
			'normal',
			'high'
		);
	}

	/**
	 * Register AJAX handlers
	 */
	public function register_ajax_handlers() {
		add_action( 'wp_ajax_seo_ai_meta_generate', array( $this, 'ajax_generate_meta' ) );
		add_action( 'save_post', array( $this, 'save_meta' ) );
	}

	/**
	 * Save meta when post is saved
	 *
	 * @param int $post_id Post ID.
	 */
	public function save_meta( $post_id ) {
		// Check autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Verify nonce
		if ( ! isset( $_POST['seo_ai_meta_nonce'] ) || ! wp_verify_nonce( $_POST['seo_ai_meta_nonce'], 'seo_ai_meta_nonce' ) ) {
			return;
		}

		// Check permissions
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-database.php';
		
		// Get existing meta data
		$meta_data = SEO_AI_Meta_Database::get_post_meta( $post_id );
		$meta_data = $meta_data ? $meta_data : array();
		
		// Save meta title
		if ( isset( $_POST['seo_ai_meta_title'] ) ) {
			$meta_data['meta_title'] = sanitize_text_field( $_POST['seo_ai_meta_title'] );
			update_post_meta( $post_id, '_seo_ai_meta_title', $meta_data['meta_title'] ); // Backward compatibility
		}

		// Save meta description
		if ( isset( $_POST['seo_ai_meta_description'] ) ) {
			$meta_data['meta_description'] = sanitize_textarea_field( $_POST['seo_ai_meta_description'] );
			update_post_meta( $post_id, '_seo_ai_meta_description', $meta_data['meta_description'] ); // Backward compatibility
		}
		
		// Update custom database
		if ( ! empty( $meta_data ) ) {
			SEO_AI_Meta_Database::update_post_meta( $post_id, $meta_data );
		}
	}

	/**
	 * Render meta box
	 *
	 * @param WP_Post $post Current post.
	 */
	public function render_meta_box( $post ) {
		wp_nonce_field( 'seo_ai_meta_nonce', 'seo_ai_meta_nonce' );

		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-database.php';
		
		// Get meta from custom database
		$meta_data = SEO_AI_Meta_Database::get_post_meta( $post->ID );
		
		// Fallback to WordPress post meta for backward compatibility
		if ( ! $meta_data ) {
			$meta_title       = get_post_meta( $post->ID, '_seo_ai_meta_title', true );
			$meta_description = get_post_meta( $post->ID, '_seo_ai_meta_description', true );
			$generated_at     = get_post_meta( $post->ID, '_seo_ai_meta_generated_at', true );
			$model            = get_post_meta( $post->ID, '_seo_ai_meta_model', true );
		} else {
			$meta_title       = $meta_data['meta_title'] ?? '';
			$meta_description = $meta_data['meta_description'] ?? '';
			$generated_at     = $meta_data['generated_at'] ?? '';
			$model            = $meta_data['model'] ?? '';
		}

		$settings        = SEO_AI_Meta_Database::get_setting( 'settings', array() );
		// Fallback to WordPress options for backward compatibility
		if ( empty( $settings ) ) {
			$settings = get_option( 'seo_ai_meta_settings', array() );
		}
		$title_max       = ! empty( $settings['title_max_length'] ) ? intval( $settings['title_max_length'] ) : 60;
		$description_max = ! empty( $settings['description_max_length'] ) ? intval( $settings['description_max_length'] ) : 160;

		// Check usage
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-usage-tracker.php';
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-usage-governance.php';

		$usage_stats = SEO_AI_Meta_Usage_Tracker::get_stats_display();
		$can_generate = SEO_AI_Meta_Usage_Governance::can_generate();
		$at_limit = is_wp_error( $can_generate );

		include 'partials/seo-ai-meta-metabox.php';
	}

	/**
	 * AJAX handler for generating meta
	 */
	public function ajax_generate_meta() {
		check_ajax_referer( 'seo_ai_meta_nonce', 'nonce' );

		$post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
		if ( ! $post_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid post ID.', 'seo-ai-meta-generator' ) ) );
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'seo-ai-meta-generator' ) ) );
		}

		$model = isset( $_POST['model'] ) ? sanitize_text_field( $_POST['model'] ) : null;

		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-generator.php';
		$generator = new SEO_AI_Meta_Generator();

		$result = $generator->generate( $post_id, $model );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array(
				'message' => $result->get_error_message(),
				'code'    => $result->get_error_code(),
			) );
		}

		// Check for duplicates
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-generator.php';
		$duplicate_check = SEO_AI_Meta_Generator::check_duplicate_meta( $result['title'], $post_id );
		
		$response_data = array(
			'title'       => $result['title'],
			'description' => $result['description'],
		);

		if ( $duplicate_check ) {
			$duplicate_posts = array_map( function( $post ) {
				$post_obj = get_post( $post['post_id'] );
				return array(
					'id'    => $post['post_id'],
					'title' => $post_obj ? $post_obj->post_title : '',
					'url'   => $post_obj ? get_edit_post_link( $post['post_id'] ) : '',
				);
			}, $duplicate_check['posts'] );

			$response_data['duplicate_warning'] = array(
				'count' => $duplicate_check['count'],
				'posts' => $duplicate_posts,
				'message' => sprintf(
					/* translators: %d: number of duplicates */
					_n(
						'⚠️ Warning: This meta title already exists on %d other post.',
						'⚠️ Warning: This meta title already exists on %d other posts.',
						$duplicate_check['count'],
						'seo-ai-meta-generator'
					),
					$duplicate_check['count']
				),
			);
		}

		wp_send_json_success( $response_data );
	}
}

