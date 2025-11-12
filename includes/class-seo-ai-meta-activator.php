<?php
/**
 * Legacy activator compatibility.
 *
 * @package SEO_AI_Meta
 */

if ( ! class_exists( 'SEO_AI_Meta_Activator', false ) ) {
	/**
	 * Legacy activator class that wraps the new namespaced Activator.
	 * Cannot extend the new Activator because it's final, so we use composition.
	 */
	class SEO_AI_Meta_Activator {
		/**
		 * Execute activation tasks.
		 */
		public static function activate() {
			return \SeoAiMeta\Core\Activator::activate();
		}
	}
}
