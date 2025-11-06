<?php
/**
 * Error Handler for SEO AI Meta Generator
 * Provides user-friendly error messages with actionable steps
 *
 * @package    SEO_AI_Meta
 * @subpackage SEO_AI_Meta/includes
 */
class SEO_AI_Meta_Error_Handler {

	/**
	 * Get user-friendly error message
	 *
	 * @param WP_Error|string $error Error object or message.
	 * @return array Error data with message and action.
	 */
	public static function get_user_message( $error ) {
		if ( is_wp_error( $error ) ) {
			$code = $error->get_error_code();
			$message = $error->get_error_message();
			$data = $error->get_error_data();
		} else {
			$code = 'unknown';
			$message = is_string( $error ) ? $error : __( 'An unknown error occurred.', 'seo-ai-meta-generator' );
			$data = array();
		}

		$error_messages = self::get_error_messages();

		if ( isset( $error_messages[ $code ] ) ) {
			$error_info = $error_messages[ $code ];
			return array(
				'message' => $error_info['message'],
				'action'  => $error_info['action'],
				'type'    => $error_info['type'] ?? 'error',
				'data'    => $data,
			);
		}

		// Default error message
		return array(
			'message' => $message,
			'action'  => __( 'Please try again or contact support if the problem persists.', 'seo-ai-meta-generator' ),
			'type'    => 'error',
			'data'    => $data,
		);
	}

	/**
	 * Get error messages mapping
	 *
	 * @return array
	 */
	private static function get_error_messages() {
		return array(
			'rate_limit_exceeded' => array(
				'message' => __( 'You\'re generating meta tags too quickly. Please slow down.', 'seo-ai-meta-generator' ),
				'action'  => __( 'Wait a moment and try again. This helps us protect the service from abuse.', 'seo-ai-meta-generator' ),
				'type'    => 'warning',
			),
			'limit_reached' => array(
				'message' => __( 'You\'ve reached your monthly generation limit.', 'seo-ai-meta-generator' ),
				'action'  => __( 'Upgrade your plan to generate more meta tags. Your limit resets on the first of each month.', 'seo-ai-meta-generator' ),
				'type'    => 'info',
			),
			'api_error' => array(
				'message' => __( 'Unable to connect to the AI service.', 'seo-ai-meta-generator' ),
				'action'  => __( 'Check your internet connection and try again. If the problem persists, the service may be temporarily unavailable.', 'seo-ai-meta-generator' ),
				'type'    => 'error',
			),
			'invalid_response' => array(
				'message' => __( 'The AI service returned an unexpected response.', 'seo-ai-meta-generator' ),
				'action'  => __( 'Try generating again. If the issue continues, contact support.', 'seo-ai-meta-generator' ),
				'type'    => 'error',
			),
			'not_authenticated' => array(
				'message' => __( 'You need to be logged in to generate meta tags.', 'seo-ai-meta-generator' ),
				'action'  => __( 'Please log in and try again.', 'seo-ai-meta-generator' ),
				'type'    => 'error',
			),
			'no_user' => array(
				'message' => __( 'Unable to identify your account.', 'seo-ai-meta-generator' ),
				'action'  => __( 'Please log out and log back in, then try again.', 'seo-ai-meta-generator' ),
				'type'    => 'error',
			),
			'permission_denied' => array(
				'message' => __( 'You don\'t have permission to perform this action.', 'seo-ai-meta-generator' ),
				'action'  => __( 'Contact your administrator if you believe this is an error.', 'seo-ai-meta-generator' ),
				'type'    => 'error',
			),
			'post_not_found' => array(
				'message' => __( 'The post you\'re trying to generate meta for doesn\'t exist.', 'seo-ai-meta-generator' ),
				'action'  => __( 'Please refresh the page and try again.', 'seo-ai-meta-generator' ),
				'type'    => 'error',
			),
			'backend_unavailable' => array(
				'message' => __( 'The backend service is temporarily unavailable.', 'seo-ai-meta-generator' ),
				'action'  => __( 'Please wait a moment and try again. The service should be back online shortly.', 'seo-ai-meta-generator' ),
				'type'    => 'warning',
			),
		);
	}

	/**
	 * Format error for display
	 *
	 * @param WP_Error|string $error Error object or message.
	 * @return string HTML formatted error message.
	 */
	public static function format_error( $error ) {
		$error_data = self::get_user_message( $error );
		$type_class = 'notice-' . $error_data['type'];
		
		$html = '<div class="notice ' . esc_attr( $type_class ) . ' is-dismissible">';
		$html .= '<p><strong>' . esc_html( $error_data['message'] ) . '</strong></p>';
		
		if ( ! empty( $error_data['action'] ) ) {
			$html .= '<p>' . esc_html( $error_data['action'] ) . '</p>';
		}

		// Add additional data if available
		if ( ! empty( $error_data['data'] ) && isset( $error_data['data']['remaining'] ) ) {
			$html .= '<p><small>' . sprintf(
				/* translators: %d: seconds */
				esc_html__( 'Please wait %d seconds before trying again.', 'seo-ai-meta-generator' ),
				intval( $error_data['data']['remaining'] )
			) . '</small></p>';
		}

		$html .= '</div>';

		return $html;
	}
}

