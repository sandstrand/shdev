<?php
/**
 * The template part for displaying a message that posts cannot be found.
 *
 * Learn more: http://codex.wordpress.org/Template_Hierarchy
 *
 * @package understrap
 */
 
/**
* shdev
* Title moved to breadcrumbs
* grid-divs added to wrap search form
*/

?>

<article id="post-0" class="post no-results not-found">
	
	<header class="page-header">

		<?php //sh_header(__( 'Nothing found', 'sporthyra' ), "page-title"); ?> 

	</header><!-- .page-header -->
	
	<div class="page-content">

		<?php if ( is_home() && current_user_can( 'publish_posts' ) ) : ?>

			<p><?php printf( __( 'Ready to publish your first post? <a href="%1$s">Get started here</a>.', 'understrap' ), esc_url( admin_url( 'post-new.php' ) ) ); ?></p>

		<?php elseif ( is_search() ) : ?>

			<p><?php _e( 'Sorry, but nothing matched your search terms. Please try again with some different keywords.', 'understrap' ); ?></p>
			<div class="hidden-md-up">
				<?php get_search_form(); ?>
			</div>

		<?php else : ?>

			<p><?php _e( 'It seems we can&rsquo;t find what you&rsquo;re looking for. Perhaps searching can help.', 'understrap' ); ?></p>
			<div class="hidden-md-up">
				<?php get_search_form(); ?>
			</div>		
		<?php endif; ?>

	</div><!-- .page-content -->
	
</article><!-- .no-results -->
