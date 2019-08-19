<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings for Swish Ecommerce Gateway
 */
class WC_Gateway_Swish_Ecommerce_Settings{
	/**
	 * Returns the fields.
	 */
	public static function fields() {
		$settings = array(
			'api_title' => array(
				'title' => __( 'License information', 'woocommerce-gateway-swish-ecommerce' ), 
				'type' => 'title', 
			), 	
			'redlight_license_key' => array(
				'title'     => __( 'License key', 'woocommerce-gateway-swish-ecommerce' ),
				'type'      => 'text',
				'desc_tip'  => __( 'Enter the license key you recived when purschasing this plugin.', 'woocommerce-gateway-swish-ecommerce' ),
				),
			'redlight_license_deactivate' => array(
				'label'     => __( 'Deactivate this license', 'woocommerce-gateway-swish-ecommerce' ),
				'type'      => 'checkbox',
				'default'   => 'no',
				'description'   => __( 'Deactivating this license key will disable updates', 'redlight-swish' ),
				),		
			'settings_title' => array(
				'title' => __( 'Settings', 'woocommerce-gateway-swish-ecommerce' ), 
				'type' => 'title', 
			), 	
			'enabled' => array(
				'title'     => __( 'Enable / Disable', 'woocommerce-gateway-swish-ecommerce' ),
				'label'     => __( 'Enable this payment gateway', 'woocommerce-gateway-swish-ecommerce' ),
				'type'      => 'checkbox',
				'default'   => 'no',
				),
			'title' => array(
				'title'     => __( 'Title', 'woocommerce-gateway-swish-ecommerce' ),
				'type'      => 'text',
				'desc_tip'  => __( 'Enter the name you want displayed for the user.', 'woocommerce-gateway-swish-ecommerce' ),
				'default'   => __( 'Swish', 'woocommerce-gateway-swish-ecommerce' ),
				),
			'description' => array(
				'title'     => __( 'Description', 'woocommerce-gateway-swish-ecommerce' ),
				'type'      => 'textarea',
				'desc_tip'  => __( 'Enter the description you want shown to the user at checkout.', 'woocommerce-gateway-swish-ecommerce' ),
				'default'   => __( 'When you choose to pay with Swish, open your Swish-app and make sure the prefilled data is correct. You approve the purchase with Mobile BankID and recive a confirmation of the purchase', 'woocommerce-gateway-swish-ecommerce' ),
				),
			'message' => array(
				'title'       => __( 'Message', 'woocommerce-gateway-swish-ecommerce' ),
				'type'        => 'textarea',
				'description' => __( 'Message that will appear on the "Thank you for your order " and the e-mail message.', 'woocommerce-gateway-swish-ecommerce' ),
				'default'     => __( 'To complete your order you need to do the following.<br/> 1.Open your Swish-app. <br/> 2.Make sure the prefilled data is correct.<br/>3.Sign with Mobile BankID', 'woocommerce-gateway-swish-ecommerce' ),
				'desc_tip'    => true,
				),
			'swish_number' => array(
				'title'     => __( 'Your Swish number', 'woocommerce-gateway-swish-ecommerce' ),
				'type'      => 'text',
				'description' => __( 'Enter the number you received when you joined Swish .', 'woocommerce-gateway-swish-ecommerce' ),
				),
			'sslcert_path' => array(
				'title'       => __( 'Certificate', 'woocommerce-gateway-swish-ecommerce' ),
				'type'        => 'text',
				'default'     => "",
				'description' => sprintf( __( 'cURL is using <code>%s</code><p>Server root is:<code>%s</code></p>', 'woocommerce-gateway-swish-ecommerce' ), curl_version()['ssl_version'],$_SERVER["DOCUMENT_ROOT"]  )
				),
			'customer_phone_form' => array(
				'title'     => __( 'Use form for customers mobilephone instead of WooCommerce standard field', 'woocommerce-gateway-swish-ecommerce' ),
				'label'     => __( 'Use form for customers mobilephone instead of WooCommerce standard field', 'woocommerce-gateway-swish-ecommerce' ),
				'type'      => 'checkbox',
				'default'   => 'no',
				'description' => __( 'If this is checked a form will be shown to the user where the customer can input the phone number.', 'woocommerce-gateway-swish-ecommerce' ),
				),
			'show_desc' => array(
				'title'     => __( 'Show / Hide description', 'woocommerce-gateway-swish-ecommerce' ),
				'label'     => __( 'Show description', 'woocommerce-gateway-swish-ecommerce' ),
				'type'      => 'checkbox',
				'default'   => 'no',
				),
			'swish_number_desc' => array(
				'title'       => __( 'Description of your Swish account', 'woocommerce-gateway-swish-ecommerce' ),
				'type'        => 'textarea',
				'description' => __( 'Example: Company Inc', 'woocommerce-gateway-swish-ecommerce' ),
				'default'     => '',
				'desc_tip'    => true,
				),
			'testmode' => array(
				'title'       => __( 'Testmode', 'woocommerce-gateway-swish-ecommerce' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable Swish Testmode', 'woocommerce-gateway-swish-ecommerce' ),
				'default'     => 'no',
				'description' => __( 'If Testmode is enabled, no payments will be completed, only simulated.', 'woocommerce-gateway-swish-ecommerce' ),
			),
			'debug' => array(
				'title'       => __( 'Debug Log', 'woocommerce-gateway-swish-ecommerce' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable logging', 'woocommerce-gateway-swish-ecommerce' ),
				'default'     => 'no',
				'description' => sprintf( __( 'Log Swish events, such as API requests, inside <code>%s</code>', 'woocommerce-gateway-swish-ecommerce' ), wc_get_log_file_path( 'swish-ecommerce' ) )
			)
		);
		return  apply_filters( 'redlight_swish_ecommerce_settings', $settings);
	}
}
