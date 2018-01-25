<?php
/**
 * Checkout billing information form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-billing.php.
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
 * @version 2.1.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/** @global WC_Checkout $checkout */

?>

<div class='form-wrapper'>
<div class="woocommerce-billing-fields form-content">
		
	<?php 
		if ( !is_user_logged_in() || !rvlvr_customer_has_billing_fields() ){ echo "<h3>" . __( 'Dina uppgifter', 'woocommerce' ) . "</h3>"; } else { echo "<h3>" . __('Du är inloggad som ', 'understrap') . get_user_meta(get_current_user_id(), 'billing_first_name', true ) . " " .get_user_meta( get_current_user_id(), 'billing_last_name', true ) . "</h3>";}
			
			if ( ! rvlvr_customer_has_billing_fields() || !is_user_logged_in() ){ $billing_class = ''; } else { $billing_class='logged_in_hidden'; } ?>
			<div class="row <?=$billing_class;?>">
				<?php do_action( 'woocommerce_before_checkout_billing_form', $checkout ); ?>

				<?php foreach ( $checkout->checkout_fields['billing'] as $key => $field ) : ?>

					<?php woocommerce_form_field( $key, $field, $checkout->get_value( $key ) ); ?>

				<?php endforeach; ?>

				<?php do_action('woocommerce_after_checkout_billing_form', $checkout ); ?>

				<?php if ( ! is_user_logged_in() && $checkout->enable_signup ) : ?>

					<?php if ( $checkout->enable_guest_checkout ) : ?>

						<p class="form-row form-row-wide create-account">
							<input class="input-checkbox" id="createaccount" <?php checked( ( true === $checkout->get_value( 'createaccount' ) || ( true === apply_filters( 'woocommerce_create_account_default_checked', false ) ) ), true) ?> type="checkbox" name="createaccount" value="1" /> <label for="createaccount" class="checkbox"><?php _e( 'Create an account?', 'woocommerce' ); ?></label>
						</p>

					<?php endif; ?>

					<?php do_action( 'woocommerce_before_checkout_registration_form', $checkout ); ?>

					<?php if ( ! empty( $checkout->checkout_fields['account'] ) ) : ?>

						<div class="create-account">

							<p class="col-xs-12"><?php _e( 'Ett konto kommer att skapas åt dig. Om du är en återvändande kund så loggar du in på toppen av den här sidan', 'woocommerce' ); ?></p>

							<?php foreach ( $checkout->checkout_fields['account'] as $key => $field ) : ?>

								<?php woocommerce_form_field( $key, $field, $checkout->get_value( $key ) ); ?>

							<?php endforeach; ?>

							<div class="clear"></div>

						</div>

					<?php endif; ?>

					<?php do_action( 'woocommerce_after_checkout_registration_form', $checkout ); ?>

				<?php endif; ?>	
					
		</div>
		<?php 
		
		if ( rvlvr_customer_has_billing_fields() && is_user_logged_in() ){
			
			echo "<div style='margin-bottom:5px;' class='row saved_billing'>";
			//var_export(rvlvr_get_customer_billing_fields());
			foreach(rvlvr_get_customer_billing_fields() as $key){
				echo "<div class='col-xs-12 " .  $key['class'] . "'><span class='billing_label'>" . $key['title'] . ": </span><span class='billing_detail'>" . $key['value'] . "</span></div>";
			}
			echo "<div class='col-xs-12'><p style='margin-top:5px;' ><a class='update_billing' href=" . "#" . ">Uppdatera uppgifter</a></p></div>";
			echo "</div>";
			
			
			echo "Är det här inte du? <a href=" . wp_logout_url( get_permalink()) . ">Logga ut</a> för att byta eller skapa en ny användare.";
		}	

	?>
 
</div>
</div>
<?php  ?>
