<?php
/**
 * Theme  functions and definitions
 * 
 * @package understrap
 */


/**
 * Theme setup and custom theme supports.
 */
require get_template_directory() . '/inc/setup.php';

/**
 * Register widget area.
 *
 * @link http://codex.wordpress.org/Function_Reference/register_sidebar
 */
require get_template_directory() . '/inc/widgets.php';

/**
* Load functions to secure your WP install.
*/
require get_template_directory() . '/inc/security.php';

/**
 * Enqueue scripts and styles.
 */
require get_template_directory() . '/inc/enqueue.php';

/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/template-tags.php';

/**
 * Custom functions that act independently of the theme templates.
 */
require get_template_directory() . '/inc/extras.php';

/**
 * Customizer additions.
 */
require get_template_directory() . '/inc/customizer.php';

/**
 * Customizer additions.
 */
require get_template_directory() . '/inc/custom-comments.php';

/**
 * Load Jetpack compatibility file.
 */
require get_template_directory() . '/inc/jetpack.php';

/**
* Load custom WordPress nav walker.
*/
require get_template_directory() . '/inc/bootstrap-wp-navwalker.php';

/**
* Load custom WordPress gallery.
*/
require get_template_directory() . '/inc/bootstrap-wp-gallery.php';


/**
* Load WooCommerce functions.
*/
require get_template_directory() . '/inc/woocommerce.php';

add_filter( 'woocommerce_enqueue_styles', '__return_false' );




//////////////////// Styling



// BG
add_action('init', 'rvlvr_set_body_bg');

function rvlvr_set_body_bg(){
	$cookie_name = "sporthyra_bg";
	if(!isset($_COOKIE[$cookie_name])){
		
		$body_bg = rvlvr_get_random_bg_img();
	
		setcookie($cookie_name, $body_bg, time() + (60), "/");
	}
}

function rvlvr_get_random_bg_img(){
	$season = rvlvr_get_seasons('current');
	$imgs = get_post_meta($season[0]['ID'], 'rvlvr_season_bg');
	$rand_img_id = $imgs[rand(0,count($imgs)-1)];
	return wp_get_attachment_url($rand_img_id);
}
function rvlvr_get_body_bg(){
	$cookie_name = "sporthyra_bg";
	
	if(isset($_COOKIE[$cookie_name])){
		$body_bg = $_COOKIE[$cookie_name];
	}
	else{
		$body_bg = rvlvr_get_random_bg_img();
	}
	
	return $body_bg;
} 

//add_filter( 'wp_get_attachment_url', function( $url, $id ){
/*add_filter( 'wp_get_attachment_url', function( $url ){
  if( is_ssl() )
    $url = str_replace( 'http://', 'https://', $url );
  return $url;
});*/

//add_filter('wp_get_attachment_url', 'honor_ssl_for_attachments');
function honor_ssl_for_attachments($url) {
	$http = site_url(FALSE, 'http');
	$https = site_url(FALSE, 'https');
	return ( $_SERVER['HTTPS'] == 'on' ) ? str_replace($http, $https, $url) : $url;
}
//////////////////// WC and WP cleanup

/** rvlvrdev
 * remove woocommerce native: breadcrumbs and other hooked actions
 */
 
remove_action( 'woocommerce_before_main_content','woocommerce_breadcrumb', 20, 0);
remove_action( 'woocommerce_before_shop_loop','woocommerce_result_count', 20, 0);
remove_action( 'woocommerce_before_shop_loop','woocommerce_catalog_ordering', 30, 0);
//remove_action( 'woocommerce_single_product_summary','woocommerce_template_single_title', 5, 0);


// Single prod
remove_action( 'woocommerce_before_single_product_summary','woocommerce_show_product_sale_flash', 10, 0);
remove_action( 'woocommerce_after_single_product_summary','woocommerce_output_related_products', 20, 0);
remove_action( 'woocommerce_single_product_summary','woocommerce_template_single_meta', 40, 0);
remove_action( 'woocommerce_single_product_summary','woocommerce_template_single_price', 10, 0);




/** rvlvrdev does not work
 * Allow for the same base url for cataegaories and products
 *
 */
add_filter( 'rewrite_rules_array', function( $rules )
{
    $new_rules = array(
        'shop/([^/]*?)/page/([0-9]{1,})/?$' => 'index.php?product_cat=$matches[1]&paged=$matches[2]',
        'shop/([^/]*?)/?$' => 'index.php?product_cat=$matches[1]',
    );
    return $new_rules + $rules;
} );

/** shdev
 * remove autor pages, redirect to home
 */
function author_archive_redirect() {
   if( is_author() ) {
       wp_redirect( home_url(), 301 );
       exit;
   }
}

// Disable downloadable products in user backend
function cheapmaal_woocommerce_account_menu_items_callback($items) {
    unset( $items['downloads'] );
	unset( $items['dashboard'] );
    return $items;
}
add_filter('woocommerce_account_menu_items', 'cheapmaal_woocommerce_account_menu_items_callback', 10, 1);

add_action( 'template_redirect', 'author_archive_redirect' );

//disable select2
add_action( 'wp_enqueue_scripts', 'agentwp_dequeue_stylesandscripts', 100 );
function agentwp_dequeue_stylesandscripts() {
if ( class_exists( 'woocommerce' ) ) {
wp_dequeue_style( 'select2' );
wp_deregister_style( 'select2' );
wp_dequeue_script( 'select2');
wp_deregister_script('select2');
}
}

/////////////////// The loop ////////////////

/** rvlvrdev
 * wrap loop in row class
 */
 
function rvlvr_before_loop(){
	echo "<div class='row'>";
} 
function rvlvr_after_loop(){
	echo "</div>";
}

add_action('woocommerce_before_shop_loop', 'rvlvr_before_loop', 40); 	
add_action('woocommerce_after_shop_loop', 'rvlvr_after_loop', 5); 	


/** rvlvrdev
 * Returns classes as string.
 * Applied to all loop li's
 *
 */
function rvlvr_product_class(){
	$classes="";
	if ( is_shop() || is_product_category() || is_page_template('page-templates/fullwidthpage.php') ){
		$classes .=" col-lg-3"; 
	}
	else{
		$classes .=" col-lg-4"; 
	}
	
	$classes .= " product col-xs-12 col-md-4 col-sm-4 ";
	return $classes;
}


/** rvlvrdev
 * Custom product thumbnail within looop
 * Used to wrap thumbanil images
 * Hooked in woocommerce/content-product 
 */
 
//Remove old action from hook 
remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10, 0);
remove_action( 'woocommerce_before_subcategory_title', 'woocommerce_subcategory_thumbnail', 10);

function rvlvr_loop_product_thumbnail( $size = 'shop_catalog', $deprecated1 = 0, $deprecated2 = 0 ) {
	echo "<div class='rvlvr_loop_thumb'>";
		echo woocommerce_get_product_thumbnail(); 	//woocommerce native function
	echo "</div>";
}
function rvlvr_loop_product_category_thumbnail( $cat ) {
	echo "<div class='rvlvr_loop_thumb'>";
		
			//global $term;
			
			$thumbnail_id = get_woocommerce_term_meta( $cat->term_id, 'thumbnail_id', true );
			$image = wp_get_attachment_url( $thumbnail_id );
			
			if ( $image ) {
				echo '<img src="' . $image . '" alt="" />';
			}
		
	echo "</div>";
}
//Add new funtion to hook	
add_action('woocommerce_after_shop_loop_item_title', 'rvlvr_loop_product_thumbnail', 7);
add_action('woocommerce_after_subcategory_title', 'rvlvr_loop_product_category_thumbnail', 7);
 	



/** rvlrdev
* wrap loop li content in divs
* grid classes are added to li elements through /js/rvlvr.js
*/
function rvlvr_before_loop_item(){
	echo "<div class='rvlvr_loop_item'>";
}
add_action( 'woocommerce_before_shop_loop_item', 'rvlvr_before_loop_item', 5 );
add_action( 'woocommerce_before_subcategory', 'rvlvr_before_loop_item', 5 );
function rvlvr_after_loop_item(){
	echo "</div>";
}
add_action( 'woocommerce_after_shop_loop_item', 'rvlvr_after_loop_item', 15 );
add_action( 'woocommerce_after_subcategory', 'rvlvr_after_loop_item', 15 );



/** rvlvrdev
 * Cleanup product and categories in loop
 * Removes salesflash, add-to-cart etc.
 */
remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10, 0);
remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10, 0);



/** rvlvrdev
 * Replace loop title for products and
 * Hooked in woocommerce/content-product 
 */
 
//Remove old action from hook 
remove_action( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10, 0);
remove_action( 'woocommerce_shop_loop_subcategory_title', 'woocommerce_template_loop_category_title', 10, 0);

