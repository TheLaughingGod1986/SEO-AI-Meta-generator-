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

			<!-- Pricing Plans -->
			<div class="seo-ai-meta-pricing-container">
				<!-- Pro Plan -->
				<div class="seo-ai-meta-plan-card seo-ai-meta-plan-card--pro">
					<div class="seo-ai-meta-plan-header">
						<h3><?php esc_html_e( 'Pro Plan', 'seo-ai-meta-generator' ); ?></h3>
						<div class="seo-ai-meta-plan-price">
							<span class="seo-ai-meta-price-amount">£12.99</span>
							<span class="seo-ai-meta-price-period"><?php esc_html_e( '/month', 'seo-ai-meta-generator' ); ?></span>
						</div>
						<p class="seo-ai-meta-plan-value"><?php esc_html_e( 'Perfect for growing websites', 'seo-ai-meta-generator' ); ?></p>
					</div>

					<div class="seo-ai-meta-plan-features">
						<ul>
							<li>
								<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
									<polyline points="20 6 9 17 4 12"/>
								</svg>
								<span class="seo-ai-meta-feature-highlight"><?php esc_html_e( '100', 'seo-ai-meta-generator' ); ?></span>
								<?php esc_html_e( 'posts per month', 'seo-ai-meta-generator' ); ?>
							</li>
							<li>
								<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
									<polyline points="20 6 9 17 4 12"/>
								</svg>
								<?php esc_html_e( 'GPT-4-turbo for advanced quality', 'seo-ai-meta-generator' ); ?>
							</li>
							<li>
								<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
									<polyline points="20 6 9 17 4 12"/>
								</svg>
								<?php esc_html_e( 'Bulk generate unlimited posts', 'seo-ai-meta-generator' ); ?>
							</li>
							<li>
								<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
									<polyline points="20 6 9 17 4 12"/>
								</svg>
								<?php esc_html_e( 'Priority support', 'seo-ai-meta-generator' ); ?>
							</li>
						</ul>
					</div>

					<?php if ( ! $is_authenticated ) : ?>
						<button class="seo-ai-meta-btn-primary seo-ai-meta-plan-cta" onclick="seoAiMetaShowLoginModal(); seoAiMetaCloseModal();" type="button">
							<span><?php esc_html_e( 'Login/Register to Get Started', 'seo-ai-meta-generator' ); ?></span>
						</button>
						<p class="seo-ai-meta-plan-notice"><?php esc_html_e( 'Please create an account to subscribe', 'seo-ai-meta-generator' ); ?></p>
					<?php elseif ( ! empty( $pro_price_id ) ) : ?>
						<a href="<?php echo esc_url( $pro_url ); ?>" class="seo-ai-meta-btn-primary seo-ai-meta-plan-cta">
							<span><?php esc_html_e( 'Get Started with Pro', 'seo-ai-meta-generator' ); ?></span>
						</a>
					<?php else : ?>
						<button class="seo-ai-meta-btn-primary seo-ai-meta-plan-cta" disabled>
							<span><?php esc_html_e( 'Coming Soon', 'seo-ai-meta-generator' ); ?></span>
						</button>
						<p class="seo-ai-meta-plan-notice"><?php esc_html_e( 'Stripe integration being configured. Please check back soon.', 'seo-ai-meta-generator' ); ?></p>
					<?php endif; ?>
				</div>

				<!-- Agency Plan -->
				<div class="seo-ai-meta-plan-card seo-ai-meta-plan-card--featured seo-ai-meta-plan-card--agency">
					<div class="seo-ai-meta-plan-badge"><?php esc_html_e( 'MOST POPULAR', 'seo-ai-meta-generator' ); ?></div>
					<div class="seo-ai-meta-plan-header">
						<h3><?php esc_html_e( 'Agency Plan', 'seo-ai-meta-generator' ); ?></h3>
						<div class="seo-ai-meta-plan-price">
							<span class="seo-ai-meta-price-amount">£49.99</span>
							<span class="seo-ai-meta-price-period"><?php esc_html_e( '/month', 'seo-ai-meta-generator' ); ?></span>
						</div>
						<p class="seo-ai-meta-plan-value"><?php esc_html_e( 'Best value for agencies', 'seo-ai-meta-generator' ); ?></p>
					</div>

					<div class="seo-ai-meta-plan-features">
						<ul>
							<li>
								<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
									<polyline points="20 6 9 17 4 12"/>
								</svg>
								<span class="seo-ai-meta-feature-highlight"><?php esc_html_e( '1,000', 'seo-ai-meta-generator' ); ?></span>
								<?php esc_html_e( 'posts per month', 'seo-ai-meta-generator' ); ?>
							</li>
							<li>
								<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
									<polyline points="20 6 9 17 4 12"/>
								</svg>
								<?php esc_html_e( 'GPT-4-turbo for advanced quality', 'seo-ai-meta-generator' ); ?>
							</li>
							<li>
								<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
									<polyline points="20 6 9 17 4 12"/>
								</svg>
								<?php esc_html_e( 'Bulk generate unlimited posts', 'seo-ai-meta-generator' ); ?>
							</li>
							<li>
								<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
									<polyline points="20 6 9 17 4 12"/>
								</svg>
								<?php esc_html_e( 'Priority support', 'seo-ai-meta-generator' ); ?>
							</li>
							<li>
								<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
									<polyline points="20 6 9 17 4 12"/>
								</svg>
								<?php esc_html_e( 'White-label options', 'seo-ai-meta-generator' ); ?>
							</li>
						</ul>
					</div>

					<?php if ( ! $is_authenticated ) : ?>
						<button class="seo-ai-meta-btn-success seo-ai-meta-plan-cta" onclick="seoAiMetaShowLoginModal(); seoAiMetaCloseModal();" type="button">
							<span><?php esc_html_e( 'Login/Register to Upgrade', 'seo-ai-meta-generator' ); ?></span>
						</button>
						<p class="seo-ai-meta-plan-notice"><?php esc_html_e( 'Please create an account to subscribe', 'seo-ai-meta-generator' ); ?></p>
					<?php elseif ( ! empty( $agency_price_id ) ) : ?>
						<a href="<?php echo esc_url( $agency_url ); ?>" class="seo-ai-meta-btn-success seo-ai-meta-plan-cta">
							<span><?php esc_html_e( 'Upgrade to Agency', 'seo-ai-meta-generator' ); ?></span>
						</a>
					<?php else : ?>
						<button class="seo-ai-meta-btn-success seo-ai-meta-plan-cta" disabled>
							<span><?php esc_html_e( 'Coming Soon', 'seo-ai-meta-generator' ); ?></span>
						</button>
						<p class="seo-ai-meta-plan-notice"><?php esc_html_e( 'Stripe integration being configured. Please check back soon.', 'seo-ai-meta-generator' ); ?></p>
					<?php endif; ?>
				</div>
			</div>

			<!-- Trust Elements -->
			<div class="seo-ai-meta-upgrade-modal__footer">
				<div class="seo-ai-meta-trust-elements">
					<div class="seo-ai-meta-trust-item">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
							<path d="M7 11V7a5 5 0 0 1 10 0v4"/>
						</svg>
						<span class="seo-ai-meta-trust-text"><?php esc_html_e( 'Secure checkout via Stripe', 'seo-ai-meta-generator' ); ?></span>
					</div>
					<div class="seo-ai-meta-trust-item">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<polyline points="20 6 9 17 4 12"/>
						</svg>
						<span class="seo-ai-meta-trust-text"><?php esc_html_e( 'Cancel anytime', 'seo-ai-meta-generator' ); ?></span>
					</div>
					<div class="seo-ai-meta-trust-item">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
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
function seoAiMetaCloseModal() {
	const modal = document.getElementById('seo-ai-meta-upgrade-modal');
	if (modal) {
		modal.style.display = 'none';
		document.body.style.overflow = '';
	}
}

function seoAiMetaShowModal() {
	const modal = document.getElementById('seo-ai-meta-upgrade-modal');
	if (modal) {
		modal.style.display = 'flex';
		document.body.style.overflow = 'hidden';
	}
}

// Alias for backward compatibility
function seoAiMetaShowUpgradeModal() {
	seoAiMetaShowModal();
}

// Close modal when clicking backdrop
document.addEventListener('DOMContentLoaded', function() {
	const modal = document.getElementById('seo-ai-meta-upgrade-modal');
	if (modal) {
		modal.addEventListener('click', function(e) {
			if (e.target === modal) {
				seoAiMetaCloseModal();
			}
		});

		const content = modal.querySelector('.seo-ai-meta-upgrade-modal__content');
		if (content) {
			content.addEventListener('click', function(e) {
				e.stopPropagation();
			});
		}

		// Close on Escape key
		document.addEventListener('keydown', function(e) {
			if (e.key === 'Escape' && modal.style.display === 'flex') {
				seoAiMetaCloseModal();
			}
		});
	}

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
});
</script>
