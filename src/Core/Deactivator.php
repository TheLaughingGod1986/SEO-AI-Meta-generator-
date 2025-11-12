<?php
/**
 * Handles deactivation.
 *
 * @package SeoAiMeta\Core
 */

declare( strict_types=1 );

namespace SeoAiMeta\Core;

/**
 * Clears scheduled events on deactivate.
 */
final class Deactivator {

	/**
	 * Execute routine.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		wp_clear_scheduled_hook( 'seo_ai_meta_daily_cleanup' );
	}
}
