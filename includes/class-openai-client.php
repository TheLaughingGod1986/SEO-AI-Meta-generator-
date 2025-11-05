<?php
/**
 * OpenAI API Client for SEO Meta Generation
 *
 * @package    SEO_AI_Meta
 * @subpackage SEO_AI_Meta/includes
 */
class SEO_AI_Meta_OpenAI_Client {

	/**
	 * OpenAI API endpoint
	 *
	 * @var string
	 */
	private $api_endpoint = 'https://api.openai.com/v1/chat/completions';

	/**
	 * API key
	 *
	 * @var string
	 */
	private $api_key;

	/**
	 * Default model
	 *
	 * @var string
	 */
	private $model;

	/**
	 * Constructor
	 */
	public function __construct() {
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-database.php';
		
		$settings = SEO_AI_Meta_Database::get_setting( 'settings', array() );
		// Fallback to WordPress options for backward compatibility
		if ( empty( $settings ) ) {
			$settings = get_option( 'seo_ai_meta_settings', array() );
		}
		
		$this->api_key = ! empty( $settings['openai_api_key'] ) ? $settings['openai_api_key'] : '';
		
		// Try to get API key from environment variable (Render credentials)
		if ( empty( $this->api_key ) && defined( 'OPENAI_API_KEY' ) ) {
			$this->api_key = OPENAI_API_KEY;
		}

		// Try to get from .env file if available
		if ( empty( $this->api_key ) && file_exists( SEO_AI_META_PLUGIN_DIR . '.env' ) ) {
			$env_content = file_get_contents( SEO_AI_META_PLUGIN_DIR . '.env' );
			if ( preg_match( '/OPENAI_API_KEY=(.+)/', $env_content, $matches ) ) {
				$this->api_key = trim( $matches[1] );
			}
		}

		$this->model = ! empty( $settings['default_model'] ) ? $settings['default_model'] : 'gpt-4o-mini';
	}

	/**
	 * Generate SEO meta title and description
	 *
	 * @param int    $post_id Post ID.
	 * @param string $model   Model to use (optional, overrides default).
	 * @return array|WP_Error Array with 'title' and 'description', or WP_Error on failure.
	 */
	public function generate_meta( $post_id, $model = null ) {
		if ( empty( $this->api_key ) ) {
			return new WP_Error( 'no_api_key', __( 'OpenAI API key is not configured.', 'seo-ai-meta-generator' ) );
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return new WP_Error( 'invalid_post', __( 'Post not found.', 'seo-ai-meta-generator' ) );
		}

		$model_to_use = $model ? $model : $this->model;
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-database.php';
		$settings     = SEO_AI_Meta_Database::get_setting( 'settings', array() );
		// Fallback to WordPress options for backward compatibility
		if ( empty( $settings ) ) {
			$settings = get_option( 'seo_ai_meta_settings', array() );
		}
		$title_max    = ! empty( $settings['title_max_length'] ) ? intval( $settings['title_max_length'] ) : 60;
		$desc_max     = ! empty( $settings['description_max_length'] ) ? intval( $settings['description_max_length'] ) : 160;
		$tone          = ! empty( $settings['tone'] ) ? $settings['tone'] : 'professional, engaging';

		// Get post content for context
		$post_title   = $post->post_title;
		$post_excerpt = $post->post_excerpt ? $post->post_excerpt : wp_trim_words( $post->post_content, 30 );
		$post_content = wp_trim_words( $post->post_content, 200 );
		$full_content = wp_strip_all_tags( $post->post_content ); // Full content for keyword extraction

		// Get keywords if available (from Yoast, Rank Math, etc.)
		$focus_keyword = '';
		$keywords = array();
		
		if ( class_exists( 'WPSEO_Meta' ) ) {
			$focus_keyword = WPSEO_Meta::get_value( 'focuskw', $post_id );
		} elseif ( function_exists( 'rank_math_get_post_meta' ) ) {
			$focus_keyword = rank_math_get_post_meta( 'focus_keyword', $post_id );
		}
		
		// If no focus keyword from SEO plugins, extract from post content
		if ( empty( $focus_keyword ) && ! empty( $full_content ) ) {
			require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-validator.php';
			$extracted_keywords = SEO_AI_Meta_Validator::extract_keywords_from_content( $full_content, 5 );
			if ( ! empty( $extracted_keywords ) ) {
				$focus_keyword = $extracted_keywords[0]; // Use most frequent keyword as focus
				$keywords = array_slice( $extracted_keywords, 1 ); // Rest as additional keywords
			}
		}

		// Build prompt
		$prompt = $this->build_prompt( $post_title, $post_excerpt, $post_content, $focus_keyword, $keywords, $title_max, $desc_max, $tone );

		// Make API request
		$response = $this->make_api_request( $prompt, $model_to_use );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Parse response
		$result = $this->parse_response( $response, $title_max, $desc_max );
		
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		
		// Validate SEO performance
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-validator.php';
		$validation = SEO_AI_Meta_Validator::validate_seo(
			$result['title'],
			$result['description'],
			$focus_keyword,
			$keywords,
			$title_max,
			$desc_max
		);
		
		// Add validation results to response
		$result['seo_score'] = $validation['overall_score'];
		$result['seo_grade'] = $validation['overall_grade'];
		$result['validation'] = $validation;
		$result['focus_keyword'] = $focus_keyword;
		$result['keywords'] = $keywords;
		
		return $result;
	}

