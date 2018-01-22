<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
include_once( 'class-wc-gateway-swish-ecommerce-api-response.php' );
/**
 * Handles responses from Swish API
 */
class WC_Gateway_Swish_Ecommerce_API_Handler extends WC_Gateway_Swish_Ecommerce_Response {
	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'woocommerce_api_wc_gateway_swish_ecommerce', array( $this, 'check_response' ) );
		add_action( 'valid-swish-ecommerce-paymentrequest-callback', array( $this, 'valid_payment_request' ) );
		add_action( 'valid-swish-ecommerce-refund-callback', array( $this, 'valid_swish_refund' ) );
	}
	/**
	 * Check for Swish API Response
	 */
	public function check_response() {
		if ( ! empty( $entityBody = file_get_contents('php://input') ) ) {
			$posted = wp_unslash( $entityBody );
			$postedArray = json_decode($posted);
			WC_Gateway_Swish_Ecommerce::log( 'This our our callback data:'. $posted);
			WC_Gateway_Swish_Ecommerce::log( 'Callback is not emtpy, payment request or refund?');
			
			if(empty($postedArray->originalPaymentReference)){
			WC_Gateway_Swish_Ecommerce::log( 'Callback is paymentrequest. trigger paymentrequest function, valid_payment_request');
				do_action( "valid-swish-ecommerce-paymentrequest-callback", $posted );
				exit;			
			}
			if(!empty($postedArray->originalPaymentReference)){
				WC_Gateway_Swish_Ecommerce::log( 'Callback is refund. trigger refund function, valid_swish_refund');
				do_action( "valid-swish-ecommerce-refund-callback", $posted );
				exit;
			}			
		}
		wp_die( "Swish API Request Failure", "Swish API", array( 'response' => 500 ) );
	}

	/**
	 * There was a valid response
	 * @param  array $posted Post data after wp_unslash
	 */
	public function valid_payment_request( $posted ) {
		if ( ! empty( $posted ) && ( $order = $this->get_swish_order( $posted ) ) ) {
			$posted = json_decode($posted,true);
			// Lowercase returned variables
			$posted['status'] = strtolower( $posted['status'] );
			WC_Gateway_Swish_Ecommerce::log( 'Found order #' . $order->get_id() );
			WC_Gateway_Swish_Ecommerce::log( 'Payment status: ' . $posted['status'] );
			if ( method_exists( $this, 'payment_status_' . $posted['status'] ) ) {
				call_user_func( array( $this, 'payment_status_' . $posted['status'] ), $order, $posted );
			}
		}
	}
	/**
	* There was a valid response
	* @param  array $posted Post data after wp_unslash
	*/
	public function valid_swish_refund( $posted ) {
		if ( ! empty( $posted ) && ( $order = $this->get_swish_refund_order( $posted ) ) ) {
			$posted = json_decode($posted,true);
			//Lowercase returned variables
			$posted['status'] = strtolower( $posted['status'] );
			WC_Gateway_Swish_Ecommerce::log( 'Found order #' . $order->get_id() );
			WC_Gateway_Swish_Ecommerce::log( 'Refund status: ' . $posted['status'] );
			if ( method_exists( $this, 'refund_status_' . $posted['status'] ) ) {
				call_user_func( array( $this, 'refund_status_' . $posted['status'] ), $order, $posted );
			}
		}
	}
	/**
	 * Handle a completed payment
	 * @param  WC_Order $order
	 */
	protected function payment_status_paid( $order, $posted ) {
		if ( $order->has_status( 'completed' ) ) {
			WC_Gateway_Swish_Ecommerce::log( 'Aborting, Order #' . $order->get_id() . ' is already complete.' );
			exit;
		}
		$this->save_swish_ecommerce_meta_data( $order, $posted );
		if ( 'paid' === $posted['status'] ) {
			WC_Gateway_Swish_Ecommerce::log( 'Setting order ' . $order->get_id() . ' as completed.' );
			// Reduce stock levels
			//WC_Gateway_Swish_Ecommerce::log( 'Reducing stock' );
			//$order->reduce_order_stock();
			$this->payment_complete( $order, ( ! empty( $posted['payeePaymentReference'] ) ? wc_clean( $posted['payeePaymentReference'] ) : '' ), __( 'Swish API payment completed', 'woocommerce-gateway-swish-ecommerce' ) );
		}
	}
	/**	 * Handle a completed refund
	* @param  WC_Order $order
	*/	
	protected function refund_status_debited( $order, $posted ) {
		if ( $order->has_status( 'refunded' ) ) {
			WC_Gateway_Swish_Ecommerce::log( 'Aborting, Order #' . $order->get_id() . ' is already refunded.' );
			exit;
		}
		$this->save_swish_ecommerce_meta_data( $order, $posted );
		if ( 'debited' === $posted['status'] ) {
			WC_Gateway_Swish_Ecommerce::log( 'Order ' . $order->get_id() . ' is now refunded.' );	
			$order->add_order_note( 'Order ' . $order->get_id() . ' is now refunded and marked as debited' );
		}
	}
	/**
	 * Handle a failed payment
	 * @param  WC_Order $order
	 */
	protected function payment_status_declined( $order, $posted ) {
		if ( $order->has_status( 'pending' ) ) {
			$order->set_status( 'failed', sprintf( __( 'Error from Swish API. Payment declined.', 'woocommerce-gateway-swish-ecommerce' ), wc_clean( $posted['status'] ) ) );
		}else{
			$order->add_order_note( sprintf( __( 'Error from Swish API. Payment declined.', 'woocommerce-gateway-swish-ecommerce' ), wc_clean( $posted['status'] ) ) );
		}
		$order->save();
		WC_Gateway_Swish_Ecommerce::log( 'Payment for order #' . $order->get_id() . ' was declined.' );
	}
	/**
	 * Handle a failed payment
	 * @param  WC_Order $order
	 */
	protected function payment_status_error( $order, $posted ) {
		if ( $order->has_status( 'pending' ) ) {
			$order->set_status( 'failed', sprintf( __( 'Error from Swish API. %s: %s.', 'woocommerce-gateway-swish-ecommerce' ), wc_clean( $posted['errorCode'] ),wc_clean( $posted['errorMessage'] ) ) );
		}else{
			$order->add_order_note( sprintf( __( 'Error from Swish API. %s: %s.', 'woocommerce-gateway-swish-ecommerce' ), wc_clean( $posted['errorCode'] ),wc_clean( $posted['errorMessage'] ) ) );
		}
		$order->save();
		WC_Gateway_Swish_Ecommerce::log( 'Payment for order #' . $order->get_id() . ' was declined.' . wc_clean( $posted['errorCode'] ) .": " .wc_clean( $posted['errorMessage'] ));
	}
	/**
	 * Save important data from the API-Callback to the order
	 * @param WC_Order $order
	 */
	protected function save_swish_ecommerce_meta_data( $order, $posted ) {
		if ( ! empty( $posted['paymentReference'] ) ) {
			WC_Gateway_Swish_Ecommerce::log( 'updating post meta "_swish_payment_reference" with:' .wc_clean( $posted['paymentReference'] ));
			add_post_meta( $order->get_id(), '_swish_payment_reference', wc_clean( $posted['paymentReference'] ) );
			WC_Gateway_Swish_Ecommerce::log( 'updating post meta "_swish_payment_status" with:' .wc_clean( $posted['status'] ));
			add_post_meta( $order->get_id(), '_swish_payment_status', wc_clean( $posted['status'] ) );
		}
		if ( ! empty( $posted['originalPaymentReference'] ) ) {
			WC_Gateway_Swish_Ecommerce::log( 'updating post meta "_swish_refund_id" with:' .wc_clean( $posted['id'] ));
			add_post_meta( $order->get_id(), '_swish_refund_id', wc_clean( $posted['id'] ) );
			WC_Gateway_Swish_Ecommerce::log( 'updating post meta "_swish_refund_status" with:' .wc_clean( $posted['status'] ));
			add_post_meta( $order->get_id(), '_swish_refund_status', wc_clean( $posted['status'] ) );
		}
	}
}
