<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class WC_Gateway_Swish_Ecommerce extends WC_Payment_Gateway {
		
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
    public function __construct() {

        $this->id					= "redlight_swish-ecommerce";
        $this->has_fields 			= true;
        $this->method_title 		= "Swish Handel";
        $this->method_description 	= __( "Extends WooCommerce. Provides a <a href='http://www.getswish.se/handel' target='_blank'>Swish Handel</a> gateway for WooCommerce.", 'woocommerce-gateway-swish-ecommerce' );
        $this->title 				= __( "Swish Handel", 'woocommerce-gateway-swish-ecommerce' );
        $this->icon 				= plugins_url( 'assets/images/swish_logo.png', REDLIGHT_SA_MAIN_FILE );
        $this->test_endpoint		= 'https://mss.cpc.getswish.net/swish-cpcapi/api/v1/';
        $this->live_endpoint		= 'https://cpc.getswish.net/swish-cpcapi/api/v1/';
        $this->callback_url 		= WC()->api_request_url( 'WC_Gateway_Swish_Ecommerce', true);
        $this->supports          	= array(
            'default_credit_card_form',
            'products',
            'refunds',
            );
        
        // Load Settings
        $this->init_form_fields();
        $this->init_settings(); 
        
        // Turn these settings into variables we can use
        foreach ( $this->settings as $setting_key => $value ) {
            $this->$setting_key = $value;
        }
        if( $this->testmode == 'yes' ){
            $this->sslcert_path = REDLIGHT_SA_PLUGIN_PATH .'/test-certificate/Swish-Test-1231181189.pem';
            $this->swish_number = '1231181189';
            $this->description .=' ' . sprintf( __( 'TEST MODE ENABLED. In test mode, you can use the phonenumber 07211234567.', 'woocommerce-gateway-swish-ecommerce' ), 'https://stripe.com/docs/testing' );
        }
        
        // Lets check for SSL & other stuff
        add_action( 'admin_notices', array( $this,	'do_ssl_check' ) );
        add_action( 'admin_notices', array( $this,	'do_curl_check' ) );
        add_action( 'admin_notices', array( $this,	'do_license_check' ) );

        // Actions
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array(
            $this,
            'process_admin_options' 
        ) );
    }

    /**
     * Build the administration fields for the gateway.
     *
     * @access public
     * @return void
     */

    public function init_form_fields() {
        $this->form_fields = WC_Gateway_Swish_Ecommerce_Settings::fields();
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
    public function payment_fields() {
        if ( $description = $this->get_description() ) {
            echo wpautop( wptexturize( $description ) );
        }
        if ( $this->customer_phone_form == 'yes' ) {
            $this->form();
        }
    }

    public function apiCall ($requestMethod, $entity, $body = null) { 
        if($this->testmode == 'yes'){
            $url = $this->test_endpoint;
        }else{
            $url = $this->live_endpoint;
        }
        $curl = curl_init($url . $entity);
        $options = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_CUSTOMREQUEST => $requestMethod,
            CURLOPT_SSLCERT => $this->sslcert_path,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ]
        );

        if ($requestMethod == 'POST' || $requestMethod == 'PUT') {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        } 
        
        if($this->testmode == 'yes'){
            curl_setopt($curl, CURLOPT_SSLKEY, plugin_dir_path(REDLIGHT_SA_MAIN_FILE ) .'test-certificate/Swish-Test-1231181189.key');
        }
        curl_setopt_array($curl, $options);

        $curlResponse = curl_exec($curl);

        if(curl_error($curl)){
            self::log("curl returned error: (".curl_errno($curl).")". curl_error($curl));
        }

        $responseCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $header_len = curl_getinfo($curl, CURLINFO_HEADER_SIZE);

        $header = substr($curlResponse, 0, $header_len);
        $headers = array(
            "plain_text"=>$header,
            "http_code"=>$responseCode
        );
        $body = substr($curlResponse, $header_len);
        $result = array(
            "headers"=>$headers,
            "body"=>json_decode($body,true)
        );

        return json_encode($result);
    }
    public function get_headers_from_curl_response($response){
        $headers = array();
        $header_text = substr($response, 0, strpos($response, "\r\n\r\n"));
        foreach (explode("\r\n", $header_text) as $i => $line)
            if ($i === 0)
                $headers['http_code'] = $line;
            else
            {
                list ($key, $value) = explode(': ', $line);
                $headers[$key] = $value;
            }
        return $headers;
    }
        
    public function form(){
        $fields = array();

        $default_fields = array(
            'payer-alias-field' => '<p class="form-row form-row-wide">
                <label for="' . esc_attr( $this->id ) . '-payer-alias">' . esc_html__( 'Enter mobilephone', 'woocommerce-gateway-swish-ecommerce' ) . '&nbsp;<span class="required">*</span></label>
                <input id="' . esc_attr( $this->id ) . '-payer-alias" class="input-number wc-swish-form-payer-alias" inputmode="numeric" autocomplete="tel" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" placeholder="0731234567" name="' . esc_attr( $this->id . '-payer-alias'  ) . '"  />
            </p>'
        );
        $fields = wp_parse_args( $fields, apply_filters( 'redlight_swish_ecommerce_form_fields', $default_fields, $this->id ) );
        ?>

        <fieldset id="wc-<?php echo esc_attr( $this->id ); ?>-swish-form" class='wc-swish-form wc-payment-form'>
            <?php do_action( 'redlight_swish_ecommerce_form_start', $this->id ); ?>
            <?php
            foreach ( $fields as $field ) {
                echo $field; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
            }
            ?>
            <?php do_action( 'redlight_swish_ecommerce_form_end', $this->id ); ?>
            <div class="clear"></div>
        </fieldset>
        <?php
    }

    /**
     * Process the payment and return the result
     *
     * @param int $order_id
     * @return array
     */

    public function process_payment( $order_id) {

        $country_codes = array(
            'AC' => '247',
            'AD' => '376',
            'AE' => '971',
            'AF' => '93',
            'AG' => '1268',
            'AI' => '1264',
            'AL' => '355',
            'AM' => '374',
            'AO' => '244',
            'AQ' => '672',
            'AR' => '54',
            'AS' => '1684',
            'AT' => '43',
            'AU' => '61',
            'AW' => '297',
            'AX' => '358',
            'AZ' => '994',
            'BA' => '387',
            'BB' => '1246',
            'BD' => '880',
            'BE' => '32',
            'BF' => '226',
            'BG' => '359',
            'BH' => '973',
            'BI' => '257',
            'BJ' => '229',
            'BL' => '590',
            'BM' => '1441',
            'BN' => '673',
            'BO' => '591',
            'BQ' => '599',
            'BR' => '55',
            'BS' => '1242',
            'BT' => '975',
            'BW' => '267',
            'BY' => '375',
            'BZ' => '501',
            'CA' => '1',
            'CC' => '61',
            'CD' => '243',
            'CF' => '236',
            'CG' => '242',
            'CH' => '41',
            'CI' => '225',
            'CK' => '682',
            'CL' => '56',
            'CM' => '237',
            'CN' => '86',
            'CO' => '57',
            'CR' => '506',
            'CU' => '53',
            'CV' => '238',
            'CW' => '599',
            'CX' => '61',
            'CY' => '357',
            'CZ' => '420',
            'DE' => '49',
            'DJ' => '253',
            'DK' => '45',
            'DM' => '1767',
            'DO' => '1809',
            'DO' => '1829',
            'DO' => '1849',
            'DZ' => '213',
            'EC' => '593',
            'EE' => '372',
            'EG' => '20',
            'EH' => '212',
            'ER' => '291',
            'ES' => '34',
            'ET' => '251',
            'EU' => '388',
            'FI' => '358',
            'FJ' => '679',
            'FK' => '500',
            'FM' => '691',
            'FO' => '298',
            'FR' => '33',
            'GA' => '241',
            'GB' => '44',
            'GD' => '1473',
            'GE' => '995',
            'GF' => '594',
            'GG' => '44',
            'GH' => '233',
            'GI' => '350',
            'GL' => '299',
            'GM' => '220',
            'GN' => '224',
            'GP' => '590',
            'GQ' => '240',
            'GR' => '30',
            'GT' => '502',
            'GU' => '1671',
            'GW' => '245',
            'GY' => '592',
            'HK' => '852',
            'HN' => '504',
            'HR' => '385',
            'HT' => '509',
            'HU' => '36',
            'ID' => '62',
            'IE' => '353',
            'IL' => '972',
            'IM' => '44',
            'IN' => '91',
            'IO' => '246',
            'IQ' => '964',
            'IR' => '98',
            'IS' => '354',
            'IT' => '39',
            'JE' => '44',
            'JM' => '1876',
            'JO' => '962',
            'JP' => '81',
            'KE' => '254',
            'KG' => '996',
            'KH' => '855',
            'KI' => '686',
            'KM' => '269',
            'KN' => '1869',
            'KP' => '850',
            'KR' => '82',
            'KW' => '965',
            'KY' => '1345',
            'KZ' => '7',
            'LA' => '856',
            'LB' => '961',
            'LC' => '1758',
            'LI' => '423',
            'LK' => '94',
            'LR' => '231',
            'LS' => '266',
            'LT' => '370',
            'LU' => '352',
            'LV' => '371',
            'LY' => '218',
            'MA' => '212',
            'MC' => '377',
            'MD' => '373',
            'ME' => '382',
            'MF' => '590',
            'MG' => '261',
            'MH' => '692',
            'MK' => '389',
            'ML' => '223',
            'MM' => '95',
            'MN' => '976',
            'MO' => '853',
            'MP' => '1670',
            'MQ' => '596',
            'MR' => '222',
            'MS' => '1664',
            'MT' => '356',
            'MU' => '230',
            'MV' => '960',
            'MW' => '265',
            'MX' => '52',
            'MY' => '60',
            'MZ' => '258',
            'NA' => '264',
            'NC' => '687',
            'NE' => '227',
            'NF' => '672',
            'NG' => '234',
            'NI' => '505',
            'NL' => '31',
            'NO' => '47',
            'NP' => '977',
            'NR' => '674',
            'NU' => '683',
            'NZ' => '64',
            'OM' => '968',
            'PA' => '507',
            'PE' => '51',
            'PF' => '689',
            'PG' => '675',
            'PH' => '63',
            'PK' => '92',
            'PL' => '48',
            'PM' => '508',
            'PR' => '1787',
            'PR' => '1939',
            'PS' => '970',
            'PT' => '351',
            'PW' => '680',
            'PY' => '595',
            'QA' => '974',
            'QN' => '374',
            'QS' => '252',
            'QY' => '90',
            'RE' => '262',
            'RO' => '40',
            'RS' => '381',
            'RU' => '7',
            'RW' => '250',
            'SA' => '966',
            'SB' => '677',
            'SC' => '248',
            'SD' => '249',
            'SE' => '46',
            'SG' => '65',
            'SH' => '290',
            'SI' => '386',
            'SJ' => '47',
            'SK' => '421',
            'SL' => '232',
            'SM' => '378',
            'SN' => '221',
            'SO' => '252',
            'SR' => '597',
            'SS' => '211',
            'ST' => '239',
            'SV' => '503',
            'SX' => '1721',
            'SY' => '963',
            'SZ' => '268',
            'TA' => '290',
            'TC' => '1649',
            'TD' => '235',
            'TG' => '228',
            'TH' => '66',
            'TJ' => '992',
            'TK' => '690',
            'TL' => '670',
            'TM' => '993',
            'TN' => '216',
            'TO' => '676',
            'TR' => '90',
            'TT' => '1868',
            'TV' => '688',
            'TW' => '886',
            'TZ' => '255',
            'UA' => '380',
            'UG' => '256',
            'UK' => '44',
            'US' => '1',
            'UY' => '598',
            'UZ' => '998',
            'VA' => '379',
            'VA' => '39',
            'VC' => '1784',
            'VE' => '58',
            'VG' => '1284',
            'VI' => '1340',
            'VN' => '84',
            'VU' => '678',
            'WF' => '681',
            'WS' => '685',
            'XC' => '991',
            'XD' => '888',
            'XG' => '881',
            'XL' => '883',
            'XN' => '857',
            'XN' => '858',
            'XN' => '870',
            'XP' => '878',
            'XR' => '979',
            'XS' => '808',
            'XT' => '800',
            'XV' => '882',
            'YE' => '967',
            'YT' => '262',
            'ZA' => '27',
            'ZM' => '260',
            'ZW' => '263',
        );

        $order = wc_get_order( $order_id );
        $this->log('Started to process order:' . $order->get_id() );
        $this->log('Checking curl version:' . curl_version()['ssl_version'] );
        // Only use custom form is setting is enabled
        $n = '';
        if( empty($_POST[$this->id . '-payer-alias']) && $this->customer_phone_form == 'yes' ) {
            // Add notice to the cart
            wc_add_notice(__( 'Phonenumber is missing', 'woocommerce-gateway-swish-ecommerce' ), 'error' );
        }elseif( isset($_POST[$this->id . '-payer-alias']) && $this->customer_phone_form == 'yes' ){
            $n = $_POST[$this->id . '-payer-alias'];
        }else{
            $n = $order->get_billing_phone();
        }
        //The default country code if the recipient's is unknown:
        $default_country_code  = '46';
        //Remove any parentheses and the numbers they contain:
        $n = preg_replace("/\([0-9]+?\)/", "", $n);
        //Strip spaces and non-numeric characters:
        $n = preg_replace("/[^0-9]/", "", $n);
        //Strip out leading zeros:
        $n = ltrim($n, '0');
        //Look up the country dialling code for this number:
        $billing_country = $order->get_billing_country();
        if ( array_key_exists($billing_country, $country_codes)  ) {
            $pfx = $country_codes[$billing_country];
        } else {
            $pfx = $default_country_code;
        }
        //Check if the number doesn't already start with the correct dialling code:
        if ( !preg_match('/^'.$pfx.'/', $n)  ) {
            $n = $pfx.$n;
        }
        $customerPhoneNumber = $n;
        $order->update_meta_data('_swish_payer_alias', $customerPhoneNumber);
        $order->save();

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

        $this->log('Curl version: '.curl_version()['ssl_version']);
        $this->log('Plugin version: '.REDLIGHT_SA_VERSION);
        $jsonResponse = self::apiCall('POST', 'paymentrequests', $data_string);
        
        $jsonArray = json_decode($jsonResponse,true);
        $headers = self::get_headers_from_curl_response($jsonArray['headers']['plain_text']);
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
            $order->update_meta_data('_swish_payment_request_id', $id[0]);
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
                'payment_request_id'  => $order->get_meta('_swish_payment_request_id',true),
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
        return $order && $order->get_meta('_swish_payment_reference',true);
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
        $swishPaymentReference = $order->get_meta('_swish_payment_reference',true);
        if ( ! $this->can_refund_swish_order( $order ) ) {
            $this->log( 'Refund Failed: Missing Swish Payment Reference ' );
            return false;
        }
        if($order->get_meta('_swish_payer_alias', true) !== null){
            $payer_alias = $order->get_meta('_swish_payer_alias', true);
        }else{
            $payer_alias = $order->get_billing_phone();
            $ptn = "/^(\+46|0|0046)(?=\d{8,15}$)/";  // Replace leading zero
            $rpltxt = "46";  // Replacement string
            $customerPhoneNumber = preg_replace("/[^0-9]+/", "", $payer_alias);
            $customerPhoneNumber = preg_replace($ptn, $rpltxt, $customerPhoneNumber);
        }
        
        $message = sprintf( __( "Refund for order %s", 'woocommerce-gateway-swish-ecommerce' ), $order->get_order_number() );
        //POST fields we'll be sending.
        $data =
            [
                'payeePaymentReference' 	=> $order->get_id(),
                'originalPaymentReference' 	=> $swishPaymentReference,
                'callbackUrl'           	=> $this->callback_url,
                'payerAlias'            	=> $this->swish_number,
                'payeeAlias'            	=> $payer_alias,
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
        $jsonResponse = self::apiCall('POST', 'refunds', $data_string);
        $jsonArray = json_decode($jsonResponse,true);
        $headers = self::get_headers_from_curl_response($jsonArray['headers']['plain_text']);
        
        // Handle the response.
        if(isset($headers)){
            preg_match("/\w{10,}\z/", $headers['Location'], $id);
            preg_match("/\d{3}/", $headers['http_code'], $http_code);
        }

        // Verify the code so we know if the transaction went through or not.
        if ($http_code[0] == 201) {
            // Add order notes.
            $this->log('Swish Refund created. Swish-API returned http_code : ' . $headers['http_code'] );
            $this->log('Swish Refund created. Swish-API returned payment id : ' . $id[0] );
            $this->log('Our callbackURL is : ' . $this->callback_url );

            // Add post meta
            $order->update_meta_data('_swish_refunds_id', $id[0]);
            $order->save();
            // Add order refund note
            $order->add_order_note( sprintf( __( 'Refunded %s - Refund ID: %s', 'woocommerce-gateway-swish-ecommerce' ), $amount, $id[0] ) );
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

    public static function log($message){
        $Swish_Ecommerce_For_WooCommerce = Swish_Ecommerce_For_WooCommerce::get_instance();
        $Swish_Ecommerce_For_WooCommerce->logger->log($message);
    }

}