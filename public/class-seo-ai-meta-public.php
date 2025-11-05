<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @package    SEO_AI_Meta
 * @subpackage SEO_AI_Meta/public
 */
class SEO_AI_Meta_Public {

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
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		add_action( 'wp_head', array( $this, 'output_meta_tags' ) );
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		// No public styles needed currently
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		// No public scripts needed currently
	}

	/**
	 * Output meta tags in wp_head.
	 *
	 * @since    1.0.0
	 */
	public function output_meta_tags() {
		if ( ! is_singular( 'post' ) ) {
			return;
		}

		global $post;
		if ( ! $post ) {
			return;
		}

		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-database.php';
		
		// Get meta from custom database
		$meta_data = SEO_AI_Meta_Database::get_post_meta( $post->ID );
		
		// Fallback to WordPress post meta for backward compatibility
		if ( $meta_data ) {
			$meta_title       = $meta_data['meta_title'] ?? '';
			$meta_description = $meta_data['meta_description'] ?? '';
		} else {
			$meta_title       = get_post_meta( $post->ID, '_seo_ai_meta_title', true );
			$meta_description = get_post_meta( $post->ID, '_seo_ai_meta_description', true );
		}

		if ( $meta_title ) {
			echo '<meta name="title" content="' . esc_attr( $meta_title ) . '" />' . "\n";
		}

		if ( $meta_description ) {
			echo '<meta name="description" content="' . esc_attr( $meta_description ) . '" />' . "\n";
		}
	}
}

