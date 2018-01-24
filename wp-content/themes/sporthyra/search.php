<?php
/**
 * The template for displaying search results pages.
 *
 * @package understrap
 */

get_header(); ?>
<div class="wrapper search-wrapper">
    searc
    <div class="container">

        <div class="row">
        
            <div id="primary" class="<?php if ( is_active_sidebar( 'sidebar-1' ) ) : ?>col-lg-12<?php else : ?>col-lg-12<?php endif; ?> content-area">
                
                <main id="main" class="site-main" role="main">

               

                    <header class="page-header">

                        <?php rvlvr_header('page-header'); ?>
                        
                    </header><!-- .page-header -->
                
					<?php get_template_part( 'loop-templates/content', 'none' ); ?>

           
                </main><!-- #main -->
                
            </div><!-- #primary -->

            <?php //get_sidebar(); ?>

        </div><!-- .row -->
    
    </div><!-- Container end -->
    
</div><!-- Wrapper end -->

<?php get_footer(); ?>