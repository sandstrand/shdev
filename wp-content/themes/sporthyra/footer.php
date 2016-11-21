<?php
/**
 * The template for displaying the footer.
 *
 * Contains the closing of the #content div and all content after
 *
 * @package understrap
 */
?>
<div id="om" class='anchorfix'></div>

<?php get_sidebar('footerfull'); ?>


<div class="wrapper" id="wrapper-footer-copy">
	<div class="container container-footer-widgets">
		<div class="row site-info">
			 <footer id="colophon" class="site-footer" role="contentinfo">
			 <div class="col-md-2 hidden-sm-down cert">
				<a id="celink816" href="http://www.ehandelscertifiering.se/">certifierad ehandel</a>
				<script src="https://www.ehandelscertifiering.se/lv6/bootstrap.php?url=www.sporthyra.se&amp;size=70px&amp;lang=sv&amp;autolang=off&amp;nr=816" defer="defer"></script>
			 </div>
			 <div class="col-md-8 copy">
				<?php echo get_option('copytext'); ?>
			 </div>
			 <div class="col-md-2 hidden-sm-down logo">
				<img src="<?= get_template_directory_uri() . "/media/logo_footer.png" ?>" />
			 </div>
			 </footer>
		</div>
	</div>
</div>

</div><!-- #page -->

<?php wp_footer(); ?>

</body>

</html>
