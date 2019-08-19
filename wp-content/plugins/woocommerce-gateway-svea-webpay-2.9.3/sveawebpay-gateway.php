<?php
/**
 * @wordpress-plugin
 * Plugin Name: WooCommerce SveaWebPay Payment Gateway
 * Description: Supercharge your WooCommerce Store with powerful features to pay via Svea Ekonomi Creditcard, Direct Bank Payment, Invoice and Part Payment.
 * Version: 2.9.3
 * Author: The Generation
 * Author URI: https://thegeneration.se/
 * Domain Path: /languages
 * Text Domain: sveawebpay
 * WC tested up to: 3.2.1
 */

/**
 * Exit if accessed directly
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WC_SveaWebPay_Gateway' ) ) :

class WC_SveaWebPay_Gateway {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @var     string
	 */
	const VERSION = '2.9.3';

	/**
	 * Plugin slug
	 *
	 * @var 	string
	 */
	const PLUGIN_SLUG = 'sveawebpay';

	/**
	 * General class constructor where we'll setup our actions, hooks, and shortcodes.
	 *
	 * @return 	WC_SveaWebPay_Gateway
	 */
	public function __construct() {

		/**
		 * Define the plugin base directory
		 */
		if( ! defined( 'WC_SVEAWEBPAY_GATEWAY_PLUGIN_DIR' ) ) {
			define( 'WC_SVEAWEBPAY_GATEWAY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
		}

		/**
		 * Define the plugin base url
		 */
		if( ! defined( 'WC_SVEAWEBPAY_GATEWAY_PLUGIN_URL' ) ) {
			define( 'WC_SVEAWEBPAY_GATEWAY_PLUGIN_URL', plugins_url( '/', __FILE__ ) );
		}

		register_activation_hook( __FILE__, array( $this, 'plugin_activation' ) );
		register_deactivation_hook( __FILE__, array( $this, 'plugin_deactivation' ) );

		load_plugin_textdomain( self::PLUGIN_SLUG, false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

		/**
		 * Check if woocommerce is activated, else display a message and deactivate the plugin
		 */
		if( ! self::is_woocommerce_installed() ) {
			if( isset( $_GET['action'] ) && 
				! in_array( $_GET['action'], array( 'activate-plugin', 'upgrade-plugin', 'activate', 'do-plugin-upgrade' ) ) ) {
				return;
			}

			$notices = get_option( 'sveawebpay_deferred_admin_notices', array() );
			$notices[] = array( 
				'type' 		=> 'error', 
				'message' 	=> __( 'WooCommerce Svea WebPay Gateway has been deactivated because WooCommerce is not installed. Please install WooCommerce and re-activate.', self::PLUGIN_SLUG )
			);

			update_option( 'sveawebpay_deferred_admin_notices', $notices );
			add_action( 'admin_notices', array( $this, 'display_admin_notices' ) );
			add_action( 'admin_init', array( $this, 'deactivate_gateway' ) );
			return;
		}

		$this->plugin_description = __( 'Supercharge your WooCommerce Store with powerful features to pay via Svea Ekonomi Creditcard, Direct Bank Payment, Invoice and Part Payment.', self::PLUGIN_SLUG );

		add_action( 'plugins_loaded', array( $this, 'init' ) );
		add_action( 'admin_notices', array( $this, 'display_admin_notices' ) );

		add_action( 'admin_notices', array( $this, 'check_compatibility' ) );

		// add_action( 'admin_footer-edit.php', array( &$this, 'add_order_bulk_actions'), 10 );
		// add_action( 'load-edit.php', array( &$this, 'bulk_admin_order_actions' ), 10 );

		add_action( 'woocommerce_attribute_label', array( $this, 'label_order_item_meta' ), 20, 2 );
		
		// Hide these for now, we'll have to wait for Svea to fix their API
		// add_action( 'woocommerce_order_item_add_action_buttons', array(&$this, 'display_admin_action_buttons') );

		add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'admin_display_svea_order_id' ) );

		add_action( 'add_meta_boxes', array( $this, 'add_admin_functions_meta_box' ) );
		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'add_invoice_fee' ), 40 );

    	add_action( 'woocommerce_checkout_process', array( $this, 'checkout_validation_handler' ), 10, 1 );

    	add_filter( 'woocommerce_hidden_order_itemmeta', array( $this, 'hide_order_item_meta' ), 10, 1 );

		add_filter( 'woocommerce_get_order_item_totals', array( $this, 'receipt_display_svea_order_id' ), 10, 2 );
		add_filter( 'woocommerce_payment_gateways', array( $this, 'woocommerce_add_gateway_svea_gateway' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'checkout_enqueue_scripts' ) );

		add_action( 'admin_init', array( $this, 'check_plugin_updates' ) );

		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'settings_link' ) );

		add_filter( 'woocommerce_subscriptions_update_payment_via_pay_shortcode', array( $this, 'should_update_payment_method' ), 10, 3 );
	
		add_action( 'woocommerce_order_status_completed', array( $this, 'sync_delivered_order' ), 10, 1 );
		add_action( 'woocommerce_order_status_cancelled', array( $this, 'sync_cancelled_order' ), 10, 1 );
		add_action( 'woocommerce_order_status_refunded', array( $this, 'sync_refunded_order' ), 10, 1 );

		// Part payment widget
        add_action( 'init', array( $this, 'product_part_payment_widget' ), 11, 1 );
	}

	/**
	 * Initializes and loads essential classes
	 *
	 * @return 	void
	 */
	public function init() {
		/**
		 * Load the Svea integration package.
		 */
		// require_once( WC_SVEAWEBPAY_GATEWAY_PLUGIN_DIR . 'classes/lib/svea/Includes.php' );
		require_once( WC_SVEAWEBPAY_GATEWAY_PLUGIN_DIR . 'vendor/autoload.php' );

		/**
		 * Load funtionality classes
		 */
		require_once( WC_SVEAWEBPAY_GATEWAY_PLUGIN_DIR . 'classes/wc-gateway-svea-shortcodes.php' );
		require_once( WC_SVEAWEBPAY_GATEWAY_PLUGIN_DIR . 'classes/wc-gateway-svea-ajax-functions.php' );
		require_once( WC_SVEAWEBPAY_GATEWAY_PLUGIN_DIR . 'classes/wc-gateway-svea-admin-functions.php' );

		/**
		 * Load the helper files
		 */
		require_once( WC_SVEAWEBPAY_GATEWAY_PLUGIN_DIR . 'classes/wc-gateway-svea-helper.php' );
		
		/**
		 * Load the Svea configuration classes
		 */
		require_once( WC_SVEAWEBPAY_GATEWAY_PLUGIN_DIR . 'classes/wc-svea-config-production.php' );
		require_once( WC_SVEAWEBPAY_GATEWAY_PLUGIN_DIR . 'classes/wc-svea-config-test.php' );

		/**
		 * If WC_Payment Gateway isn't set don't load class files
		 * to avoid error
		 */
		if( ! class_exists( 'WC_Payment_Gateway' ) ) {
			return;
		}

		/** 
		 * Load all Svea payment gateway classes
		 */
		require_once( WC_SVEAWEBPAY_GATEWAY_PLUGIN_DIR . 'classes/wc-gateway-svea-card.php' );
		require_once( WC_SVEAWEBPAY_GATEWAY_PLUGIN_DIR . 'classes/wc-gateway-svea-direct-bank.php' );
		require_once( WC_SVEAWEBPAY_GATEWAY_PLUGIN_DIR . 'classes/wc-gateway-svea-invoice.php' );
		require_once( WC_SVEAWEBPAY_GATEWAY_PLUGIN_DIR . 'classes/wc-gateway-svea-part-pay.php' );
		
	}

	public function check_plugin_updates() {
		$svea_db_version = get_option( 'sveawebpay_plugin_version', false );

		/**
		 * See if the version has been changed
		 */
		if( ! $svea_db_version || $svea_db_version != self::VERSION ) {
			// Run plugin update function here
			update_option( 'sveawebpay_plugin_version', self::VERSION );
		}
	}
	

	/**
	 * Check if WooCommerce is installed and activated
	 *
	 * @return 	boolean 	whether or not WooCommerce is installed
	 */
	public static function is_woocommerce_installed() {
		/**
		 * Get a list of active plugins
		 */
		$active_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins' ) );

		$is_woocommerce_installed = false;

		/**
		 * Loop through the active plugins
		 */
		foreach( $active_plugins as $plugin ) {
			/**
			 * If the plugin name matches WooCommerce
			 * it means that WooCommerce is active
			 */
			if( preg_match( '/.+\/woocommerce\.php/', $plugin ) ) {
				$is_woocommerce_installed = true;
				break;
			}
		}

		return $is_woocommerce_installed;
	}

	/**
	 * Check compatibility with PHP- and WooCommerce-versions
	 *
	 * @return 	void
	 */
	public function check_compatibility() {
		
		/**
		 * Only display message if the current user is administrator
		 */
		if( ! current_user_can( 'manage_options' ) )
			return;

		/**
		 * Required modules by the Svea WebPay Integration package
		 */
		if( ! extension_loaded( 'soap' ) || ! class_exists( 'SoapClient' ) ) {
			printf(
				'<div class="error"><h3>Svea WebPay</h3><p>'.
				__( 'The PHP Module <strong>Soap</strong> is not enabled. Svea WebPay requires this module to be enabled for it to function properly. Talk to your web host and make sure it is enabled.', self::PLUGIN_SLUG ).
				'</p></div>'
			);
		}

		/**
		 * Versions that are tested with this module.
		 * Remember to test this with each new version.
		 */
		$php_version = '5.5.0';
		$woocommerce_version = '3.0.0';

		if( version_compare( PHP_VERSION, $php_version, '<' ) ) {
			printf(
				'<div class="error"><h3>Svea WebPay</h3><p>'.
				__( 'Your PHP version is <strong>%s</strong>, lower than the supported version for Svea WebPay for WooCommerce, <strong>%s</strong>. The integration might not work as expected.', self::PLUGIN_SLUG ).
				'</p></div>',
				PHP_VERSION,
				$php_version
			);
		}

		if( defined( 'WOOCOMMERCE_VERSION' ) 
			&& version_compare( WOOCOMMERCE_VERSION, $woocommerce_version, '<' ) ) {
			printf(
				'<div class="error"><h3>Svea WebPay</h3><p>'.
				__( 'Your WooCommerce version is <strong>%s</strong>, lower than the supported version for Svea WebPay for WooCommerce, <strong>%s</strong>. The integration might not work as expected.', self::PLUGIN_SLUG ).
				'</p></div>',
				WOOCOMMERCE_VERSION,
				$woocommerce_version
			);

			if( version_compare( WOOCOMMERCE_VERSION, '3.0.0', '<' ) ) {
				printf(
					'<div class="error"><h3>Svea WebPay</h3><p>'.
					__( 'Version 3.0.0 of WooCommerce brought breaking changes and any version lower than that will not work with this version of the Svea WebPay module. The module has been deactivated. Please upgrade WooCommerce and activate the gateway again.', self::PLUGIN_SLUG ).
					'</p></div>',
					WOOCOMMERCE_VERSION,
					$woocommerce_version
				);

				// Version 3.0.0 brings breaking changes and lower version will not work with this module
				// Deactivate this plugin if version is too low.
				$this->deactivate_gateway();
			}
		}
		
	}

	/**
	 * Handles plugin activation
	 *
	 * @return 	void
	 */
	public function plugin_activation() {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php');

		if( ! self::is_woocommerce_installed() ) {

			$notices = get_option( 'sveawebpay_deferred_admin_notices', array() );
			$notices[] = array(
				"type" 		=> "error",
				"message" 	=> _( 'WooCommerce Svea WebPay Gateway has been deactivated because WooCommerce is not installed. Please install WooCommerce and re-activate.' )
			);

			update_option( 'sveawebpay_deferred_admin_notices', $notices );
			add_action( 'admin_notices', array( &$this, 'display_admin_notices' ) );

			add_action( 'admin_init', array( &$this, 'deactivate_gateway' ) );
			return;
		}

		$notices = get_option( 'sveawebpay_deferred_admin_notices', array() );
		$notices[] = array(
			"type" 		=> "updated",
			"message" 	=> __( 'WooCommerce SveaWebPay Payment Gateway has now been activated, you can configure the different gateways', self::PLUGIN_SLUG ) . ' <a href="' . get_admin_url( null, 'admin.php?page=wc-settings&tab=checkout&section=wc_gateway_svea_card' ) . '">'.__('on this page', self::PLUGIN_SLUG ).'</a>. '.__( 'If you don´t have a contract with SveaWebPay please contact them', self::PLUGIN_SLUG ).' <a href="http://webpay.svea.com/sv/swe/salja/kontakta-vara-saljare/">'.__('here', self::PLUGIN_SLUG ).'</a>.'
		);

		update_option( 'sveawebpay_deferred_admin_notices', $notices );

		update_option( 'sveawebpay_plugin_version', self::VERSION );
	}

	/**
	 * Handles plugin de-activation
	 *
	 * @TODO
	 * @return 	void
	 */
	public function plugin_deactivation() { }

	/**
	 * Display admin notices saved in the cache.
	 *
	 * @return 	void
	 */
	public function display_admin_notices() {
		if( $notices = get_option( 'sveawebpay_deferred_admin_notices' ) ) {
			foreach($notices as $notice) {
				echo "<div class='".$notice['type']."'><p>".$notice['message']."</p></div>";
			}

			delete_option( 'sveawebpay_deferred_admin_notices' );
		}
	}

	/**
	 * Deactivate the WooCommerce Svea WebPay Gateway
	 *
	 * @return 	void
	 */
	public function deactivate_gateway() {
		if( ! function_exists( 'deactivate_plugins' ) )
			return;

		deactivate_plugins( plugin_basename( __FILE__ ) );
	}

	/**
	 * Add settings link on the plugin page.
	 *
	 * @param 	array 	$links 	associative array of links
	 * @return 	array 	associative array of links
	 */
	public function settings_link($links) { 
		$settings_link = 
			'<a href="admin.php?page=wc-settings&tab=checkout&section=wc_gateway_svea_card">'.
				__( 'Settings', self::PLUGIN_SLUG ).
			'</a>'; 
		array_unshift( $links, $settings_link ); 
		return $links;
	}

	/**
	 * Disable Subscriptions default way of changing payment method
	 * in favor to implement our own way
	 *
	 * @param 	boolean 			$update 	whether or not the payment method should be updated
	 * @param 	string 				$new_payment_method 	the payment method that the subscription is changed to
	 * @param 	WC_Subscription 	$subscription 	the subscription object
	 * @return 	boolean 	whether or not the payment method should be updated
	 */
	public function should_update_payment_method( $update, $new_payment_method, $subscription ) {
		if ( $new_payment_method === WC_Gateway_Svea_Invoice::GATEWAY_ID
			|| $new_payment_method === WC_Gateway_Svea_Card::GATEWAY_ID ) {
			$update = false;
		}

		return $update;
	}

	/**
	 * Display buttons for admin actions
	 *
	 * @return 	void
	 */
	public function display_admin_action_buttons() {
		global $post;

		if( is_null( $post ) || ! isset( $post->ID ) ) {
			return;
		}

		$order = wc_get_order( $post->ID );

		if( ! $order ) {
			return;
		}

		$svea_order_id = ( $value = get_post_meta( $order->get_id(), "_svea_order_id", true ) ) 
			? $value : wc_get_order_item_meta( $order->get_id(), "svea_order_id" );

		if( strlen( $svea_order_id ) <= 0 )
			return;

		$payment_method = $order->get_payment_method();

		if( $payment_method === WC_Gateway_Svea_Direct_Bank::GATEWAY_ID )
			$action_buttons_function = 'WC_Gateway_Svea_Direct_Bank::display_admin_action_buttons';
		else if( $payment_method === WC_Gateway_Svea_Invoice::GATEWAY_ID )
			$action_buttons_function = 'WC_Gateway_Svea_Invoice::display_admin_action_buttons';
		else if( $payment_method === WC_Gateway_Svea_Card::GATEWAY_ID )
			$action_buttons_function = 'WC_Gateway_Svea_Card::display_admin_action_buttons';
		else
			return;

		call_user_func( $action_buttons_function );
	}

	/**
	 * Makes the labels of order item meta for
	 *
	 * @return 	string 	
	 */
	public function label_order_item_meta( $label, $meta_key ) {
		if( $meta_key === "svea_delivered" ) {
			return __( 'Delivered in Svea', self::PLUGIN_SLUG );
		} else if( $meta_key === "svea_credited" ) {
			return __( 'Credited in Svea', self::PLUGIN_SLUG );
		}

		return $label;
	}

	/**
	 * Adds an invoice fee to the WooCommerce cart if
	 * it has been set in the invoice gateway
	 *
	 * @return 	void
	 */
	public function add_invoice_fee() {
		$current_gateway = WC_Gateway_Svea_Helper::get_current_gateway();

		if( ! $current_gateway ||
			get_class( $current_gateway ) != "WC_Gateway_Svea_Invoice" ) {
			return;
		}

		WC_Gateway_Svea_Invoice::init()->add_invoice_fee();
	}

	/**
	 * Register and enqueue stylesheets and javascripts for backend use
	 *
	 * @return 	void
	 */
	public function admin_enqueue_scripts( $hook ) {
		/**
		 * Link to the font awesome stylesheet for icons
		 */
		wp_enqueue_style( 'font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css', array(), '4.5.0' );

		wp_enqueue_style( 'sveawebpay-backend-css', WC_SVEAWEBPAY_GATEWAY_PLUGIN_URL . 'assets/css/backend.min.css', array(), self::VERSION );
		wp_enqueue_script( 'sveawebpay-backend-js', WC_SVEAWEBPAY_GATEWAY_PLUGIN_URL . 'assets/js/backend.min.js', array('jquery'), self::VERSION, true );

		global $post, $woocommerce;

		if( ! is_null( $post ) ) {
			$svea_data["adminCreditUrl"] = admin_url( 
				'admin-post.php?action=svea_webpay_admin_credit_order&order_id=' 
				. $post->ID
				. '&security=' 
				. wp_create_nonce( WC_SveaWebPay_Gateway_Admin_Functions::CREDIT_NONCE )
				. '&order_items=' 
			);

			$svea_data["adminDeliverUrl"] = admin_url( 
				'admin-post.php?action=svea_webpay_admin_deliver_order&order_id=' 
				. $post->ID 
				. '&security=' 
				. wp_create_nonce( WC_SveaWebPay_Gateway_Admin_Functions::DELIVER_NONCE )
				. '&order_items=' 
			);
		}

		$countries = $woocommerce->countries->get_allowed_countries();

		if( count( $countries ) > 1) {
			$onlyOneAllowedCountry = false;
		} else {
			foreach( $countries as $key => $country ) {
				$onlyOneAllowedCountry = $key;
				break;
			}
		}

		$svea_data["gaSecurity"] = wp_create_nonce( WC_SveaWebPay_Gateway_Ajax_Functions::GET_ADDRESS_NONCE_NAME );
		$svea_data["ajaxUrl"] = admin_url( 'admin-ajax.php' );
		$svea_data["onlyOneAllowedCountry"] = ( $onlyOneAllowedCountry ? $onlyOneAllowedCountry : false );

		/**
		 * Localize the javascript with Svea data
		 */
		wp_localize_script( 'sveawebpay-backend-js', 'Svea', $svea_data);

		$phrases = array( 
			"confirm_credit_items" => __( 'Are you sure you want to credit %d items?', self::PLUGIN_SLUG ),
			"confirm_deliver_items" => __( 'Are you sure you want to deliver %d items?', self::PLUGIN_SLUG ),
			"not_selected_any_items"	=> __( 'You have not selected any items yet.', self::PLUGIN_SLUG ),
			"no_payment_plans_country_total" => __( 'There are no available payment plans for this country and order total.', self::PLUGIN_SLUG ),
			"your_address_was_found" => __( 'Your address was found.', self::PLUGIN_SLUG ),
			"part_payment_plans" => __( 'Part Payment Plans', self::PLUGIN_SLUG ),
			"company_name" => __( 'Company Name', self::PLUGIN_SLUG ),
			"invoice_fee" => __( 'Invoice fee', self::PLUGIN_SLUG ),
			"includes" => __( 'Includes', self::PLUGIN_SLUG ),
			"vat" => __( 'VAT', self::PLUGIN_SLUG ),
			"could_not_get_address" => __( 'An error occurred whilst getting your address. Please try again later.', self::PLUGIN_SLUG )
		);

		/**
		 * Localize the javascript with translated phrases
		 */
		wp_localize_script( 'sveawebpay-backend-js', 'Phrases', $phrases );
	}

	/**
	 * Register and enqueue stylesheets and javascripts
 	 *
 	 * @return 	void
 	 */
	public function checkout_enqueue_scripts() {
	    /**
		 * Only enqueue scripts and styles in the checkout page
		 */
		if( ( ! function_exists( 'is_checkout' ) || ! is_checkout() )
			&& ( ! function_exists( 'is_checkout_pay_page' ) || ! is_checkout_pay_page() )
            && ( ! function_exists( 'is_product' ) || ! is_product() ) ) {
			return;
		}

		/**
		 * Link to the font awesome stylesheet for icons
		 */
		wp_enqueue_style( 'font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.6.3/css/font-awesome.min.css', array(), '4.6.3' );

		/**
		 * Enqueue styles and javascript, cache-bust using versioning
		 */
		wp_enqueue_style( 'sveawebpay-styles', WC_SVEAWEBPAY_GATEWAY_PLUGIN_URL . 'assets/css/frontend.min.css', array(), self::VERSION );
		//shdev
		//wp_enqueue_script( 'sveawebpay-js', WC_SVEAWEBPAY_GATEWAY_PLUGIN_URL . 'assets/js/frontend.min.js', array('jquery'), self::VERSION, true );

		global $woocommerce;

		$countries = $woocommerce->countries->get_allowed_countries();

		if( count( $countries ) > 1) {
			$onlyOneAllowedCountry = false;
		} else {
			foreach( $countries as $key => $country ) {
				$onlyOneAllowedCountry = $key;
				break;
			}
		}

		$svea_data = array();

		$wc_invoice = WC_Gateway_Svea_Invoice::init();
		$wc_part_pay = WC_Gateway_Svea_Part_Pay::init();

		$svea_data["gaSecurity"] = wp_create_nonce( WC_SveaWebPay_Gateway_Ajax_Functions::GET_ADDRESS_NONCE_NAME );
		$svea_data["ajaxUrl"] = admin_url( 'admin-ajax.php' );
		$svea_data["onlyOneAllowedCountry"] = ( $onlyOneAllowedCountry ? $onlyOneAllowedCountry : false );
		$svea_data["sameShippingAsBilling"] = array(
			$wc_invoice->id => $wc_invoice->enabled === "yes" ? ( $wc_invoice->same_shipping_as_billing ? true : false ) : false,
			$wc_part_pay->id => $wc_part_pay->enabled === "yes" ? ( $wc_part_pay->same_shipping_as_billing ? true : false ) : false
		);

		$svea_data["isPayPage"] = is_checkout_pay_page() ? true : false;

		if( is_checkout_pay_page() ) {
			$svea_data["customerCountry"] = WC()->customer->get_billing_country();
		}

		/**
		 * Localize the javascript with Svea data
		 */
		wp_localize_script( 'sveawebpay-js', 'Svea', $svea_data);

		$phrases = array( 
			"no_payment_plans_country_total" => __('There are no available payment plans for this country and order total.', self::PLUGIN_SLUG ),
			"your_address_was_found" => __('Your address was found.', self::PLUGIN_SLUG ),
			"part_payment_plans" => __('Part Payment Plans', self::PLUGIN_SLUG ),
			"company_name" => __('Company Name', self::PLUGIN_SLUG ),
			"invoice_fee" => __('Invoice fee', self::PLUGIN_SLUG ),
			"includes" => __('Includes', self::PLUGIN_SLUG ),
			"vat" => __('VAT', self::PLUGIN_SLUG ),
			"could_not_get_address" => __( 'An error occurred whilst getting your address. Please try again later.', self::PLUGIN_SLUG )
		);

		/**
		 * Localize the javascript with translated phrases
		 */
		wp_localize_script( 'sveawebpay-js', 'Phrases', $phrases );
	}

	/**
 	 * Add the payment Gateways to WooCommerce
 	 *
 	 * @param 	array 	$methods 	associative array with payment gateways
 	 * @return 	array 	associative array with payment gateways
 	 */
	public function woocommerce_add_gateway_svea_gateway( $methods ) {
		$methods[] = 'WC_Gateway_Svea_Card';
		$methods[] = 'WC_Gateway_Svea_Invoice';
		$methods[] = 'WC_Gateway_Svea_Direct_Bank';
		$methods[] = 'WC_Gateway_Svea_Part_Pay';
		return $methods;
	}

	/**
	 * Hook the part payment widget function on the part payment gateway
     *
     * @return void
	 */
	public function product_part_payment_widget() {
	    $wc_gateway_part_pay = WC_Gateway_Svea_Part_Pay::init();

		$product_widget_position = intval( $wc_gateway_part_pay->get_option( 'product_widget_position' ) );

		if( $product_widget_position <= 0 ) {
			$product_widget_position = 11;
		}

		add_action( 'woocommerce_single_product_summary', array( $wc_gateway_part_pay, 'product_part_payment_widget' ), $product_widget_position, 1 );
    }

	/**
	 * Parent function that calls checkout validation handlers depending
	 * on payment gateway
	 *
	 * @return 	void
	 */
	public function checkout_validation_handler() {
		if( ! isset( $_POST["payment_method"] ) )
			return;

		$payment_method = $_POST["payment_method"];

		/**
		 * Use the validation handlers in the gateway-classes depending
		 * on the chosen payment method
		 */
		if( $payment_method === WC_Gateway_Svea_Direct_Bank::GATEWAY_ID )
			WC_Gateway_Svea_Direct_Bank::init()->checkout_validation_handler();
		else if( $payment_method === WC_Gateway_Svea_Invoice::GATEWAY_ID )
			WC_Gateway_Svea_Invoice::init()->checkout_validation_handler();
		else if( $payment_method === WC_Gateway_Svea_Part_Pay::GATEWAY_ID )
			WC_Gateway_Svea_Part_Pay::init()->checkout_validation_handler();
	}

	/**
	 * Sync refunded orders to Svea
	 *
	 * @param 	int 	$order_id 	id of the order being refunded
	 * @return 	void
	 */
	public function sync_refunded_order( $order_id ) {
		$wc_order = new WC_Order( $order_id );

		$svea_order_id = ( $value = get_post_meta( $wc_order->get_id(), "_svea_order_id", true ) ) 
			? $value : wc_get_order_item_meta( $wc_order->get_id(), "svea_order_id" );

        if( ! $svea_order_id || strlen( $svea_order_id ) <= 0 ) {
            return;
        }

        $payment_method_id = $wc_order->get_payment_method();

		$wc_gateway = false;

		if( $payment_method_id === WC_Gateway_Svea_Card::GATEWAY_ID ) {
			$wc_gateway = WC_Gateway_Svea_Card::init();
		} else if( $payment_method_id === WC_Gateway_Svea_Invoice::GATEWAY_ID ) {
			$wc_gateway = WC_Gateway_Svea_Invoice::init();
		} else if( $payment_method_id === WC_Gateway_Svea_Direct_Bank::GATEWAY_ID ) {
			$wc_gateway = WC_Gateway_Svea_Direct_Bank::init();
		}

		if( $wc_gateway !== false
			&& $wc_gateway->get_option( 'disable_order_sync' ) !== 'yes' ) {
			$wc_gateway->credit_order( $wc_order, $svea_order_id );
		}
	}

	/**
	 * Sync cancelled orders to Svea
	 *
	 * @param 	int 	$order_id 	id of the order being cancelled
	 * @return 	void
	 */
	public function sync_cancelled_order( $order_id ) {
		$wc_order = new WC_Order( $order_id );

		$svea_order_id = ( $value = get_post_meta( $wc_order->get_id(), "_svea_order_id", true ) ) 
			? $value : wc_get_order_item_meta( $wc_order->get_id(), "svea_order_id" );

		/**
		 * Determine if this order is a Svea order
		 */
        if( ! $svea_order_id || strlen( $svea_order_id ) <= 0 ) {
            return;
        }

        $payment_method_id = $wc_order->get_payment_method();

        $wc_gateway = false;

        /**
         * Determine if it's a Svea payment method and if which of the payment
         * method it is
         */
		if( $payment_method_id === WC_Gateway_Svea_Card::GATEWAY_ID ) {
			$wc_gateway = WC_Gateway_Svea_Card::init();
		} else if( $payment_method_id === WC_Gateway_Svea_Invoice::GATEWAY_ID ) {
			$wc_gateway = WC_Gateway_Svea_Invoice::init();
		} else if( $payment_method_id === WC_Gateway_Svea_Part_Pay::GATEWAY_ID ) {
			$wc_gateway = WC_Gateway_Svea_Part_Pay::init();
		}

		/**
		 * If current gateway is a Svea gateway and order sync is enabled
		 * sync the order to Svea
		 */
		if( $wc_gateway !== false
			&& $wc_gateway->get_option( 'disable_order_sync' ) !== 'yes' ) {
			$wc_gateway->cancel_order( $wc_order, $svea_order_id );
		}
	}

	/**
	 * Sync delivered orders to Svea
	 *
	 * @param 	int 	$order_id 	id of the order being delivered
	 * @return 	void
	 */
	public function sync_delivered_order( $order_id ) {
		$wc_order = new WC_Order( $order_id );

		$svea_order_id = ( $value = get_post_meta( $wc_order->get_id(), "_svea_order_id", true ) ) 
			? $value : wc_get_order_item_meta( $wc_order->get_id(), "svea_order_id" );

        if( ! $svea_order_id || strlen( $svea_order_id ) <= 0 ) {
            return;
        }

		$payment_method_id = $wc_order->get_payment_method();

		$wc_gateway = false;

		if( $payment_method_id === WC_Gateway_Svea_Card::GATEWAY_ID ) {
			$wc_gateway = WC_Gateway_Svea_Card::init();
		} else if( $payment_method_id === WC_Gateway_Svea_Invoice::GATEWAY_ID ) {
			$wc_gateway = WC_Gateway_Svea_Invoice::init();
		} else if( $payment_method_id === WC_Gateway_Svea_Part_Pay::GATEWAY_ID ) {
			$wc_gateway = WC_Gateway_Svea_Part_Pay::init();
		}

		if( $wc_gateway !== false
			&& $wc_gateway->get_option( 'disable_order_sync' ) !== 'yes' ) {
			$wc_gateway->deliver_order( $wc_order, $svea_order_id );
		}
	}

	/**
	 * Handle bulk admin order actions
	 *
	 * @return 	void
	 */
	public function bulk_admin_order_actions() {
		if( ! isset( $_GET['post'] ) || ! is_array( $_GET['post'] ) )
			return;

		$wp_list_table = _get_list_table('WP_Posts_List_Table');
		$action = $wp_list_table->current_action();

		check_admin_referer( 'bulk-posts' );

		$sendback = admin_url();

		switch( $action ) {
			case 'svea_deliver':

			$orders_delivered = 0;

			foreach( $_GET['post'] as $post_id ) {
				$wc_order = new WC_Order( $post_id );

				if( is_null( $wc_order ) )
					continue;

				$svea_order_id = ( $value = get_post_meta( $wc_order->get_id(), "_svea_order_id", true ) ) 
					? $value : wc_get_order_item_meta( $wc_order->get_id(), "svea_order_id" );

		        if( ! $svea_order_id || strlen( $svea_order_id ) <= 0 ) {
		            continue;
		        }

				$payment_method_id = $wc_order->get_payment_method();

				if( $payment_method_id === WC_Gateway_Svea_Card::GATEWAY_ID ) {
					$response = WC_Gateway_Svea_Card::init()->deliver_order( $wc_order, $svea_order_id );
				} else if( $payment_method_id === WC_Gateway_Svea_Invoice::GATEWAY_ID ) {
					$response = WC_Gateway_Svea_Invoice::init()->deliver_order( $wc_order, $svea_order_id );
				} else if( $payment_method_id === WC_Gateway_Svea_Part_Pay::GATEWAY_ID ) {
					$response = WC_Gateway_Svea_Part_Pay::init()->deliver_order( $wc_order, $svea_order_id );
				} else
					continue;

				if( is_array( $response ) && isset( $response["success"] ) && $response["success"] )
					++$orders_delivered;
			}

			if( $orders_delivered > 0 ) {
				WC_Gateway_Svea_Helper::add_admin_notice(
					sprintf( _n( 'Delivered %s order', 'Delivered %s orders', $orders_delivered, self::PLUGIN_SLUG ), $orders_delivered )
				);
			} else {
				WC_Gateway_Svea_Helper::add_admin_notice(
					__( 'No orders were delivered', self::PLUGIN_SLUG )
				);
			}

			$sendback = admin_url( 'edit.php?post_type=shop_order' );

			break;
			case 'svea_cancel':

			$orders_cancelled = 0;

			foreach( $_GET['post'] as $post_id ) {
				$wc_order = new WC_Order( $post_id );

				if( is_null( $wc_order ) )
					continue;

				$svea_order_id = ( $value = get_post_meta( $wc_order->get_id(), "_svea_order_id", true ) ) 
					? $value : wc_get_order_item_meta( $wc_order->get_id(), "svea_order_id" );

		        if( ! $svea_order_id || strlen( $svea_order_id ) <= 0 ) {
		            continue;
		        }

		        $payment_method_id = $wc_order->get_payment_method();

				if( $payment_method_id === WC_Gateway_Svea_Card::GATEWAY_ID ) {
					$response = WC_Gateway_Svea_Card::init()->cancel_order( $wc_order, $svea_order_id );
				} else if( $payment_method_id === WC_Gateway_Svea_Invoice::GATEWAY_ID ) {
					$response = WC_Gateway_Svea_Invoice::init()->cancel_order( $wc_order, $svea_order_id );
				} else if( $payment_method_id === WC_Gateway_Svea_Part_Pay::GATEWAY_ID ) {
					$response = WC_Gateway_Svea_Part_Pay::init()->cancel_order( $wc_order, $svea_order_id );
				} else
					continue;

				if( is_array( $response ) && isset( $response["success"] ) && $response["success"] )
					++$orders_cancelled;
			}

			if( $orders_cancelled > 0 ) {
				WC_Gateway_Svea_Helper::add_admin_notice(
					sprintf( _n( 'Cancelled %s order', 'Cancelled %s orders', $orders_cancelled, self::PLUGIN_SLUG ), $orders_cancelled )
				);
			} else {
				WC_Gateway_Svea_Helper::add_admin_notice(
					__( 'No orders were cancelled', self::PLUGIN_SLUG )
				);
			}

			$sendback = admin_url( 'edit.php?post_type=shop_order' );

			break;
			case 'svea_credit':

			$orders_credited = 0;

			foreach( $_GET['post'] as $post_id ) {
				$wc_order = new WC_Order( $post_id );

				if( is_null( $wc_order ) )
					continue;

				$svea_order_id = ( $value = get_post_meta( $wc_order->get_id(), "_svea_order_id", true ) ) 
					? $value : wc_get_order_item_meta( $wc_order->get_id(), "svea_order_id" );

		        if( ! $svea_order_id || strlen( $svea_order_id ) <= 0 ) {
		            continue;
		        }

		        $payment_method_id = $wc_order->get_payment_method();

				if( $payment_method_id === WC_Gateway_Svea_Card::GATEWAY_ID ) {
					$response = WC_Gateway_Svea_Card::init()->credit_order( $wc_order, $svea_order_id );
				} else if( $payment_method_id === WC_Gateway_Svea_Invoice::GATEWAY_ID ) {
					$response = WC_Gateway_Svea_Invoice::init()->credit_order( $wc_order, $svea_order_id );
				} else if( $payment_method_id === WC_Gateway_Svea_Direct_Bank::GATEWAY_ID ) {
					$response = WC_Gateway_Svea_Direct_Bank::init()->credit_order( $wc_order, $svea_order_id );
				} else
					continue;

				if( is_array( $response ) && isset( $response["success"] ) && $response["success"] )
					++$orders_credited;
			}

			if( $orders_credited > 0 ) {
				WC_Gateway_Svea_Helper::add_admin_notice(
					sprintf( _n( 'Credited %s order', 'Credited %s orders', $orders_credited, self::PLUGIN_SLUG ), $orders_credited )
				);
			} else {
				WC_Gateway_Svea_Helper::add_admin_notice(
					__( 'No orders were credited', self::PLUGIN_SLUG )
				);
			}

			$sendback = admin_url( 'edit.php?post_type=shop_order' );

			break;
			default: return;
		}

		wp_redirect( $sendback );

		exit;
	}

	/**
	 * Add the bulk actions to the select-box in the shop order view
	 *
	 * @return 	void
	 */
	public function add_order_bulk_actions() {
		global $post_type;

		if( $post_type !== "shop_order" )
			return;
		?>
		<script type="text/javascript">
			jQuery(document).ready(function() {
				var optGroup = '<optgroup label=\'<?php _e( "Svea Webpay Actions", "sveawebpay" ); ?>\'>'
							 + '<option value="svea_deliver"><?php _e( "Deliver order", "sveawebpay" ); ?></option>'
							 + '<option value="svea_credit"><?php _e( "Credit order", "sveawebpay" ); ?></option>'
							 + '<option value="svea_cancel"><?php _e( "Cancel order", "sveawebpay" ); ?></option>'
							 + '</optgroup>';
				jQuery("select[name='action'], select[name='action2']").append(optGroup);
			});
		</script>
		<?php
	}

	/**
	 * Display the svea order id whilst viewing the receipt
	 *
	 * @param 	array 		$total_rows		the table rows in receipt view
	 * @param 	WC_Order 	$order 			the order currently being viewed
	 * @return 	array 		an array of the order rows
	 */
	public function receipt_display_svea_order_id( $total_rows, $order ) {
		$svea_order_id = ( $value = get_post_meta( $order->get_id(), "_svea_order_id", true ) ) 
					? $value : wc_get_order_item_meta( $order->get_id(), "svea_order_id" );

		if( ! $svea_order_id )
			return $total_rows;

		$total_rows['transaction_id'] = array(
			'value'	=> '#' . $svea_order_id,
			'label'	=> __( 'SveaWebPay transaction id: ', self::PLUGIN_SLUG )
		);

		$order_total = $total_rows['order_total'];
		unset( $total_rows['order_total'] );
		$total_rows['order_total'] = $order_total;
		return $total_rows;
	}

	/**
	 * Displays the svea meta box in orders that has a 
	 * svea order id
	 *
	 * @return 	void
	 */
	public function add_admin_functions_meta_box() {
		global $post;

		if( is_null( $post ) ||
			! in_array( $post->post_type, wc_get_order_types( 'order-meta-boxes' ) )
			|| ! isset( $post->ID ) ) {
			return;
		}

		$order = wc_get_order( $post->ID );

		if( ! $order ) {
			return;
		}

		$svea_order_id = ( $value = get_post_meta( $order->get_id(), "_svea_order_id", true ) ) 
					? $value : wc_get_order_item_meta( $order->get_id(), "svea_order_id" );

		if( strlen( $svea_order_id ) <= 0 )
			return;

		$metabox_title = __( 'Svea Webpay Actions', self::PLUGIN_SLUG );
		$metabox_id = 'woocommerce-svea-webpay-admin-functions';

		$payment_method = $order->get_payment_method();

		$wc_gateway = false;

		if( $payment_method === WC_Gateway_Svea_Direct_Bank::GATEWAY_ID ) {
			$wc_gateway = WC_Gateway_Svea_Direct_Bank::init();
		} else if( $payment_method === WC_Gateway_Svea_Card::GATEWAY_ID ) {
			$wc_gateway = WC_Gateway_Svea_Card::init();
		} else if( $payment_method === WC_Gateway_Svea_Invoice::GATEWAY_ID ) {
			$wc_gateway = WC_Gateway_Svea_Invoice::init();
		} else if( $payment_method === WC_Gateway_Svea_Part_Pay::GATEWAY_ID ) {
			$wc_gateway = WC_Gateway_Svea_Part_Pay::init();
		}

		if( $wc_gateway === false
			|| $wc_gateway->get_option( 'disable_order_sync' ) !== 'yes' ) {
			return;
		}

		$metabox_output_function = false;

		if( $payment_method === WC_Gateway_Svea_Direct_Bank::GATEWAY_ID )
			$metabox_output_function = 'WC_Gateway_Svea_Direct_Bank::admin_functions_meta_box';
		else if( $payment_method === WC_Gateway_Svea_Invoice::GATEWAY_ID )
			$metabox_output_function = 'WC_Gateway_Svea_Invoice::admin_functions_meta_box';
		else if( $payment_method === WC_Gateway_Svea_Part_Pay::GATEWAY_ID )
			$metabox_output_function = 'WC_Gateway_Svea_Part_Pay::admin_functions_meta_box';
		else if( $payment_method === WC_Gateway_Svea_Card::GATEWAY_ID )
			$metabox_output_function = 'WC_Gateway_Svea_Card::admin_functions_meta_box';

		if( ! $metabox_output_function )
			return;

		add_meta_box( $metabox_id, $metabox_title, $metabox_output_function, $post->post_type, 'side', 'default' );
	}

	public function hide_order_item_meta( $hidden_meta ) {
		$hidden_meta[] = "svea_order_number";
		$hidden_meta[] = "svea_order_id";
		$hidden_meta[] = "svea_address_selector";

		$hidden_meta[] = "svea_iv_billing_ssn";
		$hidden_meta[] = "svea_iv_billing_customer_type";
		$hidden_meta[] = "svea_iv_billing_org_number";
		$hidden_meta[] = "svea_iv_billing_initials";
		$hidden_meta[] = "svea_iv_billing_vat_number";
		$hidden_meta[] = "svea_iv_birth_date_year";
		$hidden_meta[] = "svea_iv_birth_date_month";
		$hidden_meta[] = "svea_iv_birth_date_day";

		return $hidden_meta;
	}

	/**
	 * Display the svea order id in the backend whilst viewing an order processed
	 * through svea
	 *
	 * @param 	WC_Order 	$order 		the order currently being viewed
	 * @return 	void
	 */
	public function admin_display_svea_order_id( $order )
	{
		$svea_order_id = ( $value = get_post_meta( $order->get_id(), "_svea_order_id", true ) ) 
					? $value : wc_get_order_item_meta( $order->get_id(), "svea_order_id" );

		/**
		 * Only display the svea order id if this order was processed
		 * through svea
		 */ 
		if( ! $svea_order_id || strlen( $svea_order_id ) <= 0 )
			return;
		?>
		<div class="order_data_column">
			<div class="address">
				<p>
					<strong><?php _e('Svea Order Id', self::PLUGIN_SLUG ); ?></strong>
					#<?php echo $svea_order_id; ?>
				</p>
			</div>
		</div>
		<?php
	}
}

new WC_SveaWebPay_Gateway;

endif;