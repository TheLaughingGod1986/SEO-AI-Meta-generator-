<?php
/**
 * Usage Tracker for SEO AI Meta Generator
 * Tracks usage via usermeta with monthly reset logic
 *
 * @package    SEO_AI_Meta
 * @subpackage SEO_AI_Meta/includes
 */
class SEO_AI_Meta_Usage_Tracker {

	/**
	 * Cache key
	 */
	const CACHE_KEY = 'seo_ai_meta_usage_cache';

	/**
	 * Cache expiry (5 minutes)
	 */
	const CACHE_EXPIRY = 300;

	/**
	 * Plan limits
	 */
	const PLAN_LIMITS = array(
		'free'    => 10,
		'pro'     => 100,
		'agency'  => 1000,
	);

	/**
	 * Update cached usage data
	 *
	 * @param array $usage_data Usage data from API.
	 */
	public static function update_usage( $usage_data ) {
		if ( ! is_array( $usage_data ) ) {
			return;
		}

		$used  = isset( $usage_data['used'] ) ? max( 0, intval( $usage_data['used'] ) ) : 0;
		$limit = isset( $usage_data['limit'] ) ? intval( $usage_data['limit'] ) : self::PLAN_LIMITS['free'];
		if ( $limit <= 0 ) {
			$limit = self::PLAN_LIMITS['free'];
		}
		$remaining = isset( $usage_data['remaining'] ) ? intval( $usage_data['remaining'] ) : ( $limit - $used );
		if ( $remaining < 0 ) {
			$remaining = 0;
		}

		$current_ts = current_time( 'timestamp' );
		$reset_raw  = $usage_data['resetDate'] ?? '';
		$reset_ts   = isset( $usage_data['resetTimestamp'] ) ? intval( $usage_data['resetTimestamp'] ) : 0;
		if ( $reset_ts <= 0 && $reset_raw ) {
			$reset_ts = strtotime( $reset_raw );
		}
		if ( $reset_ts <= 0 ) {
			$reset_ts = strtotime( 'first day of next month', $current_ts );
		}
		$seconds_until_reset = max( 0, $reset_ts - $current_ts );

		$normalized = array(
			'used'                => $used,
			'limit'               => $limit,
			'remaining'           => $remaining,
			'plan'                => $usage_data['plan'] ?? 'free',
			'resetDate'           => $reset_raw ?: date( 'Y-m-01', strtotime( '+1 month', $current_ts ) ),
			'reset_timestamp'      => $reset_ts,
			'seconds_until_reset' => $seconds_until_reset,
		);
		set_transient( self::CACHE_KEY, $normalized, self::CACHE_EXPIRY );
	}

	/**
	 * Get cached usage data
	 *
	 * Supports both per-user and site-wide licensing modes
	 *
	 * @param bool $force_refresh Force refresh from API.
	 * @return array
	 */
	public static function get_cached_usage( $force_refresh = false ) {
		if ( $force_refresh ) {
			delete_transient( self::CACHE_KEY );
		}

		$cached = get_transient( self::CACHE_KEY );

		if ( $cached === false ) {
			require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-database.php';
			require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-site-license.php';

			// Check if using site-wide licensing
			if ( SEO_AI_Meta_Site_License::is_site_wide_mode() ) {
				return self::get_site_wide_usage();
			}

			// Per-user mode (existing logic)
			$user_id = get_current_user_id();
			$user_data = SEO_AI_Meta_Database::get_user_data( $user_id );

			// Fallback to WordPress user meta for backward compatibility
			if ( ! $user_data ) {
				$plan      = get_user_meta( $user_id, 'seo_ai_meta_plan', true ) ?: 'free';
				$used      = intval( get_user_meta( $user_id, 'seo_ai_meta_usage_count', true ) ) ?: 0;
				$reset_date_str = get_user_meta( $user_id, 'seo_ai_meta_reset_date', true );
				$reset_ts  = is_numeric( $reset_date_str ) ? intval( $reset_date_str ) : ( $reset_date_str ? strtotime( $reset_date_str ) : strtotime( 'first day of next month' ) );
			} else {
				$plan = $user_data['plan'] ?: 'free';
				$used = intval( $user_data['usage_count'] ) ?: 0;
				$reset_date_str = $user_data['reset_date'];
				$reset_ts = $reset_date_str ? ( is_numeric( $reset_date_str ) ? intval( $reset_date_str ) : strtotime( $reset_date_str ) ) : strtotime( 'first day of next month' );
			}

			$limit     = self::PLAN_LIMITS[ $plan ] ?? self::PLAN_LIMITS['free'];
			$reset_date = date( 'Y-m-01', $reset_ts );

			return array(
				'used'                => $used,
				'limit'               => $limit,
				'remaining'           => max( 0, $limit - $used ),
				'plan'                => $plan,
				'resetDate'           => $reset_date,
				'reset_timestamp'     => $reset_ts,
				'seconds_until_reset' => max( 0, $reset_ts - current_time( 'timestamp' ) ),
			);
		}

		return $cached;
	}

