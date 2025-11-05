<?php
/**
 * SEO Validator for Meta Tags
 * Validates generated meta titles and descriptions for SEO performance
 *
 * @package    SEO_AI_Meta
 * @subpackage SEO_AI_Meta/includes
 */
class SEO_AI_Meta_Validator {

	/**
	 * Extract keywords from post content
	 *
	 * @param string $content Post content.
	 * @param int    $limit   Maximum number of keywords to return.
	 * @return array Array of keywords with their frequency.
	 */
	public static function extract_keywords_from_content( $content, $limit = 5 ) {
		// Remove HTML tags
		$content = wp_strip_all_tags( $content );
		
		// Convert to lowercase
		$content = mb_strtolower( $content );
		
		// Remove common stop words
		$stop_words = array(
			'the', 'be', 'to', 'of', 'and', 'a', 'in', 'that', 'have', 'i',
			'it', 'for', 'not', 'on', 'with', 'he', 'as', 'you', 'do', 'at',
			'this', 'but', 'his', 'by', 'from', 'they', 'we', 'say', 'her', 'she',
			'or', 'an', 'will', 'my', 'one', 'all', 'would', 'there', 'their',
			'what', 'so', 'up', 'out', 'if', 'about', 'who', 'get', 'which', 'go',
			'me', 'when', 'make', 'can', 'like', 'time', 'no', 'just', 'him', 'know',
			'take', 'people', 'into', 'year', 'your', 'good', 'some', 'could', 'them',
			'see', 'other', 'than', 'then', 'now', 'look', 'only', 'come', 'its',
			'over', 'think', 'also', 'back', 'after', 'use', 'two', 'how', 'our',
			'work', 'first', 'well', 'way', 'even', 'new', 'want', 'because', 'any',
			'these', 'give', 'day', 'most', 'us', 'is', 'are', 'was', 'were'
		);
		
		// Extract words (minimum 3 characters)
		preg_match_all( '/\b[a-z]{3,}\b/', $content, $matches );
		$words = $matches[0];
		
		// Filter out stop words
		$words = array_filter( $words, function( $word ) use ( $stop_words ) {
			return ! in_array( $word, $stop_words, true );
		} );
		
		// Count frequency
		$word_freq = array_count_values( $words );
		
		// Sort by frequency (descending)
		arsort( $word_freq );
		
		// Get top keywords
		$keywords = array_slice( array_keys( $word_freq ), 0, $limit );
		
		return $keywords;
	}

	/**
	 * Validate meta title for SEO
	 *
	 * @param string $title     Meta title.
	 * @param string $keyword   Focus keyword (optional).
	 * @param array  $keywords  Additional keywords (optional).
	 * @param int    $max_length Maximum length.
	 * @return array Validation results with score and issues.
	 */
	public static function validate_title( $title, $keyword = '', $keywords = array(), $max_length = 60 ) {
		$score = 100;
		$issues = array();
		$suggestions = array();
		
		$length = mb_strlen( $title );
		$title_lower = mb_strtolower( $title );
		
		// Check length
		if ( $length < 30 ) {
			$score -= 20;
			$issues[] = __( 'Title is too short (recommended: 30-60 characters)', 'seo-ai-meta-generator' );
		} elseif ( $length > $max_length ) {
			$score -= 30;
			$issues[] = sprintf( __( 'Title is too long (%d characters, max: %d)', 'seo-ai-meta-generator' ), $length, $max_length );
		} elseif ( $length > 50 && $length <= $max_length ) {
			$score -= 5;
			$issues[] = __( 'Title is slightly long (recommended: 30-60 characters)', 'seo-ai-meta-generator' );
		}
		
		// Check keyword usage
		if ( ! empty( $keyword ) ) {
			$keyword_lower = mb_strtolower( $keyword );
			if ( strpos( $title_lower, $keyword_lower ) === false ) {
				$score -= 15;
				$issues[] = sprintf( __( 'Focus keyword "%s" not found in title', 'seo-ai-meta-generator' ), $keyword );
				$suggestions[] = sprintf( __( 'Consider including "%s" in the title', 'seo-ai-meta-generator' ), $keyword );
			} else {
				// Check keyword position (prefer at the beginning)
				$position = strpos( $title_lower, $keyword_lower );
				if ( $position > 30 ) {
					$score -= 5;
					$issues[] = __( 'Focus keyword should be closer to the beginning of the title', 'seo-ai-meta-generator' );
				}
			}
		}
		
		// Check for power words
		$power_words = array( 'best', 'ultimate', 'guide', 'tips', 'how', 'why', 'what', 'top', 'complete', 'essential' );
		$has_power_word = false;
		foreach ( $power_words as $power_word ) {
			if ( strpos( $title_lower, $power_word ) !== false ) {
				$has_power_word = true;
				break;
			}
		}
		if ( ! $has_power_word ) {
			$score -= 5;
		}
		
		// Check for numbers (improves CTR)
		if ( ! preg_match( '/\d/', $title ) ) {
			$score -= 5;
		}
		
		// Check for questions (can improve CTR)
		if ( ! preg_match( '/\?/', $title ) ) {
			// Not an issue, but questions can be effective
		}
		
		// Ensure score doesn't go below 0
		$score = max( 0, $score );
		
		// Determine grade
		$grade = 'A';
		if ( $score < 70 ) {
			$grade = 'D';
		} elseif ( $score < 80 ) {
			$grade = 'C';
		} elseif ( $score < 90 ) {
			$grade = 'B';
		}
		
		return array(
			'score'       => $score,
			'grade'       => $grade,
			'issues'      => $issues,
			'suggestions' => $suggestions,
			'length'      => $length,
		);
	}

