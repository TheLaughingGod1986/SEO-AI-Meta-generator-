<?php
/**
 * Test script for backend and Stripe connections
 * Run this from WordPress admin or via WP-CLI
 *
 * Usage: wp eval-file test-connections.php
 */

$plugin_dir = dirname(__DIR__, 2);
require_once $plugin_dir . '/includes/class-api-client-v2.php';
require_once $plugin_dir . '/includes/class-seo-ai-meta-core.php';

echo "🧪 Testing SEO AI Meta Backend & Stripe Connections\n";
echo "==================================================\n\n";

$api_client = new SEO_AI_Meta_API_Client_V2();
$core = new SEO_AI_Meta_Core();

// ==========================================
// TEST 1: Backend API Connection
// ==========================================
echo "1️⃣ Testing Backend API Connection...\n";
echo "   Backend URL: " . (defined('SEO_AI_META_API_URL') ? SEO_AI_META_API_URL : 'https://alttext-ai-backend.onrender.com') . "\n";

// Test basic connectivity
$test_response = wp_remote_get(trailingslashit(defined('SEO_AI_META_API_URL') ? SEO_AI_META_API_URL : 'https://alttext-ai-backend.onrender.com') . 'health', array(
    'timeout' => 10,
    'sslverify' => true
));

if (is_wp_error($test_response)) {
    echo "   ❌ Cannot reach backend: " . $test_response->get_error_message() . "\n";
} else {
    $status_code = wp_remote_retrieve_response_code($test_response);
    if ($status_code >= 200 && $status_code < 300) {
        echo "   ✅ Backend is reachable (HTTP {$status_code})\n";
    } else {
        echo "   ⚠️  Backend responded with HTTP {$status_code}\n";
    }
}
echo "\n";

// ==========================================
// TEST 2: Authentication Status
// ==========================================
echo "2️⃣ Testing Authentication...\n";
$is_authenticated = $api_client->is_authenticated();
if ($is_authenticated) {
    echo "   ✅ User is authenticated\n";
    
    $user_info = $api_client->get_user_info();
    if (is_wp_error($user_info)) {
        echo "   ⚠️  Could not get user info: " . $user_info->get_error_message() . "\n";
    } else {
        echo "   User Email: " . ($user_info['email'] ?? 'N/A') . "\n";
        echo "   User Plan: " . ($user_info['plan'] ?? 'N/A') . "\n";
        echo "   Service: " . ($user_info['service'] ?? 'N/A') . "\n";
    }
} else {
    echo "   ⚠️  User is not authenticated\n";
    echo "   ℹ️  You can test authentication by registering/logging in\n";
}
echo "\n";

// ==========================================
// TEST 3: Stripe Price IDs Configuration
// ==========================================
echo "3️⃣ Testing Stripe Price IDs Configuration...\n";
$pro_price_id = $core->get_checkout_price_id('pro');
$agency_price_id = $core->get_checkout_price_id('agency');

if (!empty($pro_price_id)) {
    echo "   ✅ Pro Plan Price ID: {$pro_price_id}\n";
} else {
    echo "   ❌ Pro Plan Price ID: NOT CONFIGURED\n";
}

if (!empty($agency_price_id)) {
    echo "   ✅ Agency Plan Price ID: {$agency_price_id}\n";
} else {
    echo "   ❌ Agency Plan Price ID: NOT CONFIGURED\n";
}
echo "\n";

// ==========================================
// TEST 4: Stripe Checkout Session Creation
// ==========================================
echo "4️⃣ Testing Stripe Checkout Session Creation...\n";
if (!$is_authenticated) {
    echo "   ⚠️  Skipping (requires authentication)\n";
} elseif (empty($pro_price_id)) {
    echo "   ⚠️  Skipping (Pro Price ID not configured)\n";
} else {
    $success_url = admin_url('edit.php?page=seo-ai-meta-generator&checkout=success');
    $cancel_url = admin_url('edit.php?page=seo-ai-meta-generator&checkout=cancel');
    
    echo "   Testing with Pro Price ID: {$pro_price_id}\n";
    $checkout_result = $api_client->create_checkout_session($pro_price_id, $success_url, $cancel_url);
    
    if (is_wp_error($checkout_result)) {
        echo "   ❌ Checkout session creation failed: " . $checkout_result->get_error_message() . "\n";
        echo "   Error Code: " . $checkout_result->get_error_code() . "\n";
        
        // Common error codes
        if ($checkout_result->get_error_code() === 'auth_required') {
            echo "   ℹ️  This means authentication failed or expired\n";
        } elseif ($checkout_result->get_error_code() === 'checkout_failed') {
            echo "   ℹ️  This could mean:\n";
            echo "      - Stripe API key not configured on backend\n";
            echo "      - Price ID is invalid\n";
            echo "      - Backend Stripe integration issue\n";
        }
    } else {
        if (!empty($checkout_result['url'])) {
            echo "   ✅ Checkout session created successfully!\n";
            echo "   Checkout URL: " . $checkout_result['url'] . "\n";
            echo "   ✅ Stripe connection is working!\n";
        } else {
            echo "   ⚠️  Checkout session created but no URL returned\n";
        }
    }
}
echo "\n";

