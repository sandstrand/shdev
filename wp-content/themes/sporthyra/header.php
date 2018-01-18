<?php
/**
 * The header for our theme.
 *
 * Displays all of the <head> section and everything up till <div id="content">
 *
 * @package understrap
 */
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta http-equiv="X-UA-Compatible" content="IE=edge, chrome=1">
<meta name="viewport" content="width=device-width">

<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="<?php bloginfo('name'); ?> - <?php bloginfo('description'); ?>">
<link rel="profile" href="http://gmpg.org/xfn/11">
<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">
<?php wp_head(); ?>
</head>
																		
<body style="background-image: url(<?php echo rvlvr_get_body_bg(); ?>)" <?php if ( is_user_logged_in() ) { body_class(); } else { body_class('not-logged-in'); }; echo sporthyra_map_load() ?> >
<?php echo sporthyra_fb_load(); ?>
<div id="page" class="hfeed site">
    
    <!-- ******************* The Navbar Area ******************* -->
    <div class="wrapper-fluid wrapper-navbar" id="wrapper-navbar">
	
        <a class="skip-link screen-reader-text sr-only" href="#content"><?php _e( 'Skip to content', 'understrap' ); ?></a>
		
        <nav class="navbar navbar-fixed-top site-navigation" itemscope="itemscope" itemtype="http://schema.org/SiteNavigationElement">
			<div class="container" id="site-navigation">
				
				<div class="navbar-header" >
					
					<!-- Your site title as branding in the menu -->
					<a id="site-home-link" class="navbar-brand hidden-xs-down" href="<?php echo esc_url( home_url( '/' ) ); ?>" title="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>" rel="home"><img src="<?= get_template_directory_uri() . "/media/logo_top.png" ?>" /></a>
					
					<!-- Secondary nav-->
					<a href="<?php global $woocommerce; echo wc_get_cart_url(); ?>">
					<div class="menu_cart hidden-xs-down">
						
						<i class="fa fa-shopping-cart" aria-hidden="true"></i>
						<?= sprintf ( _n( '%d vara', '%d varor', WC()->cart->get_cart_contents_count() ), WC()->cart->get_cart_contents_count() ) ?> <br />
						<?= WC()->cart->get_cart_total() ?>
						<span class="hidden-lg-down"><br /><?=__('Till kassan &raquo;', 'understrap') ?></span>

					</div>
					</a>
					<div style="float:right;" id="menu_cart_sm" class="hidden-sm-up menu_cart_sm">
						
						<a href="<?php echo wc_get_cart_url(); ?>" class="nav-link">
						<span class="fa fa-shopping-cart"><?php 
						$count = WC()->cart->get_cart_contents_count(); 
						
						?></span><?php _e('Kundkorg', 'understarp');
						if($count > 0){
							echo "(" . $count . ")";
						}
						
						?></a>
						
						
					</div>
					</a>
					
					<!-- Cart-->
					
					<div class="hidden-lg-down menu_meta">
						<?php wp_nav_menu(
							array(
								'theme_location' => 'secondary-md-up',
								'container_class' => '',
								'menu_class' => 'secondary',
								'fallback_cb' => '',
								'menu_id' => 'secondary-md-up',
								'walker' => new wp_bootstrap_navwalker()
							)
						);	?>
					</div>
					<div class="primary-menu hidden-lg-down">
						<?php wp_nav_menu(
							array(
								'theme_location' => 'primary-xl',
								'container_class' => 'menu_expanded',
								'menu_class' => 'nav navbar-nav',
								'fallback_cb' => '',
								'menu_id' => 'main-menu-xl',
								'walker' => new wp_bootstrap_navwalker()
							)
						); ?>
					</div>					
					<!-- The Main wordpress Menu goes here -->	
					<div class="primary-menu hidden-md-down hidden-xl-up">
						<?php wp_nav_menu(
							array(
								'theme_location' => 'primary-lg',
								'container_class' => 'menu_semi_compact',
								'menu_class' => 'nav navbar-nav',
								'fallback_cb' => '',
								'menu_id' => 'main-menu-lg',
								'walker' => new wp_bootstrap_navwalker()
							)
						); ?>
					</div>
					<div class="primary-menu hidden-sm-down hidden-lg-up">
						
						<?php wp_nav_menu(
							array(
								'theme_location' => 'primary-md',
								'container_class' => 'menu_semi_compact',
								'menu_class' => 'nav navbar-nav',
								'fallback_cb' => '',
								'menu_id' => 'main-menu-md',
								'walker' => new wp_bootstrap_navwalker()
							)
						); ?>
					</div>
					<div class="primary-menu hidden-md-up hidden-xs-down">
						<?php wp_nav_menu(
							array(
								'theme_location' => 'primary-sm',
								'container_class' => 'menu_compact',
								'menu_class' => 'nav navbar-nav',
								'fallback_cb' => '',
								'menu_id' => 'main-menu-sm',
								'walker' => new wp_bootstrap_navwalker()
							)
						); ?>
					</div>
					<div class="primary-menu hidden-sm-up">
						<?php wp_nav_menu(
							array(
								'theme_location' => 'primary-xs',
								'container_class' => 'menu_compact',
								'menu_class' => 'nav navbar-nav',
								'fallback_cb' => '',
								'menu_id' => 'main-menu-xs',
								'walker' => new wp_bootstrap_navwalker()
							)
						); ?>
					</div>
					
				</div> <!-- navbar-header -->
				
				
					
			</div><!-- container -->
			<div class="container toggled_menus">
				
			</div>
		</nav>
	</div>

	 <div class="">
		<nav class="">
			
				
		</nav>
	</div>
	
	<div class="wrapper-fluid wrapper-navbar" id="">
		<nav class="navbar navbar-fixed-semi-top" itemscope="itemscope" itemtype="http://schema.org/SiteNavigationElement">
			<div id="" class="container container_toggled_menus">
				<div class="row menu_row_fix_remove">
				<div id="t_menus">
					
					<div id="t_sm_equipment" class="toggled_menu_div menu_div">
						<?php rvlvr_menu_non_season_equipment(); ?>
						<?php rvlvr_menu_season_equipment(); ?>
					</div>
					<div id="t_sm_products" class="toggled_menu_div menu_div">
						<?php rvlvr_menu_products_brands("brands"); ?>
						<?php rvlvr_menu_products("categories"); ?>			
					</div>
			  		<div id="t_md_nav" class="toggled_menu_div menu_div">
						<div class="col-lg-12">
							<?php rvlvr_menu_md_nav(); ?>
						</div>
					</div>
					<div id="t_md_stores" class="toggled_menu_div menu_div">
						<?php rvlvr_menu_md_stores(); ?>	
					</div>
					<div id="t_sm_nav" class="toggled_menu_div menu_div">
						<div class="col-sm-12">	
							<?php rvlvr_menu_sm_nav(); ?>
						</div>		
					</div>
					<div id="t_xs_nav" class="toggled_menu_div menu_div">			
						<div class="col-xs-12">
							<?php rvlvr_menu_xs_nav(); ?>
						</div>							
			    	</div>
				</div>
				</div>


			</div>
		</nav>
	</div>
	
	 <div class="wrapper-fluid wrapper-navbar" id="debug-navbar">
		<nav class="navbar navbar-fixed-bottom site-navigation" itemscope="itemscope" itemtype="http://schema.org/SiteNavigationElement">
			<div id="debug" class="container debug">
				<div class="row">
					<div class="col-xs-12 hidden-sm-up"><span class="responsive-debug responsive-debug-xs">xs -px -px //Low phone</span></div>
					<div class="col-sm-12 hidden-xs-down hidden-md-up"><span class="responsive-debug responsive-debug-sm">sm 544px 576,	//Mid phone</span></div>
					<div class="col-md-12 hidden-sm-down hidden-lg-up"><span class="responsive-debug responsive-debug-md">md 768px 720px //Pad protrait</span></div>
					<div class="col-lg-12 hidden-md-down hidden-xl-up"><span class="responsive-debug responsive-debug-lg">lg 1024px 980px //Pad landscape</span></div>
					<div class="col-xl-12 hidden-lg-down"><span class="responsive-debug responsive-debug-xl">xl 1140px 1140px //Desktop</span></div>
					<div class="col-xs-12" id="jsDebug">Debug: </div>
				</div>
				<br />
			</div>
		</nav>
	</div>
	<div style="position:relative">		
		<div id="s_menu" class="container static_menu menu_div hidden-sm-down">
			
		</div>
	</div>
	

			