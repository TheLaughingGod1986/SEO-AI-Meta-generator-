<?php
/**
 * Core plugin orchestrator.
 *
 * @package SeoAiMeta\Core
 */

declare( strict_types=1 );

namespace SeoAiMeta\Core;

/**
 * Bootstraps plugin functionality.
 */
final class Plugin {

	/**
	 * Loader instance.
	 *
	 * @var Loader
	 */
	private Loader $loader;

	/**
	 * Plugin slug.
	 *
	 * @var string
	 */
	private string $plugin_name = 'seo-ai-meta-generator';

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	private string $version;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->version = defined( 'SEO_AI_META_VERSION' ) ? SEO_AI_META_VERSION : '1.0.0';
		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function run(): void {
		$this->loader->run();
	}

	/**
	 * Retrieve plugin slug.
	 *
	 * @return string
	 */
	public function get_plugin_name(): string {
		return $this->plugin_name;
	}

	/**
	 * Retrieve plugin version.
	 *
	 * @return string
	 */
	public function get_version(): string {
		return $this->version;
	}

	/**
	 * Include dependencies.
	 *
	 * @return void
	 */
	private function load_dependencies(): void {
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-loader.php';
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-i18n.php';
		require_once SEO_AI_META_PLUGIN_DIR . 'admin/class-seo-ai-meta-admin.php';
		require_once SEO_AI_META_PLUGIN_DIR . 'admin/class-seo-ai-meta-metabox.php';
		require_once SEO_AI_META_PLUGIN_DIR . 'admin/class-seo-ai-meta-bulk.php';
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-core.php';
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-helpers.php';
		require_once SEO_AI_META_PLUGIN_DIR . 'public/class-seo-ai-meta-public.php';
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-rest-api.php';
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-database-cleanup.php';

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-wp-cli.php';
		}

		$this->loader = new Loader();
	}

	/**
	 * Configure i18n loader.
	 *
	 * @return void
	 */
	private function set_locale(): void {
		$plugin_i18n = new I18n();
		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register admin hooks.
	 *
	 * @return void
	 */
	private function define_admin_hooks(): void {
		$this->loader->add_action( 'admin_init', $this, 'check_database_tables' );

		$plugin_admin = new \SEO_AI_Meta_Admin( $this->plugin_name, $this->version );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_plugin_admin_menu' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'register_settings' );

		new \SEO_AI_Meta_Metabox();
		new \SEO_AI_Meta_Bulk();

		$core = new \SEO_AI_Meta_Core();
		$this->loader->add_filter( 'allowed_redirect_hosts', $core, 'allow_stripe_redirects', 10, 2 );
		$this->loader->add_action( 'admin_init', $core, 'register_ajax_handlers' );
		$this->loader->add_action( 'admin_init', $core, 'maybe_handle_direct_checkout' );

		$this->loader->add_action( 'rest_api_init', \SEO_AI_Meta_REST_API::class, 'register_routes' );
		$this->loader->add_action( 'seo_ai_meta_daily_cleanup', \SEO_AI_Meta_Database_Cleanup::class, 'run_full_cleanup' );

		if ( ! wp_next_scheduled( 'seo_ai_meta_daily_cleanup' ) ) {
			wp_schedule_event( time(), 'daily', 'seo_ai_meta_daily_cleanup' );
		}
	}

	/**
	 * Register public hooks.
	 *
	 * @return void
	 */
	private function define_public_hooks(): void {
		$plugin_public = new \SEO_AI_Meta_Public( $this->plugin_name, $this->version );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
	}

	/**
	 * Create database tables if missing.
	 *
	 * @return void
	 */
	public function check_database_tables(): void {
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-database.php';

		global $wpdb;

		$tables = array(
			'settings'  => $wpdb->prefix . 'seo_ai_meta_settings',
			'post_meta' => $wpdb->prefix . 'seo_ai_meta_post_meta',
			'users'     => $wpdb->prefix . 'seo_ai_meta_users',
			'usage_log' => $wpdb->prefix . 'seo_ai_meta_usage_log',
		);

		$missing_tables = array();

		foreach ( $tables as $key => $table_name ) {
			$exists = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s',
					DB_NAME,
					$table_name
				)
			);

			if ( 0 === (int) $exists ) {
				$missing_tables[] = $key;
			}
		}

		if ( ! empty( $missing_tables ) ) {
			\SEO_AI_Meta_Database::create_tables();
		}
	}
}
