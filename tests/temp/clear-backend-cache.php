<?php
/**
 * Clear backend status cache and test connection
 * Run: wp eval-file clear-backend-cache.php
 */

// Clear all SEO AI Meta transients
global $wpdb;
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_seo_ai_meta_%'" );
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_seo_ai_meta_%'" );

echo "✅ Cleared all SEO AI Meta transients\n";

// Test backend connection
require_once __DIR__ . '/bootstrap.php';

$plugin_dir = SEO_AI_META_PLUGIN_ROOT;
require_once $plugin_dir . '/includes/class-api-client-v2.php';
$api_client = new SEO_AI_Meta_API_Client_V2();
$status = $api_client->get_backend_status();

echo "\n📊 Current Backend Status:\n";
echo "   Status: " . ($status['status'] ?? 'unknown') . "\n";
echo "   Message: " . ($status['message'] ?? 'N/A') . "\n";

if (($status['status'] ?? '') === 'healthy') {
    echo "\n✅ Backend is healthy! The error should clear on next page load.\n";
} else {
    echo "\n⚠️  Backend check failed. Testing direct connection...\n";
    
    $response = wp_remote_get('https://alttext-ai-backend.onrender.com/health', array(
        'timeout' => 10,
        'sslverify' => true
    ));
    
    if (is_wp_error($response)) {
        echo "   ❌ Error: " . $response->get_error_message() . "\n";
    } else {
        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        echo "   HTTP Status: {$code}\n";
        echo "   Response: {$body}\n";
    }
}

echo "\n💡 Next steps:\n";
echo "   1. Refresh the WordPress admin page\n";
echo "   2. The error should be gone if backend is healthy\n";
