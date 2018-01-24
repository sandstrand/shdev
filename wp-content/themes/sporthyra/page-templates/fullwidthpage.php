<?php
/**
 * Template Name: Full Width Page
 *
 * Template for displaying a page without sidebar even if a sidebar widget is published
 *
 * @package understrap
 */

get_header(); ?>

<div class="wrapper" id="full-width-page-wrapper">
    
    <div  id="content" class="container">
		<div class="row">
			<div id="primary" class="col-md-12 col-lg-12 content-area">

            	<main id="main" class="site-main" role="main">
				
                	<?php while ( have_posts() ) : the_post(); ?>

                    	<?php get_template_part( 'loop-templates/content', 'page' ); ?>

                    	<?php
                        // If comments are open or we have at least one comment, load up the comment template
                        	if ( comments_open() || get_comments_number() ) :

	                            comments_template();
                        
    	                    endif;
        	            ?>

	                <?php endwhile; // end of the loop. ?>

	            </main><!-- #main -->
           
		    </div><!-- #primary -->
		</div> 
    </div><!-- Container end -->
	<?php if(is_home() || is_front_page()){
		echo "<div class='sliders semi_full hidden-sm-down'>";
			if ( function_exists( 'easingslider' ) ) { easingslider( 8228 ); }
			if ( function_exists( 'easingslider' ) ) { easingslider( 8226 ); }
		echo "</div>";
	}
	?>
</div><!-- Wrapper end -->

<?php get_footer(); ?>
