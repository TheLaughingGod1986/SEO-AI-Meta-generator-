<?php
/**
 * API Client for SEO AI Meta Generator
 * Handles JWT authentication and communication with the backend API
 * Adapted from AltText AI
 *
 * @package    SEO_AI_Meta
 * @subpackage SEO_AI_Meta/includes
 */
class SEO_AI_Meta_API_Client_V2 {

	/**
	 * API URL
	 *
	 * @var string
	 */
	private $api_url;

	/**
	 * Token option key
	 *
	 * @var string
	 */
	private $token_option_key = 'seo_ai_meta_jwt_token';

	/**
	 * User option key
	 *
	 * @var string
	 */
	private $user_option_key = 'seo_ai_meta_user_data';

	/**
	 * Constructor
	 */
	public function __construct() {
		// Use same backend as AltText AI (can be shared or separate)
		$production_url = defined( 'SEO_AI_META_API_URL' ) ? SEO_AI_META_API_URL : 'https://alttext-ai-backend.onrender.com';
		$local_url = 'http://host.docker.internal:3001';

		// Allow developers to override for local development via wp-config.php
		if ( defined( 'SEO_AI_META_API_URL' ) && ( strpos( SEO_AI_META_API_URL, 'localhost' ) !== false || strpos( SEO_AI_META_API_URL, 'docker.internal' ) !== false ) ) {
			// Explicitly set local URL
			$local_available = $this->check_local_backend( $local_url );
			$this->api_url = $local_available ? $local_url : $production_url;
		} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_LOCAL_DEV' ) && WP_LOCAL_DEV ) {
			// Check if local backend is available, otherwise use production
			$local_available = $this->check_local_backend( $local_url );
			$this->api_url = $local_available ? $local_url : $production_url;
		} else {
			$this->api_url = $production_url;
		}
	}

	/**
	 * Check if local backend is available
	 *
	 * @param string $url Local backend URL.
	 * @return bool
	 */
	private function check_local_backend( $url ) {
		// Use a transient to cache the check for 5 minutes
		$cache_key = 'seo_ai_meta_local_backend_check';
		$cached = get_transient( $cache_key );
		
		if ( $cached !== false ) {
			return (bool) $cached;
		}

		$response = wp_remote_get( $url . '/health', array(
			'timeout' => 2,
			'sslverify' => false,
		) );

		$is_available = ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200;
		
		// Cache the result for 5 minutes
		set_transient( $cache_key, $is_available ? 1 : 0, 5 * MINUTE_IN_SECONDS );
		
		return $is_available;
	}

	/**
	 * Ensure backend is available before making requests
	 *
	 * @return true|WP_Error
	 */
	public function ensure_backend_available() {
		$api_url = defined( 'SEO_AI_META_API_URL' ) ? SEO_AI_META_API_URL : 'https://alttext-ai-backend.onrender.com';
		$timeout = defined( 'SEO_AI_META_API_TIMEOUT' ) ? SEO_AI_META_API_TIMEOUT : 5;
		
		$response = wp_remote_get( trailingslashit( $api_url ) . 'health', array(
			'timeout' => $timeout,
			'sslverify' => true,
		) );
		
		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'backend_unavailable', 
				__( 'Backend service is currently unavailable. Please try again in a few moments.', 'seo-ai-meta-generator' ),
				array( 'error_code' => $response->get_error_code(), 'error_message' => $response->get_error_message() )
			);
		}
		
		$status = wp_remote_retrieve_response_code( $response );
		if ( $status !== 200 ) {
			return new WP_Error( 'backend_unhealthy', 
				sprintf( 
					/* translators: %d: HTTP status code */
					__( 'Backend service returned status %d. Please try again later.', 'seo-ai-meta-generator' ), 
					$status 
				),
				array( 'status_code' => $status )
			);
		}
		
		return true;
	}

	/**
	 * Get backend status information
	 *
	 * @return array Status information
	 */
	public function get_backend_status() {
		$api_url = defined( 'SEO_AI_META_API_URL' ) ? SEO_AI_META_API_URL : 'https://alttext-ai-backend.onrender.com';
		$timeout = defined( 'SEO_AI_META_API_TIMEOUT' ) ? SEO_AI_META_API_TIMEOUT : 5;
		
		$response = wp_remote_get( trailingslashit( $api_url ) . 'health', array(
			'timeout' => $timeout,
			'sslverify' => true,
		) );
		
		if ( is_wp_error( $response ) ) {
			return array(
				'status' => 'unavailable',
				'message' => __( 'Backend service is currently unavailable.', 'seo-ai-meta-generator' ),
				'can_work_offline' => $this->can_work_offline(),
			);
		}
		
		$status_code = wp_remote_retrieve_response_code( $response );
		if ( $status_code !== 200 ) {
			return array(
				'status' => 'unhealthy',
				'message' => sprintf(
					/* translators: %d: HTTP status code */
					__( 'Backend service returned status %d.', 'seo-ai-meta-generator' ),
					$status_code
				),
				'can_work_offline' => $this->can_work_offline(),
			);
		}
		
		return array(
			'status' => 'healthy',
			'message' => __( 'Backend service is operational.', 'seo-ai-meta-generator' ),
			'can_work_offline' => false,
		);
	}

	/**
	 * Check if plugin can work offline (without backend)
	 *
	 * @return bool
	 */
	private function can_work_offline() {
		// Check if offline mode is explicitly disabled
		if ( defined( 'SEO_AI_META_OFFLINE_MODE' ) && SEO_AI_META_OFFLINE_MODE === false ) {
			return false;
		}
		
		// Check if OpenAI API key is configured for local generation
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-database.php';
		$settings = SEO_AI_Meta_Database::get_setting( 'settings', array() );
		if ( empty( $settings ) ) {
			$settings = get_option( 'seo_ai_meta_settings', array() );
		}
		
		$openai_key = $settings['openai_api_key'] ?? '';
		return ! empty( $openai_key );
	}

	/**
	 * Get stored JWT token
	 *
	 * @return string
	 */
	protected function get_token() {
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-database.php';
		$user_id = get_current_user_id();
		
		if ( $user_id > 0 ) {
			$user_data = SEO_AI_Meta_Database::get_user_data( $user_id );
			if ( $user_data && ! empty( $user_data['jwt_token'] ) ) {
				return $user_data['jwt_token'];
			}
		}
		
		// Fallback to WordPress options for backward compatibility
		$token = get_option( $this->token_option_key, '' );
		return is_string( $token ) ? $token : '';
	}

	/**
	 * Store JWT token
	 *
	 * @param string $token JWT token.
	 */
	public function set_token( $token ) {
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-database.php';
		$user_id = get_current_user_id();
		
		if ( $user_id > 0 ) {
			$user_data = SEO_AI_Meta_Database::get_user_data( $user_id );
			$data = $user_data ? $user_data : array();
			$data['jwt_token'] = $token;
			SEO_AI_Meta_Database::update_user_data( $user_id, $data );
		}
		
		// Also update WordPress options for backward compatibility
		update_option( $this->token_option_key, $token );
	}

	/**
	 * Clear stored token
	 */
	public function clear_token() {
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-database.php';
		$user_id = get_current_user_id();
		
		if ( $user_id > 0 ) {
			$user_data = SEO_AI_Meta_Database::get_user_data( $user_id );
			if ( $user_data ) {
				$user_data['jwt_token'] = '';
				$user_data['user_data'] = null;
				SEO_AI_Meta_Database::update_user_data( $user_id, $user_data );
			}
		}
		
		// Also clear WordPress options for backward compatibility
		delete_option( $this->token_option_key );
		delete_option( $this->user_option_key );
	}

	/**
	 * Get stored user data
	 *
	 * @return array|null
	 */
	public function get_user_data() {
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-database.php';
		$user_id = get_current_user_id();
		
		if ( $user_id > 0 ) {
			$user_data = SEO_AI_Meta_Database::get_user_data( $user_id );
			if ( $user_data && ! empty( $user_data['user_data'] ) ) {
				return $user_data['user_data'];
			}
		}
		
		// Fallback to WordPress options for backward compatibility
		$data = get_option( $this->user_option_key, null );
		return ( $data !== false && $data !== null ) ? $data : null;
	}

	/**
	 * Store user data
	 *
	 * @param array $user_data User data.
	 */
	public function set_user_data( $user_data ) {
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-database.php';
		$user_id = get_current_user_id();
		
		if ( $user_id > 0 ) {
			$data = SEO_AI_Meta_Database::get_user_data( $user_id );
			$data = $data ? $data : array();
			$data['user_data'] = $user_data;
			SEO_AI_Meta_Database::update_user_data( $user_id, $data );
		}
		
		// Also update WordPress options for backward compatibility
		update_option( $this->user_option_key, $user_data );
	}

	/**
	 * Check if user is authenticated
	 *
	 * @return bool
	 */
	public function is_authenticated() {
		$token = $this->get_token();
		if ( empty( $token ) ) {
			return false;
		}

		// In local development, just check if token exists
		if ( defined( 'WP_LOCAL_DEV' ) && WP_LOCAL_DEV ) {
			return true;
		}

		// Validate token periodically
		$last_check = get_transient( 'seo_ai_meta_token_last_check' );
		$should_validate = $last_check === false;

		if ( $should_validate ) {
			$user_info = $this->get_user_info();
			if ( is_wp_error( $user_info ) ) {
				$this->clear_token();
				return false;
			}
			set_transient( 'seo_ai_meta_token_last_check', time(), 5 * MINUTE_IN_SECONDS );
		}

		return true;
	}

	/**
	 * Get authentication headers
	 *
	 * @return array
	 */
	private function get_auth_headers() {
		$token   = $this->get_token();
		$headers = array(
			'Content-Type' => 'application/json',
		);

		if ( $token ) {
			$headers['Authorization'] = 'Bearer ' . $token;
		}

		return $headers;
	}

	/**
	 * Make authenticated API request
	 *
	 * @param string $endpoint API endpoint.
	 * @param string $method    HTTP method.
	 * @param array  $data      Request data.
	 * @return array|WP_Error
	 */
	public function make_request( $endpoint, $method = 'GET', $data = null ) {
		// Load logger
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-logger.php';

		$url     = trailingslashit( $this->api_url ) . ltrim( $endpoint, '/' );
		$headers = $this->get_auth_headers();

		$args = array(
			'method'  => $method,
			'headers' => $headers,
			'timeout' => defined( 'SEO_AI_META_API_TIMEOUT' ) ? SEO_AI_META_API_TIMEOUT : 30,
		);

		if ( $data && in_array( $method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
			$args['body'] = wp_json_encode( $data );
			// Ensure Content-Type is set for JSON requests
			if ( ! isset( $args['headers']['Content-Type'] ) ) {
				$args['headers']['Content-Type'] = 'application/json';
			}
		}

		// Log request (sanitize sensitive data)
		$log_data = $data;
		if ( isset( $log_data['password'] ) ) {
			$log_data['password'] = '[REDACTED]';
		}
		if ( isset( $log_data['apiKey'] ) ) {
			$log_data['apiKey'] = '[REDACTED]';
		}

		SEO_AI_Meta_Logger::debug( 'API request started', array(
			'endpoint' => $endpoint,
			'method' => $method,
			'has_auth' => isset( $headers['Authorization'] ),
			'data_keys' => is_array( $log_data ) ? array_keys( $log_data ) : null,
		) );

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			SEO_AI_Meta_Logger::error( 'API request failed - network error', array(
				'endpoint' => $endpoint,
				'method' => $method,
				'error_code' => $response->get_error_code(),
				'error_message' => $response->get_error_message(),
			) );
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );
		$data        = json_decode( $body, true );

		// If JSON decode failed, return the raw body
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			$data = $body;
		}

		// Log response
		SEO_AI_Meta_Logger::debug( 'API response received', array(
			'endpoint' => $endpoint,
			'status_code' => $status_code,
			'response_size' => strlen( $body ),
		) );

		if ( $status_code === 404 ) {
			SEO_AI_Meta_Logger::warning( 'API endpoint not found', array(
				'endpoint' => $endpoint,
				'status_code' => $status_code,
			) );
			return new WP_Error( 'not_found', __( 'Resource not found.', 'seo-ai-meta-generator' ) );
		}

		if ( $status_code >= 500 ) {
			// Extract error message from response if available
			$error_message = __( 'Server temporarily unavailable.', 'seo-ai-meta-generator' );
			if ( is_array( $data ) ) {
				if ( isset( $data['error'] ) ) {
					$error_message = $data['error'];
				} elseif ( isset( $data['message'] ) ) {
					$error_message = $data['message'];
				}
			} elseif ( is_string( $data ) && ! empty( $data ) ) {
				$error_message = $data;
			}

			SEO_AI_Meta_Logger::error( 'API server error', array(
				'endpoint' => $endpoint,
				'status_code' => $status_code,
				'error_message' => $error_message,
			) );

			return new WP_Error( 'server_error', $error_message );
		}

		if ( $status_code === 401 || $status_code === 403 ) {
			// Clear token immediately on auth failure
			$this->clear_token();
			$error_message = __( 'Authentication required.', 'seo-ai-meta-generator' );
			if ( is_array( $data ) ) {
				if ( isset( $data['error'] ) ) {
					$error_message = $data['error'];
				} elseif ( isset( $data['message'] ) ) {
					$error_message = $data['message'];
				}
			} elseif ( is_string( $data ) && ! empty( $data ) ) {
				$error_message = $data;
			}

			SEO_AI_Meta_Logger::warning( 'API authentication failed', array(
				'endpoint' => $endpoint,
				'status_code' => $status_code,
				'error_message' => $error_message,
			) );

			return new WP_Error( 'auth_required', $error_message );
		}

		// Handle 400 Bad Request errors (common for validation errors)
		if ( $status_code === 400 ) {
			$error_message = __( 'Invalid request. Please check your input.', 'seo-ai-meta-generator' );
			if ( is_array( $data ) ) {
				if ( isset( $data['error'] ) ) {
					$error_message = $data['error'];
				} elseif ( isset( $data['message'] ) ) {
					$error_message = $data['message'];
				} elseif ( isset( $data['errors'] ) && is_array( $data['errors'] ) ) {
					$error_message = implode( ', ', $data['errors'] );
				}
			}

			SEO_AI_Meta_Logger::warning( 'API bad request', array(
				'endpoint' => $endpoint,
				'status_code' => $status_code,
				'error_message' => $error_message,
			) );

			return new WP_Error( 'bad_request', $error_message );
		}

		if ( $status_code >= 200 && $status_code < 300 ) {
			SEO_AI_Meta_Logger::debug( 'API request successful', array(
				'endpoint' => $endpoint,
				'status_code' => $status_code,
			) );
		}

		return array(
			'status_code' => $status_code,
			'data'        => $data,
			'success'     => $status_code >= 200 && $status_code < 300,
		);
	}

	/**
	 * Register new user
	 *
	 * @param string $email    User email.
	 * @param string $password User password.
	 * @return array|WP_Error
	 */
	public function register( $email, $password ) {
		$response = $this->make_request( '/auth/register', 'POST', array(
			'email'    => $email,
			'password' => $password,
			'service'  => 'seo-ai-meta', // Identify this as SEO AI Meta service
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( $response['success'] && isset( $response['data']['token'] ) ) {
			$this->set_token( $response['data']['token'] );
			$this->set_user_data( $response['data']['user'] );
			return $response['data'];
		}

		// Extract error message from various possible response structures
		$error_message = __( 'Registration failed', 'seo-ai-meta-generator' );
		if ( isset( $response['data']['error'] ) ) {
			$error_message = $response['data']['error'];
		} elseif ( isset( $response['data']['message'] ) ) {
			$error_message = $response['data']['message'];
		} elseif ( isset( $response['data']['errors'] ) && is_array( $response['data']['errors'] ) ) {
			$error_message = implode( ', ', $response['data']['errors'] );
		} elseif ( isset( $response['data'] ) && is_string( $response['data'] ) ) {
			$error_message = $response['data'];
		}

		return new WP_Error( 'registration_failed', $error_message );
	}

	/**
	 * Login user
	 *
	 * @param string $email    User email.
	 * @param string $password User password.
	 * @return array|WP_Error
	 */
	public function login( $email, $password ) {
		$response = $this->make_request( '/auth/login', 'POST', array(
			'email'    => $email,
			'password' => $password,
			'service'  => 'seo-ai-meta', // Identify this as SEO AI Meta service
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( $response['success'] && isset( $response['data']['token'] ) ) {
			$this->set_token( $response['data']['token'] );
			$this->set_user_data( $response['data']['user'] );
			return $response['data'];
		}

		return new WP_Error( 'login_failed', $response['data']['error'] ?? __( 'Login failed', 'seo-ai-meta-generator' ) );
	}

	/**
	 * Request password reset
	 *
	 * @param string $email User email.
	 * @return array|WP_Error
	 */
	public function request_password_reset( $email ) {
		// Get the WordPress site URL for the reset link
		$reset_url = admin_url( 'edit.php?page=seo-ai-meta-generator&reset_token=' );
		
		$response = $this->make_request( '/auth/forgot-password', 'POST', array(
			'email'     => $email,
			'service'   => 'seo-ai-meta',
			'reset_url' => $reset_url, // Backend will append the token to this URL
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( $response['success'] ) {
			return $response['data'];
		}

		// Extract error message
		$error_message = __( 'Failed to send password reset email', 'seo-ai-meta-generator' );
		if ( isset( $response['data']['error'] ) ) {
			$error_message = $response['data']['error'];
		} elseif ( isset( $response['data']['message'] ) ) {
			$error_message = $response['data']['message'];
		}

		return new WP_Error( 'password_reset_failed', $error_message );
	}

	/**
	 * Reset password with token
	 *
	 * @param string $token       Reset token.
	 * @param string $new_password New password.
	 * @return array|WP_Error
	 */
	public function reset_password( $token, $new_password ) {
		$response = $this->make_request( '/auth/reset-password', 'POST', array(
			'token'    => $token,
			'password' => $new_password,
			'service'  => 'seo-ai-meta',
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( $response['success'] ) {
			return $response['data'];
		}

		// Extract error message
		$error_message = __( 'Failed to reset password', 'seo-ai-meta-generator' );
		if ( isset( $response['data']['error'] ) ) {
			$error_message = $response['data']['error'];
		} elseif ( isset( $response['data']['message'] ) ) {
			$error_message = $response['data']['message'];
		}

		return new WP_Error( 'password_reset_failed', $error_message );
	}

	/**
	 * Get current user info
	 *
	 * @return array|WP_Error
	 */
	public function get_user_info() {
		$response = $this->make_request( '/auth/me' );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( $response['success'] ) {
			$this->set_user_data( $response['data']['user'] );
			return $response['data']['user'];
		}

		return new WP_Error( 'user_info_failed', $response['data']['error'] ?? __( 'Failed to get user info', 'seo-ai-meta-generator' ) );
	}

	/**
	 * Get usage information
	 *
	 * @return array|WP_Error
	 */
	public function get_usage() {
		$response = $this->make_request( '/usage?service=seo-ai-meta' );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( $response['success'] ) {
			return $response['data']['usage'];
		}

		return new WP_Error( 'usage_failed', $response['data']['error'] ?? __( 'Failed to get usage info', 'seo-ai-meta-generator' ) );
	}

	/**
	 * Get billing information
	 *
	 * @return array|WP_Error
	 */
	public function get_billing_info() {
		$response = $this->make_request( '/billing/info?service=seo-ai-meta' );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( $response['success'] ) {
			return $response['data']['billing'];
		}

		return new WP_Error( 'billing_failed', $response['data']['error'] ?? __( 'Failed to get billing info', 'seo-ai-meta-generator' ) );
	}

	/**
	 * Get available plans
	 *
	 * @return array|WP_Error
	 */
	public function get_plans() {
		$response = $this->make_request( '/billing/plans?service=seo-ai-meta' );

		if ( is_wp_error( $response ) ) {
			// Don't return error if server is temporarily unavailable - return empty array instead
			// This allows the plugin to fall back to default price IDs
			if ( $response->get_error_code() === 'server_error' ) {
				return array();
			}
			return $response;
		}

		if ( $response['success'] ) {
			return $response['data']['plans'] ?? array();
		}

		// If request failed but wasn't an error, return empty array to allow fallback
		return array();
	}

	/**
	 * Create checkout session
	 *
	 * @param string $price_id   Stripe price ID.
	 * @param string $success_url Success URL.
	 * @param string $cancel_url  Cancel URL.
	 * @return array|WP_Error
	 */
	public function create_checkout_session( $price_id, $success_url, $cancel_url ) {
		// Debug: Log the request
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'SEO AI Meta API: Creating checkout session - Price ID: ' . $price_id . ', Service: seo-ai-meta' );
		}

		$response = $this->make_request( '/billing/checkout', 'POST', array(
			'priceId'    => $price_id,
			'price_id'   => $price_id, // Provide underscored key for backward compatibility
			'successUrl' => $success_url,
			'cancelUrl'  => $cancel_url,
			'service'    => 'seo-ai-meta', // Identify this as SEO AI Meta service
		) );

		if ( is_wp_error( $response ) ) {
			// Debug: Log the error
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'SEO AI Meta API: Checkout session error - ' . $response->get_error_code() . ': ' . $response->get_error_message() );
			}
			return $response;
		}

		if ( $response['success'] ) {
			// Debug: Log success
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'SEO AI Meta API: Checkout session created successfully - URL: ' . ( $response['data']['url'] ?? 'NOT PROVIDED' ) );
			}
			return $response['data'];
		}

		// Extract error message similar to AltText AI
		$error_message = '';
		if ( isset( $response['data']['error'] ) && is_string( $response['data']['error'] ) ) {
			$error_message = $response['data']['error'];
		} elseif ( isset( $response['data']['message'] ) && is_string( $response['data']['message'] ) ) {
			$error_message = $response['data']['message'];
		} elseif ( ! empty( $response['data'] ) && is_array( $response['data'] ) ) {
			$error_message = wp_json_encode( $response['data'] );
		}

		if ( ! $error_message ) {
			$error_message = __( 'Failed to create checkout session', 'seo-ai-meta-generator' );
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'SEO AI Meta API: Checkout failed - ' . $error_message );
		}
		return new WP_Error( 'checkout_failed', $error_message );
	}

	/**
	 * Create customer portal session
	 *
	 * @param string $return_url Return URL.
	 * @return array|WP_Error
	 */
	public function create_customer_portal_session( $return_url ) {
		$response = $this->make_request( '/billing/portal', 'POST', array(
			'returnUrl' => $return_url,
			'service'   => 'seo-ai-meta', // Identify this as SEO AI Meta service
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( $response['success'] ) {
			return $response['data'];
		}

		return new WP_Error( 'portal_failed', $response['data']['error'] ?? __( 'Failed to create customer portal session', 'seo-ai-meta-generator' ) );
	}
}
