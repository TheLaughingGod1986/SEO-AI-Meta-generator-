<?php
/**
 * Usage Governance for SEO AI Meta Generator
 * Handles limit checks and upgrade prompts
 *
 * @package    SEO_AI_Meta
 * @subpackage SEO_AI_Meta/includes
 */
class SEO_AI_Meta_Usage_Governance {

	/**
	 * Check if user can generate meta
	 *
	 * @return bool|WP_Error
	 */
	public static function can_generate() {
		if ( SEO_AI_Meta_Usage_Tracker::is_at_limit() ) {
			return new WP_Error(
				'limit_reached',
				__( 'You have reached your monthly limit. Please upgrade your plan to generate more meta tags.', 'seo-ai-meta-generator' )
			);
		}

		return true;
	}

	/**
	 * Check if user should see upgrade prompt
	 *
	 * @return bool
	 */
	public static function should_show_upgrade_prompt() {
		$usage = SEO_AI_Meta_Usage_Tracker::get_stats_display();
		$percentage = ( $usage['used'] / max( $usage['limit'], 1 ) ) * 100;
		return $percentage >= 80;
	}

	/**
	 * Get upgrade URL
	 *
	 * @return string
	 */
	public static function get_upgrade_url() {
		$default = 'https://alttextai.com/pricing'; // Reuse AltText AI pricing page or create new one
		$stored  = get_option( 'seo_ai_meta_upgrade_url', $default );
		return apply_filters( 'seo_ai_meta_upgrade_url', $stored ?: $default );
	}
}

