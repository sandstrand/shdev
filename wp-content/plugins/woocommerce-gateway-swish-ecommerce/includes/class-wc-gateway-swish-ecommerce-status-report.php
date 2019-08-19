<?php
/**
 * WooCommerce status page extension
 *
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
class WC_Gateway_Swish_Ecommerce_Status_Report {
	public function __construct() {
		add_action( 'woocommerce_system_status_report', array( $this, 'add_status_page_box' ) );
	}
	public function add_status_page_box() {
		include_once( REDLIGHT_SA_PLUGIN_PATH . '/includes/wc-gateway-swish-ecommerce-status-report.php' );
	}
}
$WC_Gateway_Swish_Ecommerce_Status_Report = new WC_Gateway_Swish_Ecommerce_Status_Report();