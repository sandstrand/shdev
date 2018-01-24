<?php
/**
 * Single variation cart button
 *
 * @see 	https://docs.woocommerce.com/document/template-structure/
 * @author  WooThemes
 * @package WooCommerce/Templates
 * @version 2.5.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;
?>


<div class="clear"></div>	

<script type="text/template" id="tmpl-variation-template">
	<div class="woocommerce-variation-description">
		{{{ data.variation.variation_description }}}
	</div>

	<div class="woocommerce-variation-price pull-left">
		<label><?php _e('Pris', 'understrap'); ?></label>		
		<?php //var_export(is_rent($product)); ?>
		<?php if( !is_rent($product) ) { ?>
			<span class="price"><span class="woocommerce-Price-amount amount"><?php echo $product->get_price_html(); ?></span></span>
		<?php } ?> 

		{{{ data.variation.price_html }}} 

	</div>

	<div class="woocommerce-variation-availability">
		{{{ data.variation.availability_html }}}
	</div>
</script>
<script type="text/template" id="tmpl-unavailable-variation-template">
	<p><?php _e( 'Sorry, this product is unavailable. Please choose a different combination.', 'woocommerce' ); ?></p>
</script>


