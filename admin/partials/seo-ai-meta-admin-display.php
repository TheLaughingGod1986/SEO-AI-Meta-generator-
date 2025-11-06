<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @package    SEO_AI_Meta
 * @subpackage SEO_AI_Meta/admin/partials
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-usage-tracker.php';
require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-usage-governance.php';
require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-api-client-v2.php';
require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-helpers.php';
require_once SEO_AI_META_PLUGIN_DIR . 'admin/class-seo-ai-meta-bulk.php';
require_once SEO_AI_META_PLUGIN_DIR . 'templates/upgrade-modal.php';

$api_client = new SEO_AI_Meta_API_Client_V2();
$is_authenticated = $api_client->is_authenticated();
$usage_stats = SEO_AI_Meta_Usage_Tracker::get_stats_display();

// Get bulk stats
$posts_with_meta = SEO_AI_Meta_Helpers::get_posts_with_meta_count();
$posts_without_meta = SEO_AI_Meta_Helpers::get_posts_without_meta_count();
$total_posts = $posts_with_meta + $posts_without_meta;
$optimized_percentage = $total_posts > 0 ? round( ( $posts_with_meta / $total_posts ) * 100 ) : 0;

// Get recent activity
$recent_activity = SEO_AI_Meta_Helpers::get_recent_activity( 3 );

// Get SEO impact stats
$seo_impact = SEO_AI_Meta_Helpers::get_seo_impact_stats();

// Calculate FOMO threshold (70%)
$show_fomo = $usage_stats['percentage'] >= 70 && $usage_stats['percentage'] < 100;
$fomo_percentage = $usage_stats['percentage'];

// Calculate time saved (estimate: 2 minutes per post)
$time_saved_hours = round( ( $seo_impact['posts_optimized'] * 2 ) / 60, 1 );

// Get tab
$tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'dashboard';

// Base URL for tab navigation without transient query args.
$base_tab_url = menu_page_url( 'seo-ai-meta-generator', false );
if ( empty( $base_tab_url ) ) {
	$base_tab_url = admin_url( 'edit.php?page=seo-ai-meta-generator' );
}

// Get bulk generate data
$paged = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
$per_page = 20;
$bulk_query = SEO_AI_Meta_Bulk::get_posts_without_meta( $per_page, $paged );
$total_bulk_posts = $bulk_query->found_posts;

// Check for checkout success/error messages
$checkout_success = isset( $_GET['checkout'] ) && $_GET['checkout'] === 'success';
$checkout_error = isset( $_GET['checkout_error'] ) ? urldecode( $_GET['checkout_error'] ) : '';

// Get settings
require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-database.php';
$settings = SEO_AI_Meta_Database::get_setting( 'settings', array() );
// Fallback to WordPress options for backward compatibility
if ( empty( $settings ) ) {
	$settings = get_option( 'seo_ai_meta_settings', array() );
}
?>