function rvlvr_loop_product_title( $size = 'shop_catalog', $deprecated1 = 0, $deprecated2 = 0 ) {
	
	$primary_title = "no match 1";
	$secondary_title = "no match 2";
	global $post;
	echo "<div class='rvlvr_loop_title'>";
		if( !is_search() && get_the_terms( $post->ID, 'product_cat' )[0]->to_array()['term_id'] == get_queried_object()->term_id ) {
			$primary_title = strtok($post->post_title, " ");
			$secondary_title = substr(strstr($post->post_title," "), 1);
		}
		else{
			$product_cats = wp_get_post_terms( get_the_ID(), 'product_cat' );
			if ( $product_cats && ! is_wp_error ( $product_cats ) ){	
				$single_cat = array_shift( $product_cats );
				$primary_title = $single_cat->name;
			}
			$secondary_title = get_the_title();

		}
		if( $post->post_type=='product_category') {
			//$primary_title = $post->post_type;
		}
		else if( $post->post_type=='product') {
			//$primary_title = "is Product";
			//$primary_title = $post->post_type;
		}
		echo "<h3>" . $primary_title . "</h3>";
		echo "<h4>" . $secondary_title . "</h3>";
	
	echo "</div>";
}
//Add new funtion to hook	
add_action('woocommerce_shop_loop_item_title', 'rvlvr_loop_product_title', 7); 	

/** rvlvrdev
 * Replace loop title for categories
 * Hooked in woocommerce/content-product-cat 
 */
function rvlvr_loop_category_title( $category ) {
	
	$primary_title = $category->name;
	$secondary_title = "";
	global $post;
	echo "<div class='rvlvr_loop_title'>";

		/*if( $post->post_type=='product_category') {
			$primary_title = $post->post_type;
		}
		else if( $post->post_type=='product') {
			$primary_title = "Category";
			//$primary_title = $post->post_type;
		}*/
		echo "<h3>" . $primary_title . "</h4>";	
		//echo "<h4>" . $secondary_title . "</h4>";
	echo "</div>";
}
add_action('woocommerce_before_subcategory_title', 'rvlvr_loop_category_title', 7); 	


/** rvlvrdev
 * Replace loop price for products
 * Hooked in woocommerce/content-product 
 */

//Remove old action from hook 
remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10, 0);

function rvlvr_loop_product_prices() {
	global $product;
	
	$terms = get_terms(sanitize_title('pa_rvlvr-condition'));
	$prices = array();
	$url = array();
	
	$price1_primary = "";
	$price1_secondary = "";
	$price1_class ="";
	
	$price2_primary = "";
	$price2_secondary = "";
	$price2_class = "";
	
	echo "<div class='rvlvr_loop_prices'>";

		// Variable type
		
		if($product->get_type() == 'variable'){
			//var_export($product->get_available_variations());
			if( $product->get_attribute('pa_rvlvr-condition') ){ 																		
				
				foreach ($product->get_available_variations() as $variation){
				
				// Condition attribute exists
				
					$prices[$variation['attributes']['attribute_pa_rvlvr-condition']] = $variation['price_html'];
					//$url[$variation['attributes']['attribute_pa_rvlvr_type']] = $variation['variation_id'];
					//var_export($prices);
					
					// A week attribute value exists
					if(array_key_exists('rent-week',$prices)==true){  				
						$price1_primary = $prices['rent-week'];
						foreach( $terms as $object){
							if($object->slug=='rent-week'){
								$price1_secondary = "/ " . $object->name;
								break;
							}
						}
					}
					
					// A season attribute value exists
					$args = array(
						'post_type'             => 'season',
						'post_status'           => 'publish',
					);
					$seasons = get_posts($args);
					//var_export($seasons);
					foreach ($seasons as $season){
						$season_attribute = get_post_meta($season->ID, 'rvlvr_season_attribute', true);
						if(array_key_exists($season_attribute, $prices)){
							$price2_primary = $prices[$season_attribute];
							foreach( $terms as $object){
								if($object->slug==$season_attribute){
									$price2_secondary = "/ " . $object->name;
									break;
								}
							}	
						}
					}
					/*if(array_key_exists('rent-season-winter',$prices)==true){ 				
						$price2_primary = $prices['rent-season-winter'];
						foreach( $terms as $object){
							if($object->slug=='rent-season-winter'){
								$price2_secondary = "/" . $object->name;
								break;
							}
						}
					}
					elseif(array_key_exists('rent-season-summer',$prices)==true){ 				
						$price2_primary = $prices['rent-season-winter'];
						foreach( $terms as $object){
							if($object->slug=='rent-season-winter'){
								$price2_secondary = "/" . $object->name;
								break;
							}
						}
					}*/
				}
			}
			
			// Condition attribute does NOT exist but it is still a variable product
			else{
				$variations = $product->get_available_variations();
				$variation_id = $variations[0]['variation_id'];
				$variation_product = new WC_Product_Variation( $variation_id );
				if($product->is_on_sale()==true){
					$price1_primary = $variation_product->get_regular_price() . get_woocommerce_currency_symbol();
					$price1_class = "price_old";
					$price2_primary = $variation_product->get_sale_price() . get_woocommerce_currency_symbol();
					$price2_secondary = "/ " . __('Reapris', 'understrap');
					$price2_class = "price_sale";
				}
				else{
					$price1_primary = $variation_product->get_regular_price() . get_woocommerce_currency_symbol();
					
					foreach( $terms as $object){
						if($object->slug==key($prices)) {
							$left_price_suffix = "/ " . $object->name;
							break;		
						}
						else{
							//No suffix, no villkor
							$left_price_suffix = "";
						}
					}
				}		
			}		
		}
		
		// Simple type
		elseif($product->get_type() == 'simple'){
			
			// REA
			if($product->is_on_sale()==true){
				$price1_primary = $product->get_regular_price() . get_woocommerce_currency_symbol();
				$price1_secondary = "";
				$price1_class = "price_old";
				$price2_primary = $product->get_sale_price() . get_woocommerce_currency_symbol();
				$price2_secondary = "<span class='sale'>/" . __('Reapris', 'understrap') . "</span>";
				$price2_class = "price_sale";
			}
			
			//Ej REA
			else{
				$price1_primary = $product->get_regular_price() . get_woocommerce_currency_symbol();			
			}
		}
		
		// The type is neither Simple och Variable
		else{
			$price1_primary = "Unknown product type";
		}
		
		// Output set prices
		//echo "a";
		echo "<div class='rvlvr_loop_price1 rvlvr_loop_price " .  $price1_class . "'>";
			echo "<span class='rvlvr_price'>" . $price1_primary . "</span><br />";
			echo "<span class='rvlvr_price_condition'>" . $price1_secondary . "</span>";	
		echo "</div>";

		echo "<div class='rvlvr_loop_price2 rvlvr_loop_price " . $price2_class . "'>";
			echo "<span class='rvlvr_price'>" . $price2_primary . "</span><br />";
			echo "<span class='rvlvr_price_condition'>" . $price2_secondary . "</span>";		
		echo "</div>";	
	echo "</div>";
}

    
add_action('woocommerce_after_shop_loop_item_title', 'rvlvr_loop_product_prices', 7); 	


////////// Navigation and headers

/** rvlvrdev
 * Print header containing breadrcrumbs, title, and search.
 * Used in page templates instead of title.
 */
 
function rvlvr_header($class){
	echo '<div class="row">';
 		if (is_front_page() || is_search() || is_page_template('page-templates/fullwidthpage.php')) {
			echo '<div class="col-md-9 col-lg-9">'; 
		}
		elseif( is_shop() || is_single() || !is_product_category() ){ 
			echo '<div class="col-md-12">'; 
		
		} 
		else {  
			echo '<div class="col-md-9 col-lg-9">'; 
		}
			rvlvr_breadcrumbs($class);
		echo '</div>';
		/* Prepared for product sorting
		if( is_shop() || is_product_category() ){   
			echo '<div class="col-md-3 col-sm-6 sort"><input type="text" value="sort"></div>';
			echo '<div class="col-md-3 col-sm-6 search"><input type="text" value="search"></div>';
		}*/
		echo '<div class="col-md-4 col-lg-3 col-sm-6 hidden-md-down search">' . rvlvr_get_search_form() . '</div>';
	echo '<div class=""></div></div>';	
}


/*function rvlvr_product_parent($skip=0){
	$breadcrumbs = new WC_Breadcrumb();
    $crumbs = $breadcrumbs->generate();
	$lastkey = count($crumbs)-1;
	$parent = array($crumbs[$lastkey][0], get_category_by_slug($crumbs[$lastkey][0]));
	var_export($crumbs);
	}
	
*/

