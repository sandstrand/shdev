<?php
/**
 * Payment methods
 *
 * Shows customer payment methods on the account page.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/payment-methods.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see 	https://docs.woocommerce.com/document/template-structure/
 * @author  WooThemes
 * @package WooCommerce/Templates
 * @version 2.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$saved_methods = wc_get_customer_saved_methods_list( get_current_user_id() );
$has_methods   = (bool) $saved_methods;
$types         = wc_get_account_payment_methods_types();

do_action( 'woocommerce_before_account_payment_methods', $has_methods ); 

echo "<p>" . __("Här ser du dina sparade kort. Du kan lägga till ytterligare kort nästa gång du beställer något.", "understrap") . "</p><br />";
?>

<?php if ( $has_methods ) : ?>
<div class="form-wrapper">
	<div class="form-content">
		<div class="woocommerce-MyAccount-paymentMethods shop_table shop_table_responsive account-payment-methods-table">
			<div class="row">
					<?php foreach ( wc_get_account_payment_methods_columns() as $column_id => $column_name ) : ?>
						<div class="col-xs-6 woocommerce-PaymentMethod woocommerce-PaymentMethod--<?php echo esc_attr( $column_id ); ?> payment-method-<?php echo esc_attr( $column_id ); ?>"><span class="nobr"><h3><?php echo esc_html( $column_name ); ?></h3></span></div>
					<?php endforeach; ?>
			</div>
			
			<?php foreach ( $saved_methods as $type => $methods ) : ?>
				<?php foreach ( $methods as $method ) : ?>
					<div class="payment-method<?php echo ! empty( $method['is_default'] ) ? ' default-payment-method' : '' ?>">
						<div class="row">
						<?php foreach ( wc_get_account_payment_methods_columns() as $column_id => $column_name ) : ?>
							
								<div class="woocommerce-PaymentMethod woocommerce-PaymentMethod--<?php echo esc_attr( $column_id ); ?> payment-method-<?php echo esc_attr( $column_id ); ?>" data-title="<?php echo esc_attr( $column_name ); ?>">
								<?php
								if ( has_action( 'woocommerce_account_payment_methods_column_' . $column_id ) ) {
									do_action( 'woocommerce_account_payment_methods_column_' . $column_id, $method );
								} else if ( 'method' === $column_id ) {
									if ( ! empty ( $method['method']['last4'] ) ) {
										echo '<span class="col-xs-6">' . sprintf( __( '%s ending in %s', 'woocommerce' ), esc_html( wc_get_credit_card_type_label( $method['method']['brand'] ) ), esc_html( $method['method']['last4'] ) ) . "</span>";
									} else {
										echo esc_html( wc_get_credit_card_type_label( $method['method']['brand'] ) );
									}
								} else if ( 'expires' === $column_id ) {
									echo '<span class="col-xs-6">' . esc_html( $method['expires'] );
									foreach ( $method['actions'] as $key => $action ) {
										echo ' &nbsp;&nbsp;<a href="' . esc_url( $action['url'] ) . '" class="button ' . sanitize_html_class( $key ) . '">' . esc_html( $action['name'] ) . '</a>';
									}
									echo "</span>";
								}
								?>
								</div>
							
						<?php endforeach; ?>
					</div>
					</div>
				<?php endforeach; ?>
			<?php endforeach; ?>
	</div>
</div>		

<?php else : ?>

	<p class="woocommerce-Message woocommerce-Message--info woocommerce-info"><?php esc_html_e( 'No saved methods found.', 'woocommerce' ); ?></p>

<?php endif; ?>

<?php do_action( 'woocommerce_after_account_payment_methods', $has_methods ); ?>


