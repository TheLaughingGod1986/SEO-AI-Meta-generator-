<?php
/**
 * Plugin Name: SEO AI Meta Generator
 * Plugin URI: https://wordpress.org/plugins/seo-ai-meta-generator/
 * Description: AI-powered SEO meta title and description generator for WordPress posts. Uses GPT-4 to create optimized meta tags that boost search engine rankings. Free tier: 10 posts/month.
 * Version: 1.1.7
 * Author: Benjamin Oats
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: seo-ai-meta-generator
 * Requires at least: 5.8
 * Tested up to: 6.8
 * Requires PHP: 7.4
 * Tags: SEO, SEO AI, meta tags, meta description, meta title, AI, GPT-4, search engine optimization, automation
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Backend API Configuration
 * 
 * These constants can be overridden in wp-config.php:
 * - SEO_AI_META_API_URL: Backend API URL (default: https://alttext-ai-backend.onrender.com)
 * - SEO_AI_META_API_TIMEOUT: Request timeout in seconds (default: 30)
 * - SEO_AI_META_OFFLINE_MODE: Allow plugin to work without backend (default: true if OpenAI key set)
 */

// Set defaults if not defined in wp-config.php
if ( ! defined( 'SEO_AI_META_API_URL' ) ) {
	define( 'SEO_AI_META_API_URL', 'https://alttext-ai-backend.onrender.com' );
}

if ( ! defined( 'SEO_AI_META_API_TIMEOUT' ) ) {
	define( 'SEO_AI_META_API_TIMEOUT', 30 );
}

// Offline mode is enabled by default if OpenAI key is available
// Can be disabled by setting SEO_AI_META_OFFLINE_MODE to false in wp-config.php

// Suppress PHP 8.3 deprecation warnings from WordPress core during development
if ( defined( 'WP_DEBUG' ) && WP_DEBUG && PHP_VERSION_ID >= 80300 ) {
	// Only suppress deprecation warnings, keep other errors visible
	$error_reporting = error_reporting();
	error_reporting( $error_reporting & ~E_DEPRECATED );
}

define( 'SEO_AI_META_VERSION', '1.1.7' );
define( 'SEO_AI_META_PLUGIN_FILE', __FILE__ );
define( 'SEO_AI_META_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SEO_AI_META_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SEO_AI_META_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Register the activation hook.
 */
register_activation_hook(
	__FILE__,
	static function (): void {
		\SeoAiMeta\Core\Activator::activate();
	}
);

register_deactivation_hook(
	__FILE__,
	static function (): void {
		\SeoAiMeta\Core\Deactivator::deactivate();
	}
);

require_once SEO_AI_META_PLUGIN_DIR . 'includes/Autoloader.php';

( new \SeoAiMeta\Autoloader( SEO_AI_META_PLUGIN_DIR . 'src' ) )->register();

( new \SeoAiMeta\Core\Plugin() )->run();
