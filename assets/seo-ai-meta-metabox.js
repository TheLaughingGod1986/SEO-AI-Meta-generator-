/**
 * SEO AI Meta Generator Metabox JavaScript
 */
(function($) {
	'use strict';

	$(document).ready(function() {
		var $generateBtn = $('#seo-ai-meta-generate-btn');
		var $spinner = $('#seo-ai-meta-spinner');
		var $messages = $('#seo-ai-meta-messages');
		var $titleInput = $('#seo-ai-meta-title');
		var $descInput = $('#seo-ai-meta-description');
		var postId = $('#post_ID').val();

		if (!$generateBtn.length || !postId) {
			return;
		}

		// Handle regenerate button
		var $regenerateBtn = $('#seo-ai-meta-regenerate-btn');
		if ($regenerateBtn.length) {
			$regenerateBtn.on('click', function(e) {
				e.preventDefault();
				$generateBtn.trigger('click');
			});
		}

		$generateBtn.on('click', function(e) {
			e.preventDefault();
			
			if ($generateBtn.prop('disabled')) {
				return;
			}

			$generateBtn.prop('disabled', true);
			if ($regenerateBtn.length) {
				$regenerateBtn.prop('disabled', true);
			}
			$spinner.addClass('is-active');
			$messages.html('').removeClass('notice notice-success notice-error');

			$.ajax({
				url: seoAiMetaAjax.ajaxurl,
				type: 'POST',
				data: {
					action: 'seo_ai_meta_generate',
					nonce: seoAiMetaAjax.nonce,
					post_id: postId
				},
				success: function(response) {
					$spinner.removeClass('is-active');
					$generateBtn.prop('disabled', false);
					if ($regenerateBtn.length) {
						$regenerateBtn.prop('disabled', false);
					}

					if (response.success) {
						$titleInput.val(response.data.title);
						$descInput.val(response.data.description);
						
						// Trigger input event to update character counts and preview
						$titleInput.trigger('input');
						$descInput.trigger('input');

						var messageHtml = '<p>' + (response.data.message || 'Meta tags generated successfully!') + '</p>';
						
						// Show duplicate warning if present
						if (response.data.duplicate_warning) {
							var warning = response.data.duplicate_warning;
							messageHtml += '<div style="margin-top: 12px; padding: 12px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">';
							messageHtml += '<strong>' + warning.message + '</strong>';
							if (warning.posts && warning.posts.length > 0) {
								messageHtml += '<ul style="margin: 8px 0 0 20px; padding: 0;">';
								warning.posts.forEach(function(post) {
									messageHtml += '<li><a href="' + post.url + '" target="_blank">' + post.title + ' (ID: ' + post.id + ')</a></li>';
								});
								messageHtml += '</ul>';
							}
							messageHtml += '<p style="margin: 8px 0 0 0; font-size: 12px; color: #856404;">';
							messageHtml += 'Consider editing the meta title to make it unique for better SEO.';
							messageHtml += '</p>';
							messageHtml += '</div>';
						}
						
						$messages.html(messageHtml).addClass('notice notice-success');
						
						// Trigger custom event for undo button
						$(document).trigger('seo-ai-meta-generated');
						
						// Update save button state if Gutenberg
						if (wp && wp.data) {
							wp.data.dispatch('core/editor').markEdited();
						}
					} else {
						var errorMsg = response.data && response.data.message ? response.data.message : 'Failed to generate meta tags.';
						$messages.html('<p><strong>Error:</strong> ' + errorMsg + '</p>').addClass('notice notice-error');
					}
				},
				error: function(xhr, status, error) {
					$spinner.removeClass('is-active');
					$generateBtn.prop('disabled', false);
					if ($regenerateBtn.length) {
						$regenerateBtn.prop('disabled', false);
					}
					$messages.html('<p><strong>Error:</strong> Network error. Please try again.</p>').addClass('notice notice-error');
				}
			});
		});

		// Save meta when post is saved
		$(document).on('submit', '#post', function() {
			// Meta is saved via normal form submission (name attributes)
		});

		// Store previous values for undo functionality
		var previousTitle = $titleInput.val();
		var previousDescription = $descInput.val();

		// Update previous values when user manually edits (but not during generation)
		var isGenerating = false;
		$titleInput.on('input', function() {
			if (!isGenerating) {
				previousTitle = $(this).val();
			}
		});
		$descInput.on('input', function() {
			if (!isGenerating) {
				previousDescription = $(this).val();
			}
		});

		// Store values before generation for undo
		$generateBtn.on('click', function() {
			isGenerating = true;
			previousTitle = $titleInput.val();
			previousDescription = $descInput.val();
		});

		// Add undo button after generation
		function addUndoButton() {
			if ($('#seo-ai-meta-undo-btn').length === 0 && (previousTitle || previousDescription)) {
				var $undoBtn = $('<button>', {
					type: 'button',
					id: 'seo-ai-meta-undo-btn',
					class: 'button button-secondary',
					style: 'margin-left: 8px;',
					text: '↶ Undo'
				});
				$('#seo-ai-meta-generate-btn').after($undoBtn);

				$undoBtn.on('click', function(e) {
					e.preventDefault();
					isGenerating = true;
					if (previousTitle) {
						$titleInput.val(previousTitle).trigger('input');
					}
					if (previousDescription) {
						$descInput.val(previousDescription).trigger('input');
					}
					isGenerating = false;
					$undoBtn.remove();
				});
			}
		}

		// Show undo button after successful generation
		$(document).on('seo-ai-meta-generated', function() {
			isGenerating = false;
			addUndoButton();
		});

		// Copy to clipboard functionality
		$('#seo-ai-meta-copy-title').on('click', function(e) {
			e.preventDefault();
			var title = $titleInput.val();
			if (title) {
				if (navigator.clipboard && navigator.clipboard.writeText) {
					navigator.clipboard.writeText(title).then(function() {
						var $btn = $('#seo-ai-meta-copy-title');
						var originalText = $btn.text();
						$btn.text('✓ Copied!');
						setTimeout(function() {
							$btn.text(originalText);
						}, 2000);
					});
				} else {
					// Fallback for older browsers
					var $temp = $('<textarea>');
					$('body').append($temp);
					$temp.val(title).select();
					document.execCommand('copy');
					$temp.remove();
					var $btn = $('#seo-ai-meta-copy-title');
					var originalText = $btn.text();
					$btn.text('✓ Copied!');
					setTimeout(function() {
						$btn.text(originalText);
					}, 2000);
				}
			}
		});

		$('#seo-ai-meta-copy-description').on('click', function(e) {
			e.preventDefault();
			var desc = $descInput.val();
			if (desc) {
				if (navigator.clipboard && navigator.clipboard.writeText) {
					navigator.clipboard.writeText(desc).then(function() {
						var $btn = $('#seo-ai-meta-copy-description');
						var originalText = $btn.text();
						$btn.text('✓ Copied!');
						setTimeout(function() {
							$btn.text(originalText);
						}, 2000);
					});
				} else {
					// Fallback for older browsers
					var $temp = $('<textarea>');
					$('body').append($temp);
					$temp.val(desc).select();
					document.execCommand('copy');
					$temp.remove();
					var $btn = $('#seo-ai-meta-copy-description');
					var originalText = $btn.text();
					$btn.text('✓ Copied!');
					setTimeout(function() {
						$btn.text(originalText);
					}, 2000);
				}
			}
		});

		// Keyboard shortcut: Ctrl+G or Cmd+G to generate (only when not in input/textarea)
		$(document).on('keydown', function(e) {
			// Check if we're in a post editor and not in a text input/textarea
			if ((e.ctrlKey || e.metaKey) && e.key === 'g' && 
				!$(e.target).is('input, textarea, [contenteditable="true"]')) {
				if ($generateBtn.length && !$generateBtn.prop('disabled')) {
					e.preventDefault();
					$generateBtn.trigger('click');
				}
			}
		});
	});
})(jQuery);

