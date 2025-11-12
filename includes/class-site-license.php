<?php
/**
 * Site-Wide License Management
 *
 * Handles site-wide licensing model alongside per-user authentication.
 * This class manages site API keys, site-wide usage tracking, and site-level billing.
 *
 * @package    SEO_AI_Meta
 * @subpackage SEO_AI_Meta/includes
 * @since      1.2.0
 */

class SEO_AI_Meta_Site_License {

	/**
	 * License modes
	 */
	const MODE_PER_USER = 'per-user';
	const MODE_SITE_WIDE = 'site-wide';

	/**
	 * Settings keys
	 */
	const SETTING_LICENSE_MODE = 'license_mode';
	const SETTING_SITE_API_KEY = 'site_api_key';
	const SETTING_SITE_DATA = 'site_license_data';
	const SETTING_SITE_USAGE = 'site_usage_data';

	/**
	 * Get the current license mode
	 *
	 * @return string Either 'per-user' or 'site-wide'
	 */
	public static function get_license_mode() {
		$mode = SEO_AI_Meta_Database::get_setting( self::SETTING_LICENSE_MODE );

		// Default to per-user for backward compatibility
		if ( empty( $mode ) ) {
			return self::MODE_PER_USER;
		}

		return $mode;
	}

	/**
	 * Set the license mode
	 *
	 * @param string $mode Either 'per-user' or 'site-wide'
	 * @return bool Success status
	 */
	public static function set_license_mode( $mode ) {
		// Validate mode
		if ( ! in_array( $mode, array( self::MODE_PER_USER, self::MODE_SITE_WIDE ), true ) ) {
			return false;
		}

		return SEO_AI_Meta_Database::update_setting( self::SETTING_LICENSE_MODE, $mode );
	}

	/**
	 * Check if site-wide mode is active
	 *
	 * @return bool True if site-wide mode is active
	 */
	public static function is_site_wide_mode() {
		return self::get_license_mode() === self::MODE_SITE_WIDE;
	}

	/**
	 * Check if per-user mode is active
	 *
	 * @return bool True if per-user mode is active
	 */
	public static function is_per_user_mode() {
		return self::get_license_mode() === self::MODE_PER_USER;
	}

	/**
	 * Get the site API key
	 *
	 * @return string|null The site API key or null if not set
	 */
	public static function get_site_api_key() {
		$api_key = SEO_AI_Meta_Database::get_setting( self::SETTING_SITE_API_KEY );

		if ( empty( $api_key ) ) {
			return null;
		}

		// Decrypt if stored encrypted (optional security enhancement)
		return $api_key;
	}

	/**
	 * Set the site API key
	 *
	 * @param string $api_key The site API key
	 * @return bool Success status
	 */
	public static function set_site_api_key( $api_key ) {
		// Validate API key format (basic validation)
		if ( empty( $api_key ) || strlen( $api_key ) < 20 ) {
			return false;
		}

		// Encrypt if needed (optional security enhancement)
		return SEO_AI_Meta_Database::update_setting( self::SETTING_SITE_API_KEY, $api_key );
	}

	/**
	 * Clear the site API key
	 *
	 * @return bool Success status
	 */
	public static function clear_site_api_key() {
		SEO_AI_Meta_Database::delete_setting( self::SETTING_SITE_API_KEY );
		SEO_AI_Meta_Database::delete_setting( self::SETTING_SITE_DATA );
		SEO_AI_Meta_Database::delete_setting( self::SETTING_SITE_USAGE );
		return true;
	}

	/**
	 * Check if site has a valid API key
	 *
	 * @return bool True if site has an API key
	 */
	public static function has_site_api_key() {
		$api_key = self::get_site_api_key();
		return ! empty( $api_key );
	}

	/**
	 * Get site license data (plan, usage limits, etc.)
	 *
	 * @return array Site license data
	 */
	public static function get_site_data() {
		$data = SEO_AI_Meta_Database::get_setting( self::SETTING_SITE_DATA );

		if ( empty( $data ) ) {
			return array(
				'plan'        => 'free',
				'usage_limit' => 10,
				'usage_count' => 0,
				'reset_date'  => null,
			);
		}

		// Decode if stored as JSON
		if ( is_string( $data ) ) {
			$data = json_decode( $data, true );
		}

		return $data;
	}

