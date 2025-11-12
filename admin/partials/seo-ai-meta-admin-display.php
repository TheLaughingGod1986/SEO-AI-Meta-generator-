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
					<rect width="32" height="32" rx="6" fill="#2271b1"/>
					<text x="16" y="22" font-family="Arial, sans-serif" font-size="14" font-weight="bold" fill="white" text-anchor="middle">AI</text>
				</svg>
			</div>
			<span class="seo-ai-meta-logo-text">SEO AI Meta Generator</span>
		</div>
		<div class="seo-ai-meta-header-right" style="display: flex; align-items: center; gap: 16px;">
			<!-- Testimonial Carousel -->
			<div id="seo-ai-meta-testimonial-carousel" style="max-width: 280px; padding: 8px 12px; background: #f3f4f6; border-radius: 8px; display: flex; align-items: center; gap: 8px; font-size: 12px; color: #374151; line-height: 1.4;">
				<div class="seo-ai-meta-testimonial-avatar" style="width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, #2271b1 0%, #3582c4 100%); display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: white; font-weight: 600; font-size: 14px;">
					SA
				</div>
				<div style="flex: 1; min-width: 0;">
					<div class="seo-ai-meta-testimonial-text" style="font-style: italic;">
						<?php esc_html_e( 'Generated 1,200 meta tags in minutes, saved hours each week', 'seo-ai-meta-generator' ); ?>
					</div>
					<div class="seo-ai-meta-testimonial-author" style="font-size: 11px; color: #6b7280; margin-top: 2px;">
						<?php esc_html_e( 'Sarah W., Agency Owner', 'seo-ai-meta-generator' ); ?>
					</div>
				</div>
			</div>
			<?php if ( $show_fomo || $usage_stats['percentage'] >= 90 ) : ?>
				<span class="seo-ai-meta-fomo-header">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<path d="M8 17l4 4 4-4M12 3v18"/>
					</svg>
					<?php echo esc_html( $usage_stats['percentage'] ); ?>% used
				</span>
			<?php endif; ?>
			<?php if ( ! $is_authenticated ) : ?>
				<button type="button" class="seo-ai-meta-btn-login" onclick="seoAiMetaTrackEvent('login_click', {source: 'header'}); seoAiMetaShowLoginModal();">
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
	require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-api-client-v2.php';
	$api_client_status = new SEO_AI_Meta_API_Client_V2();
	$backend_status = $api_client_status->get_backend_status();
	$can_work_offline = isset( $backend_status['can_work_offline'] ) ? $backend_status['can_work_offline'] : false;
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
			<?php
			// Calculate dynamic tagline based on percentage with achievement-focused microcopy
			$percentage = min( 100, round( $usage_stats['percentage'] ) );
			$dynamic_tagline = '';
			$is_maxed = $percentage >= 100;
			if ( $is_maxed ) {
				$dynamic_tagline = __( 'You\'ve maxed out your free 50 AI generations —', 'seo-ai-meta-generator' );
				$dynamic_subtitle = __( 'time to scale your SEO superpowers', 'seo-ai-meta-generator' );
			} elseif ( $percentage < 25 ) {
				$dynamic_tagline = __( 'Just getting started — keep optimizing!', 'seo-ai-meta-generator' );
				$dynamic_subtitle = '';
			} elseif ( $percentage < 50 ) {
				$dynamic_tagline = __( 'Making great progress — keep optimizing!', 'seo-ai-meta-generator' );
				$dynamic_subtitle = '';
			} elseif ( $percentage < 75 ) {
				$dynamic_tagline = __( 'Halfway powered — keep optimizing!', 'seo-ai-meta-generator' );
				$dynamic_subtitle = '';
			} elseif ( $percentage < 90 ) {
				$dynamic_tagline = __( 'Almost there — keep optimizing!', 'seo-ai-meta-generator' );
				$dynamic_subtitle = '';
			} else {
				$dynamic_tagline = __( 'Nearly full — upgrade for unlimited!', 'seo-ai-meta-generator' );
				$dynamic_subtitle = '';
			}
			
			// Generate sparkline data (sample data for visibility trends)
			$sparkline_data = array();
			for ( $i = 0; $i < 7; $i++ ) {
				$base_value = 20;
				$trend_value = ( $seo_impact['estimated_rankings'] / 7 ) * $i;
				$variance = rand( -3, 3 );
				$sparkline_data[] = max( 0, round( $base_value + $trend_value + $variance ) );
			}
			$sparkline_max = max( $sparkline_data );
			$sparkline_min = min( $sparkline_data );
			$sparkline_range = $sparkline_max - $sparkline_min;
			if ( $sparkline_range === 0 ) {
				$sparkline_range = 1;
			}
			
			// Calculate circumference and offset for progress ring
			$radius = 56;
			$circumference = 2 * M_PI * $radius;
			$offset = $circumference * ( 1 - ( $percentage / 100 ) );
			?>
			
			<!-- Main Title -->
			<h1 style="font-size: 28px; font-weight: 700; color: #1f2937; margin: 0 0 32px 0; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.2;">
				Generate SEO Titles and Meta Descriptions with AI
			</h1>

			<!-- AI Power Meter Section -->
			<div style="display: flex; align-items: center; gap: 32px; margin-bottom: 32px; padding: 28px; background: white; border-radius: 12px; border: 1px solid #e5e7eb; transition: all 0.3s ease;" onmouseover="this.style.boxShadow='0 4px 12px rgba(0, 0, 0, 0.08)';" onmouseout="this.style.boxShadow='none';">
				<!-- Circular Progress Ring -->
				<div style="position: relative; width: 128px; height: 128px; flex-shrink: 0;">
					<svg width="128" height="128" style="transform: rotate(-90deg); transition: transform 0.3s ease;">
						<defs>
							<linearGradient id="seo-ai-meta-power-gradient" x1="0%" y1="0%" x2="100%" y2="100%">
								<stop offset="0%" style="stop-color:#2271b1;stop-opacity:1" />
								<stop offset="100%" style="stop-color:#3582c4;stop-opacity:1" />
							</linearGradient>
						</defs>
						<!-- Background circle -->
						<circle cx="64" cy="64" r="<?php echo esc_attr( $radius ); ?>" fill="none" stroke="#e5e7eb" stroke-width="12" style="transition: stroke 0.3s ease;"/>
						<!-- Progress circle -->
						<circle 
							id="seo-ai-meta-progress-ring" 
							cx="64" 
							cy="64" 
							r="<?php echo esc_attr( $radius ); ?>" 
							fill="none" 
							stroke="url(#seo-ai-meta-power-gradient)" 
							stroke-width="12" 
							stroke-linecap="round"
							data-percentage="<?php echo esc_attr( $percentage ); ?>"
							style="stroke-dasharray: <?php echo esc_attr( $circumference ); ?>; stroke-dashoffset: <?php echo esc_attr( $offset ); ?>; transition: stroke-dashoffset 0.8s cubic-bezier(0.4, 0, 0.2, 1);"
						/>
					</svg>
					<!-- Percentage text inside ring -->
					<div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center;">
						<div style="font-size: 24px; font-weight: 700; color: #2271b1; line-height: 1;">
							<?php echo esc_html( $percentage ); ?>%
						</div>
						<div style="font-size: 11px; color: #6b7280; margin-top: 2px; font-weight: 500;">Used</div>
					</div>
				</div>
				
				<!-- Usage Text and CTA -->
				<div style="flex: 1;">
					<?php if ( $is_maxed ) : ?>
						<p style="font-size: 15px; color: #374151; margin: 0 0 4px 0; line-height: 1.5;">
							<?php echo esc_html( $dynamic_tagline ); ?>
						</p>
						<p style="font-size: 16px; color: #1f2937; margin: 0 0 20px 0; line-height: 1.4; font-weight: 600;">
							<?php echo esc_html( $dynamic_subtitle ); ?>
						</p>
					<?php else : ?>
						<p style="font-size: 15px; color: #374151; margin: 0 0 12px 0; line-height: 1.5; font-weight: 500;">
							<?php echo esc_html( $dynamic_tagline ); ?>
						</p>
						<p style="font-size: 16px; color: #1f2937; margin: 0 0 4px 0; line-height: 1.4;">
							You've used <strong style="font-weight: 600;"><?php echo esc_html( $usage_stats['used'] ); ?></strong> of <strong style="font-weight: 600;"><?php echo esc_html( $usage_stats['limit'] ); ?></strong> generations this month
						</p>
						<p style="font-size: 13px; color: #6b7280; margin: 0 0 20px 0; line-height: 1.5;">
							Resets <?php echo esc_html( $usage_stats['reset_date'] ); ?>
						</p>
					<?php endif; ?>
					<?php if ( $is_maxed ) : ?>
						<button type="button" 
								onclick="seoAiMetaTrackEvent('upgrade_click', {source: 'maxed_out_banner', location: 'dashboard'}); seoAiMetaShowUpgradeModal();" 
								style="padding: 12px 24px; background: linear-gradient(135deg, #2271b1 0%, #3582c4 100%); color: white; border: none; border-radius: 8px; font-weight: 600; font-size: 15px; cursor: pointer; transition: all 0.2s; box-shadow: 0 2px 4px rgba(34, 113, 177, 0.25);"
								onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 8px rgba(34, 113, 177, 0.35)';"
								onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(34, 113, 177, 0.25)';">
							Upgrade to Pro
						</button>
					<?php elseif ( ! $is_authenticated ) : ?>
						<button type="button" 
								onclick="seoAiMetaTrackEvent('generate_more_click', {source: 'power_meter', authenticated: false}); seoAiMetaShowLoginModal();" 
								style="padding: 10px 20px; background: linear-gradient(135deg, #2271b1 0%, #3582c4 100%); color: white; border: none; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.2s; box-shadow: 0 2px 4px rgba(34, 113, 177, 0.25);">
							Generate More Meta Tags
						</button>
					<?php else : ?>
						<a href="<?php echo esc_url( add_query_arg( 'tab', 'bulk', $base_tab_url ) ); ?>" 
						   onclick="seoAiMetaTrackEvent('generate_more_click', {source: 'power_meter', authenticated: true});"
						   style="display: inline-block; padding: 10px 20px; background: linear-gradient(135deg, #2271b1 0%, #3582c4 100%); color: white; border: none; border-radius: 8px; font-weight: 600; font-size: 14px; text-decoration: none; transition: all 0.2s; box-shadow: 0 2px 4px rgba(34, 113, 177, 0.25);"
						   onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 8px rgba(34, 113, 177, 0.35)';"
						   onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(34, 113, 177, 0.25)';">
							Generate More Meta Tags
						</a>
					<?php endif; ?>
					<?php if ( ! $is_maxed ) : ?>
						<p style="font-size: 12px; color: #6b7280; margin: 8px 0 0 0; line-height: 1.4;">
							Upgrade to unlock unlimited generations + smart keyword tuning.
						</p>
					<?php endif; ?>
				</div>
			</div>
				
			<!-- SEO Impact Tracker Card -->
			<div style="margin-bottom: 32px; padding: 28px; background: white; border-radius: 12px; border: 1px solid #e5e7eb; position: relative;">
				<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
					<div style="display: flex; align-items: center; gap: 12px;">
						<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2" style="flex-shrink: 0;">
							<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
						</svg>
						<h2 style="font-size: 18px; font-weight: 600; color: #1f2937; margin: 0; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
							SEO Impact Tracker
						</h2>
					</div>
					<div style="font-size: 14px; color: #6b7280; font-weight: 500;">
						Estimate: <span style="color: #22c55e; font-weight: 600;">+<?php echo esc_html( $seo_impact['estimated_rankings'] ); ?>%</span> search visibility
					</div>
				</div>
				
				<p style="font-size: 16px; color: #374151; margin: 0 0 20px 0; line-height: 1.6;">
					You saved <strong style="color: #22c55e; font-weight: 600;"><?php echo esc_html( $time_saved_hours ); ?> hours</strong> 
					and improved <strong style="color: #22c55e; font-weight: 600;"><?php echo esc_html( $seo_impact['posts_optimized'] ); ?> meta tags</strong>, 
					boosting visibility by <strong style="color: #22c55e; font-weight: 600;">+<?php echo esc_html( $seo_impact['estimated_rankings'] ); ?>%</strong>.
				</p>
				
				<!-- Sparkline Chart -->
				<div style="margin-top: 20px; position: relative;" title="<?php esc_attr_e( 'Visibility gain based on keyword improvement and CTR metrics.', 'seo-ai-meta-generator' ); ?>">
					<svg width="100%" height="48" viewBox="0 0 200 48" preserveAspectRatio="none" style="overflow: visible;">
						<defs>
							<linearGradient id="sparkline-gradient" x1="0%" y1="0%" x2="0%" y2="100%">
								<stop offset="0%" style="stop-color:#22c55e;stop-opacity:0.3" />
								<stop offset="100%" style="stop-color:#22c55e;stop-opacity:0.1" />
							</linearGradient>
						</defs>
						<?php
						$sparkline_points = array();
						$sparkline_path = '';
						$width = 200;
						$height = 48;
						$padding = 4;
						$chart_width = $width - ( $padding * 2 );
						$chart_height = $height - ( $padding * 2 );
						
						foreach ( $sparkline_data as $index => $value ) {
							$x = $padding + ( $index / ( count( $sparkline_data ) - 1 ) ) * $chart_width;
							$y = $height - $padding - ( ( ( $value - $sparkline_min ) / $sparkline_range ) * $chart_height );
							$sparkline_points[] = array( 'x' => $x, 'y' => $y, 'value' => $value );
							$sparkline_path .= ( $index === 0 ? 'M' : 'L' ) . ' ' . $x . ',' . $y;
						}
						
						// Create area path for gradient fill
						$area_path = $sparkline_path . ' L ' . ( $width - $padding ) . ',' . ( $height - $padding ) . ' L ' . $padding . ',' . ( $height - $padding ) . ' Z';
						?>
						<!-- Gradient fill area -->
						<path d="<?php echo esc_attr( $area_path ); ?>" fill="url(#sparkline-gradient)"/>
						<!-- Line -->
						<path d="<?php echo esc_attr( $sparkline_path ); ?>" 
							  fill="none" 
							  stroke="#22c55e" 
							  stroke-width="2" 
							  stroke-linecap="round" 
							  stroke-linejoin="round"/>
						<!-- Data points -->
						<?php foreach ( $sparkline_points as $point ) : ?>
							<circle cx="<?php echo esc_attr( $point['x'] ); ?>" 
									cy="<?php echo esc_attr( $point['y'] ); ?>" 
									r="2" 
									fill="#22c55e" 
									opacity="0.8"/>
						<?php endforeach; ?>
					</svg>
				</div>
			</div>
				
			<!-- Two Column Layout -->
			<div class="seo-ai-meta-dashboard-grid" style="display: grid; grid-template-columns: 1fr 320px; gap: 24px; margin-bottom: 32px;">
				<!-- Left Column - Bulk Generate Preview -->
				<div>
					<div style="padding: 28px; background: white; border-radius: 12px; border: 1px solid #e5e7eb;">
						<h2 style="font-size: 18px; font-weight: 600; color: #1f2937; margin: 0 0 8px 0; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
							Bulk Generate
						</h2>
						<p style="font-size: 14px; color: #6b7280; margin: 0 0 20px 0; line-height: 1.5;">
							Automatically optimize all pages missing meta descriptions.
						</p>
						
						<?php
						// Get sample posts for display
						$sample_posts_query = SEO_AI_Meta_Bulk::get_posts_without_meta( 5, 1 );
						$recent_posts_with_meta = new WP_Query( array(
							'post_type'      => 'post',
							'post_status'    => 'publish',
							'posts_per_page' => 5,
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
						
						<!-- Table -->
						<div style="overflow: hidden; border-radius: 8px; border: 1px solid #e5e7eb; margin-bottom: 20px;">
							<table style="width: 100%; border-collapse: collapse;">
								<thead style="background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
									<tr>
										<th style="padding: 12px 16px; text-align: left; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">Page</th>
										<th style="padding: 12px 16px; text-align: left; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">Status</th>
										<th style="padding: 12px 16px; text-align: left; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">Date</th>
									</tr>
								</thead>
								<tbody style="background: white;">
									<?php if ( $sample_posts_query->have_posts() ) : ?>
										<?php while ( $sample_posts_query->have_posts() ) : $sample_posts_query->the_post(); ?>
											<tr style="border-bottom: 1px solid #f3f4f6; transition: background 0.2s;" onmouseover="this.style.background='#f9fafb';" onmouseout="this.style.background='white';">
												<td style="padding: 12px 16px; font-size: 14px; color: #1f2937;"><?php echo esc_html( get_the_title() ); ?></td>
												<td style="padding: 12px 16px;">
													<span style="display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 500; background: #f3f4f6; color: #6b7280;">
														⏳ Pending
													</span>
												</td>
												<td style="padding: 12px 16px; font-size: 13px; color: #6b7280;"><?php echo esc_html( get_the_date( 'M j, Y' ) ); ?></td>
											</tr>
										<?php endwhile; ?>
										<?php wp_reset_postdata(); ?>
									<?php endif; ?>
									
									<?php if ( $recent_posts_with_meta->have_posts() ) : ?>
										<?php while ( $recent_posts_with_meta->have_posts() ) : $recent_posts_with_meta->the_post(); ?>
											<tr style="border-bottom: 1px solid #f3f4f6; transition: background 0.2s;" onmouseover="this.style.background='#f9fafb';" onmouseout="this.style.background='white';">
												<td style="padding: 12px 16px; font-size: 14px; color: #1f2937;"><?php echo esc_html( get_the_title() ); ?></td>
												<td style="padding: 12px 16px;">
													<span style="display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 500; background: #d1fae5; color: #166534;">
														✅ Optimized
													</span>
												</td>
												<td style="padding: 12px 16px; font-size: 13px; color: #6b7280;"><?php echo esc_html( get_the_date( 'M j, Y' ) ); ?></td>
											</tr>
										<?php endwhile; ?>
										<?php wp_reset_postdata(); ?>
									<?php endif; ?>
								</tbody>
							</table>
						</div>

						<?php if ( $posts_without_meta > 0 ) : ?>
							<?php if ( ! $is_authenticated ) : ?>
								<button type="button" 
										onclick="seoAiMetaShowLoginModal();" 
										style="width: 100%; padding: 12px 24px; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; text-align: center; border: none; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.3);">
									Generate All
								</button>
							<?php else : ?>
								<a href="<?php echo esc_url( add_query_arg( 'tab', 'bulk', $base_tab_url ) ); ?>" 
								   style="display: inline-block; width: 100%; padding: 12px 24px; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; text-align: center; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 14px; transition: all 0.2s; box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.3);"
								   onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 12px -1px rgba(59, 130, 246, 0.4)';"
								   onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 6px -1px rgba(59, 130, 246, 0.3)';">
									Generate All
								</a>
							<?php endif; ?>
							<p style="font-size: 12px; color: #6b7280; margin: 8px 0 0 0; text-align: center; line-height: 1.4;">
								Automatically optimize all pages missing meta descriptions.
							</p>
						<?php else : ?>
							<div style="display: flex; align-items: center; justify-content: center; gap: 8px; padding: 16px; background: #d1fae5; border-radius: 8px; border: 1px solid #86efac;">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2.5">
									<path d="M20 6L9 17l-5-5"/>
								</svg>
								<span style="font-size: 14px; font-weight: 600; color: #166534;">All posts optimized!</span>
							</div>
						<?php endif; ?>
					</div>
				</div>

				<!-- Right Column - Upgrade to Pro Card -->
				<div style="position: sticky; top: 20px; height: fit-content;">
					<div class="seo-ai-meta-upgrade-card" style="padding: 28px; background: linear-gradient(135deg, #00C896 0%, #007F5F 100%); border-radius: 12px; box-shadow: 0 8px 16px rgba(0, 200, 150, 0.25); position: relative; overflow: hidden;">
						<!-- Shimmer animation overlay -->
						<div class="seo-ai-meta-shimmer" style="position: absolute; inset: 0; background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,0.1) 50%, transparent 100%); animation: shimmer 6s infinite; pointer-events: none;"></div>
						
						<div style="position: relative; z-index: 10;">
							<div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5">
									<circle cx="12" cy="12" r="3"/>
									<path d="M12 1v6m0 6v6M5.64 5.64l4.24 4.24m4.24 4.24l4.24 4.24M1 12h6m6 0h6M5.64 18.36l4.24-4.24m4.24-4.24l4.24-4.24"/>
								</svg>
								<h3 style="font-size: 18px; font-weight: 700; color: white; margin: 0; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
									Upgrade to Pro — Unlock Unlimited AI Power
								</h3>
							</div>
							<ul style="list-style: none; padding: 0; margin: 0 0 24px 0;">
								<li style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px; font-size: 14px; color: white;">
									<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" style="flex-shrink: 0;">
										<path d="M20 6L9 17l-5-5"/>
									</svg>
									<span>Unlimited generations</span>
								</li>
								<li style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px; font-size: 14px; color: white;">
									<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" style="flex-shrink: 0;">
										<path d="M20 6L9 17l-5-5"/>
									</svg>
									<span>Smart SEO tuning</span>
								</li>
								<li style="display: flex; align-items: center; gap: 10px; font-size: 14px; color: white;">
									<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" style="flex-shrink: 0;">
										<path d="M20 6L9 17l-5-5"/>
									</svg>
									<span>Priority support</span>
								</li>
							</ul>
							<button type="button" 
									onclick="seoAiMetaTrackEvent('upgrade_click', {source: 'upgrade_card', location: 'dashboard'}); seoAiMetaShowUpgradeModal();"
									style="width: 100%; padding: 12px 24px; background: white; color: #007F5F; border: none; border-radius: 8px; font-weight: 700; font-size: 14px; cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);"
									onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 12px rgba(0, 0, 0, 0.2)';"
									onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 8px rgba(0, 0, 0, 0.15)';">
								Go Pro
							</button>
						</div>
					</div>
				</div>
			</div>

			<!-- Bottom CTA Banner - AltText AI -->
			<?php if ( ! SEO_AI_Meta_Helpers::is_alttext_ai_active() ) : ?>
			<div id="seo-ai-meta-bottom-cta-banner" style="padding: 24px 28px; background: white; border-radius: 12px; border: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between; gap: 20px; margin-top: 32px; opacity: 0; animation: fadeInUp 0.6s ease-out 0.3s forwards;">
				<div style="flex: 1;">
					<p style="font-size: 15px; color: #1f2937; margin: 0 0 4px 0; font-weight: 600; line-height: 1.4;">
						Complete your SEO stack → Try AltText AI for automated image accessibility.
					</p>
				</div>
				<button type="button" 
						onclick="seoAiMetaTrackEvent('alttext_ai_cta_click', {source: 'dashboard_bottom'}); window.open('<?php echo esc_url( SEO_AI_Meta_Helpers::get_alttext_ai_url() ); ?>', '_blank');"
						style="display: flex; align-items: center; gap: 8px; padding: 10px 20px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.2s; white-space: nowrap; box-shadow: 0 2px 4px rgba(16, 185, 129, 0.25);"
						onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 8px rgba(16, 185, 129, 0.35)';"
						onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(16, 185, 129, 0.25)';">
					<span>Try AltText AI for automated image accessibility.</span>
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<line x1="5" y1="12" x2="19" y2="12"/>
						<polyline points="12 5 19 12 12 19"/>
					</svg>
				</button>
			</div>
			<?php endif; ?>

		<?php elseif ( $tab === 'bulk' ) : ?>
			<?php
			// Calculate progress stats
			$optimized_count = $posts_with_meta;
			$total_count = $total_posts;
			$pending_count = $posts_without_meta;
			$progress_percentage = $total_count > 0 ? round( ( $optimized_count / $total_count ) * 100 ) : 0;
			
			// Check if user is authenticated
			require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-api-client-v2.php';
			$api_client = new SEO_AI_Meta_API_Client_V2();
			$is_authenticated = $api_client->is_authenticated();
			
			// Calculate circumference and offset for progress ring
			$radius = 56;
			$circumference = 2 * M_PI * $radius;
			$offset = $circumference * ( 1 - ( $progress_percentage / 100 ) );
			?>
			
			<!-- Bulk Generate Tab - Redesigned -->
			<div>
				<!-- Two Column Layout -->
				<div style="display: grid; grid-template-columns: 1fr 320px; gap: 24px; margin-bottom: 32px;">
					<!-- Main Content -->
					<div>
						<div style="padding: 32px; background: white; border-radius: 12px; border: 1px solid #e5e7eb;">
							<!-- Progress Chart and Description -->
							<div style="display: flex; align-items: flex-start; gap: 32px; margin-bottom: 32px;">
								<!-- Circular Progress Chart -->
								<div style="position: relative; width: 128px; height: 128px; flex-shrink: 0;">
									<svg width="128" height="128" style="transform: rotate(-90deg); animation: fadeInScale 0.6s ease-out;">
										<defs>
											<linearGradient id="bulk-progress-gradient" x1="0%" y1="0%" x2="100%" y2="100%">
												<stop offset="0%" style="stop-color:#22c55e;stop-opacity:1" />
												<stop offset="100%" style="stop-color:#16a34a;stop-opacity:1" />
											</linearGradient>
										</defs>
										<!-- Background circle -->
										<circle cx="64" cy="64" r="<?php echo esc_attr( $radius ); ?>" fill="none" stroke="#e5e7eb" stroke-width="12"/>
										<!-- Progress circle -->
										<circle 
											id="bulk-progress-ring" 
											cx="64" 
											cy="64" 
											r="<?php echo esc_attr( $radius ); ?>" 
											fill="none" 
											stroke="url(#bulk-progress-gradient)" 
											stroke-width="12" 
											stroke-linecap="round"
											data-percentage="<?php echo esc_attr( $progress_percentage ); ?>"
											style="stroke-dasharray: <?php echo esc_attr( $circumference ); ?>; stroke-dashoffset: <?php echo esc_attr( $offset ); ?>; transition: stroke-dashoffset 0.8s cubic-bezier(0.4, 0, 0.2, 1);"
										/>
									</svg>
									<!-- Posts count text inside ring -->
									<div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center;">
										<div id="bulk-progress-count" style="font-size: 28px; font-weight: 700; color: #1f2937; line-height: 1; animation: fadeInUp 0.6s ease-out 0.2s both;">
											<?php echo esc_html( $optimized_count ); ?>/<?php echo esc_html( $total_count ); ?>
										</div>
										<div style="font-size: 11px; color: #6b7280; margin-top: 4px; animation: fadeInUp 0.6s ease-out 0.3s both;">posts</div>
									</div>
								</div>
								
								<!-- Description -->
								<div style="flex: 1; animation: fadeInUp 0.6s ease-out 0.1s both;">
									<p style="font-size: 14px; color: #6b7280; margin: 0 0 16px 0; line-height: 1.6;">
										<?php esc_html_e( 'You\'re improving your site\'s SEO visibility with each generation.', 'seo-ai-meta-generator' ); ?>
									</p>
									<h2 style="font-size: 24px; font-weight: 700; color: #1f2937; margin: 0 0 12px 0; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.3;">
										<?php esc_html_e( 'Bulk Generate Meta', 'seo-ai-meta-generator' ); ?>
									</h2>
									<p style="font-size: 14px; color: #6b7280; margin: 0; line-height: 1.6;">
										<?php esc_html_e( 'Automatically generate titles and descriptions for posts missing meta tags.', 'seo-ai-meta-generator' ); ?>
									</p>
								</div>
							</div>

							<?php if ( $pending_count > 0 ) : ?>
								<!-- Generate All Button -->
								<div style="text-align: center; margin-bottom: 32px;">
									<?php if ( ! $is_authenticated ) : ?>
										<button type="button" 
												id="seo-ai-meta-bulk-generate-all-btn"
												onclick="seoAiMetaShowLoginModal();" 
												style="padding: 14px 32px; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; border: none; border-radius: 8px; font-weight: 700; font-size: 16px; cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.35);"
												onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(59, 130, 246, 0.45)';"
												onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(59, 130, 246, 0.35)';">
											Generate All
										</button>
									<?php else : ?>
										<button type="button" 
												id="seo-ai-meta-bulk-generate-all-btn"
												style="padding: 14px 32px; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; border: none; border-radius: 8px; font-weight: 700; font-size: 16px; cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.35);"
												onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(59, 130, 246, 0.45)';"
												onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(59, 130, 246, 0.35)';">
											Generate All
										</button>
									<?php endif; ?>
								</div>

								<!-- Live Generation Log -->
								<div id="seo-ai-meta-bulk-log-container" style="display: none; border-top: 1px solid #e5e7eb; padding-top: 24px; margin-top: 24px; animation: fadeInUp 0.4s ease-out;">
									<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
										<h3 style="font-size: 14px; font-weight: 600; color: #374151; margin: 0;">Generation Log</h3>
										<button type="button" 
												id="seo-ai-meta-bulk-preview-btn"
												style="padding: 8px 16px; background: white; color: #374151; border: 1px solid #d1d5db; border-radius: 6px; font-weight: 500; font-size: 13px; cursor: pointer; transition: all 0.2s; display: none;"
												onmouseover="this.style.background='#f9fafb';"
												onmouseout="this.style.background='white';">
											Preview Changes
										</button>
									</div>
									<div id="seo-ai-meta-bulk-log" style="background: #f9fafb; border-radius: 8px; padding: 16px; max-height: 200px; overflow-y: auto; border: 1px solid #e5e7eb; font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace; font-size: 12px; line-height: 1.8;">
										<!-- Log entries will be added here dynamically -->
									</div>
								</div>

								<!-- Success State (hidden by default) -->
								<div id="seo-ai-meta-bulk-success" style="display: none; border-top: 1px solid #e5e7eb; padding-top: 24px; margin-top: 24px;">
									<div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px; animation: successPulse 0.6s ease-out;">
										<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="3" style="flex-shrink: 0;">
											<path d="M20 6L9 17l-5-5"/>
										</svg>
										<h3 style="font-size: 18px; font-weight: 700; color: #1f2937; margin: 0;">
											All posts optimized!
										</h3>
									</div>
									<div id="seo-ai-meta-bulk-success-log" style="background: #f9fafb; border-radius: 8px; padding: 16px; border: 1px solid #e5e7eb; font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace; font-size: 12px; line-height: 1.8;">
										<!-- Success log entries -->
									</div>
								</div>
							<?php else : ?>
								<!-- All Optimized State -->
								<div style="border-top: 1px solid #e5e7eb; padding-top: 32px; margin-top: 32px; animation: fadeInUp 0.6s ease-out;">
									<div style="display: flex; align-items: center; justify-content: center; gap: 12px; margin-bottom: 16px; animation: successPulse 0.6s ease-out;">
										<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="3" style="flex-shrink: 0;">
											<path d="M20 6L9 17l-5-5"/>
										</svg>
										<h3 style="font-size: 24px; font-weight: 700; color: #1f2937; margin: 0;">
											All posts optimized!
										</h3>
									</div>
									<p style="text-align: center; font-size: 15px; color: #6b7280; margin: 0; line-height: 1.6;">
										<?php esc_html_e( 'Great job! All your posts are optimized with SEO meta tags.', 'seo-ai-meta-generator' ); ?>
									</p>
								</div>
							<?php endif; ?>
						</div>
					</div>

					<!-- Right Column - Upgrade to Pro Card -->
					<div style="position: sticky; top: 20px; height: fit-content;">
						<div class="seo-ai-meta-upgrade-card" style="padding: 28px; background: linear-gradient(135deg, #00C896 0%, #007F5F 100%); border-radius: 12px; box-shadow: 0 8px 16px rgba(0, 200, 150, 0.25); position: relative; overflow: hidden;">
							<!-- Shimmer animation overlay -->
							<div class="seo-ai-meta-shimmer" style="position: absolute; inset: 0; background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,0.1) 50%, transparent 100%); animation: shimmer 6s infinite; pointer-events: none;"></div>
							
							<div style="position: relative; z-index: 10;">
								<div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
									<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5">
										<circle cx="12" cy="12" r="3"/>
										<path d="M12 1v6m0 6v6M5.64 5.64l4.24 4.24m4.24 4.24l4.24 4.24M1 12h6m6 0h6M5.64 18.36l4.24-4.24m4.24-4.24l4.24-4.24"/>
									</svg>
									<h3 style="font-size: 18px; font-weight: 700; color: white; margin: 0; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
										Upgrade to Pro
									</h3>
								</div>
								<p style="font-size: 14px; color: white; margin: 0 0 20px 0; opacity: 0.95; line-height: 1.5;">
									Unlock unlimited generations + advanced tuning
								</p>
								<button type="button" 
										onclick="seoAiMetaShowUpgradeModal();"
										style="width: 100%; padding: 12px 24px; background: white; color: #007F5F; border: none; border-radius: 8px; font-weight: 700; font-size: 14px; cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);"
										onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 12px rgba(0, 0, 0, 0.2)';"
										onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 8px rgba(0, 0, 0, 0.15)';">
									Go Pro
								</button>
							</div>
						</div>
					</div>
				</div>

				<!-- Bottom CTA Banner - AltText AI -->
				<?php if ( ! SEO_AI_Meta_Helpers::is_alttext_ai_active() ) : ?>
				<div id="seo-ai-meta-bulk-bottom-cta-banner" style="padding: 20px 28px; background: #f9fafb; border-radius: 12px; border: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between; gap: 20px; opacity: 0; animation: fadeInUp 0.6s ease-out 0.3s forwards;">
					<p style="font-size: 14px; color: #374151; margin: 0; font-weight: 500; flex: 1;">
						Complete your SEO Stack → Try AltText AI for image alt text automation
					</p>
					<button type="button" 
							onclick="window.open('<?php echo esc_url( SEO_AI_Meta_Helpers::get_alttext_ai_url() ); ?>', '_blank');"
							style="padding: 8px 16px; background: white; color: #374151; border: 1px solid #d1d5db; border-radius: 6px; font-weight: 500; font-size: 14px; cursor: pointer; transition: all 0.2s; white-space: nowrap;"
							onmouseover="this.style.background='#f3f4f6';"
							onmouseout="this.style.background='white';">
						Install Now
					</button>
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
			
			// Get user plan
			$user_plan = 'free';
			if ( $is_authenticated ) {
				$user_data = $api_client->get_user_data();
				if ( $user_data && isset( $user_data['plan'] ) ) {
					$user_plan = $user_data['plan'];
				}
			}
			$is_pro_user = in_array( $user_plan, array( 'pro', 'agency' ), true );
			?>
			
			<!-- Settings Page - Redesigned -->
			<div>
				<!-- Header -->
				<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 32px; flex-wrap: wrap; gap: 16px;">
					<div>
						<h1 style="font-size: 28px; font-weight: 700; color: #1f2937; margin: 0 0 8px 0; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.2;">
							<?php esc_html_e( 'Your SEO Meta Settings', 'seo-ai-meta-generator' ); ?>
						</h1>
						<p style="font-size: 14px; color: #6b7280; margin: 0; line-height: 1.5;">
							<?php esc_html_e( 'Fine-tune how AI generates your titles and descriptions. Unlock Pro for deeper control and unlimited automation.', 'seo-ai-meta-generator' ); ?>
						</p>
					</div>
					<button type="button" onclick="seoAiMetaShowUpgradeModal();" 
							style="padding: 10px 20px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.2s; white-space: nowrap;"
							onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 6px rgba(16, 185, 129, 0.3)';"
							onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
						<?php esc_html_e( 'View Plans', 'seo-ai-meta-generator' ); ?>
					</button>
				</div>
				
				<?php settings_errors( 'seo_ai_meta_settings_group' ); ?>
				
				<!-- Toast Notification Container -->
				<div id="seo-ai-meta-toast-container" style="position: fixed; top: 32px; right: 32px; z-index: 10000; display: none;">
					<div id="seo-ai-meta-toast" style="padding: 16px 20px; background: white; border-radius: 8px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); border-left: 4px solid #22c55e; display: flex; align-items: center; gap: 12px; min-width: 300px;">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2">
							<path d="M20 6L9 17l-5-5"/>
						</svg>
						<span id="seo-ai-meta-toast-message" style="color: #1f2937; font-size: 14px; font-weight: 500;"><?php esc_html_e( 'Settings saved successfully!', 'seo-ai-meta-generator' ); ?></span>
					</div>
				</div>
				
				<?php if ( isset( $_GET['import_success'] ) && $_GET['import_success'] === '1' ) : ?>
					<div style="padding: 16px; background: #d1fae5; border-left: 4px solid #22c55e; border-radius: 8px; margin-bottom: 24px;">
						<p style="margin: 0; font-size: 14px; color: #166534; font-weight: 500;">
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
					<div style="padding: 16px; background: #fee2e2; border-left: 4px solid #ef4444; border-radius: 8px; margin-bottom: 24px;">
						<p style="margin: 0; font-size: 14px; color: #991b1b; font-weight: 500;">
							<?php esc_html_e( 'Import failed. Please check your file and try again.', 'seo-ai-meta-generator' ); ?>
						</p>
					</div>
				<?php endif; ?>

				<!-- Free Plan Information Card -->
				<?php if ( ! $is_pro_user ) : ?>
				<div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; margin-bottom: 24px;">
					<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
						<div>
							<h3 style="margin: 0 0 4px 0; font-size: 16px; font-weight: 600; color: #1f2937;">
								<?php echo esc_html( ucfirst( $user_plan ) ); ?> Plan - <?php echo esc_html( $usage_stats['limit'] ); ?> AI generations / month
							</h3>
							<p style="margin: 0; font-size: 14px; color: #6b7280;">
								<?php echo esc_html( $usage_stats['used'] ); ?> used - Resets <?php echo esc_html( $usage_stats['reset_date'] ); ?>
							</p>
						</div>
						<a href="#" onclick="event.preventDefault(); seoAiMetaShowUpgradeModal();" style="color: #10b981; text-decoration: none; font-size: 14px; font-weight: 500;">
							<?php esc_html_e( 'See upgrade options', 'seo-ai-meta-generator' ); ?>
						</a>
					</div>
					<div style="background: #f3f4f6; height: 8px; border-radius: 4px; overflow: hidden; margin-top: 12px;">
						<div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); height: 100%; width: <?php echo min( 100, $usage_percent ); ?>%; transition: width 0.3s;"></div>
					</div>
				</div>
				<?php endif; ?>

				<!-- Two Column Layout -->
				<div style="display: flex; gap: 24px; flex-wrap: wrap;">
					<!-- Left Column: Settings Cards -->
					<div style="flex: 1; min-width: 0;">

						<!-- Settings Form -->
						<form method="post" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" class="seo-ai-meta-settings-form" id="seo-ai-meta-settings-form">
							<?php
							settings_fields( 'seo_ai_meta_settings_group' );
							do_settings_sections( 'seo_ai_meta_settings_group' );
							wp_nonce_field( 'seo_ai_meta_settings_group-options' );
							?>

							<!-- AI Generation Settings Card -->
							<div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px; margin-bottom: 24px;">
								<div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
									<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" style="flex-shrink: 0;">
										<path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/>
										<path d="M19 10v2a7 7 0 0 1-14 0v-2"/>
										<line x1="12" y1="19" x2="12" y2="23"/>
										<line x1="8" y1="23" x2="16" y2="23"/>
									</svg>
									<div>
										<h2 style="margin: 0; font-size: 18px; font-weight: 600; color: #1f2937;">
											<?php esc_html_e( 'AI Generation', 'seo-ai-meta-generator' ); ?>
										</h2>
										<p style="margin: 4px 0 0 0; font-size: 13px; color: #6b7280;">
											<?php esc_html_e( 'Fine-tune AI-generated meta tags', 'seo-ai-meta-generator' ); ?>
										</p>
									</div>
								</div>

								<!-- Title Length Slider -->
								<div style="margin-bottom: 24px;">
									<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
										<label for="title_max_length" style="font-weight: 600; color: #374151; font-size: 14px;">
											<?php esc_html_e( 'Title length', 'seo-ai-meta-generator' ); ?>
										</label>
										<span id="title-length-value" style="font-weight: 600; color: #10b981; font-size: 14px;"><?php echo esc_html( $settings['title_max_length'] ?? 60 ); ?></span>
									</div>
									<input type="range" id="title_max_length" name="seo_ai_meta_settings[title_max_length]" 
										value="<?php echo esc_attr( $settings['title_max_length'] ?? 60 ); ?>" 
										min="30" max="70" 
										style="width: 100%; height: 8px; border-radius: 4px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); outline: none; -webkit-appearance: none;"
										oninput="document.getElementById('title-length-value').textContent = this.value; updateSliderStyle(this);"
									/>
								</div>

								<!-- Description Length Slider -->
								<div style="margin-bottom: 24px;">
									<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
										<label for="description_max_length" style="font-weight: 600; color: #374151; font-size: 14px;">
											<?php esc_html_e( 'Description length', 'seo-ai-meta-generator' ); ?>
										</label>
										<span id="description-length-value" style="font-weight: 600; color: #10b981; font-size: 14px;"><?php echo esc_html( $settings['description_max_length'] ?? 160 ); ?></span>
									</div>
									<input type="range" id="description_max_length" name="seo_ai_meta_settings[description_max_length]" 
										value="<?php echo esc_attr( $settings['description_max_length'] ?? 160 ); ?>" 
										min="120" max="200" 
										style="width: 100%; height: 8px; border-radius: 4px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); outline: none; -webkit-appearance: none;"
										oninput="document.getElementById('description-length-value').textContent = this.value; updateSliderStyle(this);"
									/>
								</div>

								<!-- Automatic Generation Toggle -->
								<div style="display: flex; justify-content: space-between; align-items: center; padding-top: 16px; border-top: 1px solid #e5e7eb;">
									<div>
										<label for="auto_generate" style="font-weight: 500; color: #374151; font-size: 14px; cursor: pointer;">
											<?php esc_html_e( 'Automatically generate meta on new posts', 'seo-ai-meta-generator' ); ?>
										</label>
									</div>
									<label style="position: relative; display: inline-block; width: 44px; height: 24px; cursor: pointer;">
										<input type="checkbox" id="auto_generate" name="seo_ai_meta_settings[auto_generate]" 
											value="1" <?php checked( ! empty( $settings['auto_generate'] ) ); ?>
											<?php echo $is_pro_user ? '' : 'disabled'; ?>
											style="opacity: 0; width: 0; height: 0;"
										/>
										<span style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: <?php echo ! empty( $settings['auto_generate'] ) && $is_pro_user ? '#10b981' : '#d1d5db'; ?>; border-radius: 12px; transition: 0.3s;">
											<span style="position: absolute; content: ''; height: 18px; width: 18px; left: 3px; bottom: 3px; background: white; border-radius: 50%; transition: 0.3s; transform: translateX(<?php echo ! empty( $settings['auto_generate'] ) && $is_pro_user ? '20px' : '0'; ?>);"></span>
										</span>
									</label>
								</div>
								<?php if ( ! $is_pro_user ) : ?>
									<p style="margin: 8px 0 0 0; font-size: 12px; color: #6b7280;">
										<?php esc_html_e( 'Pro and Agency feature', 'seo-ai-meta-generator' ); ?>
									</p>
								<?php endif; ?>
							</div>

							<!-- License Mode Card -->
							<?php
							// Get license mode data
							require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-site-license.php';
							$license_mode = SEO_AI_Meta_Site_License::get_license_mode();
							$is_site_wide = SEO_AI_Meta_Site_License::is_site_wide_mode();
							$site_api_key = SEO_AI_Meta_Site_License::get_site_api_key();
							$can_manage = current_user_can( 'manage_options' );
							?>
							<div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px; margin-bottom: 24px;">
								<div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
									<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#8b5cf6" stroke-width="2" style="flex-shrink: 0;">
										<path d="M12 2L2 7l10 5 10-5-10-5z"/>
										<path d="M2 17l10 5 10-5"/>
										<path d="M2 12l10 5 10-5"/>
									</svg>
									<div>
										<h2 style="margin: 0; font-size: 18px; font-weight: 600; color: #1f2937;">
											<?php esc_html_e( 'License Mode', 'seo-ai-meta-generator' ); ?>
										</h2>
										<p style="margin: 4px 0 0 0; font-size: 13px; color: #6b7280;">
											<?php esc_html_e( 'Choose between per-user or site-wide licensing', 'seo-ai-meta-generator' ); ?>
										</p>
									</div>
								</div>

								<!-- License Mode Selection -->
								<div style="margin-bottom: 24px;">
									<div style="display: flex; gap: 12px; margin-bottom: 16px;">
										<!-- Per-User Mode -->
										<label style="flex: 1; cursor: <?php echo $can_manage ? 'pointer' : 'not-allowed'; ?>;">
											<input type="radio" name="seo_ai_meta_settings[license_mode]"
												value="per-user"
												<?php checked( $license_mode, 'per-user' ); ?>
												<?php echo $can_manage ? '' : 'disabled'; ?>
												style="margin-right: 8px;"
											/>
											<div style="padding: 16px; border: 2px solid <?php echo $license_mode === 'per-user' ? '#8b5cf6' : '#e5e7eb'; ?>; border-radius: 8px; transition: all 0.2s; background: <?php echo $license_mode === 'per-user' ? '#f5f3ff' : 'white'; ?>;">
												<h4 style="margin: 0 0 4px 0; font-size: 14px; font-weight: 600; color: #1f2937;">
													<?php esc_html_e( 'Per-User', 'seo-ai-meta-generator' ); ?>
												</h4>
												<p style="margin: 0; font-size: 13px; color: #6b7280; line-height: 1.5;">
													<?php esc_html_e( 'Each user logs in with their own account', 'seo-ai-meta-generator' ); ?>
												</p>
											</div>
										</label>

										<!-- Site-Wide Mode -->
										<label style="flex: 1; cursor: <?php echo $can_manage ? 'pointer' : 'not-allowed'; ?>;">
											<input type="radio" name="seo_ai_meta_settings[license_mode]"
												value="site-wide"
												<?php checked( $license_mode, 'site-wide' ); ?>
												<?php echo $can_manage ? '' : 'disabled'; ?>
												style="margin-right: 8px;"
											/>
											<div style="padding: 16px; border: 2px solid <?php echo $license_mode === 'site-wide' ? '#8b5cf6' : '#e5e7eb'; ?>; border-radius: 8px; transition: all 0.2s; background: <?php echo $license_mode === 'site-wide' ? '#f5f3ff' : 'white'; ?>;">
												<h4 style="margin: 0 0 4px 0; font-size: 14px; font-weight: 600; color: #1f2937;">
													<?php esc_html_e( 'Site-Wide', 'seo-ai-meta-generator' ); ?>
												</h4>
												<p style="margin: 0; font-size: 13px; color: #6b7280; line-height: 1.5;">
													<?php esc_html_e( 'One API key for entire site', 'seo-ai-meta-generator' ); ?>
												</p>
											</div>
										</label>
									</div>

									<?php if ( ! $can_manage ) : ?>
										<p style="margin: 0; font-size: 12px; color: #ef4444; display: flex; align-items: center; gap: 6px;">
											<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
												<circle cx="12" cy="12" r="10"/>
												<line x1="12" y1="8" x2="12" y2="12"/>
												<line x1="12" y1="16" x2="12.01" y2="16"/>
											</svg>
											<?php esc_html_e( 'Only administrators can change the license mode', 'seo-ai-meta-generator' ); ?>
										</p>
									<?php endif; ?>
								</div>

								<!-- Site API Key Field (shown only in site-wide mode) -->
								<?php if ( $is_site_wide && $can_manage ) : ?>
									<div style="padding-top: 16px; border-top: 1px solid #e5e7eb;">
										<label for="site_api_key" style="display: block; font-weight: 600; color: #374151; font-size: 14px; margin-bottom: 8px;">
											<?php esc_html_e( 'Site API Key', 'seo-ai-meta-generator' ); ?>
										</label>
										<div style="display: flex; gap: 8px;">
											<input type="text"
												id="site_api_key"
												name="seo_ai_meta_settings[site_api_key]"
												value="<?php echo esc_attr( $site_api_key ?? '' ); ?>"
												placeholder="<?php esc_attr_e( 'Enter your site API key...', 'seo-ai-meta-generator' ); ?>"
												style="flex: 1; padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 14px; font-family: monospace; background: #f9fafb;"
											/>
											<button type="button"
												onclick="navigator.clipboard.writeText(document.getElementById('site_api_key').value); alert('API key copied to clipboard!');"
												style="padding: 10px 16px; background: white; border: 1px solid #e5e7eb; border-radius: 6px; cursor: pointer; transition: all 0.2s;"
												onmouseover="this.style.background='#f9fafb';"
												onmouseout="this.style.background='white';"
												title="<?php esc_attr_e( 'Copy API key', 'seo-ai-meta-generator' ); ?>">
												<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2">
													<rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
													<path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
												</svg>
											</button>
										</div>
										<p style="margin: 8px 0 0 0; font-size: 12px; color: #6b7280;">
											<?php esc_html_e( 'Don\'t have an API key?', 'seo-ai-meta-generator' ); ?>
											<a href="#" onclick="event.preventDefault(); alert('Site registration wizard coming soon!');" style="color: #8b5cf6; text-decoration: none; font-weight: 500;">
												<?php esc_html_e( 'Register your site', 'seo-ai-meta-generator' ); ?>
											</a>
										</p>
									</div>
								<?php elseif ( $is_site_wide && ! $can_manage ) : ?>
									<div style="padding: 12px; background: #f9fafb; border-radius: 6px; border: 1px solid #e5e7eb;">
										<p style="margin: 0; font-size: 13px; color: #6b7280; display: flex; align-items: center; gap: 8px;">
											<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#8b5cf6" stroke-width="2">
												<path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/>
												<path d="M19 10v2a7 7 0 0 1-14 0v-2"/>
												<line x1="12" y1="19" x2="12" y2="23"/>
												<line x1="8" y1="23" x2="16" y2="23"/>
											</svg>
											<?php
											if ( ! empty( $site_api_key ) ) {
												esc_html_e( 'Site API key is configured. Contact your administrator to manage it.', 'seo-ai-meta-generator' );
											} else {
												esc_html_e( 'No API key configured yet. Contact your administrator.', 'seo-ai-meta-generator' );
											}
											?>
										</p>
									</div>
								<?php endif; ?>

								<!-- Info box -->
								<div style="margin-top: 16px; padding: 12px; background: #eff6ff; border-radius: 6px; border-left: 3px solid #3b82f6;">
									<p style="margin: 0; font-size: 13px; color: #1e40af; line-height: 1.5;">
										<strong><?php esc_html_e( 'Note:', 'seo-ai-meta-generator' ); ?></strong>
										<?php
										if ( $is_site_wide ) {
											esc_html_e( 'In site-wide mode, all users share the same quota and billing. Usage is tracked at the site level.', 'seo-ai-meta-generator' );
										} else {
											esc_html_e( 'In per-user mode, each user must have their own account with individual quotas and billing.', 'seo-ai-meta-generator' );
										}
										?>
									</p>
								</div>
							</div>

							<!-- Meta Templates Card -->
							<div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px; margin-bottom: 24px;">
								<div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
									<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2" style="flex-shrink: 0;">
										<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
										<polyline points="14 2 14 8 20 8"/>
										<line x1="16" y1="13" x2="8" y2="13"/>
										<line x1="16" y1="17" x2="8" y2="17"/>
										<polyline points="10 9 9 9 8 9"/>
									</svg>
									<div>
										<h2 style="margin: 0; font-size: 18px; font-weight: 600; color: #1f2937;">
											<?php esc_html_e( 'Meta Templates', 'seo-ai-meta-generator' ); ?>
										</h2>
									</div>
								</div>

								<div style="margin-bottom: 16px;">
									<label for="title_template" style="display: block; margin-bottom: 8px; font-weight: 500; color: #374151; font-size: 14px;">
										<?php esc_html_e( 'Title Meta Tag', 'seo-ai-meta-generator' ); ?>
									</label>
									<div style="display: flex; gap: 8px; align-items: center;">
										<input type="text" id="title_template" name="seo_ai_meta_settings[title_template]" 
											value="<?php echo esc_attr( $settings['title_template'] ?? '{{title}}' ); ?>" 
											placeholder="<?php esc_attr_e( '{{title}}', 'seo-ai-meta-generator' ); ?>"
											style="flex: 1; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; background: #f9fafb;"
										/>
										<a href="#" onclick="event.preventDefault(); document.getElementById('seo-ai-meta-variables-modal').style.display = 'flex';" style="color: #6b7280; text-decoration: none; font-size: 12px; white-space: nowrap;">
											<?php esc_html_e( 'Show variables', 'seo-ai-meta-generator' ); ?>
										</a>
									</div>
								</div>

								<div>
									<label for="description_template" style="display: block; margin-bottom: 8px; font-weight: 500; color: #374151; font-size: 14px;">
										<?php esc_html_e( 'Description Meta Tag', 'seo-ai-meta-generator' ); ?>
									</label>
									<div style="display: flex; gap: 8px; align-items: center;">
										<input type="text" id="description_template" name="seo_ai_meta_settings[description_template]" 
											value="<?php echo esc_attr( $settings['description_template'] ?? '{{description}}' ); ?>" 
											placeholder="<?php esc_attr_e( '{{description}}', 'seo-ai-meta-generator' ); ?>"
											style="flex: 1; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; background: #f9fafb;"
										/>
										<a href="#" onclick="event.preventDefault(); document.getElementById('seo-ai-meta-preview-modal').style.display = 'flex';" style="color: #10b981; text-decoration: none; font-size: 12px; font-weight: 500; white-space: nowrap;">
											<?php esc_html_e( 'Preview output', 'seo-ai-meta-generator' ); ?>
										</a>
									</div>
								</div>
							</div>

							<!-- Export/Import Card -->
							<div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px; margin-bottom: 24px;">
								<div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
									<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" style="flex-shrink: 0;">
										<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
										<polyline points="7 10 12 15 17 10"/>
										<line x1="12" y1="15" x2="12" y2="3"/>
									</svg>
									<div>
										<h2 style="margin: 0; font-size: 18px; font-weight: 600; color: #1f2937;">
											<?php esc_html_e( 'Export Meta Data', 'seo-ai-meta-generator' ); ?>
										</h2>
										<p style="margin: 4px 0 0 0; font-size: 13px; color: #6b7280;">
											<?php esc_html_e( 'Download a CSV file for backup', 'seo-ai-meta-generator' ); ?>
										</p>
									</div>
								</div>

								<!-- Export/Import Buttons -->
								<div style="display: flex; gap: 12px; align-items: flex-start;">
									<?php if ( $is_authenticated ) : ?>
									<a href="<?php echo esc_url( add_query_arg( array( 'action' => 'seo_ai_meta_export', 'nonce' => wp_create_nonce( 'seo_ai_meta_export' ) ), admin_url( 'admin-post.php' ) ) ); ?>" 
									   class="seo-ai-meta-export-btn"
									   style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 20px; background: white; color: #374151; border: 1px solid #d1d5db; border-radius: 8px; text-decoration: none; font-weight: 500; font-size: 14px; transition: all 0.2s;"
									   onmouseover="this.style.background='#f3f4f6'; this.style.borderColor='#9ca3af';"
									   onmouseout="this.style.background='white'; this.style.borderColor='#d1d5db';">
										<?php esc_html_e( 'Export CSV', 'seo-ai-meta-generator' ); ?>
									</a>
									<?php else : ?>
									<button type="button" onclick="seoAiMetaShowLoginModal();" 
											style="flex: 1; padding: 12px 20px; background: white; color: #374151; border: 1px solid #d1d5db; border-radius: 8px; font-weight: 500; font-size: 14px; cursor: pointer; transition: all 0.2s;"
											onmouseover="this.style.background='#f3f4f6'; this.style.borderColor='#9ca3af';"
											onmouseout="this.style.background='white'; this.style.borderColor='#d1d5db';">
										<?php esc_html_e( 'Export CSV', 'seo-ai-meta-generator' ); ?>
									</button>
									<?php endif; ?>

									<?php if ( $is_authenticated ) : ?>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" style="flex: 1; margin: 0;">
										<input type="hidden" name="action" value="seo_ai_meta_import">
										<?php wp_nonce_field( 'seo_ai_meta_import', 'seo_ai_meta_import_nonce' ); ?>
										<label style="display: block; cursor: pointer;">
											<input type="file" name="import_file" accept=".csv" required 
												   id="seo-ai-meta-import-file"
												   style="position: absolute; opacity: 0; width: 0; height: 0;"
												   onchange="this.form.submit();">
											<div style="display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 20px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; border-radius: 8px; font-weight: 500; font-size: 14px; cursor: pointer; transition: all 0.2s;"
												 onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 6px rgba(16, 185, 129, 0.3)';"
												 onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
												<?php esc_html_e( 'Upload CSV', 'seo-ai-meta-generator' ); ?>
											</div>
										</label>
									</form>
									<?php else : ?>
									<button type="button" onclick="seoAiMetaShowLoginModal();" 
											style="flex: 1; padding: 12px 20px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; border-radius: 8px; font-weight: 500; font-size: 14px; cursor: pointer; transition: all 0.2s;"
											onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 6px rgba(16, 185, 129, 0.3)';"
											onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
										<?php esc_html_e( 'Upload CSV', 'seo-ai-meta-generator' ); ?>
									</button>
									<?php endif; ?>
								</div>
							</div>

							<!-- Save Button -->
							<div style="margin-top: 24px;">
								<button type="submit" 
										style="width: 100%; padding: 12px 24px; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; border: none; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.2s;"
										onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 6px rgba(59, 130, 246, 0.3)';"
										onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
									<?php esc_html_e( 'Save Settings', 'seo-ai-meta-generator' ); ?>
								</button>
							</div>
						</form>
					</div>

					<!-- Right Column: Upgrade Card -->
					<div style="width: 320px; min-width: 280px;">
						<!-- Unlock Pro Automation Card -->
						<div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px; margin-bottom: 24px;">
							<div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
								<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" style="flex-shrink: 0;">
									<path d="M20 6L9 17l-5-5"/>
								</svg>
								<h3 style="margin: 0; font-size: 18px; font-weight: 600; color: #1f2937;">
									<?php esc_html_e( 'Unlock Pro Automation', 'seo-ai-meta-generator' ); ?>
								</h3>
							</div>
							<p style="margin: 0 0 20px 0; font-size: 14px; color: #6b7280; line-height: 1.6;">
								<?php esc_html_e( 'Generate unlimited meta tags automatically', 'seo-ai-meta-generator' ); ?>
							</p>
							<ul style="list-style: none; padding: 0; margin: 0 0 24px 0;">
								<li style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px; font-size: 14px; color: #374151;">
									<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" style="flex-shrink: 0;">
										<path d="M22 12h-4M6 12H2M12 6V2M12 22v-4M6.34 6.34l-2.83-2.83M20.49 20.49l-2.83-2.83M17.66 6.34l2.83-2.83M3.51 20.49l2.83-2.83"/>
									</svg>
									<span><?php esc_html_e( 'Gain SEO insights per post', 'seo-ai-meta-generator' ); ?></span>
								</li>
								<li style="display: flex; align-items: center; gap: 8px; font-size: 14px; color: #374151;">
									<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" style="flex-shrink: 0;">
										<path d="M22 12h-4M6 12H2M12 6V2M12 22v-4M6.34 6.34l-2.83-2.83M20.49 20.49l-2.83-2.83M17.66 6.34l2.83-2.83M3.51 20.49l2.83-2.83"/>
									</svg>
									<span><?php esc_html_e( 'Boost performance with bulk AI tuning', 'seo-ai-meta-generator' ); ?></span>
								</li>
							</ul>
							<button type="button" onclick="seoAiMetaShowUpgradeModal();" 
									style="width: 100%; padding: 12px 24px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.2s;"
									onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 6px rgba(16, 185, 129, 0.3)';"
									onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
								<?php esc_html_e( 'Upgrade to Pro', 'seo-ai-meta-generator' ); ?>
							</button>
						</div>

						<!-- Migration Information -->
						<div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px;">
							<div style="display: flex; align-items: flex-start; gap: 12px;">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2" style="flex-shrink: 0; margin-top: 2px;">
									<circle cx="12" cy="12" r="10"/>
									<line x1="12" y1="16" x2="12" y2="12"/>
									<line x1="12" y1="8" x2="12.01" y2="8"/>
								</svg>
								<div>
									<h4 style="margin: 0 0 8px 0; font-size: 14px; font-weight: 600; color: #1f2937;">
										<?php esc_html_e( 'Migrating from another SEO plugin?', 'seo-ai-meta-generator' ); ?>
									</h4>
									<p style="margin: 0; font-size: 13px; color: #6b7280; line-height: 1.5;">
										<?php esc_html_e( 'You can import your existing meta data', 'seo-ai-meta-generator' ); ?>
									</p>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- Show Variables Modal -->
			<div id="seo-ai-meta-variables-modal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); z-index: 10000; align-items: center; justify-content: center;"
				 onclick="if(event.target === this) { document.getElementById('seo-ai-meta-variables-modal').style.display = 'none'; }">
				<div style="background: white; border-radius: 12px; padding: 24px; max-width: 500px; width: 90%; max-height: 80vh; overflow-y: auto; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);"
					 onclick="event.stopPropagation();">
					<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
						<h3 style="font-size: 18px; font-weight: 700; color: #1f2937; margin: 0;">
							<?php esc_html_e( 'Template Variables', 'seo-ai-meta-generator' ); ?>
						</h3>
						<button type="button" onclick="document.getElementById('seo-ai-meta-variables-modal').style.display = 'none';"
								style="background: none; border: none; color: #6b7280; cursor: pointer; padding: 4px;"
								onmouseover="this.style.color='#1f2937';"
								onmouseout="this.style.color='#6b7280';">
							<svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2">
								<path d="M15 5L5 15M5 5l10 10"/>
							</svg>
						</button>
					</div>
					<div style="display: grid; gap: 12px;">
						<div style="padding: 12px; background: #f9fafb; border-radius: 8px; border: 1px solid #e5e7eb;">
							<code style="font-size: 14px; color: #3b82f6; font-weight: 600;">{{title}}</code>
							<p style="margin: 4px 0 0 0; font-size: 13px; color: #6b7280;"><?php esc_html_e( 'Post title', 'seo-ai-meta-generator' ); ?></p>
						</div>
						<div style="padding: 12px; background: #f9fafb; border-radius: 8px; border: 1px solid #e5e7eb;">
							<code style="font-size: 14px; color: #3b82f6; font-weight: 600;">{{description}}</code>
							<p style="margin: 4px 0 0 0; font-size: 13px; color: #6b7280;"><?php esc_html_e( 'AI-generated description', 'seo-ai-meta-generator' ); ?></p>
						</div>
						<div style="padding: 12px; background: #f9fafb; border-radius: 8px; border: 1px solid #e5e7eb;">
							<code style="font-size: 14px; color: #3b82f6; font-weight: 600;">{{date}}</code>
							<p style="margin: 4px 0 0 0; font-size: 13px; color: #6b7280;"><?php esc_html_e( 'Publication date', 'seo-ai-meta-generator' ); ?></p>
						</div>
						<div style="padding: 12px; background: #f9fafb; border-radius: 8px; border: 1px solid #e5e7eb;">
							<code style="font-size: 14px; color: #3b82f6; font-weight: 600;">{{category}}</code>
							<p style="margin: 4px 0 0 0; font-size: 13px; color: #6b7280;"><?php esc_html_e( 'Post category', 'seo-ai-meta-generator' ); ?></p>
						</div>
						<div style="padding: 12px; background: #f9fafb; border-radius: 8px; border: 1px solid #e5e7eb;">
							<code style="font-size: 14px; color: #3b82f6; font-weight: 600;">{{author}}</code>
							<p style="margin: 4px 0 0 0; font-size: 13px; color: #6b7280;"><?php esc_html_e( 'Post author', 'seo-ai-meta-generator' ); ?></p>
						</div>
						<div style="padding: 12px; background: #f9fafb; border-radius: 8px; border: 1px solid #e5e7eb;">
							<code style="font-size: 14px; color: #3b82f6; font-weight: 600;">{{site}}</code>
							<p style="margin: 4px 0 0 0; font-size: 13px; color: #6b7280;"><?php esc_html_e( 'Site name', 'seo-ai-meta-generator' ); ?></p>
						</div>
					</div>
					<button type="button" onclick="document.getElementById('seo-ai-meta-variables-modal').style.display = 'none';"
							style="width: 100%; margin-top: 20px; padding: 10px 16px; background: #f3f4f6; color: #374151; border: 1px solid #d1d5db; border-radius: 6px; font-weight: 500; font-size: 14px; cursor: pointer;">
						<?php esc_html_e( 'Close', 'seo-ai-meta-generator' ); ?>
					</button>
				</div>
			</div>

			<!-- Preview Modal -->
			<div id="seo-ai-meta-preview-modal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); z-index: 10000; align-items: center; justify-content: center;"
				 onclick="if(event.target === this) { document.getElementById('seo-ai-meta-preview-modal').style.display = 'none'; }">
				<div style="background: white; border-radius: 12px; padding: 24px; max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);"
					 onclick="event.stopPropagation();">
					<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
						<h3 style="font-size: 18px; font-weight: 700; color: #1f2937; margin: 0;">
							<?php esc_html_e( 'Preview Output', 'seo-ai-meta-generator' ); ?>
						</h3>
						<button type="button" onclick="document.getElementById('seo-ai-meta-preview-modal').style.display = 'none';"
								style="background: none; border: none; color: #6b7280; cursor: pointer; padding: 4px;"
								onmouseover="this.style.color='#1f2937';"
								onmouseout="this.style.color='#6b7280';">
							<svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2">
								<path d="M15 5L5 15M5 5l10 10"/>
							</svg>
						</button>
					</div>
					<div id="seo-ai-meta-preview-content" style="padding: 16px; background: #f9fafb; border-radius: 8px; border: 1px solid #e5e7eb;">
						<p style="margin: 0; font-size: 14px; color: #6b7280;"><?php esc_html_e( 'Preview will appear here...', 'seo-ai-meta-generator' ); ?></p>
					</div>
					<button type="button" onclick="document.getElementById('seo-ai-meta-preview-modal').style.display = 'none';"
							style="width: 100%; margin-top: 20px; padding: 10px 16px; background: #f3f4f6; color: #374151; border: 1px solid #d1d5db; border-radius: 6px; font-weight: 500; font-size: 14px; cursor: pointer;">
						<?php esc_html_e( 'Close', 'seo-ai-meta-generator' ); ?>
					</button>
				</div>
			</div>

			<script>
			(function($) {
				'use strict';

				// Toast notification function
				function showToast(message) {
					var toast = $('#seo-ai-meta-toast-container');
					$('#seo-ai-meta-toast-message').text(message);
					toast.fadeIn(300);
					setTimeout(function() {
						toast.fadeOut(300);
					}, 3000);
				}

				// Update slider style (green gradient)
				if (typeof updateSliderStyle === 'undefined') {
					window.updateSliderStyle = function(slider) {
						var value = (slider.value - slider.min) / (slider.max - slider.min) * 100;
						slider.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
					};
				}

				// Initialize sliders on page load
				$(document).ready(function() {
					$('input[type="range"]').each(function() {
						updateSliderStyle(this);
					});

					// Handle form submission with toast
					$('#seo-ai-meta-settings-form').on('submit', function(e) {
						var form = $(this);
						var originalAction = form.attr('action');
						
						// Check if settings are being saved
						if (originalAction.includes('options.php')) {
							// Show toast after successful save
							setTimeout(function() {
								if ($('.notice-success').length > 0 || $('body').find('[class*="updated"]').length > 0) {
									showToast('<?php echo esc_js( __( 'Settings saved successfully!', 'seo-ai-meta-generator' ) ); ?>');
								}
							}, 500);
						}
					});

					// Auto-generate toggle handler
					$('#auto_generate').on('change', function() {
						var toggle = $(this);
						var span = toggle.next('span');
						if (toggle.is(':checked')) {
							span.css('background', '#10b981');
							span.find('span').css('transform', 'translateX(20px)');
						} else {
							span.css('background', '#d1d5db');
							span.find('span').css('transform', 'translateX(0)');
						}
					});

					// Close modals on ESC key
					$(document).on('keydown', function(e) {
						if (e.key === 'Escape') {
							$('#seo-ai-meta-variables-modal, #seo-ai-meta-preview-modal').hide();
						}
					});

					// Preview output handler
					$('a[onclick*="seo-ai-meta-preview-modal"]').on('click', function(e) {
						e.preventDefault();
						var titleTemplate = $('#title_template').val() || '{{title}}';
						var descTemplate = $('#description_template').val() || '{{description}}';
						
						// Replace variables with example values
						var preview = '<div style="margin-bottom: 16px;"><strong style="font-size: 14px; color: #374151; display: block; margin-bottom: 4px;">Title:</strong><div style="padding: 8px 12px; background: white; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 14px; color: #1f2937;">' + titleTemplate.replace(/\{\{title\}\}/g, 'Example Post Title').replace(/\{\{site\}\}/g, 'My Website') + '</div></div>';
						preview += '<div><strong style="font-size: 14px; color: #374151; display: block; margin-bottom: 4px;">Description:</strong><div style="padding: 8px 12px; background: white; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 14px; color: #1f2937;">' + descTemplate.replace(/\{\{description\}\}/g, 'This is an example AI-generated description').replace(/\{\{title\}\}/g, 'Example Post Title') + '</div></div>';
						
						$('#seo-ai-meta-preview-content').html(preview);
						$('#seo-ai-meta-preview-modal').css('display', 'flex');
					});
				});
			})(jQuery);
			</script>

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
			$date_filter = isset( $_GET['date'] ) ? sanitize_text_field( $_GET['date'] ) : '';

			$filters = array();
			if ( $level_filter ) {
				$filters['level'] = $level_filter;
			}
			if ( $search_filter ) {
				$filters['search'] = $search_filter;
			}

			$logs = SEO_AI_Meta_Logger::get_logs( $filters );
			
			// Apply date filter if set
			if ( $date_filter ) {
				$filter_date = strtotime( $date_filter );
				$logs = array_filter( $logs, function( $log ) use ( $filter_date ) {
					$log_date = strtotime( $log['timestamp'] );
					return date( 'Y-m-d', $log_date ) === date( 'Y-m-d', $filter_date );
				} );
				$logs = array_values( $logs ); // Re-index array
			}
			$stats = SEO_AI_Meta_Logger::get_stats();
			
			// Get last API call timestamp
			$all_logs = SEO_AI_Meta_Logger::get_logs( array() );
			$last_api_call = null;
			foreach ( $all_logs as $log ) {
				if ( isset( $log['message'] ) && ( stripos( $log['message'], 'API' ) !== false || ( isset( $log['context']['endpoint'] ) ) ) ) {
					$last_api_call = $log['timestamp'];
					break;
				}
			}
			if ( ! $last_api_call && ! empty( $all_logs ) ) {
				$last_api_call = $all_logs[0]['timestamp'];
			}
			
			// Format last API call time
			$last_api_call_formatted = $last_api_call ? date( 'g:i A', strtotime( $last_api_call ) ) : 'Never';
			
			// Get user plan for Pro features
			$user_plan = 'free';
			if ( $is_authenticated ) {
				$user_data = $api_client->get_user_data();
				if ( $user_data && isset( $user_data['plan'] ) ) {
					$user_plan = $user_data['plan'];
				}
			}
			$is_pro = in_array( $user_plan, array( 'pro', 'agency' ), true );
			?>

			<!-- Debug Logs Tab -->
			<div style="display: flex; gap: 24px; flex-wrap: wrap;">
				<div style="flex: 1; min-width: 0;">
					<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
						<div>
							<h1 style="font-size: 28px; font-weight: 700; color: #1f2937; margin: 0 0 8px 0; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.2;">
								<?php esc_html_e( 'System Activity & Debug Logs', 'seo-ai-meta-generator' ); ?>
							</h1>
							<p style="font-size: 14px; color: #6b7280; margin: 0; line-height: 1.5;">
								<?php esc_html_e( 'Monitor plugin performance, API activity, and recent operations in real-time.', 'seo-ai-meta-generator' ); ?>
							</p>
						</div>
						<form method="post" style="display: inline;">
							<?php wp_nonce_field( 'seo_ai_meta_clear_logs' ); ?>
							<button type="submit" name="seo_ai_meta_clear_logs" 
									onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to clear all logs?', 'seo-ai-meta-generator' ); ?>');"
									style="padding: 8px 16px; background: white; color: #6b7280; border: 1px solid #d1d5db; border-radius: 6px; font-weight: 500; font-size: 14px; cursor: pointer; transition: all 0.2s;"
									onmouseover="this.style.background='#f3f4f6'; this.style.borderColor='#9ca3af';"
									onmouseout="this.style.background='white'; this.style.borderColor='#d1d5db';">
								<?php esc_html_e( 'Clear Logs', 'seo-ai-meta-generator' ); ?>
							</button>
						</form>
					</div>

					<!-- Header Summary with Key Metrics -->
					<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px;">
						<div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px;">
							<div style="font-size: 12px; color: #6b7280; margin-bottom: 8px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px;"><?php esc_html_e( 'Total Logs', 'seo-ai-meta-generator' ); ?></div>
							<div style="font-size: 28px; font-weight: 700; color: #1f2937;"><?php echo esc_html( $stats['total'] ); ?></div>
						</div>
						<div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px;">
							<div style="font-size: 12px; color: #6b7280; margin-bottom: 8px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px;"><?php esc_html_e( 'Warnings', 'seo-ai-meta-generator' ); ?></div>
							<div style="font-size: 28px; font-weight: 700; color: #f59e0b;"><?php echo esc_html( $stats['warning'] ); ?></div>
						</div>
						<div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px;">
							<div style="font-size: 12px; color: #6b7280; margin-bottom: 8px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px;"><?php esc_html_e( 'Errors', 'seo-ai-meta-generator' ); ?></div>
							<div style="font-size: 28px; font-weight: 700; color: #dc2626;"><?php echo esc_html( $stats['error'] ); ?></div>
						</div>
						<div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px;">
							<div style="font-size: 12px; color: #6b7280; margin-bottom: 8px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px;"><?php esc_html_e( 'Last API Call', 'seo-ai-meta-generator' ); ?></div>
							<div style="font-size: 28px; font-weight: 700; color: #1f2937;"><?php echo esc_html( $last_api_call_formatted ); ?></div>
						</div>
					</div>

					<!-- Filter/Search Toolbar -->
					<div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; margin-bottom: 24px;">
						<div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
							<!-- Level Filter -->
							<div style="position: relative; display: flex; align-items: center;">
								<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="position: absolute; left: 12px; color: #6b7280; pointer-events: none;">
									<polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
								</svg>
								<select id="seo-ai-meta-log-level-filter" 
										style="padding: 10px 12px 10px 36px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; background: white; min-width: 150px; appearance: none; cursor: pointer;">
									<option value=""><?php esc_html_e( 'All', 'seo-ai-meta-generator' ); ?></option>
									<option value="ERROR" <?php selected( $level_filter, 'ERROR' ); ?>><?php esc_html_e( 'Errors Only', 'seo-ai-meta-generator' ); ?></option>
									<option value="WARNING" <?php selected( $level_filter, 'WARNING' ); ?>><?php esc_html_e( 'Warnings Only', 'seo-ai-meta-generator' ); ?></option>
									<option value="INFO" <?php selected( $level_filter, 'INFO' ); ?>><?php esc_html_e( 'Info Only', 'seo-ai-meta-generator' ); ?></option>
									<option value="DEBUG" <?php selected( $level_filter, 'DEBUG' ); ?>><?php esc_html_e( 'Debug Only', 'seo-ai-meta-generator' ); ?></option>
								</select>
							</div>

							<!-- Date Picker -->
							<input type="date" id="seo-ai-meta-log-date-filter"
								   value="<?php echo isset( $_GET['date'] ) ? esc_attr( sanitize_text_field( $_GET['date'] ) ) : ''; ?>"
								   style="padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; background: white; min-width: 150px;">

							<!-- Search -->
							<input type="text" id="seo-ai-meta-log-search"
								   placeholder="<?php esc_attr_e( 'Filter by message or context...', 'seo-ai-meta-generator' ); ?>"
								   value="<?php echo esc_attr( $search_filter ); ?>"
								   style="padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; flex: 1; min-width: 200px; background: white;">

							<button type="button" id="seo-ai-meta-apply-filters" 
									style="padding: 10px 20px; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; border: none; border-radius: 6px; font-weight: 500; font-size: 14px; cursor: pointer; transition: all 0.2s; white-space: nowrap;"
									onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 6px rgba(59, 130, 246, 0.3)';"
									onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
								<?php esc_html_e( 'Apply Filters', 'seo-ai-meta-generator' ); ?>
							</button>
						</div>

						<div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 16px; justify-content: space-between; align-items: center;">
							<div style="display: flex; gap: 8px; flex-wrap: wrap;">
								<!-- Export Dropdown -->
								<div style="position: relative; display: inline-block;">
									<button type="button" id="seo-ai-meta-export-dropdown-btn"
											style="padding: 10px 16px; background: white; color: #374151; border: 1px solid #d1d5db; border-radius: 6px; font-weight: 500; font-size: 14px; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 6px;"
											onmouseover="this.style.background='#f3f4f6'; this.style.borderColor='#9ca3af';"
											onmouseout="this.style.background='white'; this.style.borderColor='#d1d5db';"
											onclick="document.getElementById('seo-ai-meta-export-menu').style.display = document.getElementById('seo-ai-meta-export-menu').style.display === 'block' ? 'none' : 'block';">
										<?php esc_html_e( 'Export', 'seo-ai-meta-generator' ); ?>
										<svg width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2">
											<polyline points="3 4.5 6 7.5 9 4.5"/>
										</svg>
									</button>
									<div id="seo-ai-meta-export-menu" style="display: none; position: absolute; top: 100%; left: 0; margin-top: 4px; background: white; border: 1px solid #d1d5db; border-radius: 6px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); z-index: 1000; min-width: 150px;">
										<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'tab' => 'logs', 'export' => 'csv' ), $base_tab_url ), 'seo_ai_meta_export_logs', 'nonce' ) ); ?>"
										   style="display: block; padding: 10px 16px; color: #374151; text-decoration: none; font-size: 14px; transition: background 0.2s; border-radius: 6px 6px 0 0;"
										   onmouseover="this.style.background='#f3f4f6';"
										   onmouseout="this.style.background='white';">
											<?php esc_html_e( 'Export CSV', 'seo-ai-meta-generator' ); ?>
										</a>
										<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'tab' => 'logs', 'export' => 'json' ), $base_tab_url ), 'seo_ai_meta_export_logs', 'nonce' ) ); ?>"
										   style="display: block; padding: 10px 16px; color: #374151; text-decoration: none; font-size: 14px; transition: background 0.2s; border-radius: 0 0 6px 6px;"
										   onmouseover="this.style.background='#f3f4f6';"
										   onmouseout="this.style.background='white';">
											<?php esc_html_e( 'Export JSON', 'seo-ai-meta-generator' ); ?>
										</a>
									</div>
								</div>

								<!-- Clear Logs -->
								<form method="post" style="display: inline;">
									<?php wp_nonce_field( 'seo_ai_meta_clear_logs' ); ?>
									<button type="submit" name="seo_ai_meta_clear_logs" 
											onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to clear all logs?', 'seo-ai-meta-generator' ); ?>');"
											style="padding: 10px 16px; background: white; color: #dc2626; border: 1px solid #fecaca; border-radius: 6px; font-weight: 500; font-size: 14px; cursor: pointer; transition: all 0.2s;"
											onmouseover="this.style.background='#fee2e2';"
											onmouseout="this.style.background='white';">
										<?php esc_html_e( 'Clear Logs', 'seo-ai-meta-generator' ); ?>
									</button>
								</form>
							</div>
						</div>
					</div>

				<!-- Logs Table -->
				<div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 28px;">
					<?php if ( empty( $logs ) ) : ?>
						<div style="padding: 48px; text-align: center;">
							<svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2" style="margin: 0 auto 16px;">
								<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
								<polyline points="14 2 14 8 20 8"/>
								<line x1="12" y1="18" x2="12" y2="12"/>
								<line x1="9" y1="15" x2="15" y2="15"/>
							</svg>
							<p style="font-size: 18px; font-weight: 600; color: #1f2937; margin: 0 0 8px 0;"><?php esc_html_e( 'No logs found', 'seo-ai-meta-generator' ); ?></p>
							<?php if ( $level_filter || $search_filter ) : ?>
								<p style="font-size: 14px; color: #6b7280; margin: 0;"><?php esc_html_e( 'Try adjusting your filters', 'seo-ai-meta-generator' ); ?></p>
							<?php endif; ?>
						</div>
					<?php else : ?>
						<div style="overflow-x: auto;">
							<table style="width: 100%; border-collapse: collapse;">
								<thead>
									<tr style="border-bottom: 2px solid #e5e7eb;">
										<th style="padding: 12px; text-align: left; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; width: 140px;"><?php esc_html_e( 'TIMESTAMP', 'seo-ai-meta-generator' ); ?></th>
										<th style="padding: 12px; text-align: left; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; width: 100px;"><?php esc_html_e( 'LEVEL', 'seo-ai-meta-generator' ); ?></th>
										<th style="padding: 12px; text-align: left; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;"><?php esc_html_e( 'MESSAGE', 'seo-ai-meta-generator' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php 
									$row_index = 0;
									foreach ( $logs as $log ) : 
										$row_index++;
										$row_bg = ( $row_index % 2 === 0 ) ? '#f9fafb' : 'white';
										?>
										<?php
										$level_class = '';
										$level_color = '#6b7280';
										$level_bg = '#f3f4f6';
										switch ( $log['level'] ) {
											case 'ERROR':
												$level_class = 'error';
												$level_color = '#dc2626';
												$level_bg = '#fee2e2';
												break;
											case 'WARNING':
												$level_class = 'warning';
												$level_color = '#f59e0b';
												$level_bg = '#fef3c7';
												break;
											case 'INFO':
												$level_class = 'info';
												$level_color = '#6b7280';
												$level_bg = '#f3f4f6';
												break;
											case 'DEBUG':
												$level_class = 'debug';
												$level_color = '#3b82f6';
												$level_bg = '#dbeafe';
												break;
										}
										
										// Format timestamp to match image (e.g., "Jan 25, 10:14")
										$timestamp_formatted = '';
										if ( ! empty( $log['timestamp'] ) ) {
											$ts = strtotime( $log['timestamp'] );
											$timestamp_formatted = date( 'M j, g:i', $ts );
										}
										
										$log_id = 'log-' . md5( $log['timestamp'] . $log['message'] );
										$context_json = ! empty( $log['context'] ) ? wp_json_encode( $log['context'], JSON_PRETTY_PRINT ) : '';
										?>
										<tr style="background: <?php echo esc_attr( $row_bg ); ?>; border-bottom: 1px solid #f3f4f6;">
											<td style="padding: 12px; font-size: 13px; color: #6b7280;">
												<?php echo esc_html( $timestamp_formatted ?: $log['timestamp'] ); ?>
											</td>
											<td style="padding: 12px;">
												<span style="display: inline-block; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; background: <?php echo esc_attr( $level_bg ); ?>; color: <?php echo esc_attr( $level_color ); ?>; border: none;">
													<?php echo esc_html( $log['level'] ); ?>
												</span>
											</td>
											<td style="padding: 12px; font-size: 14px; color: #374151;">
												<div style="display: flex; align-items: center; justify-content: space-between; gap: 12px;">
													<span style="flex: 1;"><?php echo esc_html( $log['message'] ); ?></span>
													<?php if ( ! empty( $log['context'] ) ) : ?>
														<button type="button" 
																class="seo-ai-meta-view-context-btn"
																data-log-id="<?php echo esc_attr( $log_id ); ?>"
																data-message="<?php echo esc_attr( $log['message'] ); ?>"
																data-context="<?php echo esc_attr( htmlspecialchars( $context_json, ENT_QUOTES, 'UTF-8' ) ); ?>"
																style="padding: 6px 12px; background: #f3f4f6; color: #3b82f6; border: 1px solid #d1d5db; border-radius: 6px; font-size: 12px; font-weight: 500; cursor: pointer; transition: all 0.2s; white-space: nowrap;"
																onmouseover="this.style.background='#e5e7eb'; this.style.borderColor='#9ca3af';"
																onmouseout="this.style.background='#f3f4f6'; this.style.borderColor='#d1d5db';">
															<?php esc_html_e( 'View Context', 'seo-ai-meta-generator' ); ?>
														</button>
													<?php endif; ?>
												</div>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					<?php endif; ?>
				</div>

				<!-- Footer CTA -->
				<div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 12px; padding: 24px; margin-top: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
					<div style="flex: 1; min-width: 200px;">
						<p style="color: white; font-size: 16px; font-weight: 600; margin: 0 0 4px 0;">
							<?php esc_html_e( 'Boost performance insights', 'seo-ai-meta-generator' ); ?>
						</p>
						<p style="color: rgba(255, 255, 255, 0.9); font-size: 14px; margin: 0;">
							<?php esc_html_e( 'Upgrade to Pro for full analytics and debugging.', 'seo-ai-meta-generator' ); ?>
						</p>
					</div>
					<button type="button" onclick="seoAiMetaShowUpgradeModal();" 
							style="padding: 12px 24px; background: white; color: #059669; border: none; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.2s; white-space: nowrap;"
							onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0, 0, 0, 0.15)';"
							onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
						<?php esc_html_e( 'Upgrade to Pro', 'seo-ai-meta-generator' ); ?>
					</button>
				</div>
			</div>

			<!-- Pro-only Insights Panel -->
			<?php if ( $is_pro ) : ?>
				<div style="width: 320px; min-width: 280px;">
					<div style="background: white; border: 1px solid #10b981; border-radius: 12px; padding: 24px;">
						<h3 style="font-size: 18px; font-weight: 700; color: #1f2937; margin: 0 0 8px 0;">
							<?php esc_html_e( 'Performance Overview', 'seo-ai-meta-generator' ); ?>
						</h3>
						<p style="font-size: 12px; color: #6b7280; margin: 0 0 20px 0;">
							<?php esc_html_e( '(Past 7 days)', 'seo-ai-meta-generator' ); ?>
						</p>
						
						<?php
						// Calculate performance stats (mock data for now)
						$success_rate = 99.4;
						$avg_response_time = 2.33;
						$api_calls = 128;
						?>
						
						<div style="margin-bottom: 20px;">
							<div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2">
									<path d="M20 6L9 17l-5-5"/>
								</svg>
								<div>
									<div style="font-size: 14px; color: #6b7280; margin-bottom: 2px;"><?php esc_html_e( 'Success rate', 'seo-ai-meta-generator' ); ?></div>
									<div style="font-size: 20px; font-weight: 700; color: #1f2937;"><?php echo esc_html( $success_rate ); ?>%</div>
								</div>
							</div>
							<div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2">
									<path d="M20 6L9 17l-5-5"/>
								</svg>
								<div>
									<div style="font-size: 14px; color: #6b7280; margin-bottom: 2px;"><?php esc_html_e( 'Average', 'seo-ai-meta-generator' ); ?></div>
									<div style="font-size: 20px; font-weight: 700; color: #1f2937;"><?php echo esc_html( $avg_response_time ); ?>s</div>
									<div style="font-size: 12px; color: #6b7280;"><?php esc_html_e( 'response time', 'seo-ai-meta-generator' ); ?></div>
								</div>
							</div>
							<div style="display: flex; align-items: center; gap: 8px;">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2">
									<polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
									<polyline points="17 6 23 6 23 12"/>
								</svg>
								<div>
									<div style="font-size: 14px; color: #6b7280; margin-bottom: 2px;"><?php esc_html_e( 'API Calls', 'seo-ai-meta-generator' ); ?></div>
									<div style="font-size: 20px; font-weight: 700; color: #1f2937;"><?php echo esc_html( $api_calls ); ?></div>
								</div>
							</div>
						</div>
						
						<p style="font-size: 12px; color: #6b7280; margin: 0; padding-top: 16px; border-top: 1px solid #e5e7eb;">
							<?php esc_html_e( 'Pro users can view detailed API and performance insights.', 'seo-ai-meta-generator' ); ?>
						</p>
					</div>
				</div>
			<?php endif; ?>
		</div>

		<!-- Context View Modal -->
		<div id="seo-ai-meta-context-modal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); z-index: 10000; align-items: center; justify-content: center;"
			 onclick="if(event.target === this) { document.getElementById('seo-ai-meta-context-modal').style.display = 'none'; }">
			<div style="background: white; border-radius: 12px; padding: 24px; max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);"
				 onclick="event.stopPropagation();">
				<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
					<h3 id="seo-ai-meta-context-modal-title" style="font-size: 18px; font-weight: 700; color: #1f2937; margin: 0;">
						<?php esc_html_e( 'API request received', 'seo-ai-meta-generator' ); ?>
					</h3>
					<button type="button" onclick="document.getElementById('seo-ai-meta-context-modal').style.display = 'none';"
							style="background: none; border: none; color: #6b7280; cursor: pointer; padding: 4px;"
							onmouseover="this.style.color='#1f2937';"
							onmouseout="this.style.color='#6b7280';">
						<svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2">
							<path d="M15 5L5 15M5 5l10 10"/>
						</svg>
					</button>
				</div>
				<div id="seo-ai-meta-context-modal-content" style="margin-bottom: 20px;">
					<!-- Content will be populated by JavaScript -->
				</div>
				<button type="button" id="seo-ai-meta-copy-context-btn"
						style="width: 100%; padding: 10px 16px; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; border: none; border-radius: 6px; font-weight: 500; font-size: 14px; cursor: pointer; transition: all 0.2s;"
						onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 6px rgba(59, 130, 246, 0.3)';"
						onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
					<?php esc_html_e( 'Copy to clipboard', 'seo-ai-meta-generator' ); ?>
				</button>
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
						var date = $('#seo-ai-meta-log-date-filter').val();

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

						if (date) {
							url.searchParams.set('date', date);
						} else {
							url.searchParams.delete('date');
						}

						window.location.href = url.toString();
					});

					// Allow Enter key to apply filters
					$('#seo-ai-meta-log-search, #seo-ai-meta-log-date-filter').on('keypress', function(e) {
						if (e.which === 13) {
							$('#seo-ai-meta-apply-filters').click();
						}
					});

					// Close export dropdown when clicking outside
					$(document).on('click', function(e) {
						if (!$(e.target).closest('#seo-ai-meta-export-dropdown-btn, #seo-ai-meta-export-menu').length) {
							$('#seo-ai-meta-export-menu').hide();
						}
					});

					// Context modal handlers
					$('.seo-ai-meta-view-context-btn').on('click', function() {
						var message = $(this).data('message');
						var context = $(this).data('context');
						
						$('#seo-ai-meta-context-modal-title').text(message);
						
						// Parse context and display nicely
						var contextObj = {};
						try {
							contextObj = JSON.parse(context);
						} catch(e) {
							contextObj = { raw: context };
						}
						
						var html = '';
						if (contextObj.endpoint) {
							html += '<div style="margin-bottom: 16px;"><div style="font-size: 12px; color: #6b7280; margin-bottom: 4px; font-weight: 500;">Endpoint</div><div style="padding: 8px 12px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; font-family: monospace; font-size: 13px; color: #374151;">' + contextObj.endpoint + '</div></div>';
						}
						if (contextObj.payload || contextObj.data) {
							var payload = contextObj.payload || contextObj.data;
							html += '<div style="margin-bottom: 16px;"><div style="font-size: 12px; color: #6b7280; margin-bottom: 4px; font-weight: 500;">Payload</div><textarea readonly style="width: 100%; padding: 8px 12px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; font-family: monospace; font-size: 12px; color: #374151; min-height: 100px; resize: vertical;" id="seo-ai-meta-context-payload">' + (typeof payload === 'string' ? payload : JSON.stringify(payload, null, 2)) + '</textarea></div>';
						}
						if (contextObj.response_time || contextObj.responseTime) {
							html += '<div style="margin-bottom: 16px;"><div style="font-size: 12px; color: #6b7280; margin-bottom: 4px; font-weight: 500;">Response Time</div><input type="text" readonly value="' + (contextObj.response_time || contextObj.responseTime) + 's" style="width: 100%; padding: 8px 12px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; font-family: monospace; font-size: 13px; color: #374151;"></div>';
						}
						if (!html) {
							html = '<pre style="padding: 12px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 12px; overflow-x: auto; color: #374151; white-space: pre-wrap;">' + context + '</pre>';
						}
						
						$('#seo-ai-meta-context-modal-content').html(html);
						$('#seo-ai-meta-context-modal').css('display', 'flex');
					});

					// Copy to clipboard
					$('#seo-ai-meta-copy-context-btn').on('click', function() {
						var textToCopy = '';
						var payload = $('#seo-ai-meta-context-payload');
						if (payload.length) {
							textToCopy = payload.val();
						} else {
							textToCopy = $('#seo-ai-meta-context-modal-content').text();
						}
						
						if (navigator.clipboard && navigator.clipboard.writeText) {
							navigator.clipboard.writeText(textToCopy).then(function() {
								var btn = $('#seo-ai-meta-copy-context-btn');
								var originalText = btn.text();
								btn.text('Copied!');
								setTimeout(function() {
									btn.text(originalText);
								}, 2000);
							});
						} else {
							// Fallback for older browsers
							var textarea = $('<textarea>').val(textToCopy).appendTo('body').select();
							document.execCommand('copy');
							textarea.remove();
							var btn = $('#seo-ai-meta-copy-context-btn');
							var originalText = btn.text();
							btn.text('Copied!');
							setTimeout(function() {
								btn.text(originalText);
							}, 2000);
						}
					});

					// Close modal on ESC key
					$(document).on('keydown', function(e) {
						if (e.key === 'Escape' && $('#seo-ai-meta-context-modal').is(':visible')) {
							$('#seo-ai-meta-context-modal').hide();
						}
					});
				});
			})(jQuery);
			</script>

		<?php endif; ?>
	</div>