	/**
	 * Build the prompt for OpenAI
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
		$keyword_instruction = '';
		if ( ! empty( $focus_keyword ) ) {
			$keyword_instruction = "\n- CRITICAL: Naturally incorporate the focus keyword '{$focus_keyword}' into both the title and description. Place it near the beginning of the title if possible.";
			
			if ( ! empty( $keywords ) ) {
				$keywords_list = implode( "', '", array_slice( $keywords, 0, 3 ) );
				$keyword_instruction .= "\n- Also consider incorporating these related keywords: '{$keywords_list}' for better SEO performance.";
			}
			
			$keyword_instruction .= "\n- Prioritize readability and user intent, but ensure keyword integration is natural and effective.";
		}

		return "You are an expert SEO copywriter. Generate an optimized meta title and meta description for the following blog post.

Post Title: {$post_title}
Post Excerpt: {$post_excerpt}
Post Content Sample: {$post_content}

Requirements:
- Meta Title: {$title_max} characters or less (optimal: 30-60). Must be compelling, include the main topic, and encourage clicks. Be specific and action-oriented. Include power words when appropriate (best, ultimate, guide, tips, how, why, what, top, complete, essential).
- Meta Description: {$desc_max} characters or less (optimal: 120-160). Must summarize the content, include a clear call-to-action or value proposition, and encourage clicks. Use action verbs like 'learn', 'discover', 'explore', 'get', 'find', 'start'.
- Tone: {$tone}
- Optimize for search engines while maintaining natural, readable language.
- Avoid generic phrases like 'read more' or 'learn more' unless contextually appropriate.
- Ensure the description expands on or complements the title without repeating it.
- Include numbers or specific benefits when possible to improve click-through rates.{$keyword_instruction}

Respond in valid JSON format only, with this exact structure:
{
  \"title\": \"Your optimized meta title here\",
  \"description\": \"Your optimized meta description here\"
}";
	}

	/**
	 * Make API request to OpenAI
	 *
	 * @param string $prompt Prompt text.
	 * @param string $model  Model to use.
	 * @return array|WP_Error
	 */
	private function make_api_request( $prompt, $model ) {
		$body = array(
			'model'      => $model,
			'messages'   => array(
				array(
					'role'    => 'system',
					'content' => 'You are an expert SEO copywriter specializing in meta tag optimization. Always respond with valid JSON only.',
				),
				array(
					'role'    => 'user',
					'content' => $prompt,
				),
			),
			'temperature' => 0.7,
			'max_tokens' => 300,
		);

		$args = array(
			'method'  => 'POST',
			'headers' => array(
				'Authorization' => 'Bearer ' . $this->api_key,
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode( $body ),
			'timeout' => 30,
		);

		$response = wp_remote_request( $this->api_endpoint, $args );

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'api_error', $response->get_error_message() );
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );

		if ( $status_code !== 200 ) {
			$error_data = json_decode( $body, true );
			$error_msg  = isset( $error_data['error']['message'] ) ? $error_data['error']['message'] : __( 'OpenAI API error', 'seo-ai-meta-generator' );
			return new WP_Error( 'api_error', $error_msg, array( 'status' => $status_code ) );
		}

		$data = json_decode( $body, true );
		return $data;
	}

	/**
	 * Parse API response
	 *
	 * @param array $response  API response.
	 * @param int   $title_max Max title length.
	 * @param int   $desc_max  Max description length.
	 * @return array|WP_Error
	 */
	private function parse_response( $response, $title_max, $desc_max ) {
		if ( ! isset( $response['choices'][0]['message']['content'] ) ) {
			return new WP_Error( 'parse_error', __( 'Invalid response from OpenAI API.', 'seo-ai-meta-generator' ) );
		}

		$content = $response['choices'][0]['message']['content'];
		
		// Try to extract JSON from response (sometimes wrapped in markdown code blocks)
		if ( preg_match( '/```json\s*(.*?)\s*```/s', $content, $matches ) ) {
			$content = $matches[1];
		} elseif ( preg_match( '/```\s*(.*?)\s*```/s', $content, $matches ) ) {
			$content = $matches[1];
		}

		$data = json_decode( trim( $content ), true );

		if ( ! $data || ! isset( $data['title'] ) || ! isset( $data['description'] ) ) {
			return new WP_Error( 'parse_error', __( 'Failed to parse OpenAI response.', 'seo-ai-meta-generator' ) );
		}

		// Trim to max lengths
		$title       = mb_substr( trim( $data['title'] ), 0, $title_max );
		$description = mb_substr( trim( $data['description'] ), 0, $desc_max );

		return array(
			'title'       => $title,
			'description' => $description,
		);
	}
}

