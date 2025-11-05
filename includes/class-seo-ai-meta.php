<?php
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * @package    SEO_AI_Meta
 * @subpackage SEO_AI_Meta/includes
 */
class SEO_AI_Meta {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @var      SEO_AI_Meta_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 */
	public function __construct() {
		if ( defined( 'SEO_AI_META_VERSION' ) ) {
			$this->version = SEO_AI_META_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'seo-ai-meta-generator';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-i18n.php';

		/**
		 * Admin-specific classes
		 */
		require_once SEO_AI_META_PLUGIN_DIR . 'admin/class-seo-ai-meta-admin.php';
		require_once SEO_AI_META_PLUGIN_DIR . 'admin/class-seo-ai-meta-metabox.php';
		require_once SEO_AI_META_PLUGIN_DIR . 'admin/class-seo-ai-meta-bulk.php';
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-core.php';
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-helpers.php';

		/**
		 * Public-facing classes
		 */
		require_once SEO_AI_META_PLUGIN_DIR . 'public/class-seo-ai-meta-public.php';

		$this->loader = new SEO_AI_Meta_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 */
	private function set_locale() {
		$plugin_i18n = new SEO_AI_Meta_i18n();
		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 */
	private function define_admin_hooks() {
		// Check and create database tables if needed
		$this->loader->add_action( 'admin_init', $this, 'check_database_tables' );
		
		$plugin_admin = new SEO_AI_Meta_Admin( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_plugin_admin_menu' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'register_settings' );

		// Meta box
		$metabox = new SEO_AI_Meta_Metabox();

		// Bulk generate
		$bulk = new SEO_AI_Meta_Bulk();

		// Core functionality (billing, checkout)
		$core = new SEO_AI_Meta_Core();
		$this->loader->add_filter( 'allowed_redirect_hosts', $core, 'allow_stripe_redirects', 10, 2 );
		$this->loader->add_action( 'admin_init', $core, 'register_ajax_handlers' );
		$this->loader->add_action( 'admin_init', $core, 'maybe_handle_direct_checkout' );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 */
	private function define_public_hooks() {
		$plugin_public = new SEO_AI_Meta_Public( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
	}

	/**
	 * Check database tables and create if missing
	 */
	public function check_database_tables() {
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-database.php';
		
		global $wpdb;
		$tables = array(
			'settings' => $wpdb->prefix . 'seo_ai_meta_settings',
			'post_meta' => $wpdb->prefix . 'seo_ai_meta_post_meta',
			'users' => $wpdb->prefix . 'seo_ai_meta_users',
			'usage_log' => $wpdb->prefix . 'seo_ai_meta_usage_log',
		);
		
		$missing_tables = array();
		foreach ( $tables as $key => $table_name ) {
			// Check if table exists using a simple query
			$table_exists = $wpdb->get_var( $wpdb->prepare( 
				"SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
				DB_NAME,
				$table_name
			) );
			if ( 0 === (int) $table_exists ) {
				$missing_tables[] = $key;
			}
		}
		
		if ( ! empty( $missing_tables ) ) {
			// Create missing tables
			SEO_AI_Meta_Database::create_tables();
			
			// Initialize price IDs if not set
			$price_ids = SEO_AI_Meta_Database::get_setting( 'price_ids', null );
			if ( $price_ids === null || empty( $price_ids ) || ! is_array( $price_ids ) ) {
				$default_price_ids = array(
					'pro'     => 'price_1SQ72OJl9Rm418cMruYB5Pgb',
					'agency'  => 'price_1SQ72KJl9Rm418cMB0CYh8xe',
				);
				$existing_from_options = get_option( 'seo_ai_meta_price_ids', null );
				if ( $existing_from_options && is_array( $existing_from_options ) ) {
					SEO_AI_Meta_Database::update_setting( 'price_ids', $existing_from_options );
				} else {
					SEO_AI_Meta_Database::update_setting( 'price_ids', $default_price_ids );
				}
			}
			
			// Show admin notice (only once per page load)
			if ( ! get_transient( 'seo_ai_meta_tables_created_notice' ) ) {
				add_action( 'admin_notices', function() {
					?>
					<div class="notice notice-success is-dismissible">
						<p><strong>SEO AI Meta Generator:</strong> Database tables have been created successfully.</p>
					</div>
					<?php
					set_transient( 'seo_ai_meta_tables_created_notice', true, 30 );
				} );
			}
		} else {
			// Tables exist, but ensure price IDs are initialized
			$price_ids = SEO_AI_Meta_Database::get_setting( 'price_ids', null );
			if ( $price_ids === null || empty( $price_ids ) || ! is_array( $price_ids ) ) {
				$default_price_ids = array(
					'pro'     => 'price_1SQ72OJl9Rm418cMruYB5Pgb',
					'agency'  => 'price_1SQ72KJl9Rm418cMB0CYh8xe',
				);
				$existing_from_options = get_option( 'seo_ai_meta_price_ids', null );
				if ( $existing_from_options && is_array( $existing_from_options ) ) {
					SEO_AI_Meta_Database::update_setting( 'price_ids', $existing_from_options );
				} else {
					SEO_AI_Meta_Database::update_setting( 'price_ids', $default_price_ids );
				}
			}
		}
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @return    SEO_AI_Meta_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}
