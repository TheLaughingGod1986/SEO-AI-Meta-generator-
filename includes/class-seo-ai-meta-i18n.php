<?php
/**
 * Legacy i18n compatibility.
 *
 * @package SEO_AI_Meta
 */

if ( ! class_exists( 'SEO_AI_Meta_i18n', false ) ) {
	/**
	 * Legacy i18n class that wraps the new namespaced I18n.
	 * Cannot extend the new I18n because it's final, so we use composition.
	 */
	class SEO_AI_Meta_i18n {
		/**
		 * Instance of the new namespaced I18n.
		 *
		 * @var \SeoAiMeta\Core\I18n
		 */
		private $i18n;

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->i18n = new \SeoAiMeta\Core\I18n();
		}

		/**
		 * Load plugin textdomain.
		 */
		public function load_plugin_textdomain() {
			return $this->i18n->load_plugin_textdomain();
		}
	}
}