	/**
	 * Validate meta description for SEO
	 *
	 * @param string $description Meta description.
	 * @param string $keyword     Focus keyword (optional).
	 * @param array  $keywords    Additional keywords (optional).
	 * @param int    $max_length  Maximum length.
	 * @return array Validation results with score and issues.
	 */
	public static function validate_description( $description, $keyword = '', $keywords = array(), $max_length = 160 ) {
		$score = 100;
		$issues = array();
		$suggestions = array();
		
		$length = mb_strlen( $description );
		$desc_lower = mb_strtolower( $description );
		
		// Check length
		if ( $length < 120 ) {
			$score -= 15;
			$issues[] = __( 'Description is too short (recommended: 120-160 characters)', 'seo-ai-meta-generator' );
		} elseif ( $length > $max_length ) {
			$score -= 30;
			$issues[] = sprintf( __( 'Description is too long (%d characters, max: %d)', 'seo-ai-meta-generator' ), $length, $max_length );
		} elseif ( $length > 155 && $length <= $max_length ) {
			$score -= 5;
			$issues[] = __( 'Description may be truncated in search results (recommended: 120-160 characters)', 'seo-ai-meta-generator' );
		}
		
		// Check keyword usage
		if ( ! empty( $keyword ) ) {
			$keyword_lower = mb_strtolower( $keyword );
			if ( strpos( $desc_lower, $keyword_lower ) === false ) {
				$score -= 15;
				$issues[] = sprintf( __( 'Focus keyword "%s" not found in description', 'seo-ai-meta-generator' ), $keyword );
				$suggestions[] = sprintf( __( 'Consider including "%s" in the description', 'seo-ai-meta-generator' ), $keyword );
			}
		}
		
		// Check for call-to-action
		$cta_words = array( 'learn', 'discover', 'explore', 'get', 'find', 'start', 'try', 'read', 'see', 'view' );
		$has_cta = false;
		foreach ( $cta_words as $cta_word ) {
			if ( strpos( $desc_lower, $cta_word ) !== false ) {
				$has_cta = true;
				break;
			}
		}
		if ( ! $has_cta ) {
			$score -= 10;
			$issues[] = __( 'Description lacks a clear call-to-action', 'seo-ai-meta-generator' );
		}
		
		// Check for value proposition
		$value_words = array( 'benefit', 'advantage', 'improve', 'enhance', 'better', 'best', 'top', 'essential', 'complete' );
		$has_value = false;
		foreach ( $value_words as $value_word ) {
			if ( strpos( $desc_lower, $value_word ) !== false ) {
				$has_value = true;
				break;
			}
		}
		if ( ! $has_value ) {
			$score -= 5;
		}
		
		// Check sentence structure (should be readable)
		$sentences = preg_split( '/[.!?]+/', $description );
		$avg_sentence_length = 0;
		foreach ( $sentences as $sentence ) {
			$avg_sentence_length += mb_strlen( trim( $sentence ) );
		}
		$avg_sentence_length = count( $sentences ) > 0 ? $avg_sentence_length / count( $sentences ) : 0;
		
		if ( $avg_sentence_length > 100 ) {
			$score -= 5;
			$issues[] = __( 'Sentences are too long (affects readability)', 'seo-ai-meta-generator' );
		}
		
		// Ensure score doesn't go below 0
		$score = max( 0, $score );
		
		// Determine grade
		$grade = 'A';
		if ( $score < 70 ) {
			$grade = 'D';
		} elseif ( $score < 80 ) {
			$grade = 'C';
		} elseif ( $score < 90 ) {
			$grade = 'B';
		}
		
		return array(
			'score'       => $score,
			'grade'      => $grade,
			'issues'     => $issues,
			'suggestions' => $suggestions,
			'length'     => $length,
		);
	}

