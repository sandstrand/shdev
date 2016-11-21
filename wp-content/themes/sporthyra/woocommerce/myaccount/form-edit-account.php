<?php
/**
 * Edit account form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/form-edit-account.php.
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

do_action( 'woocommerce_before_edit_account_form' ); ?>
<p> <?=__("Uppdatera dina kontouppgifter nedan. Du kan också byta lösenord.", "understrap");?> </p><br />
<form class="woocommerce-EditAccountForm edit-account" action="" method="post">
	<div class="form-wrapper">
		<div class="form-content">
			<?php do_action( 'woocommerce_edit_account_form_start' ); ?>
			<h3><?=__('Kontouppgifter', "understrap");?></h3>
			<div class="row">
				<p class="col-sm-6 col-xs-12 woocommerce-FormRow woocommerce-FormRow--first  form-row form-row-first">
					<label for="account_first_name"><?php _e( 'First name', 'woocommerce' ); ?> <span class="required">*</span></label>
					<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="account_first_name" id="account_first_name" value="<?php echo esc_attr( $user->first_name ); ?>" />
				</p>
				<p class="col-sm-6 col-xs-12woocommerce-FormRow woocommerce-FormRow--last form-row form-row-last">
					<label for="account_last_name"><?php _e( 'Last name', 'woocommerce' ); ?> <span class="required">*</span></label>
					<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="account_last_name" id="account_last_name" value="<?php echo esc_attr( $user->last_name ); ?>" />
				</p>
				<div class="clear"></div>

				<p class="col-sm-12 col-xs-12 woocommerce-FormRow woocommerce-FormRow--wide form-row form-row-wide">
					<label for="account_email"><?php _e( 'Email address', 'woocommerce' ); ?> <span class="required">*</span></label>
					<input type="email" class="woocommerce-Input woocommerce-Input--email input-text" name="account_email" id="account_email" value="<?php echo esc_attr( $user->user_email ); ?>" />
				</p>
			</div>
			<br /><h3><?=__('Byt lösenord', "understrap");?></h3>
			<div class="row">			
				<p class="col-sm-6 col-xs-12 woocommerce-FormRow woocommerce-FormRow--wide form-row form-row-wide">
					<label for="password_current"><?php _e( 'Current Password (leave blank to leave unchanged)', 'woocommerce' ); ?></label>
					<input type="password" class="woocommerce-Input woocommerce-Input--password input-text" name="password_current" id="password_current" />
				</p>
			</div>
			<div class="row">
				<p class="col-sm-6 col-xs-12 woocommerce-FormRow woocommerce-FormRow--wide form-row form-row-wide">
					<label for="password_1"><?php _e( 'New Password (leave blank to leave unchanged)', 'woocommerce' ); ?></label>
					<input type="password" class="woocommerce-Input woocommerce-Input--password input-text" name="password_1" id="password_1" />
				</p>
				<div class="col-sm-6 col-xs-12 woocommerce-FormRow woocommerce-FormRow--wide form-row form-row-wide">
					<label for="password_2" class="extra_height"><?php _e( 'Confirm New Password', 'woocommerce' ); ?></label>
					<input type="password" class="woocommerce-Input woocommerce-Input--password input-text" name="password_2" id="password_2" />
				</div>
			</div>
		
			<div class="clear"></div>

			<?php do_action( 'woocommerce_edit_account_form' ); ?>
			
			<div class="row">
			<p class="col-sm-4 col-xs-12 pull-right" >
				<?php wp_nonce_field( 'save_account_details' ); ?>
				<input type="submit" class="woocommerce-Button button" name="save_account_details" value="<?php esc_attr_e( 'Save changes', 'woocommerce' ); ?>" />
				<input type="hidden" name="action" value="save_account_details" />
			</p>
			</div>
			<?php do_action( 'woocommerce_edit_account_form_end' ); ?>
		</div>
	</div>

</form>

<?php do_action( 'woocommerce_after_edit_account_form' ); ?>
