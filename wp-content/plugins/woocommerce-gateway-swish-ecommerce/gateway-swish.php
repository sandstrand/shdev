<?php
if ( ! defined( 'ABSPATH' ) ) { 
    exit; // Exit if accessed directly
}
/**
 * Plugin Name: WooCommerce Swish Ecommerce Gateway
 * Plugin URI: https://redlight.se/swish
 * Description: Extends WooCommerce. Provides a <a href="https://www.getswish.se/" target="_blank">Swish Handel</a> gateway for WooCommerce.
 * Version: 2.1.2
 * Author: Redlight Media
 * Author URI: https://redlight.se/
 * Developer: Christopher Hedqvist
 * Developer URI: https://redlight.se/
 * Text Domain: woocommerce-gateway-swish-ecommerce
 * Domain Path: /languages
 * WC requires at least: 3.0.0
 * WC tested up to: 3.2.5
 *
 * Copyright: © 2015 Redlight.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

/* Initiate redlight swish when all plugins have loaded */
add_action('plugins_loaded', 'init_wc_gateway_swish_ecommerce', 0);


/**
 * Initiate the payment gateway.
 *
 * @access public
 * @return void
 */

function init_wc_gateway_swish_ecommerce() {

	if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;
	
	define( 'REDLIGHT_SA_STORE_URL', 'http://redlight.se' );
	define( 'REDLIGHT_SA_ITEM_NAME', 'Swish för handel (automatisk)' );

	if( !class_exists( 'EDD_SL_Plugin_Updater' ) ) {
		// load our custom updater
		include( dirname( __FILE__ ) . '/includes/EDD_SL_Plugin_Updater.php' );
	}
	function redlight_swish_ecommerce_plugin_updater() {
		// retrieve our license key from the DB
		$license = get_option( 'woocommerce_redlight_swish-ecommerce_settings');
		$license_key = $license['redlight_license_key'];
		// setup the updater
		$edd_updater = new EDD_SL_Plugin_Updater( REDLIGHT_SA_STORE_URL, __FILE__, array(
				'version' 	=> '2.1.2', 				// current version number
				'license' 	=> $license_key, 		// license key (used get_option above to retrieve from DB)
				'item_name' => REDLIGHT_SA_ITEM_NAME, 	// name of this plugin
				'author' 	=> 'Redlight Media'  // author of this plugin
				)
		);
	}
	add_action( 'admin_init', 'redlight_swish_ecommerce_plugin_updater', 0 );
	function redlight_swish_ecommerce_activate_license() {
		// listen for our activate button to be clicked
		if( isset( $_POST['woocommerce_redlight_swish-ecommerce_redlight_license_key'] ) ) {
			// retrieve the license from the database
			$license = get_option( 'woocommerce_redlight_swish-ecommerce_settings');
			$license = trim($license['redlight_license_key']);

			// data to send in our API request
			$api_params = array(
				'edd_action'=> 'activate_license',
				'license' 	=> $license,
				'item_name' => urlencode( REDLIGHT_SA_ITEM_NAME ), // the name of our product in EDD
				'url'       => home_url()
				);

			// Call the custom API.
			$response = wp_remote_post( REDLIGHT_SA_STORE_URL, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

			// make sure the response came back okay
			if ( is_wp_error( $response ) )
				return false;

			// decode the license data
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );
			
			// $license_data->license will be either "valid" or "invalid"
			update_option( 'redlight_swish_ecommerce_license_status', $license_data->license );

		}
	}
	add_action('admin_init', 'redlight_swish_ecommerce_activate_license');	
	
	function redlight_swish_ecommerce_deactivate_license() {
		// listen for our activate button to be clicked
		if( isset( $_POST['woocommerce_redlight_swish-ecommerce_redlight_license_deactivate'] ) ) {
			// retrieve the license from the database
			$license = get_option( 'woocommerce_redlight_swish-ecommerce_settings');
			$license = trim($license['redlight_license_key']);
			
			// data to send in our API request
			$api_params = array(
				'edd_action'=> 'deactivate_license',
				'license' 	=> $license,
				'item_name' => urlencode( REDLIGHT_SA_ITEM_NAME ), // the name of our product in EDD
				'url'       => home_url()
				);

			// Call the custom API.
			$response = wp_remote_post( REDLIGHT_SA_STORE_URL, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

			// make sure the response came back okay
			if ( is_wp_error( $response ) )
				return false;

			// decode the license data
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			// $license_data->license will be either "deactivated" or "failed"
			if( $license_data->license == 'deactivated' )
				delete_option( 'redlight_swish_ecommerce_license_status' );
		}
	}
	add_action('admin_init', 'redlight_swish_ecommerce_deactivate_license');
	// Localisation
	load_plugin_textdomain('woocommerce-gateway-swish-ecommerce', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/');
	
	add_filter('woocommerce_payment_gateways', 'add_wc_gateway_swish_ecommerce' );
	
	class WC_Gateway_Swish_Ecommerce extends WC_Payment_Gateway {

		
		/** @var boolean Whether or not logging is enabled */
		public static $log_enabled = false;

		/** @var WC_Logger Logger instance */
		public static $log = false;
		
		/**
		 * Cloning is forbidden.
		 *
		 * @since 1.2
		 */
		public function __clone() {}
		/**
		 * Unserializing instances of this class is forbidden.
		 *
		 * @since 1.2
		 */
		public function __wakeup() {}

		/**
		 * Constructor for the Swish gateway.
		 */
		function __construct() {
		  // Run the activation function
			register_activation_hook( __FILE__, array( $this, 'activation' ) );

			$this->id					= "redlight_swish-ecommerce";
			$this->has_fields 			= true;
			$this->method_title 		= "Swish Handel";
			$this->method_description 	= __( "Extends WooCommerce. Provides a <a href='http://www.getswish.se/handel' target='_blank'>Swish Handel</a> gateway for WooCommerce.", 'woocommerce-gateway-swish-ecommerce' );
			$this->title 				= __( "Swish Handel", 'woocommerce-gateway-swish-ecommerce' );
			$this->icon 				= plugins_url( 'assets/images/swish_logo.png', __FILE__ );
			$this->swishimglogo 		= plugins_url( 'assets/images/Swish-logo-image-vert.png', __FILE__ );
			$this->swishtextlogo 		= plugins_url( 'assets/images/Swish-logo-text-vert.png', __FILE__ );
			$this->swishecommerceexample = plugins_url( 'assets/images/Swish_460_550.png', __FILE__ );
			$this->callback_url 		= WC()->api_request_url( 'WC_Gateway_Swish_Ecommerce', true);
			$this->supports          	= array(
				'products',
				'refunds',
				);
			
          //Prepare css
			wp_register_style( 'swish', plugins_url( 'assets/css/swish.css', __FILE__ ) );
			
          // Load Settings
			$this->init_form_fields();
			$this->init_settings(); 
			
          // Turn these settings into variables we can use
			foreach ( $this->settings as $setting_key => $value ) {
				$this->$setting_key = $value;
			}
			
		  //Check if alternative callback url should be used.
			if($this->use_another_callback == 'yes'){
				$this->callback_url = $this->alternative_callbackurl ."wc-api/WC_Gateway_Swish_Ecommerce/";
			}		
			
			self::$log_enabled    		= $this->debug;

			define('SWISH_SSL_PATH', $this->sslcert_path);
			
			// Lets check for SSL & other stuff
			add_action( 'admin_notices', array( $this,	'do_ssl_check' ) );
			add_action( 'admin_notices', array( $this,	'do_curl_check' ) );
			add_action( 'admin_notices', array( $this,	'do_license_check' ) );

			// Actions
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			add_action( 'woocommerce_thankyou_redlight_swish-ecommerce', array( $this, 'thankyou_page' ) );
			
			include_once( 'includes/class-wc-gateway-swish-ecommerce-api-handler.php' );
			include_once('includes/class-wc-gateway-swish-ecommerce-api-functions.php');
			new WC_Gateway_Swish_Ecommerce_API_Handler();

			
			
		}

        /**
         * Build the administration fields for the gateway.
         *
         * @access public
         * @return void
         */

        public function init_form_fields() {
        	$this->form_fields = include( 'includes/settings-swish-ecommerce.php' );
        }

		// Check if we are forcing SSL on checkout pages
        public function do_ssl_check() {
        	if( $this->enabled == "yes" ) {
        		if( get_option( 'woocommerce_force_ssl_checkout' ) == "no" && is_ssl() == false ) {
        			echo "<div class=\"error\"><p>". sprintf( __( "<strong>%s</strong> is enabled and WooCommerce is not forcing the SSL certificate on your checkout page. Please ensure that you have a valid SSL certificate and that you are <a href=\"%s\">forcing the checkout pages to be secured.</a>" ), $this->method_title, admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ) ."</p></div>";	
        		}
        	}		
        }
		// Check if we are forcing SSL on checkout pages
        public function do_curl_check() {
        	if ( !function_exists( 'curl_version' ) ) {
        		echo "<div class=\"error\"><p>". __( 'cURL is not installed on server. This is needed for the plugin to function properly', 'woocommerce-gateway-swish-ecommerce' )."</p></div>";	
        	}		
        }
		// Check if we using the correct license
        public function do_license_check() {
        	if( get_option( 'redlight_swish_ecommerce_license_status' ) != "valid" ) {
        		echo "<div class=\"error\"><p>". sprintf( __( "License key for <strong>%s</strong> is invalid. Please ensure that you have <a href=\"%s\">entered a valid license key.</a>", 'woocommerce-gateway-swish-ecommerce' ), $this->method_title, admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_gateway_swish_ecommerce' ) ) ."</p></div>";	
        	}		
        }
		/**
         * Build the administration fields for the gateway.
         *
         * @access public
         * @return void
         */

		/**
		 * Logging method
		 * @param  string $message
		 */
		public static function log( $message ) {
			if ( self::$log_enabled ) {
				if ( empty( self::$log ) ) {
					self::$log = new WC_Logger();
				}
				self::$log->add( 'swish', $message );
			}
		}
        /**
         * Output the "order received"-page.
         *
         * @access public
         * @param int $order_id
         * @return void
         */

        public function thankyou_page( $order_id ) {
        	// $this->swish_details( $order_id );
        }


        /**
         * Get Swish details
         *
         * @access private
         * @param string $order_id (default: '')
         * @return void
         */

        private function swish_details( $order_id = '' ) {

        	if ( empty( $this->swish_number ) ) {
        		return;
        	}
        		
			//Load Swish CSS
			wp_enqueue_style('swish');
			?>
			<div class="redlight-swish-logo redlight-swish-centered">
				<img class="centered" src="<?php echo $this->swishimglogo;?>" />
				<img class="centered" src="<?php echo $this->swishtextlogo;?>" />
			</div>
			<div class="redlight-swish-messages redlight-swish-centered">
				<?php
				echo '<h2>' . __( 'To pay with Swish', 'woocommerce-gateway-swish-ecommerce' ) . '</h2>' . PHP_EOL;
				echo '<p>' . __( 'To complete your order you need to do the following .', 'woocommerce-gateway-swish-ecommerce' ) . '</p>' . PHP_EOL;
				echo wpautop( wptexturize( $this->message ) );
				if($this->show_desc == 'yes'){
					echo '<p>' . sprintf( __( "In your payment request you will see our name <strong>%s</strong>", 'woocommerce-gateway-swish-ecommerce' ), $this->swish_number_desc ) . '</p>' . PHP_EOL;
				}
				?>
			</div>
			<?php
        	
        }

        /**
         * Process the payment and return the result
         *
         * @param int $order_id
         * @return array
         */

        public function process_payment( $order_id) {

        	$order = wc_get_order( $order_id );
        	$this->log('Started to process order:' . $order->get_id() );
        	$this->log('Checking curl version:' . curl_version()['ssl_version'] );
			// preg_match("/\d{8,15}/", $order->billing_phone, $customerPhoneNumber);
			$ptn = "/^(\+46|0|0046)(?=\d{8,15}$)/";  // Replace leading zero
			$rpltxt = "46";  // Replacement string
			$customerPhoneNumber = preg_replace("/[^0-9]+/", "", $order->get_billing_phone());
			$customerPhoneNumber = preg_replace($ptn, $rpltxt, $customerPhoneNumber);
			$this->log('Setting order status to pending for order ' . $order->get_id() );
			$message = sprintf( __( "Payment for order %s", 'woocommerce-gateway-swish-ecommerce' ), $order->get_order_number() );
			//POST fields we'll be sending.
			$data = [
				'payeePaymentReference' => $order->get_id(),
				'callbackUrl'           => $this->callback_url,
				'payerAlias'            => $customerPhoneNumber,
				'payeeAlias'            => $this->swish_number,
				'amount'                => $order->get_total(),
				'currency'              => $order->get_currency(),
				'message'               => apply_filters('redlight_swish_ecommerce_paymentrequest_message',$message, $order)
			];
			$data = apply_filters('redlight_swish_ecommerce_paymentrequest_data',$data, $order);
			//Prepare payload for transer
			$this->log('Preparing order data, callback_url is ' . $this->callback_url );			
			$this->log('Our certificate: ' . $this->sslcert_path );
			$data_string = json_encode($data);
			$this->log('Sending POST to Swish-API, this is what we are sending: ' . $data_string );

			if(strpos(curl_version()['ssl_version'], 'NSS') !== false ){
				// Send this payload to Swish for processing using our NSS apiCall
				$this->log('We are using the NSS function.');
				$jsonResponse = apiCallNSS('POST', 'paymentrequests', $data_string);
			}
			if(strpos(curl_version()['ssl_version'], 'OpenSSL') !== false ){
				// Send this payload to Swish for processing using our normal apiCall
				$this->log('We are using the OpenSSL function.');
				$jsonResponse = apiCall('POST', 'paymentrequests', $data_string);
			}
			
			$jsonArray = json_decode($jsonResponse,true);
			$headers = get_headers_from_curl_response($jsonArray['headers']['plain_text']);
			if(isset($headers)){
        		// Handle the response.
				if(isset($headers['Location'])){
					preg_match("/\w{10,}\z/", $headers['Location'], $id);
				}
				preg_match("/\d{3}/", $headers['http_code'], $http_code);      
			}

			// Verify the code so we know if the transaction went through or not.
			// 201 means the transaction was a success
			if ($http_code[0] == 201) {

				// Add order notes.
				$this->log('Payment Request created. Swish-API returned http_code : ' . $headers['http_code'] );
				$this->log('Payment Request created. Swish-API returned payment id : ' . $id[0] );
				$this->log('Our callbackURL is : ' . $this->callback_url );
				// Add post meta
				add_post_meta( $order_id, '_swish_payment_request_id', $id[0] , true );
				$order->set_transaction_id($id[0]);

				// Mark order as pending
				$order->set_status( 'pending', __( 'Awaiting swish payment', 'woocommerce-gateway-swish-ecommerce' ) );
				$order->save();				

				return array(
					'result'    => 'success',
					'redirect'  => add_query_arg( 'swish_order_id', $order_id, get_permalink( get_option( 'woocommerce_checkout_page_id' ) ) )
					// 'redirect'  => add_query_arg( 'swish_order_id', $order_id, wc_get_checkout_url() )
				);

			} else {
				// Transaction was not succesful			
				$this->log('Payment Request was not created. Swish-API returned : ' . $jsonResponse );      
				// Add notice to the cart
				switch ($jsonArray['body'][0]['errorCode']) {
					case "FF08":
					wc_add_notice(__( 'PayeePaymentReference is invalid', 'woocommerce-gateway-swish-ecommerce' ), 'error' );
					break;
					case "RP03":
					wc_add_notice(__( 'Callback URL is missing or does not use Https', 'woocommerce-gateway-swish-ecommerce' ), 'error' );
					break;
					case "BE18":
					wc_add_notice(__( 'Payer alias is invalid', 'woocommerce-gateway-swish-ecommerce' ), 'error' );
					break;
					case "RP01":
					wc_add_notice(__( 'Payer alias is invalid', 'woocommerce-gateway-swish-ecommerce' ), 'error' );
					break;
					case "RP01":
					wc_add_notice(__( 'Payee alias is missing or empty', 'woocommerce-gateway-swish-ecommerce' ), 'error' );
					break;
					case "PA02":
					wc_add_notice(__( 'Amount value is missing or not a valid number', 'woocommerce-gateway-swish-ecommerce' ), 'error' );
					break;
					case "AM06":
					wc_add_notice(__( 'Amount value is too low', 'woocommerce-gateway-swish-ecommerce' ), 'error' );
					break;
					case "AM02":
					wc_add_notice(__( 'Amount value is too large', 'woocommerce-gateway-swish-ecommerce' ), 'error' );
					break;
					case "AM03":
					wc_add_notice(__( 'Invalid or missing Currency', 'woocommerce-gateway-swish-ecommerce' ), 'error' );
					break;
					case "RP02":
					wc_add_notice(__( 'Wrong formatted message', 'woocommerce-gateway-swish-ecommerce' ), 'error' );
					break;
					case "RP06":
					wc_add_notice(__( 'Another active PaymentRequest already exists for this payerAlias. Only applicable for E-Commerce.', 'woocommerce-gateway-swish-ecommerce' ), 'error' );
					break;
					case "ACMT03":
					wc_add_notice(__( 'Payer not Enrolled', 'woocommerce-gateway-swish-ecommerce' ), 'error' );
					break;
					case "ACMT01":
					wc_add_notice(__( 'Counterpart is not activated', 'woocommerce-gateway-swish-ecommerce' ), 'error' );
					break;
					case "ACMT07":
					wc_add_notice(__( 'Payee not Enrolled', 'woocommerce-gateway-swish-ecommerce' ), 'error' );
					break;
					case "RF07":
					wc_add_notice(__( 'Transaction declined', 'woocommerce-gateway-swish-ecommerce' ), 'error' );
					break;
					case "BANKIDCL":
					wc_add_notice(__( 'Payer cancelled BankId signing', 'woocommerce-gateway-swish-ecommerce' ), 'error' );
					break;
					case "BANKIDONGOING":
					wc_add_notice(__( 'BankID already in use', 'woocommerce-gateway-swish-ecommerce' ), 'error' );
					break;
					case "FF10":
					wc_add_notice(__( 'Bank system processing error', 'woocommerce-gateway-swish-ecommerce' ), 'error' );
					break;
					case "TM01":
					wc_add_notice(__( 'Swish timed out before the payment was started', 'woocommerce-gateway-swish-ecommerce' ), 'error' );
					break;
					case "DS24":
					wc_add_notice(__( 'Swish timed out waiting for an answer from the banks after payment was started. Note:If this happens Swish has no knowledge of whether the payment was successful or not. The Merchant should inform its consumer about this and recommend them to check with their bank about the status of this payment.', 'woocommerce-gateway-swish-ecommerce' ), 'error' );
					break;
					default:
					wc_add_notice(__( 'An error has accured', 'woocommerce-gateway-swish-ecommerce' ), 'error' );
				}
				// Add note to the order for your reference
				//$order->add_order_note( 'Error: '. $jsonResponse );
			} 


		}

		/**
        * Process the payment and return the result
        *
        * @param int $order_id
        * @return array
        */

		public function get_process_payment( $order_id ) {
			$order = wc_get_order( $order_id );

			if ($order->get_status() == 'processing' || $order->get_status() == 'completed') {

				// Return thankyou redirect
				$jsonArray = array(
					'result'    => 'success',
					'redirect'  => $this->get_return_url( $order ),
					'order_id'	=> $order_id,
				);
				echo json_encode($jsonArray);

			}elseif($order->get_status() == 'failed' || $order->get_status() == 'canceled'){
				// Return thankyou redirect
				$jsonArray = array(
					'result'    => 'error',
					'redirect'  => wc_get_checkout_url(),
					'order_id'	=> $order->get_id(),
				);
				echo json_encode($jsonArray);

			}else {
				 
				$jsonArray = array(
					'result'    => $order->get_status(),
					'payment_request_id'  => $order->get_meta_data('_swish_payment_request_id')[0]->value,
					'order_id'	=> $order->get_id(),
				);
				echo json_encode($jsonArray);
			} 

			die();

		}


		/**
		 * Can the order be refunded via Swish?
		 * @param  WC_Order $order
		 * @return bool
		 */
		public function can_refund_swish_order( $order ) {
			return $order && get_post_meta( $order->get_id(), '_swish_payment_reference', true );
		}	
	
		/**
		 * Process a refund if supported
		 * @param  int $order_id
		 * @param  float $amount
		 * @param  string $reason
		 * @return  boolean True or false based on success, or a WP_Error object
		 */
		public function process_refund( $order_id, $amount = null, $reason = '' ) {
			$order = wc_get_order( $order_id );
			$swishPaymentReference = get_post_meta( $order->get_id(), '_swish_payment_reference', true );
			if ( ! $this->can_refund_swish_order( $order ) ) {
				$this->log( 'Refund Failed: Missing Swish Payment Reference ' );
				return false;
			}
			$ptn = "/^(\+46|0|0046)(?=\d{8,15}$)/";  // Replace leading zero
			$rpltxt = "46";  // Replacement string
			$customerPhoneNumber = preg_replace("/[^0-9]+/", "", $order->get_billing_phone());
			$customerPhoneNumber = preg_replace($ptn, $rpltxt, $customerPhoneNumber);
			$message = sprintf( __( "Refund for order %s", 'woocommerce-gateway-swish-ecommerce' ), $order->get_order_number() );
			//POST fields we'll be sending.
			$data =
				[
					'payeePaymentReference' 	=> $order->get_id(),
					'originalPaymentReference' 	=> $swishPaymentReference,
					'callbackUrl'           	=> $this->callback_url,
					'payerAlias'            	=> $this->swish_number,
					'payeeAlias'            	=> $customerPhoneNumber,
					'amount'                	=> $amount,
					'currency'              	=> $order->get_currency(),
					'message'              		=> apply_filters('redlight_swish_ecommerce_refund_message',$message, $order)
				];
			$data = apply_filters('redlight_swish_ecommerce_refund_data',$data, $order);
			//Prepare payload for transer
			$this->log('Preparing refund data, callback_url is ' . $this->callback_url );
			$data_string = json_encode($data);
			$this->log('Sending POST to Swish-API(refunds), this is what we are sending: ' . $data_string );

			// Send this payload to Swish for processing
			$jsonResponse = apiCall('POST', 'refunds', $data_string);
			$jsonArray = json_decode($jsonResponse,true);
			$headers = get_headers_from_curl_response($jsonArray['headers']['plain_text']);
			
			// Handle the response.
			if(isset($headers)){
				preg_match("/\w{10,}\z/", $headers['Location'], $id);
				preg_match("/\d{3}/", $headers['http_code'], $http_code);
			}

			// Verify the code so we know if the transaction went through or not.
			// 1 or 4 means the transaction was a success
			if ($http_code[0] == 201) {
				// Add order notes.
				$this->log('Swish Refund created. Swish-API returned http_code : ' . $headers['http_code'] );
				$this->log('Swish Refund created. Swish-API returned payment id : ' . $id[0] );
				$this->log('Our callbackURL is : ' . $this->callback_url );

				// Add post meta
				add_post_meta( $order_id, '_swish_refunds_id', $id[0] , true );

				// Do we need to change the stock?
				//$order->reduce_order_stock();
	 
				// Add order refund note
				$order->add_order_note( sprintf( __( 'Refunded %s - Refund ID: %s', 'woocommerce' ), $amount, $id[0] ) );
				return true;
			} else {
				// Transaction was not succesful			
				$this->log('Swish Refund was not created. Swish-API returned : ' . $headers['http_code'] );
				$this->log('Swish Refund was not created. Swish-API returned : ' . $jsonResponse );
				// Add notice to the cart
				switch ($jsonArray['body'][0]['errorCode']) {
				  case "FF08":
					  wc_add_notice(__( 'PayeePaymentReference is invalid', 'woocommerce-gateway-swish-ecommerce' ), 'error' );
					  break;
				  case "RP03":
					  wc_add_notice(__( 'Callback URL is missing or does not use Https', 'woocommerce-gateway-swish-ecommerce' ), 'error' );
					  break;
				  case "BE18":
					  wc_add_notice(__( 'Payer alias is invalid', 'woocommerce-gateway-swish-ecommerce' ), 'error' );
					  break;
				  case "RP01":
					  wc_add_notice(__( 'Payer alias is invalid', 'woocommerce-gateway-swish-ecommerce' ), 'error' );
					  break;
				  case "RP01":
					  wc_add_notice(__( 'Payee alias is missing or empty', 'woocommerce-gateway-swish-ecommerce' ), 'error' );
					  break;
				  case "PA02":
					  wc_add_notice(__( 'Amount value is missing or not a valid number', 'woocommerce-gateway-swish-ecommerce' ), 'error' );
					  break;
				  case "AM06":
					  wc_add_notice(__( 'Amount value is too low', 'woocommerce-gateway-swish-ecommerce' ), 'error' );
					  break;
				  case "AM02":
					  wc_add_notice(__( 'Amount value is too large', 'woocommerce-gateway-swish-ecommerce' ), 'error' );
					  break;
				  case "AM03":
					  wc_add_notice(__( 'Invalid or missing Currency', 'woocommerce-gateway-swish-ecommerce' ), 'error' );
					  break;
				  case "RP02":
					  wc_add_notice(__( 'Wrong formatted message', 'woocommerce-gateway-swish-ecommerce' ), 'error' );
					  break;
				  case "RP06":
					  wc_add_notice(__( 'Another active PaymentRequest already exists for this payerAlias. Only applicable for E-Commerce.', 'woocommerce-gateway-swish-ecommerce' ), 'error' );
					  break;
				  case "ACMT03":
					  wc_add_notice(__( 'Payer not Enrolled', 'woocommerce-gateway-swish-ecommerce' ), 'error' );
					  break;
				  case "ACMT01":
					  wc_add_notice(__( 'Counterpart is not activated', 'woocommerce-gateway-swish-ecommerce' ), 'error' );
					  break;
				  case "ACMT07":
					  wc_add_notice(__( 'Payee not Enrolled', 'woocommerce-gateway-swish-ecommerce' ), 'error' );
					  break;
				  case "RF07":
					  wc_add_notice(__( 'Transaction declined', 'woocommerce-gateway-swish-ecommerce' ), 'error' );
					  break;
				  case "BANKIDCL":
					  wc_add_notice(__( 'Payer cancelled BankId signing', 'woocommerce-gateway-swish-ecommerce' ), 'error' );
					  break;
				  case "BANKIDONGOING":
					  wc_add_notice(__( 'BankID already in use', 'woocommerce-gateway-swish-ecommerce' ), 'error' );
					  break;
				  case "FF10":
					  wc_add_notice(__( 'Bank system processing error', 'woocommerce-gateway-swish-ecommerce' ), 'error' );
					  break;
				  case "TM01":
					  wc_add_notice(__( 'Swish timed out before the payment was started', 'woocommerce-gateway-swish-ecommerce' ), 'error' );
					  break;
				  case "DS24":
					  wc_add_notice(__( 'Swish timed out waiting for an answer from the banks after payment was started. Note:If this happens Swish has no knowledge of whether the payment was successful or not. The Merchant should inform its consumer about this and recommend them to check with their bank about the status of this payment.', 'woocommerce-gateway-swish-ecommerce' ), 'error' );
					  break;
				  default:
					  wc_add_notice(__( 'An error has accured', 'woocommerce-gateway-swish-ecommerce' ), 'error' );
			  }
				// Add note to the order for your reference
				$order->add_order_note( 'Error: '. $headers['http_code'] );
				return false;
			}

			
		}

	}
	if( !function_exists('is_plugin_active') ) {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}
	if( is_plugin_active( 'woocommerce-gateway-klarna/woocommerce-gateway-klarna.php' ) ) {
		/**
		 * Add some extra settings
		 *
		 */
		add_filter( 'redlight_swish_ecommerce_form_fields', 'rswemp_klarna_checkout_form_fields' );
		function rswemp_klarna_checkout_form_fields( $settings ) {
			$icon = plugins_url( '/assets/images/swish_logo.png', __FILE__ );
			$settings['kco_epm_swish_settings_title'] = array(
				'title' => __( 'Klarna Checkout - External Payment Method - Swish', 'woocommerce-gateway-swish-ecommerce' ),
				'type'  => 'title',
			);
			$settings['kco_epm_swish_active'] = array(
				'label'     => __( 'Show Swish in Klarna Checkout', 'woocommerce-gateway-swish-ecommerce' ),
				'type'      => 'checkbox',
				'default'   => 'no',
				'description'   => __( 'This will display as an external payment method in Klarna Checkout', 'woocommerce-gateway-swish-ecommerce' ),
			);
			$settings['kco_epm_swish_name'] = array(
				'title'       => __( 'Name', 'woocommerce-gateway-swish-ecommerce' ),
				'type'        => 'text',
				'description' => __( 'Title for Swish payment method. This controls the title which the user sees in the checkout form.', 'woocommerce-gateway-swish-ecommerce' ),
				'default'     => __( 'Swish', 'woocommerce-gateway-klarna' )
			);
			$settings['kco_epm_swish_img_url'] = array(
				'title'       => __( 'Image url', 'woocommerce-gateway-swish-ecommerce' ),
				'type'        => 'text',
				'description' => __( 'The url to the Swish payment Icon.', 'woocommerce-gateway-swish-ecommerce' ),
				'default'     => $icon
			);
			
			return $settings;
		}
		/**
		 * Add Swish as Payment Method to the KCO iframe.
		 *
		 */
		add_filter('kco_create_order', 'redlight_kco_create_order_swish');
		function redlight_kco_create_order_swish( $create ) {
			$swish_ecommerce_settings = get_option( 'woocommerce_redlight_swish-ecommerce_settings' );
			$active   		= $swish_ecommerce_settings['kco_epm_swish_active'];
			$name   		= ( isset( $swish_ecommerce_settings['kco_epm_swish_name'] ) ) ? $swish_ecommerce_settings['kco_epm_swish_name'] : '';
			$image_url   	= ( isset( $swish_ecommerce_settings['kco_epm_swish_img_url'] ) ) ? $swish_ecommerce_settings['kco_epm_swish_img_url'] : '';
			$description   	= ( isset( $swish_ecommerce_settings['description'] ) ) ? $swish_ecommerce_settings['description'] : '';
			if($active == 'yes'){
				$klarna_external_payment = array(
					'name' 			=> $name,
					'redirect_uri' 	=> esc_url( add_query_arg( 'kco-external-payment', 'swish', get_site_url() ) ),
					'image_uri' 	=> $image_url,
					'description' 	=> $description,
				);
				$klarna_external_payment = array( $klarna_external_payment );
					
				$create['external_payment_methods'] = $klarna_external_payment;
			}
			
			
			return $create;
		}
		/**
		 * Redirect to Swish when the Proceed to Swish button in KCO iframe is clicked.
		 *
		 */
		add_action('init', 'kco_redirect_to_swish' );
		function kco_redirect_to_swish() {
			if( isset ( $_GET['kco-external-payment'] ) && 'swish' == $_GET['kco-external-payment'] ) {
				$swish_ecommerce_gateway = new WC_Gateway_Swish_Ecommerce();
				$order          = wc_get_order( WC()->session->get( 'ongoing_klarna_order' ) );
				if( $order ) {
					// Det här känns inte rätt men är en fullösning för att hämta KCO data.
					$klarna_country         	= $order->get_billing_country();
					$klarna_checkout_settings 	= get_option( 'woocommerce_klarna_checkout_settings' );
					$klarna_country_lowercase 	= strtolower( $klarna_country );
					$klarna_secret           	= $klarna_checkout_settings[ 'secret_' . $klarna_country_lowercase ];
					$connector      			= Klarna_Checkout_Connector::create( $klarna_secret, Klarna\Rest\Transport\ConnectorInterface::EU_BASE_URL );
					$klarna_order   			= new Klarna_Checkout_Order( $connector, WC()->session->get( 'klarna_checkout' ) );

					$klarna_order->fetch();

					// Sätt Betalsättet till Swish
					$available_gateways = WC()->payment_gateways->payment_gateways();
					$payment_method     = $available_gateways['redlight_swish-ecommerce'];
					$order->set_payment_method( $payment_method );
					// Justera orderstatus från kco_incomplete till pending
					$order->update_status( 'pending' );
					
					// Spara kunduppgifter
					$order->set_billing_first_name($klarna_order['billing_address']['given_name']);
					$order->set_billing_last_name($klarna_order['billing_address']['family_name']);
					$order->set_billing_company($klarna_order['billing_address']['organization_name']);
					$order->set_billing_address_1($klarna_order['billing_address']['street_address']);
					$order->set_billing_address_2($klarna_order['billing_address']['care_of']);
					$order->set_billing_city($klarna_order['billing_address']['city']);
					$order->set_billing_postcode($klarna_order['billing_address']['postal_code']);
					$order->set_billing_country(strtoupper($klarna_order['billing_address']['country']));
					$order->set_billing_email($klarna_order['billing_address']['email']);
					$order->set_billing_phone($klarna_order['billing_address']['phone']);

					$order->set_shipping_first_name($klarna_order['shipping_address']['given_name']);
					$order->set_shipping_last_name($klarna_order['shipping_address']['family_name']);
					$order->set_shipping_company($klarna_order['shipping_address']['organization_name']);
					$order->set_shipping_address_1($klarna_order['shipping_address']['street_address']);
					$order->set_shipping_address_2($klarna_order['shipping_address']['care_of']);
					$order->set_shipping_city($klarna_order['shipping_address']['city']);
					$order->set_shipping_postcode($klarna_order['shipping_address']['postal_code']);
					$order->set_shipping_country(strtoupper($klarna_order['shipping_address']['country']));


					$order->save();
					$order->calculate_totals();

					$swish_paymentrequest = $swish_ecommerce_gateway->process_payment($order->id);

					if($swish_paymentrequest['result']== 'success'){
						// Clear KCO sessions
						WC()->session->__unset( 'klarna_checkout' );
						WC()->session->__unset( 'klarna_checkout_country' );
						WC()->session->__unset( 'ongoing_klarna_order' );
						WC()->session->__unset( 'klarna_order_note' );
						wp_redirect( $swish_paymentrequest['redirect']);
						exit;
					}else{
						// Magic
					}
					
				}
				
			}
		}
		
	}
	
}

/**
 * Add the gateway to WooCommerce.
 *
 * @access public
 * @param array $methods
 * @return array
 */

function add_wc_gateway_swish_ecommerce( $methods ) {

	$methods[] = 'WC_Gateway_Swish_Ecommerce';
	return $methods;

}

/*require WC_Gateway_Swish_Ecommerce_Popup*/
include_once('includes/class-wc-gateway-swish-ecommerce-popup.php');

$wc_gateway_swish_ecommerce_popup = new WC_Gateway_Swish_Ecommerce_Popup();