/** rvlvrdev
 * Get product ancestry
 * $levels, optional, all assumed, determine how many levels up form parent to fetch
 * Returns leveled associated array as
 * array ( 
 *  0 => array(
 *   0 => string 
 *   1 => href
 *  )
 * )
 * Used by the function to retrive and build breadcrumbs and to get category in product title
 */ 
 
function rvlvr_product_ancestry($skip=0){

	$breadcrumbs = new WC_Breadcrumb();
    $crumbs = $breadcrumbs->generate();
	$lastkey = count($crumbs)-1;
	
	unset($crumbs[$lastkey]);
	$lastkey = count($crumbs)-1;
	
	if( $skip > 0 ){
		if( key_exists($skip ,$crumbs)){
			for ($i = 0; $i <= $skip; $i++) {
				unset($crumbs[$i-1]);
			}
		}
		else{
			$crumbs = null;
			//$crumbs = array(array("Cant skip", ""));
		}
		
	}	
	if( $crumbs != null ){
		$crumbs = array_values($crumbs);
		return $crumbs;
	}
}
 
/** rvlvrdev
 * Build breadcrumb
 * outputs breadcrumbs and title or just title  as div > ul > li
 * Used by rvlvr_header
 */
 
function rvlvr_breadcrumbs($class){
	$crumbs = array();
	 
	if( is_woocommerce()){
		if ( is_search() ) {
      		$page_title = array(sprintf(__( 'Search Results: &ldquo;%s&rdquo;', 'woocommerce' ), get_search_query() ), "product search title");
			
			if ( get_query_var( 'paged' ) ){
    	    	$page_title[0] .= sprintf(__( '&nbsp;&ndash; Page %s', 'woocommerce' ), get_query_var( 'paged' ) );
			}
			
   		} elseif ( is_tax() ) {
			$crumbs = rvlvr_product_ancestry(1);
     		$page_title = array(single_term_title( "", false ), "product tax title");

   		} elseif ( is_single() ) {
   			$crumbs = rvlvr_product_ancestry(1);
      		
      		//$shop_page_id = wc_get_page_id( 'shop' );
      		$page_title = array(get_the_title(), "single product title");
    	}
    	
	}
	elseif ( is_404() ){
		$page_title = array(__("404: hittade inget", "understrap"));			
	}
	elseif ( is_search() ){
		$page_title = array(__("404: hittade inget", "understrap"));			
	}
	elseif ( is_page() ){
		$page_title = array(get_the_title(), "single page title");		
	}
	elseif ( is_singular('store')){
		$crumbs[] = array(__('Våra butiker','rvlr'), get_post_type_archive_link( 'post' ));
	    $page_title = array(get_the_title(), "single post title");		
	}
	elseif ( is_singular('offer')){
		$crumbs[] = array(__('Erbjudanden','rvlr'), get_post_type_archive_link( 'post' ));
	    $page_title = array(get_the_title(), "single post title");		
	}
	elseif ( is_single() ){
		$crumbs[] = array(__('Blogg','understrap'), get_post_type_archive_link( 'post' ));
	    $page_title = array(get_the_title(), "single post title");			
	} 
	elseif ( is_home() ){ 
		$page_title = array(__('404: hittade inget', "understrap"));		
	}
	else{
	    $page_title = array(get_the_title(), "catch-all title");			
	}
	//$crumbs[] = array(get_the_title(),"");
	if( isset($page_title)){
		$crumbs[] = $page_title;
	}
  	
  	echo "<div class='rvlvr_header'><ul id='breadcrumbs' class='breadcrumbs'>";
  	$keys = count($crumbs);
  	$i=1;
  	foreach ( $crumbs as $key){
  		if ( $i < $keys ) {
  			echo "<li class='19 heavy crumb-" . $i . " crumb'><a href='" . $key[1] . "'>" . $key[0] . "</a></li><li class='divider-" . $i . " divider'> / </li>";
  		}
  		else {
			echo "<li class='19 heavy crumb-current crumb'><h1 class='" .  $class . "'>" . $key[0] . "<h1></li>";
  		}
  		
  		$i++;
  	}
  	echo "</ul></div>"; 
}


//////////////////// toggled and fixed Menus

function rvlvr_get_seasons($which) {
	$args = array(
		'post_type'             => 'season',
		'post_status'           => 'publish',
	);
	$seasons = get_posts($args);	
	$season_subset = array();
	$current_season = get_option('rvlvr_settings')['rvlvr_select_season'];

	/*if($include == true){
		$season_subset[] = array('ID' => '0', 'title' => $season->post_title, 'expire' => get_post_meta($season->ID, 'rvlvr_season_expires')[0]);
	}*/
	
	if($which == 'current'){
		$season_subset[] = array('ID' => $current_season, 'title' => get_the_title($current_season), 'expires' => get_post_meta($current_season, 'rvlvr_season_expires')[0]);
	}
	elseif($which == 'other'){
		foreach($seasons as $season){
			if($season->ID != $current_season){
				$season_subset[] = array('ID' => $season->ID, 'title' => $season->post_title, 'expire' => get_post_meta($season->ID, 'rvlvr_season_expires')[0]);
			}
		}
	}
	elseif($which == 'all'){
		foreach($seasons as $season){
			$season_subset[] = array('ID' => $season->ID, 'title' => $season->post_title, 'expire' => get_post_meta($season->ID, 'rvlvr_season_expires')[0], 'attribute' => get_post_meta($season->ID, 'rvlvr_season_attribute')[0]);
		}
	}
	return $season_subset;
}

function rvlvr_get_categories($topcat, $season = false){
	$args = array(
		'taxonomy' => 'product_cat',
		'hide_empty' => false,
		'hierarchical' => false,
		'parent' => $topcat,
	);

	$categories = get_categories( $args );
	return $categories;	
}

function rvlvr_get_products($cat){
	$args = array(
		'post_type'             => 'product',
		'post_status'           => 'publish',
		'ignore_sticky_posts'   => 1,
		'posts_per_page'   		=> 20,
		'meta_query'            => array(
			array(
				'key'           => '_visibility',
				'value'         => array('catalog', 'visible'),
				'compare'       => 'IN'
			)
		),
		'tax_query'             => array(
			array(
				'taxonomy'      => 'product_cat',
				'field' => 'term_id', //This is optional, as it defaults to 'term_id'
				'terms'         => $cat,
				'operator'      => 'IN' // Possible values are 'IN', 'NOT IN', 'AND'.
			)
		)
	);
	$products = get_posts($args);
	return $products;
}

function rvlvr_build_menu_products($list, $title, $catid, $break){
	
	

	//var_export(get_option( "taxonomy_$catid" ));
	echo "<a href='" . get_category_link($catid) . "'><b>" . $title . "</b></a>";
	echo "<ul class='rvlvr_menu_products'>";
	foreach($list as $item){
		echo "<li>";
			echo "<a href='" . get_permalink($item->ID). "'>";
				//var_export($item);
				echo $item->post_title;
			echo "</a>";
		echo "</li>";
		}
	echo "</ul>";
	
	if($break){
		echo "</div>";
		echo "<div class='rvlvr_menu_block menu_products col-lg-2-manual col-xs-12 col-md-3 col-sm-4'>";
	}
				
	
}
function rvlvr_build_menu_categories($list, $title = false, $catid, $season = false, $class = '', $break = false){
	echo "<div class='rvlvr_menu_block col-lg-2-manual menu_categories col-md-3 col-sm-4 col-xs-12 " . $class . "'>";
		if($catid){
			echo "<a href='" . get_category_link($catid) . "'><b>" . $title . "</b></a>";
		}
		else{
			echo "<b>" . $title . "</b>";
		}
		echo "<ul class='rvlvr_menu_categories'>";
		//var_export($season);
		if($season){
			foreach($list as $item){
				$item_id = $item->term_id;
				$cat_seasons = get_option( "rvlvr_tax_seasons_$item_id" );
				
				if($cat_seasons && in_array($season, $cat_seasons)){
				echo "<li>";
					echo "<a href='" .get_term_link($item->term_id) . "'>";
						echo $item->name;
						//var_export($item);
					echo "</a>";
				echo "</li>";
				}
			}
		}
		else{
			foreach($list as $item){
				//$item_id = $item->term_id;

				echo "<li>";
					//echo $item->term_id;
					//echo get_category_link($item->term_id);
					echo "<a href='" . get_term_link($item->term_id) . "'>";
						echo $item->name;
						//var_export($item);
					echo "</a>";
				echo "</li>";
			}
		}
		echo "</ul>";
	echo "</div>";
}

