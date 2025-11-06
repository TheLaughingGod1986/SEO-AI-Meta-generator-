/**
 * SEO AI Meta Generator Dashboard JavaScript
 */
(function($) {
	'use strict';

	$(document).ready(function() {
		// Load subscription info if on account tab
		if ($('#seo-ai-meta-subscription-info').length) {
			loadSubscriptionInfo();
		}

		// Handle billing portal button
		$(document).on('click', '#seo-ai-meta-open-portal', function(e) {
			e.preventDefault();
			openCustomerPortal();
		});

		// Clear activity link handler
		$(document).on('click', '#seo-ai-meta-clear-activity', function(e) {
			e.preventDefault();
			if (confirm('Are you sure you want to clear completed activity? This will hide completed items from the recent activity list.')) {
				// For now, just hide the activity items
				// In future, you could add an AJAX call to clear them from the database
				$('.seo-ai-meta-activity-item').fadeOut(300, function() {
					$(this).remove();
					if ($('.seo-ai-meta-activity-item').length === 0) {
						$('.seo-ai-meta-activity-list').html('<p class="seo-ai-meta-no-activity">No recent activity.</p>');
						$('#seo-ai-meta-clear-activity').hide();
					}
				});
			}
		});

		// Animate progress bars on page load
		setTimeout(function() {
			$('.seo-ai-meta-progress-animated').each(function() {
				var $bar = $(this);
				var percentage = $bar.data('percentage');
				if (percentage !== undefined && percentage !== null) {
					$bar.css('width', percentage + '%');
				}
			});
		}, 150);

		// Animate circular progress ring on page load
		setTimeout(function() {
			var $ring = $('#seo-ai-meta-progress-ring');
			if ($ring.length) {
				var radius = 56; // Updated radius
				var circumference = 2 * Math.PI * radius;
				var percentage = parseFloat($ring.attr('data-percentage') || $ring.closest('[data-percentage]').data('percentage') || 0);
				var offset = circumference * (1 - (percentage / 100));
				
				// Set initial state (0%)
				$ring.css({
					'stroke-dasharray': circumference,
					'stroke-dashoffset': circumference
				});
				
				// Animate to actual percentage
				setTimeout(function() {
					$ring.css({
						'stroke-dashoffset': offset,
						'transition': 'stroke-dashoffset 0.8s cubic-bezier(0.4, 0, 0.2, 1)'
					});
				}, 100);
			}
		}, 200);

		// Bulk generate functionality (for tab)
		// Select all checkbox
		$('#seo-ai-meta-select-all').on('change', function() {
			$('.seo-ai-meta-post-checkbox').prop('checked', $(this).prop('checked'));
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
				alert('Please select at least one post.');
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
							if (typeof seoAiMetaAjax !== 'undefined' && seoAiMetaAjax.debug) {
							console.error('Generation failed for post', postId, ':', response.data);
							}
						}
						
						// Process next
						setTimeout(function() {
							processNext(index + 1);
						}, 600); // Small delay between requests
					},
					error: function(xhr, status, error) {
						if (typeof seoAiMetaAjax !== 'undefined' && seoAiMetaAjax.debug) {
						console.error('AJAX Error for post', postId, ':', status, error);
						console.error('Response:', xhr.responseText);
						}
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

	function loadSubscriptionInfo() {
		var $container = $('#seo-ai-meta-subscription-info');

		$.ajax({
			url: seoAiMetaAjax.ajaxurl,
			type: 'POST',
			data: {
				action: 'seo_ai_meta_get_subscription',
				nonce: seoAiMetaAjax.nonce
			},
			success: function(response) {
				if (response.success) {
					var data = response.data;
					var html = '<div class="seo-ai-meta-subscription-details">';
					
					if (data.plan === 'free' || data.status === 'free') {
						html += '<p><strong>Plan:</strong> Free</p>';
						html += '<p>Upgrade to unlock more features!</p>';
					} else {
						html += '<p><strong>Plan:</strong> ' + (data.plan ? data.plan.charAt(0).toUpperCase() + data.plan.slice(1) : 'Unknown') + '</p>';
						html += '<p><strong>Status:</strong> ' + (data.status ? data.status.charAt(0).toUpperCase() + data.status.slice(1) : 'Active') + '</p>';
						if (data.nextBillingDate) {
							var date = new Date(data.nextBillingDate);
							html += '<p><strong>Next Billing:</strong> ' + date.toLocaleDateString() + '</p>';
						}
					}
					
					html += '</div>';
					$container.html(html);
				} else {
					$container.html('<p class="error">' + (response.data.message || 'Failed to load subscription info') + '</p>');
				}
			},
			error: function() {
				$container.html('<p class="error">Network error. Please try again.</p>');
			}
		});
	}

	function openCustomerPortal() {
		var $btn = $('#seo-ai-meta-open-portal');
		$btn.prop('disabled', true).text('Loading...');

		$.ajax({
			url: seoAiMetaAjax.ajaxurl,
			type: 'POST',
			data: {
				action: 'seo_ai_meta_open_portal',
				nonce: seoAiMetaAjax.nonce
			},
			success: function(response) {
				if (response.success && response.data.url) {
					window.open(response.data.url, '_blank');
				} else {
					alert('Failed to open billing portal. Please try again.');
				}
				$btn.prop('disabled', false).text('Manage Subscription');
			},
			error: function() {
				alert('Network error. Please try again.');
				$btn.prop('disabled', false).text('Manage Subscription');
			}
		});
	}
})(jQuery);