<div class="seo-ai-meta-dashboard-wrapper">
	<!-- Header -->
	<div class="seo-ai-meta-dashboard-header">
		<div class="seo-ai-meta-logo">
			<div class="seo-ai-meta-logo-icon">
				<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
					<rect width="32" height="32" rx="6" fill="#1a1a1a"/>
					<text x="16" y="22" font-family="Arial, sans-serif" font-size="14" font-weight="bold" fill="white" text-anchor="middle">AI</text>
				</svg>
			</div>
			<span class="seo-ai-meta-logo-text">SEO AI Meta Generator</span>
		</div>
		<div class="seo-ai-meta-header-right">
			<?php if ( $show_fomo || $usage_stats['percentage'] >= 90 ) : ?>
				<span class="seo-ai-meta-fomo-header">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<path d="M8 17l4 4 4-4M12 3v18"/>
					</svg>
					<?php echo esc_html( $usage_stats['percentage'] ); ?>% used
				</span>
			<?php endif; ?>
			<?php if ( ! $is_authenticated ) : ?>
				<button type="button" class="seo-ai-meta-btn-login" onclick="seoAiMetaShowLoginModal();">
					<?php esc_html_e( 'Login/Register', 'seo-ai-meta-generator' ); ?>
				</button>
			<?php else : ?>
				<div class="seo-ai-meta-user-status-wrapper" style="display: flex; align-items: center; gap: 8px;">
					<span class="seo-ai-meta-user-status">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
							<circle cx="12" cy="7" r="4"/>
						</svg>
						Logged in
					</span>
					<button type="button" class="seo-ai-meta-btn-logout" onclick="seoAiMetaLogout();" style="background: none; border: 1px solid #d1d5db; color: #6b7280; padding: 4px 12px; border-radius: 4px; cursor: pointer; font-size: 13px; transition: all 0.2s;">
						Logout
					</button>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<?php
	// Get backend status for display
	$core = new SEO_AI_Meta_Core();
	$backend_status = $core->get_backend_status();
	$can_work_offline = $core->can_work_offline();
	?>

	<?php if ( isset( $backend_status['status'] ) && $backend_status['status'] !== 'healthy' ) : ?>
		<div class="notice notice-warning inline seo-ai-meta-backend-status" style="margin: 10px 0; padding: 12px;">
			<p style="margin: 0;">
				<strong><?php esc_html_e( 'Backend Status:', 'seo-ai-meta-generator' ); ?></strong>
				<?php echo esc_html( $backend_status['message'] ); ?>
				<?php if ( $can_work_offline ) : ?>
					<span style="color: #22c55e; margin-left: 8px;">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle;">
							<path d="M20 6L9 17l-5-5"/>
						</svg>
						<?php esc_html_e( 'Plugin can still generate meta tags locally.', 'seo-ai-meta-generator' ); ?>
					</span>
				<?php endif; ?>
			</p>
		</div>
	<?php endif; ?>

	<?php if ( $checkout_success ) : ?>
		<div class="notice notice-success is-dismissible seo-ai-meta-notice-auto-clear" style="margin: 20px 0;">
			<p><?php esc_html_e( 'Checkout completed successfully! Your subscription is now active.', 'seo-ai-meta-generator' ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( $checkout_error ) : ?>
		<div class="notice notice-error is-dismissible seo-ai-meta-checkout-error" style="margin: 20px 0;">
			<p><strong><?php esc_html_e( 'Checkout Error:', 'seo-ai-meta-generator' ); ?></strong> <?php echo esc_html( $checkout_error ); ?></p>
			<?php if ( strpos( $checkout_error, 'temporarily unavailable' ) !== false || strpos( $checkout_error, 'Server temporarily' ) !== false ) : ?>
				<p style="margin-top: 8px; font-size: 13px; color: #6b7280;">
					<?php esc_html_e( 'The backend service is currently unavailable. Please try again in a few moments.', 'seo-ai-meta-generator' ); ?>
				</p>
			<?php endif; ?>
		</div>
		<?php
		// Clear the error from URL after showing it once to prevent it showing on every tab
		$clean_url = remove_query_arg( 'checkout_error' );
		if ( $clean_url !== $_SERVER['REQUEST_URI'] ) {
			?>
			<script>
				// Clear error from URL without reload to prevent showing on every tab switch
				if (window.history && window.history.replaceState) {
					window.history.replaceState({}, document.title, '<?php echo esc_js( $clean_url ); ?>');
				}
			</script>
			<?php
		}
		?>
	<?php endif; ?>

	<?php if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] === 'true' ) : ?>
		<div class="notice notice-success is-dismissible" style="margin: 20px 0;">
			<p><?php esc_html_e( 'Settings saved successfully!', 'seo-ai-meta-generator' ); ?></p>
		</div>
	<?php endif; ?>

	<!-- Tab Navigation -->
	<div class="seo-ai-meta-tabs">
		<a href="<?php echo esc_url( add_query_arg( 'tab', 'dashboard', $base_tab_url ) ); ?>"
		   class="seo-ai-meta-tab <?php echo $tab === 'dashboard' ? 'active' : ''; ?>">
			Dashboard
		</a>
		<a href="<?php echo esc_url( add_query_arg( 'tab', 'bulk', $base_tab_url ) ); ?>"
		   class="seo-ai-meta-tab <?php echo $tab === 'bulk' ? 'active' : ''; ?>">
			Bulk Generate Meta
		</a>
		<a href="<?php echo esc_url( add_query_arg( 'tab', 'settings', $base_tab_url ) ); ?>"
		   class="seo-ai-meta-tab <?php echo $tab === 'settings' ? 'active' : ''; ?>">
			Settings
		</a>
		<a href="<?php echo esc_url( add_query_arg( 'tab', 'logs', $base_tab_url ) ); ?>"
		   class="seo-ai-meta-tab <?php echo $tab === 'logs' ? 'active' : ''; ?>">
			Debug Logs
		</a>
	</div>

	<div class="seo-ai-meta-dashboard-content" style="max-width: 960px; margin: 0 auto; padding: 28px 0;">
		<?php if ( $tab === 'dashboard' ) : ?>
			<!-- Main Title -->
			<h1 style="font-size: 28px; font-weight: 700; color: #1f2937; margin: 0 0 32px 0; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.2;">
				Generate SEO Titles and Meta Descriptions with AI
			</h1>

			<!-- Usage Section with Circular Progress Ring -->
			<div style="display: flex; align-items: center; gap: 32px; margin-bottom: 32px; padding: 28px; background: white; border-radius: 12px; border: 1px solid #e5e7eb;">
				<!-- Circular Progress Ring -->
				<div style="position: relative; width: 120px; height: 120px; flex-shrink: 0;">
					<svg width="120" height="120" style="transform: rotate(-90deg);">
						<defs>
							<linearGradient id="seo-ai-meta-gradient" x1="0%" y1="0%" x2="100%" y2="100%">
								<stop offset="0%" style="stop-color:#3b82f6;stop-opacity:1" />
								<stop offset="100%" style="stop-color:#60a5fa;stop-opacity:1" />
							</linearGradient>
						</defs>
						<!-- Background circle -->
						<circle cx="60" cy="60" r="54" fill="none" stroke="#e5e7eb" stroke-width="12"/>
						<!-- Progress circle -->
						<circle 
							id="seo-ai-meta-progress-ring" 
							cx="60" 
							cy="60" 
							r="54" 
							fill="none" 
							stroke="url(#seo-ai-meta-gradient)" 
							stroke-width="12" 
							stroke-linecap="round"
							data-percentage="<?php echo esc_attr( min( 100, round( $usage_stats['percentage'] ) ) ); ?>"
							style="stroke-dasharray: <?php echo esc_attr( 2 * M_PI * 54 ); ?>; stroke-dashoffset: <?php echo esc_attr( 2 * M_PI * 54 ); ?>;"
						/>
						</svg>
					<!-- Percentage text -->
					<div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center;">
						<div style="font-size: 32px; font-weight: 700; color: #1f2937; line-height: 1;">
							<?php echo esc_html( min( 100, round( $usage_stats['percentage'] ) ) ); ?>%
					</div>
					</div>
				</div>
				
				<!-- Usage Text -->
				<div style="flex: 1;">
					<p style="font-size: 14px; color: #6b7280; margin: 0 0 8px 0; line-height: 1.5;">
						Your AI is <?php echo esc_html( min( 100, round( $usage_stats['percentage'] ) ) ); ?>% powered up this month - keep optimizing!
					</p>
					<p style="font-size: 24px; font-weight: 700; color: #1f2937; margin: 0 0 8px 0; line-height: 1.2;">
						<?php echo esc_html( $usage_stats['used'] ); ?> of <?php echo esc_html( $usage_stats['limit'] ); ?> generations used
					</p>
					<p style="font-size: 14px; color: #6b7280; margin: 0; line-height: 1.5;">
						Resets <?php echo esc_html( $usage_stats['reset_date'] ); ?>
					</p>
					</div>
				</div>
				
			<!-- SEO Impact Tracker Card -->
			<div style="margin-bottom: 32px; padding: 28px; background: white; border-radius: 12px; border: 1px solid #e5e7eb; position: relative;">
				<div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
					<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2" style="flex-shrink: 0;">
						<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
					</svg>
					<h2 style="font-size: 18px; font-weight: 600; color: #1f2937; margin: 0; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
						SEO Impact Tracker
					</h2>
						</div>
				<p style="font-size: 16px; color: #374151; margin: 0; line-height: 1.6;">
					You saved <strong style="color: #22c55e; font-weight: 600;"><?php echo esc_html( $time_saved_hours ); ?> hours</strong> 
					and improved <strong style="color: #22c55e; font-weight: 600;"><?php echo esc_html( $seo_impact['posts_optimized'] ); ?> meta tags</strong> 
					— that's <strong style="color: #22c55e; font-weight: 600;">+<?php echo esc_html( $seo_impact['estimated_rankings'] ); ?>%</strong> visibility.
				</p>
				<div style="position: absolute; top: 28px; right: 28px; cursor: help;" 
					 title="<?php esc_attr_e( 'Visibility gain represents the estimated improvement in click-through rate (CTR) potential. Better meta tags can increase how often your posts appear in search results and get clicked.', 'seo-ai-meta-generator' ); ?>"
					 class="seo-ai-meta-tooltip-trigger">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2" style="transition: stroke 0.2s;">
						<circle cx="12" cy="12" r="10"/>
						<line x1="12" y1="16" x2="12" y2="12"/>
						<line x1="12" y1="8" x2="12.01" y2="8"/>
					</svg>
				</div>
				</div>
				
			<!-- Two Column Layout -->
			<div class="seo-ai-meta-dashboard-grid" style="display: grid; grid-template-columns: 1fr 320px; gap: 24px; margin-bottom: 32px;">
				<!-- Left Column - Bulk Generate -->
				<div>
					<div style="padding: 28px; background: white; border-radius: 12px; border: 1px solid #e5e7eb;">
						<h2 style="font-size: 18px; font-weight: 600; color: #1f2937; margin: 0 0 8px 0; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
							Bulk Generate
						</h2>
						<p style="font-size: 14px; color: #6b7280; margin: 0 0 20px 0; line-height: 1.5;">
							Automatically generates meta for <?php echo esc_html( $posts_without_meta ); ?> pages – estimated +<?php echo esc_html( $seo_impact['estimated_rankings'] ); ?>% search visibility.
						</p>
						
						<?php
						// Get sample posts for display
						$sample_posts_query = SEO_AI_Meta_Bulk::get_posts_without_meta( 5, 1 );
						$recent_posts_with_meta = new WP_Query( array(
							'post_type'      => 'post',
							'post_status'    => 'publish',
							'posts_per_page' => 3,
							'meta_query'     => array(
								array(
									'key'     => '_seo_ai_meta_title',
									'compare' => 'EXISTS',
								),
								array(
									'key'     => '_seo_ai_meta_title',
									'value'   => '',
									'compare' => '!=',
								),
							),
							'orderby'        => 'date',
							'order'          => 'DESC',
						) );
						?>
						
						<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #e5e7eb;">
							<span style="font-size: 14px; font-weight: 500; color: #374151;">Bulk Generate</span>
							<span style="font-size: 14px; color: #6b7280;">Sort more</span>
					</div>
						
						<!-- Post List -->
						<div style="margin-bottom: 20px;">
							<?php if ( $sample_posts_query->have_posts() ) : ?>
								<?php while ( $sample_posts_query->have_posts() ) : $sample_posts_query->the_post(); ?>
									<div style="display: flex; align-items: center; gap: 12px; padding: 12px 0; border-bottom: 1px solid #f3f4f6;">
										<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2" style="flex-shrink: 0;">
											<circle cx="12" cy="12" r="10"/>
											<polyline points="12 6 12 12 16 14"/>
										</svg>
										<span style="flex: 1; font-size: 14px; color: #374151;"><?php echo esc_html( get_the_title() ); ?></span>
										<span style="font-size: 12px; color: #9ca3af; padding: 4px 8px; background: #f3f4f6; border-radius: 4px;">Pending</span>
			</div>
								<?php endwhile; ?>
								<?php wp_reset_postdata(); ?>
							<?php endif; ?>
							
							<?php if ( $recent_posts_with_meta->have_posts() ) : ?>
								<?php while ( $recent_posts_with_meta->have_posts() ) : $recent_posts_with_meta->the_post(); ?>
									<div style="display: flex; align-items: center; gap: 12px; padding: 12px 0; border-bottom: 1px solid #f3f4f6;">
										<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2" style="flex-shrink: 0;">
											<path d="M20 6L9 17l-5-5"/>
					</svg>
										<span style="flex: 1; font-size: 14px; color: #374151;"><?php echo esc_html( get_the_title() ); ?></span>
										<span style="font-size: 12px; color: #22c55e; padding: 4px 8px; background: #d1fae5; border-radius: 4px; font-weight: 500;">Optimized</span>
				</div>
								<?php endwhile; ?>
								<?php wp_reset_postdata(); ?>
							<?php endif; ?>
			</div>

							<?php if ( $posts_without_meta > 0 ) : ?>
							<a href="<?php echo esc_url( add_query_arg( 'tab', 'bulk', $base_tab_url ) ); ?>" 
							   style="display: inline-block; width: 100%; padding: 12px 24px; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; text-align: center; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 14px; transition: all 0.2s; box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.3);"
							   onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 12px -1px rgba(59, 130, 246, 0.4)';"
							   onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 6px -1px rgba(59, 130, 246, 0.3)';">
								Generate All
								</a>
							<?php else : ?>
							<div style="display: flex; align-items: center; gap: 8px; padding: 12px; background: #d1fae5; border-radius: 8px; color: #22c55e;">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
									<path d="M20 6L9 17l-5-5"/>
									</svg>
								<span style="font-size: 14px; font-weight: 500;">All posts optimized!</span>
								</div>
							<?php endif; ?>
						</div>
					</div>

				<!-- Right Column - Upgrade to Pro Card -->
				<div style="position: sticky; top: 20px; height: fit-content;">
					<div style="padding: 28px; background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); border-radius: 12px; border: 1px solid #86efac; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
						<div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2">
								<line x1="12" y1="5" x2="12" y2="19"/>
								<line x1="5" y1="12" x2="19" y2="12"/>
								</svg>
							<h3 style="font-size: 18px; font-weight: 700; color: #1f2937; margin: 0; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
								Upgrade to Pro
							</h3>
							</div>
						<p style="font-size: 14px; color: #374151; margin: 0 0 20px 0; font-weight: 500;">
							Unlock Unlimited AI Power
						</p>
						<ul style="list-style: none; padding: 0; margin: 0 0 24px 0;">
							<li style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px; font-size: 14px; color: #374151;">
								<div style="width: 6px; height: 6px; border-radius: 50%; background: #22c55e; flex-shrink: 0;"></div>
								<span>Unlimited generations</span>
							</li>
							<li style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px; font-size: 14px; color: #374151;">
								<div style="width: 6px; height: 6px; border-radius: 50%; background: #22c55e; flex-shrink: 0;"></div>
								<span>Smart SEO tuning</span>
							</li>
							<li style="display: flex; align-items: center; gap: 10px; font-size: 14px; color: #374151;">
								<div style="width: 6px; height: 6px; border-radius: 50%; background: #22c55e; flex-shrink: 0;"></div>
								<span>Priority support</span>
							</li>
						</ul>
						<button type="button" 
								onclick="seoAiMetaShowModal();"
								style="width: 100%; padding: 12px 24px; background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); color: white; border: none; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 6px -1px rgba(34, 197, 94, 0.3);"
								onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 12px -1px rgba(34, 197, 94, 0.4)';"
								onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 6px -1px rgba(34, 197, 94, 0.3)';">
							Go Pro
						</button>
					</div>
				</div>
			</div>

			<!-- Bottom Banner - AltText AI -->
			<?php if ( ! SEO_AI_Meta_Helpers::is_alttext_ai_active() ) : ?>
			<div style="padding: 20px 28px; background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); border-radius: 12px; border: 1px solid #93c5fd; display: flex; align-items: center; justify-content: space-between; gap: 16px;">
				<p style="font-size: 14px; color: #1e40af; margin: 0; font-weight: 500; flex: 1;">
					Complete your SEO Stack → Try AltText AI for image alt text automation.
				</p>
				<button type="button" 
						onclick="window.open('<?php echo esc_url( SEO_AI_Meta_Helpers::get_alttext_ai_url() ); ?>', '_blank');"
						style="padding: 8px 16px; background: white; color: #1e40af; border: 1px solid #93c5fd; border-radius: 6px; font-weight: 500; font-size: 14px; cursor: pointer; transition: all 0.2s; white-space: nowrap;"
						onmouseover="this.style.background='#eff6ff';"
						onmouseout="this.style.background='white';">
					Install Now
				</button>
			</div>
			<?php endif; ?>

		<?php elseif ( $tab === 'bulk' ) : ?>
			<!-- Bulk Generate Tab -->
			<div class="seo-ai-meta-bulk-tab">
				<h1 class="seo-ai-meta-tab-title">Bulk Generate Meta</h1>
				
				<div class="seo-ai-meta-bulk-header">
					<p><?php esc_html_e( 'Generate SEO meta tags for posts that don\'t have them yet.', 'seo-ai-meta-generator' ); ?></p>
					<p>
						<strong><?php esc_html_e( 'Found:', 'seo-ai-meta-generator' ); ?></strong>
						<?php echo esc_html( $total_bulk_posts ); ?> <?php esc_html_e( 'posts without meta tags', 'seo-ai-meta-generator' ); ?>
					</p>
				</div>

				<?php if ( $bulk_query->have_posts() ) : ?>
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
								<?php while ( $bulk_query->have_posts() ) : $bulk_query->the_post(); ?>
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
											<span class="seo-ai-meta-status-no-meta" style="color: #dc3232;">
												<?php esc_html_e( 'No Meta', 'seo-ai-meta-generator' ); ?>
											</span>
										</td>
									</tr>
								<?php endwhile; ?>
							</tbody>
						</table>

						<div class="seo-ai-meta-bulk-actions">
							<button type="button" id="seo-ai-meta-bulk-generate-btn" class="button button-primary">
								<?php esc_html_e( 'Generate Meta for Selected Posts', 'seo-ai-meta-generator' ); ?>
							</button>
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
							'base'      => add_query_arg( array( 'tab' => 'bulk', 'paged' => '%#%' ), $base_tab_url ),
							'format'    => '',
							'prev_text' => __( '&laquo;' ),
							'next_text' => __( '&raquo;' ),
							'total'     => $bulk_query->max_num_pages,
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

		<?php elseif ( $tab === 'settings' ) : ?>
			<?php
			// Get usage stats for settings page
			require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-usage-tracker.php';
			$usage_stats = SEO_AI_Meta_Usage_Tracker::get_stats_display( true );
			$is_pro = $usage_stats['is_pro'];
			$usage_percent = $usage_stats['percentage'];
			
			// Get API client for authentication check
			require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-api-client-v2.php';
			$api_client = new SEO_AI_Meta_API_Client_V2();
			$is_authenticated = $api_client->is_authenticated();
			?>
			
			<!-- Settings Page - Modern Design -->
			<div class="seo-ai-meta-settings-container" style="max-width: 900px; margin: 0 auto;">
				<?php settings_errors( 'seo_ai_meta_settings_group' ); ?>
				
				<?php if ( isset( $_GET['import_success'] ) && $_GET['import_success'] === '1' ) : ?>
					<div class="notice notice-success is-dismissible">
						<p>
							<?php
							printf(
								/* translators: %1$d: imported count, %2$d: skipped count */
								esc_html__( 'Import completed! %1$d meta tags imported, %2$d skipped.', 'seo-ai-meta-generator' ),
								intval( $_GET['imported'] ?? 0 ),
								intval( $_GET['skipped'] ?? 0 )
							);
							?>
						</p>
					</div>
				<?php endif; ?>
				
				<?php if ( isset( $_GET['import_error'] ) ) : ?>
					<div class="notice notice-error is-dismissible">
						<p><?php esc_html_e( 'Import failed. Please check your file and try again.', 'seo-ai-meta-generator' ); ?></p>
					</div>
				<?php endif; ?>
				
				<!-- Settings Header -->
				<div class="seo-ai-meta-settings-header" style="margin-bottom: 32px;">
					<div style="display: flex; align-items: center; gap: 16px; margin-bottom: 8px;">
						<div style="width: 48px; height: 48px; border-radius: 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center;">
							<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
								<circle cx="12" cy="12" r="3"/>
								<path d="M12 1v6m0 6v6M23 12h-6M7 12H1m18.364-5.636l-4.243 4.243m0 4.243l4.243 4.243M4.636 4.636l4.243 4.243m0 4.243l-4.243 4.243"/>
							</svg>
						</div>
						<div>
							<h1 style="margin: 0; font-size: 28px; font-weight: 600; color: #1f2937;"><?php esc_html_e( 'Settings', 'seo-ai-meta-generator' ); ?></h1>
							<p style="margin: 4px 0 0 0; font-size: 14px; color: #6b7280;"><?php esc_html_e( 'Configure your SEO meta generation preferences', 'seo-ai-meta-generator' ); ?></p>
						</div>
					</div>
				</div>

				<!-- Plan Status Card -->
				<div class="seo-ai-meta-settings-plan-card" style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px; margin-bottom: 24px; <?php echo $is_pro ? 'border-color: #667eea;' : ''; ?>">
					<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
						<div>
							<div style="display: inline-block; padding: 4px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; margin-bottom: 8px; <?php echo $is_pro ? 'background: #667eea; color: white;' : 'background: #f3f4f6; color: #6b7280;'; ?>">
								<?php echo $is_pro ? '⭐ PRO' : '🆓 FREE'; ?>
							</div>
							<h3 style="margin: 0; font-size: 20px; font-weight: 600; color: #1f2937;"><?php echo esc_html( $usage_stats['plan_label'] ); ?> Plan</h3>
						</div>
					</div>

					<div style="background: #f3f4f6; height: 8px; border-radius: 4px; overflow: hidden; margin-bottom: 16px;">
						<div style="background: <?php echo $is_pro ? '#667eea' : '#10b981'; ?>; height: 100%; width: <?php echo min( 100, $usage_percent ); ?>%; transition: width 0.3s;"></div>
					</div>

					<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 20px;">
						<div>
							<span style="display: block; font-size: 12px; color: #6b7280; margin-bottom: 4px;"><?php esc_html_e( 'Used', 'seo-ai-meta-generator' ); ?></span>
							<span style="display: block; font-size: 18px; font-weight: 600; color: #1f2937;"><?php echo esc_html( $usage_stats['used'] ); ?></span>
						</div>
						<div>
							<span style="display: block; font-size: 12px; color: #6b7280; margin-bottom: 4px;"><?php esc_html_e( 'Limit', 'seo-ai-meta-generator' ); ?></span>
							<span style="display: block; font-size: 18px; font-weight: 600; color: #1f2937;"><?php echo esc_html( $usage_stats['limit'] ); ?></span>
						</div>
						<div>
							<span style="display: block; font-size: 12px; color: #6b7280; margin-bottom: 4px;"><?php esc_html_e( 'Resets', 'seo-ai-meta-generator' ); ?></span>
							<span style="display: block; font-size: 18px; font-weight: 600; color: #1f2937;"><?php echo esc_html( $usage_stats['reset_date'] ); ?></span>
						</div>
					</div>

					<?php if ( ! $is_pro ) : ?>
					<button type="button" class="seo-ai-meta-btn-primary" onclick="seoAiMetaShowUpgradeModal();" style="width: 100%; padding: 12px 16px; background: #667eea; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;">
						<svg width="16" height="16" viewBox="0 0 16 16" fill="none">
							<path d="M8 2L6 6H2L6 9L4 14L8 11L12 14L10 9L14 6H10L8 2Z" fill="currentColor"/>
						</svg>
						<span><?php esc_html_e( 'Upgrade to Pro', 'seo-ai-meta-generator' ); ?></span>
					</button>
					<?php endif; ?>
				</div>

				<!-- Account Management Card -->
				<?php if ( $is_authenticated ) : ?>
				<div class="seo-ai-meta-settings-card" style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px; margin-bottom: 24px;">
					<div style="margin-bottom: 16px;">
						<h2 style="margin: 0 0 4px 0; font-size: 18px; font-weight: 600; color: #1f2937; display: flex; align-items: center; gap: 8px;">
							<span>💳</span>
							<?php esc_html_e( 'Account Management', 'seo-ai-meta-generator' ); ?>
						</h2>
						<p style="margin: 0; font-size: 14px; color: #6b7280;"><?php esc_html_e( 'Manage your subscription, billing, and payment methods', 'seo-ai-meta-generator' ); ?></p>
					</div>

					<div style="padding: 16px; background: #f9fafb; border-radius: 8px; margin-bottom: 16px;">
						<p style="margin: 0 0 12px 0; font-size: 14px; color: #374151;">
							<?php esc_html_e( 'You are currently on the', 'seo-ai-meta-generator' ); ?> <strong><?php echo esc_html( $usage_stats['plan_label'] ); ?></strong> <?php esc_html_e( 'plan.', 'seo-ai-meta-generator' ); ?>
							<?php if ( ! $is_pro ) : ?>
								<?php esc_html_e( 'Upgrade to access more features and unlimited meta generation.', 'seo-ai-meta-generator' ); ?>
							<?php endif; ?>
						</p>
						<?php if ( ! $is_pro ) : ?>
						<button type="button" class="seo-ai-meta-btn-primary" onclick="seoAiMetaShowUpgradeModal();" style="padding: 10px 16px; background: #667eea; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">
							<?php esc_html_e( 'Upgrade Now', 'seo-ai-meta-generator' ); ?>
						</button>
						<?php endif; ?>
					</div>
				</div>
				<?php endif; ?>

				<!-- Settings Form -->
				<form method="post" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" class="seo-ai-meta-settings-form">
					<?php
					settings_fields( 'seo_ai_meta_settings_group' );
					do_settings_sections( 'seo_ai_meta_settings_group' );
					wp_nonce_field( 'seo_ai_meta_settings_group-options' );
					?>

					<!-- Generation Settings Card -->
					<div class="seo-ai-meta-settings-card" style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px; margin-bottom: 24px;">
						<div style="margin-bottom: 20px;">
							<h2 style="margin: 0 0 4px 0; font-size: 18px; font-weight: 600; color: #1f2937; display: flex; align-items: center; gap: 8px;">
								<span>⚙️</span>
								<?php esc_html_e( 'Generation Settings', 'seo-ai-meta-generator' ); ?>
							</h2>
							<p style="margin: 0; font-size: 14px; color: #6b7280;"><?php esc_html_e( 'Control how meta tags are generated', 'seo-ai-meta-generator' ); ?></p>
						</div>

						<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
							<div>
								<label for="title_max_length" style="display: block; margin-bottom: 8px; font-weight: 500; color: #374151;">
									<?php esc_html_e( 'Title Max Length', 'seo-ai-meta-generator' ); ?>
								</label>
								<input type="number" id="title_max_length" name="seo_ai_meta_settings[title_max_length]" 
									value="<?php echo esc_attr( $settings['title_max_length'] ?? 60 ); ?>" 
									min="30" max="70" 
									style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px;"
								/>
								<p style="margin: 8px 0 0 0; font-size: 13px; color: #6b7280;">
									<?php esc_html_e( 'Recommended: 50-60 characters', 'seo-ai-meta-generator' ); ?>
								</p>
							</div>
							<div>
								<label for="description_max_length" style="display: block; margin-bottom: 8px; font-weight: 500; color: #374151;">
									<?php esc_html_e( 'Description Max Length', 'seo-ai-meta-generator' ); ?>
								</label>
								<input type="number" id="description_max_length" name="seo_ai_meta_settings[description_max_length]" 
									value="<?php echo esc_attr( $settings['description_max_length'] ?? 160 ); ?>" 
									min="120" max="200" 
									style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px;"
								/>
								<p style="margin: 8px 0 0 0; font-size: 13px; color: #6b7280;">
									<?php esc_html_e( 'Recommended: 150-160 characters', 'seo-ai-meta-generator' ); ?>
								</p>
							</div>
						</div>

						<!-- Meta Templates -->
						<div style="margin-top: 20px; padding: 16px; background: #f9fafb; border-radius: 8px; border: 1px solid #e5e7eb;">
							<label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">
								📝 <?php esc_html_e( 'Meta Templates (Optional)', 'seo-ai-meta-generator' ); ?>
							</label>
							<p style="margin: 0 0 12px 0; font-size: 13px; color: #6b7280;">
								<?php esc_html_e( 'Use variables to create dynamic meta tags. Available variables:', 'seo-ai-meta-generator' ); ?>
								<code style="background: white; padding: 2px 6px; border-radius: 3px; font-size: 11px;">{{title}}</code>,
								<code style="background: white; padding: 2px 6px; border-radius: 3px; font-size: 11px;">{{date}}</code>,
								<code style="background: white; padding: 2px 6px; border-radius: 3px; font-size: 11px;">{{category}}</code>,
								<code style="background: white; padding: 2px 6px; border-radius: 3px; font-size: 11px;">{{author}}</code>,
								<code style="background: white; padding: 2px 6px; border-radius: 3px; font-size: 11px;">{{site}}</code>
							</p>
							<div style="display: grid; gap: 12px;">
								<div>
									<label for="title_template" style="display: block; margin-bottom: 6px; font-size: 13px; font-weight: 500; color: #374151;">
										<?php esc_html_e( 'Title Template', 'seo-ai-meta-generator' ); ?>
									</label>
									<input type="text" id="title_template" name="seo_ai_meta_settings[title_template]" 
										value="<?php echo esc_attr( $settings['title_template'] ?? '' ); ?>" 
										placeholder="<?php esc_attr_e( 'e.g., {{title}} | {{site}}', 'seo-ai-meta-generator' ); ?>"
										style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px;"
									/>
								</div>
								<div>
									<label for="description_template" style="display: block; margin-bottom: 6px; font-size: 13px; font-weight: 500; color: #374151;">
										<?php esc_html_e( 'Description Template', 'seo-ai-meta-generator' ); ?>
									</label>
									<textarea id="description_template" name="seo_ai_meta_settings[description_template]" 
										rows="2"
										placeholder="<?php esc_attr_e( 'e.g., Read about {{title}} on {{date}}', 'seo-ai-meta-generator' ); ?>"
										style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; resize: vertical;"
									><?php echo esc_textarea( $settings['description_template'] ?? '' ); ?></textarea>
								</div>
							</div>
							<p style="margin: 8px 0 0 0; font-size: 12px; color: #6b7280;">
								<?php esc_html_e( '💡 Leave empty to use AI-generated content without templates. Templates are applied after AI generation.', 'seo-ai-meta-generator' ); ?>
							</p>
						</div>
					</div>

					<!-- Save Button -->
					<div style="display: flex; align-items: center; gap: 16px; padding-top: 8px;">
						<button type="submit" style="padding: 12px 24px; background: #667eea; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px;">
							<svg width="16" height="16" viewBox="0 0 16 16" fill="none">
								<path d="M13 4L6 11L3 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
							</svg>
							<span><?php esc_html_e( 'Save Settings', 'seo-ai-meta-generator' ); ?></span>
						</button>
						<p style="margin: 0; font-size: 13px; color: #6b7280;">
							<?php esc_html_e( 'Changes will apply to all future generations', 'seo-ai-meta-generator' ); ?>
						</p>
					</div>
				</form>

				<!-- Export/Import Card -->
				<div class="seo-ai-meta-settings-card" style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px; margin-top: 24px;">
					<div style="margin-bottom: 20px;">
						<h2 style="margin: 0 0 4px 0; font-size: 18px; font-weight: 600; color: #1f2937; display: flex; align-items: center; gap: 8px;">
							<span>📥📤</span>
							<?php esc_html_e( 'Export / Import Meta Tags', 'seo-ai-meta-generator' ); ?>
						</h2>
						<p style="margin: 0; font-size: 14px; color: #6b7280;"><?php esc_html_e( 'Export meta tags to CSV or import from a file', 'seo-ai-meta-generator' ); ?></p>
					</div>

					<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
						<div style="padding: 16px; background: #f9fafb; border-radius: 8px;">
							<h3 style="margin: 0 0 8px 0; font-size: 14px; font-weight: 600; color: #374151;">
								<?php esc_html_e( 'Export Meta Tags', 'seo-ai-meta-generator' ); ?>
							</h3>
							<p style="margin: 0 0 12px 0; font-size: 13px; color: #6b7280;">
								<?php esc_html_e( 'Download all meta tags as CSV file', 'seo-ai-meta-generator' ); ?>
							</p>
							<a href="<?php echo esc_url( add_query_arg( array( 'action' => 'seo_ai_meta_export', 'nonce' => wp_create_nonce( 'seo_ai_meta_export' ) ), admin_url( 'admin-post.php' ) ) ); ?>" class="button button-secondary" style="width: 100%;">
								<?php esc_html_e( '📥 Export CSV', 'seo-ai-meta-generator' ); ?>
							</a>
						</div>

						<div style="padding: 16px; background: #f9fafb; border-radius: 8px;">
							<h3 style="margin: 0 0 8px 0; font-size: 14px; font-weight: 600; color: #374151;">
								<?php esc_html_e( 'Import Meta Tags', 'seo-ai-meta-generator' ); ?>
							</h3>
							<p style="margin: 0 0 12px 0; font-size: 13px; color: #6b7280;">
								<?php esc_html_e( 'Upload CSV file to import meta tags', 'seo-ai-meta-generator' ); ?>
							</p>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" style="display: flex; gap: 8px; align-items: flex-end;">
								<input type="hidden" name="action" value="seo_ai_meta_import">
								<?php wp_nonce_field( 'seo_ai_meta_import', 'seo_ai_meta_import_nonce' ); ?>
								<input type="file" name="import_file" accept=".csv" required style="flex: 1; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px;">
								<button type="submit" class="button button-secondary" style="padding: 8px 16px;">
									<?php esc_html_e( '📤 Import', 'seo-ai-meta-generator' ); ?>
								</button>
							</form>
						</div>
					</div>
				</div>
			</div>

		<?php elseif ( $tab === 'logs' ) : ?>
			<?php
			// Load logger
			require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-logger.php';

			// Handle actions
			if ( isset( $_POST['seo_ai_meta_clear_logs'] ) && check_admin_referer( 'seo_ai_meta_clear_logs' ) ) {
				SEO_AI_Meta_Logger::clear_logs();
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Logs cleared successfully!', 'seo-ai-meta-generator' ) . '</p></div>';
			}

			if ( isset( $_GET['export'] ) && $_GET['export'] === 'json' && check_admin_referer( 'seo_ai_meta_export_logs', 'nonce' ) ) {
				header( 'Content-Type: application/json' );
				header( 'Content-Disposition: attachment; filename="seo-ai-meta-logs-' . date( 'Y-m-d-His' ) . '.json"' );
				echo SEO_AI_Meta_Logger::export_logs_json();
				exit;
			}

			if ( isset( $_GET['export'] ) && $_GET['export'] === 'csv' && check_admin_referer( 'seo_ai_meta_export_logs', 'nonce' ) ) {
				header( 'Content-Type: text/csv' );
				header( 'Content-Disposition: attachment; filename="seo-ai-meta-logs-' . date( 'Y-m-d-His' ) . '.csv"' );
				echo SEO_AI_Meta_Logger::export_logs_csv();
				exit;
			}

			// Get filters
			$level_filter = isset( $_GET['level'] ) ? sanitize_text_field( $_GET['level'] ) : '';
			$search_filter = isset( $_GET['search'] ) ? sanitize_text_field( $_GET['search'] ) : '';

			$filters = array();
			if ( $level_filter ) {
				$filters['level'] = $level_filter;
			}
			if ( $search_filter ) {
				$filters['search'] = $search_filter;
			}

			$logs = SEO_AI_Meta_Logger::get_logs( $filters );
			$stats = SEO_AI_Meta_Logger::get_stats();
			?>

			<!-- Debug Logs Tab -->
			<div class="seo-ai-meta-logs-tab">
				<h1 class="seo-ai-meta-tab-title">Debug Logs</h1>

				<!-- Stats Cards -->
				<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
					<div class="seo-ai-meta-card">
						<div style="padding: 16px;">
							<div style="font-size: 13px; color: #6b7280; margin-bottom: 4px;">Total Logs</div>
							<div style="font-size: 24px; font-weight: 600;"><?php echo esc_html( $stats['total'] ); ?></div>
						</div>
					</div>
					<div class="seo-ai-meta-card">
						<div style="padding: 16px;">
							<div style="font-size: 13px; color: #6b7280; margin-bottom: 4px;">Errors</div>
							<div style="font-size: 24px; font-weight: 600; color: #dc2626;"><?php echo esc_html( $stats['error'] ); ?></div>
						</div>
					</div>
					<div class="seo-ai-meta-card">
						<div style="padding: 16px;">
							<div style="font-size: 13px; color: #6b7280; margin-bottom: 4px;">Warnings</div>
							<div style="font-size: 24px; font-weight: 600; color: #f59e0b;"><?php echo esc_html( $stats['warning'] ); ?></div>
						</div>
					</div>
					<div class="seo-ai-meta-card">
						<div style="padding: 16px;">
							<div style="font-size: 13px; color: #6b7280; margin-bottom: 4px;">Info</div>
							<div style="font-size: 24px; font-weight: 600; color: #3b82f6;"><?php echo esc_html( $stats['info'] ); ?></div>
						</div>
					</div>
				</div>

				<!-- Filters and Actions -->
				<div class="seo-ai-meta-card" style="margin-bottom: 24px;">
					<div style="padding: 16px;">
						<div style="display: flex; gap: 16px; flex-wrap: wrap; align-items: center; justify-content: space-between;">
							<div style="display: flex; gap: 12px; flex-wrap: wrap; flex: 1;">
								<!-- Level Filter -->
								<select id="seo-ai-meta-log-level-filter" style="padding: 6px 12px; border: 1px solid #d1d5db; border-radius: 4px;">
									<option value=""><?php esc_html_e( 'All Levels', 'seo-ai-meta-generator' ); ?></option>
									<option value="ERROR" <?php selected( $level_filter, 'ERROR' ); ?>><?php esc_html_e( 'Errors Only', 'seo-ai-meta-generator' ); ?></option>
									<option value="WARNING" <?php selected( $level_filter, 'WARNING' ); ?>><?php esc_html_e( 'Warnings Only', 'seo-ai-meta-generator' ); ?></option>
									<option value="INFO" <?php selected( $level_filter, 'INFO' ); ?>><?php esc_html_e( 'Info Only', 'seo-ai-meta-generator' ); ?></option>
									<option value="DEBUG" <?php selected( $level_filter, 'DEBUG' ); ?>><?php esc_html_e( 'Debug Only', 'seo-ai-meta-generator' ); ?></option>
								</select>

								<!-- Search -->
								<input type="text" id="seo-ai-meta-log-search"
									   placeholder="<?php esc_attr_e( 'Search logs...', 'seo-ai-meta-generator' ); ?>"
									   value="<?php echo esc_attr( $search_filter ); ?>"
									   style="padding: 6px 12px; border: 1px solid #d1d5db; border-radius: 4px; flex: 1; min-width: 200px;">

								<button type="button" id="seo-ai-meta-apply-filters" class="button"><?php esc_html_e( 'Apply Filters', 'seo-ai-meta-generator' ); ?></button>
								<button type="button" id="seo-ai-meta-clear-filters" class="button"><?php esc_html_e( 'Clear', 'seo-ai-meta-generator' ); ?></button>
							</div>

							<div style="display: flex; gap: 8px;">
								<!-- Export Buttons -->
								<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'tab' => 'logs', 'export' => 'json' ), $base_tab_url ), 'seo_ai_meta_export_logs', 'nonce' ) ); ?>"
								   class="button">
									<?php esc_html_e( 'Export JSON', 'seo-ai-meta-generator' ); ?>
								</a>
								<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'tab' => 'logs', 'export' => 'csv' ), $base_tab_url ), 'seo_ai_meta_export_logs', 'nonce' ) ); ?>"
								   class="button">
									<?php esc_html_e( 'Export CSV', 'seo-ai-meta-generator' ); ?>
								</a>

								<!-- Clear Logs -->
								<form method="post" style="display: inline;">
									<?php wp_nonce_field( 'seo_ai_meta_clear_logs' ); ?>
									<button type="submit" name="seo_ai_meta_clear_logs" class="button"
											onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to clear all logs?', 'seo-ai-meta-generator' ); ?>');">
										<?php esc_html_e( 'Clear Logs', 'seo-ai-meta-generator' ); ?>
									</button>
								</form>
							</div>
						</div>
					</div>
				</div>

				<!-- Logs Table -->
				<div class="seo-ai-meta-card">
					<?php if ( empty( $logs ) ) : ?>
						<div style="padding: 48px; text-align: center; color: #6b7280;">
							<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin: 0 auto 16px;">
								<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
								<polyline points="14 2 14 8 20 8"/>
								<line x1="12" y1="18" x2="12" y2="12"/>
								<line x1="9" y1="15" x2="15" y2="15"/>
							</svg>
							<p style="font-size: 16px; margin: 0;"><?php esc_html_e( 'No logs found', 'seo-ai-meta-generator' ); ?></p>
							<?php if ( $level_filter || $search_filter ) : ?>
								<p style="font-size: 14px; margin-top: 8px;"><?php esc_html_e( 'Try adjusting your filters', 'seo-ai-meta-generator' ); ?></p>
							<?php endif; ?>
						</div>
					<?php else : ?>
						<div style="overflow-x: auto;">
							<table class="wp-list-table widefat fixed striped" style="margin: 0;">
								<thead>
									<tr>
										<th style="width: 140px;"><?php esc_html_e( 'Timestamp', 'seo-ai-meta-generator' ); ?></th>
										<th style="width: 80px;"><?php esc_html_e( 'Level', 'seo-ai-meta-generator' ); ?></th>
										<th><?php esc_html_e( 'Message', 'seo-ai-meta-generator' ); ?></th>
										<th style="width: 200px;"><?php esc_html_e( 'Context', 'seo-ai-meta-generator' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $logs as $log ) : ?>
										<?php
										$level_class = '';
										$level_color = '#6b7280';
										switch ( $log['level'] ) {
											case 'ERROR':
												$level_class = 'error';
												$level_color = '#dc2626';
												break;
											case 'WARNING':
												$level_class = 'warning';
												$level_color = '#f59e0b';
												break;
											case 'INFO':
												$level_class = 'info';
												$level_color = '#3b82f6';
												break;
											case 'DEBUG':
												$level_class = 'debug';
												$level_color = '#6b7280';
												break;
										}
										?>
										<tr>
											<td style="font-size: 12px; color: #6b7280;">
												<?php echo esc_html( $log['timestamp'] ); ?>
											</td>
											<td>
												<span style="display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; background: <?php echo esc_attr( $level_color ); ?>20; color: <?php echo esc_attr( $level_color ); ?>;">
													<?php echo esc_html( $log['level'] ); ?>
												</span>
											</td>
											<td style="font-size: 13px;">
												<?php echo esc_html( $log['message'] ); ?>
											</td>
											<td style="font-size: 12px; color: #6b7280;">
												<?php if ( ! empty( $log['context'] ) ) : ?>
													<details style="cursor: pointer;">
														<summary style="user-select: none;"><?php esc_html_e( 'View Context', 'seo-ai-meta-generator' ); ?></summary>
														<pre style="margin-top: 8px; padding: 8px; background: #f3f4f6; border-radius: 4px; font-size: 11px; overflow-x: auto;"><?php echo esc_html( wp_json_encode( $log['context'], JSON_PRETTY_PRINT ) ); ?></pre>
													</details>
												<?php else : ?>
													<span style="color: #d1d5db;">-</span>
												<?php endif; ?>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<script>
			(function($) {
				'use strict';

				$(document).ready(function() {
					// Apply filters
					$('#seo-ai-meta-apply-filters').on('click', function() {
						var level = $('#seo-ai-meta-log-level-filter').val();
						var search = $('#seo-ai-meta-log-search').val();

						var url = new URL(window.location.href);
						url.searchParams.set('tab', 'logs');

						if (level) {
							url.searchParams.set('level', level);
						} else {
							url.searchParams.delete('level');
						}

						if (search) {
							url.searchParams.set('search', search);
						} else {
							url.searchParams.delete('search');
						}

						window.location.href = url.toString();
					});

					// Clear filters
					$('#seo-ai-meta-clear-filters').on('click', function() {
						var url = new URL(window.location.href);
						url.searchParams.delete('level');
						url.searchParams.delete('search');
						window.location.href = url.toString();
					});

					// Allow Enter key to apply filters
					$('#seo-ai-meta-log-search').on('keypress', function(e) {
						if (e.which === 13) {
							$('#seo-ai-meta-apply-filters').click();
						}
					});
				});
			})(jQuery);
			</script>

		<?php endif; ?>
	</div>
