<?php
/**
 * Database Cleanup for SEO AI Meta Generator
 * Handles cleanup of old logs and optimization
 *
 * @package    SEO_AI_Meta
 * @subpackage SEO_AI_Meta/includes
 */
class SEO_AI_Meta_Database_Cleanup {

	/**
	 * Cleanup old usage logs
	 *
	 * @param int $days_old Keep logs newer than this many days (default: 90).
	 * @return int Number of records deleted.
	 */
	public static function cleanup_old_logs( $days_old = 90 ) {
		global $wpdb;
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-database.php';
		
		$table = SEO_AI_Meta_Database::get_table_name( 'usage_log' );
		$cutoff_date = date( 'Y-m-d H:i:s', strtotime( "-{$days_old} days" ) );
		
		$deleted = $wpdb->query( $wpdb->prepare(
			"DELETE FROM {$table} WHERE created_at < %s",
			$cutoff_date
		) );
		
		return intval( $deleted );
	}

	/**
	 * Cleanup orphaned meta data
	 *
	 * @return int Number of records deleted.
	 */
	public static function cleanup_orphaned_meta() {
		global $wpdb;
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-database.php';
		
		$table = SEO_AI_Meta_Database::get_table_name( 'post_meta' );
		$posts_table = $wpdb->posts;
		
		$deleted = $wpdb->query(
			"DELETE pm FROM {$table} pm
			LEFT JOIN {$posts_table} p ON pm.post_id = p.ID
			WHERE p.ID IS NULL"
		);
		
		return intval( $deleted );
	}

	/**
	 * Cleanup orphaned user data
	 *
	 * @return int Number of records deleted.
	 */
	public static function cleanup_orphaned_users() {
		global $wpdb;
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-database.php';
		
		$table = SEO_AI_Meta_Database::get_table_name( 'users' );
		$users_table = $wpdb->users;
		
		$deleted = $wpdb->query(
			"DELETE u FROM {$table} u
			LEFT JOIN {$users_table} wpu ON u.user_id = wpu.ID
			WHERE wpu.ID IS NULL"
		);
		
		return intval( $deleted );
	}

	/**
	 * Optimize database tables
	 *
	 * @return array Results of optimization.
	 */
	public static function optimize_tables() {
		global $wpdb;
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-database.php';
		
		$tables = array(
			SEO_AI_Meta_Database::get_table_name( 'settings' ),
			SEO_AI_Meta_Database::get_table_name( 'post_meta' ),
			SEO_AI_Meta_Database::get_table_name( 'users' ),
			SEO_AI_Meta_Database::get_table_name( 'usage_log' ),
		);
		
		$results = array();
		foreach ( $tables as $table ) {
			$result = $wpdb->query( "OPTIMIZE TABLE {$table}" );
			$results[ $table ] = $result !== false;
		}
		
		return $results;
	}

	/**
	 * Run full cleanup
	 *
	 * @param int $days_old Days to keep logs.
	 * @return array Cleanup results.
	 */
	public static function run_full_cleanup( $days_old = 90 ) {
		$results = array(
			'logs_deleted'      => self::cleanup_old_logs( $days_old ),
			'orphaned_meta'     => self::cleanup_orphaned_meta(),
			'orphaned_users'    => self::cleanup_orphaned_users(),
			'optimization'      => self::optimize_tables(),
		);
		
		return $results;
	}
}

