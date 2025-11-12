<?php
/**
 * SEO AI Meta Generator Core Class
 * Orchestrates meta generation workflow
 *
 * @package    SEO_AI_Meta
 * @subpackage SEO_AI_Meta/includes
 */
class SEO_AI_Meta_Generator {

	/**
	 * OpenAI client instance
	 *
	 * @var SEO_AI_Meta_OpenAI_Client
	 */
	private $openai_client;

	/**
	 * API client instance
	 *
	 * @var SEO_AI_Meta_API_Client_V2
	 */
	private $api_client;

	/**
	 * Constructor
	 */
	public function __construct() {
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-openai-client.php';
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-api-client-v2.php';
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-usage-tracker.php';
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-usage-governance.php';

		$this->openai_client = new SEO_AI_Meta_OpenAI_Client();
		$this->api_client = new SEO_AI_Meta_API_Client_V2();
	}

	/**
	 * Generate meta for a post
	 *
	 * @param int    $post_id Post ID.
	 * @param string $model   Model to use (optional).
	 * @return array|WP_Error
	 */
	public function generate( $post_id, $model = null ) {
		// Check permissions
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new WP_Error( 'permission_denied', __( 'You do not have permission to generate meta for this post.', 'seo-ai-meta-generator' ) );
		}

		// Check usage limits
		$can_generate = SEO_AI_Meta_Usage_Governance::can_generate();
		if ( is_wp_error( $can_generate ) ) {
			return $can_generate;
		}

		// Determine model based on plan if not specified
		if ( ! $model ) {
			require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-site-license.php';

			// Get plan based on license mode
			if ( SEO_AI_Meta_Site_License::is_site_wide_mode() ) {
				$plan = SEO_AI_Meta_Site_License::get_site_plan();
			} else {
				$user_id = get_current_user_id();
				$plan    = get_user_meta( $user_id, 'seo_ai_meta_plan', true ) ?: 'free';
			}

			$model = ( $plan === 'pro' || $plan === 'agency' ) ? 'gpt-4-turbo' : 'gpt-4o-mini';
		}

		// Prefer backend API if authenticated, otherwise use local OpenAI client
		$result = null;
		if ( $this->api_client->is_authenticated() ) {
			// Use backend API (which has the OpenAI key configured)
			$result = $this->generate_via_backend( $post_id, $model );
			
			// If backend doesn't support meta generation yet, fall back to local
			if ( is_wp_error( $result ) && $result->get_error_code() === 'invalid_response' ) {
				$result = null; // Allow fallback
			}
		}
		
		// Fallback to local OpenAI client if backend fails or not authenticated
		if ( is_wp_error( $result ) || $result === null ) {
			// Only try local if user has configured an API key
			$local_result = $this->openai_client->generate_meta( $post_id, $model );
			if ( is_wp_error( $local_result ) && $local_result->get_error_code() === 'no_api_key' ) {
				// No local key and backend not available - show helpful error based on license mode
				require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-site-license.php';

				if ( SEO_AI_Meta_Site_License::is_site_wide_mode() ) {
					return new WP_Error(
						'no_api_available',
						__( 'Site license is not configured. Please contact your site administrator to activate the site license in SEO AI Meta → Settings.', 'seo-ai-meta-generator' )
					);
				} else {
					return new WP_Error(
						'no_api_available',
						__( 'Please authenticate with the backend to use the service. Go to SEO AI Meta → Dashboard to log in.', 'seo-ai-meta-generator' )
					);
				}
			}
			$result = $local_result;
		}

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Apply templates if configured
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-meta-template-processor.php';
		$result = SEO_AI_Meta_Template_Processor::apply_templates( $result, $post_id );

		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-database.php';
		
		// Store meta in custom database table
		$meta_data = array(
			'meta_title'       => $result['title'],
			'meta_description' => $result['description'],
			'generated_at'     => current_time( 'mysql' ),
			'model'            => $model,
		);
		
		// Store SEO validation results
		if ( isset( $result['seo_score'] ) ) {
			$meta_data['seo_score'] = floatval( $result['seo_score'] );
			$meta_data['seo_grade'] = $result['seo_grade'];
		}
		
		if ( isset( $result['focus_keyword'] ) && ! empty( $result['focus_keyword'] ) ) {
			$meta_data['focus_keyword'] = $result['focus_keyword'];
		}
		
		SEO_AI_Meta_Database::update_post_meta( $post_id, $meta_data );
		
