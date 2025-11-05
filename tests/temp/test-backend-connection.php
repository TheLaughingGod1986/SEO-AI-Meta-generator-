<?php
/**
 * Test script for backend connection
 * Run this from WordPress admin or via WP-CLI
 *
 * Usage: wp eval-file test-backend-connection.php
 */

require_once __DIR__ . '/includes/class-api-client-v2.php';

echo "🧪 Testing SEO AI Meta Backend Connection\n";
echo "==========================================\n\n";

$api_client = new SEO_AI_Meta_API_Client_V2();

// Test 1: Check if authenticated
echo "1️⃣ Checking authentication status...\n";
$is_authenticated = $api_client->is_authenticated();
echo "   Authenticated: " . ($is_authenticated ? "✅ Yes" : "❌ No") . "\n\n";

// Test 2: Test registration
echo "2️⃣ Testing registration...\n";
$test_email = 'test-' . time() . '@example.com';
$register_result = $api_client->register($test_email, 'testpass123');
if (is_wp_error($register_result)) {
    echo "   ❌ Registration failed: " . $register_result->get_error_message() . "\n";
    if ($register_result->get_error_code() === 'USER_EXISTS') {
        echo "   ℹ️  User already exists, trying login...\n";
        $login_result = $api_client->login($test_email, 'testpass123');
        if (is_wp_error($login_result)) {
            echo "   ❌ Login failed: " . $login_result->get_error_message() . "\n\n";
        } else {
            echo "   ✅ Login successful!\n\n";
            $is_authenticated = true;
        }
    }
} else {
    echo "   ✅ Registration successful!\n";
    echo "   User ID: " . ($register_result['user']['id'] ?? 'N/A') . "\n";
    echo "   Plan: " . ($register_result['user']['plan'] ?? 'N/A') . "\n\n";
    $is_authenticated = true;
}

// Test 3: Get usage (if authenticated)
if ($is_authenticated) {
    echo "3️⃣ Testing usage endpoint...\n";
    $usage = $api_client->get_usage();
    if (is_wp_error($usage)) {
        echo "   ❌ Usage failed: " . $usage->get_error_message() . "\n\n";
    } else {
        echo "   ✅ Usage retrieved:\n";
        echo "      Used: " . ($usage['used'] ?? 'N/A') . "\n";
        echo "      Limit: " . ($usage['limit'] ?? 'N/A') . "\n";
        echo "      Remaining: " . ($usage['remaining'] ?? 'N/A') . "\n";
        echo "      Plan: " . ($usage['plan'] ?? 'N/A') . "\n";
        echo "      Service: " . ($usage['service'] ?? 'N/A') . "\n";
        
        // Verify limit is correct for SEO AI Meta free plan
        if (($usage['limit'] ?? 0) === 10) {
            echo "   ✅ Limit correct for SEO AI Meta free plan (10)\n\n";
        } else {
            echo "   ⚠️  Limit may be incorrect (expected 10, got " . ($usage['limit'] ?? 'N/A') . ")\n\n";
        }
    }

    // Test 4: Get plans
    echo "4️⃣ Testing plans endpoint...\n";
    $plans = $api_client->get_plans();
    if (is_wp_error($plans)) {
        echo "   ❌ Plans failed: " . $plans->get_error_message() . "\n\n";
    } else {
        echo "   ✅ Plans retrieved:\n";
        foreach ($plans as $plan) {
            echo "      - " . ($plan['name'] ?? 'N/A') . ": £" . ($plan['price'] ?? '0') . "/month\n";
        }
        echo "\n";
    }

    // Test 5: Get billing info
    echo "5️⃣ Testing billing info endpoint...\n";
    $billing = $api_client->get_billing_info();
    if (is_wp_error($billing)) {
        echo "   ❌ Billing info failed: " . $billing->get_error_message() . "\n\n";
    } else {
        echo "   ✅ Billing info retrieved:\n";
        echo "      Plan: " . ($billing['plan'] ?? 'N/A') . "\n";
        echo "      Status: " . ($billing['status'] ?? 'N/A') . "\n\n";
    }

    // Test 6: Get user info
    echo "6️⃣ Testing user info endpoint...\n";
    $user_info = $api_client->get_user_info();
    if (is_wp_error($user_info)) {
        echo "   ❌ User info failed: " . $user_info->get_error_message() . "\n\n";
    } else {
        echo "   ✅ User info retrieved:\n";
        echo "      Email: " . ($user_info['email'] ?? 'N/A') . "\n";
        echo "      Plan: " . ($user_info['plan'] ?? 'N/A') . "\n";
        echo "      Service: " . ($user_info['service'] ?? 'N/A') . "\n\n";
    }
} else {
    echo "⚠️  Skipping authenticated tests (not authenticated)\n\n";
}

echo "✅ Test complete!\n";
echo "\n";
echo "📝 Summary:\n";
echo "   If you see ✅ for all tests, the backend connection is working!\n";
echo "   If you see ❌, check:\n";
echo "   1. Backend is deployed and accessible\n";
echo "   2. Database migration has been run\n";
echo "   3. API endpoints are returning correct responses\n";