	/**
	 * Get site-wide usage data
	 *
	 * @return array
	 */
	private static function get_site_wide_usage() {
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-site-license.php';

		$site_usage = SEO_AI_Meta_Site_License::get_site_usage();
		$limit = SEO_AI_Meta_Site_License::get_site_usage_limit();
		$plan = SEO_AI_Meta_Site_License::get_site_plan();
		$used = isset( $site_usage['count'] ) ? intval( $site_usage['count'] ) : 0;
		$reset_date = isset( $site_usage['reset_date'] ) ? $site_usage['reset_date'] : date( 'Y-m-01', strtotime( 'first day of next month' ) );
		$reset_ts = strtotime( $reset_date );

		return array(
			'used'                => $used,
			'limit'               => $limit,
			'remaining'           => max( 0, $limit - $used ),
			'plan'                => $plan,
			'resetDate'           => $reset_date,
			'reset_timestamp'     => $reset_ts,
			'seconds_until_reset' => max( 0, $reset_ts - current_time( 'timestamp' ) ),
		);
	}

	/**
	 * Check if user is at limit
	 *
	 * @return bool
	 */
	public static function is_at_limit() {
		$usage = self::get_cached_usage();
		return $usage['remaining'] <= 0;
	}

	/**
	 * Get usage stats for display
	 *
	 * @param bool $force_refresh Force refresh.
	 * @return array
	 */
	public static function get_stats_display( $force_refresh = false ) {
		$usage = self::get_cached_usage( $force_refresh );
		$limit = max( 1, intval( $usage['limit'] ) );
		$used  = max( 0, intval( $usage['used'] ) );
		if ( $used > $limit ) {
			$used = $limit;
		}
		$remaining = max( 0, $limit - $used );

		$reset_timestamp = isset( $usage['reset_timestamp'] ) ? intval( $usage['reset_timestamp'] ) : 0;
		if ( $reset_timestamp <= 0 && ! empty( $usage['resetDate'] ) ) {
			$reset_timestamp = strtotime( $usage['resetDate'] );
		}
		if ( $reset_timestamp <= 0 ) {
			$reset_timestamp = strtotime( 'first day of next month', current_time( 'timestamp' ) );
		}

		$current_timestamp   = current_time( 'timestamp' );
		$seconds_until_reset = $reset_timestamp > 0 ? max( 0, $reset_timestamp - $current_timestamp ) : 0;
		$days_until_reset   = (int) floor( $seconds_until_reset / DAY_IN_SECONDS );

		if ( $seconds_until_reset <= 0 ) {
			$end_of_day        = strtotime( 'tomorrow', $current_timestamp ) - 1;
			$seconds_until_reset = max( 0, $end_of_day - $current_timestamp );
			$days_until_reset   = (int) floor( $seconds_until_reset / DAY_IN_SECONDS );
		}

		return array(
			'used'                => $used,
			'limit'               => $limit,
			'remaining'           => $remaining,
			'percentage'         => min( 100, round( ( $used / max( $limit, 1 ) ) * 100 ) ),
			'plan'                => $usage['plan'],
			'plan_label'          => ucfirst( $usage['plan'] ),
			'reset_date'          => $reset_timestamp ? date( 'F j, Y', $reset_timestamp ) : date( 'F j, Y', strtotime( $usage['resetDate'] ) ),
			'days_until_reset'    => $days_until_reset,
			'seconds_until_reset' => $seconds_until_reset,
			'is_free'             => $usage['plan'] === 'free',
			'is_pro'              => $usage['plan'] === 'pro',
		);
	}

