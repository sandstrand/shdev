<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use Svea\WebPay\Constant\PaymentMethod;
use Svea\WebPay\Response\SveaResponse;
use Svea\WebPay\WebPay;
use Svea\WebPay\WebPayItem;
use Svea\WebPay\WebPayAdmin;

class WC_Gateway_Svea_Card extends WC_Payment_Gateway {

	/**
	 * Id of this gateway
	 *
	 * @var 	string
	 */
	const GATEWAY_ID = 'sveawebpay_card';

	/**
	 * Static instance of this class
	 *
     * @var 	WC_Gateway_Svea_Card
     */
    private static $instance = null;

    private static $log_enabled = false;
    private static $log = null;

    public static function init() {
        if ( is_null( self::$instance ) ) {
            $instance = new WC_Gateway_Svea_Card;
        }
        return self::$instance;
    }

	public function __construct() {
		if( is_null( self::$instance ) )
			self::$instance = $this;

		$this->supports = array(
			'products',
			'subscriptions',
			'subscription_cancellation',
			'subscription_suspension',
			'subscription_reactivation',
			'multiple_subscriptions',
			'subscription_amount_changes',
			'subscription_date_changes',
			'subscription_payment_method_change',
			'subscription_payment_method_change_customer',
			//'refunds'
		);

		$this->id = self::GATEWAY_ID;

		$this->method_title = __( 'SveaWebPay Card Payment', WC_SveaWebPay_Gateway::PLUGIN_SLUG );
		$this->icon = apply_filters( 'woocommerce_sveawebpay_card_icon', WC_SVEAWEBPAY_GATEWAY_PLUGIN_URL . 'assets/img/visa-mastercard.png' );
		$this->has_fields = true;

		$this->init_form_fields();
		$this->init_settings();

		$this->title = __( $this->get_option( 'title' ), WC_SveaWebPay_Gateway::PLUGIN_SLUG );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_api_wc_gateway_svea_card', array( $this, 'handle_callback_request' ) );
		// add_filter( 'woocommerce_gateway_description', array( &$this, 'get_gateway_description' ), 1, 2 );
		add_action( 'woocommerce_scheduled_subscription_payment_' . $this->id, array( $this, 'scheduled_subscription_payment' ), 10, 2 );

		//Merchant set fields
		$this->enabled = $this->get_option('enabled');
		$wc_countries = new WC_Countries();
		$this->base_country = $wc_countries->get_base_country();

        $this->selected_currency = get_woocommerce_currency();

		$this->merchant_id = $this->get_option('merchant_id');
		$this->secret_word = $this->get_option('secret_word');
		$this->testmode = $this->get_option( 'testmode' ) === "yes";
		$this->language = $this->get_option( 'language' );
		$this->card_payment_method = $this->get_option( 'card_payment_method' );
		self::$log_enabled = $this->get_option( 'debug' ) === "yes";

		$config_class = $this->testmode ? "WC_Svea_Config_Test" : "WC_Svea_Config_Production";

		$this->description = __( $this->get_option( 'description' ), WC_SveaWebPay_Gateway::PLUGIN_SLUG );

		if( $this->enabled !== "yes" )
			return;

		$this->config = new $config_class($this->merchant_id, $this->secret_word, false, false, false);		
	}

	/**
	 * Logging method.
	 * @param string $message
	 */
	public static function log( $message ) {
		if ( self::$log_enabled ) {
			if ( is_null( self::$log ) ) {
				self::$log = new WC_Logger();
			}

			self::$log->add( self::GATEWAY_ID, $message );
		}
	}

	/**
	 * Display payment fields at checkout
	 *
	 * @return void
	 */
	public function payment_fields() {
		echo $this->description;

		$wc_order = false;

		if( function_exists( 'is_checkout_pay_page' ) && is_checkout_pay_page() ) {
			if( isset( $_GET["key"] ) ) {
				$wc_order = wc_get_order( wc_get_order_id_by_order_key( $_GET["key"] ) );
			}
		}

		if( class_exists( 'WC_Subscriptions_Order' ) ) {
			/**
			 * If this order is not associated with subscriptions
			 * in any way, return without doing anything
			 */
			if ( $wc_order !== false
				&& ! wcs_is_subscription( $wc_order->get_id() )
				&& ! wcs_order_contains_subscription( $wc_order, array( 'order_type' => 'any' ) ) ) {
				return;
			}

			if ( ! empty( WC()->cart->cart_contents ) ) {
				$only_switches = true;

				foreach ( WC()->cart->cart_contents as $cart_item ) {
					if( ! isset( $cart_item['subscription_switch'] ) ) {
						$only_switches = false;
						break;
					}
				}

				if( $only_switches ) {
					return;
				}
			}

			if( ( WC_Subscriptions_Cart::cart_contains_subscription() || ( $wc_order && wcs_is_subscription( $wc_order->get_id() ) ) )
				&& ( WC()->cart->total <= 0 
					|| ( isset( $_GET["change_payment_method"] ) && $wc_order && $_GET["change_payment_method"] == $wc_order->get_id() ) ) ) {
				?><div class="card-reservation-fee"><?php
				printf( 
					__( 
						'%s will be reserved on your bank account and then refunded after about 5 banking days. This is required to set up your subscription.',
						WC_SveaWebPay_Gateway::PLUGIN_SLUG
					),
					wc_price( 1 )
				);
				?></div><?php
			}
		}
	}