</div>

<!-- Login/Register Modal -->
<div id="seo-ai-meta-login-modal" class="seo-ai-meta-modal-backdrop" style="display: none;" role="dialog" aria-modal="true">
	<div class="seo-ai-meta-login-modal__content">
		<div class="seo-ai-meta-login-modal__header">
			<h2 id="seo-ai-meta-auth-modal-title"><?php esc_html_e( 'Login to SEO AI Meta', 'seo-ai-meta-generator' ); ?></h2>
			<button type="button" class="seo-ai-meta-modal-close" onclick="seoAiMetaCloseLoginModal();" aria-label="<?php esc_attr_e( 'Close login modal', 'seo-ai-meta-generator' ); ?>">
				<svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
					<path d="M15 5L5 15M5 5l10 10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
				</svg>
			</button>
		</div>
		<div class="seo-ai-meta-login-modal__body">
			<!-- Tabs -->
			<div class="seo-ai-meta-auth-tabs">
				<button type="button" class="seo-ai-meta-auth-tab active" data-tab="login">
					<?php esc_html_e( 'Login', 'seo-ai-meta-generator' ); ?>
				</button>
				<button type="button" class="seo-ai-meta-auth-tab" data-tab="register">
					<?php esc_html_e( 'Register', 'seo-ai-meta-generator' ); ?>
				</button>
			</div>

			<!-- Login Form -->
			<div id="seo-ai-meta-login-tab" class="seo-ai-meta-auth-tab-content active">
				<form id="seo-ai-meta-login-form">
					<p>
						<label for="seo-ai-meta-login-email"><?php esc_html_e( 'Email', 'seo-ai-meta-generator' ); ?></label>
						<input type="email" id="seo-ai-meta-login-email" name="email" required class="regular-text" />
					</p>
					<p>
						<label for="seo-ai-meta-login-password"><?php esc_html_e( 'Password', 'seo-ai-meta-generator' ); ?></label>
						<input type="password" id="seo-ai-meta-login-password" name="password" required class="regular-text" />
					</p>
					<p style="text-align: right; margin-top: -10px; margin-bottom: 15px;">
						<a href="#" onclick="event.preventDefault(); seoAiMetaShowForgotPassword();" style="color: #14b8a6; text-decoration: none; font-size: 13px;">
							<?php esc_html_e( 'Forgot password?', 'seo-ai-meta-generator' ); ?>
						</a>
					</p>
					<div id="seo-ai-meta-login-message" style="margin: 10px 0; padding: 12px; border-radius: 4px; display: none; font-weight: 500;"></div>
					<p class="submit">
						<button type="submit" class="button button-primary"><?php esc_html_e( 'Login', 'seo-ai-meta-generator' ); ?></button>
						<span class="spinner" id="seo-ai-meta-login-spinner" style="float: none; margin-left: 10px;"></span>
					</p>
				</form>
			</div>

			<!-- Forgot Password Form -->
			<div id="seo-ai-meta-forgot-password-tab" class="seo-ai-meta-auth-tab-content" style="display: none;">
				<form id="seo-ai-meta-forgot-password-form">
					<p>
						<label for="seo-ai-meta-forgot-email"><?php esc_html_e( 'Email', 'seo-ai-meta-generator' ); ?></label>
						<input type="email" id="seo-ai-meta-forgot-email" name="email" required class="regular-text" />
						<small style="color: #6b7280; font-size: 12px; display: block; margin-top: 4px;">
							<?php esc_html_e( 'Enter your email address and we\'ll send you a link to reset your password.', 'seo-ai-meta-generator' ); ?>
						</small>
					</p>
					<div id="seo-ai-meta-forgot-message" style="margin: 10px 0; padding: 12px; border-radius: 4px; display: none; font-weight: 500;"></div>
					<p class="submit">
						<button type="submit" class="button button-primary"><?php esc_html_e( 'Send Reset Link', 'seo-ai-meta-generator' ); ?></button>
						<span class="spinner" id="seo-ai-meta-forgot-spinner" style="float: none; margin-left: 10px;"></span>
					</p>
					<p style="text-align: center; margin-top: 15px;">
						<a href="#" onclick="event.preventDefault(); seoAiMetaShowLoginTab();" style="color: #14b8a6; text-decoration: none; font-size: 13px;">
							<?php esc_html_e( '← Back to Login', 'seo-ai-meta-generator' ); ?>
						</a>
					</p>
				</form>
			</div>

			<!-- Reset Password Form (shown when token is in URL) -->
			<?php
			$reset_token = isset( $_GET['reset_token'] ) ? sanitize_text_field( $_GET['reset_token'] ) : '';
			if ( ! empty( $reset_token ) ) :
			?>
			<div id="seo-ai-meta-reset-password-tab" class="seo-ai-meta-auth-tab-content" style="display: none;">
				<form id="seo-ai-meta-reset-password-form">
					<input type="hidden" id="seo-ai-meta-reset-token" value="<?php echo esc_attr( $reset_token ); ?>" />
					<p>
						<label for="seo-ai-meta-reset-password"><?php esc_html_e( 'New Password', 'seo-ai-meta-generator' ); ?></label>
						<input type="password" id="seo-ai-meta-reset-password" name="password" required class="regular-text" minlength="6" />
						<small style="color: #6b7280; font-size: 12px; display: block; margin-top: 4px;">
							<?php esc_html_e( 'Password must be at least 6 characters long.', 'seo-ai-meta-generator' ); ?>
						</small>
					</p>
					<p>
						<label for="seo-ai-meta-reset-password-confirm"><?php esc_html_e( 'Confirm Password', 'seo-ai-meta-generator' ); ?></label>
						<input type="password" id="seo-ai-meta-reset-password-confirm" name="password_confirm" required class="regular-text" minlength="6" />
					</p>
					<div id="seo-ai-meta-reset-message" style="margin: 10px 0; padding: 12px; border-radius: 4px; display: none; font-weight: 500;"></div>
					<p class="submit">
						<button type="submit" class="button button-primary"><?php esc_html_e( 'Reset Password', 'seo-ai-meta-generator' ); ?></button>
						<span class="spinner" id="seo-ai-meta-reset-spinner" style="float: none; margin-left: 10px;"></span>
					</p>
				</form>
			</div>
			<?php endif; ?>

			<!-- Register Form -->
			<div id="seo-ai-meta-register-tab" class="seo-ai-meta-auth-tab-content" style="display: none;">
				<form id="seo-ai-meta-register-form">
					<p>
						<label for="seo-ai-meta-register-email"><?php esc_html_e( 'Email', 'seo-ai-meta-generator' ); ?></label>
						<input type="email" id="seo-ai-meta-register-email" name="email" required class="regular-text" />
					</p>
					<p>
						<label for="seo-ai-meta-register-password"><?php esc_html_e( 'Password', 'seo-ai-meta-generator' ); ?></label>
						<input type="password" id="seo-ai-meta-register-password" name="password" required class="regular-text" minlength="6" />
						<small style="color: #6b7280; font-size: 12px; display: block; margin-top: 4px;">
							<?php esc_html_e( 'Password must be at least 6 characters long.', 'seo-ai-meta-generator' ); ?>
						</small>
					</p>
					<div id="seo-ai-meta-register-message" style="margin: 10px 0; padding: 12px; border-radius: 4px; display: none; font-weight: 500;"></div>
					<p class="submit">
						<button type="submit" class="button button-primary"><?php esc_html_e( 'Create Account', 'seo-ai-meta-generator' ); ?></button>
						<span class="spinner" id="seo-ai-meta-register-spinner" style="float: none; margin-left: 10px;"></span>
					</p>
				</form>
			</div>
		</div>
	</div>
