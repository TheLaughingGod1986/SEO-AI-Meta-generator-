<?php
/**
 * Custom Logger for SEO AI Meta Generator
 *
 * @package    SEO_AI_Meta
 * @subpackage SEO_AI_Meta/includes
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Logger class for debugging and monitoring
 */
class SEO_AI_Meta_Logger {

	/**
	 * Log levels
	 */
	const LEVEL_DEBUG = 'DEBUG';
	const LEVEL_INFO = 'INFO';
	const LEVEL_WARNING = 'WARNING';
	const LEVEL_ERROR = 'ERROR';

	/**
	 * Maximum number of log entries to keep
	 */
	const MAX_LOGS = 1000;

	/**
	 * Option key for storing logs
	 */
	private static $option_key = 'seo_ai_meta_debug_logs';

	/**
	 * Log a debug message
	 *
	 * @param string $message Log message.
	 * @param array  $context Additional context data.
	 */
	public static function debug( $message, $context = array() ) {
		self::log( self::LEVEL_DEBUG, $message, $context );
	}

	/**
	 * Log an info message
	 *
	 * @param string $message Log message.
	 * @param array  $context Additional context data.
	 */
	public static function info( $message, $context = array() ) {
		self::log( self::LEVEL_INFO, $message, $context );
	}

	/**
	 * Log a warning message
	 *
	 * @param string $message Log message.
	 * @param array  $context Additional context data.
	 */
	public static function warning( $message, $context = array() ) {
		self::log( self::LEVEL_WARNING, $message, $context );
	}

	/**
	 * Log an error message
	 *
	 * @param string $message Log message.
	 * @param array  $context Additional context data.
	 */
	public static function error( $message, $context = array() ) {
		self::log( self::LEVEL_ERROR, $message, $context );
	}

	/**
	 * Write a log entry
	 *
	 * @param string $level   Log level.
	 * @param string $message Log message.
	 * @param array  $context Additional context data.
	 */
	private static function log( $level, $message, $context = array() ) {
		$logs = get_option( self::$option_key, array() );

		if ( ! is_array( $logs ) ) {
			$logs = array();
		}

		$entry = array(
			'timestamp' => current_time( 'mysql' ),
			'level'     => $level,
			'message'   => $message,
			'context'   => $context,
			'user_id'   => get_current_user_id(),
			'ip'        => self::get_client_ip(),
		);

		// Add to the beginning of the array (newest first)
		array_unshift( $logs, $entry );

		// Keep only the most recent entries
		if ( count( $logs ) > self::MAX_LOGS ) {
			$logs = array_slice( $logs, 0, self::MAX_LOGS );
		}

		update_option( self::$option_key, $logs, false );

		// Also log to PHP error log if WP_DEBUG is enabled
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$context_str = ! empty( $context ) ? ' | Context: ' . wp_json_encode( $context ) : '';
			error_log( sprintf( 'SEO AI Meta [%s]: %s%s', $level, $message, $context_str ) );
		}
	}

	/**
	 * Get all log entries
	 *
	 * @param array $filters Optional filters (level, search, limit).
	 * @return array
	 */
	public static function get_logs( $filters = array() ) {
		$logs = get_option( self::$option_key, array() );

		if ( ! is_array( $logs ) ) {
			return array();
		}

		// Apply filters
		if ( ! empty( $filters['level'] ) ) {
			$logs = array_filter( $logs, function( $log ) use ( $filters ) {
				return $log['level'] === $filters['level'];
			} );
		}

		if ( ! empty( $filters['search'] ) ) {
			$search = strtolower( $filters['search'] );
			$logs = array_filter( $logs, function( $log ) use ( $search ) {
				$message = strtolower( $log['message'] );
				$context = strtolower( wp_json_encode( $log['context'] ) );
				return strpos( $message, $search ) !== false || strpos( $context, $search ) !== false;
			} );
		}

		if ( ! empty( $filters['limit'] ) ) {
			$logs = array_slice( $logs, 0, (int) $filters['limit'] );
		}

		// Re-index array after filtering
		return array_values( $logs );
	}

	/**
	 * Clear all logs
	 */
	public static function clear_logs() {
		delete_option( self::$option_key );
		self::info( 'Logs cleared by user' );
	}

	/**
	 * Export logs as JSON
	 *
	 * @return string JSON encoded logs.
	 */
	public static function export_logs_json() {
		$logs = self::get_logs();
		return wp_json_encode( $logs, JSON_PRETTY_PRINT );
	}

	/**
	 * Export logs as CSV
	 *
	 * @return string CSV formatted logs.
	 */
	public static function export_logs_csv() {
		$logs = self::get_logs();

		if ( empty( $logs ) ) {
			return '';
		}

		$csv = "Timestamp,Level,Message,User ID,IP,Context\n";

		foreach ( $logs as $log ) {
			$context_str = ! empty( $log['context'] ) ? wp_json_encode( $log['context'] ) : '';
			$csv .= sprintf(
				'"%s","%s","%s","%s","%s","%s"' . "\n",
				$log['timestamp'],
				$log['level'],
				str_replace( '"', '""', $log['message'] ),
				$log['user_id'],
				$log['ip'],
				str_replace( '"', '""', $context_str )
			);
		}

		return $csv;
	}

	/**
	 * Get statistics about logs
	 *
	 * @return array
	 */
	public static function get_stats() {
		$logs = get_option( self::$option_key, array() );

		if ( ! is_array( $logs ) || empty( $logs ) ) {
			return array(
				'total'   => 0,
				'debug'   => 0,
				'info'    => 0,
				'warning' => 0,
				'error'   => 0,
				'oldest'  => null,
				'newest'  => null,
			);
		}

		$stats = array(
			'total'   => count( $logs ),
			'debug'   => 0,
			'info'    => 0,
			'warning' => 0,
			'error'   => 0,
		);

		foreach ( $logs as $log ) {
			switch ( $log['level'] ) {
				case self::LEVEL_DEBUG:
					$stats['debug']++;
					break;
				case self::LEVEL_INFO:
					$stats['info']++;
					break;
				case self::LEVEL_WARNING:
					$stats['warning']++;
					break;
				case self::LEVEL_ERROR:
					$stats['error']++;
					break;
			}
		}

		// Newest is first (array_unshift), oldest is last
		$stats['newest'] = ! empty( $logs[0]['timestamp'] ) ? $logs[0]['timestamp'] : null;
		$stats['oldest'] = ! empty( $logs[ count( $logs ) - 1 ]['timestamp'] ) ? $logs[ count( $logs ) - 1 ]['timestamp'] : null;

		return $stats;
	}

	/**
	 * Get client IP address
	 *
	 * @return string
	 */
	private static function get_client_ip() {
		$ip = '';

		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
		} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		return $ip;
	}
}