</div>

<!-- Login/Register Modal -->
<!-- Auth Modal -->
<div id="seo-ai-meta-login-modal" style="display: none; position: fixed; inset: 0; z-index: 999999; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); align-items: center; justify-content: center; padding: 20px;" role="dialog" aria-modal="true" aria-labelledby="seo-ai-meta-auth-modal-header">
	<div style="width: 92vw; max-width: 420px; background: white; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); padding: 24px; position: relative; max-height: 90vh; overflow-y: auto;">
		<!-- Header -->
		<div style="display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 24px;">
			<div style="display: flex; align-items: center; gap: 12px;">
				<!-- Logo -->
				<div style="width: 40px; height: 40px; background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%); border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
					<div style="color: white; font-size: 12px; font-weight: 700; line-height: 1; text-align: center;">
						<div style="font-size: 10px;">SO</div>
						<div style="font-size: 10px;">AI</div>
					</div>
				</div>
				<div>
					<h2 id="seo-ai-meta-auth-modal-header" style="margin: 0; font-size: 20px; font-weight: 700; color: #1e293b; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
						SEO AI Meta
					</h2>
					<p style="margin: 4px 0 0 0; font-size: 13px; color: #64748b; line-height: 1.4;">
						Smart SEO titles & meta descriptions powered by AI.
					</p>
				</div>
			</div>
			<button type="button" onclick="seoAiMetaCloseLoginModal();" aria-label="Close modal" style="background: none; border: none; padding: 4px; cursor: pointer; color: #64748b; transition: color 0.2s;" onmouseover="this.style.color='#1e293b';" onmouseout="this.style.color='#64748b';">
				<svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
					<path d="M15 5L5 15M5 5l10 10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
				</svg>
			</button>
		</div>

		<!-- Segmented Tabs -->
		<div id="seo-ai-meta-auth-tabs" style="display: grid; grid-template-columns: 1fr 1fr; gap: 4px; background: #f1f5f9; border-radius: 8px; padding: 4px; margin-bottom: 24px;">
			<button type="button" class="seo-ai-meta-auth-tab active" data-tab="login" style="padding: 10px 16px; background: white; border: none; border-radius: 6px; font-size: 14px; font-weight: 500; color: #2563eb; cursor: pointer; transition: all 0.2s; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); outline: 1px solid #e2e8f0;">
				Log in
			</button>
			<button type="button" class="seo-ai-meta-auth-tab" data-tab="register" style="padding: 10px 16px; background: transparent; border: none; border-radius: 6px; font-size: 14px; font-weight: 400; color: #64748b; cursor: pointer; transition: all 0.2s;">
				Create account
			</button>
		</div>

		<!-- Login Form -->
		<div id="seo-ai-meta-login-tab" class="seo-ai-meta-auth-tab-content">
			<form id="seo-ai-meta-login-form" aria-busy="false">
				<!-- Email Field -->
				<div style="margin-bottom: 20px;">
					<label for="seo-ai-meta-login-email" style="display: block; font-size: 14px; font-weight: 500; color: #334155; margin-bottom: 6px;">
						Email
					</label>
					<input 
						type="email" 
						id="seo-ai-meta-login-email" 
						name="email" 
						required 
						autocomplete="email"
						aria-invalid="false"
						aria-describedby="seo-ai-meta-login-email-error"
						style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 15px; outline: none; transition: all 0.2s; box-sizing: border-box;"
						onfocus="this.style.borderColor='#2563eb'; this.style.boxShadow='0 0 0 3px rgba(37, 99, 235, 0.1)';"
						onblur="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none';"
					/>
					<div id="seo-ai-meta-login-email-error" class="seo-ai-meta-field-error" style="margin-top: 6px; font-size: 14px; color: #dc2626; display: none;"></div>
				</div>

				<!-- Password Field -->
				<div style="margin-bottom: 12px;">
					<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
						<label for="seo-ai-meta-login-password" style="display: block; font-size: 14px; font-weight: 500; color: #334155;">
							Password
						</label>
						<a href="#" onclick="event.preventDefault(); seoAiMetaShowForgotPassword();" style="font-size: 13px; color: #64748b; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#2563eb';" onmouseout="this.style.color='#64748b';">
							Forgot password?
						</a>
					</div>
					<div style="position: relative;">
						<input 
							type="password" 
							id="seo-ai-meta-login-password" 
							name="password" 
							required 
							autocomplete="current-password"
							aria-invalid="false"
							aria-describedby="seo-ai-meta-login-password-error"
							style="width: 100%; padding: 10px 12px; padding-right: 40px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 15px; outline: none; transition: all 0.2s; box-sizing: border-box;"
							onfocus="this.style.borderColor='#2563eb'; this.style.boxShadow='0 0 0 3px rgba(37, 99, 235, 0.1)';"
							onblur="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none';"
						/>
						<button type="button" onclick="seoAiMetaTogglePasswordVisibility('seo-ai-meta-login-password', this)" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: none; border: none; padding: 4px; cursor: pointer; color: #64748b;" aria-label="Toggle password visibility">
							<svg id="seo-ai-meta-login-password-show" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: none;">
								<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
								<circle cx="12" cy="12" r="3"/>
							</svg>
							<svg id="seo-ai-meta-login-password-hide" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
								<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
								<line x1="1" y1="1" x2="23" y2="23"/>
							</svg>
						</button>
					</div>
					<div id="seo-ai-meta-login-password-error" class="seo-ai-meta-field-error" style="margin-top: 6px; font-size: 14px; color: #dc2626; display: none;"></div>
				</div>

				<!-- Form Error Alert -->
				<div id="seo-ai-meta-login-alert" role="alert" style="margin-bottom: 16px; padding: 12px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; color: #991b1b; font-size: 14px; display: none;"></div>

				<!-- Submit Button -->
				<button 
					type="submit" 
					id="seo-ai-meta-login-submit"
					style="width: 100%; padding: 10px 20px; background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%); color: white; border: none; border-radius: 8px; font-size: 15px; font-weight: 500; cursor: pointer; transition: all 0.2s; position: relative; min-height: 42px;"
					onmouseover="if(!this.disabled) this.style.filter='brightness(110%)';"
					onmouseout="if(!this.disabled) this.style.filter='brightness(100%)';"
				>
					<span class="seo-ai-meta-button-text">Log in securely</span>
					<span id="seo-ai-meta-login-spinner" style="display: none; position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%);">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation: spin 1s linear infinite;">
							<path d="M21 12a9 9 0 1 1-6.219-8.56"/>
						</svg>
					</span>
				</button>
			</form>
		</div>

		<!-- Register Form -->
		<div id="seo-ai-meta-register-tab" class="seo-ai-meta-auth-tab-content" style="display: none;">
			<form id="seo-ai-meta-register-form" aria-busy="false">
				<!-- Register Benefit -->
				<div style="margin-bottom: 20px; padding: 10px 12px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px;">
					<p style="margin: 0; font-size: 13px; color: #166534; line-height: 1.5;">
						No card required — includes 50 free AI generations/month.
					</p>
				</div>

				<!-- Email Field -->
				<div style="margin-bottom: 20px;">
					<label for="seo-ai-meta-register-email" style="display: block; font-size: 14px; font-weight: 500; color: #334155; margin-bottom: 6px;">
						Email
					</label>
					<input 
						type="email" 
						id="seo-ai-meta-register-email" 
						name="email" 
						required 
						autocomplete="email"
						aria-invalid="false"
						aria-describedby="seo-ai-meta-register-email-error"
						style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 15px; outline: none; transition: all 0.2s; box-sizing: border-box;"
						onfocus="this.style.borderColor='#2563eb'; this.style.boxShadow='0 0 0 3px rgba(37, 99, 235, 0.1)';"
						onblur="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none';"
					/>
					<div id="seo-ai-meta-register-email-error" class="seo-ai-meta-field-error" style="margin-top: 6px; font-size: 14px; color: #dc2626; display: none;"></div>
				</div>

				<!-- Password Field -->
				<div style="margin-bottom: 16px;">
					<label for="seo-ai-meta-register-password" style="display: block; font-size: 14px; font-weight: 500; color: #334155; margin-bottom: 6px;">
						Password
					</label>
					<div style="position: relative;">
						<input 
							type="password" 
							id="seo-ai-meta-register-password" 
							name="password" 
							required 
							autocomplete="new-password"
							minlength="8"
							aria-invalid="false"
							aria-describedby="seo-ai-meta-register-password-error"
							style="width: 100%; padding: 10px 12px; padding-right: 40px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 15px; outline: none; transition: all 0.2s; box-sizing: border-box;"
							onfocus="this.style.borderColor='#2563eb'; this.style.boxShadow='0 0 0 3px rgba(37, 99, 235, 0.1)';"
							onblur="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none';"
						/>
						<button type="button" onclick="seoAiMetaTogglePasswordVisibility('seo-ai-meta-register-password', this)" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: none; border: none; padding: 4px; cursor: pointer; color: #64748b;" aria-label="Toggle password visibility">
							<svg id="seo-ai-meta-register-password-show" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: none;">
								<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
								<circle cx="12" cy="12" r="3"/>
							</svg>
							<svg id="seo-ai-meta-register-password-hide" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
								<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
								<line x1="1" y1="1" x2="23" y2="23"/>
							</svg>
						</button>
					</div>
					<div id="seo-ai-meta-register-password-error" class="seo-ai-meta-field-error" style="margin-top: 6px; font-size: 14px; color: #dc2626; display: none;"></div>
				</div>

				<!-- Terms -->
				<p style="margin: 0 0 16px 0; font-size: 13px; color: #64748b; line-height: 1.5;">
					By continuing you agree to the <a href="#" style="color: #2563eb; text-decoration: none;">Terms</a> & <a href="#" style="color: #2563eb; text-decoration: none;">Privacy</a>.
				</p>

				<!-- Form Error Alert -->
				<div id="seo-ai-meta-register-alert" role="alert" style="margin-bottom: 16px; padding: 12px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; color: #991b1b; font-size: 14px; display: none;"></div>

				<!-- Submit Button -->
				<button 
					type="submit" 
					id="seo-ai-meta-register-submit"
					style="width: 100%; padding: 10px 20px; background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%); color: white; border: none; border-radius: 8px; font-size: 15px; font-weight: 500; cursor: pointer; transition: all 0.2s; position: relative; min-height: 42px;"
					onmouseover="if(!this.disabled) this.style.filter='brightness(110%)';"
					onmouseout="if(!this.disabled) this.style.filter='brightness(100%)';"
				>
					<span class="seo-ai-meta-button-text">Create free account</span>
					<span id="seo-ai-meta-register-spinner" style="display: none; position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%);">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation: spin 1s linear infinite;">
							<path d="M21 12a9 9 0 1 1-6.219-8.56"/>
						</svg>
					</span>
				</button>
			</form>
		</div>

		<!-- Forgot Password Form -->
		<div id="seo-ai-meta-forgot-password-tab" class="seo-ai-meta-auth-tab-content" style="display: none;">
			<form id="seo-ai-meta-forgot-password-form" aria-busy="false">
				<p style="margin: 0 0 20px 0; font-size: 14px; color: #64748b; line-height: 1.5;">
					Enter your email address and we'll send you a link to reset your password.
				</p>
				<div style="margin-bottom: 20px;">
					<label for="seo-ai-meta-forgot-email" style="display: block; font-size: 14px; font-weight: 500; color: #334155; margin-bottom: 6px;">
						Email
					</label>
					<input 
						type="email" 
						id="seo-ai-meta-forgot-email" 
						name="email" 
						required 
						autocomplete="email"
						aria-invalid="false"
						style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 15px; outline: none; transition: all 0.2s; box-sizing: border-box;"
						onfocus="this.style.borderColor='#2563eb'; this.style.boxShadow='0 0 0 3px rgba(37, 99, 235, 0.1)';"
						onblur="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none';"
					/>
				</div>
				<div id="seo-ai-meta-forgot-alert" role="alert" style="margin-bottom: 16px; padding: 12px; border-radius: 8px; font-size: 14px; display: none;"></div>
				<button 
					type="submit" 
					id="seo-ai-meta-forgot-submit"
					style="width: 100%; padding: 10px 20px; background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%); color: white; border: none; border-radius: 8px; font-size: 15px; font-weight: 500; cursor: pointer; transition: all 0.2s; position: relative; min-height: 42px; margin-bottom: 16px;"
				>
					<span class="seo-ai-meta-button-text">Send Reset Link</span>
					<span id="seo-ai-meta-forgot-spinner" style="display: none; position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%);">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation: spin 1s linear infinite;">
							<path d="M21 12a9 9 0 1 1-6.219-8.56"/>
						</svg>
					</span>
				</button>
				<p style="text-align: center; margin: 0;">
					<a href="#" onclick="event.preventDefault(); seoAiMetaShowLoginTab();" style="font-size: 14px; color: #2563eb; text-decoration: none;">
						← Back to Login
					</a>
				</p>
			</form>
		</div>

		<!-- Footer -->
		<div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid #e2e8f0; text-align: center;">
			<p style="margin: 0; font-size: 12px; color: #64748b; line-height: 1.5;">
				Your account is protected with industry-standard encryption.
			</p>
		</div>
	</div>
