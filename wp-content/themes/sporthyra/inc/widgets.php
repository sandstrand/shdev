<?php
/**
 * Declaring widgets
 *
 *
 * @package understrap
 */
function understrap_widgets_init() {
	register_sidebar( array(
		'name'          => __( 'Sidebar', 'understrap' ),
		'id'            => 'sidebar-1',
		'description'   => 'Sidebar widget area',
		'before_widget' => '<aside id="%1$s" class="col-lg-12 col-sm-6 col-md-4 %2$s"><div class="widget">',
		'after_widget'  => '</div></aside>',
		'before_title'  => '<h3 class="widget-title">',
		'after_title'   => '</h3>',
	) );

    register_sidebar( array(
        'name'          => __( 'Top Slider', 'understrap' ),
        'id'            => 'hero',
        'description'   => 'Top slider widgets. Place two or more widgets here and they will slide!',
        'before_widget' => '<div class="item">',
        'after_widget'  => '</div>',
        'before_title'  => '',
        'after_title'   => '',
    ) );

    register_sidebar( array(
        'name'          => __( 'Top Static', 'understrap' ),
        'id'            => 'statichero',
        'description'   => 'Static top widgets. no slider functionallity',
        'before_widget' => '',
        'after_widget'  => '',
        'before_title'  => '',
        'after_title'   => '',
    ) );

        register_sidebar( array(
        'name'          => __( 'Footer Full', 'understrap' ),
        'id'            => 'footerfull',
        'description'   => 'Widget area below main content and above footer',
        'before_widget' => '<div id="%1$s" class="col-sm-6 col-md-4 col-lg-2 %2$s"><div class="widget">', 
        'after_widget'  => '</div></div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ) );

}
add_action( 'widgets_init', 'understrap_widgets_init' );