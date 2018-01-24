<?php
/**
 * The main template file.
 *
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 * E.g., it puts together the home page when no home.php file exists.
 * Learn more: http://codex.wordpress.org/Template_Hierarchy
 *
 * @package understrap
 */


get_header(); ?>

    <?php
    if ( is_front_page() && is_home() ) {

        get_sidebar('hero'); 

        get_sidebar('statichero');
        
    } else {
    // Do nothing...or?
    }
    ?>

    <div class="wrapper" id="wrapper-index">
       
	   <div id="content" class="container">

            <div class="row">
           
    	       <div id="primary" class="<?php if ( is_active_sidebar( 'sidebar-1' ) ) : ?>col-lg-9<?php else : ?>col-lg-12<?php endif; ?> content-area">
                   
                     <main id="main" class="site-main" role="main">
						<header class="woocommerce-products-header">

							<?php rvlvr_header(get_the_title(), "page-title"); ?>

						</header>
						
                        <?php get_template_part( 'loop-templates/content', 'none' ); ?>
          
                    </main><!-- #main -->
                   
    	       </div><!-- #primary -->
        
            <?php get_sidebar(); ?>

            </div><!-- .row -->
           
       </div><!-- Container end -->
        
    </div><!-- Wrapper end -->

<?php get_footer(); ?>