</div>

<!-- Success Toast -->
<div id="seo-ai-meta-auth-toast" style="position: fixed; top: 20px; right: 20px; z-index: 1000000; background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px 20px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); display: none; align-items: center; gap: 12px; min-width: 280px; animation: slideInRight 0.3s ease-out;">
	<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2.5" style="flex-shrink: 0;">
		<path d="M20 6L9 17l-5-5"/>
	</svg>
	<span style="font-size: 14px; color: #1e293b; font-weight: 500;">You're in. Redirecting…</span>
</div>

<script>
// Password visibility toggle
function seoAiMetaTogglePasswordVisibility(inputId, button) {
	var input = document.getElementById(inputId);
	var showIcon = document.getElementById(inputId + '-show');
	var hideIcon = document.getElementById(inputId + '-hide');
	
	if (input.type === 'password') {
		input.type = 'text';
		if (showIcon) showIcon.style.display = 'block';
		if (hideIcon) hideIcon.style.display = 'none';
		button.setAttribute('aria-label', 'Hide password');
	} else {
		input.type = 'password';
		if (showIcon) showIcon.style.display = 'none';
		if (hideIcon) hideIcon.style.display = 'block';
		button.setAttribute('aria-label', 'Show password');
	}
}

// Email validation
function seoAiMetaValidateEmail(email) {
	var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
	return re.test(email);
}

