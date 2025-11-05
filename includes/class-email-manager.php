<?php
/**
 * Email Manager for SEO AI Meta Generator
 * Handles Resend API integration for onboarding emails
 *
 * @package    SEO_AI_Meta
 * @subpackage SEO_AI_Meta/includes
 */
class SEO_AI_Meta_Email_Manager {

	/**
	 * Backend API URL
	 *
	 * @var string
	 */
	private $api_url;

	/**
	 * Constructor
	 */
	public function __construct() {
		// Use same backend as AltText AI (can be shared or separate)
		$production_url = 'https://alttext-ai-backend.onrender.com';

		if ( defined( 'SEO_AI_META_API_URL' ) ) {
			$this->api_url = SEO_AI_META_API_URL;
		} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_LOCAL_DEV' ) && WP_LOCAL_DEV ) {
			$this->api_url = 'http://host.docker.internal:3001';
		} else {
			$this->api_url = $production_url;
		}
	}

	/**
	 * Send welcome email on first generation
	 *
	 * @param int $user_id User ID.
	 */
	public function send_welcome_email( $user_id = null ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		$user = get_userdata( $user_id );
		if ( ! $user || ! is_email( $user->user_email ) ) {
			return false;
		}

		// Check if welcome email already sent
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-database.php';
		$user_data = SEO_AI_Meta_Database::get_user_data( $user_id );
		$welcome_sent = $user_data ? (bool) $user_data['welcome_sent'] : false;
		
		// Fallback to WordPress user meta for backward compatibility
		if ( ! $welcome_sent ) {
			$welcome_sent = get_user_meta( $user_id, 'seo_ai_meta_welcome_sent', true );
		}
		
		if ( $welcome_sent ) {
			return false;
		}

		// Send via backend API (Resend)
		$response = wp_remote_post(
			$this->api_url . '/email/welcome',
			array(
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body'    => wp_json_encode( array(
					'email'    => $user->user_email,
					'name'     => $user->display_name,
					'site_url' => home_url(),
				) ),
				'timeout' => 10,
			)
		);

		if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
			// Update custom database
			$data = $user_data ? $user_data : array();
			$data['welcome_sent'] = 1;
			SEO_AI_Meta_Database::update_user_data( $user_id, $data );
			
			// Also update WordPress user meta for backward compatibility
			update_user_meta( $user_id, 'seo_ai_meta_welcome_sent', true );
			return true;
		}

		return false;
	}

	/**
	 * Send usage limit warning email (80% threshold)
	 *
	 * @param int $user_id User ID.
	 * @param int $percentage Usage percentage.
	 */
	public function send_limit_warning_email( $user_id = null, $percentage = 80 ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		$user = get_userdata( $user_id );
		if ( ! $user || ! is_email( $user->user_email ) ) {
			return false;
		}

		// Check if warning already sent for this month
		$warning_sent = get_user_meta( $user_id, 'seo_ai_meta_warning_sent_' . date( 'Y-m' ), true );
		if ( $warning_sent ) {
			return false;
		}

		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-usage-tracker.php';
		$usage_stats = SEO_AI_Meta_Usage_Tracker::get_stats_display();

		// Send via backend API (Resend)
		$response = wp_remote_post(
			$this->api_url . '/email/usage-warning',
			array(
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body'    => wp_json_encode( array(
					'email'      => $user->user_email,
					'name'       => $user->display_name,
					'percentage' => $percentage,
					'used'       => $usage_stats['used'],
					'limit'      => $usage_stats['limit'],
					'remaining'  => $usage_stats['remaining'],
				) ),
				'timeout' => 10,
			)
		);

		if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
			update_user_meta( $user_id, 'seo_ai_meta_warning_sent_' . date( 'Y-m' ), true );
			return true;
		}

		return false;
	}

	/**
	 * Send monthly summary email (optional)
	 *
	 * @param int $user_id User ID.
	 */
	public function send_monthly_summary( $user_id = null ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		$user = get_userdata( $user_id );
		if ( ! $user || ! is_email( $user->user_email ) ) {
			return false;
		}

		// Check user preference
		$email_preferences = get_user_meta( $user_id, 'seo_ai_meta_email_preferences', true );
		if ( ! $email_preferences || ! isset( $email_preferences['monthly_summary'] ) || ! $email_preferences['monthly_summary'] ) {
			return false;
		}

		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-usage-tracker.php';
		$usage_stats = SEO_AI_Meta_Usage_Tracker::get_stats_display();

		// Send via backend API (Resend)
		$response = wp_remote_post(
			$this->api_url . '/email/monthly-summary',
			array(
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body'    => wp_json_encode( array(
					'email'     => $user->user_email,
					'name'      => $user->display_name,
					'used'      => $usage_stats['used'],
					'limit'     => $usage_stats['limit'],
					'plan'      => $usage_stats['plan'],
					'site_url'  => home_url(),
				) ),
				'timeout' => 10,
			)
		);

		return ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200;
	}
}

