<?php
/**
 * Provide a bulk generate view for the plugin
 *
 * @package    SEO_AI_Meta
 * @subpackage SEO_AI_Meta/admin/partials
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once SEO_AI_META_PLUGIN_DIR . 'admin/class-seo-ai-meta-bulk.php';

$paged = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
$per_page = 20;
$tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'generate';

if ( $tab === 'optimize' ) {
	$query = SEO_AI_Meta_Bulk::get_posts_with_meta( $per_page, $paged );
	$total_posts = $query->found_posts;
} else {
	$query = SEO_AI_Meta_Bulk::get_posts_without_meta( $per_page, $paged );
	$total_posts = $query->found_posts;
}
?>

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	
	<!-- Tabs -->
	<div class="nav-tab-wrapper" style="margin: 20px 0 0 0;">
		<a href="<?php echo esc_url( add_query_arg( array( 'tab' => 'generate', 'paged' => 1 ) ) ); ?>" class="nav-tab <?php echo $tab === 'generate' ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Generate New', 'seo-ai-meta-generator' ); ?>
		</a>
		<a href="<?php echo esc_url( add_query_arg( array( 'tab' => 'optimize', 'paged' => 1 ) ) ); ?>" class="nav-tab <?php echo $tab === 'optimize' ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Optimize Existing', 'seo-ai-meta-generator' ); ?>
		</a>
	</div>
	
	<div class="seo-ai-meta-bulk-header" style="margin: 20px 0;">
		<?php if ( $tab === 'optimize' ) : ?>
			<p><?php esc_html_e( 'Regenerate and improve SEO meta tags for posts that already have them.', 'seo-ai-meta-generator' ); ?></p>
			<p>
				<strong><?php esc_html_e( 'Found:', 'seo-ai-meta-generator' ); ?></strong>
				<?php echo esc_html( $total_posts ); ?> <?php esc_html_e( 'posts with meta tags', 'seo-ai-meta-generator' ); ?>
			</p>
		<?php else : ?>
			<p><?php esc_html_e( 'Generate SEO meta tags for posts that don\'t have them yet.', 'seo-ai-meta-generator' ); ?></p>
			<p>
				<strong><?php esc_html_e( 'Found:', 'seo-ai-meta-generator' ); ?></strong>
				<?php echo esc_html( $total_posts ); ?> <?php esc_html_e( 'posts without meta tags', 'seo-ai-meta-generator' ); ?>
			</p>
		<?php endif; ?>
	</div>

	<?php if ( $query->have_posts() ) : ?>
		<form id="seo-ai-meta-bulk-form">
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<td class="check-column">
							<input type="checkbox" id="seo-ai-meta-select-all">
						</td>
						<th><?php esc_html_e( 'ID', 'seo-ai-meta-generator' ); ?></th>
						<th><?php esc_html_e( 'Title', 'seo-ai-meta-generator' ); ?></th>
						<th><?php esc_html_e( 'Date', 'seo-ai-meta-generator' ); ?></th>
						<th><?php esc_html_e( 'Status', 'seo-ai-meta-generator' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php while ( $query->have_posts() ) : $query->the_post(); ?>
						<tr>
							<th class="check-column">
								<input type="checkbox" name="post_ids[]" value="<?php echo esc_attr( get_the_ID() ); ?>" class="seo-ai-meta-post-checkbox">
							</th>
							<td><?php echo esc_html( get_the_ID() ); ?></td>
							<td>
								<strong><?php echo esc_html( get_the_title() ); ?></strong>
								<div class="row-actions">
									<a href="<?php echo esc_url( get_edit_post_link() ); ?>" target="_blank">
										<?php esc_html_e( 'Edit', 'seo-ai-meta-generator' ); ?>
									</a>
								</div>
							</td>
							<td><?php echo esc_html( get_the_date() ); ?></td>
							<td>
								<?php if ( $tab === 'optimize' ) : ?>
									<?php
									$meta_title = get_post_meta( get_the_ID(), '_seo_ai_meta_title', true );
									$meta_desc = get_post_meta( get_the_ID(), '_seo_ai_meta_description', true );
									?>
									<span class="seo-ai-meta-status-has-meta" style="color: #46b450;">
										<?php esc_html_e( 'Has Meta', 'seo-ai-meta-generator' ); ?>
									</span>
									<?php if ( $meta_title ) : ?>
										<div style="font-size: 11px; color: #666; margin-top: 4px; max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
											<?php echo esc_html( $meta_title ); ?>
										</div>
									<?php endif; ?>
								<?php else : ?>
									<span class="seo-ai-meta-status-no-meta" style="color: #dc3232;">
										<?php esc_html_e( 'No Meta', 'seo-ai-meta-generator' ); ?>
									</span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endwhile; ?>
				</tbody>
			</table>

			<div class="seo-ai-meta-bulk-actions" style="margin: 20px 0;">
				<?php if ( $tab === 'optimize' ) : ?>
					<button type="button" id="seo-ai-meta-bulk-optimize-btn" class="button button-primary">
						<?php esc_html_e( '🔄 Optimize Meta for Selected Posts', 'seo-ai-meta-generator' ); ?>
					</button>
				<?php else : ?>
					<button type="button" id="seo-ai-meta-bulk-generate-btn" class="button button-primary">
						<?php esc_html_e( 'Generate Meta for Selected Posts', 'seo-ai-meta-generator' ); ?>
					</button>
				<?php endif; ?>
				<span class="spinner" id="seo-ai-meta-bulk-spinner" style="float: none; margin-left: 10px;"></span>
			</div>

			<div id="seo-ai-meta-bulk-progress" style="display: none; margin: 20px 0;">
				<div class="seo-ai-meta-progress-bar" style="background: #ddd; height: 30px; border-radius: 5px; overflow: hidden; position: relative;">
					<div class="seo-ai-meta-progress-fill" style="background: #46b450; height: 100%; width: 0%; transition: width 0.3s; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
						<span class="seo-ai-meta-progress-text">0%</span>
					</div>
				</div>
				<p class="seo-ai-meta-progress-status"></p>
			</div>

			<div id="seo-ai-meta-bulk-results" style="margin: 20px 0;"></div>
		</form>

		<?php
		// Pagination
		$page_links = paginate_links(
			array(
				'base'      => add_query_arg( array( 'paged' => '%#%', 'tab' => $tab ) ),
				'format'    => '',
				'prev_text' => __( '&laquo;' ),
				'next_text' => __( '&raquo;' ),
				'total'     => $query->max_num_pages,
				'current'   => $paged,
			)
		);
		if ( $page_links ) {
			echo '<div class="tablenav"><div class="tablenav-pages">' . wp_kses_post( $page_links ) . '</div></div>';
		}
		wp_reset_postdata();
		?>
	<?php else : ?>
		<div class="notice notice-success">
			<p><?php esc_html_e( 'All posts have meta tags! Great job!', 'seo-ai-meta-generator' ); ?></p>
		</div>
	<?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
	// Select all checkbox
	$('#seo-ai-meta-select-all').on('change', function() {
		$('.seo-ai-meta-post-checkbox').prop('checked', $(this).prop('checked'));
	});

		// Bulk optimize button
		$('#seo-ai-meta-bulk-optimize-btn').on('click', function(e) {
			e.preventDefault();
			
			var $btn = $(this);
			var $spinner = $('#seo-ai-meta-bulk-spinner');
			var $progress = $('#seo-ai-meta-bulk-progress');
			var $results = $('#seo-ai-meta-bulk-results');
			
			var selectedPosts = [];
			$('.seo-ai-meta-post-checkbox:checked').each(function() {
				selectedPosts.push($(this).val());
			});

			if (selectedPosts.length === 0) {
				alert('<?php esc_html_e( 'Please select at least one post.', 'seo-ai-meta-generator' ); ?>');
				return;
			}

			if (!confirm('<?php esc_html_e( 'This will regenerate meta tags for selected posts. Continue?', 'seo-ai-meta-generator' ); ?>')) {
				return;
			}

			$btn.prop('disabled', true);
			$spinner.addClass('is-active');
			$progress.show();
			$results.html('');

			var processed = 0;
			var total = selectedPosts.length;
			
			function processNext(index) {
				if (index >= total) {
					$spinner.removeClass('is-active');
					$btn.prop('disabled', false);
					var finalPercentage = 100;
					$('.seo-ai-meta-progress-fill').css('width', finalPercentage + '%').find('.seo-ai-meta-progress-text').text(finalPercentage + '%');
					$('.seo-ai-meta-progress-status').text('Completed: ' + processed + ' of ' + total + ' posts processed.');
					
					// Show summary
					if (processed === total) {
						$results.html('<div class="notice notice-success"><p>Successfully optimized meta tags for all ' + total + ' posts!</p></div>');
					} else {
						$results.html('<div class="notice notice-warning"><p>Processed ' + processed + ' of ' + total + ' posts. Some posts may have failed. Check the browser console for details.</p></div>');
					}
					
					// Reload page after 2 seconds to show updated status
					setTimeout(function() {
						location.reload();
					}, 2000);
					return;
				}

				var postId = selectedPosts[index];
				var percentage = Math.round(((index + 1) / total) * 100);
				$('.seo-ai-meta-progress-fill').css('width', percentage + '%').find('.seo-ai-meta-progress-text').text(percentage + '%');
				$('.seo-ai-meta-progress-status').text('Processing post ' + (index + 1) + ' of ' + total + '...');

				$.ajax({
					url: seoAiMetaAjax.ajaxurl,
					type: 'POST',
					data: {
						action: 'seo_ai_meta_generate',
						nonce: seoAiMetaAjax.nonce,
						post_id: postId
					},
					success: function(response) {
						if (response.success) {
							processed++;
						} else {
							console.error('Generation failed for post', postId, ':', response.data);
						}
						
						// Process next
						setTimeout(function() {
							processNext(index + 1);
						}, 600); // Small delay between requests
					},
					error: function(xhr, status, error) {
						console.error('AJAX Error for post', postId, ':', status, error);
						// Continue processing even on error
						setTimeout(function() {
							processNext(index + 1);
						}, 600);
					}
				});
			}

			// Process one at a time
			processNext(0);
		});

		// Bulk generate button
		$('#seo-ai-meta-bulk-generate-btn').on('click', function(e) {
		e.preventDefault();
		
		var $btn = $(this);
		var $spinner = $('#seo-ai-meta-bulk-spinner');
		var $progress = $('#seo-ai-meta-bulk-progress');
		var $results = $('#seo-ai-meta-bulk-results');
		
		var selectedPosts = [];
		$('.seo-ai-meta-post-checkbox:checked').each(function() {
			selectedPosts.push($(this).val());
		});

		if (selectedPosts.length === 0) {
			alert('<?php esc_html_e( 'Please select at least one post.', 'seo-ai-meta-generator' ); ?>');
			return;
		}

		$btn.prop('disabled', true);
		$spinner.addClass('is-active');
		$progress.show();
		$results.html('');

		var processed = 0;
		var total = selectedPosts.length;
		
		function processNext(index) {
			if (index >= total) {
				$spinner.removeClass('is-active');
				$btn.prop('disabled', false);
				var finalPercentage = 100;
				$('.seo-ai-meta-progress-fill').css('width', finalPercentage + '%').find('.seo-ai-meta-progress-text').text(finalPercentage + '%');
				$('.seo-ai-meta-progress-status').text('Completed: ' + processed + ' of ' + total + ' posts processed.');
				
				// Show summary
				if (processed === total) {
					$results.html('<div class="notice notice-success"><p>Successfully generated meta tags for all ' + total + ' posts!</p></div>');
				} else {
					$results.html('<div class="notice notice-warning"><p>Processed ' + processed + ' of ' + total + ' posts. Some posts may have failed. Check the browser console for details.</p></div>');
				}
				
				// Reload page after 2 seconds to show updated status
				setTimeout(function() {
					location.reload();
				}, 2000);
				return;
			}

			var postId = selectedPosts[index];
			var percentage = Math.round(((index + 1) / total) * 100);
			$('.seo-ai-meta-progress-fill').css('width', percentage + '%').find('.seo-ai-meta-progress-text').text(percentage + '%');
			$('.seo-ai-meta-progress-status').text('Processing post ' + (index + 1) + ' of ' + total + '...');

			$.ajax({
				url: seoAiMetaAjax.ajaxurl,
				type: 'POST',
				data: {
					action: 'seo_ai_meta_generate',
					nonce: seoAiMetaAjax.nonce,
					post_id: postId
				},
				success: function(response) {
					if (response.success) {
						processed++;
					} else {
						console.error('Generation failed for post', postId, ':', response.data);
					}
					
					// Process next
					setTimeout(function() {
						processNext(index + 1);
					}, 600); // Small delay between requests
				},
				error: function(xhr, status, error) {
					console.error('AJAX Error for post', postId, ':', status, error);
					console.error('Response:', xhr.responseText);
					// Continue processing even on error
					setTimeout(function() {
						processNext(index + 1);
					}, 600);
				}
			});
		}

		// Start processing
		processNext(0);
	});
});
</script>