function rvlvr_menu_season_equipment(){
	
	$breaks = array( 
		0 => false, 
		1 => true, 
		2 => false,
		3 => true,
		4 => false,
		5 => true,
		6 => false,
		7 => true,
		8 => false
	);
	
	$topcat = get_option('rvlvr_settings')['rvlvr_equipment_cat'];
	$categories = rvlvr_get_categories($topcat);	
	
	$i=0;
	
	echo "<div class='rvlvr_menu_block menu_products col-lg-2-manual col-xs-12 col-md-3 col-sm-4'>";
	foreach($categories as $category){
		if(array_key_exists($i, $breaks)) { 
			$break = $breaks[$i]; 
		}
		else {
			$break = false;
		}
		$title = $category->name;
		$cat = $category -> term_id;
		$products = rvlvr_get_products($cat);
		$cat_seasons = get_option( "rvlvr_tax_seasons_$cat" );
		if( $cat_seasons && in_array(rvlvr_get_seasons('current')[0]['ID'], $cat_seasons)){
				rvlvr_build_menu_products($products, $title, $cat, $break );
				$i++;
		}
	}
	echo "</div>";

}

function rvlvr_menu_non_season_equipment(){
	
	$topcat = get_option('rvlvr_settings')['rvlvr_equipment_cat'];
	$categories = rvlvr_get_categories($topcat);
	foreach(rvlvr_get_seasons('other') as $season){
		rvlvr_build_menu_categories($categories, $season['title'], false, $season['ID'], 'menu_non_season menu_div_float_right hidden-md-down');
	}
	
	$categories = rvlvr_get_categories($topcat);
	
	
	/*foreach(rvlvr_get_categories($topcat) as $category){
			$title = $category->name;
			$cat = $category -> term_id;
			$subcategories = rvlvr_get_categories($cat);
			rvlvr_build_menu_categories($subcategories, $title, $cat);
			//echo "id: " . $category->term_id . " " . $category->name;
			//echo "<br />";
			//foreach(rvlvr_get_categories($subcategory) as $subcategory){
			//		echo "subid: " . $subcategory->term_id . " " . $subcategory->name;
			//}
		}	
	/*foreach(rvlvr_get_categories($topcat) as $category){
		$title = $category->name;
		$cat = $category -> term_id;
		$cat_seasons = get_option( "rvlvr_tax_seasons_$cat" );
		if( $cat_seasons && in_array(rvlvr_get_seasons('other')[0]['ID'], $cat_seasons)){
			rvlvr_build_menu_categories($categories, $title, $cat);
		}
		
		
	}*/
}

function rvlvr_menu_products(){
	$break = array(2,4,6,8,10);
	
	$topcat = get_option('rvlvr_settings')['rvlvr_products_cat'];
	$categories = rvlvr_get_categories($topcat);
	
	
		foreach(rvlvr_get_categories($topcat) as $category){
			$title = $category->name;
			$cat = $category -> term_id;
			$subcategories = rvlvr_get_categories($cat);
			rvlvr_build_menu_categories($subcategories, $title, $cat);
			//var_export($subcategories)";
			//echo "id: " . $category->term_id . " " . $category->name;
			//echo "<br />";
			//foreach(rvlvr_get_categories($subcategory) as $subcategory){
			//		echo "subid: " . $subcategory->term_id . " " . $subcategory->name;
			//}
		}
	
}

function rvlvr_menu_products_brands(){
	$terms = get_terms( array( 'taxonomy' => 'product_brand','hide_empty' => true, ) );
		if($terms){
			echo "<div class='rvlvr_menu_block menu_brands col-lg-2-manual col-md-3 col-sm-4 col-xs-12 menu_div_float_right'>";
				echo "<b>" . __("Varumärken", 'understrap') . "</b>";
				echo "<ul>";
					foreach($terms as $term){
						//var_export($term);
						echo '<li><a href="/brand/' . $term->name . '">' . $term->name . '</a></li>';
					}
				"</ul>";
			echo "</div>";
		}
	}

function rvlvr_menu_md_nav(){
	$menu_content ="Navigation for md";

	echo $menu_content;
}

function rvlvr_menu_md_stores(){
	
	echo "<div class='col-md-6 hidden-sm-down'>";
		echo "<img src='" . wp_get_attachment_url(get_option( 'rvlvr_settings' )['rvlvr_store_map']) . "' />";
	echo "</div>";
	echo "<div class='col-md-3 col-sm-6'>";
		echo "<b>" . __('Konceptbutiker', 'understrap' ) . "</b>";
		echo "<ul>";
			foreach(rvlvr_get_stores('concept') as $store){
				echo "<li><a class='secondary' href='" . get_permalink($store->ID) . "' >" . $store->post_title . "</a></li>";
			}
		echo "</ul>";
	echo "</div>";
	echo "<div class='col-md-3 col-sm-6'>";
		if(count(rvlvr_get_stores('agent'))>0){
			echo "<b>" . __('Ombud', 'understrap' ) . "</b>";
			echo "<ul>";
				foreach(rvlvr_get_stores('agent') as $store){
					echo "<li><a class='secondary' href='" . get_permalink($store->ID) . "' >" . $store->post_title . "</a></li>";
				}
			echo "</ul>";
		}
	echo "</div>";

	
}

function rvlvr_menu_sm_nav(){
	$menu_content ="Navigation menu for sm";

	echo $menu_content;
}

function rvlvr_menu_xs_nav(){
	$menu_content ="Navigation menu for xs";

	echo $menu_content;
}

/////////////////// Shortcodes

/**
 * rvlvrdev
 * Shortcode for featured produtas from given category
 */
 
add_shortcode( 'featured_products_custom', 'rvlvr_featured_products_shortcode' );
 
function rvlvr_featured_products_shortcode( $atts ) {
	global $woocommerce_loop;

	extract(shortcode_atts(array(
		'per_page'      => '12',
		'columns'       => '4',
		'orderby' => 'date',
		'order' => 'desc',
		'category'=> ''
	), $atts));
	$args = array(
		'post_type'     => 'product',
		'post_status' => 'publish',
		'ignore_sticky_posts'   => 1,
		'posts_per_page' => $per_page,
		'orderby' => $orderby,
		'order' => $order,
		'tax_query' => array(
			array(
				'taxonomy'      => 'product_cat',
				'terms'         => array( esc_attr($category) ),
				'field'         => 'slug',
				'operator'      => 'IN'
			)
		),
		'meta_query' => array(
			array(
				'key' => '_visibility',
				'value' => array('catalog', 'visible'),
				'compare' => 'IN'
			),
			array(
				'key' => '_featured',
				'value' => 'yes'
			)
		)
	);
	ob_start();
	$products = new WP_Query( $args );
	$woocommerce_loop['columns'] = $columns;

	if ( $products->have_posts() ) :
	
		woocommerce_product_loop_start();

		while ( $products->have_posts() ) : $products->the_post();
			wc_get_template_part( 'content', 'product' );
		endwhile; // end of the loop.
		
		woocommerce_product_loop_end();
	endif;
	
	wp_reset_postdata();
	 
	//Uncomment
	
	

	return '<div class="row woocommerce">' . ob_get_clean() . '</div>';
}

add_action( 'woocommerce_cart_is_empty', 'woocommerce_output_upsells', 15 );
 
if ( ! function_exists( 'woocommerce_output_upsells' ) ) {
	function woocommerce_output_upsells(){
		rvlvr_featured_products(get_option('rvlvr_settings')['rvlvr_equipment_cat_slug'], 3);
			
	}
}

function rvlvr_featured_products($cat, $number){



	//var_export($args);
	//ob_start();
	$args = apply_filters( 'woocommerce_related_products_args', array(
		'post_type'            => 'product',
		'ignore_sticky_posts'  => 1,
		'no_found_rows'        => 1,
		'posts_per_page'       => $number,
		'tax_query' => array(
			array(
				'taxonomy'      => 'product_cat',
				'terms'         => array( esc_attr($cat) ),
				'field'         => 'slug',
				'operator'      => 'IN'
			)
		),
		'meta_query' => array(
			array(
				'key' => '_visibility',
				'value' => array('catalog', 'visible'),
				'compare' => 'IN'
			),
			array(
				'key' => '_featured',
				'value' => 'yes'
			)
		)
		
		) );

		$products                    = new WP_Query( $args );
		$woocommerce_loop['name']    = 'featured';
		
	
	if ( $products->have_posts() ) : ?>
	<h2><?php _e( 'Populär utrustning', 'woocommerce' ); ?></h2>
	
	<div class="related products row">

		<?php woocommerce_product_loop_start(); ?>
			
			<?php while ( $products->have_posts() ) : $products->the_post(); ?>

				<?php wc_get_template_part( 'content', 'product' ); ?>

			<?php endwhile; // end of the loop. ?>

		<?php woocommerce_product_loop_end(); ?>

	</div>

<?php endif;

wp_reset_postdata();
	 
	//Uncomment
	
	//return '<div class="row woocommerce">' . ob_get_clean() . '</div>';*/
}


/** Third party solution to applying current/paretn/ancestor classes to custom post type archives
 *  https://gist.github.com/gerbenvandijk/5253921
 */