	/**
	 * Enables much more complex descriptions
	 *
	 * @param 	string 	$description 	the description for the provided gateway
	 * @param 	int 	$id 			the id of the provided gateway
	 * @return 	string 	the gateway description
	 */
	public function get_gateway_description( $description, $id ) {
		if( $this->id !== $id )
			return $description;

		if( ! isset( $this->description[0] ) ) {
			$this->description = '<div>' . __( $this->get_option( 'description' ), WC_SveaWebPay_Gateway::PLUGIN_SLUG ) . '</div>';

			if( class_exists( 'WC_Subscriptions_Cart' ) ) {
				if( ( WC_Subscriptions_Cart::cart_contains_subscription()
					|| wcs_cart_contains_renewal()
					|| wcs_cart_contains_resubscribe() )
					&& WC()->cart->total <= 0 ) {

					$this->description .= '<div class="card-reservation-fee">';
					$this->description .= sprintf( 
						__( 
							'%s will be reserved on your bank account and then refunded after about 5 banking days. This is required to set up your subscription.',
							WC_SveaWebPay_Gateway::PLUGIN_SLUG
						),
						wc_price( 1 )
					);
					$this->description .= '</div>';
				}
			}
		}

		return $this->description;
	}

	public static function display_admin_action_buttons() {
		?><button type="button" class="button svea-credit-items"><?php _e( 'Credit via svea', WC_SveaWebPay_Gateway::PLUGIN_SLUG ); ?></button><?php
	}

	public static function admin_functions_meta_box() {
		$order = wc_get_order( get_the_ID() );

		$deliver_nonce = wp_create_nonce( WC_SveaWebPay_Gateway_Admin_Functions::DELIVER_NONCE );
		$cancel_nonce = wp_create_nonce( WC_SveaWebPay_Gateway_Admin_Functions::CANCEL_NONCE );
		$credit_nonce = wp_create_nonce( WC_SveaWebPay_Gateway_Admin_Functions::CREDIT_NONCE );

		?>
		<a href="<?php echo admin_url( 'admin-post.php?action=svea_webpay_admin_deliver_order&order_id=' . get_the_ID() . '&security='. $deliver_nonce ); ?>">
			<?php _e( 'Confirm', WC_SveaWebPay_Gateway::PLUGIN_SLUG ); ?>
		</a><br>
		<a href="<?php echo admin_url( 'admin-post.php?action=svea_webpay_admin_cancel_order&order_id=' . get_the_ID() . '&security=' . $cancel_nonce ); ?>">
			<?php _e( 'Cancel', WC_SveaWebPay_Gateway::PLUGIN_SLUG ); ?>
		</a><br>
		<a href="<?php echo admin_url( 'admin-post.php?action=svea_webpay_admin_credit_order&order_id=' . get_the_ID() . '&security=' . $credit_nonce ); ?>">
			<?php _e( 'Credit', WC_SveaWebPay_Gateway::PLUGIN_SLUG ); ?>
		</a>
		<?php
	}

	/**
     * Check whether or not this payment gateway is available
     *
     * @return  boolean
     */
	public function is_available() {
        if( ! parent::is_available() ) {
        	return false;
        }

        // if( ! is_admin() ) {
        //     if( ! $this->check_customer_currency() ) {
        //         return false;
        //     }
        // }

        return true;
    }

    /**
     * Check if the current currency is supported
     *
     * @return  boolean
     */
    public function check_customer_currency() {
        $customer_currency = $this->selected_currency;

        if( ! in_array( $customer_currency, WC_Gateway_Svea_Helper::$ALLOWED_CURRENCIES_ASYNC ) ) {
            return false;
        }

        return true;
    }

