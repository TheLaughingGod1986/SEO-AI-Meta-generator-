<?php
/**
 * Cache clearing and testing script
 * Access via: http://your-site.local/wp-content/plugins/seo-ai-meta-generator/clear-cache-test.php
 */

// Clear opcode cache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "✅ OPcache cleared\n";
}

if (function_exists('apcu_clear_cache')) {
    apcu_clear_cache();
    echo "✅ APCu cache cleared\n";
}

// Load WordPress
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';

// Load the class
require_once __DIR__ . '/includes/class-seo-ai-meta-core.php';

// Test the price IDs
$core = new SEO_AI_Meta_Core();
$pro_price = $core->get_checkout_price_id('pro');
$agency_price = $core->get_checkout_price_id('agency');

echo "\n=== Current Price IDs ===\n";
echo "Pro: " . $pro_price . "\n";
echo "Agency: " . $agency_price . "\n";

echo "\n=== Expected Price IDs ===\n";
echo "Pro: price_1SQ6a5Jl9Rm418cMx77q8KB9\n";
echo "Agency: price_1SQ6aTJl9Rm418cMQz47wCZ2\n";

if ($pro_price === 'price_1SQ6a5Jl9Rm418cMx77q8KB9') {
    echo "\n✅ Pro price ID is CORRECT!\n";
} else {
    echo "\n❌ Pro price ID is WRONG!\n";
}

if ($agency_price === 'price_1SQ6aTJl9Rm418cMQz47wCZ2') {
    echo "\n✅ Agency price ID is CORRECT!\n";
} else {
    echo "\n❌ Agency price ID is WRONG!\n";
}

echo "\n=== PHP Info ===\n";
echo "OPcache enabled: " . (ini_get('opcache.enable') ? 'Yes' : 'No') . "\n";
echo "OPcache revalidate: " . ini_get('opcache.revalidate_freq') . " seconds\n";
