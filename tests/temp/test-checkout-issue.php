<?php
/**
 * Quick diagnostic script to test checkout issue
 * Run from WordPress admin or via wp-cli
 */

// Test backend directly
echo "Testing Backend Connection...\n";
$health = wp_remote_get('https://alttext-ai-backend.onrender.com/health');
if (is_wp_error($health)) {
    echo "❌ Backend not reachable: " . $health->get_error_message() . "\n";
} else {
    $status = wp_remote_retrieve_response_code($health);
    $body = wp_remote_retrieve_body($health);
    echo "✅ Backend responding: HTTP $status\n";
    echo "   Response: $body\n\n";
}

// Check if we have the API client
if (!class_exists('SEO_AI_Meta_API_Client_V2')) {
    echo "❌ API Client class not found. Make sure WordPress is loaded.\n";
    exit;
}

$api_client = new SEO_AI_Meta_API_Client_V2();

// Check authentication
echo "Testing Authentication...\n";
$is_authenticated = $api_client->is_authenticated();
echo ($is_authenticated ? "✅ User is authenticated\n" : "❌ User is NOT authenticated\n");
echo "   You need to log in first via the WordPress admin!\n\n";

if (!$is_authenticated) {
    echo "⚠️  Cannot test checkout - user must be logged in first.\n";
    echo "   Go to WordPress Admin → SEO AI Meta → Click 'Login' button\n";
    exit;
}

// Test checkout with a price ID
echo "Testing Checkout Session Creation...\n";
$price_id = 'price_1SQ6a5Jl9Rm418cMx77q8KB9'; // Pro price ID
$success_url = 'http://localhost:8082/wp-admin/edit.php?page=seo-ai-meta-generator&checkout=success';
$cancel_url = 'http://localhost:8082/wp-admin/edit.php?page=seo-ai-meta-generator&checkout=cancel';

$result = $api_client->create_checkout_session($price_id, $success_url, $cancel_url);

if (is_wp_error($result)) {
    echo "❌ Checkout failed!\n";
    echo "   Error Code: " . $result->get_error_code() . "\n";
    echo "   Error Message: " . $result->get_error_message() . "\n\n";
    
    if ($result->get_error_code() === 'auth_required') {
        echo "   → Authentication issue - try logging in again\n";
    } elseif ($result->get_error_code() === 'server_error') {
        echo "   → Backend server error - check backend logs\n";
    } elseif ($result->get_error_code() === 'checkout_failed') {
        echo "   → Stripe checkout failed - check Stripe key\n";
        echo "   → Error: " . $result->get_error_message() . "\n";
    }
} else {
    if (!empty($result['url'])) {
        echo "✅ Checkout session created successfully!\n";
        echo "   Checkout URL: " . substr($result['url'], 0, 80) . "...\n";
        echo "   ✅ Stripe integration is working!\n";
    } else {
        echo "⚠️  Checkout response received but no URL\n";
        echo "   Response: " . print_r($result, true) . "\n";
    }
}