// ==========================================
// TEST 5: Billing Info Endpoint
// ==========================================
echo "5️⃣ Testing Billing Info Endpoint...\n";
if (!$is_authenticated) {
    echo "   ⚠️  Skipping (requires authentication)\n";
} else {
    $billing = $api_client->get_billing_info();
    if (is_wp_error($billing)) {
        echo "   ⚠️  Could not get billing info: " . $billing->get_error_message() . "\n";
    } else {
        echo "   ✅ Billing info retrieved:\n";
        echo "      Plan: " . ($billing['plan'] ?? 'N/A') . "\n";
        echo "      Status: " . ($billing['status'] ?? 'N/A') . "\n";
        if (isset($billing['stripeSubscriptionId'])) {
            echo "      Stripe Subscription ID: " . $billing['stripeSubscriptionId'] . "\n";
            echo "      ✅ Stripe subscription is linked\n";
        }
    }
}
echo "\n";

// ==========================================
// TEST 6: Plans Endpoint
// ==========================================
echo "6️⃣ Testing Plans Endpoint...\n";
$plans = $api_client->get_plans();
if (is_wp_error($plans)) {
    echo "   ⚠️  Could not get plans: " . $plans->get_error_message() . "\n";
} else {
    echo "   ✅ Plans retrieved:\n";
    foreach ($plans as $plan) {
        echo "      - " . ($plan['name'] ?? 'N/A') . ": £" . ($plan['price'] ?? '0') . "/month\n";
        if (isset($plan['priceId'])) {
            echo "        Price ID: " . $plan['priceId'] . "\n";
        }
    }
}
echo "\n";

// ==========================================
// SUMMARY
// ==========================================
echo "📝 Summary:\n";
echo "==========\n\n";

// Check what's working
$backend_ok = !is_wp_error($test_response) && wp_remote_retrieve_response_code($test_response) >= 200 && wp_remote_retrieve_response_code($test_response) < 300;
$auth_ok = $is_authenticated;
$price_ids_ok = !empty($pro_price_id) && !empty($agency_price_id);
$stripe_ok = false;

if ($is_authenticated && !empty($pro_price_id)) {
    $test_checkout = $api_client->create_checkout_session($pro_price_id, admin_url('edit.php?page=seo-ai-meta-generator&checkout=success'), admin_url('edit.php?page=seo-ai-meta-generator&checkout=cancel'));
    $stripe_ok = !is_wp_error($test_checkout) && !empty($test_checkout['url']);
}

echo "Backend Connection: " . ($backend_ok ? "✅ Working" : "❌ Not Working") . "\n";
echo "Authentication: " . ($auth_ok ? "✅ Authenticated" : "⚠️  Not Authenticated") . "\n";
echo "Stripe Price IDs: " . ($price_ids_ok ? "✅ Configured" : "❌ Not Configured") . "\n";
echo "Stripe Checkout: " . ($stripe_ok ? "✅ Working" : ($auth_ok && $price_ids_ok ? "❌ Not Working" : "⚠️  Cannot Test")) . "\n\n";

if ($backend_ok && $auth_ok && $price_ids_ok && $stripe_ok) {
    echo "🎉 All systems operational! Backend and Stripe are connected.\n";
} elseif ($backend_ok && $price_ids_ok) {
    echo "⚠️  Backend is connected and Stripe Price IDs are configured.\n";
    if (!$auth_ok) {
        echo "   Action needed: Log in to your account\n";
    }
    if (!$stripe_ok && $auth_ok) {
        echo "   Action needed: Check backend Stripe configuration\n";
    }
} else {
    echo "❌ Some issues detected. Please check:\n";
    if (!$backend_ok) {
        echo "   - Backend server is accessible\n";
    }
    if (!$price_ids_ok) {
        echo "   - Stripe Price IDs are configured in class-seo-ai-meta-core.php\n";
    }
}

echo "\n";


