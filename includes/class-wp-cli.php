<?php
/**
 * WP-CLI Commands for SEO AI Meta Generator
 *
 * @package    SEO_AI_Meta
 * @subpackage SEO_AI_Meta/includes
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * WP-CLI commands for SEO AI Meta Generator
 */
class SEO_AI_Meta_WP_CLI extends WP_CLI_Command {

	/**
	 * Generate meta tags for posts
	 *
	 * ## OPTIONS
	 *
	 * [--post-ids=<ids>]
	 * : Comma-separated post IDs to generate meta for
	 *
	 * [--post-type=<type>]
	 * : Post type to generate meta for (default: post)
	 *
	 * [--limit=<number>]
	 * : Maximum number of posts to process (default: 100)
	 *
	 * [--model=<model>]
	 * : Model to use (gpt-4o-mini, gpt-4-turbo)
	 *
	 * ## EXAMPLES
	 *
	 *     wp seo-ai-meta generate --post-ids=1,2,3
	 *     wp seo-ai-meta generate --post-type=post --limit=50
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function generate( $args, $assoc_args ) {
		$post_ids = isset( $assoc_args['post-ids'] ) ? explode( ',', $assoc_args['post-ids'] ) : array();
		$post_type = isset( $assoc_args['post-type'] ) ? $assoc_args['post-type'] : 'post';
		$limit = isset( $assoc_args['limit'] ) ? intval( $assoc_args['limit'] ) : 100;
		$model = isset( $assoc_args['model'] ) ? $assoc_args['model'] : null;

		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-generator.php';
		$generator = new SEO_AI_Meta_Generator();

		if ( ! empty( $post_ids ) ) {
			$posts = array_map( 'intval', $post_ids );
		} else {
			$query = new WP_Query( array(
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'posts_per_page' => $limit,
				'fields'         => 'ids',
			) );
			$posts = $query->posts;
		}

		if ( empty( $posts ) ) {
			WP_CLI::error( 'No posts found to process.' );
		}

		$progress = \WP_CLI\Utils\make_progress_bar( 'Generating meta tags', count( $posts ) );
		$success = 0;
		$errors = 0;

		foreach ( $posts as $post_id ) {
			$result = $generator->generate( $post_id, $model );
			
			if ( is_wp_error( $result ) ) {
				WP_CLI::warning( "Post {$post_id}: " . $result->get_error_message() );
				$errors++;
			} else {
				$success++;
			}
			
			$progress->tick();
			usleep( 500000 ); // 0.5 second delay
		}

		$progress->finish();
		WP_CLI::success( "Generated meta tags for {$success} posts. {$errors} errors." );
	}

	/**
	 * Cleanup database
	 *
	 * ## OPTIONS
	 *
	 * [--days=<days>]
	 * : Days to keep logs (default: 90)
	 *
	 * [--optimize]
	 * : Also optimize tables
	 *
	 * ## EXAMPLES
	 *
	 *     wp seo-ai-meta cleanup --days=30
	 *     wp seo-ai-meta cleanup --optimize
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function cleanup( $args, $assoc_args ) {
		$days = isset( $assoc_args['days'] ) ? intval( $assoc_args['days'] ) : 90;
		$optimize = isset( $assoc_args['optimize'] );

		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-database-cleanup.php';
		
		WP_CLI::line( 'Starting database cleanup...' );
		
		$results = SEO_AI_Meta_Database_Cleanup::run_full_cleanup( $days );
		
		WP_CLI::line( "Deleted {$results['logs_deleted']} old log entries" );
		WP_CLI::line( "Deleted {$results['orphaned_meta']} orphaned meta records" );
		WP_CLI::line( "Deleted {$results['orphaned_users']} orphaned user records" );
		
		if ( $optimize ) {
			WP_CLI::line( 'Optimizing tables...' );
			$optimization = SEO_AI_Meta_Database_Cleanup::optimize_tables();
			foreach ( $optimization as $table => $success ) {
				WP_CLI::line( "  {$table}: " . ( $success ? 'Optimized' : 'Failed' ) );
			}
		}
		
		WP_CLI::success( 'Cleanup completed.' );
	}

	/**
	 * Export meta tags to CSV
	 *
	 * ## OPTIONS
	 *
	 * [--output=<file>]
	 * : Output file path (default: seo-meta-export.csv)
	 *
	 * ## EXAMPLES
	 *
	 *     wp seo-ai-meta export --output=meta-export.csv
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function export( $args, $assoc_args ) {
		$output = isset( $assoc_args['output'] ) ? $assoc_args['output'] : 'seo-meta-export.csv';
		
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-database.php';
		
		global $wpdb;
		$table = SEO_AI_Meta_Database::get_table_name( 'post_meta' );
		
		$meta_tags = $wpdb->get_results(
			"SELECT post_id, meta_title, meta_description, generated_at 
			FROM {$table} 
			WHERE meta_title IS NOT NULL AND meta_title != ''",
			ARRAY_A
		);
		
		if ( empty( $meta_tags ) ) {
			WP_CLI::error( 'No meta tags found to export.' );
		}
		
		$handle = fopen( $output, 'w' );
		if ( ! $handle ) {
			WP_CLI::error( "Cannot open file for writing: {$output}" );
		}
		
		// Add BOM for UTF-8
		fprintf( $handle, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );
		
		// Headers
		fputcsv( $handle, array( 'Post ID', 'Post Title', 'Meta Title', 'Meta Description', 'Generated At' ) );
		
		// Data
		foreach ( $meta_tags as $meta ) {
			$post = get_post( $meta['post_id'] );
			fputcsv( $handle, array(
				$meta['post_id'],
				$post ? $post->post_title : '',
				$meta['meta_title'],
				$meta['meta_description'],
				$meta['generated_at'],
			) );
		}
		
		fclose( $handle );
		WP_CLI::success( "Exported " . count( $meta_tags ) . " meta tags to {$output}" );
	}

	/**
	 * Show usage statistics
	 *
	 * ## EXAMPLES
	 *
	 *     wp seo-ai-meta stats
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function stats( $args, $assoc_args ) {
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-database.php';
		
		global $wpdb;
		$table = SEO_AI_Meta_Database::get_table_name( 'post_meta' );
		
		$total = $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE meta_title IS NOT NULL AND meta_title != ''" );
		$total_users = $wpdb->get_var( "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}seo_ai_meta_users" );
		
		WP_CLI::line( "Total meta tags generated: {$total}" );
		WP_CLI::line( "Total users: {$total_users}" );
	}
}

WP_CLI::add_command( 'seo-ai-meta', 'SEO_AI_Meta_WP_CLI' );