</div>

<script>
function seoAiMetaShowLoginModal() {
	const modal = document.getElementById('seo-ai-meta-login-modal');
	if (modal) {
		modal.style.display = 'flex';
		document.body.style.overflow = 'hidden';
	}
}

function seoAiMetaCloseLoginModal() {
	const modal = document.getElementById('seo-ai-meta-login-modal');
	if (modal) {
		modal.style.display = 'none';
		document.body.style.overflow = '';
	}
}

function seoAiMetaShowForgotPassword() {
	// Hide all tabs
	jQuery('.seo-ai-meta-auth-tab-content').hide();
	jQuery('.seo-ai-meta-auth-tab').removeClass('active');
	
	// Show forgot password form
	jQuery('#seo-ai-meta-forgot-password-tab').show();
	
	// Update modal title
	jQuery('#seo-ai-meta-auth-modal-title').text('Reset Password');
	
	// Show modal if not already visible
	seoAiMetaShowLoginModal();
}

function seoAiMetaShowLoginTab() {
	// Hide all tabs
	jQuery('.seo-ai-meta-auth-tab-content').hide();
	jQuery('.seo-ai-meta-auth-tab').removeClass('active');
	
	// Show login tab
	jQuery('#seo-ai-meta-login-tab').show();
	jQuery('.seo-ai-meta-auth-tab[data-tab="login"]').addClass('active');
	
	// Update modal title
	jQuery('#seo-ai-meta-auth-modal-title').text('Login to SEO AI Meta');
}

