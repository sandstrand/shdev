<?php
class WC_Gateway_Swish_Ecommerce_Popup{

	function __construct(){

		add_action( 'wp_print_scripts', array($this, 'wpdocs_enqueue_script'), 100 );
		//add_action( 'wp_print_scripts', array($this, 'wpdocs_enqueue_script'), 100 );

		add_action( 'woocommerce_before_checkout_form', array( $this, 'wc_checkout_popup') );
		//add_action( 'woocommerce_checkout_after_order_review', array( $this, 'wc_checkout_popup') );

		/*Check Payment From Ajax*/
		add_action( 'wp_ajax_swish_ajax_payment_check', array($this, 'checkPaymentFromAjax') );
		add_action( 'wp_ajax_nopriv_swish_ajax_payment_check', array($this, 'checkPaymentFromAjax') );

	}

	function wpdocs_enqueue_script() {

		wp_enqueue_style( 'swish-checkout', plugins_url('/woocommerce-gateway-swish-ecommerce/assets/css/checkout.css' ) );

		wp_enqueue_script( 
			'redlight-swish_ecommerce-checkout', 
			plugins_url('/woocommerce-gateway-swish-ecommerce/assets/js/checkout.js' ),
			array( 'jquery', 'woocommerce', 'wc-country-select', 'wc-address-i18n' ),
			WC_VERSION 
		);

		if(isset($_GET['swish_order_id']) ){
			$swish_order_id = $_GET['swish_order_id'];
		}else{
			$swish_order_id = 0;
		}

		wp_localize_script('redlight-swish_ecommerce-checkout', 'redlight_script_vars', array(
			'swish_ajax' => admin_url( 'admin-ajax.php' ),
			'swish_shop_url' => get_permalink( wc_get_page_id( 'checkout' ) ),
			'swish_order_id' => $swish_order_id,
			'redirect_message' => __('We will redirect you in', 'woocommerce-gateway-swish-ecommerce'),
			'seconds' => __('seconds', 'woocommerce-gateway-swish-ecommerce'),
			'payment_failed' => __('Payment failed', 'woocommerce-gateway-swish-ecommerce'),
			'return_to_checkout' => __('Return to checkout', 'woocommerce-gateway-swish-ecommerce'),
			'payment_succesful' => __('We have recived your payment', 'woocommerce-gateway-swish-ecommerce'),
			'succesful_payment_redirect_message' => __('We will take you to the order-conformation page, hang tight', 'woocommerce-gateway-swish-ecommerce')
		));



		//add_action( 'template_redirect', array($this, 'wc_custom_redirect_after_purchase') );


	}

	function wc_custom_redirect_after_purchase() {

		global $wp;

		if ( is_checkout() && ! empty( $wp->query_vars['order-received'] ) ) {
				//wp_redirect( 'http://www.yoururl.com/your-page/' );
			exit;
		}
	}

	function wc_checkout_popup(){

		if(!isset($_GET['swish_order_id']) ){
			return;
		}
		$order_id = $_GET['swish_order_id'];
		$order = wc_get_order( $order_id );
		?>
		<style type="text/css">
			.woocommerce-error{display: none;}
		</style>
		<section id="redlight-swish-popup_wraper" style="display: none;">
			<div class="redlight-swish-popup_wraper" align="center">
				<div class="redlight-swish-popup_content_wrap">
					<div class="swish_logo rotate">
						<img width="135" height="135" src="<?php echo plugins_url(); ?>/woocommerce-gateway-swish-ecommerce/assets/images/swish_logo_image.png"/>
					</div>
					<div class="swish_logo">
						<img width="180"  src="<?php echo plugins_url(); ?>/woocommerce-gateway-swish-ecommerce/assets/images/swish_logo_text.png"/>
					</div>
					<div class="swish-button">
						<a class="button openSwish" onclick="window.location.href='swish://';"  href="swish://"><?php echo __('Open Swish-app', 'woocommerce-gateway-swish-ecommerce');?></a>
						<p><small><?php echo __('If the button doesnt work, you can always open the swish-app manually', 'woocommerce-gateway-swish-ecommerce');?></small></p>
					</div>
					<!-- <div class="swish_loading">
						<img width="64" height="64" src="<?php echo plugins_url(); ?>/woocommerce-gateway-swish-ecommerce/assets/images/loading.gif"/>
					</div> -->
					<div class="redlight-swish-popup_message">
						<p><?php echo sprintf(__('We are currently awaiting your payment, please proceed to open your Swish-app connected to <strong>%s</strong> and finalize your payment.', 'woocommerce-gateway-swish-ecommerce'), $order->get_billing_phone()); ?></p>
					</div>
					<!-- <a href="#" class="button">Slutfor kop</a> -->
				</div>
			</div>
		</section>

		<?php

	}

	function checkPaymentFromAjax(){

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