		// Also update WordPress post meta for backward compatibility
		update_post_meta( $post_id, '_seo_ai_meta_title', $result['title'] );
		update_post_meta( $post_id, '_seo_ai_meta_description', $result['description'] );
		update_post_meta( $post_id, '_seo_ai_meta_generated_at', current_time( 'mysql' ) );
		update_post_meta( $post_id, '_seo_ai_meta_model', $model );
		if ( isset( $result['seo_score'] ) ) {
			update_post_meta( $post_id, '_seo_ai_meta_seo_score', $result['seo_score'] );
			update_post_meta( $post_id, '_seo_ai_meta_seo_grade', $result['seo_grade'] );
		}
		if ( isset( $result['focus_keyword'] ) && ! empty( $result['focus_keyword'] ) ) {
			update_post_meta( $post_id, '_seo_ai_meta_focus_keyword', $result['focus_keyword'] );
		}

		// Increment usage
		SEO_AI_Meta_Usage_Tracker::increment_usage( get_current_user_id(), $post_id, $model );

		// Send welcome email on first generation
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-email-manager.php';
		$email_manager = new SEO_AI_Meta_Email_Manager();
		$email_manager->send_welcome_email();

		// Check and send usage warning if at 80%
		if ( SEO_AI_Meta_Usage_Governance::should_show_upgrade_prompt() ) {
			$email_manager->send_limit_warning_email();
		}

