<?php if ( is_active_sidebar( 'footerfull' ) ): ?>

    <!-- ******************* The Hero Widget Area ******************* -->

    <div class="wrapper" id="wrapper-footer-full">
		
		<div class="container hidden-lg-up container-footer-search">
		<?php echo '<div class="col-xs-12 fullfix footer-search search">' . rvlvr_get_search_form() . '</div>'; ?>
		</div>
		<div class="container container-footer-widgets">
			<div class="row">
				<div class="widget-area footer-widgets" role="complementary">
					<?php dynamic_sidebar( 'footerfull' ); ?>
				</div>
			</div>
		</div>
    </div><!-- #wrapper-footer-full -->

<?php endif; ?>
