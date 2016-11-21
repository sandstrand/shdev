<?php
/**
 * Checkout shipping information form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-shipping.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @author  WooThemes
 * @package WooCommerce/Templates
 * @version 2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>

<div class="woocommerce-shipping-fields">
<?php if ( true === WC()->cart->needs_shipping_address() ) : ?>
			<div class='form-content form-content-margin'><span id="ship-to-different-address">
				<label for="ship-to-different-address-checkbox" class="checkbox"><?php _e( 'Ship to a different address?', 'woocommerce' ); ?></label>
				<input id="ship-to-different-address-checkbox" class="input-checkbox" <?php checked( apply_filters( 'woocommerce_ship_to_different_address_checked', 'shipping' === get_option( 'woocommerce_ship_to_destination' ) ? 1 : 0 ), 1 ); ?> type="checkbox" name="ship_to_different_address" value="1" />
			</div></span>
					<div class="shipping_address">
						<div id="shipping_address" class='form-wrapper'>
							
							<div class="form-content">
								<h3><?=__('Var ska vi skicka din beställning?', 'understrap');?></h3>
								<div class="row">


							<?php do_action( 'woocommerce_before_checkout_shipping_form', $checkout ); ?>

							<?php foreach ( $checkout->checkout_fields['shipping'] as $key => $field ) : ?>

								<?php woocommerce_form_field( $key, $field, $checkout->get_value( $key ) ); ?>
							<?php endforeach; ?>
								</div>
							</div>
						</div>
					</div>
<?php endif; ?>
</div>
<div class='form-wrapper'>
	
		<div class="woocommerce-shipping-details form-content">
		<?php //do_action( 'woocommerce_before_order_notes', $checkout ); ?>
		<?php 
			if(rvlvr_rent_in_cart()){
				echo "<h3>" . __( 'När vill du ha din utrustning?', 'woocommerce' ) . "</h3>"; 
			}
			else{
				echo "<h3>" . __( 'Orderdetaljer', 'woocommerce' ) . "</h3>";
			}
		?>
		<?php if ( apply_filters( 'woocommerce_enable_order_notes_field', get_option( 'woocommerce_enable_order_comments', 'yes' ) === 'yes' ) ) : ?>

			<div class="row">
			<?php do_action( 'woocommerce_after_checkout_shipping_form', $checkout ); ?>
			<?php do_action( 'woocommerce_before_order_notes', $checkout ); ?>
			<?php foreach ( $checkout->checkout_fields['order'] as $key => $field ) : ?>

				<?php woocommerce_form_field( $key, $field, $checkout->get_value( $key ) ); ?>

			<?php endforeach; ?>
			</div>
		<?php endif; ?>
		
		<?php // End point for js output for deliver return info
		echo '<div class="order_delivery_notes  rvlvr_hidden">';
			echo "<h3>" . __( 'Returdatum', 'woocommerce' ) . "</h3>"; 
			echo "<div class='order_delivery_note'></div>";
		echo '</div>';
		?>
		
		<?php // Values for js to fetch to output seasonal return dates
		$args = array(
			'post_type'             => 'season',
			'post_status'           => 'publish',
		);
		$seasons = get_posts($args);
		//var_export($seasons);
		foreach ($seasons as $season){
			echo "<div id='" . $season->post_name . "' class='rvlvr_hidden'>" . get_post_meta($season->ID, 'rvlvr_season_expires', true) . "</div>";	
		}

		?>
		
		<?php do_action( 'woocommerce_after_order_notes', $checkout ); ?>
	</div>
</div>