// Handle tab switching
jQuery(document).ready(function($) {
	// Tab switching
	$('.seo-ai-meta-auth-tab').on('click', function() {
		var tab = $(this).data('tab');
		$('.seo-ai-meta-auth-tab').removeClass('active');
		$(this).addClass('active');
		$('.seo-ai-meta-auth-tab-content').hide();
		$('#seo-ai-meta-' + tab + '-tab').show();
		
		// Update title
		var title = tab === 'login' ? 'Login to SEO AI Meta' : 'Create SEO AI Meta Account';
		$('#seo-ai-meta-auth-modal-title').text(title);
		
		// Clear messages
		$('#seo-ai-meta-login-message, #seo-ai-meta-register-message').hide().removeClass('notice-success notice-error');
	});

	// Handle login form submission
	$('#seo-ai-meta-login-form').on('submit', function(e) {
		e.preventDefault();
		
		var $form = $(this);
		var $spinner = $('#seo-ai-meta-login-spinner');
		var $message = $('#seo-ai-meta-login-message');
		var $btn = $form.find('button[type="submit"]');
		
		// Clear previous messages
		$message.removeClass('seo-ai-meta-message-success seo-ai-meta-message-error').hide().html('');
		
		$btn.prop('disabled', true).text('Logging in...');
		$spinner.addClass('is-active').show();
		
		$.ajax({
			url: seoAiMetaAjax.ajaxurl,
			type: 'POST',
			data: {
				action: 'seo_ai_meta_login',
				nonce: seoAiMetaAjax.nonce,
				email: $('#seo-ai-meta-login-email').val(),
				password: $('#seo-ai-meta-login-password').val()
			},
			success: function(response) {
				$spinner.removeClass('is-active').hide();
				$btn.prop('disabled', false).text('Login');
				
				if (response && response.success) {
					var successMsg = response.data && response.data.message ? response.data.message : 'Login successful!';
					$message.addClass('seo-ai-meta-message-success').html(successMsg).css({
						'display': 'block',
						'background-color': '#efe',
						'color': '#3c3',
						'border': '1px solid #cfc',
						'padding': '12px',
						'border-radius': '4px'
					}).show();
					setTimeout(function() {
						location.reload();
					}, 1000);
				} else {
					var errorMsg = response && response.data && response.data.message ? response.data.message : 'Login failed. Please check your credentials.';
					$message.addClass('seo-ai-meta-message-error').html(errorMsg).css({
						'display': 'block',
						'background-color': '#fee',
						'color': '#c33',
						'border': '1px solid #fcc',
						'padding': '12px',
						'border-radius': '4px'
					}).show();
				}
			},
			error: function(xhr, status, error) {
				$spinner.removeClass('is-active').hide();
				$btn.prop('disabled', false).text('Login');
				var errorMsg = 'Network error. Please try again.';
				if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
					errorMsg = xhr.responseJSON.data.message;
				}
				$message.addClass('seo-ai-meta-message-error').html(errorMsg).css({
					'display': 'block',
					'background-color': '#fee',
					'color': '#c33',
					'border': '1px solid #fcc',
					'padding': '12px',
					'border-radius': '4px'
				}).show();
			}
		});
	});

	// Handle register form submission
	$('#seo-ai-meta-register-form').on('submit', function(e) {
		e.preventDefault();
		
		var $form = $(this);
		var $spinner = $('#seo-ai-meta-register-spinner');
		var $message = $('#seo-ai-meta-register-message');
		var $btn = $form.find('button[type="submit"]');
		var email = $('#seo-ai-meta-register-email').val();
		var password = $('#seo-ai-meta-register-password').val();
		
		// Clear previous messages
		$message.removeClass('seo-ai-meta-message-success seo-ai-meta-message-error').hide().html('');
		
		// Validate email
		if (!email || email.length === 0) {
			$message.addClass('seo-ai-meta-message-error').html('Email is required.').css({
				'display': 'block',
				'background-color': '#fee',
				'color': '#c33',
				'border': '1px solid #fcc',
				'padding': '12px',
				'border-radius': '4px'
			}).show();
			return;
		}
		
		// Validate password
		if (!password || password.length < 6) {
			$message.addClass('seo-ai-meta-message-error').html('Password must be at least 6 characters long.').css({
				'display': 'block',
				'background-color': '#fee',
				'color': '#c33',
				'border': '1px solid #fcc',
				'padding': '12px',
				'border-radius': '4px'
			}).show();
			return;
		}
		
		// Show loading state
		$btn.prop('disabled', true).text('Creating Account...');
		$spinner.addClass('is-active').show();
		
		console.log('Sending registration request...', { email: email, passwordLength: password.length });
		
		$.ajax({
			url: seoAiMetaAjax.ajaxurl,
			type: 'POST',
			data: {
				action: 'seo_ai_meta_register',
				nonce: seoAiMetaAjax.nonce,
				email: email,
				password: password
			},
			success: function(response) {
				console.log('Registration response:', response);
				$spinner.removeClass('is-active').hide();
				$btn.prop('disabled', false).text('Create Account');
				
				if (response && response.success) {
					var successMsg = response.data && response.data.message ? response.data.message : 'Registration successful! You are now logged in.';
					$message.addClass('seo-ai-meta-message-success').html(successMsg).css({
						'display': 'block',
						'background-color': '#efe',
						'color': '#3c3',
						'border': '1px solid #cfc',
						'padding': '12px',
						'border-radius': '4px'
					}).show();
					
					setTimeout(function() {
						location.reload();
					}, 1500);
				} else {
					var errorMsg = 'Registration failed. Please try again.';
					if (response && response.data) {
						if (response.data.message) {
							errorMsg = response.data.message;
						} else if (response.data.error) {
							errorMsg = response.data.error;
						}
					}
					console.error('Registration failed:', response);
					$message.addClass('seo-ai-meta-message-error').html(errorMsg).css({
						'display': 'block',
						'background-color': '#fee',
						'color': '#c33',
						'border': '1px solid #fcc',
						'padding': '12px',
						'border-radius': '4px'
					}).show();
				}
			},
			error: function(xhr, status, error) {
				console.error('Registration AJAX error:', { status: status, error: error, responseText: xhr.responseText, responseJSON: xhr.responseJSON });
				$spinner.removeClass('is-active').hide();
				$btn.prop('disabled', false).text('Create Account');
				
				var errorMsg = 'Network error. Please check your connection and try again.';
				
				// Try to extract error message from response
				if (xhr.responseJSON) {
					if (xhr.responseJSON.data && xhr.responseJSON.data.message) {
						errorMsg = xhr.responseJSON.data.message;
					} else if (xhr.responseJSON.message) {
						errorMsg = xhr.responseJSON.message;
					}
				} else if (xhr.responseText) {
					try {
						var parsed = JSON.parse(xhr.responseText);
						if (parsed.data && parsed.data.message) {
							errorMsg = parsed.data.message;
						}
					} catch(e) {
						// Not JSON, use default message
					}
				}
				
				$message.addClass('seo-ai-meta-message-error').html(errorMsg).css({
					'display': 'block',
					'background-color': '#fee',
					'color': '#c33',
					'border': '1px solid #fcc',
					'padding': '12px',
					'border-radius': '4px'
				}).show();
			}
		});
	});
	
	// Close modal when clicking backdrop
	$('#seo-ai-meta-login-modal').on('click', function(e) {
		if (e.target === this) {
			seoAiMetaCloseLoginModal();
		}
	});
	
	// Close on Escape key
	$(document).on('keydown', function(e) {
		if (e.key === 'Escape' && $('#seo-ai-meta-login-modal').is(':visible')) {
			seoAiMetaCloseLoginModal();
		}
	});

	// Handle forgot password form submission
	$('#seo-ai-meta-forgot-password-form').on('submit', function(e) {
		e.preventDefault();
		
		var $form = $(this);
		var $spinner = $('#seo-ai-meta-forgot-spinner');
		var $message = $('#seo-ai-meta-forgot-message');
		var $btn = $form.find('button[type="submit"]');
		var email = $('#seo-ai-meta-forgot-email').val();
		
		if (!email || email.length === 0) {
			$message.addClass('seo-ai-meta-message-error').html('Email is required.').css({
				'display': 'block',
				'background-color': '#fee',
				'color': '#c33',
				'border': '1px solid #fcc',
				'padding': '12px',
				'border-radius': '4px'
			}).show();
			return;
		}
		
		$btn.prop('disabled', true).text('Sending...');
		$spinner.addClass('is-active').show();
		$message.hide().removeClass('seo-ai-meta-message-success seo-ai-meta-message-error').html('');
		
		$.ajax({
			url: seoAiMetaAjax.ajaxurl,
			type: 'POST',
			data: {
				action: 'seo_ai_meta_forgot_password',
				nonce: seoAiMetaAjax.nonce,
				email: email
			},
			success: function(response) {
				$spinner.removeClass('is-active').hide();
				$btn.prop('disabled', false).text('Send Reset Link');
				
				if (response && response.success) {
					var successMsg = response.data && response.data.message ? response.data.message : 'Password reset email sent. Please check your inbox.';
					$message.addClass('seo-ai-meta-message-success').html(successMsg).css({
						'display': 'block',
						'background-color': '#efe',
						'color': '#3c3',
						'border': '1px solid #cfc',
						'padding': '12px',
						'border-radius': '4px'
					}).show();
				} else {
					var errorMsg = response && response.data && response.data.message ? response.data.message : 'Failed to send reset email. Please try again.';
					$message.addClass('seo-ai-meta-message-error').html(errorMsg).css({
						'display': 'block',
						'background-color': '#fee',
						'color': '#c33',
						'border': '1px solid #fcc',
						'padding': '12px',
						'border-radius': '4px'
					}).show();
				}
			},
			error: function(xhr, status, error) {
				$spinner.removeClass('is-active').hide();
				$btn.prop('disabled', false).text('Send Reset Link');
				var errorMsg = 'Network error. Please try again.';
				if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
					errorMsg = xhr.responseJSON.data.message;
				}
				$message.addClass('seo-ai-meta-message-error').html(errorMsg).css({
					'display': 'block',
					'background-color': '#fee',
					'color': '#c33',
					'border': '1px solid #fcc',
					'padding': '12px',
					'border-radius': '4px'
				}).show();
			}
		});
	});

	// Handle reset password form submission
	$('#seo-ai-meta-reset-password-form').on('submit', function(e) {
		e.preventDefault();
		
		var $form = $(this);
		var $spinner = $('#seo-ai-meta-reset-spinner');
		var $message = $('#seo-ai-meta-reset-message');
		var $btn = $form.find('button[type="submit"]');
		var token = $('#seo-ai-meta-reset-token').val();
		var password = $('#seo-ai-meta-reset-password').val();
		var passwordConfirm = $('#seo-ai-meta-reset-password-confirm').val();
		
		if (!password || password.length < 6) {
			$message.addClass('seo-ai-meta-message-error').html('Password must be at least 6 characters long.').css({
				'display': 'block',
				'background-color': '#fee',
				'color': '#c33',
				'border': '1px solid #fcc',
				'padding': '12px',
				'border-radius': '4px'
			}).show();
			return;
		}
		
		if (password !== passwordConfirm) {
			$message.addClass('seo-ai-meta-message-error').html('Passwords do not match.').css({
				'display': 'block',
				'background-color': '#fee',
				'color': '#c33',
				'border': '1px solid #fcc',
				'padding': '12px',
				'border-radius': '4px'
			}).show();
			return;
		}
		
		$btn.prop('disabled', true).text('Resetting...');
		$spinner.addClass('is-active').show();
		$message.hide().removeClass('seo-ai-meta-message-success seo-ai-meta-message-error').html('');
		
		$.ajax({
			url: seoAiMetaAjax.ajaxurl,
			type: 'POST',
			data: {
				action: 'seo_ai_meta_reset_password',
				nonce: seoAiMetaAjax.nonce,
				token: token,
				password: password
			},
			success: function(response) {
				$spinner.removeClass('is-active').hide();
				$btn.prop('disabled', false).text('Reset Password');
				
				if (response && response.success) {
					var successMsg = response.data && response.data.message ? response.data.message : 'Password reset successfully! You can now login with your new password.';
					$message.addClass('seo-ai-meta-message-success').html(successMsg).css({
						'display': 'block',
						'background-color': '#efe',
						'color': '#3c3',
						'border': '1px solid #cfc',
						'padding': '12px',
						'border-radius': '4px'
					}).show();
					
					setTimeout(function() {
						// Switch to login tab
						seoAiMetaShowLoginTab();
						// Clear URL parameters
						window.history.replaceState({}, document.title, window.location.pathname + window.location.search.replace(/[?&]reset_token=[^&]*/, ''));
					}, 2000);
				} else {
					var errorMsg = response && response.data && response.data.message ? response.data.message : 'Failed to reset password. Please try again.';
					$message.addClass('seo-ai-meta-message-error').html(errorMsg).css({
						'display': 'block',
						'background-color': '#fee',
						'color': '#c33',
						'border': '1px solid #fcc',
						'padding': '12px',
						'border-radius': '4px'
					}).show();
				}
			},
			error: function(xhr, status, error) {
				$spinner.removeClass('is-active').hide();
				$btn.prop('disabled', false).text('Reset Password');
				var errorMsg = 'Network error. Please try again.';
				if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
					errorMsg = xhr.responseJSON.data.message;
				}
				$message.addClass('seo-ai-meta-message-error').html(errorMsg).css({
					'display': 'block',
					'background-color': '#fee',
					'color': '#c33',
					'border': '1px solid #fcc',
					'padding': '12px',
					'border-radius': '4px'
				}).show();
			}
		});
	});

	// Auto-show reset password form if token is in URL
	<?php if ( ! empty( $reset_token ) ) : ?>
	jQuery(document).ready(function($) {
		seoAiMetaShowLoginModal();
		// Hide all tabs
		$('.seo-ai-meta-auth-tab-content').hide();
		$('.seo-ai-meta-auth-tab').removeClass('active');
		// Show reset password form
		$('#seo-ai-meta-reset-password-tab').show();
		$('#seo-ai-meta-auth-modal-title').text('Reset Password');
	});
	<?php endif; ?>
});

