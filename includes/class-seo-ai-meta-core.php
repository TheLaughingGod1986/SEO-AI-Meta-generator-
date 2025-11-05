<?php
/**
 * Core functionality for SEO AI Meta Generator
 * Handles checkout, billing, and plan management
 *
 * @package    SEO_AI_Meta
 * @subpackage SEO_AI_Meta/includes
 */
class SEO_AI_Meta_Core {

	/**
	 * Default Stripe price IDs
	 * SEO AI Meta Stripe products created via CLI
	 */
	private const DEFAULT_CHECKOUT_PRICE_IDS = array(
		'pro'     => 'price_1SQ72OJl9Rm418cMruYB5Pgb', // SEO AI Meta Pro - £12.99/month (LIVE)
		'agency'  => 'price_1SQ72KJl9Rm418cMB0CYh8xe', // SEO AI Meta Agency - £49.99/month (LIVE)
	);

	/**
	 * Legacy AltText AI price IDs that should be remapped to SEO AI Meta defaults.
	 */
	private const LEGACY_PRICE_ID_MAP = array(
		'pro'    => array(
			'price_1SPXIeJl9Rm418cMVFOu0HVU' => 'price_1SQ72OJl9Rm418cMruYB5Pgb',
		),
		'agency' => array(
			'price_1SPXIfJl9Rm418cM719fxWyn' => 'price_1SQ72KJl9Rm418cMB0CYh8xe',
		),
	);

	/**
	 * API client
	 *
	 * @var SEO_AI_Meta_API_Client_V2
	 */
	private $api_client;

	/**
	 * Cached Stripe price IDs for this request.
	 *
	 * @var array
	 */
	private $resolved_price_ids = array();

	/**
	 * Cached backend price IDs for this request.
	 *
	 * @var array|null|false
	 */
	private $backend_price_ids = null;

	/**
	 * Constructor
	 */
	public function __construct() {
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-api-client-v2.php';
		$this->api_client = new SEO_AI_Meta_API_Client_V2();
	}

	/**
	 * Allow redirects to Stripe domains when using wp_safe_redirect().
	 *
	 * @param array  $hosts Allowed redirect hosts.
	 * @param string $host  Host being validated (unused).
	 * @return array
	 */
	public function allow_stripe_redirects( $hosts, $host = '' ) {
		$stripe_hosts = array(
			'checkout.stripe.com',
			'billing.stripe.com',
			'buy.stripe.com',
		);

		foreach ( $stripe_hosts as $stripe_host ) {
			if ( ! in_array( $stripe_host, $hosts, true ) ) {
				$hosts[] = $stripe_host;
			}
		}

		return $hosts;
	}

	/**
	 * Get checkout price ID for plan
	 *
	 * @param string $plan Plan name.
	 * @return string
	 */
	public function get_checkout_price_id( $plan ) {
		$plan = sanitize_key( $plan );

		if ( isset( $this->resolved_price_ids[ $plan ] ) ) {
			return apply_filters( 'seo_ai_meta_checkout_price_id', $this->resolved_price_ids[ $plan ], $plan );
		}

		$price_id   = '';
		$remote_ids = array();

		if ( $this->api_client->is_authenticated() ) {
			$remote_ids = $this->get_backend_price_ids();
			if ( isset( $remote_ids[ $plan ] ) && $this->is_valid_price_id( $remote_ids[ $plan ] ) ) {
				$price_id = $remote_ids[ $plan ];
			}
		}

		if ( ! $this->is_valid_price_id( $price_id ) ) {
			$stored_id = $this->get_stored_price_id( $plan );
			if ( $this->is_valid_price_id( $stored_id ) ) {
				$price_id = $stored_id;
			}
		}

		if ( ! $this->is_valid_price_id( $price_id ) && ! empty( $remote_ids ) ) {
			if ( isset( $remote_ids[ $plan ] ) && $this->is_valid_price_id( $remote_ids[ $plan ] ) ) {
				$price_id = $remote_ids[ $plan ];
			}
		}

		$price_id = $this->normalize_price_id_for_plan( $plan, $price_id );

		if ( ! $this->is_valid_price_id( $price_id ) && isset( self::DEFAULT_CHECKOUT_PRICE_IDS[ $plan ] ) ) {
			$price_id = self::DEFAULT_CHECKOUT_PRICE_IDS[ $plan ];
		}

		// Final fallback - ensure we always have a valid price ID from defaults if available
		if ( ! $this->is_valid_price_id( $price_id ) && isset( self::DEFAULT_CHECKOUT_PRICE_IDS[ $plan ] ) ) {
			$default_id = self::DEFAULT_CHECKOUT_PRICE_IDS[ $plan ];
			if ( $this->is_valid_price_id( $default_id ) ) {
				$price_id = $default_id;
			}
		}

		$price_id = $this->is_valid_price_id( $price_id ) ? $price_id : '';

		// Always persist valid price IDs to ensure they're available next time
		if ( $this->is_valid_price_id( $price_id ) ) {
			$this->persist_price_ids( array( $plan => $price_id ) );
		}

		$price_id = apply_filters( 'seo_ai_meta_checkout_price_id', $price_id, $plan );
		$this->resolved_price_ids[ $plan ] = $price_id;

		return $price_id;
	}

