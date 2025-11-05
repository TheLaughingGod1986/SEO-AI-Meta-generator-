<?php
/**
 * Meta Box Template
 *
 * @var string $meta_title Current meta title.
 * @var string $meta_description Current meta description.
 * @var string $generated_at Generation timestamp.
 * @var string $model Model used.
 * @var int $title_max Maximum title length.
 * @var int $description_max Maximum description length.
 * @var array $usage_stats Usage statistics.
 * @var bool $at_limit Whether user is at limit.
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
?>

<div id="seo-ai-meta-metabox" class="seo-ai-meta-metabox">
	<div class="seo-ai-meta-usage-bar" style="margin-bottom: 15px;">
		<p style="margin: 0 0 5px 0;">
			<strong><?php esc_html_e( 'Usage:', 'seo-ai-meta-generator' ); ?></strong>
			<?php echo esc_html( $usage_stats['used'] ); ?> / <?php echo esc_html( $usage_stats['limit'] ); ?>
			(<?php echo esc_html( $usage_stats['remaining'] ); ?> remaining)
			<span class="seo-ai-meta-plan-badge" style="background: #0073aa; color: white; padding: 2px 8px; border-radius: 3px; margin-left: 5px;">
				<?php echo esc_html( $usage_stats['plan_label'] ); ?>
			</span>
		</p>
		<div style="background: #ddd; height: 20px; border-radius: 3px; overflow: hidden;">
			<div style="background: <?php echo esc_attr( $usage_stats['percentage'] >= 80 ? '#dc3232' : '#46b450' ); ?>; height: 100%; width: <?php echo esc_attr( $usage_stats['percentage'] ); ?>%; transition: width 0.3s;"></div>
		</div>
	</div>

	<?php if ( $at_limit ) : ?>
		<div class="seo-ai-meta-limit-notice" style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 12px; margin-bottom: 15px;">
			<p style="margin: 0;">
				<strong><?php esc_html_e( 'Limit Reached', 'seo-ai-meta-generator' ); ?></strong><br>
				<?php esc_html_e( 'You have reached your monthly limit. Please upgrade your plan to generate more meta tags.', 'seo-ai-meta-generator' ); ?>
				<button type="button" class="button button-primary" onclick="seoAiMetaShowModal();" style="margin-left: 10px;">
					<?php esc_html_e( 'Upgrade Now', 'seo-ai-meta-generator' ); ?>
				</button>
			</p>
		</div>
	<?php endif; ?>

	<div class="seo-ai-meta-actions" style="margin-bottom: 15px;">
		<button type="button" id="seo-ai-meta-generate-btn" class="button button-primary" <?php echo $at_limit ? 'disabled' : ''; ?>>
			<?php echo $meta_title ? esc_html__( 'Regenerate Meta', 'seo-ai-meta-generator' ) : esc_html__( 'Generate Meta', 'seo-ai-meta-generator' ); ?>
		</button>
		<span class="spinner" id="seo-ai-meta-spinner" style="float: none; margin-left: 10px;"></span>
		<div id="seo-ai-meta-messages" style="margin-top: 10px;"></div>
		
		<?php if ( ! empty( $meta_title ) || ! empty( $meta_description ) ) : ?>
			<div style="margin-top: 10px;">
				<button type="button" id="seo-ai-meta-regenerate-btn" class="button button-secondary" style="margin-left: 8px;">
					<?php esc_html_e( '🔄 Regenerate', 'seo-ai-meta-generator' ); ?>
				</button>
				<span class="description" style="margin-left: 8px; color: #666;">
					<?php esc_html_e( 'Generate new meta tags to improve SEO', 'seo-ai-meta-generator' ); ?>
				</span>
			</div>
		<?php endif; ?>
	</div>

	<!-- SEO Score Indicator -->
	<?php if ( ! empty( $meta_title ) || ! empty( $meta_description ) ) : ?>
		<?php
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-validator.php';
		$seo_score = SEO_AI_Meta_Validator::calculate_seo_score( $meta_title, $meta_description, $title_max, $description_max );
		$score_color = $seo_score >= 80 ? '#22c55e' : ( $seo_score >= 60 ? '#f59e0b' : '#ef4444' );
		$score_label = $seo_score >= 80 ? __( 'Excellent', 'seo-ai-meta-generator' ) : ( $seo_score >= 60 ? __( 'Good', 'seo-ai-meta-generator' ) : __( 'Needs Work', 'seo-ai-meta-generator' ) );
		?>
		<div class="seo-ai-meta-score-indicator" style="margin-bottom: 20px; padding: 16px; background: white; border: 1px solid #e5e7eb; border-radius: 8px; display: flex; align-items: center; gap: 16px;">
			<div style="text-align: center;">
				<div style="font-size: 32px; font-weight: 700; color: <?php echo esc_attr( $score_color ); ?>; line-height: 1;">
					<?php echo esc_html( $seo_score ); ?>
				</div>
				<div style="font-size: 11px; color: #6b7280; margin-top: 4px;">
					<?php echo esc_html( $score_label ); ?>
				</div>
			</div>
			<div style="flex: 1;">
				<div style="font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 8px;">
					<?php esc_html_e( 'SEO Score', 'seo-ai-meta-generator' ); ?>
				</div>
				<div style="background: #e5e7eb; height: 8px; border-radius: 4px; overflow: hidden;">
					<div style="background: <?php echo esc_attr( $score_color ); ?>; height: 100%; width: <?php echo esc_attr( $seo_score ); ?>%; transition: width 0.3s;"></div>
				</div>
				<div style="font-size: 11px; color: #6b7280; margin-top: 4px;">
					<?php
					$tips = array();
					if ( strlen( $meta_title ) < 30 ) {
						$tips[] = __( 'Title too short', 'seo-ai-meta-generator' );
					} elseif ( strlen( $meta_title ) > $title_max ) {
						$tips[] = __( 'Title too long', 'seo-ai-meta-generator' );
					}
					if ( strlen( $meta_description ) < 120 ) {
						$tips[] = __( 'Description too short', 'seo-ai-meta-generator' );
					} elseif ( strlen( $meta_description ) > $description_max ) {
						$tips[] = __( 'Description too long', 'seo-ai-meta-generator' );
					}
					if ( empty( $tips ) ) {
						$tips[] = __( 'Well optimized!', 'seo-ai-meta-generator' );
					}
					echo esc_html( implode( ' • ', $tips ) );
					?>
				</div>
			</div>
		</div>
	<?php endif; ?>

	<div class="seo-ai-meta-preview">
		<div style="margin-bottom: 15px;">
			<label for="seo-ai-meta-title" style="display: block; margin-bottom: 5px;">
				<strong><?php esc_html_e( 'Meta Title', 'seo-ai-meta-generator' ); ?></strong>
				<span id="seo-ai-meta-title-count" style="color: #666; margin-left: 10px;">0 / <?php echo esc_html( $title_max ); ?></span>
				<button type="button" class="button button-small" id="seo-ai-meta-copy-title" style="margin-left: 8px; padding: 2px 8px; font-size: 11px;" title="<?php esc_attr_e( 'Copy to clipboard', 'seo-ai-meta-generator' ); ?>">
					📋 <?php esc_html_e( 'Copy', 'seo-ai-meta-generator' ); ?>
				</button>
			</label>
			<input type="text" 
				id="seo-ai-meta-title" 
				name="seo_ai_meta_title" 
				value="<?php echo esc_attr( $meta_title ); ?>" 
				class="large-text" 
				maxlength="<?php echo esc_attr( $title_max ); ?>"
				placeholder="<?php esc_attr_e( 'Generated meta title will appear here...', 'seo-ai-meta-generator' ); ?>"
			/>
			<p class="description">
				<?php esc_html_e( 'Recommended: 50-60 characters. This will be used in search engine results.', 'seo-ai-meta-generator' ); ?>
			</p>
		</div>

		<div>
			<label for="seo-ai-meta-description" style="display: block; margin-bottom: 5px;">
				<strong><?php esc_html_e( 'Meta Description', 'seo-ai-meta-generator' ); ?></strong>
				<span id="seo-ai-meta-description-count" style="color: #666; margin-left: 10px;">0 / <?php echo esc_html( $description_max ); ?></span>
				<button type="button" class="button button-small" id="seo-ai-meta-copy-description" style="margin-left: 8px; padding: 2px 8px; font-size: 11px;" title="<?php esc_attr_e( 'Copy to clipboard', 'seo-ai-meta-generator' ); ?>">
					📋 <?php esc_html_e( 'Copy', 'seo-ai-meta-generator' ); ?>
				</button>
			</label>
			<textarea 
				id="seo-ai-meta-description" 
				name="seo_ai_meta_description" 
				class="large-text" 
				rows="3"
				maxlength="<?php echo esc_attr( $description_max ); ?>"
				placeholder="<?php esc_attr_e( 'Generated meta description will appear here...', 'seo-ai-meta-generator' ); ?>"
			><?php echo esc_textarea( $meta_description ); ?></textarea>
			<p class="description">
				<?php esc_html_e( 'Recommended: 150-160 characters. This will appear as the snippet in search engine results.', 'seo-ai-meta-generator' ); ?>
			</p>
		</div>
	</div>

	<!-- SEO Preview (Google Search Result Preview) -->
	<?php if ( ! empty( $meta_title ) || ! empty( $meta_description ) ) : ?>
	<div class="seo-ai-meta-preview-box" style="margin-top: 20px; padding: 20px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px;">
		<h3 style="margin: 0 0 15px 0; font-size: 14px; font-weight: 600; color: #374151;">
			<?php esc_html_e( 'Search Engine Preview', 'seo-ai-meta-generator' ); ?>
			<span style="font-weight: 400; color: #6b7280; font-size: 12px; margin-left: 8px;">(How it appears in Google)</span>
		</h3>
		<div class="seo-ai-meta-google-preview" style="max-width: 600px;">
			<div class="seo-ai-meta-preview-url" style="color: #202124; font-size: 14px; line-height: 1.3; margin-bottom: 3px;">
				<?php
				$site_url = home_url();
				$site_domain = wp_parse_url( $site_url, PHP_URL_HOST );
				$preview_url = str_replace( array( 'http://', 'https://', 'www.' ), '', $site_domain );
				$preview_path = get_permalink( get_the_ID() );
				if ( $preview_path ) {
					$preview_path = str_replace( home_url(), '', $preview_path );
					$preview_path = ltrim( $preview_path, '/' );
				} else {
					$preview_path = 'your-post-slug';
				}
				?>
				<span style="color: #202124;"><?php echo esc_html( $preview_url ); ?></span>
				<span style="color: #5f6368;"> › <?php echo esc_html( $preview_path ); ?></span>
			</div>
			<div class="seo-ai-meta-preview-title" id="seo-ai-meta-preview-title" style="color: #1a0dab; font-size: 20px; line-height: 1.3; font-weight: 400; margin-bottom: 3px; cursor: pointer; text-decoration: none; display: block;">
				<?php echo esc_html( ! empty( $meta_title ) ? $meta_title : get_the_title() ); ?>
			</div>
			<div class="seo-ai-meta-preview-description" id="seo-ai-meta-preview-description" style="color: #4d5156; font-size: 14px; line-height: 1.58; word-wrap: break-word;">
				<?php
				$preview_desc = ! empty( $meta_description ) ? $meta_description : wp_trim_words( get_the_excerpt() ? get_the_excerpt() : get_the_content(), 25 );
				echo esc_html( $preview_desc );
				?>
			</div>
		</div>
		<p style="margin: 12px 0 0 0; font-size: 12px; color: #6b7280;">
			<?php esc_html_e( 'This is an approximation. Actual search results may vary.', 'seo-ai-meta-generator' ); ?>
		</p>
	</div>
	<?php endif; ?>

	<?php if ( $generated_at ) : ?>
		<div class="seo-ai-meta-meta-info" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd; color: #666; font-size: 12px;">
			<?php
			printf(
				/* translators: %1$s: Date, %2$s: Model */
				esc_html__( 'Generated on %1$s using %2$s', 'seo-ai-meta-generator' ),
				esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $generated_at ) ) ),
				esc_html( $model ?: 'gpt-4o-mini' )
			);
			?>
		</div>
	<?php endif; ?>

	<script>
	// Update character counts, live preview, and SEO score
	(function($) {
		function updateCounts() {
			var titleLength = $('#seo-ai-meta-title').val().length;
			var descLength = $('#seo-ai-meta-description').val().length;
			$('#seo-ai-meta-title-count').text(titleLength + ' / <?php echo esc_js( $title_max ); ?>');
			$('#seo-ai-meta-description-count').text(descLength + ' / <?php echo esc_js( $description_max ); ?>');
			
			// Update live preview
			var title = $('#seo-ai-meta-title').val();
			var desc = $('#seo-ai-meta-description').val();
			
			if ($('#seo-ai-meta-preview-title').length) {
				$('#seo-ai-meta-preview-title').text(title || '<?php echo esc_js( get_the_title() ); ?>');
			}
			if ($('#seo-ai-meta-preview-description').length) {
				$('#seo-ai-meta-preview-description').text(desc || '<?php echo esc_js( wp_trim_words( get_the_excerpt() ? get_the_excerpt() : get_the_content(), 25 ) ); ?>');
			}
			
			// Show/hide preview box
			if (title || desc) {
				$('.seo-ai-meta-preview-box').show();
			} else {
				$('.seo-ai-meta-preview-box').hide();
			}

			// Update SEO score indicator if it exists
			if ($('.seo-ai-meta-score-indicator').length && (title || desc)) {
				// Calculate simple score client-side
				var score = 100;
				var titleMax = <?php echo esc_js( $title_max ); ?>;
				var descMax = <?php echo esc_js( $description_max ); ?>;
				
				if (titleLength < 30) score -= 20;
				else if (titleLength > titleMax) score -= 30;
				else if (titleLength > 50) score -= 5;
				
				if (descLength < 120) score -= 15;
				else if (descLength > descMax) score -= 30;
				else if (descLength > 155) score -= 5;
				
				score = Math.max(0, Math.min(100, score));
				
				var scoreColor = score >= 80 ? '#22c55e' : (score >= 60 ? '#f59e0b' : '#ef4444');
				var scoreLabel = score >= 80 ? 'Excellent' : (score >= 60 ? 'Good' : 'Needs Work');
				
				$('.seo-ai-meta-score-indicator').find('div').first().find('div').first().css('color', scoreColor).text(Math.round(score));
				$('.seo-ai-meta-score-indicator').find('div').first().find('div').last().text(scoreLabel);
				$('.seo-ai-meta-score-indicator').find('div').last().find('div').eq(1).find('div').css('background', scoreColor).css('width', score + '%');
			}
		}
		
		$('#seo-ai-meta-title, #seo-ai-meta-description').on('input', updateCounts);
		updateCounts();
	})(jQuery);
	</script>
</div>

