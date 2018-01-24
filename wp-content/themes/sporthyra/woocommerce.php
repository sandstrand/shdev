<?php
/**
 * The template for displaying all pages.
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages
 * and that other 'pages' on your WordPress site will use a
 * different template.
 *
 * @package understrap
 */

get_header(); ?>
<?php //if( !is_search()){echo "fuuuu";} ?>
<div class="wrapper" id="woocommerce-wrapper">
    
    <div class="container">
		<div class="row">
			<div id="primary" class="<?php if ( is_active_sidebar( 'sidebar-1' ) && !is_product_category() && !is_shop() && !is_search()) : ?>col-lg-9<?php else : ?>col-lg-12<?php endif; ?> content-area">
	   
				<main id="main" class="site-main" role="main">

					<!-- The WooCommerce loop -->
					<?php 
					if ( is_singular( 'product' ) ) {
						 woocommerce_content();
					}
					else{
						//For ANY product archive.
						//Product taxonomy, product search or /shop landing
 						wc_get_template( 'archive-product.php' );
					}?>
				
				</main><!-- #main -->
			   
			</div><!-- #primary -->
			
			<?php if ( !is_product_category() && !is_shop() && !is_search() ){
				get_sidebar(); 
				} ?>
		</div>
    </div><!-- Container end -->
    
</div><!-- Wrapper end -->

<?php get_footer(); ?>
