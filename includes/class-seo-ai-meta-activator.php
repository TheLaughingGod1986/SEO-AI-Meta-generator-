<?php
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @package    SEO_AI_Meta
 * @subpackage SEO_AI_Meta/includes
 */
class SEO_AI_Meta_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		// Check PHP version
		if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die( esc_html__( 'SEO AI Meta Generator requires PHP 7.4 or higher. Please upgrade your PHP version.', 'seo-ai-meta-generator' ) );
		}

		// Load database class
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-database.php';

		// Create custom database tables
		SEO_AI_Meta_Database::create_tables();

		// Set default settings
		$default_options = array(
			'openai_api_key'      => '',
			'default_model'       => 'gpt-4o-mini',
			'title_max_length'    => 60,
			'description_max_length' => 160,
			'tone'                => 'professional, engaging',
			'auto_generate'      => false,
		);

		// Initialize default price IDs
		$default_price_ids = array(
			'pro'     => 'price_1SQ72OJl9Rm418cMruYB5Pgb', // SEO AI Meta Pro - £12.99/month (LIVE)
			'agency'  => 'price_1SQ72KJl9Rm418cMB0CYh8xe', // SEO AI Meta Agency - £49.99/month (LIVE)
		);
		
		// Check if price IDs already exist
		$existing_price_ids = SEO_AI_Meta_Database::get_setting( 'price_ids', null );
		if ( $existing_price_ids === null || empty( $existing_price_ids ) || ! is_array( $existing_price_ids ) ) {
			// Try to get from WordPress options first
			$existing_price_ids = get_option( 'seo_ai_meta_price_ids', null );
			if ( $existing_price_ids === null || empty( $existing_price_ids ) || ! is_array( $existing_price_ids ) ) {
				// Use defaults
				$existing_price_ids = $default_price_ids;
			}
			SEO_AI_Meta_Database::update_setting( 'price_ids', $existing_price_ids );
		}

		// Check if we need to migrate from old storage
		$migration_complete = get_option( 'seo_ai_meta_migration_complete', false );
		if ( ! $migration_complete ) {
			// Try to get existing settings from WordPress options
			$existing_options = get_option( 'seo_ai_meta_settings', array() );
			if ( ! empty( $existing_options ) && is_array( $existing_options ) ) {
				// Merge with defaults
				$merged = wp_parse_args( $existing_options, $default_options );
				SEO_AI_Meta_Database::update_setting( 'settings', $merged );
				
				// Run full migration
				SEO_AI_Meta_Database::migrate_data();
			} else {
				// New installation - just set defaults
				SEO_AI_Meta_Database::update_setting( 'settings', $default_options );
			}
		} else {
			// Migration already done, ensure settings exist
			$current_settings = SEO_AI_Meta_Database::get_setting( 'settings', array() );
			if ( empty( $current_settings ) ) {
				SEO_AI_Meta_Database::update_setting( 'settings', $default_options );
			}
		}

		// Add capability to administrators
		$role = get_role( 'administrator' );
		if ( $role && ! $role->has_cap( 'manage_seo_ai_meta' ) ) {
			$role->add_cap( 'manage_seo_ai_meta' );
		}

		// Set default user plan (free) - only for current user during activation
		// Initialize for all users on first use, not during activation to avoid performance issues
		$current_user_id = get_current_user_id();
		if ( $current_user_id > 0 ) {
			$user_data = SEO_AI_Meta_Database::get_user_data( $current_user_id );
			
			// Check if user data exists, if not create it
			if ( ! $user_data ) {
				$reset_date = date( 'Y-m-d', strtotime( 'first day of next month' ) );
				SEO_AI_Meta_Database::update_user_data( $current_user_id, array(
					'plan' => 'free',
					'usage_count' => 0,
					'reset_date' => $reset_date,
				) );
			} else {
				// Ensure defaults are set
				$data = $user_data;
				if ( empty( $data['plan'] ) ) {
					$data['plan'] = 'free';
				}
				if ( ! isset( $data['usage_count'] ) ) {
					$data['usage_count'] = 0;
				}
				if ( empty( $data['reset_date'] ) ) {
					$data['reset_date'] = date( 'Y-m-d', strtotime( 'first day of next month' ) );
				}
				SEO_AI_Meta_Database::update_user_data( $current_user_id, $data );
			}
			
			// Also update WordPress user meta for backward compatibility
			if ( ! get_user_meta( $current_user_id, 'seo_ai_meta_plan', true ) ) {
				update_user_meta( $current_user_id, 'seo_ai_meta_plan', 'free' );
			}
			if ( ! get_user_meta( $current_user_id, 'seo_ai_meta_usage_count', true ) ) {
				update_user_meta( $current_user_id, 'seo_ai_meta_usage_count', 0 );
			}
			if ( ! get_user_meta( $current_user_id, 'seo_ai_meta_reset_date', true ) ) {
				$reset_date = strtotime( 'first day of next month' );
				update_user_meta( $current_user_id, 'seo_ai_meta_reset_date', $reset_date );
			}
		}

		// Clear any cached data
		delete_transient( 'seo_ai_meta_usage_cache' );
	}
}
