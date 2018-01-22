<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
/**
 * Handles Callback from Swish
 */
abstract class WC_Gateway_Swish_Ecommerce_Response {
	/**
	 * Get the order from the Swish 'payeePaymentReference' variable
	 *
	 * @param  string $callback_json JSON Data passed back by Swish
	 * @return bool|WC_Order object
	 */
	protected function get_swish_order( $callback_json ) {
		// We have the data in the correct format, so get the order
		if ( ( $callback = json_decode( $callback_json ) ) ) {
			$order_id  = $callback->payeePaymentReference;
			$order_paymentReference  = $callback->paymentReference;
			$order_payment_id = $callback->id;
			$order_status = $callback->status;
			WC_Gateway_Swish_Ecommerce::log( 'Success: Callback recived from Swish-API. Order ' . $order_id ." with " . $order_paymentReference . " is now marked as ". $order_status ." Payment id: " . $order_payment_id);
		// Nothing was found, Callback invalid
		} else {
			WC_Gateway_Swish_Ecommerce::log( 'Error: ID and payeePaymentReference were not found in callback.' );
			return false;
		}
		if ( ! $order = wc_get_order( $order_id ) ) {
			WC_Gateway_Swish_Ecommerce::log( 'Error: Order-id was not found.' );
			//Use alternate way to get order id
			///$order_id = wc_get_order_id_by_order_key( $order_key );
			$order    = wc_get_order( $order_id );
		}
		if ( ! $order ) {
			WC_Gateway_Swish_Ecommerce::log( 'Error: Order not found.' );
			return false;
		}
		return $order;
	}
	
	/**
	 * Get the order from the Swish 'payeePaymentReference' variable
	 *
	 * @param  string $callback_json JSON Data passed back by Swish
	 * @return bool|WC_Order object
	 */
	protected function get_swish_refund_order( $callback_json ) {
		// We have the data in the correct format, so get the order
		if ( ( $callback = json_decode( $callback_json ) ) ) {
			$order_id  = $callback->payerPaymentReference;
			if(empty($order_id)){
				global $wpdb;
				$sql = "select post_id from " . $wpdb->prefix . "postmeta where
					meta_key = '_swish_payment_reference' &&
					meta_value like '%%%s%%'";

				$sql = $wpdb->prepare( $sql, $callback->originalPaymentReference );
				$res = $wpdb->get_var( $sql );
				$order_id = $res;
			}
			$order_originalPaymentReference  = $callback->originalPaymentReference;
			$order_refund_id = $callback->id;
			$refund_status = $callback->status;
			WC_Gateway_Swish_Ecommerce::log( 'Success:Refund Callback recived from Swish-API. Order ' . $order_id ." that had payementReference " . $order_originalPaymentReference . " is now refunded and the refund is now marked as ". $refund_status ." Refund id: ".$order_refund_id);
		// Nothing was found, Callback invalid
		} else {
			WC_Gateway_Swish_Ecommerce::log( 'Error: ID and originalPaymentReference were not found in callback.' );
			return false;
		}
		
		if ( ! $order = wc_get_order( $order_id ) ) {
			//Use alternate way to get order id
			//$order_id = wc_get_order_id_by_order_key( $order_key );
			$order    = wc_get_order( $order_id );
		}
		
		if ( ! $order ) {
			WC_Gateway_Swish_Ecommerce::log( 'Error: Order not found.' );
			return false;
		}
		
		return $order;
	}


	/**
	 * Complete order, add transaction ID and note
	 * @param  WC_Order $order
	 * @param  string $txn_id
	 * @param  string $note
	 */
	protected function payment_complete( $order, $txn_id = '', $note = '' ) {
		$order->add_order_note( $note );
		$order->payment_complete();
	}

	/**
	 * Hold order and add note
	 * @param  WC_Order $order
	 * @param  string $reason
	 */
	protected function payment_on_hold( $order, $reason = '' ) {
		$order->set_status( 'on-hold', $reason );
		$order->reduce_order_stock();
		$order->save();
		WC()->cart->empty_cart();
	}
}