<?php
/**
 * The template used for displaying page content in page.php
 *
 * @package understrap
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

	<header class="entry-header">
	
		<?php rvlvr_header(get_the_title(), "entry-title"); ?>
	
	</header><!-- .entry-header -->

    
		
	<div class="entry-content">
	 	
		<div class="map store_map">
			<div id="map_canvas"></div>
		</div>			 
		<div class="store_meta">
			
<?php rvlvr_get_store_status($post); ?>

<?php rvlvr_get_store_days($post); ?>

<?php sporthyra_store_open($post); ?>

		
		</div>
		<div class="hidden-sm-down store_thumbnail"><?=get_the_post_thumbnail( $post->ID )?></div>			
		<div class="store_content"><?php the_content(); ?></div>
	<?php if(get_post_meta( $post->ID, 'rvlvr_store_facebookurl', true ) != null){ ?>
	<div class="row store_fb">
		<div class="col-sm-4 hidden-xs-down">
			<div class="store_fb_cta" >Följ oss för lokala nyheter och erbjudanden</div>
		</div>
		<div class="col-sm-8 col-xs-12">
			<div class="fb-page " data-href="<?php echo esc_html( get_post_meta( $post->ID, 'rvlvr_store_facebookurl', true ) ) ;?> " data-small-header="false" data-adapt-container-width="true" data-hide-cover="false" data-show-facepile="false" data-width="500"><blockquote cite="https://www.facebook.com/sporthyra" class="fb-xfbml-parse-ignore"><a href="https://www.facebook.com/sporthyra">Sporthyra.se</a></blockquote></div>
		</div>
	</div>
	<?php } ?>
	</div><!-- .entry-content -->

	<footer class="entry-footer">

		<?php edit_post_link( __( 'Edit', 'understrap' ), '<span class="edit-link">', '</span>' ); ?>

	</footer><!-- .entry-footer -->

</article><!-- #post-## -->
