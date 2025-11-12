<?php
/**
 * Enhanced Bulk Generate Meta Tab Template
 *
 * @package SEO_AI_Meta
 */

// Calculate progress stats
$optimized_count     = $posts_with_meta;
$total_count         = $total_posts;
$pending_count       = $posts_without_meta;
$progress_percentage = $total_count > 0 ? round( ( $optimized_count / $total_count ) * 100 ) : 0;

// Check if user is authenticated
require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-api-client-v2.php';
$api_client      = new SEO_AI_Meta_API_Client_V2();
$is_authenticated = $api_client->is_authenticated();

// Get post types
$post_types = get_post_types( array( 'public' => true ), 'objects' );
$post_types_list = array();
foreach ( $post_types as $post_type ) {
	if ( ! in_array( $post_type->name, array( 'attachment' ), true ) ) {
		$post_types_list[ $post_type->name ] = $post_type->label;
	}
}

// Calculate stats for recommendations
$short_meta_count = 0; // We'll calculate this via JS
?>

<!-- Enhanced Bulk Generate Tab -->
<div id="seo-ai-meta-bulk-tab">
	<!-- Header with Stats -->
	<div style="margin-bottom: 32px;">
		<h1 style="font-size: 28px; font-weight: 700; color: #1f2937; margin: 0 0 8px 0;">
			<?php esc_html_e( 'Bulk Generate & Manage Meta Tags', 'seo-ai-meta-generator' ); ?>
		</h1>
		<p style="font-size: 14px; color: #6b7280; margin: 0;">
			<?php esc_html_e( 'Generate, optimize, and manage SEO meta tags for all your content in one place.', 'seo-ai-meta-generator' ); ?>
		</p>
	</div>

	<!-- Quick Stats Cards -->
	<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
		<!-- Total Posts -->
		<div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px;">
			<div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
				<div style="width: 40px; height: 40px; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
						<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
						<polyline points="14 2 14 8 20 8"/>
					</svg>
				</div>
				<div>
					<div style="font-size: 24px; font-weight: 700; color: #1f2937;"><?php echo esc_html( $total_count ); ?></div>
					<div style="font-size: 12px; color: #6b7280;"><?php esc_html_e( 'Total Posts', 'seo-ai-meta-generator' ); ?></div>
				</div>
			</div>
		</div>

		<!-- Optimized -->
		<div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px;">
			<div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
				<div style="width: 40px; height: 40px; background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5">
						<path d="M20 6L9 17l-5-5"/>
					</svg>
				</div>
				<div>
					<div style="font-size: 24px; font-weight: 700; color: #1f2937;"><?php echo esc_html( $optimized_count ); ?></div>
					<div style="font-size: 12px; color: #6b7280;"><?php esc_html_e( 'Optimized', 'seo-ai-meta-generator' ); ?></div>
				</div>
			</div>
		</div>

		<!-- Needs Attention -->
		<div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px;">
			<div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
				<div style="width: 40px; height: 40px; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
						<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
						<line x1="12" y1="9" x2="12" y2="13"/>
						<line x1="12" y1="17" x2="12.01" y2="17"/>
					</svg>
				</div>
				<div>
					<div style="font-size: 24px; font-weight: 700; color: #1f2937;"><?php echo esc_html( $pending_count ); ?></div>
					<div style="font-size: 12px; color: #6b7280;"><?php esc_html_e( 'Missing Meta', 'seo-ai-meta-generator' ); ?></div>
				</div>
			</div>
		</div>

		<!-- Completion Rate -->
		<div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px;">
			<div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
				<div style="width: 40px; height: 40px; background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
						<circle cx="12" cy="12" r="10"/>
						<polyline points="12 6 12 12 16 14"/>
					</svg>
				</div>
				<div>
					<div style="font-size: 24px; font-weight: 700; color: #1f2937;"><?php echo esc_html( $progress_percentage ); ?>%</div>
					<div style="font-size: 12px; color: #6b7280;"><?php esc_html_e( 'Complete', 'seo-ai-meta-generator' ); ?></div>
				</div>
			</div>
		</div>
	</div>

	<!-- Optimization Recommendations (if there are issues) -->
	<?php if ( $pending_count > 0 ) : ?>
	<div id="seo-ai-meta-recommendations" style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border: 1px solid #93c5fd; border-radius: 12px; padding: 20px; margin-bottom: 24px;">
		<div style="display: flex; align-items: start; gap: 16px;">
			<div style="width: 40px; height: 40px; background: #3b82f6; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
				<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
					<circle cx="12" cy="12" r="10"/>
					<path d="M12 16v-4M12 8h.01"/>
				</svg>
			</div>
			<div style="flex: 1;">
				<h3 style="font-size: 16px; font-weight: 600; color: #1e40af; margin: 0 0 8px 0;">
					<?php esc_html_e( '📊 Optimization Opportunities', 'seo-ai-meta-generator' ); ?>
				</h3>
				<ul style="list-style: none; padding: 0; margin: 0 0 16px 0;">
					<li style="font-size: 14px; color: #1e40af; margin-bottom: 6px;">
						• <?php printf( esc_html__( '%d posts missing meta descriptions', 'seo-ai-meta-generator' ), $pending_count ); ?>
					</li>
					<li id="seo-ai-meta-short-recommendations" style="font-size: 14px; color: #1e40af; margin-bottom: 6px; display: none;">
						<!-- Will be populated via JS -->
					</li>
				</ul>
				<?php if ( $is_authenticated ) : ?>
				<button type="button" id="seo-ai-meta-fix-all-btn" 
						style="padding: 10px 20px; background: #3b82f6; color: white; border: none; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.2s;"
						onmouseover="this.style.background='#2563eb';" 
						onmouseout="this.style.background='#3b82f6';">
					<?php esc_html_e( 'Generate Missing Meta Tags', 'seo-ai-meta-generator' ); ?>
				</button>
				<?php else : ?>
				<button type="button" onclick="seoAiMetaShowUpgradeModal();"
						style="padding: 10px 20px; background: #3b82f6; color: white; border: none; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.2s;"
						onmouseover="this.style.background='#2563eb';" 
						onmouseout="this.style.background='#3b82f6';">
					<?php esc_html_e( 'Unlock Bulk Generation', 'seo-ai-meta-generator' ); ?>
				</button>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<?php endif; ?>

	<!-- Filters and Search Bar -->
	<div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; margin-bottom: 24px;">
		<div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
			<!-- Post Type Filter -->
			<div style="position: relative;">
				<select id="seo-ai-meta-post-type-filter" 
						style="padding: 10px 36px 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; background: white; cursor: pointer; appearance: none;">
					<?php foreach ( $post_types_list as $type_name => $type_label ) : ?>
					<option value="<?php echo esc_attr( $type_name ); ?>" <?php selected( $type_name, 'post' ); ?>>
						<?php echo esc_html( $type_label ); ?>
					</option>
					<?php endforeach; ?>
				</select>
				<svg width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); pointer-events: none; color: #6b7280;">
					<polyline points="3 4.5 6 7.5 9 4.5"/>
				</svg>
			</div>

			<!-- Status Filter -->
			<div style="position: relative;">
				<select id="seo-ai-meta-status-filter" 
						style="padding: 10px 36px 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; background: white; cursor: pointer; appearance: none;">
					<option value="all"><?php esc_html_e( 'All Posts', 'seo-ai-meta-generator' ); ?></option>
					<option value="missing"><?php esc_html_e( 'Missing Meta', 'seo-ai-meta-generator' ); ?></option>
					<option value="complete"><?php esc_html_e( 'Complete', 'seo-ai-meta-generator' ); ?></option>
					<option value="short"><?php esc_html_e( 'Needs Improvement', 'seo-ai-meta-generator' ); ?></option>
				</select>
				<svg width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); pointer-events: none; color: #6b7280;">
					<polyline points="3 4.5 6 7.5 9 4.5"/>
				</svg>
			</div>

			<!-- Search -->
			<div style="flex: 1; min-width: 200px; position: relative;">
				<input type="text" id="seo-ai-meta-search-input" 
					   placeholder="<?php esc_attr_e( 'Search posts...', 'seo-ai-meta-generator' ); ?>"
					   style="width: 100%; padding: 10px 12px 10px 40px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #9ca3af;">
					<circle cx="11" cy="11" r="8"/>
					<path d="m21 21-4.35-4.35"/>
				</svg>
			</div>

			<!-- Bulk Actions -->
			<div style="display: flex; gap: 8px; align-items: center;">
				<button type="button" id="seo-ai-meta-select-all-btn"
						style="padding: 10px 16px; background: white; color: #374151; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; transition: all 0.2s;"
						onmouseover="this.style.background='#f9fafb';" 
						onmouseout="this.style.background='white';">
					<?php esc_html_e( 'Select All', 'seo-ai-meta-generator' ); ?>
				</button>
				<button type="button" id="seo-ai-meta-bulk-generate-selected-btn" 
						disabled
						style="padding: 10px 20px; background: #d1d5db; color: #6b7280; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: not-allowed; transition: all 0.2s;">
					<span id="seo-ai-meta-bulk-selected-text"><?php esc_html_e( 'Generate Selected (0)', 'seo-ai-meta-generator' ); ?></span>
				</button>
			</div>
		</div>
	</div>

	<!-- Posts Table -->
	<div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; margin-bottom: 24px;">
		<!-- Loading State -->
		<div id="seo-ai-meta-loading" style="display: flex; align-items: center; justify-content: center; padding: 60px 20px;">
			<div style="text-align: center;">
				<div class="seo-ai-meta-spinner" style="width: 40px; height: 40px; border: 4px solid #e5e7eb; border-top-color: #3b82f6; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 16px;"></div>
				<p style="color: #6b7280; font-size: 14px; margin: 0;"><?php esc_html_e( 'Loading posts...', 'seo-ai-meta-generator' ); ?></p>
			</div>
		</div>

		<!-- Posts Table Content -->
		<div id="seo-ai-meta-posts-table" style="display: none;">
			<table style="width: 100%; border-collapse: collapse;">
				<thead>
					<tr style="border-bottom: 2px solid #e5e7eb; background: #f9fafb;">
						<th style="padding: 16px 20px; text-align: left; width: 40px;">
							<input type="checkbox" id="seo-ai-meta-select-all-checkbox" style="cursor: pointer;">
						</th>
						<th style="padding: 16px 20px; text-align: left; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">
							<?php esc_html_e( 'Post Title', 'seo-ai-meta-generator' ); ?>
						</th>
						<th style="padding: 16px 20px; text-align: left; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; width: 140px;">
							<?php esc_html_e( 'Status', 'seo-ai-meta-generator' ); ?>
						</th>
						<th style="padding: 16px 20px; text-align: left; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; width: 100px;">
							<?php esc_html_e( 'Title', 'seo-ai-meta-generator' ); ?>
						</th>
						<th style="padding: 16px 20px; text-align: left; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; width: 100px;">
							<?php esc_html_e( 'Description', 'seo-ai-meta-generator' ); ?>
						</th>
						<th style="padding: 16px 20px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; width: 120px;">
							<?php esc_html_e( 'Actions', 'seo-ai-meta-generator' ); ?>
						</th>
					</tr>
				</thead>
				<tbody id="seo-ai-meta-posts-tbody">
					<!-- Posts will be inserted here via JavaScript -->
				</tbody>
			</table>
		</div>

		<!-- Empty State -->
		<div id="seo-ai-meta-empty-state" style="display: none; padding: 60px 20px; text-align: center;">
			<svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2" style="margin: 0 auto 16px;">
				<circle cx="12" cy="12" r="10"/>
				<line x1="12" y1="8" x2="12" y2="12"/>
				<line x1="12" y1="16" x2="12.01" y2="16"/>
			</svg>
			<p style="font-size: 16px; font-weight: 600; color: #1f2937; margin: 0 0 8px 0;"><?php esc_html_e( 'No posts found', 'seo-ai-meta-generator' ); ?></p>
			<p style="font-size: 14px; color: #6b7280; margin: 0;"><?php esc_html_e( 'Try adjusting your filters or search term.', 'seo-ai-meta-generator' ); ?></p>
		</div>
	</div>

	<!-- Pagination -->
	<div id="seo-ai-meta-pagination" style="display: none; margin-bottom: 24px;">
		<!-- Pagination will be inserted here via JavaScript -->
	</div>

	<!-- Bottom CTA Banner - AltText AI -->
	<?php if ( ! SEO_AI_Meta_Helpers::is_alttext_ai_active() ) : ?>
	<div class="seo-ai-meta-bottom-cta-banner" style="padding: 24px 28px; background: white; border-radius: 12px; border: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between; gap: 20px;">
		<div style="flex: 1;">
			<p style="font-size: 15px; color: #1f2937; margin: 0 0 4px 0; font-weight: 600; line-height: 1.4;">
				<?php esc_html_e( 'Complete your SEO stack → Try AltText AI for automated image accessibility.', 'seo-ai-meta-generator' ); ?>
			</p>
		</div>
		<button type="button" 
				onclick="seoAiMetaTrackEvent('alttext_ai_cta_click', {source: 'bulk_tab_bottom'}); window.open('<?php echo esc_url( SEO_AI_Meta_Helpers::get_alttext_ai_url() ); ?>', '_blank');"
				style="display: flex; align-items: center; gap: 8px; padding: 10px 20px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.2s; white-space: nowrap; box-shadow: 0 2px 4px rgba(16, 185, 129, 0.25);"
				onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 8px rgba(16, 185, 129, 0.35)';"
				onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(16, 185, 129, 0.25)';">
			<span><?php esc_html_e( 'Try AltText AI', 'seo-ai-meta-generator' ); ?></span>
			<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
				<line x1="5" y1="12" x2="19" y2="12"/>
				<polyline points="12 5 19 12 12 19"/>
			</svg>
		</button>
	</div>
	<?php endif; ?>
