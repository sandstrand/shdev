<?php
class WC_Gateway_Swish_Ecommerce_Popup{

	function __construct(){

		add_action( 'wp_print_scripts', array($this, 'wpdocs_enqueue_script'), 100 );
		add_action( 'woocommerce_before_checkout_form', array( $this, 'wc_checkout_popup') );
		add_action( 'wp_ajax_swish_ajax_payment_check', array($this, 'checkPaymentFromAjax') );
		add_action( 'wp_ajax_nopriv_swish_ajax_payment_check', array($this, 'checkPaymentFromAjax') );

	}

	public function wpdocs_enqueue_script() {
		if ( ! is_checkout() ) {
			return;
		}

		if ( is_order_received_page() ) {
			return;
		}
		
		wp_register_style(
			'swish-checkout',
			plugins_url( 'assets/css/checkout.css', REDLIGHT_SA_MAIN_FILE )
		);

		wp_register_script( 
			'redlight-swish_ecommerce-checkout', 
			plugins_url( 'assets/js/checkout.js', REDLIGHT_SA_MAIN_FILE ),
			array( 'jquery', 'woocommerce', 'wc-country-select', 'wc-address-i18n' ),
			REDLIGHT_SA_VERSION 
		);

		if(isset($_GET['swish_order_id']) ){
			$swish_order_id = $_GET['swish_order_id'];
		}else{
			$swish_order_id = 0;
		}
		$checkout_localize_params = array(
			'swish_ajax' 							=> admin_url( 'admin-ajax.php' ),
			'swish_shop_url' 						=> wc_get_checkout_url(),
			'swish_order_id' 						=> $swish_order_id,
			'redirect_message' 						=> __('We will redirect you in', 'woocommerce-gateway-swish-ecommerce'),
			'seconds' 								=> __('seconds', 'woocommerce-gateway-swish-ecommerce'),
			'payment_failed' 						=> __('Payment failed', 'woocommerce-gateway-swish-ecommerce'),
			'return_to_checkout'					=> __('Return to checkout', 'woocommerce-gateway-swish-ecommerce'),
			'payment_succesful'						=> __('We have recived your payment', 'woocommerce-gateway-swish-ecommerce'),
			'succesful_payment_redirect_message' 	=> __('We will take you to the order-conformation page, hang tight', 'woocommerce-gateway-swish-ecommerce')
		);
		wp_localize_script('redlight-swish_ecommerce-checkout', 'redlight_script_vars', $checkout_localize_params);
		wp_enqueue_script( 'redlight-swish_ecommerce-checkout' );
		wp_enqueue_style( 'swish-checkout' );
		
	}

	public function wc_checkout_popup(){

		if(!isset($_GET['swish_order_id']) ){
			return;
		}
		
		$order_id = $_GET['swish_order_id'];
		$order = wc_get_order( $order_id );
		if( $order->get_meta('_swish_payer_alias') !== null ){
			$payer_alias = $order->get_meta('_swish_payer_alias', true);
		}else{
			$payer_alias = $order->get_billing_phone();
		}
		?>
		<style type="text/css">
			.woocommerce-error{display: none;}
		</style>
		<section id="redlight-swish-popup_wraper" style="display: none;">
			<div class="redlight-swish-popup_wraper" align="center">
				<div class="redlight-swish-popup_content_wrap">
					<div class="swish_logo rotate">
						<img width="135" height="135" src="<?php echo plugins_url('assets/images/swish_logo_image.png', REDLIGHT_SA_MAIN_FILE); ?>"/>
					</div>
					<div class="swish_logo">
						<img width="180"  src="<?php echo plugins_url('assets/images/swish_logo_text.png', REDLIGHT_SA_MAIN_FILE); ?>"/>
					</div>
					<div class="swish-button">
						<a class="button openSwish" onclick="window.location.href='swish://';"  href="swish://"><?php echo __('Open Swish-app', 'woocommerce-gateway-swish-ecommerce');?></a>
						<p><small><?php echo __('If the button doesnt work, you can always open the swish-app manually', 'woocommerce-gateway-swish-ecommerce');?></small></p>
					</div>
					<div class="redlight-swish-popup_message">
						<p><?php echo sprintf(__('We are currently awaiting your payment, please proceed to open your Swish-app connected to <strong>%s</strong> and finalize your payment.', 'woocommerce-gateway-swish-ecommerce'), $payer_alias ); ?></p>
					</div>
				</div>
			</div>
		</section>

		<?php

	}

	public function checkPaymentFromAjax(){

		if($_POST['order_id']){
			$order_id = $_POST['order_id'];
			$order_id = (int) $order_id;
			$wc_gateway = new WC_Gateway_Swish_Ecommerce();
			$wc_gateway->get_process_payment($order_id);
		}else{
			$order_details = array(
				'headers'=>array(),
				'body'=>array(
					'status'=>'unpaid',
					),
				'redlight_swishecommerce_success_array'=>array(
					'result'=>'session_timeout',
					'redirect'=>false,
					'order_id'=>false,
					),
				);
			echo json_encode($order_details);
		}
		die();
	}


	function __destruct(){

	}
}