add_action('nav_menu_css_class', 'add_current_nav_class', 10, 2 );

function add_current_nav_class($classes, $item) {

	// Getting the current post details
	global $post;

	// Get post ID, if nothing found set to NULL
	$id = ( isset( $post->ID ) ? get_the_ID() : NULL );

	// Checking if post ID exist...
	if (isset( $id )){
	
		// Getting the post type of the current post
		$current_post_type = get_post_type_object(get_post_type($post->ID));
		$current_post_type_slug = $current_post_type->rewrite['slug'];          
		
		// Getting the URL of the menu item
		$menu_slug = strtolower(trim($item->url));	

		// If the menu item URL contains the current post types slug add the current-menu-item class
		if (strpos($menu_slug,$current_post_type_slug) !== false) {
			$classes[] = 'current-menu-item';
		}
	}
	// Return the corrected set of classes to be added to the menu item
	return $classes;
	
}
//add_filter( 'loop_shop_per_page', create_function( '$cols', 'return 1;' ), 20 ); //debug paging


//////////////////////// Theme options

function theme_settings_page(){
	?>
	<div class="wrap">
	<h1><?=__("Copyright text", "understrap") ?></h1>
	<form method="post" action="options.php">
		<?php
			settings_fields("section");
			do_settings_sections("theme-options");      
			submit_button(); 
		?>          
	</form>
	</div>
<?php
}

function display_copytext_element()
{
	?>
    	<textarea style="width:400px;" rows=10 name="copytext" id="coptyext"><?php echo get_option('copytext'); ?></textarea/>
    <?php
}

function display_theme_panel_fields()
{
	add_settings_section("section", "All Settings", null, "theme-options");
	
	add_settings_field("copytext", "copytext", "display_copytext_element", "theme-options", "section");

    register_setting("section", "copytext");
}

add_action("admin_init", "display_theme_panel_fields");

function add_theme_menu_item()
{
	add_menu_page("Theme Settings", "Theme Settings", "manage_options", "theme-panel", "theme_settings_page", null, 99);
}
add_action("admin_menu", "add_theme_menu_item");


///// Single product

remove_action('woocommerce_single_variation', 'woocommerce_single_variation', 10 );
add_action('woocommerce_single_variation', 'woocommerce_single_variation', 25 );

function rvlvr_has_attribute_variant($product, $attribute_variant){
	$prices = array();
	
	foreach ($product->get_available_variations() as $variation){
			if(array_key_exists('attribute_pa_' . rvlvr_config()['rvlvr_attribute'], $variation['attributes'])){
				$prices[$variation['attributes']['attribute_pa_' . rvlvr_config()['rvlvr_attribute']]] = $variation['price_html'];
			}
		}
	if(array_key_exists($attribute_variant, $prices)){
		return $prices[$attribute_variant];
	}
	else{
		return false;
	}
}
function rvlvr_season_upsell(){
	//echo "<pre>";
		
		global $product;
		if($product->is_type( 'variable' )){
				
			$prices = array();
			foreach ($product->get_available_variations() as $variation){
				if(array_key_exists('attribute_pa_' . rvlvr_config()['rvlvr_attribute'], $variation['attributes'])){
					$prices[$variation['attributes']['attribute_pa_' . rvlvr_config()['rvlvr_attribute']]] = $variation['price_html'];
				}
			}
			
			$args = array(
				'post_type'             => 'season',
				'post_status'           => 'publish',
			);
			
			$seasons = get_posts($args);
			//echo "<pre>";
			//var_export($prices);
			//echo "</pre>";
			foreach ($seasons as $season){
				$season_attribute = get_post_meta($season->ID, 'rvlvr_season_attribute', true);
				
				if(array_key_exists($season_attribute, $prices) && !in_array(get_the_id(), rvlvr_config()['rvlvr_no_season_upsell'])){
					echo "<div class='season_upsell'><p>";
					__(printf('Behåll utrustning till %s, bara %s med säsongshyra', get_post_meta($season->ID, 'rvlvr_season_expires', true), strip_tags($prices[$season_attribute])), 'understrap');
					echo "</p></div>";
				}			
			}
		 }
		
		//var_export($product->get_available_variations());
		//var_export($prices);
	//echo "</pre>";
}


add_action('woocommerce_single_product_summary', 'rvlvr_season_upsell', 7 );


//// Season warning / shipping info

//add_action('woocommerce_single_product_summary', 'rvlvr_shipping_info', 60 );

function rvlvr_shipping_info(){
	global $product;
	if(!in_array(get_the_id(), rvlvr_config()['rvlvr_no_season_upsell'])){
		echo "<div class='shipping_info col-md-7'>"; 
		echo "<div><h2>" . __('Fraktinformation', 'understrap') . "</h2></div>";

		$shipping_cost = get_post_meta( get_the_ID(), 'shipping_cost', true);
		if(! empty( $shipping_cost) ){	
			echo '<p>Vid frakt med DHL är tur- & returfrakt för denna produkt <span class="shipping_per_product amount"> ' . $shipping_cost  . ':-</span></p>';
		}
		elseif ( $product->get_shipping_class() == 'gratis' ){
			echo '<p>Vid frakt med DHL är tur- & returfrakt för denna produkt <span class="shipping_per_product amount">gratis</span></p>';
		}
		
		rvlvr_season_warning($product);
		echo "</div>";
		echo "<div style='clear:both;'></div>";
	}
}

function rvlvr_season_warning($product){
	
	if($product->is_type( 'variable' )){
		foreach(rvlvr_config()['rvlvr_no_rent'] as $check){
			if( rvlvr_has_attribute_variant($product, $check)){
				echo "<p class='season_warning'><i>" . __("Obs! Vi erbjuder bara hemleverans på uthyrning per säsong", 'understrap') . "</i></p>";
				break;
			}
		}
	}
}




 // Attributes
 
 function isa_woocommerce_all_pa(){
 
    global $product;
    $attributes = $product->get_attributes();
	
    if ( ! $attributes ) {
        return;
    }
 
	echo"<pre>";
    foreach ( $attributes as $attribute ) {
        if ( $attribute['is_taxonomy'] ) {
            $terms = wp_get_post_terms( $product->get_the_id(), $attribute['name'], 'all' );
			foreach ( $terms as $term ) {
                if($term->description != null){ echo '<span class="attribute-value">' . $term->description . '</span><br /> '; }
            }
			echo "<pre>";
			//	var_export($terms);
			var_export(rvlvr_get_seasons());
			
			echo "</pre>";            
        }
    }
	echo"</pre>";
    
}
//add_action('woocommerce_single_product_summary', 'isa_woocommerce_all_pa', 25);
 
 
function rvlvr_print_attribute_description($attribute_name, $options){
	//echo "t:";
	//echo $attribute_name;
	//var_export($options);
	switch ($attribute_name) {
		case "pa_ski-length-senior":
			echo "<span class='reference'><a data-rel='prettyPhotocp' href='" . get_template_directory_uri()  .  "/media/skidlangd_senior_avancerad.png'>Visa tabell</a></span>";
			break;
		case "pa_ski-length-junior":
                        echo "<span class='reference'><a data-rel='prettyPhotocp' href='" . get_template_directory_uri()  .  "/media/skidlangd_junior_medel.png'>Visa tabell</a></span>";
			break;
		case "pa_alpine-boot-size":
                        echo "<span class='reference'><a data-rel='prettyPhotocp' href='" . get_template_directory_uri()  .  "/media/fotmatare.gif'>Så mäter du</a></span>";
			break;
		default:
			break;
	}
}	

//////////// Stores	

function sporthyra_map_load(){
	if ( is_singular('store')){                
        //return "hah";
        return 'onload="if(typeof initilize!==\'undefined\'){initilize(' . get_post_meta(get_the_id(), 'rvlvr_store_x', true) . ', ' . get_post_meta(get_the_id(), 'rvlvr_store_y', true) . ', \'' . get_bloginfo('template_directory') . '/media/map_point.png\')}"';
	}
 }
 

function sporthyra_fb_load(){
	if ( is_singular('store')){   
		return '<div id="fb-root"></div><script>(function(d, s, id) { var js, fjs = d.getElementsByTagName(s)[0]; if (d.getElementById(id)) return; js = d.createElement(s); js.id = id; js.src = "//connect.facebook.net/sv_SE/sdk.js#xfbml=1&version=v2.8&appId=457551254309184"; fjs.parentNode.insertBefore(js, fjs); }(document, "script", "facebook-jssdk"));</script>';
	}
}

function sporthyra_store_open($post){

	rvlvr_get_store_open_status($post);
}


/////////// Translations

add_filter( 'woocommerce_product_single_add_to_cart_text', 'woo_custom_cart_button_text' );    // 2.1 +
 
function woo_custom_cart_button_text() {
 
        return __( 'Lägg till', 'woocommerce' );
 
 
 
}
 
 /////////////// Checkout

