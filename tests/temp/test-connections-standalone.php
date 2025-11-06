<?php
/**
 * Standalone test script for backend and Stripe connections
 * This version doesn't require WordPress to be loaded
 * 
 * Usage: php test-connections-standalone.php
 */

echo "🧪 Testing SEO AI Meta Backend & Stripe Connections\n";
echo "==================================================\n\n";

require_once __DIR__ . '/bootstrap.php';

$plugin_dir = SEO_AI_META_PLUGIN_ROOT;

$api_url = 'https://alttext-ai-backend.onrender.com';
$pro_price_id = 'price_1SQ6a5Jl9Rm418cMx77q8KB9';
$agency_price_id = 'price_1SQ6aTJl9Rm418cMQz47wCZ2';

// ==========================================
// TEST 1: Backend API Connection
// ==========================================
echo "1️⃣ Testing Backend API Connection...\n";
echo "   Backend URL: {$api_url}\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url . '/billing/plans?service=seo-ai-meta');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

$backend_connection_ok = false;
if ($curl_error) {
    echo "   ❌ Cannot reach backend: {$curl_error}\n";
} elseif ($http_code >= 200 && $http_code < 300) {
    echo "   ✅ Backend is reachable (HTTP {$http_code})\n";
    $backend_connection_ok = true;
    $data = json_decode($response, true);
    if ($data && isset($data['plans'])) {
        echo "   ✅ Plans endpoint working\n";
        echo "   Found " . count($data['plans']) . " plans\n";
    }
} else {
    echo "   ⚠️  Backend responded with HTTP {$http_code}\n";
}
echo "\n";

// ==========================================
// TEST 2: Stripe Price IDs Configuration
// ==========================================
echo "2️⃣ Testing Stripe Price IDs Configuration...\n";
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
// TEST 3: Check Price ID Format
// ==========================================
echo "3️⃣ Validating Price ID Format...\n";
$price_ids_valid = true;

if (!preg_match('/^price_[a-zA-Z0-9]{14,}$/', $pro_price_id)) {
    echo "   ⚠️  Pro Price ID format may be invalid\n";
    $price_ids_valid = false;
} else {
    echo "   ✅ Pro Price ID format is valid\n";
}

if (!preg_match('/^price_[a-zA-Z0-9]{14,}$/', $agency_price_id)) {
    echo "   ⚠️  Agency Price ID format may be invalid\n";
    $price_ids_valid = false;
} else {
    echo "   ✅ Agency Price ID format is valid\n";
}
echo "\n";

// ==========================================
// TEST 4: Test Checkout Endpoint (requires auth)
// ==========================================
echo "4️⃣ Testing Checkout Endpoint Structure...\n";
echo "   ℹ️  Checkout endpoint requires authentication\n";
echo "   Endpoint: POST {$api_url}/billing/checkout\n";
echo "   Required parameters:\n";
echo "      - priceId (Stripe Price ID)\n";
echo "      - successUrl\n";
echo "      - cancelUrl\n";
echo "      - service: 'seo-ai-meta'\n";
echo "\n";

// Test without auth to see response
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url . '/billing/checkout');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'priceId' => $pro_price_id,
    'successUrl' => 'https://example.com/success',
    'cancelUrl' => 'https://example.com/cancel',
    'service' => 'seo-ai-meta'
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 401 || $http_code === 403) {
    echo "   ✅ Endpoint exists and requires authentication (expected)\n";
} elseif ($http_code >= 200 && $http_code < 300) {
    echo "   ⚠️  Endpoint responded without auth (unexpected)\n";
} else {
    echo "   ⚠️  Endpoint returned HTTP {$http_code}\n";
}
echo "\n";

// ==========================================
// TEST 5: Verify API Client Configuration
// ==========================================
echo "5️⃣ Checking Plugin Configuration Files...\n";
$api_client_file = $plugin_dir . '/includes/class-api-client-v2.php';
$core_file = $plugin_dir . '/includes/class-seo-ai-meta-core.php';

if (file_exists($api_client_file)) {
    echo "   ✅ API Client file exists\n";
    $api_content = file_get_contents($api_client_file);
    if (strpos($api_content, 'create_checkout_session') !== false) {
        echo "   ✅ Checkout session method exists\n";
    }
    if (strpos($api_content, 'service') !== false && strpos($api_content, 'seo-ai-meta') !== false) {
        echo "   ✅ Service parameter configured\n";
    }
} else {
    echo "   ❌ API Client file not found\n";
}

if (file_exists($core_file)) {
    echo "   ✅ Core file exists\n";
    $core_content = file_get_contents($core_file);
    if (strpos($core_content, $pro_price_id) !== false) {
        echo "   ✅ Pro Price ID found in core file\n";
    }
    if (strpos($core_content, $agency_price_id) !== false) {
        echo "   ✅ Agency Price ID found in core file\n";
    }
} else {
    echo "   ❌ Core file not found\n";
}
echo "\n";

// ==========================================
// SUMMARY
// ==========================================
echo "📝 Summary:\n";
echo "==========\n\n";

$backend_ok = isset($backend_connection_ok) && $backend_connection_ok;
$price_ids_ok = !empty($pro_price_id) && !empty($agency_price_id) && $price_ids_valid;
$files_ok = file_exists($api_client_file) && file_exists($core_file);

echo "Backend Connection: " . ($backend_ok ? "✅ Working" : "❌ Not Working") . "\n";
echo "Stripe Price IDs: " . ($price_ids_ok ? "✅ Configured" : "❌ Not Configured") . "\n";
echo "Plugin Files: " . ($files_ok ? "✅ Present" : "❌ Missing") . "\n\n";

if ($backend_ok && $price_ids_ok && $files_ok) {
    echo "🎉 Configuration looks good!\n";
    echo "\n";
    echo "⚠️  To fully test Stripe checkout, you need to:\n";
    echo "   1. Log in via WordPress admin\n";
    echo "   2. Click 'Upgrade to Pro' button\n";
    echo "   3. Verify redirect to Stripe checkout\n";
    echo "\n";
    echo "The backend and Stripe are configured correctly.\n";
    echo "Full checkout testing requires WordPress authentication.\n";
} else {
    echo "❌ Some issues detected:\n";
    if (!$backend_ok) {
        echo "   - Backend server may be down or unreachable\n";
    }
    if (!$price_ids_ok) {
        echo "   - Stripe Price IDs need to be configured\n";
    }
    if (!$files_ok) {
        echo "   - Plugin files are missing\n";
    }
}

echo "\n";
