<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use Svea\WebPay\Constant\PaymentMethod;
use Svea\WebPay\Response\SveaResponse;
use Svea\WebPay\WebPayItem;
use Svea\WebPay\WebPayAdmin;

Class WC_Gateway_Svea_Direct_Bank extends WC_Payment_Gateway {

    /**
     * Id of this gateway
     *
     * @var     string
     */
    const GATEWAY_ID = 'sveawebpay_direct_bank';

	/**
	 * Static instance of this class
     *
     * @var     WC_Gateway_Svea_Direct_Bank
     */
    private static $instance = null;

    private static $log_enabled = false;
    private static $log = null;

    public static function init() {
        if ( is_null( self::$instance ) ) {
            $instance = new WC_Gateway_Svea_Direct_Bank;
        }
        return self::$instance;
    }

	public function __construct() {
        if( is_null( self::$instance ) )
            self::$instance = $this;

        $this->supports = array(
            'products',
            //'refunds'
        );

		$this->id = self::GATEWAY_ID;

		$this->method_title = __( 'SveaWebPay Direct Bank Payment', WC_SveaWebPay_Gateway::PLUGIN_SLUG );
		$this->icon = apply_filters('woocommerce_sveawebpay_direct_bank_icon', WC_SVEAWEBPAY_GATEWAY_PLUGIN_URL . 'assets/img/bank-icon.png' );
        $this->has_fields = true;

		$this->init_form_fields();
		$this->init_settings();

        $this->title = __( $this->get_option( 'title' ), WC_SveaWebPay_Gateway::PLUGIN_SLUG );

        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        add_action( 'woocommerce_api_wc_gateway_svea_direct_bank', array( $this, 'handle_callback_request' ) );

		$this->enabled = $this->get_option('enabled');

        $wc_countries = new WC_Countries();
        $this->base_country = $wc_countries->get_base_country();

		$this->merchant_id = $this->get_option('merchant_id');
		$this->secret_word = $this->get_option('secret_word');
		$this->testmode = $this->get_option('testmode') === "yes";
		$this->language = $this->get_option('language');
        self::$log_enabled = $this->get_option( 'debug' ) === "yes";

		$this->active_direct_bank_gateway = $this->get_option( 'active_direct_bank_gateway' );

        $this->selected_currency = get_woocommerce_currency();

		$this->payment_methods = array(
    		PaymentMethod::BANKAXESS => array('payment_method' => PaymentMethod::BANKAXESS, 'allowed_countries' => array( 'NO' ),
    			'logo' => WC_SVEAWEBPAY_GATEWAY_PLUGIN_URL . 'assets/img/logo_bankaxess.gif', 'label' => __( 'Direct bank payments, Norway', WC_SveaWebPay_Gateway::PLUGIN_SLUG ) ),

    		PaymentMethod::NORDEA_SE => array('payment_method' => PaymentMethod::NORDEA_SE, 'allowed_countries' => array( 'SE' ),
    			'logo' => WC_SVEAWEBPAY_GATEWAY_PLUGIN_URL . 'assets/img/logo_nordea.gif', 'label' => __( 'Direct bank payment, Nordea, Sweden', WC_SveaWebPay_Gateway::PLUGIN_SLUG ) ),

    		PaymentMethod::SEB_SE => array('payment_method' => PaymentMethod::SEB_SE, 'allowed_countries' => array( 'SE' ),
    			'logo' => WC_SVEAWEBPAY_GATEWAY_PLUGIN_URL . 'assets/img/DBSEBSE.png', 'label' => __( 'Direct bank payment, private, SEB, Sweden', WC_SveaWebPay_Gateway::PLUGIN_SLUG ) ),

    		PaymentMethod::SEBFTG_SE => array('payment_method' => PaymentMethod::SEBFTG_SE, 'allowed_countries' => array( 'SE' ),
    			'logo' => WC_SVEAWEBPAY_GATEWAY_PLUGIN_URL . 'assets/img/DBSEBFTGSE.png', 'label' => __( 'Direct bank payment, company, SEB, Sweden', WC_SveaWebPay_Gateway::PLUGIN_SLUG ) ),

    		PaymentMethod::SHB_SE => array('payment_method' => PaymentMethod::SHB_SE, 'allowed_countries' => array( 'SE' ),
    			'logo' => WC_SVEAWEBPAY_GATEWAY_PLUGIN_URL . 'assets/img/logo_handelsbanken.gif', 'label' => __( 'Direct bank payment, Handelsbanken, Sweden', WC_SveaWebPay_Gateway::PLUGIN_SLUG ) ),

    		PaymentMethod::SWEDBANK_SE => array('payment_method' => PaymentMethod::SWEDBANK_SE, 'allowed_countries' => array( 'SE' ),
    			'logo' => WC_SVEAWEBPAY_GATEWAY_PLUGIN_URL . 'assets/img/logo_swedbank.gif', 'label' => __( 'Direct bank payment, Swedbank, Sweden', WC_SveaWebPay_Gateway::PLUGIN_SLUG ) ),

    		PaymentMethod::PAYPAL => array('payment_method' => PaymentMethod::PAYPAL, 'allowed_countries' => array( 'SE', 'DK', 'NO', 'FI', 'NL', 'DE' ),
    			'logo' => WC_SVEAWEBPAY_GATEWAY_PLUGIN_URL . 'assets/img/logo_paypal.png', 'label' => __( 'PayPal', WC_SveaWebPay_Gateway::PLUGIN_SLUG ) ),

    		PaymentMethod::SKRILL => array('payment_method' => PaymentMethod::SKRILL, 'allowed_countries' => array( 'DK' ),
    			'logo' => WC_SVEAWEBPAY_GATEWAY_PLUGIN_URL . 'assets/img/logo_skrill.gif', 'label' => __( 'Card payment with Dankort, Skrill', WC_SveaWebPay_Gateway::PLUGIN_SLUG ) )
    	);

		$config_class = $this->testmode ? "WC_Svea_Config_Test" : "WC_Svea_Config_Production";

		$this->config = new $config_class( $this->merchant_id, $this->secret_word, false, false, false );

        $this->description = __( $this->get_option( 'description' ), WC_SveaWebPay_Gateway::PLUGIN_SLUG );
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

        $post_data = array();

        if( isset($_POST["post_data"]) ) {
            $values = explode("&", $_POST["post_data"]);
            if(count($values) > 0) {
                foreach($values as $value) {
                    $explode = explode("=", $value);
                    if(count($explode) !== 2)
                        continue;
                    $post_data[$explode[0]] = $explode[1];
                }
            }
        }

        $payment_methods = $this->get_available_payment_methods();

        if( count( $payment_methods ) <= 0 )
            return;
        
        $options = array();

        foreach( $payment_methods as $payment_method ) {
            $options[$payment_method['payment_method']] = $payment_method['label'];
        }

        ?>
        <div class="direct-bank-payment-method">
            <h3><?php _e('Payment Method', WC_SveaWebPay_Gateway::PLUGIN_SLUG ); ?></h3>
            <?php
                woocommerce_form_field( 'direct_bank_payment_method', array(
                    'type'          => 'radio',
                    'required'      => false,
                    'class'         => array('form-row-wide'),
                    'options'       => $options,
                ), isset( $post_data["direct_bank_payment_method"] ) ? $post_data["direct_bank_payment_method"] : null ); 
            ?>
        </div>
        <?php
    }

    public static function display_admin_action_buttons() {
        ?><button type="button" class="button svea-credit-items"><?php _e( 'Credit via svea', WC_SveaWebPay_Gateway::PLUGIN_SLUG ); ?></button><?php
    }

    public static function admin_functions_meta_box() {
        $order = new WC_Order( get_the_ID() );

        $credit_nonce = wp_create_nonce( WC_SveaWebPay_Gateway_Admin_Functions::CREDIT_NONCE );

        ?>
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

        if( ! is_admin() ) {
            if( ! $this->check_customer_country() ) {
                return false;
            } else if( ! $this->check_customer_currency() ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if the current country is supported and enabled
     *
     * @return  boolean
     */
    public function check_customer_country() {
        $customer_country = strtoupper( WC()->customer->get_billing_country() );

        if( ! in_array( $customer_country, WC_Gateway_Svea_Helper::$ALLOWED_COUNTRIES_ASYNC )
            || count( $this->get_available_payment_methods( $customer_country ) ) <= 0 ) {
            return false;
        }

        return true;
    }

    /**
     * Check if the current currency is supported
     *
     * @return  boolean
     */
    public function check_customer_currency() {
        $country_currency = WC_Gateway_Svea_Helper::get_country_currency( WC()->customer->get_billing_country() );

        if( ! isset( $country_currency[0] )
            || $country_currency !== $this->selected_currency ) {
            return false;
        }

        return true;
    }

	/**
	 * Check if the plugin gateway can be used with the specified country/currency.
	 *
	 * @return array
	 */
	public function direct_bank_is_active_and_set() {
        $wc_countries = new WC_Countries();

		if ( ! in_array( get_woocommerce_currency(), WC_Gateway_Svea_Helper::$ALLOWED_CURRENCIES_ASYNC ) 
			|| ( ! in_array( $wc_countries->get_base_country(), WC_Gateway_Svea_Helper::$ALLOWED_COUNTRIES_ASYNC ) ) )  {
			return array( "error" => true, "errormsg" => "Invalid country code/currency", WC_SveaWebPay_Gateway::PLUGIN_SLUG );
		} else if ( version_compare( WOOCOMMERCE_VERSION, '2.0', '<' ) ) {
			return array( "error" => true, "errormsg" => "Invalid woocommerce version", WC_SveaWebPay_Gateway::PLUGIN_SLUG );
        }

		return array( "error" => false );
	}

	/**
	 * If the payment gateway is properly configured, generate the settings
	 *
	 * @return     void
	 */
	public function admin_options() {
    	?>
    	<h3><?php _e('SveaWebPay Direct Bank Payment', WC_SveaWebPay_Gateway::PLUGIN_SLUG ); ?></h3>
    	<p><?php _e('Process direct bank payments through SveaWebPay.', WC_SveaWebPay_Gateway::PLUGIN_SLUG )?></p>
    	<?php $result = $this->direct_bank_is_active_and_set(); ?>
    	<?php if ( ! $result["error"] ) : ?>
    	<table class="form-table">
    	   <?php $this->generate_settings_html(); ?>
    	</table>
    	<?php else : ?>
    	<div class="inline error">
            <p><?php _e( $result["errormsg"], WC_SveaWebPay_Gateway::PLUGIN_SLUG ); ?></p>
        </div>
        <?php endif;
	}

	/**
	 * Initialize the form fields for the payment gateway
	 *
	 * @return     void
	 */
	function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
						'title' => __( 'Enable/disable', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
						'type' => 'checkbox',
						'label' => __('Enable SveaWebPay Direct Bank Payments', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
						'default' => 'no'
				),
			'title' => array(
						'title' => __( 'Title', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
						'type' => 'text',
						'description' => __('This controls the title which the user sees during checkout', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
						'default' => __( 'Direct Bank Payment', WC_SveaWebPay_Gateway::PLUGIN_SLUG )
				),
			'description' => array(
						'title' => __( 'Description', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
						'type' => 'textarea',
						'description' => __( 'This controls the description the user sees during checkout', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
						'default' => __( 'Pay with direct bank payments through Svea Ekonomi', WC_SveaWebPay_Gateway::PLUGIN_SLUG )
				),
			'merchant_id' => array(
						'title' => __( 'Merchant id', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
						'type' => 'text',
						'description' => __('Your SveaWebPay merchant id', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
						'default' => ''
				),
			'secret_word' => array(
						'title' => __( 'Secret word', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
						'type' => 'password',
						'description' => __('Your SveaWebPay secret word', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
						'default' => ''
				),
			    'testmode' => array(
						'title' => __( 'Test mode', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
						'type' => 'checkbox',
						'description' => __('Enable test mode for SveaWebPay Direct Bank Payment', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
						'default' => 'no'
				),
			     'active_direct_bank_gateway' => array(
					'title' => __( 'Enabled direct bank transfer methods', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
					'type' => 'multiselect',
					'description' => 'Choose the direct bank transfer methods that you want to enable',
					'options' => array(
										PaymentMethod::BANKAXESS => __( 'Direct bank payments, Norway', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
										PaymentMethod::NORDEA_SE => __( 'Direct bank payment, Nordea, Sweden', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
										PaymentMethod::SEB_SE => __( 'Direct bank payment, private, SEB, Sweden', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
										PaymentMethod::SEBFTG_SE => __( 'Direct bank payment, company, SEB, Sweden', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
										PaymentMethod::SHB_SE => __( 'Direct bank payment, Handelsbanken, Sweden', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
										PaymentMethod::SWEDBANK_SE => __( 'Direct bank payment, Swedbank, Sweden', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
										PaymentMethod::PAYPAL => __( 'Paypal', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
										PaymentMethod::SKRILL => __( 'Card payment with Dankort, Skrill', WC_SveaWebPay_Gateway::PLUGIN_SLUG )
						),
					'default' => ''
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
	 * Get an instance of the configuration
	 *
	 * @return WC_Svea_Config_Production|WC_Svea_Config_Test   the configuration object
	 */
    public function get_config () {
		if( ! isset($this->config) ) {
			if ($this->testmode == "yes") {
				$this->config = new WC_Svea_Config_Test();
			} else if ($this->testmode == "no") {
				$this->config = new WC_Svea_Config_Production();
			} else {
				$this->config = false;
			}
		}
		return $this->config;
	}

	public function checkout_validation_handler() {
        global $woocommerce;

		if( ! isset($_POST["payment_method"]) )
			return;

		$payment_method = $_POST["payment_method"];

		if( $payment_method != $this->id )
			return;

		$customer_country = $_POST["billing_country"];
		$email = $_POST["billing_email"];

		if( ! isset($_POST["direct_bank_payment_method"]) || empty($_POST["direct_bank_payment_method"]) ) {
			wc_add_notice( __( 'Please enter direct bank payment method.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
			return;
		}

		$direct_bank_method = $_POST["direct_bank_payment_method"];

        if( ! $this->is_valid_payment_method( $direct_bank_method, $customer_country ) ) {
            wc_add_notice( __( 'That payment method is not supported in your country.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
            return;
        }
	}

    /** 
     * Check whether or not the payment method is valid for the provided country
     *
     * @param   string      $payment_method     the payment method
     * @param   string      $customer_country   the customer's country
     * @return  boolean     whether or not the payment method is valid
     */
    public function is_valid_payment_method( $payment_method, $customer_country ) {
        if( ! isset( $this->payment_methods[ $payment_method ] ) )
            return false;

        if( ! in_array( $customer_country, $this->payment_methods[ $payment_method ][ 'allowed_countries' ] ) )
            return false;

        return true;
    }

    /**
	 * Get the available payment methods, depending on the country.
	 *
     * @param      string   $customer_country   country of the customer
	 * @return     array    an array containing available payment methods
	 */
    public function get_available_payment_methods( $customer_country = null ) {
    	$payment_methods = array();

    	if( $this->active_direct_bank_gateway === false || 
    		! is_array( $this->active_direct_bank_gateway ) ) {
    		return array();
    	}

        if( is_null( $customer_country ) ) {
            $customer_country = strtoupper( WC()->customer->get_billing_country() );
        }

    	foreach( $this->active_direct_bank_gateway as $value ) {
            if( ! $this->is_valid_payment_method( $value, $customer_country ) )
                continue;

    		$arr = $this->payment_methods[ $value ];

    		$payment_methods[$arr["label"]] = array(
    			'payment_method' => $arr["payment_method"],
    			'logo' => $arr["logo"],
    			'label' => $arr["label"]
    		);
    	}

    	return $payment_methods;
    }
    
    /**
     * Accepted request inteprits the returned object and response code from Sveawebpay.
     *
     *
     * @return  void
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
        if( ! isset( $request["response"] ) || ! isset( $request["key"] ) ) {
            status_header( 400 );
            self::log( "Response parameter in request is not set" );
            exit;
        }

        $wc_key_order_id = (int) wc_get_order_id_by_order_key( $request["key"] );

        if( $wc_key_order_id === 0 ) {
            status_header( 404 );
            self::log( "Couldn't find order id by key" );
            exit;
        }

        $wc_key_order = new WC_Order( $wc_key_order_id );

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

        if( $wc_key_order_id !== $order_id ) {
            status_header( 403 );
            self::log( "Svea order id doesn't match WooCommerce key" );
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
            self::log( "Payment successful" );

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
     * @param   string  $order_id   id of the order being refunded
     * @param   float   $amount     amount being refunded
     * @param   string  $reason     reason of the refund
     *
     * @return  boolean
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
            ->creditDirectBankOrderRows()
            ->doRequest();

        if( ! $response || ! isset( $response->accepted ) || ! $response->accepted ) {
            return new WP_Error( 'error', $response->errormessage );
        }

        return true;
    }

    /**
	 * If the payment was sucessful, redirect to the payment page
	 *
     * @param      string   $order_id   the order id being processed
	 * @return     array
	 */
    function process_payment( $order_id ) {
        self::log( "Processing payment" );

        if( ! isset( $_POST["direct_bank_payment_method"] ) ) {
            wc_add_notice( __( 'Direct bank payment method must be selected.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
            self::log( "Direct bank payment method wasn't selected" );
            return array(
                'result'    => 'failure',
            );
        }

        $wc_order = new WC_Order($order_id);
        
        $order_number = ( $value = get_post_meta( $order_id, "_svea_order_number", true ) ) 
                    ? $value : wc_get_order_item_meta( $order_id, "svea_order_number" );

        if( $order_number === false || empty($order_number) ) {
            $order_number = 1;
            update_post_meta( $order_id, '_svea_order_number', $order_number );
        } else {
            $order_number = (int) $order_number;
            $order_number += 1;
            update_post_meta( $order_id, '_svea_order_number', $order_number );
        }

        $config = $this->get_config();

        $customer_first_name = $wc_order->get_billing_first_name();
        $customer_last_name = $wc_order->get_billing_last_name();
        $customer_address_1 = $wc_order->get_billing_address_1();
        $customer_address_2 = $wc_order->get_billing_address_2();
        $customer_zip_code = $wc_order->get_billing_postcode();
        $customer_city = $wc_order->get_billing_city();
        $customer_country = $wc_order->get_billing_country();
        $customer_email = $wc_order->get_billing_email();
        $customer_phone = $wc_order->get_billing_phone();

        $svea_order = WC_Gateway_Svea_Helper::create_svea_order($wc_order, $config);
        
        $customer_information = WebPayItem::individualCustomer()
            ->setName( $customer_first_name, $customer_last_name)
            ->setStreetAddress($customer_address_1)
            ->setZipCode( $customer_zip_code )
            ->setLocality( $customer_city )
            ->setIpAddress($_SERVER['REMOTE_ADDR'])
            ->setEmail($customer_email)
            ->setPhoneNumber($customer_phone)   
            ->setCoAddress($customer_address_2);

        $wc_return_url = esc_url_raw( add_query_arg( 'wc-api', 'wc_gateway_svea_direct_bank', $this->get_return_url( $wc_order ) ) );

        $wc_callback_url = esc_url_raw(
            add_query_arg( array( 'wc-api' => 'wc_gateway_svea_direct_bank', 'svea-callback' => true ), $this->get_return_url( $wc_order ) )
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
            $response = $svea_order->addCustomerDetails($customer_information)
                ->setClientOrderNumber($order_id . '_' . $order_number)
                ->setCurrency( $wc_order->get_currency() )
                ->setCountryCode($customer_country)
                ->usePaymentMethod( $_POST["direct_bank_payment_method"] )
                ->setReturnUrl( $wc_return_url )
                ->setCallbackUrl( $wc_callback_url )
                ->setCancelUrl( esc_url( wc_get_cart_url() ) )
                ->setCardPageLanguage( $this->language )
                ->getPaymentUrl();
        } catch(Exception $e) {
            self::log( "Error: " . $e->getMessage() );
            wc_add_notice( __('An error occurred whilst connecting to Svea. Please contact the store owner and display this message.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
            return array(
                'result'    => 'failure',
            );
        }

        if( ! $response || ! isset( $response->accepted ) || ! $response->accepted ) {
            if( isset ( $response->errormessage ) ) {
                wc_add_notice( $response->errormessage, 'error' );
                self::log( "Error: " . $response->errormessage );
            } else {
                wc_add_notice( __( 'An unknown error occurred. Please contact the store owner about this issue.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
                self::log( "Response error message was empty" );
            }
            
            return array(
                'result'    => 'failure',
            );
        }

        $payment_url = $this->testmode ? $response->testurl : $response->url;

        if( ! is_string( $payment_url ) || ! strlen( $payment_url ) > 0 ) {
            wc_add_notice( __( 'An unknown error occurred. Please contact the store owner about this issue.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
            self::log( "Payment URL was empty" );
            return array(
                'result'    => 'failure',
            );
        }

        self::log( "User redirected to payment URL" );

        return array(
            'result'    => 'success',
            'redirect'  => $payment_url
        );
    }

    /**
     * Credits the order in svea
     *
     * @param   WC_Order    $order  the order being credited
     * @param   string      $svea_order_id  id of the svea order
     * @param   array       $order_item_ids     an optional array of order item ids
     * @return  array       an array containing result and message
     */
    public function credit_order( $order, $svea_order_id, $order_item_ids = array() ) {
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

        $response = $credit_order_request->creditDirectBankOrderRows()
                             ->doRequest();


        if( ! $response || ! isset( $response->accepted ) || ! $response->accepted ) {
            return array(
                "success"   => false,
                "message"   => $response->errormessage
            );
        }

        foreach( array_keys( $order->get_items( array( 'line_item', 'fee', 'shipping' ) ) ) as $order_item_id ) {
            if( wc_get_order_item_meta( $order_item_id, 'svea_credited' ) )
                continue;
            wc_update_order_item_meta( $order_item_id, 'svea_credited', date("Y-m-d H:i:s") );
        }

        /**
         * The request was successful
         */
        $order->update_status( 'refunded' );

        $order->add_order_note(
            __( 'All items have been credited in Svea.', WC_SveaWebPay_Gateway::PLUGIN_SLUG )
        );

        return array(
            "success"   => true,
            "message"   => __( 'All items have been credited in Svea.', WC_SveaWebPay_Gateway::PLUGIN_SLUG )
        );
    }
}