//// Delivery date and other custom metas

// Add delivery date field to the checkout

add_action( 'woocommerce_before_order_notes', 'rvlvr_checkout_delivery_date' );

function rvlvr_earliest_delivery($fetch = false){
	if( preg_match('/' . rvlvr_config()['rvlvr_shipping_method_name'] . '/',WC()->session->get( 'chosen_shipping_methods' )[0]) || $fetch == 'shipping' ){
		$date = date('Y-m-d', strtotime(get_option( 'rvlvr_settings' )['rvlvr_delivery_shipping'] . "days"));
		return array( 'date' => $date, 'message' => 'Tidigaste datum för hemleverans är ' . $date);
	}
	else{
		$date = date('Y-m-d', strtotime(get_option( 'rvlvr_settings' )['rvlvr_delivery_pickup'] . "days"));
		return array( 'date' => $date, 'message' => 'Tidigaste datum för upphämtning är ' . $date);
		
	}
}

function rvlvr_checkout_delivery_date( $checkout ) {

    if(rvlvr_rent_in_cart()){
		echo "<div class='col-xs-12 form-content-margin'>";
			
			//var_export(WC()->session->get( 'chosen_shipping_methods' )[0]);
			
			//get_option( 'rvlvr_settings' )['rvlvr_delivery'];
			echo __('Obs! Tidigaste datum för hemleverans är', 'understrap') . " <b>" . rvlvr_earliest_delivery('shipping')['date']  . "</b>." ;
			

		echo "</div>";
		
		/*woocommerce_form_field( 'order_delivery_date', array(
			'type'          => 'text',
			'class'         => array(''),
			'id'			=> 'order_delivery_date',
			'label'         => __('Utlämningsdatum'),
			'placeholder'   => __('yyyy-mm-dd'),
			'required'		=> true,
			), $checkout->get_value( 'order_delivery_date' ));*/
		echo "<p class='form-row form-row validate-required col-xs-12 col-sm-6'>";
		echo "<label for='order_delivery_date'>" . __('Utlämningsdatum') . "</label>";
		echo "<input type='date' id='order_delivery_date' name='order_delivery_date' placeholder='yyyy-mm-dd' value='" . rvlvr_earliest_delivery()['date'] . "'>";
		echo "</p>";
		
		woocommerce_form_field( 'order_delivery_note', array(
			'type'          => 'text',
			'class'         => array('rvlvr_hidden'),
			'id'			=> 'order_delivery_note',
			), $checkout->get_value( 'order_delivery_note' ));
		
		foreach( WC()->cart->cart_contents as $prod_in_cart ) {
			if( array_key_exists('attribute_pa_' . rvlvr_config()['rvlvr_attribute'], $prod_in_cart['variation'])){
				$variants[$prod_in_cart['variation']['attribute_pa_' . rvlvr_config()['rvlvr_attribute']]] = '';
			}			
		}
		if(isset($variants)){
			foreach ($variants as $key=>$value){
				woocommerce_form_field( 'order_return_' . $key, array(
				'label' 		=> 'lol',
				'type'          => 'text',
				'class'         => array('rvlvr_hidden'),
				'id'			=> 'order_return_' . $key,
				), $checkout->get_value( 'order_return_' . $key ));
				//echo $key;
			}
		}
	}
	
}

// Validate order input
add_action('woocommerce_checkout_process', 'rvlvr_checkout_validate');

function rvlvr_checkout_validate() {
    
	///// Delivery dates
	// Rent in cart
	if ( rvlvr_rent_in_cart() ){	
		$earliest_delivery = rvlvr_earliest_delivery();
		// Date not supplied
		if( !$_POST['order_delivery_date'] ){
			wc_add_notice( __( 'Då måste ange ett utlämningsdatum.' ), 'error' );
		}
		
		// Date supplied
		else{
			$delivery_shipping = get_option( 'rvlvr_settings' )['rvlvr_delivery_shipping'];
			$delivery_pickup = get_option( 'rvlvr_settings' )['rvlvr_delivery_pickup'];
			
			//$delivery_date_shipping = date('Y-m-d', strtotime( $delivery_shipping . "days"));
			//$delivery_date_pickup = date('Y-m-d', strtotime( $delivery_pickup . "days"));
			
			$given_value = $_POST['order_delivery_date'];
			
			function validateDate($date){
				$d = DateTime::createFromFormat('Y-m-d', $date);
				return $d && $d->format('Y-m-d') === $date;
			}		
			
			// Bad date
			if( !validateDate($given_value)){
				wc_add_notice('Ange ett giltigt utlämningsdatum enligt yyyy-mm-dd', 'error');
			
			}
			// OK date
			else{
			
				$date_diff = floor( (strtotime($given_value) - strtotime($earliest_delivery['date'])) / (60 * 60 * 24) );
				// Shipping and to soon
				//wc_add_notice( $date_diff , 'error');
				if( $date_diff < 0 ){
					//wc_add_notice( $date_diff ,'error');
					wc_add_notice( $earliest_delivery['message'] , 'error');
				}
			}		
		}
	}
}
	

//// Add meta fields to the order
add_action( 'woocommerce_checkout_update_order_meta', 'rvlvr_order_meta'); 

function rvlvr_order_meta( $order_id ) {    
    if ( ! empty( $_POST['order_delivery_date'] ) ) {
        update_post_meta( $order_id, 'order_delivery_date', $_POST['order_delivery_date']  );
    }
	if ( ! empty( $_POST['order_delivery_note'] ) ) {
        update_post_meta( $order_id, 'order_delivery_note', $_POST['order_delivery_note']  );
    }
	foreach( WC()->cart->cart_contents as $prod_in_cart ) {
		if( array_key_exists('attribute_pa_' . rvlvr_config()['rvlvr_attribute'], $prod_in_cart['variation'])){
			$variants[$prod_in_cart['variation']['attribute_pa_' . rvlvr_config()['rvlvr_attribute']]] = '';
		}			
	}
	foreach ($variants as $key=>$value){
		update_post_meta( $order_id, 'order_return_' . $key, $_POST['order_return_' . $key]  );

	}
	if ( ! empty( $_POST['order_delivery_note'] ) ) {
        update_post_meta( $order_id, 'order_delivery_note', $_POST['order_delivery_note']  );
    }
	
	if ( ! empty( $_POST['pickup_location'][1] ) ) {
        update_post_meta( $order_id, 'pickup_location_1', $_POST['pickup_location'][0]  );
    }
	if ( ! empty( $_POST['pickup_location'][2] ) ) {
        update_post_meta( $order_id, 'pickup_location_2', $_POST['pickup_location'][1]  );
    }
}

// Add order meta to order page
add_action( 'woocommerce_admin_order_data_after_billing_address', 'rvlvr_display_admin_order_meta', 10, 1 );

function rvlvr_display_admin_order_meta($order){
    if(get_post_meta( $order->get_id(), 'order_delivery_date', true )){
		echo '<p><strong>'.__('Leveransdatum').':</strong> ' . get_post_meta( $order->get_id(), 'order_delivery_date', true ) . '</p>';
	}
	elseif(get_post_meta( $order->get_id(), 'Leveransdatum', true )){
		echo '<p><strong>'.__('Leveransdatum').':</strong> ' . get_post_meta( $order->get_id(), 'Leveransdatum', true ) . '</p>';
	}
	
	if(get_post_meta( $order->get_id(), 'order_delivery_note', true )){
		echo '<p><strong>'.__('Returdatum').':</strong><br /> ' . get_post_meta( $order->get_id(), 'order_delivery_note', true ) . '</p>';
	}
		
}

//// recipients dev

function rvlvr_add_recipients($recipient, $order){

	// Bail on WC settings pages since the order object isn't yet set yet
	// Not sure why this is even a thing, but shikata ga nai
	$page = $_GET['page'] = isset( $_GET['page'] ) ? $_GET['page'] : '';
	if ( 'wc-settings' === $page ) {
		return $recipient; 
	}
	
	// just in case
	if ( ! $order instanceof WC_Order ) {
		return $recipient; 
	}
	$locations[] = get_post_meta($order->id, 'pickup_location_1', true);
	$locations[] = get_post_meta($order->id, 'pickup_location_2', true);
	
	foreach ($locations as $id){
	// uncomment for store emails
		$args = array(
			'post_type' => 'store',
			'meta_query' => array(
				array(
				   'key' => 'rvlvr_store_local_pickup_id',
				   'value' => $id,
				   'compare' => '=',
			   )
		   )
		);
		$query = new WP_Query($args);
		if( $query->have_posts() ) {
		  while( $query->have_posts() ) {
			$query->the_post();
				$recipient .= ", " .get_post_meta(get_the_id(), 'rvlvr_store_order_email', true);
		  }
		} 
		wp_reset_query(); 
	}
	
	return $recipient;
}
//commented 2018-1 pending new pickup plus
add_filter( 'woocommerce_email_recipient_new_order', 'rvlvr_add_recipients', 10, 2 );

