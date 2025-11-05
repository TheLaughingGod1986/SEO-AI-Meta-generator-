<?php
/**
 * Update Price IDs to Live Mode
 *
 * This script updates all stored price IDs from test mode to live mode.
 * Run this once to update the database.
 */

// Load WordPress
require_once dirname( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) ) . '/wp-load.php';

// Load plugin classes
require_once __DIR__ . '/includes/class-seo-ai-meta-database.php';

echo "SEO AI Meta Generator - Update Price IDs to Live Mode\n";
echo "=====================================================\n\n";

// New live mode price IDs
$new_price_ids = array(
	'pro'     => 'price_1SQ72OJl9Rm418cMruYB5Pgb', // SEO AI Meta Pro - £12.99/month (LIVE)
	'agency'  => 'price_1SQ72KJl9Rm418cMB0CYh8xe', // SEO AI Meta Agency - £49.99/month (LIVE)
);

// Get current price IDs from database
$current_price_ids = SEO_AI_Meta_Database::get_setting( 'price_ids', null );

echo "Current Price IDs:\n";
if ( $current_price_ids && is_array( $current_price_ids ) ) {
	echo "  Pro:    " . ( $current_price_ids['pro'] ?? 'Not set' ) . "\n";
	echo "  Agency: " . ( $current_price_ids['agency'] ?? 'Not set' ) . "\n";
} else {
	echo "  Not set in database\n";
}

echo "\nNew Price IDs (LIVE mode):\n";
echo "  Pro:    " . $new_price_ids['pro'] . "\n";
echo "  Agency: " . $new_price_ids['agency'] . "\n";

echo "\nUpdating database...\n";

// Update database
$result = SEO_AI_Meta_Database::update_setting( 'price_ids', $new_price_ids );

if ( $result ) {
	echo "✓ Successfully updated price IDs in database\n";

	// Verify the update
	$verified = SEO_AI_Meta_Database::get_setting( 'price_ids', null );
	echo "\nVerified Price IDs:\n";
	echo "  Pro:    " . ( $verified['pro'] ?? 'Not set' ) . "\n";
	echo "  Agency: " . ( $verified['agency'] ?? 'Not set' ) . "\n";

	// Also update WordPress options table as fallback
	update_option( 'seo_ai_meta_price_ids', $new_price_ids );
	echo "\n✓ Also updated WordPress options table\n";

	echo "\n========================================\n";
	echo "Update complete! Please refresh your WordPress admin page.\n";
} else {
	echo "✗ Failed to update price IDs\n";
}
