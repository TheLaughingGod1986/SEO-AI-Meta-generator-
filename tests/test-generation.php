<?php
/**
 * Test Meta Generation
 *
 * @package    SEO_AI_Meta
 * @subpackage SEO_AI_Meta/tests
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Test meta generation functionality
 */
function test_seo_ai_meta_generation( $post_id ) {
	require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-generator.php';

	echo "=== Meta Generation Test ===\n\n";
	echo "Testing generation for Post ID: " . $post_id . "\n\n";

	$generator = new SEO_AI_Meta_Generator();
	$result = $generator->generate( $post_id );

	if ( is_wp_error( $result ) ) {
		echo "ERROR: " . $result->get_error_message() . "\n";
		echo "Error Code: " . $result->get_error_code() . "\n";
		return false;
	}

	echo "SUCCESS!\n";
	echo "Generated Title: " . $result['title'] . "\n";
	echo "Title Length: " . mb_strlen( $result['title'] ) . " characters\n";
	echo "Generated Description: " . $result['description'] . "\n";
	echo "Description Length: " . mb_strlen( $result['description'] ) . " characters\n\n";

	// Check stored meta
	$stored_title = get_post_meta( $post_id, '_seo_ai_meta_title', true );
	$stored_desc = get_post_meta( $post_id, '_seo_ai_meta_description', true );

	echo "Stored Meta:\n";
	echo "Title: " . $stored_title . "\n";
	echo "Description: " . $stored_desc . "\n\n";

	echo "=== Test Complete ===\n";
	return true;
}