//// Better labels and such 
// Hook in

add_filter( 'woocommerce_checkout_fields' , 'custom_override_checkout_fields' );
//add_filter( 'woocommerce_default_address_fields' , 'custom_override_checkout_fields' );

// Our hooked in function - $fields is passed via the filter!
function custom_override_checkout_fields( $fields ) {

	$fields['order']['order_comments']['class'] = array('col-xs-12 col-sm-6');	 
	
	//$fields['shipping']['shipping_address_1']['label'] = 'Adressa';
	$fields['shipping']['shipping_address_1']['class'] = array('col-xs-12 col-sm-6');
	$fields['shipping']['shipping_address_2']['label'] = '&nbsp;';
	$fields['shipping']['shipping_address_2']['class'] = array('col-xs-12 col-sm-6');
	//$fields['shipping']['shipping_first_name']['label'] = 'Förnamn';
	$fields['shipping']['shipping_first_name']['class'] = array('col-xs-12 col-sm-6');
	//$fields['shipping']['shipping_last_name']['label'] = 'Efternamn';
	$fields['shipping']['shipping_last_name']['class'] = array('col-xs-12 col-sm-6');
	//$fields['shipping']['shipping_postcode']['label'] = 'Postnummer';
	$fields['shipping']['shipping_postcode']['class'] = array('col-xs-12 col-sm-6');
	//$fields['shipping']['shipping_city']['label'] = 'Stad';
	$fields['shipping']['shipping_city']['class'] = array('col-xs-12 col-sm-6');
	
	//$fields['billing']['billing_address_1']['label'] = 'Adress';
	$fields['billing']['billing_address_1']['class'] = array('col-xs-12 col-sm-6');
	$fields['billing']['billing_address_2']['label'] = '&nbsp;';
	$fields['billing']['billing_address_2']['class'] = array('col-xs-12 col-sm-6');
	//$fields['billing']['billing_first_name']['label'] = 'Förnamn';
	$fields['billing']['billing_first_name']['class'] = array('col-xs-12 col-sm-6');
	//$fields['billing']['billing_last_name']['label'] = 'Efternamn';
	$fields['billing']['billing_last_name']['class'] = array('col-xs-12 col-sm-6');
	//$fields['billing']['billing_postcode']['label'] = 'Postnummer';
	$fields['billing']['billing_postcode']['class'] = array('col-xs-12 col-sm-6');
	//$fields['billing']['billing_city']['label'] = 'Stad';
	//$fields['billing']['billing_city']['class'] = array('col-xs-12 col-sm-6');
	//$fields['billing']['billing_email']['class'] = array('col-xs-12 col-sm-6');
	//$fields['billing']['billing_phone']['class'] = array('col-xs-12 col-sm-6');
	$fields['billing']['billing_personnr'] = array( 'label' => __('Personnummer', 'woocommerce'), 'placeholder'   => __('yyyymmddnnnn', 'placeholder', 'woocommerce'), 'required'  => true,	'clear'=> true ,'class' => array('col-xs-12 col-sm-6'));
	
	$fields['account']['account_username']['class'] = array('col-xs-12 col-sm-6');		
	$fields['account']['account_password']['class'] = array('pull-right col-xs-12 col-sm-6');	 
	$fields['account']['account_username']['label'] = __('Användarnamn', 'understrap');	 	
	//$fields['account']['account_password-2']['label'] = __('Upprepa lösenord', 'understrap');	 	
	// Hook in
  
	//unset($fields['billing']['billing_phone']);
	//unset($fields['billing']['billing_email']);
	unset( $fields['shipping']['shipping_personnr'] );
	$fields['order']['order_comments']['placeholder'] = 'Fraktinstruktioner';		

	unset($fields['billing']['billing_company']);
	
	return $fields;

}

//// Add email to frormated billing address, display personnr on order page
//add_filter( 'woocommerce_order_formatted_billing_address' , 'rvlvr_add_email_to_formatted_billing' );

function rvlvr_add_email_to_formatted_billing() {
	
	$address = array(
		'last_name'		=> $this->billing_last_name,
		'first_name'	=> $this->billing_first_name,
		'last_name'		=> $this->billing_personnr,
		'company'		=> $this->billing_company,
		'address_1'		=> $this->billing_address_1,
		'address_2'		=> $this->billing_address_2,
		'city'			=> $this->billing_city,
		'state'			=> $this->billing_state,
		'postcode'		=> $this->billing_postcode,
		'country'		=> $this->billing_country
	);
	return $address;
}

/**
 * Display field value on the order edit page
 */
 

//// Check if all billing fields are set for customer
function rvlvr_customer_has_billing_fields(){
		
		$customer_id = get_current_user_id();
		$name='billing';
		$address = array(	
			'country'  => get_user_meta( $customer_id, $name . '_country', true ),
			'first_name'  => get_user_meta( $customer_id, $name . '_first_name', true ),
			'last_name'   => get_user_meta( $customer_id, $name . '_last_name', true ),
			'address_1'   => get_user_meta( $customer_id, $name . '_address_1', true ),
			'city'        => get_user_meta( $customer_id, $name . '_city', true ),
			'postcode'    => get_user_meta( $customer_id, $name . '_postcode', true ),
			'phone'    	  => get_user_meta( $customer_id, $name . '_phone', true ),
			'personnr'    => get_user_meta( $customer_id, $name . '_personnr', true )
		);
		$check = array(
			'country'=>'', 
			'first_name'=>'', 
			'last_name'=>'', 
			'address_1'=>'', 
			'city'=>'', 
			'postcode'=>'', 
			'phone'=>'',
			'personnr'=>'',
			
		);
		/*echo "<pre>";
		var_export($address);
		var_export($check);
		echo "</pre>";*/
		if(array_search("",$address) || count($check) != count($address)){
			return false;
		
		}
		else{
			return true;
		}
}

//// Get billing fields
function rvlvr_get_customer_billing_fields(){
	$customer_id = get_current_user_id();
	$name='billing';
	$address = array(	
		//'country'  => array(get_user_meta( $customer_id, $name . '_country', true ), 'country'),
		//'name'  => array('value' => get_user_meta( $customer_id, $name . '_first_name', true ) . " " .get_user_meta( $customer_id, $name . '_last_name', true ) , 'class' => 'name', 'title' => 'Namn' ),
		'address_1'   => array('value' => get_user_meta( $customer_id, 'billing_address_1', true ), 'class' => 'col-xs-12 col-sm-6', 'title' => 'Gatuadress'),
		'city'        => array('value' => get_user_meta( $customer_id, 'billing_postcode', true ) . " " . get_user_meta( $customer_id, $name . '_city', true ), 'class' => 'col-xs-12 col-sm-6', 'title' => 'Postadress'),
		'phone'    	  => array('value' => get_user_meta( $customer_id,  'billing_phone', true ), 'class' => 'col-xs-12 col-sm-6', 'title' => 'Telefonnummer'),
		'personnr' => array('value' => get_user_meta( $customer_id, 'billing_personnr', true ), 'class' => 'col-xs-12 col-sm-6', 'title' => 'Personnummer'),
		'email'    	  => array('value' => get_user_meta( $customer_id, 'billing_email', true ), 'class' => 'col-xs-12', 'title' => 'Epostadress')
		);
	return $address;
}


//// Check if there is any equipment in cart
function rvlvr_rent_in_cart(){
	$cat = get_option('rvlvr_settings')['rvlvr_equipment_cat'];
	//var_export(WC()->cart->get_cart());
	// check each cart item for our category
	
	foreach ( WC()->cart->get_cart() as $values ) {

	
	$terms = get_the_terms( $values['product_id'], 'product_cat' );

		// second level loop search, in case some items have several categories
		foreach ($terms as $term) {
			
			$_categoryid = $term->term_id;
			
			if ( get_ancestors( $_categoryid, 'product_cat' )[0] == $cat ) {
				//category is in cart!
				//echo "true";
				$rent = true;
				
			}
			else{ 
				

			}
		}
	
	}
	if($rent == true){ return true; } else { return false; }
}

function is_rent($product){
	$cat = get_option('rvlvr_settings')['rvlvr_equipment_cat'];

	// check each cart item for our category
	
	$terms = get_the_terms( $product->get_id(), 'product_cat' );

		// second level loop search, in case some items have several categories
		foreach ($terms as $term) {
			
			$_categoryid = $term->term_id;
			
			if ( get_ancestors( $_categoryid, 'product_cat' )[0] == $cat ) {
				//category is in cart!
				return true;
			}else{ 
				return false;
			}
		}

	
}

