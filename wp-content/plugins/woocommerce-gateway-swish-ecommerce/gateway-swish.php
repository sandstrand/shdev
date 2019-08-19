<?php
/**
 * Plugin Name: WooCommerce Swish Ecommerce Gateway
 * Plugin URI: https://redlight.se/swish
 * Description: Extends WooCommerce. Provides a <a href="https://www.getswish.se/" target="_blank">Swish Handel</a> gateway for WooCommerce.
 * Version: 4.0.1
 * Author: Redlight Media
 * Author URI: https://redlight.se/
 * Developer: Christopher Hedqvist
 * Developer URI: https://redlight.se/
 * Text Domain: woocommerce-gateway-swish-ecommerce
 * Domain Path: /languages
 * WC requires at least: 3.0.0
 * WC tested up to: 3.3.5
 *
 * Copyright: © 2018 Redlight.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Required minimums and constants
 */
define( 'REDLIGHT_SA_MIN_PHP_VER', '5.6.0' );
define( 'REDLIGHT_SA_MIN_WC_VER', '2.5.0' );
define( 'REDLIGHT_SA_MAIN_FILE', __FILE__ );
define( 'REDLIGHT_SA_PLUGIN_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'REDLIGHT_SA_PLUGIN_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );
define( 'REDLIGHT_SA_STORE_URL', 'http://redlight.se' );
define( 'REDLIGHT_SA_ITEM_NAME', 'Swish för handel (automatisk)' );
define( 'REDLIGHT_SA_ITEM_ID', '444' );
define( 'REDLIGHT_SA_VERSION', '4.0.1' );