	/**
	 * Get the country code function.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_country_code() {
		if( ! isset( $this->country_code ) || strlen( $this->country_code ) === 0 ) {
			$this->country_code = $this->base_country;
		}

		return $this->country_code;
	}

	/**
	 * Check if the gateway is activated and that the choosen curency is supported.
	 * if the gateway is active and the currency set return true, otherwise return false
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function card_is_active_and_set() {
		// if( ! in_array( get_woocommerce_currency(), WC_Gateway_Svea_Helper::$ALLOWED_CURRENCIES_ASYNC ) ) {
		// 	return array(
		// 		"error"		=> true,
		// 		"message"	=> sprintf( __( "Svea WebPay Card doesn't support the currency %s. The supported currencies are: %s", "sveawebpay" ),
		// 						get_woocommerce_currency(), implode(", ", WC_Gateway_Svea_Helper::$ALLOWED_CURRENCIES_ASYNC ) )
		// 	);
		// } else if( ! in_array( $this->get_country_code(), WC_Gateway_Svea_Helper::$ALLOWED_COUNTRIES_ASYNC ) ) {
		// 	return array(
		// 			"error" 	=> true, 
		// 			"message" 	=> sprintf( __("Svea WebPay Card doesn't support the country %s. The supported countries are: %s", "sveawebpay"),
		// 						$this->get_country_code(), implode( ", ", WC_Gateway_Svea_Helper::$ALLOWED_COUNTRIES_ASYNC ) )
		// 	);
		// } else

		if ( version_compare( WOOCOMMERCE_VERSION, '2.0', '<' ) ) {
			return array(
					"error" 	=> true, 
					"message" 	=> sprintf( __( "Svea WebPay Card only support WooCommerce greater than version 2.0, version %s is not supported", WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
								WOOCOMMERCE_VERSION )
			);
		}

		return array( "error" => false );
	}

	/**
	 * Admin Panel Options
	 * Sets up and prints the Sveawebpay options page.
	 * 
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_options() {
		?>
		<h3><?php _e( 'SveaWebPay Card Payment', WC_SveaWebPay_Gateway::PLUGIN_SLUG )?></h3>
		<p><?php _e( 'Process card payments through SveaWebPay.', WC_SveaWebPay_Gateway::PLUGIN_SLUG )?></p>
		<?php
		$result = $this->card_is_active_and_set();
		if ( ! $result["error"] ) : ?>
		<table class="form-table">
			<?php
			 	// Generate the HTML For the settings form.
	    		$this->generate_settings_html();
			?>
		</table>
		<?php else : ?>
		<div class="inline error"><p><?php _e( $result["message"], WC_SveaWebPay_Gateway::PLUGIN_SLUG ); ?></p></div>
		<?php
		endif;
	}//end admin_options


	/**
	 * Form fields for Settings
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title' => __( 'Enable/disable', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
				'type' => 'checkbox',
				'label' => __( 'Enable SveaWebPay Card Payment', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
				'default' => 'no'
			),
			'title' => array(
				'title' => __('Title', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
				'type' => 'text',
				'description' => __('This controls the title which the user sees during checkout', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
				'default' => __( 'Card Payment', WC_SveaWebPay_Gateway::PLUGIN_SLUG )
			),
			'description' => array(
				'title' => __('Description', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
				'type' => 'textarea',
				'description' => __( 'This controls the description the user sees during checkout', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
				'default' => __( 'Pay with creditcard through Svea Ekonomi', WC_SveaWebPay_Gateway::PLUGIN_SLUG )
			),
			'merchant_id' => array(
				'title' => __('Merchant id', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
				'type' => 'text',
				'description' => __('Your SveaWebPay merchant id', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
				'default' => ''
			),
			'secret_word' => array(
				'title' => __('Secret word', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
				'type' => 'password',
				'description' => __('Your SveaWebPay secret word', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
				'default' => ''
			),
			'language' => array(
				'title' => __( 'Language', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 
				'type' => 'select',
				'options' => array(
					'en'	=> 'English', 
					'da'	=> 'Dansk', 
					'de'	=> 'Deutsch', 
					'fi'	=> 'Suomi', 
					'nl'	=> 'Nederlands', 
					'no'	=> 'Norsk', 
					'sv'	=> 'Svenska'
				),
				'description' => __( 'The language the pay page will be displayed in', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 
				'default' => 'sv'
			),
			'testmode' => array(
				'title' => __('Test mode', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
				'type' => 'checkbox',
				'description' => __('Enable test mode for SveaWebPay Card Payment', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
				'default' => 'no'
			),
			'card_payment_method' => array(
				'title' 		=> __( 'Card payment method', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
				'type' 			=> 'select',
				'options'		=> array(
					PaymentMethod::KORTCERT 	=> __( 'CertiTrade (Legacy)', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
					PaymentMethod::SVEACARDPAY 	=> __( 'Svea CardPay', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
				),
				'description' 	=> __( 'Set the card payment method you use for processing payments. <br />
								If you are using CertiTrade and want to change to Svea CardPay, please contact Svea before changing this setting.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
				'default' 		=> PaymentMethod::KORTCERT
			),
			'debug' => array(
				'title'       => __( 'Debug log', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable logging', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
				'default'     => 'no',
				'description' => sprintf( __( 'Log Svea events, such as payment requests, inside <code>%s</code>', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), wc_get_log_file_path( self::GATEWAY_ID ) )
			),
			'disable_order_sync' => array(
				'title' => __( 'Disable automatic order sync', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
				'type' => 'checkbox',
				'description' => __( "Disable automatic syncing of orders in WooCommerce to Svea. <br />
					If you enable this option, your refunded orders will not be refunded in Svea. <br />
					Your delivered orders will not be delivered in Svea and your cancelled orders will not be cancelled in Svea. <br />
					<strong>Don't touch this if you don't know what you're doing</strong>.", WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
				'default' => 'no'
			),
		);
	}

	/**
	 * Get the config for this payment method
	 *
	 * @return 	mixed 	the configuration object
	 */
    public function get_config () {
		if( ! isset($this->config) ) {
			if ($this->testmode) {
				$this->config = new WC_Svea_Config_Test();
			} else {
				$this->config = new WC_Svea_Config_Production();
			}
		}

		return $this->config;
	}

