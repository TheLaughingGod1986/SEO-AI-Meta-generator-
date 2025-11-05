<?php
/**
 * Meta Template Processor
 * Processes meta templates with variables like {{title}}, {{date}}, etc.
 *
 * @package    SEO_AI_Meta
 * @subpackage SEO_AI_Meta/includes
 */
class SEO_AI_Meta_Template_Processor {

	/**
	 * Process template with variables
	 *
	 * @param string $template Template string with variables.
	 * @param int    $post_id  Post ID.
	 * @param string $content  Base content (AI-generated).
	 * @return string Processed template.
	 */
	public static function process( $template, $post_id, $content = '' ) {
		if ( empty( $template ) ) {
			return $content;
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return $content;
		}

		// Get replacement values
		$replacements = self::get_replacements( $post_id, $post, $content );

		// Replace variables
		$processed = $template;
		foreach ( $replacements as $key => $value ) {
			$processed = str_replace( '{{' . $key . '}}', $value, $processed );
		}

		return $processed;
	}

	/**
	 * Get replacement values for template variables
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @param string  $content Base content.
	 * @return array
	 */
	private static function get_replacements( $post_id, $post, $content = '' ) {
		$replacements = array(
			'title'    => get_the_title( $post_id ),
			'date'     => get_the_date( '', $post_id ),
			'year'     => get_the_date( 'Y', $post_id ),
			'month'    => get_the_date( 'F', $post_id ),
			'day'      => get_the_date( 'd', $post_id ),
			'time'     => get_the_time( '', $post_id ),
			'author'   => get_the_author_meta( 'display_name', $post->post_author ),
			'site'     => get_bloginfo( 'name' ),
			'siteurl'  => get_bloginfo( 'url' ),
			'content'  => $content,
		);

		// Get categories
		$categories = get_the_category( $post_id );
		$replacements['category'] = ! empty( $categories ) ? $categories[0]->name : '';

		// Get tags
		$tags = get_the_tags( $post_id );
		if ( $tags && ! empty( $tags ) ) {
			$tag_names = array_map( function( $tag ) {
				return $tag->name;
			}, $tags );
			$replacements['tags'] = implode( ', ', array_slice( $tag_names, 0, 3 ) );
		} else {
			$replacements['tags'] = '';
		}

		// Get excerpt
		$excerpt = get_the_excerpt( $post_id );
		$replacements['excerpt'] = ! empty( $excerpt ) ? $excerpt : wp_trim_words( $post->post_content, 20 );

		return $replacements;
	}

	/**
	 * Apply templates to generated meta
	 *
	 * @param array $meta_data Generated meta data (title, description).
	 * @param int   $post_id   Post ID.
	 * @return array Processed meta data.
	 */
	public static function apply_templates( $meta_data, $post_id ) {
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-database.php';
		$settings = SEO_AI_Meta_Database::get_setting( 'settings', array() );
		if ( empty( $settings ) ) {
			$settings = get_option( 'seo_ai_meta_settings', array() );
		}

		// Process title template
		if ( ! empty( $settings['title_template'] ) && ! empty( $meta_data['title'] ) ) {
			$meta_data['title'] = self::process( $settings['title_template'], $post_id, $meta_data['title'] );
		}

		// Process description template
		if ( ! empty( $settings['description_template'] ) && ! empty( $meta_data['description'] ) ) {
			$meta_data['description'] = self::process( $settings['description_template'], $post_id, $meta_data['description'] );
		}

		return $meta_data;
	}
}