	/**
	 * Increment usage count
	 *
	 * Supports both per-user and site-wide licensing modes
	 *
	 * @param int $user_id User ID.
	 * @param int $post_id Post ID.
	 * @param string $model Model used.
	 */
	public static function increment_usage( $user_id = null, $post_id = null, $model = null ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-database.php';
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-site-license.php';

		// Check if using site-wide licensing
		if ( SEO_AI_Meta_Site_License::is_site_wide_mode() ) {
			// Increment site-wide usage
			SEO_AI_Meta_Site_License::increment_site_usage( $post_id );

			// Still log which WP user generated it (for accountability/analytics)
			SEO_AI_Meta_Database::log_usage( $user_id, $post_id, 'generate', $model );
		} else {
			// Per-user mode (existing logic)
			// Check if reset is needed
			self::maybe_reset_usage( $user_id );

			// Get current usage from custom database
			$user_data = SEO_AI_Meta_Database::get_user_data( $user_id );
			$current_count = $user_data ? intval( $user_data['usage_count'] ) : 0;

			// Fallback to WordPress user meta for backward compatibility
			if ( $current_count === 0 ) {
				$current_count = intval( get_user_meta( $user_id, 'seo_ai_meta_usage_count', true ) ) ?: 0;
			}

			$new_count = $current_count + 1;

			// Update custom database
			$data = $user_data ? $user_data : array();
			$data['usage_count'] = $new_count;
			SEO_AI_Meta_Database::update_user_data( $user_id, $data );

			// Also update WordPress user meta for backward compatibility
			update_user_meta( $user_id, 'seo_ai_meta_usage_count', $new_count );

			// Log usage
			SEO_AI_Meta_Database::log_usage( $user_id, $post_id, 'generate', $model );
		}

		// Clear cache
		delete_transient( self::CACHE_KEY );
	}

	/**
	 * Maybe reset usage if new month
	 *
	 * @param int $user_id User ID.
	 */
	private static function maybe_reset_usage( $user_id ) {
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-database.php';
		
		$user_data = SEO_AI_Meta_Database::get_user_data( $user_id );
		$reset_date_str = $user_data ? $user_data['reset_date'] : null;
		
		// Fallback to WordPress user meta for backward compatibility
		if ( ! $reset_date_str ) {
			$reset_date_str = get_user_meta( $user_id, 'seo_ai_meta_reset_date', true );
		}
		
		$reset_date = is_numeric( $reset_date_str ) ? intval( $reset_date_str ) : ( $reset_date_str ? strtotime( $reset_date_str ) : 0 );
		$now        = current_time( 'timestamp' );

		if ( $reset_date <= 0 || $now >= $reset_date ) {
			// Reset usage and set new reset date
			$new_reset_date = strtotime( 'first day of next month', $now );
			$data = $user_data ? $user_data : array();
			$data['usage_count'] = 0;
			$data['reset_date'] = date( 'Y-m-d', $new_reset_date );
			SEO_AI_Meta_Database::update_user_data( $user_id, $data );
			
			// Also update WordPress user meta for backward compatibility
			update_user_meta( $user_id, 'seo_ai_meta_usage_count', 0 );
			update_user_meta( $user_id, 'seo_ai_meta_reset_date', $new_reset_date );
		}
	}
}

