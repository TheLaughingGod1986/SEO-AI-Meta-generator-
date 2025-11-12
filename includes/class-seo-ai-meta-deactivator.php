<?php
/**
 * Legacy deactivator compatibility.
 *
 * @package SEO_AI_Meta
 */

if ( ! class_exists( 'SEO_AI_Meta_Deactivator', false ) ) {
	/**
	 * Legacy deactivator class that wraps the new namespaced Deactivator.
	 * Cannot extend the new Deactivator because it's final, so we use composition.
	 */
	class SEO_AI_Meta_Deactivator {
		/**
		 * Execute deactivation tasks.
		 */
		public static function deactivate() {
			return \SeoAiMeta\Core\Deactivator::deactivate();
		}
	}
}
