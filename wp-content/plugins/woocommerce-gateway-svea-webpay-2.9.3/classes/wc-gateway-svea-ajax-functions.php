<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use Svea\WebPay\WebPay;

/**
 * Class to handle ajax requests for the Svea plugin
 */
class WC_SveaWebPay_Gateway_Ajax_Functions {
	
	/** 
	 * Nonce name, used for securing ajax-requests
	 *
	 * @var 	string
	 */
	const GET_ADDRESS_NONCE_NAME = "sveawebpay";

	public function __construct() {
		add_action( 'wp_ajax_svea_get_address', array( $this, 'get_address' ) );
		add_action( 'wp_ajax_nopriv_svea_get_address', array( $this, 'get_address' ) );
	}

	/**
	 * Fetch address credentials from Svea and present as json
	 *
	 * @return 	void
	 */
	public function get_address() {
		WC_Gateway_Svea_Invoice::log( "Getting address" );

		if( ! check_ajax_referer( self::GET_ADDRESS_NONCE_NAME, 'security' ) ) {
			WC_Gateway_Svea_Invoice::log( "Invalid nonce" );
			die();
		}

		if( ! isset( $_POST["pers_nr"] ) && ! isset( $_POST["org_nr"] ) ) {
			WC_Gateway_Svea_Invoice::log( "No organisation or personal number set" );
			die();
		}

		if( ! isset( $_POST["country_code"] ) ) {
			WC_Gateway_Svea_Invoice::log( "No country code set" );
			die();
		}

		if( ! isset( $_POST["payment_type"] ) ) {
			WC_Gateway_Svea_Invoice::log( "No payment type set" );
			die();
		}

		$customer_country = strtoupper( $_POST["country_code"] );

		$payment_method = $_POST["payment_type"];

		if( $payment_method == WC_Gateway_Svea_Invoice::init()->id ) {
			$wc_gateway = WC_Gateway_Svea_Invoice::init();
		} else if( $payment_method == WC_Gateway_Svea_Part_Pay::init()->id ) {
			$wc_gateway = WC_Gateway_Svea_Part_Pay::init();
		} else {
			die();
		}

		$request = WebPay::getAddresses( $wc_gateway->get_config( $customer_country ) );

		$request->setCountryCode( $customer_country );

		if( $wc_gateway instanceof WC_Gateway_Svea_Invoice )
			$request->setOrderTypeInvoice();
		else if( $wc_gateway instanceof WC_Gateway_Svea_Part_Pay )
			$request->setOrderTypePaymentPlan();

		$identifier = '';

		if( isset($_POST["pers_nr"]) ) {
			$request->setCustomerIdentifier( strval( $_POST["pers_nr"] ) )
					->getIndividualAddresses();
		} else if( isset($_POST["org_nr"]) ) {
			$request->setCustomerIdentifier( strval( $_POST["org_nr"] ) )
					->getCompanyAddresses();
		}

		try {
			$response = $request->doRequest();
		} catch (Exception $e) {
			WC_Gateway_Svea_Invoice::log( "Get-address request failed, error: " . $e->getMessage() );
		}

		foreach( $response->customerIdentity as &$ci ) {
			if( strlen( $ci->firstName ) <= 0 && strlen( $ci->lastName ) <= 0 ) {
				$temp = str_replace( ",", "", $ci->fullName );
				$explode = explode( " ", $temp );

				if( count( $explode ) != 2 )
					continue;

				$ci->firstName = $explode[1];
				$ci->lastName = $explode[0];
			}
		}

		WC_Gateway_Svea_Invoice::log( "Get-address request successful, sending response" );

		wp_send_json( $response );

		die();
	}
}

new WC_SveaWebPay_Gateway_Ajax_Functions();