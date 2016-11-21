<?php
/**
 * The template for displaying 404 pages (not found).
 * @package understrap
 */

get_header(); ?>
<div class="wrapper" id="404-wrapper">
    
    <div  id="content" class="container">

        <div class="row">
        
            <div id="primary" class="content-area">

                <main id="main" class="site-main" role="main">

                    <section class="error-404 not-found">
                        
                        <header class="page-header">

                            <?php rvlvr_header('page-header'); ?>
							
                        </header><!-- .page-header -->

                        <div class="page-content">

                            <p><?php _e( 'It looks like nothing was found at this location.', 'understrap' ); ?></p>

                            
                            
                        </div><!-- .page-content -->
                        
                    </section><!-- .error-404 -->

                </main><!-- #main -->
                
            </div><!-- #primary -->

        </div> <!-- .row -->
        
    </div><!-- Container end -->
    
</div><!-- Wrapper end -->

<?php get_footer(); ?>
