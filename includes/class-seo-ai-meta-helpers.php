<?php
/**
 * Helper Functions for SEO AI Meta Generator
 *
 * @package    SEO_AI_Meta
 * @subpackage SEO_AI_Meta/includes
 */
class SEO_AI_Meta_Helpers {

	/**
	 * Get post count without meta tags
	 *
	 * @return int
	 */
	public static function get_posts_without_meta_count() {
		$args = array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
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
			'fields'         => 'ids',
		);

		$query = new WP_Query( $args );
		return $query->found_posts;
	}

	/**
	 * Get total posts count with meta tags
	 *
	 * @return int
	 */
	public static function get_posts_with_meta_count() {
		$args = array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
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
			'fields'         => 'ids',
		);

		$query = new WP_Query( $args );
		return $query->found_posts;
	}

	/**
	 * Get meta coverage percentage
	 *
	 * @return float
	 */
	public static function get_meta_coverage_percentage() {
		$with_meta = self::get_posts_with_meta_count();
		$total = wp_count_posts( 'post' )->publish;

		if ( $total === 0 ) {
			return 0;
		}

		return round( ( $with_meta / $total ) * 100, 1 );
	}

	/**
	 * Format currency amount
	 *
	 * @param float  $amount   Amount.
	 * @param string $currency Currency code.
	 * @return string
	 */
	public static function format_currency( $amount, $currency = 'GBP' ) {
		$symbols = array(
			'GBP' => '£',
			'USD' => '$',
			'EUR' => '€',
		);

		$symbol = $symbols[ $currency ] ?? $currency;
		return $symbol . number_format( $amount, 2 );
	}

	/**
	 * Check if AltText AI plugin is active
	 *
	 * @return bool
	 */
	public static function is_alttext_ai_active() {
		return is_plugin_active( 'ai-alt-gpt/ai-alt-gpt.php' ) || 
		       is_plugin_active( 'ai-alt-text-generator/ai-alt-text-generator.php' );
	}

	/**
	 * Get AltText AI plugin URL
	 *
	 * @return string
	 */
	public static function get_alttext_ai_url() {
		return 'https://wordpress.org/plugins/ai-alt-text-generator/';
	}

	/**
	 * Get recent activity (recently generated meta tags)
	 *
	 * @param int $limit Number of posts to return.
	 * @return array Array of post objects with meta generation info.
	 */
	public static function get_recent_activity( $limit = 5 ) {
		$args = array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'meta_query'     => array(
				array(
					'key'     => '_seo_ai_meta_generated_at',
					'compare' => 'EXISTS',
				),
			),
			'orderby'        => 'meta_value',
			'meta_key'       => '_seo_ai_meta_generated_at',
			'order'          => 'DESC',
		);

		$query = new WP_Query( $args );
		$posts = array();

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-database.php';
			$meta_data = SEO_AI_Meta_Database::get_post_meta( get_the_ID() );
			$generated_at = $meta_data ? ( $meta_data['generated_at'] ?? '' ) : get_post_meta( get_the_ID(), '_seo_ai_meta_generated_at', true );
				$posts[] = array(
					'id'           => get_the_ID(),
					'title'        => get_the_title(),
					'generated_at' => $generated_at,
					'time_ago'     => human_time_diff( strtotime( $generated_at ), current_time( 'timestamp' ) ),
				);
			}
			wp_reset_postdata();
		}

		return $posts;
	}

	/**
	 * Get SEO impact stats for this month
	 *
	 * @return array Array with impact metrics.
	 */
	public static function get_seo_impact_stats() {
		$current_month_start = strtotime( 'first day of this month' );
		$current_month_end = strtotime( 'last day of this month' );
		
		// Get posts with meta generated this month
		$args = array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => array(
				array(
					'key'     => '_seo_ai_meta_generated_at',
					'compare' => 'EXISTS',
				),
			),
			'fields'         => 'ids',
		);

		$query = new WP_Query( $args );
		$posts_with_meta = $query->posts;
		
		// Filter by this month
		$posts_this_month = 0;
		foreach ( $posts_with_meta as $post_id ) {
			require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-database.php';
			$meta_data = SEO_AI_Meta_Database::get_post_meta( $post_id );
			$generated_at = $meta_data ? ( $meta_data['generated_at'] ?? '' ) : get_post_meta( $post_id, '_seo_ai_meta_generated_at', true );
			if ( $generated_at ) {
				$timestamp = strtotime( $generated_at );
				if ( $timestamp >= $current_month_start && $timestamp <= $current_month_end ) {
					$posts_this_month++;
				}
			}
		}

		// Calculate estimated impact (rough estimates)
		$estimated_clicks = $posts_this_month * 15; // Average 15% CTR improvement
		$estimated_rankings = $posts_this_month * 8; // Average 8 position improvement

		return array(
			'posts_optimized'    => $posts_this_month,
			'estimated_clicks'   => $estimated_clicks,
			'estimated_rankings' => $estimated_rankings,
			'coverage'           => self::get_meta_coverage_percentage(),
		);
	}
}

