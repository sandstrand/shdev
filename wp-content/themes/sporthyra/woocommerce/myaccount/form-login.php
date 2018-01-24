<?php
/**
 * Login Form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/form-login.php.
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
	exit; // Exit if accessed directly
}

?>

<?php wc_print_notices(); ?>

<?php do_action( 'woocommerce_before_customer_login_form' ); ?>

<?php if ( get_option( 'woocommerce_enable_myaccount_registration' ) === 'yes' ) : ?>

<div class="u-columns col2-set" id="customer_login">

	

<?php endif; ?>
	<div class="u-column1 col-1">
		<h3><?php _e( 'Login', 'woocommerce' ); ?></h3>
		<div class="form-wrapper">
			<div class="form-content">
		<form method="post" class="login">
			<div class="row">
			<?php do_action( 'woocommerce_login_form_start' ); ?>

			<p class="col-sm-6 woocommerce-FormRow woocommerce-FormRow--wide form-row form-row-wide">
				<label for="username"><?php _e( 'Användarnamn eller e-post', 'woocommerce' ); ?> <span class="required">*</span></label>
				<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="username" value="<?php if ( ! empty( $_POST['username'] ) ) echo esc_attr( $_POST['username'] ); ?>" />
			</p>
			<p class="col-sm-6 woocommerce-FormRow woocommerce-FormRow--wide form-row form-row-wide">
				<label for="password"><?php _e( 'Password', 'woocommerce' ); ?> <span class="required">*</span></label>
				<input class="woocommerce-Input woocommerce-Input--text input-text" type="password" name="password" id="password" />
			</p>

			<?php do_action( 'woocommerce_login_form' ); ?>
			</div>
			<div class="row">
			<p class="form-row col-xs-12 col-sm-4 pull-right ">
				<?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>
				<input type="submit" class="woocommerce-Button button" name="login" value="<?php esc_attr_e( 'Login', 'woocommerce' ); ?>" />
				
			</p>
			<p class="form-row col-xs-6 col-sm-4">
			<label for="rememberme" class="inline">
					<input class="woocommerce-Input woocommerce-Input--checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever" /> <?php _e( 'Remember me', 'woocommerce' ); ?>
				</label>
			<p class="form-row col-sm-4 col-xs-6  woocommerce-LostPassword lost_password">
				<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php _e( 'Lost your password?', 'woocommerce' ); ?></a>
			</p>

			<?php do_action( 'woocommerce_login_form_end' ); ?>
			</div>
		</form>
		</div></div>
	</div>
<?php if ( get_option( 'woocommerce_enable_myaccount_registration' ) === 'yes' ) : ?>

	

	<div class="u-column2 col-2">

		<h3><?php _e( 'Register', 'woocommerce' ); ?></h3>
		<div class="form-wrapper">
			<div class="form-content">
		<form method="post" class="register">
			<div class="row">
			<?php do_action( 'woocommerce_register_form_start' ); ?>
 
			<?php if ( 'no' === get_option( 'woocommerce_registration_generate_username' ) ) : ?>

				<p class="col-sm-6 woocommerce-FormRow woocommerce-FormRow--wide form-row form-row-wide">
					<label for="reg_username"><?php _e( 'Användarnamn eller e-post', 'woocommerce' ); ?> <span class="required">*</span></label>
					<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="reg_username" value="<?php if ( ! empty( $_POST['username'] ) ) echo esc_attr( $_POST['username'] ); ?>" />
				</p>

			<?php endif; ?>

			<p class="col-sm-6 woocommerce-FormRow woocommerce-FormRow--wide form-row form-row-wide">
				<label for="reg_email"><?php _e( 'Email address', 'woocommerce' ); ?> <span class="required">*</span></label>
				<input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="reg_email" value="<?php if ( ! empty( $_POST['email'] ) ) echo esc_attr( $_POST['email'] ); ?>" />
			</p>

			<?php if ( 'no' === get_option( 'woocommerce_registration_generate_password' ) ) : ?>

				<p class="col-sm-6 woocommerce-FormRow woocommerce-FormRow--wide form-row form-row-wide">
					<label for="reg_password"><?php _e( 'Password', 'woocommerce' ); ?> <span class="required">*</span></label>
					<input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password" id="reg_password" />
				</p>

			<?php endif; ?>

			<!-- Spam Trap -->
			<div style="<?php echo ( ( is_rtl() ) ? 'right' : 'left' ); ?>: -999em; position: absolute;"><label for="trap"><?php _e( 'Anti-spam', 'woocommerce' ); ?></label><input type="text" name="email_2" id="trap" tabindex="-1" /></div>

			<?php do_action( 'woocommerce_register_form' ); ?>
			<?php do_action( 'register_form' ); ?>
			</div>
			<div class="row">
			<p class="col-sm-4 col-xs-12  pull-right woocomerce-FormRow form-row">
				<?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>
				<input type="submit" class="woocommerce-Button button" name="register" value="<?php esc_attr_e( 'Register', 'woocommerce' ); ?>" />
			</p>
			</div>
			<?php do_action( 'woocommerce_register_form_end' ); ?>

		</form>
	</divZ
	</div>
	</div>

</div>
<?php endif; ?>

<?php do_action( 'woocommerce_after_customer_login_form' ); ?>
