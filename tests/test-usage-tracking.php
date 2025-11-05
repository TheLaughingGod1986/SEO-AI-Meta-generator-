<?php
/**
 * Test Usage Tracking
 *
 * @package    SEO_AI_Meta
 * @subpackage SEO_AI_Meta/tests
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Test usage tracking functionality
 */
function test_seo_ai_meta_usage_tracking() {
	require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-usage-tracker.php';

	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		return;
	}

	echo "=== Usage Tracking Test ===\n\n";

	// Get current usage
	$usage = SEO_AI_Meta_Usage_Tracker::get_cached_usage();
	echo "Current Plan: " . $usage['plan'] . "\n";
	echo "Current Usage: " . $usage['used'] . " / " . $usage['limit'] . "\n";
	echo "Remaining: " . $usage['remaining'] . "\n";
	echo "Reset Date: " . $usage['resetDate'] . "\n\n";

	// Get stats display
	$stats = SEO_AI_Meta_Usage_Tracker::get_stats_display();
	echo "Usage Percentage: " . $stats['percentage'] . "%\n";
	echo "Days Until Reset: " . $stats['days_until_reset'] . "\n\n";

	// Test increment
	echo "Testing usage increment...\n";
	SEO_AI_Meta_Usage_Tracker::increment_usage( $user_id );
	
	$new_usage = SEO_AI_Meta_Usage_Tracker::get_cached_usage( true );
	echo "New Usage: " . $new_usage['used'] . " / " . $new_usage['limit'] . "\n\n";

	// Check limit
	$at_limit = SEO_AI_Meta_Usage_Tracker::is_at_limit();
	echo "At Limit: " . ( $at_limit ? 'Yes' : 'No' ) . "\n\n";

	echo "=== Test Complete ===\n";
}

// Only run in CLI or admin
if ( defined( 'WP_CLI' ) || ( is_admin() && current_user_can( 'manage_options' ) ) ) {
	// Uncomment to run: test_seo_ai_meta_usage_tracking();
}

