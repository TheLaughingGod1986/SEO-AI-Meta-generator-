<?php
/**
 * Test Billing Integration
 *
 * @package    SEO_AI_Meta
 * @subpackage SEO_AI_Meta/tests
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Test billing integration
 */
function test_seo_ai_meta_billing() {
	require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-api-client-v2.php';
	require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-core.php';

	echo "=== Billing Integration Test ===\n\n";

	$api_client = new SEO_AI_Meta_API_Client_V2();
	$core = new SEO_AI_Meta_Core();

	// Test authentication
	echo "Testing authentication...\n";
	$is_authenticated = $api_client->is_authenticated();
	echo "Authenticated: " . ( $is_authenticated ? 'Yes' : 'No' ) . "\n\n";

	if ( $is_authenticated ) {
		// Test getting usage
		echo "Testing usage retrieval...\n";
		$usage = $api_client->get_usage();
		if ( is_wp_error( $usage ) ) {
			echo "ERROR: " . $usage->get_error_message() . "\n";
		} else {
			echo "Usage: " . print_r( $usage, true ) . "\n";
		}
		echo "\n";

		// Test getting billing info
		echo "Testing billing info retrieval...\n";
		$billing = $api_client->get_billing_info();
		if ( is_wp_error( $billing ) ) {
			echo "ERROR: " . $billing->get_error_message() . "\n";
		} else {
			echo "Billing Info: " . print_r( $billing, true ) . "\n";
		}
		echo "\n";

		// Test getting plans
		echo "Testing plans retrieval...\n";
		$plans = $api_client->get_plans();
		if ( is_wp_error( $plans ) ) {
			echo "ERROR: " . $plans->get_error_message() . "\n";
		} else {
			echo "Plans: " . print_r( $plans, true ) . "\n";
		}
		echo "\n";
	} else {
		echo "Skipping API tests - user not authenticated\n";
	}

	// Test price IDs
	echo "Testing price ID retrieval...\n";
	$pro_price = $core->get_checkout_price_id( 'pro' );
	$agency_price = $core->get_checkout_price_id( 'agency' );
	echo "Pro Price ID: " . $pro_price . "\n";
	echo "Agency Price ID: " . $agency_price . "\n\n";

	echo "=== Test Complete ===\n";
}