	/**
	 * Handle direct checkout link
	 */
	public function maybe_handle_direct_checkout() {
		if ( ! is_admin() ) {
			return;
		}

		// Load logger
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-logger.php';

		$page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : '';

		if ( $page !== 'seo-ai-meta-generator-checkout' ) {
			return;
		}

		SEO_AI_Meta_Logger::info( 'Checkout process started', array( 'page' => $page ) );

		if ( ! current_user_can( 'manage_seo_ai_meta' ) ) {
			SEO_AI_Meta_Logger::warning( 'Checkout permission denied', array( 'user_id' => get_current_user_id() ) );
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'seo-ai-meta-generator' ) );
		}

		$nonce = isset( $_GET['_seo_ai_meta_nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_seo_ai_meta_nonce'] ) ) : '';
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'seo_ai_meta_direct_checkout' ) ) {
			SEO_AI_Meta_Logger::error( 'Checkout security check failed', array( 'nonce_provided' => ! empty( $nonce ) ) );
			wp_die( esc_html__( 'Security check failed.', 'seo-ai-meta-generator' ) );
		}

		$plan_param = sanitize_key( $_GET['plan'] ?? '' );
		$price_id   = isset( $_GET['price_id'] ) ? sanitize_text_field( wp_unslash( $_GET['price_id'] ) ) : '';

		SEO_AI_Meta_Logger::debug( 'Checkout parameters received', array(
			'plan' => $plan_param,
			'price_id' => $price_id,
		) );

		if ( $plan_param && empty( $price_id ) ) {
			$price_id = $this->get_checkout_price_id( $plan_param );
			SEO_AI_Meta_Logger::debug( 'Price ID retrieved from plan', array( 'plan' => $plan_param, 'price_id' => $price_id ) );
		}

		if ( empty( $price_id ) ) {
			// Final attempt - try to get from defaults directly
			if ( $plan_param && isset( self::DEFAULT_CHECKOUT_PRICE_IDS[ $plan_param ] ) ) {
				$price_id = self::DEFAULT_CHECKOUT_PRICE_IDS[ $plan_param ];
				SEO_AI_Meta_Logger::debug( 'Price ID retrieved from defaults', array( 'plan' => $plan_param, 'price_id' => $price_id ) );
			}
		}

		if ( empty( $price_id ) || ! $this->is_valid_price_id( $price_id ) ) {
			$error_message = sprintf(
				/* translators: %s: Plan name */
				__( 'Invalid price ID for %s plan. Please contact support.', 'seo-ai-meta-generator' ),
				$plan_param ?: 'selected'
			);
			SEO_AI_Meta_Logger::error( 'Invalid price ID for checkout', array(
				'plan' => $plan_param,
				'price_id' => $price_id,
				'error_message' => $error_message,
			) );
			wp_safe_redirect( add_query_arg( array( 'checkout_error' => rawurlencode( $error_message ) ), admin_url( 'edit.php?page=seo-ai-meta-generator' ) ) );
			exit;
		}

		$success_url = admin_url( 'edit.php?page=seo-ai-meta-generator&checkout=success' );
		$cancel_url  = admin_url( 'edit.php?page=seo-ai-meta-generator&checkout=cancel' );

		// Check if user is authenticated before creating checkout
		$is_authenticated = $this->api_client->is_authenticated();
		SEO_AI_Meta_Logger::debug( 'Authentication status checked', array( 'is_authenticated' => $is_authenticated ) );

		if ( ! $is_authenticated ) {
			$error_message = __( 'Please log in to your account first. Click the "Login" button in the header.', 'seo-ai-meta-generator' );
			SEO_AI_Meta_Logger::warning( 'Checkout attempted without authentication', array(
				'plan' => $plan_param,
				'price_id' => $price_id,
			) );
			wp_safe_redirect( add_query_arg( array( 'checkout_error' => rawurlencode( $error_message ) ), admin_url( 'edit.php?page=seo-ai-meta-generator' ) ) );
			exit;
		}

		SEO_AI_Meta_Logger::info( 'Creating checkout session', array(
			'plan' => $plan_param,
			'price_id' => $price_id,
			'success_url' => $success_url,
			'cancel_url' => $cancel_url,
		) );

		$result = $this->api_client->create_checkout_session( $price_id, $success_url, $cancel_url );

		// Handle errors similar to AltText AI
		if ( is_wp_error( $result ) || empty( $result['url'] ) ) {
			$error_message = is_wp_error( $result ) ? $result->get_error_message() : __( 'Unable to start checkout. Please try again.', 'seo-ai-meta-generator' );
			$error_code = is_wp_error( $result ) ? $result->get_error_code() : 'unknown';

			SEO_AI_Meta_Logger::error( 'Checkout session creation failed', array(
				'error_code' => $error_code,
				'error_message' => $error_message,
				'plan' => $plan_param,
				'price_id' => $price_id,
				'is_authenticated' => $is_authenticated,
			) );

			// Provide helpful error messages
			if ( is_wp_error( $result ) ) {
				if ( $error_code === 'auth_required' || ( $error_code === 'bad_request' && strpos( $error_message, 'token' ) !== false ) ) {
					// Authentication issue - clear token and ask user to re-login
					$this->api_client->clear_token();
					$error_message = __( 'Your session has expired. Please log in again using the "Login" button in the header.', 'seo-ai-meta-generator' );
					SEO_AI_Meta_Logger::warning( 'Session expired, token cleared', array( 'error_code' => $error_code ) );
				} elseif ( $error_code === 'server_error' ) {
					$error_message = __( 'The checkout service is temporarily unavailable. Please try again in a few moments.', 'seo-ai-meta-generator' );
					SEO_AI_Meta_Logger::error( 'Backend service error during checkout', array( 'error_code' => $error_code ) );
				}
			}

			$query_args = array(
				'page'           => 'seo-ai-meta-generator',
				'checkout_error' => rawurlencode( $error_message ),
			);
			if ( ! empty( $plan_param ) ) {
				$query_args['plan'] = $plan_param;
			}
			wp_safe_redirect( add_query_arg( $query_args, admin_url( 'edit.php' ) ) );
			exit;
		}

		SEO_AI_Meta_Logger::info( 'Checkout session created successfully', array(
			'plan' => $plan_param,
			'price_id' => $price_id,
			'checkout_url' => substr( $result['url'], 0, 50 ) . '...', // Log partial URL for privacy
		) );

		// Improved UX: Open Stripe in new tab and automatically redirect back to dashboard
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<title><?php esc_html_e( 'Redirecting to checkout...', 'seo-ai-meta-generator' ); ?></title>
			<style>
				body {
					font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
					display: flex;
					align-items: center;
					justify-content: center;
					min-height: 100vh;
					margin: 0;
					background: #f0f0f1;
				}
				.container {
					text-align: center;
					background: white;
					padding: 40px;
					border-radius: 8px;
					box-shadow: 0 2px 8px rgba(0,0,0,0.1);
					max-width: 400px;
				}
				.spinner {
					border: 4px solid #f3f3f3;
					border-top: 4px solid #667eea;
					border-radius: 50%;
					width: 40px;
					height: 40px;
					animation: spin 1s linear infinite;
					margin: 0 auto 20px;
				}
				@keyframes spin {
					0% { transform: rotate(0deg); }
					100% { transform: rotate(360deg); }
				}
				h1 {
					margin: 0 0 12px 0;
					font-size: 20px;
					color: #1f2937;
				}
				p {
					margin: 0 0 24px 0;
					color: #6b7280;
					font-size: 14px;
					line-height: 1.5;
				}
				.button {
					display: inline-block;
					padding: 10px 20px;
					background: #667eea;
					color: white;
					text-decoration: none;
					border-radius: 6px;
					font-size: 14px;
					font-weight: 500;
					transition: background 0.2s;
				}
				.button:hover {
					background: #5568d3;
				}
			</style>
		</head>
		<body>
			<div class="container">
				<div class="spinner"></div>
				<h1><?php esc_html_e( 'Opening checkout...', 'seo-ai-meta-generator' ); ?></h1>
				<p><?php esc_html_e( 'You will be redirected to Stripe to complete your purchase. This page will automatically return to the dashboard.', 'seo-ai-meta-generator' ); ?></p>
				<a href="<?php echo esc_url( admin_url( 'edit.php?page=seo-ai-meta-generator' ) ); ?>" class="button">
					<?php esc_html_e( 'Return to Dashboard', 'seo-ai-meta-generator' ); ?>
				</a>
			</div>
			<script>
				// Open Stripe checkout in new tab
				window.open('<?php echo esc_js( $result['url'] ); ?>', '_blank');
				
				// Automatically redirect back to dashboard after 2 seconds
				setTimeout(function() {
					window.location.href = '<?php echo esc_js( admin_url( 'edit.php?page=seo-ai-meta-generator' ) ); ?>';
				}, 2000);
			</script>
		</body>
		</html>
		<?php
		exit;
	}

	/**
	 * Check backend health before checkout
	 *
	 * @return array Array with 'healthy' boolean and 'message' string
	 */
	private function check_backend_health() {
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-api-client-v2.php';
		$api_url = defined( 'SEO_AI_META_API_URL' ) ? SEO_AI_META_API_URL : 'https://alttext-ai-backend.onrender.com';
		
		$response = wp_remote_get( trailingslashit( $api_url ) . 'health', array(
			'timeout' => 5,
			'sslverify' => true,
		) );
		
		if ( is_wp_error( $response ) ) {
			return array(
				'healthy' => false,
				'message' => __( 'Cannot connect to the checkout service. Please check your internet connection and try again.', 'seo-ai-meta-generator' ),
			);
		}
		
		$status_code = wp_remote_retrieve_response_code( $response );
		if ( $status_code !== 200 ) {
			return array(
				'healthy' => false,
				'message' => sprintf(
					/* translators: %d: HTTP status code */
					__( 'The checkout service is experiencing issues (HTTP %d). Please try again in a few moments.', 'seo-ai-meta-generator' ),
					$status_code
				),
			);
		}
		
		return array(
			'healthy' => true,
			'message' => '',
		);
	}

	/**
	 * Get backend status for UI display
	 *
	 * @return array
	 */
	public function get_backend_status() {
		return $this->api_client->get_backend_status();
	}

	/**
	 * Check if plugin can work offline
	 *
	 * @return bool
	 */
	public function can_work_offline() {
		// Check if offline mode is explicitly disabled
		if ( defined( 'SEO_AI_META_OFFLINE_MODE' ) && SEO_AI_META_OFFLINE_MODE === false ) {
			return false;
		}
		
		// Check if OpenAI API key is configured
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-database.php';
		$settings = SEO_AI_Meta_Database::get_setting( 'settings', array() );
		if ( empty( $settings ) ) {
			$settings = get_option( 'seo_ai_meta_settings', array() );
		}
		
		$openai_key = $settings['openai_api_key'] ?? '';
		return ! empty( $openai_key );
	}

	/**
	 * Register AJAX handlers for billing
	 */
	public function register_ajax_handlers() {
		add_action( 'wp_ajax_seo_ai_meta_get_subscription', array( $this, 'ajax_get_subscription' ) );
		add_action( 'wp_ajax_seo_ai_meta_open_portal', array( $this, 'ajax_open_portal' ) );
		add_action( 'wp_ajax_seo_ai_meta_get_plans', array( $this, 'ajax_get_plans' ) );
		add_action( 'wp_ajax_seo_ai_meta_login', array( $this, 'ajax_login' ) );
		add_action( 'wp_ajax_seo_ai_meta_register', array( $this, 'ajax_register' ) );
		add_action( 'wp_ajax_seo_ai_meta_logout', array( $this, 'ajax_logout' ) );
		add_action( 'wp_ajax_seo_ai_meta_forgot_password', array( $this, 'ajax_forgot_password' ) );
		add_action( 'wp_ajax_seo_ai_meta_reset_password', array( $this, 'ajax_reset_password' ) );
	}

	/**
	 * AJAX: Get subscription info
	 */
	public function ajax_get_subscription() {
		check_ajax_referer( 'seo_ai_meta_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_seo_ai_meta' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'seo-ai-meta-generator' ) ) );
		}

		if ( ! $this->api_client->is_authenticated() ) {
			wp_send_json_success( array(
				'plan' => 'free',
				'status' => 'free',
				'is_authenticated' => false,
			) );
		}

		$subscription = $this->api_client->get_billing_info();
		if ( is_wp_error( $subscription ) ) {
			wp_send_json_error( array( 'message' => $subscription->get_error_message() ) );
		}

		wp_send_json_success( $subscription );
	}

	/**
	 * AJAX: Open customer portal
	 */
	public function ajax_open_portal() {
		check_ajax_referer( 'seo_ai_meta_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_seo_ai_meta' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'seo-ai-meta-generator' ) ) );
		}

		if ( ! $this->api_client->is_authenticated() ) {
			wp_send_json_error( array( 'message' => __( 'Please log in first.', 'seo-ai-meta-generator' ) ) );
		}

		$return_url = admin_url( 'edit.php?page=seo-ai-meta-generator&portal_return=success' );
		$result = $this->api_client->create_customer_portal_session( $return_url );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'url' => $result['url'] ) );
	}

	/**
	 * AJAX: Get plans
	 */
	public function ajax_get_plans() {
		check_ajax_referer( 'seo_ai_meta_nonce', 'nonce' );

		$plans = $this->api_client->get_plans();
		if ( is_wp_error( $plans ) ) {
			// Return default plans if API fails
			$plans = array(
				array(
					'id'      => 'free',
					'name'    => 'Free',
					'price'   => 0,
					'images'  => 10,
					'features' => array( '10 posts per month' ),
				),
				array(
					'id'      => 'pro',
					'name'    => 'Pro',
					'price'   => 12.99,
					'images'  => 100,
					'priceId' => $this->get_checkout_price_id( 'pro' ),
					'features' => array( '100 posts per month' ),
				),
				array(
					'id'      => 'agency',
					'name'    => 'Agency',
					'price'   => 49.99,
					'images'  => 1000,
					'priceId' => $this->get_checkout_price_id( 'agency' ),
					'features' => array( '1000 posts per month' ),
				),
			);
		}

		wp_send_json_success( array( 'plans' => $plans ) );
	}

	/**
	 * AJAX: Login user
	 */
	public function ajax_login() {
		check_ajax_referer( 'seo_ai_meta_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_seo_ai_meta' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'seo-ai-meta-generator' ) ) );
		}

		$email = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';
		$password = isset( $_POST['password'] ) ? $_POST['password'] : '';

		if ( empty( $email ) || empty( $password ) ) {
			wp_send_json_error( array( 'message' => __( 'Email and password are required.', 'seo-ai-meta-generator' ) ) );
		}

		$result = $this->api_client->login( $email, $password );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'message' => __( 'Login successful!', 'seo-ai-meta-generator' ) ) );
	}

	/**
	 * AJAX: Register new user
	 */
	public function ajax_register() {
		check_ajax_referer( 'seo_ai_meta_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_seo_ai_meta' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'seo-ai-meta-generator' ) ) );
		}

		$email = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';
		$password = isset( $_POST['password'] ) ? $_POST['password'] : '';

		if ( empty( $email ) || empty( $password ) ) {
			wp_send_json_error( array( 'message' => __( 'Email and password are required.', 'seo-ai-meta-generator' ) ) );
		}

		if ( strlen( $password ) < 6 ) {
			wp_send_json_error( array( 'message' => __( 'Password must be at least 6 characters long.', 'seo-ai-meta-generator' ) ) );
		}

		$result = $this->api_client->register( $email, $password );

		if ( is_wp_error( $result ) ) {
			$error_message = $result->get_error_message();
			// Log the error for debugging
			error_log( 'SEO AI Meta Registration Error: ' . $error_message );
			wp_send_json_error( array( 'message' => $error_message ) );
		}

		wp_send_json_success( array( 'message' => __( 'Registration successful! You are now logged in.', 'seo-ai-meta-generator' ) ) );
	}

	/**
	 * AJAX: Logout user
	 */
	public function ajax_logout() {
		check_ajax_referer( 'seo_ai_meta_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_seo_ai_meta' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'seo-ai-meta-generator' ) ) );
		}

		$this->api_client->clear_token();
		wp_send_json_success( array( 'message' => __( 'Logged out successfully.', 'seo-ai-meta-generator' ) ) );
	}

	/**
	 * AJAX: Request password reset
	 */
	public function ajax_forgot_password() {
		check_ajax_referer( 'seo_ai_meta_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_seo_ai_meta' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'seo-ai-meta-generator' ) ) );
		}

		$email = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';

		if ( empty( $email ) ) {
			wp_send_json_error( array( 'message' => __( 'Email is required.', 'seo-ai-meta-generator' ) ) );
		}

		$result = $this->api_client->request_password_reset( $email );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'message' => __( 'Password reset email sent. Please check your inbox.', 'seo-ai-meta-generator' ) ) );
	}

	/**
	 * AJAX: Reset password with token
	 */
	public function ajax_reset_password() {
		check_ajax_referer( 'seo_ai_meta_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_seo_ai_meta' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'seo-ai-meta-generator' ) ) );
		}

		$token = isset( $_POST['token'] ) ? sanitize_text_field( $_POST['token'] ) : '';
		$password = isset( $_POST['password'] ) ? $_POST['password'] : '';

		if ( empty( $token ) || empty( $password ) ) {
			wp_send_json_error( array( 'message' => __( 'Token and password are required.', 'seo-ai-meta-generator' ) ) );
		}

		if ( strlen( $password ) < 6 ) {
			wp_send_json_error( array( 'message' => __( 'Password must be at least 6 characters long.', 'seo-ai-meta-generator' ) ) );
		}

		$result = $this->api_client->reset_password( $token, $password );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'message' => __( 'Password reset successfully. You can now login with your new password.', 'seo-ai-meta-generator' ) ) );
	}

	/**
	 * Determine if the provided price ID matches Stripe format.
	 *
	 * @param string $price_id Stripe price identifier.
	 * @return bool
	 */
	private function is_valid_price_id( $price_id ) {
		return is_string( $price_id ) && preg_match( '/^price_[a-zA-Z0-9]{8,}$/', $price_id );
	}

	/**
	 * Replace legacy AltText AI price IDs with current SEO AI Meta defaults.
	 *
	 * @param string $plan     Plan key.
	 * @param string $price_id Original price ID.
	 * @return string Normalized price ID.
	 */
	private function normalize_price_id_for_plan( $plan, $price_id ) {
		if ( empty( $price_id ) || ! is_string( $price_id ) ) {
			return $price_id;
		}

		$plan_key = sanitize_key( $plan );
		if ( isset( self::LEGACY_PRICE_ID_MAP[ $plan_key ][ $price_id ] ) ) {
			$new_id = self::LEGACY_PRICE_ID_MAP[ $plan_key ][ $price_id ];
			if ( $this->is_valid_price_id( $new_id ) ) {
				return $new_id;
			}
		}

		return $price_id;
	}

	/**
	 * Retrieve stored price ID for the given plan.
	 *
	 * @param string $plan Plan key.
	 * @return string
	 */
	private function get_stored_price_id( $plan ) {
		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-database.php';

		$price_ids = SEO_AI_Meta_Database::get_setting( 'price_ids', null );
		if ( ! is_array( $price_ids ) || empty( $price_ids ) ) {
			$price_ids = get_option( 'seo_ai_meta_price_ids', array() );
		}

		if ( ! is_array( $price_ids ) ) {
			$price_ids = array();
		}

		if ( isset( $price_ids[ $plan ] ) ) {
			$stored_id = sanitize_text_field( $price_ids[ $plan ] );
			return $this->normalize_price_id_for_plan( $plan, $stored_id );
		}

		return '';
	}

	/**
	 * Fetch price IDs from backend plans endpoint.
	 *
	 * @return array
	 */
	private function get_backend_price_ids() {
		if ( is_array( $this->backend_price_ids ) ) {
			return $this->backend_price_ids;
		}

		if ( $this->backend_price_ids === false ) {
			return array();
		}

		if ( ! $this->api_client->is_authenticated() ) {
			$this->backend_price_ids = false;
			return array();
		}

		$cached = get_transient( 'seo_ai_meta_plan_price_ids' );
		if ( is_array( $cached ) ) {
			$this->backend_price_ids = $cached;
			return $cached;
		}

		$price_ids = array();
		
		// Try to get plans from backend, but don't fail if backend is unavailable
		try {
			$plans = $this->api_client->get_plans();

			if ( ! is_wp_error( $plans ) && is_array( $plans ) ) {
				foreach ( $plans as $plan ) {
					if ( empty( $plan['id'] ) || empty( $plan['priceId'] ) ) {
						continue;
					}

					$plan_id  = sanitize_key( $plan['id'] );
					$price_id = sanitize_text_field( $plan['priceId'] );

					if ( $plan_id && $this->is_valid_price_id( $price_id ) ) {
						$price_ids[ $plan_id ] = $this->normalize_price_id_for_plan( $plan_id, $price_id );
					}
				}
			}
		} catch ( Exception $e ) {
			// Backend unavailable - silently fail and use defaults
			error_log( 'SEO AI Meta: Backend unavailable when fetching plans: ' . $e->getMessage() );
		}

		if ( ! empty( $price_ids ) ) {
			set_transient( 'seo_ai_meta_plan_price_ids', $price_ids, HOUR_IN_SECONDS );
			$this->persist_price_ids( $price_ids );
			$this->backend_price_ids = $price_ids;
			return $price_ids;
		}

		$this->backend_price_ids = false;
		return array();
	}

	/**
	 * Persist price IDs to storage (custom table and option).
	 *
	 * @param array $price_ids Price IDs keyed by plan.
	 * @return void
	 */
	private function persist_price_ids( $price_ids ) {
		if ( empty( $price_ids ) || ! is_array( $price_ids ) ) {
			return;
		}

		require_once SEO_AI_META_PLUGIN_DIR . 'includes/class-seo-ai-meta-database.php';

		$sanitized = array();
		foreach ( $price_ids as $plan => $price_id ) {
			$plan_key = sanitize_key( $plan );
			$id_value = sanitize_text_field( $price_id );

			$id_value = $this->normalize_price_id_for_plan( $plan_key, $id_value );

			if ( $plan_key && $this->is_valid_price_id( $id_value ) ) {
				$sanitized[ $plan_key ] = $id_value;
			}
		}

		if ( empty( $sanitized ) ) {
			return;
		}

		$current = SEO_AI_Meta_Database::get_setting( 'price_ids', array() );
		if ( ! is_array( $current ) ) {
			$current = array();
		}

		$transient_cleared = false;
		$updated = array_merge( $current, $sanitized );

		if ( $updated !== $current ) {
			SEO_AI_Meta_Database::update_setting( 'price_ids', $updated );
			delete_transient( 'seo_ai_meta_plan_price_ids' );
			$transient_cleared = true;
		}

		$option_current = get_option( 'seo_ai_meta_price_ids', array() );
		if ( ! is_array( $option_current ) ) {
			$option_current = array();
		}

		if ( $updated !== $option_current ) {
			update_option( 'seo_ai_meta_price_ids', $updated );
			if ( ! $transient_cleared ) {
				delete_transient( 'seo_ai_meta_plan_price_ids' );
			}
		}
	}
}