if ( ! class_exists( 'Swish_Ecommerce_For_WooCommerce' ) ) {
	/**
	 * Class Swish_Ecommerce_For_WooCommerce
	 */
	class Swish_Ecommerce_For_WooCommerce {
        /**
		 * The reference the *Singleton* instance of this class.
		 *
		 * @var $instance
		 */
        protected static $instance;
        
        /**
		 * Reference to API Handler class.
		 *
		 * @var $api_handler
		 */
        public $api_handler;
        
        /**
		 * Reference to popup class.
		 *
		 * @var $popup
		 */
        public $popup;
        
         /**
		 * Reference to logger class.
		 *
		 * @var $logger
		 */
		public $logger;

        /**
		 * Returns the *Singleton* instance of this class.
		 *
		 * @return self::$instance The *Singleton* instance.
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
        }
        
        /**
		 * Private clone method to prevent cloning of the instance of the
		 * *Singleton* instance.
		 *
		 * @return void
		 */
		private function __clone() {
			wc_doing_it_wrong( __FUNCTION__, __( 'Nope' ), '1.0' );
        }
        
        /**
		 * Private unserialize method to prevent unserializing of the *Singleton*
		 * instance.
		 *
		 * @return void
		 */
		private function __wakeup() {
			wc_doing_it_wrong( __FUNCTION__, __( 'Nope' ), '1.0' );
        }
        
        /**
		 * Protected constructor to prevent creating a new instance of the
		 * *Singleton* via the `new` operator from outside of this class.
		 */
		protected function __construct() {
            add_action( 'plugins_loaded', array( $this, 'init' ) );
            add_action( 'admin_notices', array( $this, 'admin_notices' ) );
        }
        
        /**
		 * Init the plugin after plugins_loaded so environment variables are set.
		 */
		public function init() {
            // Init the gateway itself.
            $this->init_updater();
			$this->init_gateways();
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );
        }
        
        /**
		 * Adds plugin action links
		 *
		 * @param array $links Plugin action link before filtering.
		 *
		 * @return array Filtered links.
		 */
		public function plugin_action_links( $links ) {
            $setting_link = $this->get_setting_link();
			$settings_link = '<a href="'. esc_url($setting_link) .'">'.__( 'Settings', 'woocommerce-gateway-swish-ecommerce' ).'</a>';
            array_unshift( $links, $settings_link );
            return $links;
        }
        /**
		 * Get setting link.
		 *
		 * @since 1.0.0
		 *
		 * @return string Setting link
		 */
		public function get_setting_link() {
			$section_slug = 'wc_gateway_swish_ecommerce';

			return admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $section_slug );
		}

        /**
		 * Initialize the gateway. Called very early - in the context of the plugins_loaded action
		 *
		 * @since 1.0.0
		 */
        public function init_updater(){
            add_action( 'admin_init', array( $this, 'plugin_updater' ) );
            add_action( 'admin_init', array( $this, 'activate_license' ));	
            add_action( 'admin_init', array( $this, 'deactivate_license' ));
        }
        public function kco_v2_external_payment_method_support(){
            if( !function_exists('is_plugin_active') ) {
                include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            }
            //KCO v2
            if( is_plugin_active( 'woocommerce-gateway-klarna/woocommerce-gateway-klarna.php' ) ) {
                /**
                 * Add some extra settings
                 *
                 */
                add_filter( 'redlight_swish_ecommerce_settings', 'rswemp_klarna_checkout_form_fields' );
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
        public function kco_v3_external_payment_method_support(){
            if( !function_exists('is_plugin_active') ) {
                include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            }
            //KCO v3
            if( is_plugin_active( 'klarna-checkout-for-woocommerce/klarna-checkout-for-woocommerce.php' ) ) {
                /**
                 * Add some extra settings
                 *
                 */
                add_filter( 'redlight_swish_ecommerce_settings', 'rswemp_klarna_checkout_form_fields_v3' );
                function rswemp_klarna_checkout_form_fields_v3( $settings ) {
                    $icon = plugins_url( '/assets/images/swish_logo.png', __FILE__ );
                    $settings['kco_v3_epm_swish_settings_title'] = array(
                        'title' => __( 'Klarna Checkout v3 - External Payment Method - Swish', 'woocommerce-gateway-swish-ecommerce' ),
                        'type'  => 'title',
                    );
                    $settings['kco_v3_epm_swish_active'] = array(
                        'label'     => __( 'Show Swish in Klarna Checkout', 'woocommerce-gateway-swish-ecommerce' ),
                        'type'      => 'checkbox',
                        'default'   => 'no',
                        'description'   => __( 'This will display as an external payment method in Klarna Checkout', 'woocommerce-gateway-swish-ecommerce' ),
                    );
                    $settings['kco_v3_epm_swish_name'] = array(
                        'title'       => __( 'Name', 'woocommerce-gateway-swish-ecommerce' ),
                        'type'        => 'text',
                        'description' => __( 'Title for Swish payment method. This controls the title which the user sees in the checkout form.', 'woocommerce-gateway-swish-ecommerce' ),
                        'default'     => __( 'Swish', 'woocommerce-gateway-klarna' )
                    );
                    $settings['kco_v3_epm_swish_img_url'] = array(
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
                add_filter('kco_wc_create_order', 'redlight_kco_v3_create_order_swish');
                function redlight_kco_v3_create_order_swish( $create ) {
                    $merchant_urls    = KCO_WC()->merchant_urls->get_urls();
                    $confirmation_url = $merchant_urls['confirmation'];

                    $swish_ecommerce_settings = get_option( 'woocommerce_redlight_swish-ecommerce_settings' );
                    $active   		= $swish_ecommerce_settings['kco_v3_epm_swish_active'];
                    $name   		= ( isset( $swish_ecommerce_settings['kco_v3_epm_swish_name'] ) ) ? $swish_ecommerce_settings['kco_v3_epm_swish_name'] : '';
                    $image_url   	= ( isset( $swish_ecommerce_settings['kco_v3_epm_swish_img_url'] ) ) ? $swish_ecommerce_settings['kco_v3_epm_swish_img_url'] : '';
                    $description   	= ( isset( $swish_ecommerce_settings['description'] ) ) ? $swish_ecommerce_settings['description'] : '';
                    if($active == 'yes'){
                        $klarna_external_payment = array(
                            'name' 			=> $name,
                            'redirect_url' 	=> add_query_arg( 'kco-v3-external-payment', 'swish', $confirmation_url ),
                            'image_url' 	=> $image_url,
                            'description' 	=> $description,
                        );
                        $klarna_external_payment = array( $klarna_external_payment );
                            
                        $create['external_payment_methods'] = $klarna_external_payment;
                    }
                    
                    
                    return $create;
                }

                add_action( 'kco_wc_before_submit', 'kcoepm_payment_method' );
                function kcoepm_payment_method() {
                    if ( isset ( $_GET['kco-v3-external-payment'] ) && 'swish' == $_GET['kco-v3-external-payment'] ) { ?>
                        $('input#payment_method_redlight_swish-ecommerce').prop('checked', true);
                    // Check terms and conditions to prevent error.
                    $('input#legal').prop('checked', true);
                    <?php }
                    $swish_ecommerce_settings = get_option( 'woocommerce_redlight_swish-ecommerce_settings' );
                    $customer_phone_form   		= $swish_ecommerce_settings['customer_phone_form'];
                    if($customer_phone_form == 'yes'){ ?>
                        $( "#redlight_swish-ecommerce-payer-alias" ).val($('#billing_phone').val());
                    <?php }	
                }
                add_filter( 'kco_wc_klarna_order_pre_submit', 'kcoepm_retrieve_order' );
                function kcoepm_retrieve_order( $klarna_order ) {
                    if ( isset ( $_GET['kco-v3-external-payment'] ) && 'swish' == $_GET['kco-v3-external-payment'] ) {
                        $klarna_order_id = WC()->session->get( 'kco_wc_order_id' );
                        $response        = KCO_WC()->api->request_pre_retrieve_order( $klarna_order_id );
                        $klarna_order    = $response;
                    }
                    return $klarna_order;
                }
                
            }
        }
        /**
         * This is a means of catching errors from the activation method above and displaying it to the customer
         */
        public function admin_notices() {
            if ( isset( $_GET['sl_activation'] ) && ! empty( $_GET['message'] ) ) {

                switch( $_GET['sl_activation'] ) {

                    case 'false':
                        $message = urldecode( $_GET['message'] );
                        ?>
                        <div class="error">
                            <p><?php echo $message; ?></p>
                        </div>
                        <?php
                        break;

                    case 'true':
                    default:
                        // Developers can put a custom success message here for when activation is successful if they way.
                        break;

                }
            }
        }

        public function init_gateways() {
			if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
				return;
			}

			include_once REDLIGHT_SA_PLUGIN_PATH . '/includes/class-wc-gateway-swish-ecommerce.php';
            include_once REDLIGHT_SA_PLUGIN_PATH . '/includes/class-wc-gateway-swish-ecommerce-settings.php';
            include_once REDLIGHT_SA_PLUGIN_PATH . '/includes/class-wc-gateway-swish-ecommerce-api-response.php';
			include_once REDLIGHT_SA_PLUGIN_PATH . '/includes/class-wc-gateway-swish-ecommerce-api-handler.php';
			include_once REDLIGHT_SA_PLUGIN_PATH . '/includes/class-wc-gateway-swish-ecommerce-popup.php';
			include_once REDLIGHT_SA_PLUGIN_PATH . '/includes/class-wc-gateway-swish-ecommerce-status-report.php';
			include_once REDLIGHT_SA_PLUGIN_PATH . '/includes/class-wc-gateway-swish-ecommerce-logger.php';
			include_once REDLIGHT_SA_PLUGIN_PATH . '/includes/class-wc-gateway-swish-ecommerce-rest-api.php';

            $this->api_handler   = new WC_Gateway_Swish_Ecommerce_API_Handler();
			$this->popup         = new WC_Gateway_Swish_Ecommerce_Popup();
			$this->logger        = new WC_Gateway_Swish_Ecommerce_Logger();
			$WC_Gateway_Swish_Ecommerce_REST_API = new WC_Gateway_Swish_Ecommerce_REST_API();

            load_plugin_textdomain( 'woocommerce-gateway-swish-ecommerce', false, plugin_basename( __DIR__ ) . '/languages');
            add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateways' ) );
            
            $this->kco_v2_external_payment_method_support();
            $this->kco_v3_external_payment_method_support();
        }
        
        /**
		 * Add the gateways to WooCommerce
		 *
		 * @param  array $methods Payment methods.
		 *
		 * @return array $methods Payment methods.
		 * @since  1.0.0
		 */
		public function add_gateways( $methods ) {
			$methods[] = 'WC_Gateway_Swish_Ecommerce';

			return $methods;
        }
        
        public function plugin_updater() {
            // retrieve our license key from the DB
            $license = get_option( 'woocommerce_redlight_swish-ecommerce_settings');
            $license_key = $license['redlight_license_key'];

            if( !class_exists( 'EDD_SL_Plugin_Updater' ) ) {
                // load our custom updater
                include_once REDLIGHT_SA_PLUGIN_PATH . '/includes/EDD_SL_Plugin_Updater.php';
            }
            // setup the updater
            $edd_updater = new EDD_SL_Plugin_Updater( REDLIGHT_SA_STORE_URL, __FILE__,
                array(
                    'version' => REDLIGHT_SA_VERSION,      // current version number
                    'license' => $license_key,             // license key (used get_option above to retrieve from DB)
                    'item_id' => REDLIGHT_SA_ITEM_ID,      // ID of the product
                    'author'  => 'Redlight Media',         // author of this plugin
                    'beta'    => false,
                )
            );
        }
        public function activate_license() {
            // listen for our activate button to be clicked
            if( isset( $_POST['woocommerce_redlight_swish-ecommerce_redlight_license_key'] ) && !isset( $_POST['woocommerce_redlight_swish-ecommerce_redlight_license_deactivate'] ) ) {
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
		        if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
                    if ( is_wp_error( $response ) ) {
                        $message = $response->get_error_message();
                    } else {
                        $message = __( 'An error occurred, please try again.' );
                    }
            
                } else {
        
                    $license_data = json_decode( wp_remote_retrieve_body( $response ) );
                    if ( false === $license_data->success ) {
                        switch( $license_data->error ) {
                            case 'expired' :
                                $message = sprintf(
                                    __( 'Your license key expired on %s.' ),
                                    date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, current_time( 'timestamp' ) ) )
                                );
                                break;
                            case 'disabled' :
                            case 'revoked' :
                                $message = __( 'Your license key has been disabled.' );
                                break;
                            case 'missing' :
                                $message = __( 'Invalid license.' );
                                break;
                            case 'invalid' :
                            case 'site_inactive' :
                                $message = __( 'Your license is not active for this URL.' );
                                break;
                            case 'item_name_mismatch' :
                                $message = sprintf( __( 'This appears to be an invalid license key for %s.' ), REDLIGHT_SA_ITEM_NAME );
                                break;
                            case 'no_activations_left':
                                $message = __( 'Your license key has reached its activation limit.' );
                                break;
                            default :
                                $message = __( 'An error occurred, please try again.' );
                                break;
                        }
                    }
                }
                $this->logger->log('This is the response from "Activate license":');
                $this->logger->log(json_encode($license_data));
                // Check if anything passed on a message constituting a failure
                // if ( ! empty( $message ) ) {
                //     $base_url = $this->get_setting_link();
                //     $redirect = add_query_arg( array( 'sl_activation' => 'false', 'message' => urlencode( $message ) ), $base_url );

                //     wp_redirect( $redirect );
                //     exit();
                // }
                
                // $license_data->license will be either "valid" or "invalid"
                update_option( 'redlight_swish_ecommerce_license_status', $license_data->license );
    
            }
        }
        public function deactivate_license() {
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

                $this->logger->log('This is the response from "Deactivate license":');
                $this->logger->log(json_encode($license_data));
    
                // $license_data->license will be either "deactivated" or "failed"
                if( $license_data->license == 'deactivated' )
                    delete_option( 'redlight_swish_ecommerce_license_status' );
            }
        }

    }
    Swish_Ecommerce_For_WooCommerce::get_instance();
}