// Logout function
function seoAiMetaLogout() {
	if (!confirm('Are you sure you want to logout?')) {
		return;
	}

	jQuery.ajax({
		url: seoAiMetaAjax.ajaxurl,
		type: 'POST',
		data: {
			action: 'seo_ai_meta_logout',
			nonce: seoAiMetaAjax.nonce
		},
		success: function(response) {
			if (response && response.success) {
				location.reload();
			} else {
				alert('Logout failed. Please try again.');
			}
		},
		error: function() {
			alert('Network error. Please try again.');
		}
	});
}

// Auto-clear URL parameters when notices are dismissed
jQuery(document).ready(function($) {
	// When a notice with auto-clear class is dismissed
	$(document).on('click', '.seo-ai-meta-notice-auto-clear .notice-dismiss', function() {
		// Remove checkout_error and checkout query params from URL
		var url = new URL(window.location.href);
		url.searchParams.delete('checkout_error');
		url.searchParams.delete('checkout');

		// Update URL without reloading the page
		window.history.replaceState({}, document.title, url.toString());
	});

	// Auto-hide checkout notices after page load to prevent showing old errors
	<?php if ( $checkout_error || $checkout_success ) : ?>
	setTimeout(function() {
		var url = new URL(window.location.href);
		if (url.searchParams.has('checkout_error') || url.searchParams.has('checkout')) {
			url.searchParams.delete('checkout_error');
			url.searchParams.delete('checkout');
			window.history.replaceState({}, document.title, url.toString());
		}
	}, 10000); // Auto-clear after 10 seconds
	<?php endif; ?>
});
</script>
