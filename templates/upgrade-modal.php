<?php
/**
 * Upgrade Modal Template for SEO AI Meta Generator
 *
 * @package    SEO_AI_Meta
 * @subpackage SEO_AI_Meta/templates
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-api-client-v2.php';
require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-core.php';

$api_client = new SEO_AI_Meta_API_Client_V2();
$core = new SEO_AI_Meta_Core();

// Check if user is authenticated
$is_authenticated = $api_client->is_authenticated();

// Get checkout URLs
$checkout_nonce = wp_create_nonce( 'seo_ai_meta_direct_checkout' );
// Use edit.php since the menu is under Posts (add_posts_page)
$checkout_base = admin_url( 'edit.php' );

$core = new SEO_AI_Meta_Core();
$pro_price_id = $core->get_checkout_price_id( 'pro' );
$agency_price_id = $core->get_checkout_price_id( 'agency' );

// Ensure we always have price IDs - use defaults if empty (do this BEFORE checking)
if ( empty( $pro_price_id ) ) {
	$pro_price_id = 'price_1SQ72OJl9Rm418cMruYB5Pgb'; // Default Pro price ID (LIVE)
}
if ( empty( $agency_price_id ) ) {
	$agency_price_id = 'price_1SQ72KJl9Rm418cMB0CYh8xe'; // Default Agency price ID (LIVE)
}

// Debug: Log price IDs if still empty (only in debug mode)
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
	if ( empty( $pro_price_id ) ) {
		error_log( 'SEO AI Meta: Pro price ID is still empty after defaults' );
	}
	if ( empty( $agency_price_id ) ) {
		error_log( 'SEO AI Meta: Agency price ID is still empty after defaults' );
	}
}

$pro_url = add_query_arg( array(
	'page'             => 'seo-ai-meta-generator-checkout',
	'plan'             => 'pro',
	'price_id'         => $pro_price_id,
	'_seo_ai_meta_nonce' => $checkout_nonce,
), $checkout_base );

$agency_url = add_query_arg( array(
	'page'             => 'seo-ai-meta-generator-checkout',
	'plan'             => 'agency',
	'price_id'         => $agency_price_id,
	'_seo_ai_meta_nonce' => $checkout_nonce,
), $checkout_base );
?>

<div id="seo-ai-meta-upgrade-modal" class="seo-ai-meta-modal-backdrop" style="display: none;" role="dialog" aria-modal="true">
	<div class="seo-ai-meta-upgrade-modal__content">
		<div class="seo-ai-meta-upgrade-modal__header">
			<div class="seo-ai-meta-upgrade-modal__header-content">
				<h2><?php esc_html_e( 'Unlock Unlimited AI Power', 'seo-ai-meta-generator' ); ?></h2>
				<p class="seo-ai-meta-upgrade-modal__subtitle">
					<?php esc_html_e( 'Boost search rankings with AI-optimized meta tags', 'seo-ai-meta-generator' ); ?>
				</p>
			</div>
			<button type="button" class="seo-ai-meta-modal-close" onclick="seoAiMetaCloseModal();" aria-label="<?php esc_attr_e( 'Close upgrade modal', 'seo-ai-meta-generator' ); ?>">
				<svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
					<path d="M15 5L5 15M5 5l10 10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
				</svg>
			</button>
		</div>

		<div class="seo-ai-meta-upgrade-modal__body">
			<?php if ( ! $is_authenticated ) : ?>
				<div class="seo-ai-meta-auth-notice">
					<div class="seo-ai-meta-auth-notice__icon">🔒</div>
					<p>
						<strong><?php esc_html_e( 'Account Required', 'seo-ai-meta-generator' ); ?></strong>
						<?php esc_html_e( 'Please login or create an account to subscribe to a plan.', 'seo-ai-meta-generator' ); ?>
					</p>
				</div>
			<?php endif; ?>

			<!-- Compare Plans Table -->
			<div class="seo-ai-meta-compare-plans-container" style="margin-bottom: 32px;">
				<table class="seo-ai-meta-compare-plans-table" style="width: 100%; border-collapse: collapse; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);">
					<thead>
						<tr style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);">
							<th style="padding: 20px; text-align: left; font-size: 14px; font-weight: 600; color: #1f2937; border-bottom: 2px solid #e5e7eb;"><?php esc_html_e( 'Feature', 'seo-ai-meta-generator' ); ?></th>
							<th style="padding: 20px; text-align: center; font-size: 14px; font-weight: 600; color: #1f2937; border-bottom: 2px solid #e5e7eb;">
								<div style="font-size: 16px; margin-bottom: 4px;"><?php esc_html_e( 'Free', 'seo-ai-meta-generator' ); ?></div>
								<div style="font-size: 12px; color: #6b7280; font-weight: 400;"><?php esc_html_e( '50/month', 'seo-ai-meta-generator' ); ?></div>
							</th>
							<th style="padding: 20px; text-align: center; font-size: 14px; font-weight: 600; color: #1f2937; border-bottom: 2px solid #e5e7eb; background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); position: relative;">
								<div style="position: absolute; top: 8px; right: 8px; background: #3b82f6; color: white; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: 600;"><?php esc_html_e( 'POPULAR', 'seo-ai-meta-generator' ); ?></div>
								<div style="font-size: 16px; margin-bottom: 4px;"><?php esc_html_e( 'Pro', 'seo-ai-meta-generator' ); ?></div>
								<div style="font-size: 12px; color: #6b7280; font-weight: 400;"><?php esc_html_e( '£12.99/month', 'seo-ai-meta-generator' ); ?></div>
							</th>
							<th style="padding: 20px; text-align: center; font-size: 14px; font-weight: 600; color: #1f2937; border-bottom: 2px solid #e5e7eb;">
								<div style="font-size: 16px; margin-bottom: 4px;"><?php esc_html_e( 'Agency', 'seo-ai-meta-generator' ); ?></div>
								<div style="font-size: 12px; color: #6b7280; font-weight: 400;"><?php esc_html_e( '£49.99/month', 'seo-ai-meta-generator' ); ?></div>
							</th>
						</tr>
					</thead>
					<tbody>
						<tr style="border-bottom: 1px solid #f3f4f6;">
							<td style="padding: 16px 20px; font-size: 14px; color: #374151; font-weight: 500;"><?php esc_html_e( 'AI Generations', 'seo-ai-meta-generator' ); ?></td>
							<td style="padding: 16px 20px; text-align: center;">
								<span style="color: #6b7280; font-size: 14px;"><?php esc_html_e( '50/month', 'seo-ai-meta-generator' ); ?></span>
							</td>
							<td style="padding: 16px 20px; text-align: center; background: #f8fafc;">
								<span style="color: #1f2937; font-size: 14px; font-weight: 600;"><?php esc_html_e( '100/month', 'seo-ai-meta-generator' ); ?></span>
							</td>
							<td style="padding: 16px 20px; text-align: center;">
								<span style="color: #1f2937; font-size: 14px; font-weight: 600;"><?php esc_html_e( '1,000/month', 'seo-ai-meta-generator' ); ?></span>
							</td>
						</tr>
						<tr style="border-bottom: 1px solid #f3f4f6;">
							<td style="padding: 16px 20px; font-size: 14px; color: #374151; font-weight: 500;"><?php esc_html_e( 'Bulk Generation', 'seo-ai-meta-generator' ); ?></td>
							<td style="padding: 16px 20px; text-align: center;">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2.5" style="display: inline-block;">
									<line x1="18" y1="6" x2="6" y2="18"/>
									<line x1="6" y1="6" x2="18" y2="18"/>
								</svg>
							</td>
							<td style="padding: 16px 20px; text-align: center; background: #f8fafc;">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2.5" style="display: inline-block;">
									<path d="M20 6L9 17l-5-5"/>
								</svg>
							</td>
							<td style="padding: 16px 20px; text-align: center;">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2.5" style="display: inline-block;">
									<path d="M20 6L9 17l-5-5"/>
								</svg>
							</td>
						</tr>
						<tr style="border-bottom: 1px solid #f3f4f6;">
							<td style="padding: 16px 20px; font-size: 14px; color: #374151; font-weight: 500;"><?php esc_html_e( 'Performance Overview', 'seo-ai-meta-generator' ); ?></td>
							<td style="padding: 16px 20px; text-align: center;">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2.5" style="display: inline-block;">
									<line x1="18" y1="6" x2="6" y2="18"/>
									<line x1="6" y1="6" x2="18" y2="18"/>
								</svg>
							</td>
							<td style="padding: 16px 20px; text-align: center; background: #f8fafc;">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2.5" style="display: inline-block;">
									<path d="M20 6L9 17l-5-5"/>
								</svg>
							</td>
							<td style="padding: 16px 20px; text-align: center;">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2.5" style="display: inline-block;">
									<path d="M20 6L9 17l-5-5"/>
								</svg>
							</td>
						</tr>
						<tr style="border-bottom: 1px solid #f3f4f6;">
							<td style="padding: 16px 20px; font-size: 14px; color: #374151; font-weight: 500;"><?php esc_html_e( 'Priority Support', 'seo-ai-meta-generator' ); ?></td>
							<td style="padding: 16px 20px; text-align: center;">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2.5" style="display: inline-block;">
									<line x1="18" y1="6" x2="6" y2="18"/>
									<line x1="6" y1="6" x2="18" y2="18"/>
								</svg>
							</td>
							<td style="padding: 16px 20px; text-align: center; background: #f8fafc;">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2.5" style="display: inline-block;">
									<path d="M20 6L9 17l-5-5"/>
								</svg>
							</td>
							<td style="padding: 16px 20px; text-align: center;">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2.5" style="display: inline-block;">
									<path d="M20 6L9 17l-5-5"/>
								</svg>
							</td>
						</tr>
						<tr style="border-bottom: 1px solid #f3f4f6;">
							<td style="padding: 16px 20px; font-size: 14px; color: #374151; font-weight: 500;"><?php esc_html_e( 'White Label', 'seo-ai-meta-generator' ); ?></td>
							<td style="padding: 16px 20px; text-align: center;">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2.5" style="display: inline-block;">
									<line x1="18" y1="6" x2="6" y2="18"/>
									<line x1="6" y1="6" x2="18" y2="18"/>
								</svg>
							</td>
							<td style="padding: 16px 20px; text-align: center; background: #f8fafc;">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2.5" style="display: inline-block;">
									<line x1="18" y1="6" x2="6" y2="18"/>
									<line x1="6" y1="6" x2="18" y2="18"/>
								</svg>
							</td>
							<td style="padding: 16px 20px; text-align: center;">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2.5" style="display: inline-block;">
									<path d="M20 6L9 17l-5-5"/>
								</svg>
							</td>
						</tr>
					</tbody>
					<tfoot>
						<tr>
							<td style="padding: 20px;"></td>
							<td style="padding: 20px; text-align: center;">
								<?php if ( ! $is_authenticated ) : ?>
									<button type="button" onclick="seoAiMetaShowLoginModal(); seoAiMetaCloseModal();" style="padding: 10px 20px; background: #f3f4f6; color: #6b7280; border: 1px solid #d1d5db; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; width: 100%; transition: all 0.2s;" onmouseover="this.style.background='#e5e7eb';" onmouseout="this.style.background='#f3f4f6';">
										<?php esc_html_e( 'Current Plan', 'seo-ai-meta-generator' ); ?>
									</button>
								<?php else : ?>
									<span style="color: #6b7280; font-size: 14px;"><?php esc_html_e( 'Current Plan', 'seo-ai-meta-generator' ); ?></span>
								<?php endif; ?>
							</td>
							<td style="padding: 20px; text-align: center; background: #f8fafc;">
								<?php if ( ! $is_authenticated ) : ?>
									<button type="button" onclick="seoAiMetaShowLoginModal(); seoAiMetaCloseModal();" style="padding: 12px 24px; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; border: none; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; width: 100%; transition: all 0.2s; box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.3);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 12px -1px rgba(59, 130, 246, 0.4)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 6px -1px rgba(59, 130, 246, 0.3)';">
										<?php esc_html_e( 'Get Started', 'seo-ai-meta-generator' ); ?>
									</button>
								<?php elseif ( ! empty( $pro_price_id ) ) : ?>
									<a href="<?php echo esc_url( $pro_url ); ?>" style="display: inline-block; padding: 12px 24px; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; border: none; border-radius: 8px; font-weight: 600; font-size: 14px; text-decoration: none; width: 100%; text-align: center; transition: all 0.2s; box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.3);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 12px -1px rgba(59, 130, 246, 0.4)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 6px -1px rgba(59, 130, 246, 0.3)';">
										<?php esc_html_e( 'Get Started', 'seo-ai-meta-generator' ); ?>
									</a>
								<?php else : ?>
									<button type="button" disabled style="padding: 12px 24px; background: #d1d5db; color: #6b7280; border: none; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: not-allowed; width: 100%;">
										<?php esc_html_e( 'Coming Soon', 'seo-ai-meta-generator' ); ?>
									</button>
								<?php endif; ?>
							</td>
							<td style="padding: 20px; text-align: center;">
								<?php if ( ! $is_authenticated ) : ?>
									<button type="button" onclick="seoAiMetaShowLoginModal(); seoAiMetaCloseModal();" style="padding: 12px 24px; background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); color: white; border: none; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; width: 100%; transition: all 0.2s; box-shadow: 0 4px 6px -1px rgba(34, 197, 94, 0.3);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 12px -1px rgba(34, 197, 94, 0.4)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 6px -1px rgba(34, 197, 94, 0.3)';">
										<?php esc_html_e( 'Get Started', 'seo-ai-meta-generator' ); ?>
									</button>
								<?php elseif ( ! empty( $agency_price_id ) ) : ?>
									<a href="<?php echo esc_url( $agency_url ); ?>" style="display: inline-block; padding: 12px 24px; background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); color: white; border: none; border-radius: 8px; font-weight: 600; font-size: 14px; text-decoration: none; width: 100%; text-align: center; transition: all 0.2s; box-shadow: 0 4px 6px -1px rgba(34, 197, 94, 0.3);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 12px -1px rgba(34, 197, 94, 0.4)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 6px -1px rgba(34, 197, 94, 0.3)';">
										<?php esc_html_e( 'Get Started', 'seo-ai-meta-generator' ); ?>
									</a>
								<?php else : ?>
									<button type="button" disabled style="padding: 12px 24px; background: #d1d5db; color: #6b7280; border: none; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: not-allowed; width: 100%;">
										<?php esc_html_e( 'Coming Soon', 'seo-ai-meta-generator' ); ?>
									</button>
								<?php endif; ?>
							</td>
						</tr>
					</tfoot>
				</table>
			</div>

			<!-- Testimonials Section -->
			<div class="seo-ai-meta-upgrade-modal__testimonials" style="padding: 24px 32px; border-top: 1px solid #e5e7eb; background: white;">
				<div id="seo-ai-meta-modal-testimonial" style="text-align: center; max-width: 600px; margin: 0 auto;">
					<div style="font-size: 16px; color: #374151; font-style: italic; margin-bottom: 12px; line-height: 1.6;">
						<span class="seo-ai-meta-modal-testimonial-text"><?php esc_html_e( 'Generated 1,200 meta tags in minutes, saved hours each week', 'seo-ai-meta-generator' ); ?></span>
					</div>
					<div style="font-size: 14px; color: #6b7280; font-weight: 500;">
						<span class="seo-ai-meta-modal-testimonial-author"><?php esc_html_e( 'Sarah W., Agency Owner', 'seo-ai-meta-generator' ); ?></span>
					</div>
				</div>
			</div>

			<!-- Trust Elements -->
			<div class="seo-ai-meta-upgrade-modal__footer" style="padding: 24px 32px; border-top: 1px solid #e5e7eb; background: #f9fafb; border-radius: 0 0 22px 22px;">
				<div class="seo-ai-meta-trust-elements" style="display: flex; align-items: center; justify-content: center; gap: 32px; flex-wrap: wrap;">
					<div class="seo-ai-meta-trust-item" style="display: flex; align-items: center; gap: 8px; color: #6b7280; font-size: 13px;">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink: 0;">
							<rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
							<path d="M7 11V7a5 5 0 0 1 10 0v4"/>
						</svg>
						<span class="seo-ai-meta-trust-text"><?php esc_html_e( 'Secure checkout via Stripe', 'seo-ai-meta-generator' ); ?></span>
					</div>
					<div class="seo-ai-meta-trust-item" style="display: flex; align-items: center; gap: 8px; color: #6b7280; font-size: 13px;">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink: 0;">
							<polyline points="20 6 9 17 4 12"/>
						</svg>
						<span class="seo-ai-meta-trust-text"><?php esc_html_e( 'Cancel anytime', 'seo-ai-meta-generator' ); ?></span>
					</div>
					<div class="seo-ai-meta-trust-item" style="display: flex; align-items: center; gap: 8px; color: #6b7280; font-size: 13px;">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink: 0;">
							<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
						</svg>
						<span class="seo-ai-meta-trust-text"><?php esc_html_e( 'Instant activation', 'seo-ai-meta-generator' ); ?></span>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
(function() {
	console.log('SEO AI Meta: Upgrade modal script loading...');

	// Testimonials for upgrade modal
	var modalTestimonials = [
		{
			text: 'Generated 1,200 meta tags in minutes, saved hours each week',
			author: 'Sarah W., Agency Owner'
		},
		{
			text: 'Boosted our SEO rankings by 40% in just 2 months',
			author: 'Mike T., Marketing Director'
		},
		{
			text: 'The best SEO plugin we\'ve used. ROI is incredible.',
			author: 'Jennifer L., Content Manager'
		},
		{
			text: 'Saved 15+ hours weekly on meta tag optimization',
			author: 'David K., E-commerce Owner'
		}
	];
	
	var currentModalTestimonial = 0;
	var modalTestimonialInterval;
	
	function rotateModalTestimonial() {
		var $container = jQuery('#seo-ai-meta-modal-testimonial');
		if ($container.length && modalTestimonials.length > 1) {
			var $text = $container.find('.seo-ai-meta-modal-testimonial-text');
			var $author = $container.find('.seo-ai-meta-modal-testimonial-author');
			
			currentModalTestimonial = (currentModalTestimonial + 1) % modalTestimonials.length;
			var testimonial = modalTestimonials[currentModalTestimonial];
			
			// Fade transition
			$container.fadeOut(200, function() {
				$text.text(testimonial.text);
				$author.text(testimonial.author);
				$container.fadeIn(200);
			});
		}
	}
	
	function startModalTestimonialRotation() {
		if (modalTestimonialInterval) {
			clearInterval(modalTestimonialInterval);
		}
		// Rotate every 6 seconds when modal is open
		modalTestimonialInterval = setInterval(rotateModalTestimonial, 6000);
	}
	
	function stopModalTestimonialRotation() {
		if (modalTestimonialInterval) {
			clearInterval(modalTestimonialInterval);
			modalTestimonialInterval = null;
		}
	}

	function seoAiMetaCloseModal() {
		const modal = document.getElementById('seo-ai-meta-upgrade-modal');
		if (modal) {
			modal.style.display = 'none';
			document.body.style.overflow = '';
			stopModalTestimonialRotation();
			if (typeof seoAiMetaTrackEvent === 'function') {
				seoAiMetaTrackEvent('upgrade_modal_close', {source: 'dashboard'});
			}
		}
	}

	function seoAiMetaShowModal() {
		const modal = document.getElementById('seo-ai-meta-upgrade-modal');
		if (modal) {
			modal.style.display = 'flex';
			document.body.style.overflow = 'hidden';
			startModalTestimonialRotation();
			if (typeof seoAiMetaTrackEvent === 'function') {
				seoAiMetaTrackEvent('upgrade_modal_open', {source: 'dashboard'});
			}
		}
	}

	// Alias for backward compatibility
	window.seoAiMetaShowUpgradeModal = function() {
		console.log('SEO AI Meta: seoAiMetaShowUpgradeModal called');
		seoAiMetaShowModal();
	};
	window.seoAiMetaCloseModal = seoAiMetaCloseModal;

	console.log('SEO AI Meta: Modal functions registered:', {
		seoAiMetaShowUpgradeModal: typeof window.seoAiMetaShowUpgradeModal,
		seoAiMetaCloseModal: typeof window.seoAiMetaCloseModal
	});
	
	// Track button clicks with analytics
	jQuery(document).ready(function($) {
		// Track "Get Started" button clicks
		$(document).on('click', '#seo-ai-meta-upgrade-modal a[href*="checkout"], #seo-ai-meta-upgrade-modal button[onclick*="login"]', function() {
			var plan = $(this).closest('td').index() === 2 ? 'pro' : 'agency';
			if (typeof seoAiMetaTrackEvent === 'function') {
				seoAiMetaTrackEvent('upgrade_cta_click', {
					plan: plan,
					source: 'upgrade_modal',
					authenticated: $(this).attr('href') ? true : false
				});
			}
		});
		
		// Close modal on backdrop click
		$(document).on('click', '#seo-ai-meta-upgrade-modal', function(e) {
			if (e.target === this) {
				seoAiMetaCloseModal();
			}
		});
		
		// Close on ESC key
		$(document).on('keydown', function(e) {
			if (e.key === 'Escape' && $('#seo-ai-meta-upgrade-modal').is(':visible')) {
				seoAiMetaCloseModal();
			}
		});

		// Animate progress bars on load
		setTimeout(function() {
			document.querySelectorAll('.seo-ai-meta-progress-animated').forEach(function(bar) {
				const percentage = bar.getAttribute('data-percentage');
				if (percentage) {
					bar.style.width = percentage + '%';
				}
			});
		}, 100);

		// Ensure all CTA links work properly - minimal interference
		var modal = document.getElementById('seo-ai-meta-upgrade-modal');
		if (modal) {
			const ctaLinks = modal.querySelectorAll('.seo-ai-meta-plan-cta[href]');
			ctaLinks.forEach(function(link) {
				// Only add visual feedback, don't prevent navigation
				link.addEventListener('click', function(e) {
					// Show loading state but allow navigation to proceed
					const span = link.querySelector('span');
					if (span) {
						const originalText = span.textContent;
						span.textContent = 'Loading...';
						link.style.opacity = '0.7';

						// Debug log (only in debug mode)
						<?php if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) : ?>
						console.log('SEO AI Meta: Navigating to checkout URL:', link.getAttribute('href'));
						<?php endif; ?>

						// Navigation should happen naturally - restore if it doesn't within 5 seconds
						setTimeout(function() {
							if (!document.hidden && span.textContent === 'Loading...') {
								span.textContent = originalText;
								link.style.opacity = '1';
								<?php if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) : ?>
								console.warn('SEO AI Meta: Navigation may have been blocked');
								<?php endif; ?>
							}
						}, 5000);
					}
				});
			});
		}
	});
})();
</script>
