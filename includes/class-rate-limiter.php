<?php
/**
 * Rate Limiter for SEO AI Meta Generator
 * Prevents abuse and protects against API spam
 *
 * @package    SEO_AI_Meta
 * @subpackage SEO_AI_Meta/includes
 */
class SEO_AI_Meta_Rate_Limiter {

	/**
	 * Rate limit key prefix
	 */
	const RATE_LIMIT_PREFIX = 'seo_ai_meta_rate_limit_';

	/**
	 * Default rate limits
	 */
	const DEFAULT_LIMITS = array(
		'generate' => array(
			'requests' => 10,  // 10 requests
			'window'   => 60,  // per 60 seconds
		),
		'bulk' => array(
			'requests' => 5,   // 5 bulk operations
			'window'   => 300, // per 5 minutes
		),
	);

	/**
	 * Check if request is allowed
	 *
	 * Implements hybrid rate limiting:
	 * - Per-user soft limit (prevents single user abuse)
	 * - Site-wide hard limit (in site-wide mode only)
	 *
	 * @param string $action Action type (generate, bulk, etc.).
	 * @param int    $user_id User ID (optional, defaults to current user).
	 * @return bool|WP_Error
	 */
	public static function check_rate_limit( $action = 'generate', $user_id = null ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( ! $user_id ) {
			return new WP_Error( 'no_user', __( 'User not authenticated.', 'seo-ai-meta-generator' ) );
		}

		// Check per-user rate limit first (always applies)
		$per_user_check = self::check_per_user_rate_limit( $action, $user_id );
		if ( is_wp_error( $per_user_check ) ) {
			return $per_user_check;
		}

		// In site-wide mode, also check site-wide rate limit
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-site-license.php';
		if ( SEO_AI_Meta_Site_License::is_site_wide_mode() ) {
			$site_wide_check = self::check_site_wide_rate_limit( $action );
			if ( is_wp_error( $site_wide_check ) ) {
				return $site_wide_check;
			}
		}

		return true;
	}

	/**
	 * Check per-user rate limit (soft limit)
	 *
	 * @param string $action Action type.
	 * @param int    $user_id User ID.
	 * @return bool|WP_Error
	 */
	private static function check_per_user_rate_limit( $action, $user_id ) {
		// Get limits for this action
		$limits = self::get_limits( $action );
		$cache_key = self::RATE_LIMIT_PREFIX . $action . '_user_' . $user_id;

		// Get current count
		$current = get_transient( $cache_key );
		if ( $current === false ) {
			// First request in this window
			set_transient( $cache_key, 1, $limits['window'] );
			return true;
		}

		// Check if limit exceeded
		if ( intval( $current ) >= $limits['requests'] ) {
			$remaining = self::get_remaining_time( $cache_key );
			return new WP_Error(
				'user_rate_limit_exceeded',
				sprintf(
					/* translators: %1$d: number of requests, %2$d: time window, %3$d: seconds remaining */
					__( 'Personal rate limit exceeded. Maximum %1$d requests per %2$d seconds. Please wait %3$d seconds before trying again.', 'seo-ai-meta-generator' ),
					$limits['requests'],
					$limits['window'],
					$remaining
				),
				array(
					'limit'     => $limits['requests'],
					'window'    => $limits['window'],
					'remaining' => $remaining,
					'scope'     => 'user',
				)
			);
		}

		// Increment count
		$count = intval( $current ) + 1;
		$remaining_time = self::get_remaining_time( $cache_key );
		set_transient( $cache_key, $count, $remaining_time > 0 ? $remaining_time : $limits['window'] );

		return true;
	}