	/**
	 * Accepted request interprets the returned object and response code from Sveawebpay.
	 *
	 * @since 	1.0.0
	 * @access 	public
	 * @return 	void
	 */
	public function handle_callback_request() {
		$request = $_REQUEST;

		self::log( sprintf( "Callback request for payment initiated by client %s", $_SERVER['REMOTE_ADDR'] ) );

		/**
		 * Fetch response string directly from query string
		 * if parameter max length prevents it from being read.
		 */
		if( ( ! isset( $request["response"] ) || strlen( $request["response"] ) <= 0 )
			&& isset( $_SERVER['QUERY_STRING'] )
			&& strlen( $_SERVER['QUERY_STRING'] ) > 0 ) {

			$params_array = explode( '&', $_SERVER['QUERY_STRING'] );

			$params = array();

			foreach( $params_array as $pair ) {
				list( $key, $value ) = explode( '=', $pair );
				$params[urldecode($key)] = urldecode( $value );
			}

			if( isset( $params["response"] ) )
				$request["response"] = $params["response"];
		}

		/**
		 * If the response-parameter is not set, do not produce a PHP-error
		 */
		if( ! isset( $request["response"] ) ) {
			status_header( 400 );
			self::log( "Response parameter in request is not set" );
			exit;
		}

		$config = $this->get_config();

		/**
		 * Wrap the response in Sveas response-parser
		 */
		$svea_response = new SveaResponse( $request, null, $config );
		$response = $svea_response->getResponse();

		$payment_method = $response->paymentMethod;

		/**
		 * Since we added a underline in the order number, split it and extract
		 * the data
		 */
		$client_order_number = explode( "_", $response->clientOrderNumber );

		/**
		 * If the order number is not as expected, exit the program
		 */
		if( count( $client_order_number ) === 1 ) {
			self::log( "Client order number doesn't contain order number and order id" );
			exit;
		}

		$order_id = (int) $client_order_number[0];

        if( is_null( $order_id ) || is_null( $payment_method ) ) {
        	status_header( 400 );
        	self::log( "Order id or payment method is null" );
            exit;
        }

		$wc_order = wc_get_order( (int) $order_id );

		if( ! $wc_order ) {
			self::log( 'No order was found by ID' );
			exit;
		}

		/**
		 * Check if the callback was initiated by Svea Callback Service
		 * and save in variable for future use.
		 */
		$is_svea_callback = isset( $request["svea-callback"] ) && $request["svea-callback"];

		/**
		 * If the order is already paid, redirect to the order received page
		 */
		if( $wc_order->is_paid() ) {

			/**
			 * Check if request was initiated by Svea Callback Service
			 */
			if( $is_svea_callback ) {
				self::log( sprintf( "Order %s was already paid, request was initiated by Svea Callback Service, exiting.", $order_id ) );
				status_header( 200 );
			} else {
				self::log( sprintf( "Order %s was already paid, redirecting to order received page", $order_id ) );
				wp_redirect( $wc_order->get_checkout_order_received_url() );
			}

			exit;
		}

		$subscriptions = false;

		if( class_exists( 'WC_Subscriptions_Order' ) )  {
			if( ( wcs_order_contains_subscription( $wc_order )
				|| wcs_order_contains_switch( $wc_order )
				|| wcs_order_contains_resubscribe( $wc_order )
				|| wcs_order_contains_renewal( $wc_order ) ) ) {
				self::log( "Order contains subscriptions, fetching them." );
				$subscriptions = wcs_get_subscriptions_for_order( $wc_order->get_id(), array( 'order_type' => 'any' ) );
			} else if( wcs_is_subscription( $wc_order->get_id() ) ) {
				self::log( "Order is a subscription, fetching subscription and putting in list." );
				$subscriptions = array( wcs_get_subscription( $wc_order->get_id() ) );
			}
		}

		$cancel_url = esc_url_raw( wc_get_checkout_url() );

		$redirect_url = $cancel_url;
		$complete_url = $wc_order->get_checkout_order_received_url();

		if( isset( $response->accepted ) && $response->accepted ) {
			
			$wc_order->add_order_note( sprintf( __( 'Order was completed by client on IP: %s', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), $_SERVER['REMOTE_ADDR'] ) );
            $wc_order->payment_complete( $response->transactionId );

            // Remove cart
			WC()->cart->empty_cart();

            $redirect_url = $complete_url;
            update_post_meta( $order_id, "_svea_order_id", $response->transactionId );

            self::log( sprintf( "Payment was successful and order %s is complete", $wc_order->get_id() ) );

            if( $subscriptions ) {
            	foreach( $subscriptions as $subscription ) {
					update_post_meta( $subscription->get_id(), '_svea_subscription_id', $response->subscriptionId );

					if( $subscription->get_payment_method() != $this->id ) {
						$subscription->set_payment_method( $this->id );
						$subscription->save();
					}
            	}
            }

            /**
             * If Svea initiated the callback, prevent unnecessary requests
             * by returning 200 status code instead of redirecting.
             */
            if( $is_svea_callback ) {
            	self::log( "Request was initiated by Svea Callback Service, exiting instead of redirect." );
            	status_header( 200 );
            	exit;
            }

		} else {
			wc_add_notice( WC_Gateway_Svea_Helper::get_svea_error_message( (int) $response->resultcode ), 'error' );
			self::log( "Payment failed, response: " . $response->errormessage );

            $redirect_url = $cancel_url;
		}

		wp_redirect( $redirect_url );
		exit;
	}

