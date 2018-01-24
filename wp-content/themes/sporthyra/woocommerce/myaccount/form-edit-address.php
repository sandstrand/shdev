<?php
/**
 * Edit address form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/form-edit-address.php.
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
 * @version 2.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$page_title = ( $load_address === 'billing' ) ? __( 'Fakturaadress', 'woocommerce' ) : __( 'Leveransadress', 'woocommerce' );

do_action( 'woocommerce_before_edit_account_address_form' ); ?>
<br />
<?php if ( ! $load_address ) : ?>
	<?php wc_get_template( 'myaccount/my-address.php' ); ?>
<?php else : ?>

	<form method="post">
		<div class="form-wrapper">
			<div class="form-content">
				<div class="row">
					<h3><?php echo apply_filters( 'woocommerce_my_account_edit_address_title', $page_title ); ?></h3>

					<?php do_action( "woocommerce_before_edit_address_form_{$load_address}" ); ?>

					<?php foreach ( $address as $key => $field ) : ?>
					<?php
						if($key!='shipping_personnr'){
							woocommerce_form_field( $key, $field,  $field['value'] ); 
						}
						?>
						
					<?php endforeach; ?>
				<?php /*woocommerce_form_field( 'billing_personnr', array(
						'type'          => 'text',
						'class'         => array('my-field-class form-row-wide col-xs-12 col-sm-6'),
						'label'         => __('Personnr'),
						'placeholder'   => __('yyyy-mm-dd'),
					), get_user_meta( get_current_user_id(),  'billing_personnr', true)); */ ?>
					<?php //do_action( "woocommerce_after_edit_address_form_{$load_address}" ); ?>
				</div>
				<div class="row">
					<p class="form-row col-xs-12 col-sm-4 pull-right">
						<input type="submit" class="button" name="save_address" value="<?php esc_attr_e( 'Spara', 'woocommerce' ); ?>" />
						<?php wp_nonce_field( 'woocommerce-edit_address' ); ?>
						<input type="hidden" name="action" value="edit_address" />
					</p>
				</div>
			</div>
		</div>
	</form>

<?php endif; ?>

<?php
	//echo "<pre>";
	//var_export(get_user_meta(get_current_user_id(), 'billing_personnr')[0]);
	//var_export(get_user_meta(get_current_user_id()));
	//echo "</pre>";
?>
<?php do_action( 'woocommerce_after_edit_account_address_form' ); ?>