	/**
	 * Check site-wide rate limit (hard limit)
	 *
	 * @param string $action Action type.
	 * @return bool|WP_Error
	 */
	private static function check_site_wide_rate_limit( $action ) {
		// Get limits for this action (site-wide can have different limits)
		$limits = self::get_site_wide_limits( $action );
		$cache_key = self::RATE_LIMIT_PREFIX . $action . '_site';

		// Get current count
		$current = get_transient( $cache_key );
		if ( $current === false ) {
			// First request in this window
			set_transient( $cache_key, 1, $limits['window'] );
			return true;
		}

		// Check if limit exceeded
		if ( intval( $current ) >= $limits['requests'] ) {
			$remaining = self::get_remaining_time( $cache_key );
			return new WP_Error(
				'site_rate_limit_exceeded',
				sprintf(
					/* translators: %1$d: number of requests, %2$d: time window, %3$d: seconds remaining */
					__( 'Site-wide rate limit exceeded. Maximum %1$d requests per %2$d seconds across all users. Please wait %3$d seconds before trying again.', 'seo-ai-meta-generator' ),
					$limits['requests'],
					$limits['window'],
					$remaining
				),
				array(
					'limit'     => $limits['requests'],
					'window'    => $limits['window'],
					'remaining' => $remaining,
					'scope'     => 'site',
				)
			);
		}

		// Increment count
		$count = intval( $current ) + 1;
		$remaining_time = self::get_remaining_time( $cache_key );
		set_transient( $cache_key, $count, $remaining_time > 0 ? $remaining_time : $limits['window'] );

		return true;
	}

	/**
	 * Get limits for an action (per-user)
	 *
	 * @param string $action Action type.
	 * @return array
	 */
	private static function get_limits( $action ) {
		$limits = apply_filters( 'seo_ai_meta_rate_limits', self::DEFAULT_LIMITS );
		return isset( $limits[ $action ] ) ? $limits[ $action ] : self::DEFAULT_LIMITS['generate'];
	}

	/**
	 * Get site-wide limits for an action
	 *
	 * Site-wide limits are typically higher than per-user limits
	 * to allow multiple users to work simultaneously
	 *
	 * @param string $action Action type.
	 * @return array
	 */
	private static function get_site_wide_limits( $action ) {
		$site_wide_limits = array(
			'generate' => array(
				'requests' => 50,   // 50 requests total
				'window'   => 60,   // per 60 seconds (across all users)
			),
			'bulk' => array(
				'requests' => 20,   // 20 bulk operations
				'window'   => 300,  // per 5 minutes (across all users)
			),
		);

		$limits = apply_filters( 'seo_ai_meta_site_wide_rate_limits', $site_wide_limits );
		return isset( $limits[ $action ] ) ? $limits[ $action ] : $site_wide_limits['generate'];
	}

	/**
	 * Get remaining time for a transient
	 *
	 * @param string $cache_key Cache key.
	 * @return int Remaining seconds.
	 */
	private static function get_remaining_time( $cache_key ) {
		global $wpdb;
		$timeout = $wpdb->get_var( $wpdb->prepare(
			"SELECT TIMESTAMPDIFF(SECOND, NOW(), expiration) FROM {$wpdb->options} WHERE option_name = %s",
			'_transient_timeout_' . $cache_key
		) );
		return max( 0, intval( $timeout ) );
	}

	/**
	 * Reset rate limit for a user
	 *
	 * @param string $action Action type.
	 * @param int    $user_id User ID.
	 */
	public static function reset_rate_limit( $action = 'generate', $user_id = null ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( $user_id ) {
			$cache_key = self::RATE_LIMIT_PREFIX . $action . '_' . $user_id;
			delete_transient( $cache_key );
		}
	}

	/**
	 * Get rate limit status for a user
	 *
	 * @param string $action Action type.
	 * @param int    $user_id User ID.
	 * @return array
	 */
	public static function get_rate_limit_status( $action = 'generate', $user_id = null ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( ! $user_id ) {
			return array(
				'allowed'  => false,
				'current'  => 0,
				'limit'    => 0,
				'remaining' => 0,
				'reset_in' => 0,
			);
		}

		$limits = self::get_limits( $action );
		$cache_key = self::RATE_LIMIT_PREFIX . $action . '_' . $user_id;
		$current = get_transient( $cache_key );
		$current_count = $current !== false ? intval( $current ) : 0;
		$remaining_time = self::get_remaining_time( $cache_key );

		return array(
			'allowed'   => $current_count < $limits['requests'],
			'current'   => $current_count,
			'limit'     => $limits['requests'],
			'remaining' => max( 0, $limits['requests'] - $current_count ),
			'reset_in'  => $remaining_time,
		);
	}
}

