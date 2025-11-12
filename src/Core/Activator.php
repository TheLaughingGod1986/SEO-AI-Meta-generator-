<?php
/**
 * Handles plugin activation.
 *
 * @package SeoAiMeta\Core
 */

declare( strict_types=1 );

namespace SeoAiMeta\Core;

/**
 * Activation logic.
 */
final class Activator {

	/**
	 * Execute activation tasks.
	 *
	 * @return void
	 */
	public static function activate(): void {
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-database.php';
		\SEO_AI_Meta_Database::create_tables();
	}
}
