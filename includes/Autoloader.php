<?php
/**
 * PSR-4 autoloader.
 *
 * @package SeoAiMeta
 */

declare( strict_types=1 );

namespace SeoAiMeta;

/**
 * Simple PSR-4 autoloader for the plugin.
 */
final class Autoloader {

	/**
	 * Namespace prefix.
	 */
	private const PREFIX = 'SeoAiMeta\\';

	/**
	 * Base directory.
	 *
	 * @var string
	 */
	private string $base_dir;

	/**
	 * Constructor.
	 *
	 * @param string $base_dir Absolute path to the src directory.
	 */
	public function __construct( string $base_dir ) {
		$this->base_dir = \trailingslashit( $base_dir );
	}

	/**
	 * Register the autoloader.
	 *
	 * @return void
	 */
	public function register(): void {
		spl_autoload_register( array( $this, 'load_class' ) );
	}

	/**
	 * Load class file if in namespace.
	 *
	 * @param string $class Class name.
	 * @return void
	 */
	private function load_class( string $class ): void {
		if ( 0 !== strpos( $class, self::PREFIX ) ) {
			return;
		}

		$relative_class = substr( $class, strlen( self::PREFIX ) );
		$file           = $this->base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
}
