<?php
/**
 * My Account Dashboard
 *
 * Shows the first intro screen on the account dashboard.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/my-account-dashboard.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://docs.woocommerce.com/document/template-structure/
 * @author      WooThemes
 * @package     WooCommerce/Templates
 * @version     2.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>

<div class="form-content">
<br />
<?php
		
		
		
		
		if ( get_user_meta(get_current_user_id(),'billing_first_name') ){
		
			echo "<h3>" . __( 'Hej ', 'woocommerce' ) . get_user_meta(get_current_user_id(),'billing_first_name')[0] . '.</h3>';
		}
		else{
			echo "<h3>" . __( 'Hej! ', 'woocommerce' ) . '</h3>';
		}
		echo "<p>" . __('Välkommen till dina sidor. Vad vill du göra idag?<br  />', 'understrap') . "</p><br />";
		
		
	?>
</div>
<div class="dashboard form-wrapper">
	<div class="form-content">
	
		<div class="row"> 
		<br />
		<?php foreach ( wc_get_account_menu_items() as $endpoint => $label ) : ?>
			<p class="form-row col-xs-12 col-sm-6" class="<?php echo wc_get_account_menu_item_classes( $endpoint ); ?>">
				<a href="<?php echo esc_url( wc_get_account_endpoint_url( $endpoint ) ); ?>"><button class="rvlvr_alt"><?php echo esc_html( $label ); ?></button></a>
			</p>
		<?php endforeach; ?>
		</div>
	</div>
</div>

<?php
	/**
	 * My Account dashboard.
	 *
	 * @since 2.6.0
	 */
	do_action( 'woocommerce_account_dashboard' );

	/**
	 * Deprecated woocommerce_before_my_account action.
	 *
	 * @deprecated 2.6.0
	 */
	do_action( 'woocommerce_before_my_account' );

	/**
	 * Deprecated woocommerce_after_my_account action.
	 *
	 * @deprecated 2.6.0
	 */
	do_action( 'woocommerce_after_my_account' );
?>