	/**
	 * Get overall SEO score for meta tags
	 *
	 * @param string $title       Meta title.
	 * @param string $description Meta description.
	 * @param string $keyword     Focus keyword (optional).
	 * @param array  $keywords    Additional keywords (optional).
	 * @return array Overall validation results.
	 */
	public static function validate_seo( $title, $description, $keyword = '', $keywords = array(), $title_max = 60, $desc_max = 160 ) {
		$title_validation = self::validate_title( $title, $keyword, $keywords, $title_max );
		$desc_validation = self::validate_description( $description, $keyword, $keywords, $desc_max );
		
		// Calculate overall score (weighted: title 60%, description 40%)
		$overall_score = ( $title_validation['score'] * 0.6 ) + ( $desc_validation['score'] * 0.4 );
		
		// Determine overall grade
		$overall_grade = 'A';
		if ( $overall_score < 70 ) {
			$overall_grade = 'D';
		} elseif ( $overall_score < 80 ) {
			$overall_grade = 'C';
		} elseif ( $overall_score < 90 ) {
			$overall_grade = 'B';
		}
		
		return array(
			'overall_score' => round( $overall_score, 1 ),
			'overall_grade' => $overall_grade,
			'title'         => $title_validation,
			'description'   => $desc_validation,
			'all_issues'    => array_merge( $title_validation['issues'], $desc_validation['issues'] ),
			'all_suggestions' => array_merge( $title_validation['suggestions'], $desc_validation['suggestions'] ),
		);
	}

	/**
	 * Calculate simple SEO score (for quick display)
	 *
	 * @param string $title       Meta title.
	 * @param string $description Meta description.
	 * @param int    $title_max   Maximum title length.
	 * @param int    $desc_max    Maximum description length.
	 * @return int Score from 0-100.
	 */
	public static function calculate_seo_score( $title, $description, $title_max = 60, $desc_max = 160 ) {
		$title_length = mb_strlen( $title );
		$desc_length = mb_strlen( $description );
		$score = 100;

		// Title length scoring
		if ( $title_length < 30 ) {
			$score -= 20;
		} elseif ( $title_length > $title_max ) {
			$score -= 30;
		} elseif ( $title_length > 50 ) {
			$score -= 5;
		}

		// Description length scoring
		if ( $desc_length < 120 ) {
			$score -= 15;
		} elseif ( $desc_length > $desc_max ) {
			$score -= 30;
		} elseif ( $desc_length > 155 ) {
			$score -= 5;
		}

		// Check for power words in title
		$power_words = array( 'best', 'ultimate', 'guide', 'tips', 'how', 'why', 'what', 'top', 'complete', 'essential' );
		$has_power_word = false;
		$title_lower = mb_strtolower( $title );
		foreach ( $power_words as $power_word ) {
			if ( strpos( $title_lower, $power_word ) !== false ) {
				$has_power_word = true;
				break;
			}
		}
		if ( ! $has_power_word ) {
			$score -= 5;
		}

		// Check for CTA in description
		$cta_words = array( 'learn', 'discover', 'explore', 'get', 'find', 'start', 'try', 'read' );
		$has_cta = false;
		$desc_lower = mb_strtolower( $description );
		foreach ( $cta_words as $cta_word ) {
			if ( strpos( $desc_lower, $cta_word ) !== false ) {
				$has_cta = true;
				break;
			}
		}
		if ( ! $has_cta ) {
			$score -= 10;
		}

		return max( 0, min( 100, $score ) );
	}
}



