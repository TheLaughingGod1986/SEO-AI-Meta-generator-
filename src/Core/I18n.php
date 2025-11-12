<?php
/**
 * Internationalisation loader.
 *
 * @package SeoAiMeta\Core
 */

declare( strict_types=1 );

namespace SeoAiMeta\Core;

/**
 * Loads plugin textdomain.
 */
final class I18n {

	/**
	 * Register textdomain.
	 *
	 * @return void
	 */
	public function load_plugin_textdomain(): void {
		load_plugin_textdomain(
			'seo-ai-meta-generator',
			false,
			dirname( plugin_basename( SEO_AI_META_PLUGIN_FILE ) ) . '/languages/'
		);
	}
}
