<?php

if ( ! defined( 'ABSPATH' ) ) { 
    exit; // Exit if accessed directly
}

class WC_Gateway_Swish_Ecommerce_REST_API {
    
    protected static $instance;
    private $settings = array();
    
    public static function get_instance() {
        if( is_null( self::$instance ) ) {
            self::$instance == new self();
        }
        return self::$instance;
    }
    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ));
        $this->settings = get_option( 'woocommerce_redlight_swish-ecommerce_settings' );
    }
    
    public function register_routes() {
        
        $namespace = "swish";

        register_rest_route($namespace, '/status/(?P<paymentreference>[[a-zA-Z0-9_.-]+)', array(
            array(
                'methods'         => WP_REST_Server::READABLE,
                'callback'        => array( $this, 'get_payment_status' ),
                'permission_callback' => array( $this, 'get_permission' ),
                'args'            => array(
                    'paymentreference' => array(
                        'required'      => true,
                    ),
                ),
            )
        ));
    }
    public function get_permission() {
        return true;
    }

    static function get_payment_status( WP_REST_Request $request ){
        
        $parameters = $request->get_params();
        if( !isset( $parameters['paymentreference'] ) || empty($parameters['paymentreference']) ){
            $data = array( 
                'error' => 'no_paymentreference_given',
                'message' => 'Paymentreference is required'
            );
            return new WP_REST_Response($data,400);
        }
        $paymentreference = $parameters['paymentreference'];
        if( strlen( $paymentreference ) < 25 ){
            $data = array( 
                'error' => 'paymentreference_incorrect_length',
                'message' => 'paymentreference should be atleast 25 characters'
            );
            return new WP_REST_Response($data,400);
        }
        $result = self::apiCall('GET', 'paymentrequests/'.$paymentreference);
        if(true){
            return new WP_REST_Response($result,200);
        }else{
            $data = array( 
                'error' => 'delivery_not_possible',
                'message' => 'Best could not deliver to this zipcode'
            );
            return new WP_REST_Response($data,200);
        }
    }

    public function apiCall ($requestMethod, $entity, $body = null) { 
        if($this->testmode == 'yes'){
            $url = 'https://mss.cpc.getswish.net/swish-cpcapi/api/v1/';
        }else{
            $url = 'https://cpc.getswish.net/swish-cpcapi/api/v1/';
            $url = 'https://mss.cpc.getswish.net/swish-cpcapi/api/v1/';
        }
        $curl = curl_init($url . $entity);
        $options = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_CUSTOMREQUEST => $requestMethod,
            CURLOPT_SSLCERT => $this->settings['sslcert_path'],
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ]
        );

        if ($requestMethod == 'POST' || $requestMethod == 'PUT') {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        } 
        
        if($this->settings['testmode'] == 'no'){
            curl_setopt($curl, CURLOPT_SSLCERT, plugin_dir_path(REDLIGHT_SA_MAIN_FILE ) .'test-certificate/Swish-Test-1231181189.pem');
            curl_setopt($curl, CURLOPT_SSLKEY, plugin_dir_path(REDLIGHT_SA_MAIN_FILE ) .'test-certificate/Swish-Test-1231181189.key');
        }
        curl_setopt_array($curl, $options);

        $curlResponse = curl_exec($curl);

        if(curl_error($curl)){
        }

        $responseCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $header_len = curl_getinfo($curl, CURLINFO_HEADER_SIZE);

        return curl_error($curl);
    }
}
