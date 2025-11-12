<?php
/**
 * Legacy loader compatibility.
 *
 * @package SEO_AI_Meta
 */

if ( ! class_exists( 'SEO_AI_Meta_Loader', false ) ) {
	/**
	 * Legacy loader class that wraps the new namespaced Loader.
	 * Cannot extend the new Loader because it's final, so we use composition.
	 */
	class SEO_AI_Meta_Loader {
		/**
		 * Instance of the new namespaced Loader.
		 *
		 * @var \SeoAiMeta\Core\Loader
		 */
		private $loader;

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->loader = new \SeoAiMeta\Core\Loader();
		}

		/**
		 * Add a new filter to the collection.
		 *
		 * @param string $hook          The name of the WordPress filter.
		 * @param object $component     The class object.
		 * @param string $callback      The callback method name.
		 * @param int    $priority      Optional. Priority. Default 10.
		 * @param int    $accepted_args Optional. Number of args. Default 1.
		 */
		public function add_filter( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
			return $this->loader->add_filter( $hook, $component, $callback, $priority, $accepted_args );
		}

		/**
		 * Add a new action to the collection.
		 *
		 * @param string $hook          The name of the WordPress action.
		 * @param object $component     The class object.
		 * @param string $callback      The callback method name.
		 * @param int    $priority      Optional. Priority. Default 10.
		 * @param int    $accepted_args Optional. Number of args. Default 1.
		 */
		public function add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
			return $this->loader->add_action( $hook, $component, $callback, $priority, $accepted_args );
		}

		/**
		 * Register all hooks with WordPress.
		 */
		public function run() {
			return $this->loader->run();
		}
	}
}