	/**
	 * Update site license data
	 *
	 * @param array $data Site license data
	 * @return bool Success status
	 */
	public static function update_site_data( $data ) {
		// Encode as JSON for storage
		$json_data = json_encode( $data );
		return SEO_AI_Meta_Database::update_setting( self::SETTING_SITE_DATA, $json_data );
	}

	/**
	 * Get site usage data
	 *
	 * @return array Usage data with count and reset date
	 */
	public static function get_site_usage() {
		$usage = SEO_AI_Meta_Database::get_setting( self::SETTING_SITE_USAGE );

		if ( empty( $usage ) ) {
			return array(
				'count'      => 0,
				'reset_date' => self::get_next_reset_date(),
				'last_used'  => null,
			);
		}

		// Decode if stored as JSON
		if ( is_string( $usage ) ) {
			$usage = json_decode( $usage, true );
		}

		return $usage;
	}

	/**
	 * Update site usage data
	 *
	 * @param array $usage Usage data
	 * @return bool Success status
	 */
	public static function update_site_usage( $usage ) {
		// Encode as JSON for storage
		$json_usage = json_encode( $usage );
		return SEO_AI_Meta_Database::update_setting( self::SETTING_SITE_USAGE, $json_usage );
	}

	/**
	 * Increment site usage count
	 *
	 * @param int|null $post_id Optional post ID for logging
	 * @return bool Success status
	 */
	public static function increment_site_usage( $post_id = null ) {
		$usage = self::get_site_usage();

		// Check if reset is needed
		if ( self::should_reset_usage( $usage['reset_date'] ) ) {
			$usage['count'] = 0;
			$usage['reset_date'] = self::get_next_reset_date();
		}

		// Increment count
		$usage['count']++;
		$usage['last_used'] = current_time( 'mysql' );

		// Log the usage (for analytics and debugging)
		self::log_site_usage( $post_id );

		return self::update_site_usage( $usage );
	}

	/**
	 * Check if usage should be reset
	 *
	 * @param string|null $reset_date The reset date
	 * @return bool True if usage should be reset
	 */
	private static function should_reset_usage( $reset_date ) {
		if ( empty( $reset_date ) ) {
			return true;
		}

		$reset_timestamp = strtotime( $reset_date );
		$current_timestamp = current_time( 'timestamp' );

		return $current_timestamp >= $reset_timestamp;
	}

	/**
	 * Get the next reset date (first day of next month)
	 *
	 * @return string Reset date in Y-m-d format
	 */
	private static function get_next_reset_date() {
		return date( 'Y-m-01', strtotime( 'first day of next month' ) );
	}

	/**
	 * Log site usage for analytics
	 *
	 * @param int|null $post_id The post ID
	 * @return void
	 */
	private static function log_site_usage( $post_id = null ) {
		$user_id = get_current_user_id();

		// Log to usage log table for detailed tracking
		SEO_AI_Meta_Database::log_usage(
			0, // Site-wide usage, no specific user
			$post_id,
			'site_wide',
			array(
				'actual_user_id' => $user_id, // Track which WP user generated it
				'site_mode'      => true,
			)
		);
	}

	/**
	 * Get site usage limit based on plan
	 *
	 * @return int Usage limit
	 */
	public static function get_site_usage_limit() {
		$site_data = self::get_site_data();
		return isset( $site_data['usage_limit'] ) ? (int) $site_data['usage_limit'] : 10;
	}

	/**
	 * Check if site has remaining usage
	 *
	 * @return bool True if site has usage remaining
	 */
	public static function has_usage_remaining() {
		$usage = self::get_site_usage();
		$limit = self::get_site_usage_limit();

		// Check if reset is needed
		if ( self::should_reset_usage( $usage['reset_date'] ) ) {
			return true; // Will be reset on next increment
		}

		return $usage['count'] < $limit;
	}

	/**
	 * Get remaining usage count
	 *
	 * @return int Remaining usage count
	 */
	public static function get_remaining_usage() {
		$usage = self::get_site_usage();
		$limit = self::get_site_usage_limit();

		// Check if reset is needed
		if ( self::should_reset_usage( $usage['reset_date'] ) ) {
			return $limit;
		}

		$remaining = $limit - $usage['count'];
		return max( 0, $remaining );
	}

