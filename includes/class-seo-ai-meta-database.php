<?php
/**
 * Database handler for SEO AI Meta Generator
 * Manages custom database tables and data access
 *
 * @package    SEO_AI_Meta
 * @subpackage SEO_AI_Meta/includes
 */
class SEO_AI_Meta_Database {

	/**
	 * Database version
	 *
	 * @var string
	 */
	private static $db_version = '1.0.0';

	/**
	 * Create custom database tables
	 */
	public static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Table: Settings
		$table_settings = $wpdb->prefix . 'seo_ai_meta_settings';
		$sql_settings = "CREATE TABLE IF NOT EXISTS {$table_settings} (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			setting_key varchar(255) NOT NULL,
			setting_value longtext,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY setting_key (setting_key)
		) {$charset_collate};";

		// Table: Post Meta
		$table_post_meta = $wpdb->prefix . 'seo_ai_meta_post_meta';
		$sql_post_meta = "CREATE TABLE IF NOT EXISTS {$table_post_meta} (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			post_id bigint(20) NOT NULL,
			meta_title varchar(255) DEFAULT NULL,
			meta_description text,
			focus_keyword varchar(255) DEFAULT NULL,
			seo_score decimal(5,2) DEFAULT NULL,
			seo_grade varchar(1) DEFAULT NULL,
			model varchar(50) DEFAULT NULL,
			generated_at datetime DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY post_id (post_id),
			KEY post_id_index (post_id),
			KEY generated_at_index (generated_at)
		) {$charset_collate};";

		// Table: User Data
		$table_users = $wpdb->prefix . 'seo_ai_meta_users';
		$sql_users = "CREATE TABLE IF NOT EXISTS {$table_users} (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			user_id bigint(20) NOT NULL,
			plan varchar(50) DEFAULT 'free',
			usage_count int(11) DEFAULT 0,
			reset_date date DEFAULT NULL,
			jwt_token text DEFAULT NULL,
			user_data longtext DEFAULT NULL,
			welcome_sent tinyint(1) DEFAULT 0,
			last_warning_date date DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY user_id (user_id),
			KEY user_id_index (user_id),
			KEY plan_index (plan)
		) {$charset_collate};";

		// Table: Usage Log
		$table_usage_log = $wpdb->prefix . 'seo_ai_meta_usage_log';
		$sql_usage_log = "CREATE TABLE IF NOT EXISTS {$table_usage_log} (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			user_id bigint(20) NOT NULL,
			post_id bigint(20) DEFAULT NULL,
			action varchar(50) DEFAULT 'generate',
			model varchar(50) DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_id_index (user_id),
			KEY post_id_index (post_id),
			KEY created_at_index (created_at)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql_settings );
		dbDelta( $sql_post_meta );
		dbDelta( $sql_users );
		dbDelta( $sql_usage_log );

		// Update database version
		update_option( 'seo_ai_meta_db_version', self::$db_version );
	}

	/**
	 * Get table name
	 *
	 * @param string $table Table name.
	 * @return string
	 */
	public static function get_table_name( $table ) {
		global $wpdb;
		return $wpdb->prefix . 'seo_ai_meta_' . $table;
	}

	/**
	 * Get setting
	 *
	 * @param string $key Setting key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public static function get_setting( $key, $default = null ) {
		global $wpdb;
		$table = self::get_table_name( 'settings' );
		
		$value = $wpdb->get_var( $wpdb->prepare(
			"SELECT setting_value FROM {$table} WHERE setting_key = %s",
			$key
		) );

		if ( $value === null ) {
			return $default;
		}

		// Try to decode JSON
		$decoded = json_decode( $value, true );
		return ( json_last_error() === JSON_ERROR_NONE ) ? $decoded : $value;
	}

	/**
	 * Update setting
	 *
	 * @param string $key Setting key.
	 * @param mixed  $value Setting value.
	 * @return bool
	 */
	public static function update_setting( $key, $value ) {
		global $wpdb;
		$table = self::get_table_name( 'settings' );

		// Encode arrays/objects as JSON
		if ( is_array( $value ) || is_object( $value ) ) {
			$value = wp_json_encode( $value );
		}

		$result = $wpdb->replace(
			$table,
			array(
				'setting_key'   => $key,
				'setting_value' => $value,
			),
			array( '%s', '%s' )
		);

		return $result !== false;
	}

	/**
	 * Get post meta
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key Meta key (optional).
	 * @return array|mixed|null
	 */
	public static function get_post_meta( $post_id, $key = null ) {
		global $wpdb;
		$table = self::get_table_name( 'post_meta' );

		$row = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$table} WHERE post_id = %d",
			$post_id
		), ARRAY_A );

		if ( ! $row ) {
			return null;
		}

		// Remove ID and timestamps
		unset( $row['id'] );
		unset( $row['created_at'] );
		unset( $row['updated_at'] );

		if ( $key ) {
			return isset( $row[ $key ] ) ? $row[ $key ] : null;
		}

		return $row;
	}

	/**
	 * Update post meta
	 *
	 * @param int   $post_id Post ID.
	 * @param array $data Meta data.
	 * @return bool
	 */
	public static function update_post_meta( $post_id, $data ) {
		global $wpdb;
		$table = self::get_table_name( 'post_meta' );

		// Prepare data with defaults
		$insert_data = array(
			'post_id' => $post_id,
			'meta_title' => isset( $data['meta_title'] ) ? $data['meta_title'] : null,
			'meta_description' => isset( $data['meta_description'] ) ? $data['meta_description'] : null,
			'focus_keyword' => isset( $data['focus_keyword'] ) ? $data['focus_keyword'] : null,
			'seo_score' => isset( $data['seo_score'] ) ? floatval( $data['seo_score'] ) : null,
			'seo_grade' => isset( $data['seo_grade'] ) ? $data['seo_grade'] : null,
			'model' => isset( $data['model'] ) ? $data['model'] : null,
			'generated_at' => isset( $data['generated_at'] ) ? $data['generated_at'] : current_time( 'mysql' ),
			'updated_at' => current_time( 'mysql' ),
		);

		$formats = array(
			'%d', // post_id
			'%s', // meta_title
			'%s', // meta_description
			'%s', // focus_keyword
			'%f', // seo_score
			'%s', // seo_grade
			'%s', // model
			'%s', // generated_at
			'%s', // updated_at
		);

		$result = $wpdb->replace( $table, $insert_data, $formats );

		return $result !== false;
	}

	/**
	 * Get user data
	 *
	 * @param int    $user_id User ID.
	 * @param string $key Data key (optional).
	 * @return array|mixed|null
	 */
	public static function get_user_data( $user_id, $key = null ) {
		global $wpdb;
		$table = self::get_table_name( 'users' );

		$row = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$table} WHERE user_id = %d",
			$user_id
		), ARRAY_A );

		if ( ! $row ) {
			return null;
		}

		// Decode JSON fields
		if ( ! empty( $row['user_data'] ) ) {
			$row['user_data'] = json_decode( $row['user_data'], true );
		}

		// Remove ID and timestamps
		unset( $row['id'] );
		unset( $row['created_at'] );
		unset( $row['updated_at'] );

		if ( $key ) {
			return isset( $row[ $key ] ) ? $row[ $key ] : null;
		}

		return $row;
	}

	/**
	 * Update user data
	 *
	 * @param int   $user_id User ID.
	 * @param array $data User data.
	 * @return bool
	 */
	public static function update_user_data( $user_id, $data ) {
		global $wpdb;
		$table = self::get_table_name( 'users' );

		$data['user_id'] = $user_id;
		$data['updated_at'] = current_time( 'mysql' );

		// Encode JSON fields
		if ( isset( $data['user_data'] ) && ( is_array( $data['user_data'] ) || is_object( $data['user_data'] ) ) ) {
			$data['user_data'] = wp_json_encode( $data['user_data'] );
		}

		$result = $wpdb->replace(
			$table,
			$data,
			array(
				'%d',    // user_id
				'%s',    // plan
				'%d',    // usage_count
				'%s',    // reset_date
				'%s',    // jwt_token
				'%s',    // user_data
				'%d',    // welcome_sent
				'%s',    // last_warning_date
				'%s',    // updated_at
			)
		);

		return $result !== false;
	}

	/**
	 * Log usage
	 *
	 * @param int    $user_id User ID.
	 * @param int    $post_id Post ID.
	 * @param string $action Action type.
	 * @param string $model Model used.
	 * @return bool
	 */
	public static function log_usage( $user_id, $post_id = null, $action = 'generate', $model = null ) {
		global $wpdb;
		$table = self::get_table_name( 'usage_log' );

		return $wpdb->insert(
			$table,
			array(
				'user_id' => $user_id,
				'post_id' => $post_id,
				'action'  => $action,
				'model'   => $model,
			),
			array( '%d', '%d', '%s', '%s' )
		) !== false;
	}

	/**
	 * Get usage count for user
	 *
	 * @param int    $user_id User ID.
	 * @param string $start_date Start date (optional).
	 * @param string $end_date End date (optional).
	 * @return int
	 */
	public static function get_usage_count( $user_id, $start_date = null, $end_date = null ) {
		global $wpdb;
		$table = self::get_table_name( 'usage_log' );

		$where = $wpdb->prepare( 'user_id = %d', $user_id );

		if ( $start_date ) {
			$where .= $wpdb->prepare( ' AND created_at >= %s', $start_date );
		}

		if ( $end_date ) {
			$where .= $wpdb->prepare( ' AND created_at <= %s', $end_date );
		}

		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE {$where}" );
	}

	/**
	 * Migrate data from WordPress options/post meta to custom tables
	 */
	public static function migrate_data() {
		global $wpdb;

		// Migrate settings
		$old_settings = get_option( 'seo_ai_meta_settings', array() );
		if ( ! empty( $old_settings ) ) {
			self::update_setting( 'settings', $old_settings );
		}

		// Migrate JWT token
		$old_token = get_option( 'seo_ai_meta_jwt_token', '' );
		if ( ! empty( $old_token ) ) {
			$current_user_id = get_current_user_id();
			if ( $current_user_id > 0 ) {
				self::update_user_data( $current_user_id, array( 'jwt_token' => $old_token ) );
			}
		}

		// Migrate user data
		$old_user_data = get_option( 'seo_ai_meta_user_data', null );
		if ( $old_user_data !== null ) {
			$current_user_id = get_current_user_id();
			if ( $current_user_id > 0 ) {
				self::update_user_data( $current_user_id, array( 'user_data' => $old_user_data ) );
			}
		}

		// Migrate post meta
		$post_meta_keys = array(
			'_seo_ai_meta_title',
			'_seo_ai_meta_description',
			'_seo_ai_meta_generated_at',
			'_seo_ai_meta_model',
			'_seo_ai_meta_seo_score',
			'_seo_ai_meta_seo_grade',
			'_seo_ai_meta_focus_keyword',
		);

		$posts = $wpdb->get_results(
			"SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key LIKE '_seo_ai_meta_%'"
		);

		foreach ( $posts as $post ) {
			$post_id = $post->post_id;
			$meta_data = array();

			foreach ( $post_meta_keys as $meta_key ) {
				$value = get_post_meta( $post_id, $meta_key, true );
				if ( $value !== '' ) {
					switch ( $meta_key ) {
						case '_seo_ai_meta_title':
							$meta_data['meta_title'] = $value;
							break;
						case '_seo_ai_meta_description':
							$meta_data['meta_description'] = $value;
							break;
						case '_seo_ai_meta_generated_at':
							$meta_data['generated_at'] = $value;
							break;
						case '_seo_ai_meta_model':
							$meta_data['model'] = $value;
							break;
						case '_seo_ai_meta_seo_score':
							$meta_data['seo_score'] = floatval( $value );
							break;
						case '_seo_ai_meta_seo_grade':
							$meta_data['seo_grade'] = $value;
							break;
						case '_seo_ai_meta_focus_keyword':
							$meta_data['focus_keyword'] = $value;
							break;
					}
				}
			}

			if ( ! empty( $meta_data ) ) {
				self::update_post_meta( $post_id, $meta_data );
			}
		}

		// Migrate user meta
		$users = get_users( array( 'number' => -1 ) );
		foreach ( $users as $user ) {
			$user_data = array();

			$plan = get_user_meta( $user->ID, 'seo_ai_meta_plan', true );
			if ( $plan ) {
				$user_data['plan'] = $plan;
			}

			$usage_count = get_user_meta( $user->ID, 'seo_ai_meta_usage_count', true );
			if ( $usage_count !== '' ) {
				$user_data['usage_count'] = intval( $usage_count );
			}

			$reset_date = get_user_meta( $user->ID, 'seo_ai_meta_reset_date', true );
			if ( $reset_date ) {
				$user_data['reset_date'] = $reset_date;
			}

			$welcome_sent = get_user_meta( $user->ID, 'seo_ai_meta_welcome_sent', true );
			if ( $welcome_sent ) {
				$user_data['welcome_sent'] = 1;
			}

			if ( ! empty( $user_data ) ) {
				self::update_user_data( $user->ID, $user_data );
			}
		}

		// Mark migration as complete
		update_option( 'seo_ai_meta_migration_complete', true );
	}

	/**
	 * Drop all custom tables
	 */
	public static function drop_tables() {
		global $wpdb;

		$tables = array(
			'settings',
			'post_meta',
			'users',
			'usage_log',
		);

		foreach ( $tables as $table ) {
			$table_name = self::get_table_name( $table );
			$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );
		}

		delete_option( 'seo_ai_meta_db_version' );
		delete_option( 'seo_ai_meta_migration_complete' );
	}
}
