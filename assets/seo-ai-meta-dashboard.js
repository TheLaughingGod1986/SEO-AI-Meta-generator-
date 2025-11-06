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

		// Bulk generate button (old - for selected posts)
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

		// Generate All button (new - for all pending posts)
		$('#seo-ai-meta-bulk-generate-all-btn').on('click', function(e) {
			e.preventDefault();
			
			var $btn = $(this);
			var $logContainer = $('#seo-ai-meta-bulk-log-container');
			var $log = $('#seo-ai-meta-bulk-log');
			var $successContainer = $('#seo-ai-meta-bulk-success');
			var $successLog = $('#seo-ai-meta-bulk-success-log');
			var $progressRing = $('#bulk-progress-ring');
			var $previewBtn = $('#seo-ai-meta-bulk-preview-btn');
			
			// Disable button and show loading state
			$btn.prop('disabled', true).text('Generating...');
			$logContainer.removeClass('hidden');
			$log.html('');
			$successContainer.addClass('hidden');
			$successLog.html('');
			
			// Get all pending posts
			$.ajax({
				url: seoAiMetaAjax.ajaxurl,
				type: 'POST',
				data: {
					action: 'seo_ai_meta_get_all_pending_posts',
					nonce: seoAiMetaAjax.bulk_nonce
				},
				success: function(response) {
					if (response.success && response.data.post_ids && response.data.post_ids.length > 0) {
						var postIds = response.data.post_ids;
						var posts = response.data.posts || [];
						var total = postIds.length;
						var processed = 0;
						var failed = 0;
						
						// Helper function to format timestamp
						function getTimestamp() {
							var now = new Date();
							return now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
						}
						
						// Helper function to add log entry
						function addLogEntry(message, type) {
							var icon = '';
							if (type === 'success') {
								icon = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2" class="flex-shrink-0"><path d="M20 6L9 17l-5-5"/></svg>';
							} else if (type === 'processing') {
								icon = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2" class="flex-shrink-0 animate-spin"><circle cx="12" cy="12" r="10"/><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>';
							} else {
								icon = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" class="flex-shrink-0"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';
							}
							
							var entry = $('<div class="flex items-center gap-3 text-sm text-gray-700 py-1.5"></div>');
							entry.html(icon + '<span>' + message + '</span><span class="ml-auto text-xs text-gray-500">' + getTimestamp() + '</span>');
							$log.append(entry);
							
							// Auto-scroll to bottom
							$log.scrollTop($log[0].scrollHeight);
						}
						
						// Get initial counts from the progress ring display
						var countText = $progressRing.closest('.relative').find('.text-3xl').text();
						var counts = countText.split('/');
						var initialOptimized = parseInt(counts[0]) || 0;
						var initialTotal = parseInt(counts[1]) || total;
						
						// Helper function to update progress ring
						function updateProgressRing(processedCount) {
							var newOptimized = initialOptimized + processedCount;
							var finalTotal = initialTotal; // Total stays the same
							var percentage = finalTotal > 0 ? Math.round((newOptimized / finalTotal) * 100) : 0;
							var radius = 56;
							var circumference = 2 * Math.PI * radius;
							var offset = circumference * (1 - (percentage / 100));
							
							$progressRing.css({
								'stroke-dashoffset': offset,
								'transition': 'stroke-dashoffset 0.3s ease'
							});
							
							// Update text
							var $countContainer = $progressRing.closest('.relative').find('.text-3xl').parent();
							$countContainer.find('.text-3xl').text(newOptimized + '/' + finalTotal);
						}
						
						// Process posts one by one
						function processNext(index) {
							if (index >= total) {
								// All done
								$btn.prop('disabled', false).text('Generate All');
								
								// Show success state
								if (processed === total && failed === 0) {
									$logContainer.addClass('hidden');
									$successContainer.removeClass('hidden');
									
									// Add success animation
									setTimeout(function() {
										$successContainer.find('.seo-ai-meta-success-animation').addClass('animate-bounce');
										setTimeout(function() {
											$successContainer.find('.seo-ai-meta-success-animation').removeClass('animate-bounce');
										}, 1000);
									}, 100);
									
									// Move all log entries to success log
									$log.find('div').each(function() {
										var $entry = $(this).clone();
										$successLog.append($entry);
									});
									
									// Update progress ring to 100%
									updateProgressRing(processed);
									
									// Reload page after 3 seconds to show updated state
									setTimeout(function() {
										location.reload();
									}, 3000);
								} else {
									addLogEntry('Completed: ' + processed + ' of ' + total + ' posts processed' + (failed > 0 ? ' (' + failed + ' failed)' : ''), 'success');
									$previewBtn.removeClass('hidden');
								}
								return;
							}
							
							var postId = postIds[index];
							var postTitle = '';
							for (var i = 0; i < posts.length; i++) {
								if (posts[i].id == postId) {
									postTitle = posts[i].title;
									break;
								}
							}
							
							// Add processing log entry
							addLogEntry('Optimizing post ' + (index + 1) + ' of ' + total + '...', 'processing');
							
							// Generate meta for this post
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
										// Update last log entry to success
										var $lastEntry = $log.find('div').last();
										$lastEntry.html('<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2" class="flex-shrink-0"><path d="M20 6L9 17l-5-5"/></svg><span>Done</span><span class="ml-auto text-xs text-gray-500">' + getTimestamp() + '</span>');
										updateProgressRing(processed, total);
									} else {
										failed++;
										// Update last log entry to error
										var $lastEntry = $log.find('div').last();
										$lastEntry.html('<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" class="flex-shrink-0"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg><span>Failed</span><span class="ml-auto text-xs text-gray-500">' + getTimestamp() + '</span>');
									}
									
									// Process next
									setTimeout(function() {
										processNext(index + 1);
									}, 500); // Small delay between requests
								},
								error: function(xhr, status, error) {
									failed++;
									// Update last log entry to error
									var $lastEntry = $log.find('div').last();
									$lastEntry.html('<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" class="flex-shrink-0"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg><span>Failed</span><span class="ml-auto text-xs text-gray-500">' + getTimestamp() + '</span>');
									
									// Continue processing even on error
									setTimeout(function() {
										processNext(index + 1);
									}, 500);
								}
							});
						}
						
						// Start processing
						processNext(0);
					} else {
						alert('No posts found without meta tags.');
						$btn.prop('disabled', false).text('Generate All');
					}
				},
				error: function() {
					alert('Failed to load pending posts. Please try again.');
					$btn.prop('disabled', false).text('Generate All');
				}
			});
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