	/**
	 * Process refunds
	 *
	 * @param 	string 	$order_id 	id of the order being refunded
	 * @param 	float 	$amount 	amount being refunded
	 * @param 	string 	$reason 	reason of the refund
	 *
	 * @return 	boolean
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$order = new WC_Order( $order_id );

		$svea_order_id = ( $value = get_post_meta( $order->get_id(), "_svea_order_id", true ) ) 
                    ? $value : wc_get_order_item_meta( $order->get_id(), "svea_order_id" );

        if( strlen( (string) $svea_order_id ) <= 0 ) {
            return false;
        }

        $credit_name = __( 'Refund', WC_SveaWebPay_Gateway::PLUGIN_SLUG );

        if( strlen( $reason ) > 0 )
            $credit_name .= ': ' . $reason;

        $response = WebPayAdmin::creditOrderRows( $this->config )
        	->setOrderId( $svea_order_id )
        	->setCountryCode( $order->get_billing_country() )
        	->addCreditOrderRow( 
	        	WebPayItem::orderRow()
	        		->setAmountExVat( (float) $amount )
                    ->setVatPercent( 0 )
	        		->setDiscountPercent( 0 )
	        		->setName( $credit_name )
	        		->setQuantity( 1 )
        	)
        	->creditCardOrderRows()
        	->doRequest();

        if( ! $response || ! isset( $response->accepted ) || ! $response->accepted ) {
            return new WP_Error( 'error', $response->errormessage );
        }

        return true;
	}

	/**
	 * Process payment and redirect ot the checkout payment url
	 *
	 * @param int $order_id The id of the order being processed
	 * @return array
	 */
	function process_payment( $order_id ) {
		$wc_order = wc_get_order( $order_id );

		$order_number = get_post_meta( $order_id, "_svea_order_number", true );

		if( $order_number === false || strlen( (string) $order_number ) <= 0 ) {
			$order_number = 1;
			update_post_meta( $order_id, '_svea_order_number', $order_number );
		} else {
			$order_number = (int) $order_number;
			$order_number += 1;
			update_post_meta( $order_id, '_svea_order_number', $order_number );
		}

		$config = $this->get_config();
		
		$customer_country = $wc_order->get_billing_country();

		$customer_first_name = $wc_order->get_billing_first_name();
		$customer_last_name = $wc_order->get_billing_last_name();
		$customer_address_1 = $wc_order->get_billing_address_1();
		$customer_address_2 = $wc_order->get_billing_address_2();
		$customer_zip_code = $wc_order->get_billing_postcode();
		$customer_city = $wc_order->get_billing_city();
		$customer_country = $wc_order->get_billing_country();
		$customer_email = $wc_order->get_billing_email();
		$customer_phone = $wc_order->get_billing_phone();

		$customer_information = WebPayItem::individualCustomer()
			->setName( $customer_first_name, $customer_last_name)
			->setStreetAddress($customer_address_1)
			->setZipCode( $customer_zip_code )
			->setLocality( $customer_city )
			->setIpAddress( $_SERVER['REMOTE_ADDR'] )
			->setEmail($customer_email)
			->setPhoneNumber($customer_phone)	
			->setCoAddress($customer_address_2);

		/**
		 * If we are hooked into WooCommerce subscriptions,
		 * see if any payment is required right now
		 */
		if( class_exists( 'WC_Subscriptions_Order' ) 
			&& ( wcs_is_subscription( $wc_order->get_id() )
				|| wcs_order_contains_subscription( $wc_order, array( 'order_type' => 'any' ) ) )
			&& $wc_order->get_total() <= 0 ) {

			self::log( "Creating order for capturing card in subscription ID" );

			$svea_order = WebPay::createOrder( $config );

			/**
			 * The order total is 0 but we need to
			 * create a subscription for the customer in
			 * svea so we send a reservation payment of 1 SEK
			 * which will be refunded after 5 days
			 */
			$svea_order->addOrderRow( 
                WebPayItem::orderRow()
                    ->setArticleNumber( sanitize_title( __( 'Reservation', WC_SveaWebPay_Gateway::PLUGIN_SLUG ) ) )
                    ->setQuantity( 1 )
                    ->setAmountIncVat( 1.00 )
                    ->setName( __( 'Reservation', WC_SveaWebPay_Gateway::PLUGIN_SLUG ) )
                    ->setUnit( "st" )
                    ->setVatPercent( 0 )
                    ->setDiscountPercent( 0 )
            );

			$wc_return_url = esc_url_raw( add_query_arg( 'wc-api', 'wc_gateway_svea_card', $this->get_return_url( $wc_order ) ) );

	        $wc_callback_url = esc_url_raw(
	            add_query_arg( array( 'wc-api' => 'wc_gateway_svea_card', 'svea-callback' => true ), $this->get_return_url( $wc_order ) )
	        );

	        /**
	         * If the payment was issued in the checkout page,
	         * send the user back if they cancel the order in Svea.
	         */
	        if( is_checkout() ) {
	        	$wc_cancel_url = esc_url_raw( wc_get_checkout_url() );
	        } else {
	        	$wc_cancel_url = esc_url_raw( $wc_order->get_cancel_order_url() );
	        }

			try {
				$response = $svea_order->addCustomerDetails( $customer_information )
					->setClientOrderNumber( $order_id . '_' . $order_number )
					->setCurrency( $wc_order->get_currency() )
					->setCountryCode( $customer_country )
					->usePaymentMethod( $this->card_payment_method )
					->setReturnUrl( $wc_return_url )
					->setCallbackUrl( $wc_callback_url )
					->setCancelUrl( $wc_cancel_url )
					->setCardPageLanguage( $this->language )
					->setSubscriptionType( Svea\WebPay\HostedService\Payment\HostedPayment::RECURRING )
					->getPaymentUrl();
			} catch( Exception $e ) {
				self::log( "An error occurred when creating order: " . $e->getMessage() );
				wc_add_notice( __( 'An error occurred whilst connecting to Svea. Please contact the store owner and display this message.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
				return array(
					'result'	=> 'failure',
				);
			}

			if( ! $response || ! isset( $response->accepted ) || ! $response->accepted ) {
				if( isset( $response->resultcode ) ) {
					wc_add_notice( WC_Gateway_Svea_Helper::get_svea_error_message( (int) $response->resultcode ), 'error' );
					self::log( "Received error message from Svea: " . $response->errormessage );
				} else if( isset ( $response->errormessage ) ) {
					wc_add_notice( $response->errormessage, 'error' );
					self::log( "Received error message from Svea: " . $response->errormessage );
				} else {
					wc_add_notice( __( 'An unknown error occurred. Please contact the store owner about this issue.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
					self::log( "Error: Response error message was empty" );
				}
				
				return array(
					'result'	=> 'failure',
				);
			}

			$payment_url = $this->testmode ? $response->testurl : $response->url;

			if( ! is_string( $payment_url ) || ! strlen( $payment_url ) > 0 ) {
				wc_add_notice( __( 'An unknown error occurred. Please contact the store owner about this issue.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
				self::log( "Error: Payment URL is empty" );
				return array(
					'result'	=> 'failure',
				);
			}

			self::log( "User redirected to payment URL" );

			return array(
				'result' 	=> 'success',
				'redirect'	=> $payment_url
			);
		}

		self::log( sprintf( "Creating order by id %s", $order_id ) );

		$svea_order = WC_Gateway_Svea_Helper::create_svea_order( $wc_order, $config );

        $wc_return_url = esc_url_raw( add_query_arg( 'wc-api', 'wc_gateway_svea_card', $this->get_return_url( $wc_order ) ) );

        $wc_callback_url = esc_url_raw(
            add_query_arg( array( 'wc-api' => 'wc_gateway_svea_card', 'svea-callback' => true ), $this->get_return_url( $wc_order ) )
        );

        /**
         * If the payment was issued in the checkout page,
         * send the user back if they cancel the order in Svea.
         */
        if( is_checkout() ) {
        	$wc_cancel_url = esc_url_raw( wc_get_checkout_url() );
        } else {
        	$wc_cancel_url = esc_url_raw( $wc_order->get_cancel_order_url() );
        }

        $wc_cancel_url = esc_url_raw( wc_get_checkout_url() );

		try {
			$request = $svea_order->addCustomerDetails( $customer_information )
				->setClientOrderNumber( $order_id . '_' . $order_number )
				->setCurrency( $wc_order->get_currency() )
				->setCountryCode( $customer_country )
				->usePaymentMethod( $this->card_payment_method )
				->setReturnUrl( $wc_return_url )
				->setCallbackUrl( $wc_callback_url )
				->setCancelUrl( $wc_cancel_url )
				->setCardPageLanguage( $this->language );

			if( class_exists( 'WC_Subscriptions_Order' )
				&& ( wcs_is_subscription( $wc_order->get_id() )
				|| wcs_order_contains_subscription( $wc_order, array( 'order_type' => 'any' ) ) ) ) {
				$request = $request->setSubscriptionType( Svea\WebPay\HostedService\Payment\HostedPayment::RECURRINGCAPTURE );
				self::log( "Setting order to subscription" );
			}

			$response = $request->getPaymentUrl();

		} catch( Exception $e ) {
			wc_add_notice( __( 'An error occurred whilst connecting to Svea. Please contact the store owner and display this message.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
			self::log( "Received error when creating order: " . $e->getMessage() );
			return array(
				'result'	=> 'failure',
			);
		}

		if( ! $response || ! isset( $response->accepted ) || ! $response->accepted ) {
			if( isset( $response->resultcode ) ) {
				wc_add_notice( WC_Gateway_Svea_Helper::get_svea_error_message( (int) $response->resultcode ), 'error' );
				self::log( "Received error message from Svea: " . $response->errormessage );
			} else if( isset ( $response->errormessage ) ) {
				wc_add_notice( $response->errormessage, 'error' );
				self::log( "Received error message from Svea: " . $response->errormessage );
			} else {
				wc_add_notice( __( 'An unknown error occurred. Please contact the store owner about this issue.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
				self::log( "Error: Response error message was empty" );
			}

			return array(
				'result'	=> 'failure',
			);
		}

		$payment_url = $this->testmode ? $response->testurl : $response->url;

		if( ! is_string( $payment_url ) || ! strlen( $payment_url ) > 0 ) {
			wc_add_notice( __( 'An unknown error occurred. Please contact the store owner about this issue.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
			self::log( "Error: Payment URL was empty" );
			return array(
				'result'	=> 'failure',
			);
		}

		self::log( "User redirected to payment URL" );

		return array(
			'result' 	=> 'success',
			'redirect'	=> $payment_url
		);
	}

	/**
	 * Handles scheduled subscription payments
	 *
	 * @param 	float 		$amount_to_charge 	the amount that should be charged
	 * @param 	WC_Order 	$wc_order 			the original order
	 * @param 	int 		$product_id 		id of the subscription product
	 * @return 	void
	 */
	public function scheduled_subscription_payment( $amount_to_charge, $wc_order ) {

		self::log( "Subscription payment called" );

		$config = $this->get_config();
		$customer_country = $wc_order->get_billing_country();

		/**
		 * Get the subscription from the WooCommerce order
		 */
		$subscriptions = wcs_get_subscriptions_for_order( $wc_order->get_id(), array( 'order_type' => 'any' ) );

		$subscription = array_shift( $subscriptions );

		// REMOVED AS OF VERSION 2.8.0 - THERE SHOULD BE NO ORDERS THAT HAVE THIS STRUCTURE LEFT
		// $wc_original_order = false;
		// /**
		//  * In some cases the original order might not be set, we need to
		//  * check for that
		//  */
		// if( $subscription->get_parent() !== false ) {
		// 	$wc_original_order = $subscription->get_parent();
		// }

		/**
		 * Get the subscription id from the subscription
		 */
		$subscription_id = get_post_meta( $subscription->get_id(), '_svea_subscription_id', true );

		// /**
		//  * We want to move subscription by subscription to the new system
		//  */
		// if( isset( $wc_original_order ) && $wc_original_order ) {
		// 	$old_field_value = wc_get_order_item_meta( $wc_original_order->id, 'svea_subscription_id' );

		// 	if( $old_field_value ) {
		// 		$original_order_subscriptions = wcs_get_subscriptions_for_order( $wc_original_order->id, array( 'order_type' => 'any' ) );
				
		// 		foreach( $original_order_subscriptions as $original_order_subscription ) {
		// 			/**
		// 			 * Update the subscription if it does not already contain the data
		// 			 */
		// 			update_post_meta( 
		// 				$original_order_subscription->id, 
		// 				'_svea_subscription_id',
		// 				$old_field_value
		// 			);

		// 			wc_delete_order_item_meta( $wc_original_order->id, 'svea_subscription_id' );
		// 		}
		// 	}
		// }

		// $svea_order = WC_Gateway_Svea_Helper::create_svea_subscription_order( $wc_order, $config );
		// Use same helper function for both subscription payments and regular ones
		$svea_order = WC_Gateway_Svea_Helper::create_svea_order( $wc_order, $config );

		$response = $svea_order->setClientOrderNumber( $wc_order->get_id() )
			->setCurrency( $wc_order->get_currency() )
			->setCountryCode( $wc_order->get_billing_country() )
			->usePaymentMethod( $this->card_payment_method )
			->setSubscriptionId( $subscription_id )
			->doRecur();

		/**
		 * Handle errors and denied purchases
		 */
		if( ! $response || ! isset( $response->accepted ) || ! $response->accepted ) {
			WC_Subscriptions_Manager::process_subscription_payment_failure_on_order( $wc_order );

			$wc_order->update_status( 'failed' );

			if( isset ( $response->errormessage ) ) {
				$wc_order->add_order_note( __( 'Error occurred whilst processing subscription: ', WC_SveaWebPay_Gateway::PLUGIN_SLUG ) . $response->errormessage );
				self::log( "Received error message: " . $response->errormessage );
            } else {
                $wc_order->add_order_note( __( 'Error occurred whilst processing subscription: ', WC_SveaWebPay_Gateway::PLUGIN_SLUG ) . __( 'An unknown error occurred. Please contact the store owner about this issue.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ) );
				self::log( "Error: Response error message was empty" );
            }

			return;
        }

        /**
         * Fetch the transaction id from the response
         */
		$svea_order_id = $response->transactionId;

		WC_Subscriptions_Manager::process_subscription_payments_on_order( $wc_order );

		/** 
		 * Save Svea's order id on the newly created subscription order
		 * so that we can administrate it in the future
		 */
		update_post_meta( $wc_order->get_id(), '_svea_order_id', $svea_order_id );
		$wc_order->payment_complete( $svea_order_id );

		self::log( "Scheduled subscription payment successful" );
	}

	/**
	 * Cancels the order in svea
	 *
	 * @param 	WC_Order 	$order 	the order being cancelled
	 * @param 	string 		$svea_order_id	id of the svea order
	 * @return 	array 		an array containing result and message
	 */
	public function cancel_order( $order, $svea_order_id ) {
		$config = $this->get_config( $order->get_billing_country() );

		$response = WebPayAdmin::cancelOrder( $config )
                    ->setCountryCode( $order->get_billing_country() )
                    ->setOrderId( $svea_order_id )
                    ->cancelCardOrder()
                    ->doRequest();

        if( ! $response || ! isset( $response->accepted ) || ! $response->accepted ) {
            return array(
            	"success"	=> false,
            	"message"	=> $response->errormessage
            );
        }

        /**
         * The request was successful
         */
        $order->add_order_note(
            __( 'The order has been cancelled in Svea.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ) 
        );

        $order->update_status( 'cancelled' );

        return array(
        	"success"	=> true,
        	"message"	=> __( 'The order has been cancelled in Svea.', WC_SveaWebPay_Gateway::PLUGIN_SLUG )
        );
	}

	/**
	 * Credits the order in svea
	 *
	 * @param 	WC_Order 	$order 	the order being credited
	 * @param 	string 		$svea_order_id	id of the svea order
	 * @return 	array 		an array containing result and message
	 */
	public function credit_order( $order, $svea_order_id ) {
		$config = $this->get_config();

    	$credit_order_request = WebPayAdmin::creditOrderRows( $config )
                ->setCountryCode( $order->get_billing_country() )
                ->setOrderId( $svea_order_id );

        $order_tax_percentage = 0;

        if( $order->get_total_tax() > 0 ) {
        	$order_tax_percentage = ( $order->get_total_tax() / ( $order->get_total() - $order->get_total_tax() ) ) * 100;
		}

        $credit_order_request->addCreditOrderRow(
        	WebPayItem::orderRow()
        	->setAmountExVat( $order->get_total() - $order->get_total_tax() )
        		->setVatPercent( $order_tax_percentage )
        		->setQuantity( 1 )
        		->setDescription( 
        			sprintf( __( "Credited order #%s" ), $svea_order_id )
        		)
        );

        $response = $credit_order_request->creditCardOrderRows()
        					 ->doRequest();

        if( ! $response || ! isset( $response->accepted ) || ! $response->accepted ) {
        	return array(
        		"success"	=> false,
        		"message"	=> $response->errormessage
        	);
        }

        foreach( array_keys( $order->get_items( array( 'line_item', 'fee', 'shipping' ) ) ) as $order_item_id ) {
        	if( wc_get_order_item_meta( $order_item_id, 'svea_credited' ) ) {
                continue;
        	}

        	wc_update_order_item_meta( $order_item_id, 'svea_credited', date("Y-m-d H:i:s") );
        }

        $order->update_status( 'refunded' );

    	$order->add_order_note(
            __( 'All items have been credited in Svea.', WC_SveaWebPay_Gateway::PLUGIN_SLUG )
        );

        return array(
        	"success"	=> true,
        	"message"	=> __( 'All items have been credited in Svea.', WC_SveaWebPay_Gateway::PLUGIN_SLUG )
        );
	}

	/**
	 * Delivers the order in svea
	 *
	 * @param 	WC_Order 	$order 	the order being delivered
	 * @param 	string 		$svea_order_id	id of the svea order
	 * @param 	array 		$order_item_ids 	an optional array of order item ids
	 * @return 	array 		an array containing result and message
	 */
	public function deliver_order( $order, $svea_order_id, $order_item_ids = array() ) {
		$config = $this->get_config();

		$response = WebPayAdmin::queryOrder( $config )
					->setOrderId( $svea_order_id )
					->setCountryCode( $order->get_billing_country() )
					->queryCardOrder()
					->doRequest();

		if( ! $response || ! isset( $response->accepted ) || ! $response->accepted ) {
            return array(
            	"success"	=> false,
            	"message"	=> $response->errormessage
            );
        }

        if( $response->status === "SUCCESS"
        	|| $response->status === "CONFIRMED" ) {
        	
        	if( $order->get_status() == 'completed' ) {
        		return array(
		        	"success"	=> true,
		        	"message"	=> __( 'Order has already been delivered', WC_SveaWebPay_Gateway::PLUGIN_SLUG )
		        );
        	}

        	foreach( $order->get_items( array( 'line_item', 'fee', 'shipping' ) ) as $order_item_id => $order_item ) {
	            if( wc_get_order_item_meta( $order_item_id, 'svea_delivered' ) )
	            	continue;
	            wc_update_order_item_meta( $order_item_id, 'svea_delivered', date("Y-m-d H:i:s") );
	        }

        	$order->update_status( 'completed' );

        	$order->add_order_note(
	            __( 'All items have been delivered in Svea.', WC_SveaWebPay_Gateway::PLUGIN_SLUG )
	        );

	        return array(
	        	"success"	=> true,
	        	"message"	=> __( 'All items have been delivered in Svea.', WC_SveaWebPay_Gateway::PLUGIN_SLUG )
	        );
        }

        $response = WebPay::deliverOrder( $config )
                    ->setOrderId( $svea_order_id )
                    ->setCountryCode( $order->get_billing_country() )
                    ->deliverCardOrder()
                    ->doRequest();

        if( ! $response || ! isset( $response->accepted ) || ! $response->accepted ) {
            return array(
            	"success"	=> false,
            	"message"	=> $response->errormessage
            );
        }

        foreach( $order->get_items( array( 'line_item', 'fee', 'shipping' ) ) as $order_item_id => $order_item ) {
            if( wc_get_order_item_meta( $order_item_id, 'svea_delivered' ) )
            	continue;
            wc_update_order_item_meta( $order_item_id, 'svea_delivered', date("Y-m-d H:i:s") );
        }

        $order->update_status( 'completed' );

        /**
         * The request was successful
         */
        $order->add_order_note(
            __( 'All items have been delivered in Svea.', WC_SveaWebPay_Gateway::PLUGIN_SLUG )
        );

        return array(
        	"success"	=> true,
        	"message"	=> __( 'All items have been delivered in Svea.', WC_SveaWebPay_Gateway::PLUGIN_SLUG )
        );
	}
}