		return $result;
	}

	/**
	 * Check for duplicate meta tags
	 *
	 * @param string $meta_title Meta title to check.
	 * @param int    $current_post_id Current post ID (to exclude from check).
	 * @return array|false Array with duplicate info or false if no duplicates.
	 */
	public static function check_duplicate_meta( $meta_title, $current_post_id ) {
		if ( empty( $meta_title ) ) {
			return false;
		}

		global $wpdb;
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-database.php';
		$table = SEO_AI_Meta_Database::get_table_name( 'post_meta' );

		// Check for similar titles (exact match or very similar)
		$duplicates = $wpdb->get_results( $wpdb->prepare(
			"SELECT post_id, meta_title FROM {$table} 
			WHERE post_id != %d 
			AND meta_title = %s 
			AND meta_title IS NOT NULL 
			AND meta_title != '' 
			LIMIT 5",
			$current_post_id,
			$meta_title
		), ARRAY_A );

		if ( ! empty( $duplicates ) ) {
			return array(
				'count' => count( $duplicates ),
				'posts' => $duplicates,
			);
		}

		return false;
	}

	/**
	 * Generate meta via backend API
	 *
	 * @param int    $post_id Post ID.
	 * @param string $model   Model to use.
	 * @return array|WP_Error
	 */
	private function generate_via_backend( $post_id, $model ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new WP_Error( 'invalid_post', __( 'Post not found.', 'seo-ai-meta-generator' ) );
		}

		// Get settings for prompt building
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-database.php';
		$settings = SEO_AI_Meta_Database::get_setting( 'settings', array() );
		if ( empty( $settings ) ) {
			$settings = get_option( 'seo_ai_meta_settings', array() );
		}
		
		$title_max = ! empty( $settings['title_max_length'] ) ? intval( $settings['title_max_length'] ) : 60;
		$desc_max  = ! empty( $settings['description_max_length'] ) ? intval( $settings['description_max_length'] ) : 160;
		$tone      = ! empty( $settings['tone'] ) ? $settings['tone'] : 'professional, engaging';

		// Get post content
		$post_title   = $post->post_title;
		$post_excerpt = $post->post_excerpt ? $post->post_excerpt : wp_trim_words( $post->post_content, 30 );
		$post_content = wp_trim_words( $post->post_content, 200 );
		$full_content = wp_strip_all_tags( $post->post_content );

		// Get keywords
		$focus_keyword = '';
		$keywords = array();
		
		if ( class_exists( 'WPSEO_Meta' ) ) {
			$focus_keyword = WPSEO_Meta::get_value( 'focuskw', $post_id );
		} elseif ( function_exists( 'rank_math_get_post_meta' ) ) {
			$focus_keyword = rank_math_get_post_meta( 'focus_keyword', $post_id );
		}
		
		if ( empty( $focus_keyword ) && ! empty( $full_content ) ) {
			require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-validator.php';
			$extracted_keywords = SEO_AI_Meta_Validator::extract_keywords_from_content( $full_content, 5 );
			if ( ! empty( $extracted_keywords ) ) {
				$focus_keyword = $extracted_keywords[0];
				$keywords = array_slice( $extracted_keywords, 1 );
			}
		}

		// Build prompt (same as OpenAI client)
		$prompt = $this->build_prompt( $post_title, $post_excerpt, $post_content, $focus_keyword, $keywords, $title_max, $desc_max, $tone );

		// Build system message for meta generation
		$system_message = 'You are an expert SEO copywriter specializing in meta tag optimization. Always respond with valid JSON only.';
		
		// Call backend API - use a modified request that works with the existing endpoint
		// The backend endpoint expects image_data/context, but we can use context for the prompt
		$response = $this->api_client->make_request( '/api/generate', 'POST', array(
			'image_data' => null, // No image for meta generation
			'context' => $prompt, // Use context field for our prompt
			'service' => 'seo-ai-meta',
			'model' => $model,
			'type' => 'meta' // Add type hint for backend
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// The backend returns alt_text, but for meta we expect JSON with title/description
		// Parse the response - it might be in alt_text field or data.content
		$content = null;
		if ( isset( $response['alt_text'] ) ) {
			$content = $response['alt_text'];
		} elseif ( isset( $response['data']['content'] ) ) {
			$content = $response['data']['content'];
		} elseif ( isset( $response['content'] ) ) {
			$content = $response['content'];
		}

		if ( $content ) {
			$data = json_decode( $content, true );
			
			if ( json_last_error() === JSON_ERROR_NONE && isset( $data['title'] ) && isset( $data['description'] ) ) {
				$result = array(
					'title' => $data['title'],
					'description' => $data['description'],
					'focus_keyword' => $focus_keyword,
					'keywords' => $keywords
				);

				// Validate SEO
				require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-validator.php';
				$validation = SEO_AI_Meta_Validator::validate_seo(
					$result['title'],
					$result['description'],
					$focus_keyword,
					$keywords,
					$title_max,
					$desc_max
				);
				
				$result['seo_score'] = $validation['overall_score'];
				$result['seo_grade'] = $validation['overall_grade'];
				$result['validation'] = $validation;
				
				return $result;
			}
		}

		return new WP_Error( 'invalid_response', __( 'Invalid response from backend API. Backend may not support meta generation yet.', 'seo-ai-meta-generator' ) );
	}

	/**
	 * Build prompt for meta generation (shared with OpenAI client)
	 *
	 * @param string $post_title    Post title.
	 * @param string $post_excerpt  Post excerpt.
	 * @param string $post_content  Post content sample.
	 * @param string $focus_keyword Focus keyword.
	 * @param array  $keywords      Additional keywords.
	 * @param int    $title_max     Max title length.
	 * @param int    $desc_max      Max description length.
	 * @param string $tone          Tone to use.
	 * @return string
	 */
	private function build_prompt( $post_title, $post_excerpt, $post_content, $focus_keyword, $keywords = array(), $title_max = 60, $desc_max = 160, $tone = 'professional, engaging' ) {
		// Use reflection to call private method from OpenAI client, or duplicate the logic
		// For now, we'll duplicate the prompt building logic
		$prompt = "Generate SEO-optimized meta title and description for the following content:\n\n";
		$prompt .= "Title: {$post_title}\n\n";
		if ( ! empty( $post_excerpt ) ) {
			$prompt .= "Excerpt: {$post_excerpt}\n\n";
		}
		if ( ! empty( $post_content ) ) {
			$prompt .= "Content: {$post_content}\n\n";
		}
		if ( ! empty( $focus_keyword ) ) {
			$prompt .= "Focus Keyword: {$focus_keyword}\n\n";
		}
		if ( ! empty( $keywords ) ) {
			$prompt .= "Additional Keywords: " . implode( ', ', $keywords ) . "\n\n";
		}
		$prompt .= "Requirements:\n";
		$prompt .= "- Meta title: Maximum {$title_max} characters, include focus keyword near the beginning\n";
		$prompt .= "- Meta description: Maximum {$desc_max} characters, include focus keyword naturally\n";
		$prompt .= "- Tone: {$tone}\n";
		$prompt .= "- Make it compelling and click-worthy\n";
		$prompt .= "- Ensure it accurately represents the content\n\n";
		$prompt .= "Respond in valid JSON format only, with this exact structure:\n";
		$prompt .= "{\n";
		$prompt .= "  \"title\": \"Your optimized meta title here\",\n";
		$prompt .= "  \"description\": \"Your optimized meta description here\"\n";
		$prompt .= "}";
		
		return $prompt;
	}
}
