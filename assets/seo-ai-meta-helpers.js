/**
 * SEO AI Meta Generator Helper Functions
 * Global functions for button handlers and utilities
 */
(function() {
	'use strict';

	// Debug flag - set via WordPress (WP_DEBUG)
	window.SEO_AI_META_DEBUG = window.SEO_AI_META_DEBUG || false;

	// Debug logging helper
	window.seoAiMetaDebug = function() {
		if (window.SEO_AI_META_DEBUG && typeof console !== 'undefined' && console.log) {
			console.log.apply(console, ['[SEO AI Meta]'].concat(Array.prototype.slice.call(arguments)));
		}
	};

	// Error logging helper (always show errors)
	window.seoAiMetaError = function() {
		if (typeof console !== 'undefined' && console.error) {
			console.error.apply(console, ['[SEO AI Meta ERROR]'].concat(Array.prototype.slice.call(arguments)));
		}
	};

	seoAiMetaDebug('Helpers loaded');

	// Track events (stub function - can be connected to analytics later)
	window.seoAiMetaTrackEvent = function(eventName, eventData) {
		seoAiMetaDebug('Event:', eventName, eventData);

		// You can integrate with Google Analytics, Mixpanel, etc. here
		if (typeof gtag !== 'undefined') {
			gtag('event', eventName, eventData || {});
		}
	};

	// Logout function
	window.seoAiMetaLogout = function() {
		seoAiMetaDebug('seoAiMetaLogout called');

		if (!confirm('Are you sure you want to logout?')) {
			return;
		}

		// Check if seoAiMetaAjax is defined
		if (typeof seoAiMetaAjax === 'undefined') {
			seoAiMetaError('seoAiMetaAjax is not defined!');
			alert('Configuration error. Please refresh the page.');
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
				if (response.success) {
					seoAiMetaTrackEvent('logout_success', {});
					// Reload page to show logged out state
					window.location.reload();
				} else {
					alert('Logout failed. Please try again.');
				}
			},
			error: function() {
				alert('Network error. Please try again.');
			}
		});
	};

	// Toggle password visibility
	window.seoAiMetaTogglePasswordVisibility = function(inputId, button) {
		var input = document.getElementById(inputId);
		if (!input) return;

		if (input.type === 'password') {
			input.type = 'text';
			button.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>';
		} else {
			input.type = 'password';
			button.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
		}
	};

	// Show login tab (for forgot password flow)
	window.seoAiMetaShowLoginTab = function() {
		// Hide forgot password, show login
		var modal = document.getElementById('seo-ai-meta-login-modal');
		if (!modal) return;

		var loginForm = modal.querySelector('#seo-ai-meta-login-form');
		var forgotForm = modal.querySelector('#seo-ai-meta-forgot-password-form');

		if (loginForm) loginForm.style.display = 'block';
		if (forgotForm) forgotForm.style.display = 'none';
	};

	// Show forgot password form
	window.seoAiMetaShowForgotPassword = function() {
		var modal = document.getElementById('seo-ai-meta-login-modal');
		if (!modal) return;

		var loginForm = modal.querySelector('#seo-ai-meta-login-form');
		var forgotForm = modal.querySelector('#seo-ai-meta-forgot-password-form');

		if (loginForm) loginForm.style.display = 'none';
		if (forgotForm) forgotForm.style.display = 'block';
	};

	// Copy context button handler
	jQuery(document).ready(function($) {
		// Copy title button
		$(document).on('click', '#seo-ai-meta-copy-title', function() {
			var titleField = document.getElementById('seo-ai-meta-title');
			if (!titleField) return;

			var textToCopy = titleField.value;
			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(textToCopy).then(function() {
					showCopyFeedback('#seo-ai-meta-copy-title', 'Copied!');
				}).catch(function() {
					fallbackCopy(textToCopy);
				});
			} else {
				fallbackCopy(textToCopy);
			}
		});

		// Copy description button
		$(document).on('click', '#seo-ai-meta-copy-description', function() {
			var descField = document.getElementById('seo-ai-meta-description');
			if (!descField) return;

			var textToCopy = descField.value;
			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(textToCopy).then(function() {
					showCopyFeedback('#seo-ai-meta-copy-description', 'Copied!');
				}).catch(function() {
					fallbackCopy(textToCopy);
				});
			} else {
				fallbackCopy(textToCopy);
			}
		});

		// Copy context button in modal
		$(document).on('click', '#seo-ai-meta-copy-context-btn', function() {
			var contextDiv = document.getElementById('seo-ai-meta-current-context');
			if (!contextDiv) return;

			var textToCopy = contextDiv.textContent || contextDiv.innerText;
			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(textToCopy).then(function() {
					showCopyFeedback('#seo-ai-meta-copy-context-btn', 'Copied!');
				}).catch(function() {
					fallbackCopy(textToCopy);
				});
			} else {
				fallbackCopy(textToCopy);
			}
		});

		// Helper function to show copy feedback
		function showCopyFeedback(buttonSelector, message) {
			var $btn = $(buttonSelector);
			var originalText = $btn.text();
			$btn.text(message);
			$btn.css('background', '#10b981');
			$btn.css('color', 'white');

			setTimeout(function() {
				$btn.text(originalText);
				$btn.css('background', '');
				$btn.css('color', '');
			}, 2000);
		}

		// Fallback copy method for older browsers
		function fallbackCopy(text) {
			var textArea = document.createElement('textarea');
			textArea.value = text;
			textArea.style.position = 'fixed';
			textArea.style.left = '-999999px';
			document.body.appendChild(textArea);
			textArea.select();
			try {
				document.execCommand('copy');
				alert('Copied to clipboard!');
			} catch (err) {
				alert('Failed to copy. Please copy manually.');
			}
			document.body.removeChild(textArea);
		}

		// Handle export dropdown close on click outside
		$(document).on('click', function(e) {
			var dropdown = $('#seo-ai-meta-export-menu');
			var button = $('#seo-ai-meta-export-dropdown-btn');

			if (!dropdown.is(e.target) && dropdown.has(e.target).length === 0 &&
				!button.is(e.target) && button.has(e.target).length === 0) {
				dropdown.hide();
			}
		});

		// Prevent dropdown from closing when clicking inside
		$(document).on('click', '#seo-ai-meta-export-menu', function(e) {
			e.stopPropagation();
		});
	});

	// Initialize testimonial carousel (header)
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

	function rotateTestimonial() {
		var carousel = document.getElementById('seo-ai-meta-testimonial-carousel');
		if (!carousel) return;

		currentTestimonial = (currentTestimonial + 1) % testimonials.length;
		var testimonial = testimonials[currentTestimonial];

		var textEl = carousel.querySelector('.seo-ai-meta-testimonial-text');
		var authorEl = carousel.querySelector('.seo-ai-meta-testimonial-author');
		var avatarEl = carousel.querySelector('.seo-ai-meta-testimonial-avatar');

		if (textEl) {
			jQuery(carousel).fadeOut(300, function() {
				textEl.textContent = testimonial.text;
				if (authorEl) authorEl.textContent = testimonial.author;
				if (avatarEl) avatarEl.textContent = testimonial.initials;
				jQuery(carousel).fadeIn(300);
			});
		}
	}

	// Start testimonial rotation every 8 seconds
	if (document.getElementById('seo-ai-meta-testimonial-carousel')) {
		setInterval(rotateTestimonial, 8000);
	}

	// Debug: Confirm all functions are registered
	seoAiMetaDebug('Functions registered:', {
		seoAiMetaTrackEvent: typeof window.seoAiMetaTrackEvent,
		seoAiMetaLogout: typeof window.seoAiMetaLogout,
		seoAiMetaTogglePasswordVisibility: typeof window.seoAiMetaTogglePasswordVisibility,
		seoAiMetaShowLoginTab: typeof window.seoAiMetaShowLoginTab,
		seoAiMetaShowForgotPassword: typeof window.seoAiMetaShowForgotPassword
	});

	// BACKUP: Add jQuery-based click handlers in case onclick doesn't work
	jQuery(document).ready(function($) {
		seoAiMetaDebug('Setting up jQuery click handlers as backup...');

		// Log current state of all functions
		seoAiMetaDebug('Function availability check:', {
			seoAiMetaShowLoginModal: typeof window.seoAiMetaShowLoginModal,
			seoAiMetaShowUpgradeModal: typeof window.seoAiMetaShowUpgradeModal,
			seoAiMetaCloseModal: typeof window.seoAiMetaCloseModal,
			seoAiMetaLogout: typeof window.seoAiMetaLogout,
			seoAiMetaAjax: typeof seoAiMetaAjax,
			jQuery: typeof jQuery
		});

		// Login button handler
		$(document).on('click', '.seo-ai-meta-btn-login', function(e) {
			seoAiMetaDebug('Login button clicked (jQuery handler)');
			if (typeof window.seoAiMetaShowLoginModal === 'function') {
				window.seoAiMetaShowLoginModal();
			} else {
				seoAiMetaError('seoAiMetaShowLoginModal not available!');
				alert('Login modal not ready. Please refresh the page and check console for errors.');
			}
		});

		// Logout button handler
		$(document).on('click', '.seo-ai-meta-btn-logout', function(e) {
			seoAiMetaDebug('Logout button clicked (jQuery handler)');
			if (typeof window.seoAiMetaLogout === 'function') {
				window.seoAiMetaLogout();
			} else {
				seoAiMetaError('seoAiMetaLogout not available!');
			}
		});

		// Generic upgrade button handler (for any button with upgrade in onclick)
		$(document).on('click', '[onclick*="seoAiMetaShowUpgradeModal"]', function(e) {
			seoAiMetaDebug('Upgrade button clicked (jQuery handler)');
			// Check if function exists - if not, call it ourselves as backup
			if (typeof window.seoAiMetaShowUpgradeModal !== 'function') {
				seoAiMetaError('seoAiMetaShowUpgradeModal not available!');
				e.preventDefault();
				alert('Upgrade modal not ready. Please refresh the page and check console for errors.');
			} else {
				seoAiMetaDebug('seoAiMetaShowUpgradeModal is available, onclick should work');
			}
		});

		seoAiMetaDebug('jQuery click handlers registered');
		seoAiMetaDebug('Total buttons found:', {
			loginButtons: $('.seo-ai-meta-btn-login').length,
			logoutButtons: $('.seo-ai-meta-btn-logout').length,
			upgradeButtons: $('[onclick*="seoAiMetaShowUpgradeModal"]').length
		});
	});

})();