function kia_woocommerce_order_item_name( $name, $item ){ 

   $product_id = $item['product_id'];

   $tax = 'product_cat'; 
   $terms = wp_get_post_terms( $product_id, $tax, array( 'fields' => 'names' ) ); 
   if( $terms && ! is_wp_error( $terms )) {
       $taxonomy = get_taxonomy($tax);
       $new_name = "<strong>" . implode( ', ', $terms ) . " > </strong>  " . $name;
   } 
   return $new_name;
}
add_filter( 'woocommerce_order_item_name', 'kia_woocommerce_order_item_name', 10, 2 );



///////////// My account


function rvlvr_config(){
	return array(
		'rvlvr_shipping_method_name' => 'flat_rate',
		'rvlvr_no_season_upsell' => array( 
			'3755'
		),
		'rvlvr_no_shipping_info' => array(
			'3755'
		),
		'rvlvr_attribute' => 'rvlvr-condition',
		'rvlvr_no_rent' => array('rent-1_day', 'rent-2_days', 'rent-3_days', 'rent-4_days', 'rent-week', 'rent-1_month', 'rent-3_months'),
		'rvlvr_all_attributes' => array(
			array(
				'attribute' => 'rent-1_day', 
				'title' => '1 dag'),
			array(
				'attribute' => 'rent-2_days',
				'title' => '2 dagar'),
			array( 
				'attribute' => 'rent-3_days',
				'title' => '3 dagar'),
			array(
				'attribute' => 'rent-4_days',
				'title' => '4 dagar'), 
			array(
				'attribute' => 'rent-week',
				'title' => '5-8 dagar'),
			array(
				'attribute' => 'rent-1_month',
				'title' => '1 månad'),
			array(
				'attribute' => 'rent-3_months',
				'title' => '3 månader'),
			array(
				'attribute' => 'rent-season-summer',
				'title' => 'Säsong'), 
			array(
				'attribute' => 'rent-season-winter',
				'title' => 'Säsong')/*, 
			array(
				'attribute' => 'buy-new',
				'title' => 'Nytt'),
			array(
				'attribute' => 'buy-used',
				'title' => 'Begagnat')*/
		)
	);

}
add_filter("woocommerce_default_address_fields", "order_address_fields");

function order_address_fields($fields) {
	
	$fields['address_1']['class'] = array('col-xs-12 col-sm-6');
	$fields['address_2']['label'] = '&nbsp;';
	$fields['address_2']['class'] = array('col-xs-12 col-sm-6');
	//$fields['shipping']['shipping_first_name']['label'] = 'Förnamn';
	$fields['first_name']['class'] = array('col-xs-12 col-sm-6');
	//$fields['shipping']['shipping_last_name']['label'] = 'Efternamn';
	$fields['last_name']['class'] = array('col-xs-12 col-sm-6');
	//$fields['shipping']['shipping_postcode']['label'] = 'Postnummer';
	$fields['postcode']['class'] = array('col-xs-12 col-sm-6');
	//$fields['shipping']['shipping_city']['label'] = 'Stad';
	$fields['city']['class'] = array('col-xs-12 col-sm-6');
	$fields['country']['class'] = array('rvlvr_hidden');
	unset($fields['company']);
	
	
	$fields['personnr'] = array (
			'label' => 'Personnummer',
			'required' => false,
			'placeholder' => 'yyyymmddnnnn',
			'class' =>			
				array (
				0 => 'col-sm-6 form-row-first',
				)
	);
	
	
    return $fields;
}

//////// Emails

function custom_gift_card__order_meta_keys($fields, $sent_to_admin, $order ) {
	$fields['personnr'] = array(
	'label' => "<span class='h3'>" . __('Personnr', 'woocommerce') . "</h3>",
	'placeholder' => _x('', 'placeholder', 'woocommerce'),
	'required' => true,
	'class' => array('form-row-last'),
	'clear' => true
	);
	$fields["personnr"]["value"] = get_user_meta($order->get_user_id(), 'billing_personnr', true) ;
	//$fields["gift_card_comments"]["value"] = get_post_meta( $order->id, 'Gift Card Comments', true );
	return $fields;
}
add_filter( 'woocommerce_email_customer_details_fields', 'custom_gift_card__order_meta_keys', 40, 3);


function custom_woocommerce_email_order_meta_fields( $fields, $sent_to_admin, $order ) {
    $fields['order_delivery_date'] = array(
        'label' => 'Leveransdatum',
        'value' => get_post_meta( $order->get_id(), 'order_delivery_date', true ),
    );
    $fields['order_delivery_notes'] = array(
        'label' => 'Retur av utrustning',
        'value' => get_post_meta( $order->get_id(), 'order_delivery_note', true ),
    );
	

    return $fields;
}
add_filter( 'woocommerce_email_order_meta_fields', 'custom_woocommerce_email_order_meta_fields', 100, 3 );


//xmlrpc security fix
add_filter('xmlrpc_enabled', '__return_false');


// Customise functionality of the default Intercom Wordpress plugin 
// https://github.com/intercom/intercom-wordpress

// customise the current Intercom script
//   - add custom attributes  https://docs.intercom.io/configuring-intercom/send-custom-user-attributes-to-intercom
//   - add custom activator   https://docs.intercom.io/configuring-Intercom/in-app-messenger#custom-style
function customise_intercom(){
	// $current_user = wp_get_current_user(); // get current Wordpress user if needed
	echo "\n".'<script>';
        // ensure this is defined, i.e. don't break if Intercom plugin not installed 
	echo "\n".'window.intercomSettings = window.intercomSettings || {};'; 
	
	// specify custom attributes
	// echo "\n".'window.intercomSettings["test_string"] = "123";';
	// echo "\n".'window.intercomSettings["test_string"] = "' . encodeAppropriately($current_user->display_name). '";'; // encodeAppropriately() is a custom function you will need to write or replace and is to ensure that it doesn't break the Javascript code. Escaping double quotes should be sufficient
        // widget activator old messenger
	// echo "\n".'window.intercomSettings.widget = {"activator":"#idOfActivatorElement"};';
	
	// use a customer element as a launcher
	 echo "\n".'window.intercomSettings["custom_launcher_selector"] = ".intercom_launch";';
	// hide the messenger 
	// echo "\n".'window.intercomSettings["hide_default_launcher"] = true;';
	echo "\n".'</script>'."\n";
}

add_action('wp_footer', 'customise_intercom',20); 
add_action('admin_footer', 'add_intercom_snippet');
add_action('admin_footer', 'customise_intercom');

/*
 * Add google analytics to footer
 */

add_action('wp_footer', 'add_googleanalytics');
function add_googleanalytics() { ?>
<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-86276092-1', 'auto');
  ga('send', 'pageview');

</script>
<?php } ?>

<?php
function custom_login_page() {
 $new_login_page_url = home_url( 'mina-sidor/' ); // new login page
 global $pagenow;
 if( $pagenow == "wp-login.php" && $_SERVER['REQUEST_METHOD'] == 'GET') {
    wp_redirect($new_login_page_url);
    exit;
 }
}

if(!is_user_logged_in()){
 add_action('init','custom_login_page');
}


/** 
 * Register new status
 * Tutorial: http://www.sellwithwp.com/woocommerce-custom-order-status-2/
**/
function register_returned_order_status() {
    register_post_status( 'wc-returned', array(
        'label'                     => 'Returnerad',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'Returnerade <span class="count">(%s)</span>', 'Returnerade <span class="count">(%s)</span>' )
    ) );
}
add_action( 'init', 'register_returned_order_status' );

// Add to list of WC Order statuses
function add_returned_to_order_statuses( $order_statuses ) {

    $new_order_statuses = array();

    // add new order status after processing
    foreach ( $order_statuses as $key => $status ) {

        $new_order_statuses[ $key ] = $status;

        if ( 'wc-completed' === $key ) {
            $new_order_statuses['wc-returned'] = 'Returnerad';
        }
    }

    return $new_order_statuses;
}
add_filter( 'wc_order_statuses', 'add_returned_to_order_statuses' );

add_action('admin_head', 'my_add_status_icons');

function my_add_status_icons() {
	echo "<style>
	
		.column-order_status mark.returned::after {
			font-family: WooCommerce;
    		speak: none;
    		font-weight: 400;
    		font-variant: normal;
    		text-transform: none;
    		line-height: 1;
    		-webkit-font-smoothing: antialiased;
    		margin: 0;
    		text-indent: 0;
    		position: absolute;
    		top: 0;
    		left: 0;
    		width: 100%;
    		height: 100%;
	   	 	text-align: center;
	   	 	color: #73a724;
			content: '\\e015'; 
		}
		.column-order_status mark.failed::after{
 	   	 	color: #a00 !important;
 		}
		.column-order_status mark.processing::after{
 	   	 	color: #ffba00 !important;
 		}

		</style>"; 

}


?>
