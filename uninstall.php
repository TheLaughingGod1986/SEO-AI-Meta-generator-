<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package    SEO_AI_Meta
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Load database class
require_once plugin_dir_path( __FILE__ ) . 'includes/class-seo-ai-meta-database.php';

// Drop all custom database tables
SEO_AI_Meta_Database::drop_tables();

// Delete all plugin options (legacy - for backward compatibility)
delete_option( 'seo_ai_meta_settings' );
delete_option( 'seo_ai_meta_jwt_token' );
delete_option( 'seo_ai_meta_user_data' );
delete_option( 'seo_ai_meta_price_ids' );
delete_option( 'seo_ai_meta_db_version' );
delete_option( 'seo_ai_meta_migration_complete' );

// Delete all user meta related to this plugin (legacy cleanup)
// Get all users - use get_users() with limit to avoid memory issues
$users = get_users( array( 'number' => -1 ) );
foreach ( $users as $user ) {
	delete_user_meta( $user->ID, 'seo_ai_meta_plan' );
	delete_user_meta( $user->ID, 'seo_ai_meta_usage_count' );
	delete_user_meta( $user->ID, 'seo_ai_meta_reset_date' );
	delete_user_meta( $user->ID, 'seo_ai_meta_welcome_sent' );
}

// Delete all post meta (optional - comment out if you want to preserve generated meta)
global $wpdb;
$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_seo_ai_meta_%'" );

// Clear transients
delete_transient( 'seo_ai_meta_usage_cache' );