</div>

<style>
@keyframes spin {
	to { transform: rotate(360deg); }
}

@keyframes fadeInUp {
	from {
		opacity: 0;
		transform: translateY(10px);
	}
	to {
		opacity: 1;
		transform: translateY(0);
	}
}
</style>

<script>
jQuery(document).ready(function($) {
	// Bulk Tab JavaScript
	var currentPage = 1;
	var perPage = 20;
	var selectedPosts = [];

	// Load posts on page load
	loadPosts();

	// Filter changes
	$('#seo-ai-meta-post-type-filter, #seo-ai-meta-status-filter').on('change', function() {
		currentPage = 1;
		loadPosts();
	});

	// Search with debounce
	var searchTimeout;
	$('#seo-ai-meta-search-input').on('input', function() {
		clearTimeout(searchTimeout);
		searchTimeout = setTimeout(function() {
			currentPage = 1;
			loadPosts();
		}, 500);
	});

	// Select All checkbox
	$('#seo-ai-meta-select-all-checkbox').on('change', function() {
		var checked = $(this).prop('checked');
		$('.seo-ai-meta-post-checkbox').prop('checked', checked);
		updateSelectedPosts();
	});

	// Select All button
	$('#seo-ai-meta-select-all-btn').on('click', function() {
		var allChecked = $('.seo-ai-meta-post-checkbox:checked').length === $('.seo-ai-meta-post-checkbox').length;
		$('.seo-ai-meta-post-checkbox').prop('checked', !allChecked);
		$('#seo-ai-meta-select-all-checkbox').prop('checked', !allChecked);
		updateSelectedPosts();
	});

	// Fix All button
	$('#seo-ai-meta-fix-all-btn').on('click', function() {
		// Select all posts with missing status
		$('.seo-ai-meta-post-checkbox').each(function() {
			var status = $(this).closest('tr').data('status');
			if (status === 'missing') {
				$(this).prop('checked', true);
			}
		});
		updateSelectedPosts();
		
		// Trigger bulk generate
		setTimeout(function() {
			$('#seo-ai-meta-bulk-generate-selected-btn').click();
		}, 100);
	});

	// Bulk generate selected
	$('#seo-ai-meta-bulk-generate-selected-btn').on('click', function() {
		if (selectedPosts.length === 0) return;
		
		<?php if ( ! $is_authenticated ) : ?>
		seoAiMetaShowUpgradeModal();
		return;
		<?php else : ?>
		bulkGenerateSelected();
		<?php endif; ?>
	});

	// Load posts function
	function loadPosts() {
		$('#seo-ai-meta-loading').show();
		$('#seo-ai-meta-posts-table').hide();
		$('#seo-ai-meta-empty-state').hide();
		$('#seo-ai-meta-pagination').hide();

		var postType = $('#seo-ai-meta-post-type-filter').val();
		var status = $('#seo-ai-meta-status-filter').val();
		var search = $('#seo-ai-meta-search-input').val();

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'seo_ai_meta_get_posts',
				nonce: '<?php echo esc_js( wp_create_nonce( 'seo_ai_meta_bulk_nonce' ) ); ?>',
				post_type: postType,
				status: status,
				search: search,
				per_page: perPage,
				page: currentPage
			},
			success: function(response) {
				if (response.success) {
					renderPosts(response.data);
				} else {
					console.error('Failed to load posts:', response.data.message);
					showEmptyState();
				}
			},
			error: function() {
				console.error('AJAX error loading posts');
				showEmptyState();
			}
		});
	}

	// Render posts
	function renderPosts(data) {
		$('#seo-ai-meta-loading').hide();
		
		if (!data.posts || data.posts.length === 0) {
			showEmptyState();
			return;
		}

		$('#seo-ai-meta-posts-table').show();
		var tbody = $('#seo-ai-meta-posts-tbody');
		tbody.empty();

		$.each(data.posts, function(index, post) {
			var statusBadge = getStatusBadge(post.status);
			var titleLength = post.title_length || 0;
			var descLength = post.desc_length || 0;
			
			var titleBadge = getLengthBadge(titleLength, 30, 60);
			var descBadge = getLengthBadge(descLength, 120, 160);

			var row = $('<tr>').attr('data-post-id', post.id).attr('data-status', post.status)
				.css({
					'border-bottom': '1px solid #f3f4f6',
					'transition': 'background 0.2s'
				})
				.hover(
					function() { $(this).css('background', '#f9fafb'); },
					function() { $(this).css('background', 'white'); }
				);

			// Checkbox
			row.append($('<td>').css('padding', '16px 20px').html(
				'<input type="checkbox" class="seo-ai-meta-post-checkbox" value="' + post.id + '" style="cursor: pointer;">'
			));

			// Post Title
			row.append($('<td>').css({'padding': '16px 20px', 'font-size': '14px', 'color': '#1f2937'}).html(
				'<a href="' + post.edit_url + '" target="_blank" style="color: #3b82f6; text-decoration: none; font-weight: 500;">' + 
				post.title + 
				'</a><br><span style="font-size: 12px; color: #9ca3af;">' + post.date + '</span>'
			));

			// Status
			row.append($('<td>').css('padding', '16px 20px').html(statusBadge));

			// Title Length
			row.append($('<td>').css('padding', '16px 20px').html(titleBadge));

			// Description Length
			row.append($('<td>').css('padding', '16px 20px').html(descBadge));

			// Actions
			var actionsHtml = '';
			if (post.status === 'missing') {
				actionsHtml = '<button type="button" class="seo-ai-meta-generate-single-btn" data-post-id="' + post.id + '" ' +
					'style="padding: 6px 12px; background: #3b82f6; color: white; border: none; border-radius: 6px; font-size: 12px; font-weight: 500; cursor: pointer; transition: all 0.2s;" ' +
					'onmouseover="this.style.background=\'#2563eb\';" onmouseout="this.style.background=\'#3b82f6\';">' +
					'<?php esc_html_e( 'Generate', 'seo-ai-meta-generator' ); ?></button>';
			} else {
				actionsHtml = '<button type="button" class="seo-ai-meta-view-meta-btn" data-post-id="' + post.id + '" ' +
					'style="padding: 6px 12px; background: white; color: #374151; border: 1px solid #d1d5db; border-radius: 6px; font-size: 12px; font-weight: 500; cursor: pointer; transition: all 0.2s;" ' +
					'onmouseover="this.style.background=\'#f9fafb\';" onmouseout="this.style.background=\'white\';">' +
					'<?php esc_html_e( 'View', 'seo-ai-meta-generator' ); ?></button>';
			}
			row.append($('<td>').css({'padding': '16px 20px', 'text-align': 'right'}).html(actionsHtml));

			tbody.append(row);
		});

		// Bind checkbox events
		$('.seo-ai-meta-post-checkbox').on('change', updateSelectedPosts);
		
		// Bind generate button events
		$('.seo-ai-meta-generate-single-btn').on('click', function() {
			var postId = $(this).data('post-id');
			generateSinglePost(postId, $(this));
		});

		// Bind view button events
		$('.seo-ai-meta-view-meta-btn').on('click', function() {
			var postId = $(this).data('post-id');
			viewPostMeta(postId);
		});

		// Render pagination
		if (data.total_pages > 1) {
			renderPagination(data.current_page, data.total_pages);
		}
	}

	// Get status badge HTML
	function getStatusBadge(status) {
		var badges = {
			'complete': '<span style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; background: #d1fae5; color: #166534; border-radius: 6px; font-size: 12px; font-weight: 600;"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M20 6L9 17l-5-5"/></svg> ' + '<?php esc_html_e( 'Complete', 'seo-ai-meta-generator' ); ?>' + '</span>',
			'missing': '<span style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; background: #fee2e2; color: #991b1b; border-radius: 6px; font-size: 12px; font-weight: 600;"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg> ' + '<?php esc_html_e( 'Missing', 'seo-ai-meta-generator' ); ?>' + '</span>',
			'short': '<span style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; background: #fef3c7; color: #92400e; border-radius: 6px; font-size: 12px; font-weight: 600;"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg> ' + '<?php esc_html_e( 'Short', 'seo-ai-meta-generator' ); ?>' + '</span>'
		};
		return badges[status] || badges['missing'];
	}

	// Get length badge HTML
	function getLengthBadge(length, min, max) {
		if (length === 0) {
			return '<span style="font-size: 13px; color: #9ca3af;">—</span>';
		}
		
		var color = '#22c55e';
		if (length < min) {
			color = '#dc2626';
		} else if (length > max) {
			color = '#f59e0b';
		}
		
		return '<span style="font-size: 13px; font-weight: 600; color: ' + color + ';">' + length + '</span>';
	}

	// Show empty state
	function showEmptyState() {
		$('#seo-ai-meta-loading').hide();
		$('#seo-ai-meta-posts-table').hide();
		$('#seo-ai-meta-empty-state').show();
	}

	// Update selected posts
	function updateSelectedPosts() {
		selectedPosts = [];
		$('.seo-ai-meta-post-checkbox:checked').each(function() {
			selectedPosts.push(parseInt($(this).val()));
		});

		var $btn = $('#seo-ai-meta-bulk-generate-selected-btn');
		var $text = $('#seo-ai-meta-bulk-selected-text');
		
		if (selectedPosts.length > 0) {
			$btn.prop('disabled', false)
				.css({'background': 'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)', 'color': 'white', 'cursor': 'pointer'});
			$text.text('<?php esc_html_e( 'Generate Selected', 'seo-ai-meta-generator' ); ?>' + ' (' + selectedPosts.length + ')');
		} else {
			$btn.prop('disabled', true)
				.css({'background': '#d1d5db', 'color': '#6b7280', 'cursor': 'not-allowed'});
			$text.text('<?php esc_html_e( 'Generate Selected', 'seo-ai-meta-generator' ); ?>' + ' (0)');
		}

		// Update select all checkbox state
		var totalCheckboxes = $('.seo-ai-meta-post-checkbox').length;
		var checkedCheckboxes = $('.seo-ai-meta-post-checkbox:checked').length;
		$('#seo-ai-meta-select-all-checkbox').prop('checked', totalCheckboxes > 0 && totalCheckboxes === checkedCheckboxes);
	}

	// Generate single post
	function generateSinglePost(postId, $button) {
		<?php if ( ! $is_authenticated ) : ?>
		seoAiMetaShowUpgradeModal();
		return;
		<?php else : ?>
		var originalHtml = $button.html();
		$button.prop('disabled', true).html('<span class="seo-ai-meta-spinner" style="display: inline-block; width: 12px; height: 12px; border: 2px solid white; border-top-color: transparent; border-radius: 50%; animation: spin 1s linear infinite;"></span>');

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'seo_ai_meta_generate_single',
				nonce: '<?php echo esc_js( wp_create_nonce( 'seo_ai_meta_bulk_nonce' ) ); ?>',
				post_id: postId
			},
			success: function(response) {
				if (response.success) {
					// Update the row with new data
					var $row = $button.closest('tr');
					var post = response.data;
					
					// Update status badge
					$row.find('td:eq(2)').html(getStatusBadge(post.status));
					
					// Update length badges
					$row.find('td:eq(3)').html(getLengthBadge(post.title_length, 30, 60));
					$row.find('td:eq(4)').html(getLengthBadge(post.desc_length, 120, 160));
					
					// Update action button
					$button.parent().html(
						'<button type="button" class="seo-ai-meta-view-meta-btn" data-post-id="' + post.id + '" ' +
						'style="padding: 6px 12px; background: white; color: #374151; border: 1px solid #d1d5db; border-radius: 6px; font-size: 12px; font-weight: 500; cursor: pointer; transition: all 0.2s;" ' +
						'onmouseover="this.style.background=\'#f9fafb\';" onmouseout="this.style.background=\'white\';">' +
						'<?php esc_html_e( 'View', 'seo-ai-meta-generator' ); ?></button>'
					);
					
					// Show success notification
					showNotification('✅ Meta tags generated successfully!', 'success');
					
					// Reload stats
					setTimeout(function() {
						location.reload();
					}, 1500);
				} else {
					$button.prop('disabled', false).html(originalHtml);
					showNotification('❌ ' + response.data.message, 'error');
				}
			},
			error: function() {
				$button.prop('disabled', false).html(originalHtml);
				showNotification('❌ Failed to generate meta tags', 'error');
			}
		});
		<?php endif; ?>
	}

	// View post meta (placeholder for future modal)
	function viewPostMeta(postId) {
		// TODO: Implement meta view modal
		window.open($('tr[data-post-id="' + postId + '"] td:eq(1) a').attr('href'), '_blank');
	}

	// Bulk generate selected posts
	function bulkGenerateSelected() {
		if (selectedPosts.length === 0) return;

		// Show progress modal/notification
		showNotification('🚀 Generating meta tags for ' + selectedPosts.length + ' posts...', 'info');

		// Process posts one by one
		var processed = 0;
		var errors = 0;

		function processNext() {
			if (processed >= selectedPosts.length) {
				// All done
				if (errors === 0) {
					showNotification('✅ Successfully generated meta tags for all ' + processed + ' posts!', 'success');
				} else {
					showNotification('⚠️ Generated ' + (processed - errors) + ' posts. ' + errors + ' failed.', 'warning');
				}
				setTimeout(function() {
					location.reload();
				}, 2000);
				return;
			}

			var postId = selectedPosts[processed];
			
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'seo_ai_meta_generate_single',
					nonce: '<?php echo esc_js( wp_create_nonce( 'seo_ai_meta_bulk_nonce' ) ); ?>',
					post_id: postId
				},
				success: function(response) {
					if (!response.success) {
						errors++;
					}
					processed++;
					// Update progress
					showNotification('⏳ Processing ' + processed + ' of ' + selectedPosts.length + '...', 'info');
					// Small delay between requests
					setTimeout(processNext, 1000);
				},
				error: function() {
					errors++;
					processed++;
					setTimeout(processNext, 1000);
				}
			});
		}

		processNext();
	}

	// Render pagination
	function renderPagination(current, total) {
		if (total <= 1) {
			$('#seo-ai-meta-pagination').hide();
			return;
		}

		var html = '<div style="display: flex; justify-content: center; align-items: center; gap: 8px;">';
		
		// Previous button
		if (current > 1) {
			html += '<button type="button" class="seo-ai-meta-page-btn" data-page="' + (current - 1) + '" ' +
				'style="padding: 8px 12px; background: white; color: #374151; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; cursor: pointer;">' +
				'<?php esc_html_e( '← Previous', 'seo-ai-meta-generator' ); ?></button>';
		}

		// Page numbers (show max 5 pages around current)
		var startPage = Math.max(1, current - 2);
		var endPage = Math.min(total, current + 2);

		if (startPage > 1) {
			html += '<button type="button" class="seo-ai-meta-page-btn" data-page="1" ' +
				'style="padding: 8px 12px; background: white; color: #374151; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; cursor: pointer;">1</button>';
			if (startPage > 2) {
				html += '<span style="padding: 8px; color: #9ca3af;">...</span>';
			}
		}

		for (var i = startPage; i <= endPage; i++) {
			if (i === current) {
				html += '<button type="button" style="padding: 8px 12px; background: #3b82f6; color: white; border: 1px solid #3b82f6; border-radius: 6px; font-size: 14px; font-weight: 600;">' + i + '</button>';
			} else {
				html += '<button type="button" class="seo-ai-meta-page-btn" data-page="' + i + '" ' +
					'style="padding: 8px 12px; background: white; color: #374151; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; cursor: pointer;">' + i + '</button>';
			}
		}

		if (endPage < total) {
			if (endPage < total - 1) {
				html += '<span style="padding: 8px; color: #9ca3af;">...</span>';
			}
			html += '<button type="button" class="seo-ai-meta-page-btn" data-page="' + total + '" ' +
				'style="padding: 8px 12px; background: white; color: #374151; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; cursor: pointer;">' + total + '</button>';
		}

		// Next button
		if (current < total) {
			html += '<button type="button" class="seo-ai-meta-page-btn" data-page="' + (current + 1) + '" ' +
				'style="padding: 8px 12px; background: white; color: #374151; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; cursor: pointer;">' +
				'<?php esc_html_e( 'Next →', 'seo-ai-meta-generator' ); ?></button>';
		}

		html += '</div>';

		$('#seo-ai-meta-pagination').html(html).show();

		// Bind pagination click events
		$('.seo-ai-meta-page-btn').on('click', function() {
			currentPage = parseInt($(this).data('page'));
			loadPosts();
			$('html, body').animate({ scrollTop: $('#seo-ai-meta-bulk-tab').offset().top - 50 }, 300);
		});
	}

	// Show notification
	function showNotification(message, type) {
		// Remove existing notification
		$('.seo-ai-meta-notification').remove();

		var colors = {
			'success': { bg: '#d1fae5', border: '#86efac', text: '#166534' },
			'error': { bg: '#fee2e2', border: '#fecaca', text: '#991b1b' },
			'warning': { bg: '#fef3c7', border: '#fde047', text: '#92400e' },
			'info': { bg: '#dbeafe', border: '#93c5fd', text: '#1e40af' }
		};

		var color = colors[type] || colors['info'];

		var $notification = $('<div class="seo-ai-meta-notification">')
			.css({
				'position': 'fixed',
				'top': '20px',
				'right': '20px',
				'z-index': '100000',
				'background': color.bg,
				'border': '1px solid ' + color.border,
				'border-radius': '8px',
				'padding': '16px 20px',
				'box-shadow': '0 4px 6px rgba(0, 0, 0, 0.1)',
				'max-width': '400px',
				'animation': 'slideInRight 0.3s ease-out'
			})
			.html('<div style="font-size: 14px; font-weight: 500; color: ' + color.text + ';">' + message + '</div>');

		$('body').append($notification);

		// Auto-remove after 5 seconds
		setTimeout(function() {
			$notification.fadeOut(300, function() {
				$(this).remove();
			});
		}, 5000);
	}
});
</script>