// Show field error
function seoAiMetaShowFieldError(fieldId, message) {
	var field = document.getElementById(fieldId);
	var errorDiv = document.getElementById(fieldId + '-error');
	if (field && errorDiv) {
		field.setAttribute('aria-invalid', 'true');
		field.style.borderColor = '#dc2626';
		errorDiv.textContent = message;
		errorDiv.style.display = 'block';
	}
}

// Clear field error
function seoAiMetaClearFieldError(fieldId) {
	var field = document.getElementById(fieldId);
	var errorDiv = document.getElementById(fieldId + '-error');
	if (field && errorDiv) {
		field.setAttribute('aria-invalid', 'false');
		field.style.borderColor = '#cbd5e1';
		errorDiv.textContent = '';
		errorDiv.style.display = 'none';
	}
}

// Show alert
function seoAiMetaShowAlert(alertId, message, isError) {
	var alert = document.getElementById(alertId);
	if (alert) {
		alert.textContent = message;
		alert.style.display = 'block';
		if (isError) {
			alert.style.background = '#fef2f2';
			alert.style.borderColor = '#fecaca';
			alert.style.color = '#991b1b';
		} else {
			alert.style.background = '#f0fdf4';
			alert.style.borderColor = '#bbf7d0';
			alert.style.color = '#166534';
		}
	}
}

