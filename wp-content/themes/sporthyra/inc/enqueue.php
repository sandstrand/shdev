<?php
/**
 * understrap enqueue scripts
 *
 * @package understrap
 */
 
function understrap_scripts() {
    wp_enqueue_style( 'understrap-styles', get_stylesheet_directory_uri() . '/css/theme.css', array(), '0.4.7');
    wp_enqueue_script('jquery'); 
    wp_enqueue_script( 'understrap-scripts', get_template_directory_uri() . '/js/theme.min.js', array(), '0.4.7', true );
    if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
        wp_enqueue_script( 'comment-reply' );
    }
	// Menus and navigation etc.
	wp_enqueue_script( 'rvlvr-scripts', get_template_directory_uri() . '/js/rvlvr.js', array(), '0.1', true );

	 // Google maps
    wp_enqueue_script( 'google-maps_local', get_template_directory_uri() . '/js/google-maps.js', array(), '1' , true );
	wp_enqueue_script( 'google_maps', 'https://maps.google.com/maps/api/js?key=AIzaSyBE-3jlM-jsilybxUlnO5Yq1mlrkGKYI0I', array(), '0.4.7', true );

	// Nexa fonts
	wp_enqueue_script( 'nexa_fonts', get_template_directory_uri() . '/js/nexa_fonts.js', array(), '0.1', false);
	
	// Delivery date info
	wp_enqueue_script( 'rvlvr-scripts-delivery-date', get_template_directory_uri() . '/js/rvlvr-delivery_date.js', array(), '0.1', true );
    
}

add_action( 'wp_enqueue_scripts', 'understrap_scripts' );

/** 
*Loading slider script conditionally
**/

if ( is_active_sidebar( 'hero' ) ):
add_action("wp_enqueue_scripts","understrap_slider");
  
function understrap_slider(){
    if ( is_front_page() ) {    
    $data = array(
        "timeout"=> intval( get_theme_mod( 'understrap_theme_slider_time_setting', 5000 )),
        "items"=> intval( get_theme_mod( 'understrap_theme_slider_count_setting', 1 ))
    	);

    wp_enqueue_script("understrap-slider-script", get_stylesheet_directory_uri() . '/js/slider_settings.js', array(), '0.4.7');
    wp_localize_script( "understrap-slider-script", "understrap_slider_variables", $data );
    }
}
endif;

