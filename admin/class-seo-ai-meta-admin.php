<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package    SEO_AI_Meta
 * @subpackage SEO_AI_Meta/admin
 */
class SEO_AI_Meta_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		// Export/Import handlers
		add_action( 'admin_post_seo_ai_meta_export', array( $this, 'export_meta_tags' ) );
		add_action( 'admin_post_seo_ai_meta_import', array( $this, 'import_meta_tags' ) );
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles( $hook ) {
		// Load dashboard styles on our plugin pages
		// Check if we're on one of our plugin pages
		$plugin_pages = array(
			'posts_page_' . $this->plugin_name,
			'posts_page_' . $this->plugin_name . '-bulk',
		);
		
		// Also check by screen ID or if hook contains our plugin name
		if ( in_array( $hook, $plugin_pages, true ) || 
		     strpos( $hook, $this->plugin_name ) !== false ||
		     ( isset( $_GET['page'] ) && strpos( $_GET['page'], $this->plugin_name ) !== false ) ) {
			// Add Google Fonts (Inter)
			wp_enqueue_style(
				'google-fonts-inter',
				'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
				array(),
				null,
				'all'
			);
			
			wp_enqueue_style(
				$this->plugin_name,
				SEO_AI_META_PLUGIN_URL . 'assets/seo-ai-meta-dashboard.css',
				array( 'google-fonts-inter' ),
				$this->version,
				'all'
			);
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts( $hook ) {
		// Load auth modal FIRST so its functions are available
		wp_enqueue_script(
			$this->plugin_name . '-auth-modal',
			SEO_AI_META_PLUGIN_URL . 'assets/seo-ai-meta-auth-modal.js',
			array( 'wp-element' ),
			$this->version,
			false  // Load in header so functions are available when buttons render
		);

		// Load helper functions (depends on auth-modal being loaded first)
		wp_enqueue_script(
			$this->plugin_name . '-helpers',
			SEO_AI_META_PLUGIN_URL . 'assets/seo-ai-meta-helpers.js',
			array( 'jquery', $this->plugin_name . '-auth-modal' ),
			$this->version,
			false
		);

		// Always load dashboard script
		wp_enqueue_script(
			$this->plugin_name . '-dashboard',
			SEO_AI_META_PLUGIN_URL . 'assets/seo-ai-meta-dashboard.js',
			array( 'jquery', $this->plugin_name . '-helpers' ),
			$this->version,
			false
		);

		// Load metabox script on post edit pages
		if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			wp_enqueue_script(
				$this->plugin_name . '-metabox',
				SEO_AI_META_PLUGIN_URL . 'assets/seo-ai-meta-metabox.js',
				array( 'jquery' ),
				$this->version,
				false
			);

			// Localize script with AJAX URL and nonce for metabox
			wp_localize_script(
				$this->plugin_name . '-metabox',
				'seoAiMetaAjax',
				array(
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'seo_ai_meta_nonce' ),
				)
			);
		}

		// Localize script with AJAX URL and nonce for dashboard and bulk pages
		wp_localize_script(
			$this->plugin_name . '-helpers',
			'seoAiMetaAjax',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'seo_ai_meta_nonce' ),
				'bulk_nonce' => wp_create_nonce( 'seo_ai_meta_bulk_nonce' ),
			)
		);
	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {
		add_posts_page(
			__( 'SEO AI Meta Generator', 'seo-ai-meta-generator' ),
			__( 'SEO AI Meta', 'seo-ai-meta-generator' ),
			'manage_seo_ai_meta',
			$this->plugin_name,
			array( $this, 'display_plugin_admin_page' )
		);

		// Hidden checkout page
		add_submenu_page(
			null,
			__( 'Checkout', 'seo-ai-meta-generator' ),
			__( 'Checkout', 'seo-ai-meta-generator' ),
			'manage_seo_ai_meta',
			$this->plugin_name . '-checkout',
			array( $this, 'handle_checkout_redirect' )
		);
	}

	/**
	 * Handle checkout redirect
	 */
	public function handle_checkout_redirect() {
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-core.php';
		$core = new SEO_AI_Meta_Core();
		
		// The maybe_handle_direct_checkout() handles the redirect via admin_init
		// If we reach here and it didn't redirect, show an error
		if ( ! headers_sent() ) {
			wp_safe_redirect( add_query_arg( array( 'checkout_error' => rawurlencode( __( 'Checkout initialization failed. Please try again.', 'seo-ai-meta-generator' ) ) ), admin_url( 'edit.php?page=seo-ai-meta-generator' ) ) );
			exit;
		}
	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_page() {
		include_once 'partials/seo-ai-meta-admin-display.php';
	}

	/**
	 * Register settings
	 *
	 * @since    1.0.0
	 */
	public function register_settings() {
		register_setting(
			'seo_ai_meta_settings_group',
			'seo_ai_meta_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => array(),
			)
		);

		// Add settings section
		add_settings_section(
			'seo_ai_meta_settings_section',
			__( 'General Settings', 'seo-ai-meta-generator' ),
			array( $this, 'render_settings_section' ),
			'seo_ai_meta_settings_group'
		);
		
		// Hook into option update to also save to custom database
		add_action( 'update_option_seo_ai_meta_settings', array( $this, 'sync_settings_to_db' ), 10, 2 );
		add_action( 'add_option_seo_ai_meta_settings', array( $this, 'sync_settings_to_db' ), 10, 2 );
		
		// Redirect to settings tab after save
		add_filter( 'wp_redirect', array( $this, 'redirect_to_settings_tab' ), 10, 2 );
	}
	
	/**
	 * Redirect to settings tab after saving
	 *
	 * @param string $location Redirect URL.
	 * @param int    $status   Status code.
	 * @return string
	 */
	public function redirect_to_settings_tab( $location, $status ) {
		// Only handle redirects for our settings page
		if ( isset( $_POST['option_page'] ) && $_POST['option_page'] === 'seo_ai_meta_settings_group' ) {
			// Check if this is coming from our settings page
			if ( isset( $_POST['_wp_http_referer'] ) && strpos( $_POST['_wp_http_referer'], 'seo-ai-meta-generator' ) !== false ) {
				// Add settings tab and success message
				$location = add_query_arg( array(
					'page' => 'seo-ai-meta-generator',
					'tab' => 'settings',
					'settings-updated' => 'true',
				), admin_url( 'edit.php' ) );
			}
		}
		return $location;
	}
	
	/**
	 * Sync settings to custom database
	 *
	 * @param mixed $old_value Old value.
	 * @param mixed $value New value.
	 */
	public function sync_settings_to_db( $old_value, $value ) {
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-database.php';
		SEO_AI_Meta_Database::update_setting( 'settings', $value );
	}

	/**
	 * Render settings section description
	 */
	public function render_settings_section() {
		echo '<p>' . esc_html__( 'Configure your SEO AI Meta Generator settings below.', 'seo-ai-meta-generator' ) . '</p>';
	}

	/**
	 * Sanitize settings
	 *
	 * @param array $input Input data.
	 * @return array
	 */
	public function sanitize_settings( $input ) {
		$output = array();

		if ( isset( $input['openai_api_key'] ) ) {
			$output['openai_api_key'] = sanitize_text_field( $input['openai_api_key'] );
		}

		if ( isset( $input['title_template'] ) ) {
			$output['title_template'] = sanitize_text_field( $input['title_template'] );
		}

		if ( isset( $input['description_template'] ) ) {
			$output['description_template'] = sanitize_textarea_field( $input['description_template'] );
		}

		if ( isset( $input['default_model'] ) ) {
			$output['default_model'] = in_array( $input['default_model'], array( 'gpt-4o-mini', 'gpt-4-turbo' ), true )
				? $input['default_model']
				: 'gpt-4o-mini';
		}

		if ( isset( $input['title_max_length'] ) ) {
			$output['title_max_length'] = max( 30, min( 70, intval( $input['title_max_length'] ) ) );
		}

		if ( isset( $input['description_max_length'] ) ) {
			$output['description_max_length'] = max( 120, min( 200, intval( $input['description_max_length'] ) ) );
		}

		// License mode (admin only)
		if ( isset( $input['license_mode'] ) && current_user_can( 'manage_options' ) ) {
			$valid_modes = array( 'per-user', 'site-wide' );
			if ( in_array( $input['license_mode'], $valid_modes, true ) ) {
				$output['license_mode'] = $input['license_mode'];

				// Store license mode in Site_License class
				require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-site-license.php';
				SEO_AI_Meta_Site_License::set_license_mode( $input['license_mode'] );
			}
		}

		// Site API key (admin only, only for site-wide mode)
		if ( isset( $input['site_api_key'] ) && current_user_can( 'manage_options' ) ) {
			$api_key = sanitize_text_field( trim( $input['site_api_key'] ) );

			if ( ! empty( $api_key ) ) {
				// Validate API key format (basic check)
				require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-site-license.php';
				if ( SEO_AI_Meta_Site_License::validate_api_key_format( $api_key ) ) {
					$output['site_api_key'] = $api_key;
					SEO_AI_Meta_Site_License::set_site_api_key( $api_key );
				} else {
					add_settings_error(
						'seo_ai_meta_settings',
						'invalid_api_key',
						__( 'Invalid API key format. Please check and try again.', 'seo-ai-meta-generator' ),
						'error'
					);
				}
			} else {
				// Allow clearing the API key
				$output['site_api_key'] = '';
				SEO_AI_Meta_Site_License::clear_site_api_key();
			}
		}

		// Auto-generate (preserve existing value in output array for display)
		if ( isset( $input['auto_generate'] ) ) {
			$output['auto_generate'] = (bool) $input['auto_generate'];
		}

		// Preserve existing settings
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-database.php';
		$existing = SEO_AI_Meta_Database::get_setting( 'settings', array() );
		// Fallback to WordPress options for backward compatibility
		if ( empty( $existing ) ) {
			$existing = get_option( 'seo_ai_meta_settings', array() );
		}
		return wp_parse_args( $output, $existing );
	}

	/**
	 * Export meta tags to CSV
	 */
	public function export_meta_tags() {
		// Check nonce
		if ( ! isset( $_GET['nonce'] ) || ! wp_verify_nonce( $_GET['nonce'], 'seo_ai_meta_export' ) ) {
			wp_die( __( 'Security check failed', 'seo-ai-meta-generator' ) );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Permission denied', 'seo-ai-meta-generator' ) );
		}

		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-database.php';

		global $wpdb;
		$table = SEO_AI_Meta_Database::get_table_name( 'post_meta' );

		// Get all meta tags
		$meta_tags = $wpdb->get_results( "SELECT post_id, meta_title, meta_description, generated_at FROM {$table} WHERE meta_title IS NOT NULL AND meta_title != ''", ARRAY_A );

		// Set headers for CSV download
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=seo-meta-tags-export-' . date( 'Y-m-d' ) . '.csv' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		// Output CSV
		$output = fopen( 'php://output', 'w' );

		// Add BOM for UTF-8
		fprintf( $output, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

		// Headers
		fputcsv( $output, array( 'Post ID', 'Post Title', 'Meta Title', 'Meta Description', 'Generated At' ) );

		// Data rows
		foreach ( $meta_tags as $meta ) {
			$post = get_post( $meta['post_id'] );
			fputcsv( $output, array(
				$meta['post_id'],
				$post ? $post->post_title : '',
				$meta['meta_title'],
				$meta['meta_description'],
				$meta['generated_at'],
			) );
		}

		fclose( $output );
		exit;
	}

	/**
	 * Import meta tags from CSV
	 */
	public function import_meta_tags() {
		// Check nonce
		if ( ! isset( $_POST['seo_ai_meta_import_nonce'] ) || ! wp_verify_nonce( $_POST['seo_ai_meta_import_nonce'], 'seo_ai_meta_import' ) ) {
			wp_die( __( 'Security check failed', 'seo-ai-meta-generator' ) );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Permission denied', 'seo-ai-meta-generator' ) );
		}

		// Check file upload
		if ( ! isset( $_FILES['import_file'] ) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK ) {
			wp_redirect( add_query_arg( array( 'page' => 'seo-ai-meta-generator', 'tab' => 'settings', 'import_error' => 'file_upload_error' ), admin_url( 'edit.php' ) ) );
			exit;
		}

		$file = $_FILES['import_file']['tmp_name'];
		if ( ! file_exists( $file ) ) {
			wp_redirect( add_query_arg( array( 'page' => 'seo-ai-meta-generator', 'tab' => 'settings', 'import_error' => 'file_not_found' ), admin_url( 'edit.php' ) ) );
			exit;
		}

		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-database.php';

		$imported = 0;
		$skipped = 0;

		// Read CSV
		$handle = fopen( $file, 'r' );
		if ( $handle ) {
			// Skip header row
			fgetcsv( $handle );

			// Process rows
			while ( ( $data = fgetcsv( $handle ) ) !== false ) {
				if ( count( $data ) < 3 ) {
					continue;
				}

				$post_id = intval( $data[0] );
				$meta_title = sanitize_text_field( $data[2] );
				$meta_description = sanitize_textarea_field( $data[3] ?? '' );

				if ( $post_id && get_post( $post_id ) && ! empty( $meta_title ) ) {
					SEO_AI_Meta_Database::update_post_meta( $post_id, array(
						'meta_title'       => $meta_title,
						'meta_description' => $meta_description,
						'generated_at'     => current_time( 'mysql' ),
					) );
					$imported++;
				} else {
					$skipped++;
				}
			}
			fclose( $handle );
		}

		wp_redirect( add_query_arg( array(
			'page' => 'seo-ai-meta-generator',
			'tab' => 'settings',
			'import_success' => '1',
			'imported' => $imported,
			'skipped' => $skipped,
		), admin_url( 'edit.php' ) ) );
		exit;
	}
}
