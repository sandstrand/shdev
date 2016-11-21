<?php
/**
 * The sidebar containing the main widget area.
 *
 * @package understrap
 */

if ( ! is_active_sidebar( 'sidebar-1' ) ) {
	return;
}
?>

<div id="secondary" class="col-lg-3 hidden-md-down widget-area" role="complementary">
	<div class="row">
		<?php echo '<div class="rvlvr_search col-lg-12 col-sm-12 col-md-12">' . rvlvr_get_search_form() . '</div>'; ?>
		
		<?php dynamic_sidebar( 'sidebar-1' ); ?>
	</div>
</div><!-- #secondary -->
