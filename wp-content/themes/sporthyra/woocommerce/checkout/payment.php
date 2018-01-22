<?php
/**
 * Checkout Payment Section
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/payment.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see 	    https://docs.woocommerce.com/document/template-structure/
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     2.5.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! is_ajax() ) {
	do_action( 'woocommerce_review_order_before_payment' );
}
?>
<div id="payment" class="woocommerce-checkout-payment">
	<div class="form-wrapper"><div class="form-content">
		<ul class="wc_payment_methods payment_methods methods"> 
			<h3><?=__('Slutför beställning', 'understrap');?></h3>
			
				<?php if ( WC()->cart->needs_payment() ) : ?>
						
							<?php
								if ( ! empty( $available_gateways ) ) {
									foreach ( $available_gateways as $gateway ) {
										wc_get_template( 'checkout/payment-method.php', array( 'gateway' => $gateway ) );
									}
								} else {
									echo '<li>' . apply_filters( 'woocommerce_no_available_payment_methods_message', WC()->customer->get_country() ? __( 'Sorry, it seems that there are no available payment methods for your state. Please contact us if you require assistance or wish to make alternate arrangements.', 'woocommerce' ) : __( 'Please fill in your details above to see available payment methods.', 'woocommerce' ) ) . '</li>';
								}
							?>
					
					<?php endif; ?>
					
					<div class="form-row place-order">
						<noscript>
							<?php _e( 'Since your browser does not support JavaScript, or it is disabled, please ensure you click the <em>Update Totals</em> button before placing your order. You may be charged more than the amount stated above if you fail to do so.', 'woocommerce' ); ?>
							<br/><input type="submit" class="button alt" name="woocommerce_checkout_update_totals" value="<?php esc_attr_e( 'Update totals', 'woocommerce' ); ?>" />
						</noscript>

						

						<?php do_action( 'woocommerce_review_order_before_submit' ); ?>
					
						<?php do_action( 'woocommerce_review_order_after_submit' ); ?>

						<?php wp_nonce_field( 'woocommerce-process_checkout' ); ?>
					</div>
				<div class="row">	
				<div class='col-xs-12 billing_disclaimer' style='display:none; margin-top:5px; margin-bottom:10px;'><p>Obs! Med dena betalningsmetod kommer alltid uppgifter att hämtas från folkbokföringsregistret.</p></div>
				<div class="col-xs-12"><?php wc_get_template( 'checkout/terms.php' ); ?></div>
				<div class="col-xs-12 col-sm-4 pull-right">
					<?php echo apply_filters( 'woocommerce_order_button_html', '<button name="woocommerce_checkout_place_order" id="place_order"><i class="fa fa-lock" aria-hidden="true"></i> ' . esc_attr( $order_button_text ) . '</button>'); ?>
				</div>
				</div>
			
		</div>
			</ul>
	</div>
</div>
<?php
if ( ! is_ajax() ) {
	do_action( 'woocommerce_review_order_after_payment' );
}