	/**
	 * Check if site license is authenticated (has API key and it's valid)
	 *
	 * @return bool True if site is authenticated
	 */
	public static function is_site_authenticated() {
		if ( ! self::is_site_wide_mode() ) {
			return false;
		}

		if ( ! self::has_site_api_key() ) {
			return false;
		}

		// Additional validation could be added here (e.g., verify with backend)
		return true;
	}

	/**
	 * Get site plan name
	 *
	 * @return string Plan name (free, pro, agency)
	 */
	public static function get_site_plan() {
		$site_data = self::get_site_data();
		return isset( $site_data['plan'] ) ? $site_data['plan'] : 'free';
	}

	/**
	 * Check if user can manage site license
	 *
	 * @param int|null $user_id Optional user ID, defaults to current user
	 * @return bool True if user can manage license
	 */
	public static function user_can_manage_license( $user_id = null ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		// Check if user is admin or has specific capability
		return user_can( $user_id, 'manage_options' ) || user_can( $user_id, 'manage_seo_ai_license' );
	}

	/**
	 * Validate API key format
	 *
	 * @param string $api_key The API key to validate
	 * @return bool True if valid format
	 */
	public static function validate_api_key_format( $api_key ) {
		// Basic validation - adjust based on your backend's key format
		if ( empty( $api_key ) ) {
			return false;
		}

		// Check minimum length
		if ( strlen( $api_key ) < 20 ) {
			return false;
		}

		// Check for common prefixes (adjust to match your backend)
		$valid_prefixes = array( 'sk_live_', 'sk_test_', 'api_' );
		$has_valid_prefix = false;

		foreach ( $valid_prefixes as $prefix ) {
			if ( strpos( $api_key, $prefix ) === 0 ) {
				$has_valid_prefix = true;
				break;
			}
		}

		return $has_valid_prefix;
	}

	/**
	 * Get authentication status summary
	 *
	 * @return array Status information
	 */
	public static function get_auth_status() {
		$mode = self::get_license_mode();

		if ( $mode === self::MODE_SITE_WIDE ) {
			$is_authenticated = self::is_site_authenticated();
			$usage = self::get_site_usage();
			$limit = self::get_site_usage_limit();

			return array(
				'mode'            => 'site-wide',
				'authenticated'   => $is_authenticated,
				'has_api_key'     => self::has_site_api_key(),
				'plan'            => self::get_site_plan(),
				'usage_count'     => $usage['count'],
				'usage_limit'     => $limit,
				'remaining'       => self::get_remaining_usage(),
				'reset_date'      => $usage['reset_date'],
			);
		} else {
			// Per-user mode - return user-specific status
			$user_id = get_current_user_id();
			$api_client = new SEO_AI_Meta_API_Client_V2();

			return array(
				'mode'            => 'per-user',
				'authenticated'   => $api_client->is_authenticated(),
				'user_id'         => $user_id,
			);
		}
	}

	/**
	 * Switch from per-user to site-wide mode
	 *
	 * @param string $site_api_key The site API key
	 * @return array Result with success status and message
	 */
	public static function switch_to_site_wide( $site_api_key ) {
		// Validate API key format
		if ( ! self::validate_api_key_format( $site_api_key ) ) {
			return array(
				'success' => false,
				'message' => 'Invalid API key format.',
			);
		}

		// Set the API key
		if ( ! self::set_site_api_key( $site_api_key ) ) {
			return array(
				'success' => false,
				'message' => 'Failed to save API key.',
			);
		}

		// Switch mode
		if ( ! self::set_license_mode( self::MODE_SITE_WIDE ) ) {
			return array(
				'success' => false,
				'message' => 'Failed to switch mode.',
			);
		}

		return array(
			'success' => true,
			'message' => 'Successfully switched to site-wide licensing.',
		);
	}

	/**
	 * Switch from site-wide to per-user mode
	 *
	 * @return array Result with success status and message
	 */
	public static function switch_to_per_user() {
		// Switch mode (but keep the API key in case they want to switch back)
		if ( ! self::set_license_mode( self::MODE_PER_USER ) ) {
			return array(
				'success' => false,
				'message' => 'Failed to switch mode.',
			);
		}

		return array(
			'success' => true,
			'message' => 'Successfully switched to per-user licensing.',
		);
	}
}