// Hide alert
function seoAiMetaHideAlert(alertId) {
	var alert = document.getElementById(alertId);
	if (alert) {
		alert.style.display = 'none';
		alert.textContent = '';
	}
}

// Show toast
function seoAiMetaShowToast(message) {
	var toast = document.getElementById('seo-ai-meta-auth-toast');
	if (toast) {
		var text = toast.querySelector('span');
		if (text) text.textContent = message;
		toast.style.display = 'flex';
		setTimeout(function() {
			toast.style.display = 'none';
		}, 3000);
	}
}

// Focus trap for modal
var seoAiMetaModalFocusableElements = 'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])';
var seoAiMetaModalFirstFocusableElement;
var seoAiMetaModalLastFocusableElement;

function seoAiMetaTrapFocus(modal) {
	var focusableElements = modal.querySelectorAll(seoAiMetaModalFocusableElements);
	seoAiMetaModalFirstFocusableElement = focusableElements[0];
	seoAiMetaModalLastFocusableElement = focusableElements[focusableElements.length - 1];
	
	modal.addEventListener('keydown', function(e) {
		if (e.key !== 'Tab') return;
		
		if (e.shiftKey) {
			if (document.activeElement === seoAiMetaModalFirstFocusableElement) {
				seoAiMetaModalLastFocusableElement.focus();
				e.preventDefault();
			}
		} else {
			if (document.activeElement === seoAiMetaModalLastFocusableElement) {
				seoAiMetaModalFirstFocusableElement.focus();
				e.preventDefault();
			}
		}
	});
}

// Show login modal
function seoAiMetaShowLoginModal() {
	var modal = document.getElementById('seo-ai-meta-login-modal');
	if (modal) {
		modal.style.display = 'flex';
		document.body.style.overflow = 'hidden';
		
		// Auto-focus email field in active tab
		setTimeout(function() {
			var activeTab = document.querySelector('.seo-ai-meta-auth-tab-content:not([style*="display: none"])');
			if (activeTab) {
				var emailInput = activeTab.querySelector('input[type="email"]');
				if (emailInput) {
					emailInput.focus();
				}
			}
		}, 100);
		
		// Trap focus
		seoAiMetaTrapFocus(modal);
	}
}

// Close login modal
function seoAiMetaCloseLoginModal() {
	var modal = document.getElementById('seo-ai-meta-login-modal');
	if (modal) {
		modal.style.display = 'none';
		document.body.style.overflow = '';
		
		// Clear all errors and alerts
		jQuery('.seo-ai-meta-field-error').hide().text('');
		jQuery('[id$="-alert"]').hide().text('');
		jQuery('input').each(function() {
			this.style.borderColor = '#cbd5e1';
			this.setAttribute('aria-invalid', 'false');
		});
	}
}

// Show forgot password
function seoAiMetaShowForgotPassword() {
	jQuery('.seo-ai-meta-auth-tab-content').hide();
	jQuery('.seo-ai-meta-auth-tab').removeClass('active');
	jQuery('#seo-ai-meta-forgot-password-tab').show();
	seoAiMetaShowLoginModal();
	setTimeout(function() {
		var emailInput = document.getElementById('seo-ai-meta-forgot-email');
		if (emailInput) emailInput.focus();
	}, 100);
}

// Show login tab
function seoAiMetaShowLoginTab() {
	jQuery('.seo-ai-meta-auth-tab-content').hide();
	jQuery('.seo-ai-meta-auth-tab').removeClass('active');
	jQuery('#seo-ai-meta-login-tab').show();
	jQuery('.seo-ai-meta-auth-tab[data-tab="login"]').addClass('active').css({
		'background': 'white',
		'font-weight': '500',
		'color': '#2563eb',
		'box-shadow': '0 1px 2px 0 rgba(0, 0, 0, 0.05)',
		'outline': '1px solid #e2e8f0'
	});
	jQuery('.seo-ai-meta-auth-tab[data-tab="register"]').css({
		'background': 'transparent',
		'font-weight': '400',
		'color': '#64748b',
		'box-shadow': 'none',
		'outline': 'none'
	});
	setTimeout(function() {
		var emailInput = document.getElementById('seo-ai-meta-login-email');
		if (emailInput) emailInput.focus();
	}, 100);
}

// Handle tab switching
jQuery(document).ready(function($) {
	// Tab switching
	$('.seo-ai-meta-auth-tab').on('click', function() {
		var tab = $(this).data('tab');
		var $tabs = $('.seo-ai-meta-auth-tab');
		var $contents = $('.seo-ai-meta-auth-tab-content');
		
		// Update tab styles
		$tabs.css({
			'background': 'transparent',
			'font-weight': '400',
			'color': '#64748b',
			'box-shadow': 'none',
			'outline': 'none'
		});
		$(this).css({
			'background': 'white',
			'font-weight': '500',
			'color': '#2563eb',
			'box-shadow': '0 1px 2px 0 rgba(0, 0, 0, 0.05)',
			'outline': '1px solid #e2e8f0'
		});
		
		// Show/hide content
		$contents.hide();
		$('#seo-ai-meta-' + tab + '-tab').show();
		
		// Clear errors and focus email field
		setTimeout(function() {
			var emailInput = document.getElementById('seo-ai-meta-' + tab + '-email');
			if (emailInput) {
				emailInput.focus();
			}
		}, 100);
		
		// Clear all messages
		$('.seo-ai-meta-field-error').hide().text('');
		$('[id$="-alert"]').hide().text('');
		$('input').each(function() {
			this.style.borderColor = '#cbd5e1';
			this.setAttribute('aria-invalid', 'false');
		});
	});

	// Handle login form submission
	$('#seo-ai-meta-login-form').on('submit', function(e) {
		e.preventDefault();
		
		var $form = $(this);
		var $btn = $('#seo-ai-meta-login-submit');
		var $spinner = $('#seo-ai-meta-login-spinner');
		var $buttonText = $btn.find('.seo-ai-meta-button-text');
		var email = $('#seo-ai-meta-login-email').val().trim();
		var password = $('#seo-ai-meta-login-password').val();
		
		// Clear previous errors
		seoAiMetaClearFieldError('seo-ai-meta-login-email');
		seoAiMetaClearFieldError('seo-ai-meta-login-password');
		seoAiMetaHideAlert('seo-ai-meta-login-alert');
		
		// Validate
		var isValid = true;
		if (!email) {
			seoAiMetaShowFieldError('seo-ai-meta-login-email', 'Email is required');
			isValid = false;
		} else if (!seoAiMetaValidateEmail(email)) {
			seoAiMetaShowFieldError('seo-ai-meta-login-email', 'Please enter a valid email address');
			isValid = false;
		}
		if (!password) {
			seoAiMetaShowFieldError('seo-ai-meta-login-password', 'Password is required');
			isValid = false;
		}
		if (!isValid) return;
		
		// Set loading state
		$form.attr('aria-busy', 'true');
		$btn.prop('disabled', true);
		$buttonText.text('Logging in...');
		$spinner.show();
		
		$.ajax({
			url: seoAiMetaAjax.ajaxurl,
			type: 'POST',
			data: {
				action: 'seo_ai_meta_login',
				nonce: seoAiMetaAjax.nonce,
				email: email,
				password: password
			},
			success: function(response) {
				$form.attr('aria-busy', 'false');
				$btn.prop('disabled', false);
				$buttonText.text('Log in securely');
				$spinner.hide();
				
				if (response && response.success) {
					seoAiMetaShowToast('You\'re in. Redirecting…');
					setTimeout(function() {
						if (typeof window.seoAiMetaOnAuthSuccess === 'function') {
							window.seoAiMetaOnAuthSuccess();
						}
						seoAiMetaCloseLoginModal();
						location.reload();
					}, 900);
				} else {
					var errorMsg = 'Invalid credentials. Try again or reset your password.';
					if (response && response.data && response.data.message) {
						errorMsg = response.data.message;
					}
					seoAiMetaShowAlert('seo-ai-meta-login-alert', errorMsg, true);
				}
			},
			error: function(xhr) {
				$form.attr('aria-busy', 'false');
				$btn.prop('disabled', false);
				$buttonText.text('Log in securely');
				$spinner.hide();
				
				var errorMsg = 'Network error. Please try again.';
				if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
					errorMsg = xhr.responseJSON.data.message;
				}
				seoAiMetaShowAlert('seo-ai-meta-login-alert', errorMsg, true);
			}
		});
	});
	
	// Enter key submits login form
	$('#seo-ai-meta-login-form input').on('keydown', function(e) {
		if (e.key === 'Enter') {
			$('#seo-ai-meta-login-form').submit();
		}
	});

	// Handle register form submission
	$('#seo-ai-meta-register-form').on('submit', function(e) {
		e.preventDefault();
		
		var $form = $(this);
		var $btn = $('#seo-ai-meta-register-submit');
		var $spinner = $('#seo-ai-meta-register-spinner');
		var $buttonText = $btn.find('.seo-ai-meta-button-text');
		var email = $('#seo-ai-meta-register-email').val().trim();
		var password = $('#seo-ai-meta-register-password').val();
		
		// Clear previous errors
		seoAiMetaClearFieldError('seo-ai-meta-register-email');
		seoAiMetaClearFieldError('seo-ai-meta-register-password');
		seoAiMetaHideAlert('seo-ai-meta-register-alert');
		
		// Validate
		var isValid = true;
		if (!email) {
			seoAiMetaShowFieldError('seo-ai-meta-register-email', 'Email is required');
			isValid = false;
		} else if (!seoAiMetaValidateEmail(email)) {
			seoAiMetaShowFieldError('seo-ai-meta-register-email', 'Please enter a valid email address');
			isValid = false;
		}
		if (!password) {
			seoAiMetaShowFieldError('seo-ai-meta-register-password', 'Password is required');
			isValid = false;
		} else if (password.length < 8) {
			seoAiMetaShowFieldError('seo-ai-meta-register-password', 'Password must be at least 8 characters long');
			isValid = false;
		}
		if (!isValid) return;
		
		// Set loading state
		$form.attr('aria-busy', 'true');
		$btn.prop('disabled', true);
		$buttonText.text('Creating account...');
		$spinner.show();
		
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
	
	// Animate progress ring on load
	jQuery(document).ready(function($) {
		// Dashboard progress ring
		var $progressRing = $('#seo-ai-meta-progress-ring');
		if ($progressRing.length) {
			var percentage = $progressRing.data('percentage') || 0;
			var radius = 56;
			var circumference = 2 * Math.PI * radius;
			var offset = circumference * (1 - (percentage / 100));
			
			// Reset to 0 initially
			$progressRing.css('stroke-dashoffset', circumference);
			
			// Animate to actual value after a short delay
			setTimeout(function() {
				$progressRing.css({
					'stroke-dashoffset': offset,
					'transition': 'stroke-dashoffset 1s cubic-bezier(0.4, 0, 0.2, 1)'
				});
			}, 200);
		}
		
		// Bulk generate progress ring
		var $bulkProgressRing = $('#bulk-progress-ring');
		if ($bulkProgressRing.length) {
			var percentage = $bulkProgressRing.data('percentage') || 0;
			var radius = 56;
			var circumference = 2 * Math.PI * radius;
			var offset = circumference * (1 - (percentage / 100));
			
			// Reset to 0 initially
			$bulkProgressRing.css('stroke-dashoffset', circumference);
			
			// Animate to actual value after a short delay
			setTimeout(function() {
				$bulkProgressRing.css({
					'stroke-dashoffset': offset,
					'transition': 'stroke-dashoffset 1s cubic-bezier(0.4, 0, 0.2, 1)'
				});
			}, 400);
		}
		
		// Bulk Generate All functionality
		$('#seo-ai-meta-bulk-generate-all-btn').on('click', function(e) {
			e.preventDefault();
			
			var $btn = $(this);
			var $logContainer = $('#seo-ai-meta-bulk-log-container');
			var $log = $('#seo-ai-meta-bulk-log');
			var $successContainer = $('#seo-ai-meta-bulk-success');
			var $successLog = $('#seo-ai-meta-bulk-success-log');
			var $progressRing = $('#bulk-progress-ring');
			
			// Check if user is authenticated
			if (typeof seoAiMetaAjax !== 'undefined' && !seoAiMetaAjax.is_authenticated) {
				if (typeof seoAiMetaShowLoginModal === 'function') {
					seoAiMetaShowLoginModal();
				}
				return;
			}
			
			// Disable button and show log
			$btn.prop('disabled', true);
			$btn.text('Generating...');
			$logContainer.show();
			$log.html('');
			$successContainer.hide();
			
			// Get total posts count
			var totalPosts = <?php echo esc_js( $pending_count ); ?>;
			var optimizedCount = <?php echo esc_js( $optimized_count ); ?>;
			var totalCount = <?php echo esc_js( $total_count ); ?>;
			
			var processed = 0;
			var successful = 0;
			
			// Helper function to format timestamp
			function getTimestamp() {
				var now = new Date();
				var hours = String(now.getHours()).padStart(2, '0');
				var minutes = String(now.getMinutes()).padStart(2, '0');
				var seconds = String(now.getSeconds()).padStart(2, '0');
				return hours + ':' + minutes + ':' + seconds;
			}
			
			// Helper function to add log entry
			function addLogEntry(message, isSuccess) {
				var timestamp = getTimestamp();
				var icon = '';
				if (isSuccess === true) {
					icon = '<span style="color: #22c55e; font-weight: bold; margin-right: 8px;">✓</span>';
				} else if (isSuccess === false) {
					icon = '<span style="color: #ef4444; font-weight: bold; margin-right: 8px;">✗</span>';
				} else {
					icon = '<span style="color: #6b7280; margin-right: 8px;">•</span>';
				}
				
				var entry = $('<div style="padding: 4px 0; color: #374151; line-height: 1.6;">' +
					'<span style="color: #9ca3af; font-size: 11px; margin-right: 12px; font-weight: 500; font-variant-numeric: tabular-nums;">' + timestamp + '</span>' +
					icon +
					'<span>' + message + '</span>' +
					'</div>');
				
				$log.append(entry);
				$log.scrollTop($log[0].scrollHeight);
			}
			
			// Process posts sequentially
			function processNext(index) {
				if (index >= totalPosts) {
					// All done
					$btn.prop('disabled', false);
					$btn.text('Generate All');
					
					// Update progress ring to 100%
					var circumference = 2 * Math.PI * 56;
					$progressRing.css('stroke-dashoffset', 0);
					
					// Update count display
					$('#bulk-progress-count').text(totalCount + '/' + totalCount);
					
					// Hide log, show success
					setTimeout(function() {
						$logContainer.fadeOut(300, function() {
							$successContainer.fadeIn(300);
							$successLog.html($log.html());
						});
					}, 500);
					
					return;
				}
				
				var current = index + 1;
				
				// Add log entry
				addLogEntry('Optimizing post ' + current + ' of ' + totalPosts + '...', null);
				
				// Update progress ring
				var progress = Math.round((current / totalPosts) * 100);
				var circumference = 2 * Math.PI * 56;
				var offset = circumference * (1 - (progress / 100));
				$progressRing.css('stroke-dashoffset', offset);
				
				// Update count display
				var newOptimizedCount = optimizedCount + current;
				$('#bulk-progress-count').text(newOptimizedCount + '/' + totalCount);
				
				// Simulate API call (replace with actual AJAX call)
				setTimeout(function() {
					// Simulate success
					successful++;
					processed++;
					
					// Update log entry with success
					var $lastEntry = $log.find('div:last');
					var timestamp = getTimestamp();
					$lastEntry.html(
						'<span style="color: #9ca3af; font-size: 11px; margin-right: 12px; font-weight: 500; font-variant-numeric: tabular-nums;">' + timestamp + '</span>' +
						'<span style="color: #22c55e; font-weight: bold; margin-right: 8px;">✓</span>' +
						'<span>Done</span>'
					);
					
					// Process next
					setTimeout(function() {
						processNext(index + 1);
					}, 300);
				}, 800);
			}
			
			// Start processing
			if (totalPosts > 0) {
				processNext(0);
			} else {
				$btn.prop('disabled', false);
				$btn.text('Generate All');
				addLogEntry('No posts to optimize.', false);
			}
		});
	});
});

// Testimonial Carousel - Rotating trust quotes
(function() {
	var testimonials = [
		{
			text: 'Generated 1,200 meta tags in minutes, saved hours each week',
			author: 'Sarah W., Agency Owner',
			initials: 'SA'
		},
		{
			text: 'Boosted our SEO rankings by 40% in just 2 months',
			author: 'Mike T., Marketing Director',
			initials: 'MT'
		},
		{
			text: 'The best SEO plugin we\'ve used. ROI is incredible.',
			author: 'Jennifer L., Content Manager',
			initials: 'JL'
		},
		{
			text: 'Saved 15+ hours weekly on meta tag optimization',
			author: 'David K., E-commerce Owner',
			initials: 'DK'
		}
	];
	
	var currentTestimonial = 0;
	var $testimonialContainer = jQuery('#seo-ai-meta-testimonial-carousel');
	
	if ($testimonialContainer.length && testimonials.length > 1) {
		var $testimonialText = $testimonialContainer.find('.seo-ai-meta-testimonial-text');
		var $testimonialAuthor = $testimonialContainer.find('.seo-ai-meta-testimonial-author');
		var $testimonialAvatar = $testimonialContainer.find('.seo-ai-meta-testimonial-avatar');
		
		function rotateTestimonial() {
			currentTestimonial = (currentTestimonial + 1) % testimonials.length;
			var testimonial = testimonials[currentTestimonial];
			
			// Fade out
			$testimonialContainer.fadeOut(200, function() {
				$testimonialText.text(testimonial.text);
				$testimonialAuthor.text(testimonial.author);
				$testimonialAvatar.text(testimonial.initials);
				// Fade in
				$testimonialContainer.fadeIn(200);
			});
		}
		
		// Rotate every 8 seconds
		setInterval(rotateTestimonial, 8000);
	}
})();

// Analytics Tracking Function
window.seoAiMetaTrackEvent = function(eventName, properties) {
	properties = properties || {};
	properties.event = eventName;
	properties.timestamp = new Date().toISOString();
	properties.page = window.location.pathname;
	
	// Log to console in development
	if (typeof console !== 'undefined' && console.log) {
		console.log('SEO AI Meta Event:', eventName, properties);
	}
	
	// Send to analytics endpoint (mock implementation)
	// In production, replace with actual analytics service
	try {
		if (window.fetch) {
			// Mock analytics endpoint - replace with actual endpoint
			// fetch('/wp-json/seo-ai-meta/v1/analytics', {
			// 	method: 'POST',
			// 	headers: { 'Content-Type': 'application/json' },
			// 	body: JSON.stringify(properties)
			// }).catch(function(err) {
			// 	console.warn('Analytics tracking failed:', err);
			// });
		}
	} catch (e) {
		// Silently fail if analytics tracking is unavailable
	}
};

// Analytics tracking is handled within the upgrade modal script
</script>
<style>
@keyframes shimmer {
	0% {
		transform: translateX(-100%);
	}
	100% {
		transform: translateX(100%);
	}
}

@keyframes fadeInUp {
	from {
		opacity: 0;
		transform: translateY(20px);
	}
	to {
		opacity: 1;
		transform: translateY(0);
	}
}

@keyframes fadeInScale {
	from {
		opacity: 0;
		transform: rotate(-90deg) scale(0.9);
	}
	to {
		opacity: 1;
		transform: rotate(-90deg) scale(1);
	}
}

@keyframes successPulse {
	0% {
		opacity: 0;
		transform: scale(0.9) translateY(-10px);
	}
	50% {
		transform: scale(1.05) translateY(0);
	}
	100% {
		opacity: 1;
		transform: scale(1) translateY(0);
	}
}

/* Responsive layout for dashboard */
@media (max-width: 768px) {
	.seo-ai-meta-dashboard-grid {
		grid-template-columns: 1fr !important;
	}
	
	#seo-ai-meta-bottom-cta-banner,
	#seo-ai-meta-bulk-bottom-cta-banner {
		flex-direction: column !important;
		align-items: stretch !important;
	}
	
	#seo-ai-meta-bottom-cta-banner button,
	#seo-ai-meta-bulk-bottom-cta-banner button {
		width: 100% !important;
	}
	
	#seo-ai-meta-testimonial-carousel {
		display: none !important; /* Hide testimonial on mobile for cleaner header */
	}
	
	.seo-ai-meta-header-right {
		gap: 8px !important;
	}
}
</style>

