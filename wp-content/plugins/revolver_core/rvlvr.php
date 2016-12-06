<?php
/** 
 * Plugin Name: Revolver Core
 * Plugin URI:  -
 * Description: Core functions for Revolver.
 * Version:     1.0.0
 * Author:      Jan Sandstroem
 * Author URI:  https://legofarmen.se
 * Text Domain: rvlvr
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

require('rvlvr_options.php'); // Add options page
require('rvlvr_cat_meta.php'); // Add season on product categories
require('rvlvr_post_types.php'); // Add post types support, admin pages, templates and widgets
//require('rvlvr_social.php'); // Add social widget
require('rvlvr_search.php'); // Add search forms and widget

//replace currency symbol
add_filter('woocommerce_currency_symbol', 'change_existing_currency_symbol', 10, 2);

function change_existing_currency_symbol( $currency_symbol, $currency ) {
     switch( $currency ) {
          case 'SEK': $currency_symbol = ':-'; break;
     }
     return $currency_symbol;
}

add_filter( 'post_thumbnail_html', 'remove_thumbnail_dimensions', 10, 3 );

function remove_thumbnail_dimensions( $html, $post_id, $post_image_id ) {
    $html = preg_replace( '/(width|height)=\"\d*\"\s/', "", $html );
    return $html;
}

function the_content_filter($content) {
    $block = join("|",array("one_third", "team_member"));
    $rep = preg_replace("/(<p>)?\[($block)(\s[^\]]+)?\](<\/p>|<br \/>)?/","[$2$3]",$content);
    $rep = preg_replace("/(<p>)?\[\/($block)](<\/p>|<br \/>)?/","[/$2]",$rep);
return $rep;
}
add_filter("the_content", "the_content_filter");







/** 
* 	Print all prices
*
*/


add_shortcode( 'rvlvr_all_prices', 'rvlvr_all_prices_callback' );

function rvlvr_all_prices_callback() {

	$topcat = get_option('rvlvr_settings')['rvlvr_equipment_cat'];
	$list = rvlvr_get_categories($topcat);

	function rvlvr_get_attributes($season_attribute, $product = false){
		$attributes = rvlvr_config()['rvlvr_all_attributes'];
		$new_attributes =array();
		foreach($attributes as $attribute){
			if(strpos($attribute['attribute'], 'season') == false || $attribute['attribute'] == $season_attribute){
				$new_attributes[] =array(
					'attribute' => $attribute['attribute'],
					'title' => $attribute['title']
				);
			}					
		}
		return $new_attributes;
	}
	
	function rvlvr_get_attribute_price($attribute, $product){
		if(!$product){
			$price = "noprice";
		}
		else{
			//$find_variant = 'attribute_pa_' . $attribute;
			//$terms = get_terms(sanitize_title('pa_rvlvr-condition'));
			$prices = array();
			//var_export($product);
			//echo "a: " . $product->slug . " :b ";
			
			if($product->product_type == 'variable' && $product->get_attribute('pa_rvlvr-condition')) {
				//var_export($product->get_available_variations());							
				foreach ($product->get_available_variations() as $variation){
					$prices[] = $variation['attributes']['attribute_pa_rvlvr-condition'] . "  <br /><b>" . $variation['display_price'] . "</b>";		
				}
			}
			else{
				$prices[]="missed";
			}
	
		}
		//return $product->product_type;
		return $prices;		
	}
	

	ob_start();
	echo "<div class='all_prices'>";
	foreach(rvlvr_get_seasons('all') as $season){
		//var_export($season);
		
		
		
		echo "<h3>Hyrutrustning " . $season['title'] . "</h3>";
		echo "<p>Säsongen varar till " . $season['expire'] . "</p>";


		//$season = $season['ID'];

		echo "<div class=''>";
		
		echo "<ul class='rvlvr_menu_categories'>";
		//var_export($season);
		if($season){
			//echo "season";
			foreach($list as $item){

				$item_id = $item->term_id;
				$cat_seasons = get_option( "rvlvr_tax_seasons_$item_id" );
				//var_export($cat_seasons);
				//var_export($season);
				if($cat_seasons && in_array($season['ID'], $cat_seasons)){
				
					echo "<li class='category'>";						
						echo "<div class='row categories'>";
								
							echo "<div class='col-sm-4'>";	
								echo "<h2><a href='" .get_category_link($item->term_id) . "'>" .  $item->name . "</a></h2>";
							echo "</div>";

							foreach(rvlvr_get_attributes($season['attribute']) as $attribute){
								echo "<div class='col-sm-1 hidden-xs-down attribute_header'>" . $attribute['title'] . "</div>"; 
							}
									
						echo "</div>";
					
						$args = array(
							'post_type'             => 'product',
							'post_status'           => 'publish',
							'ignore_sticky_posts'   => 1,
							'posts_per_page'   		=> 100,
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
									'field' 		=> 'term_id', //This is optional, as it defaults to 'term_id'
									'terms'         => $item->term_id,
									'operator'      => 'IN' // Possible values are 'IN', 'NOT IN', 'AND'.
								)
							)
						);
						$products = get_posts($args);	
						//var_export($products);
						//echo $season['attribute'];
						echo "<ul>";
						foreach($products as $product){
							$product = wc_get_product($product->ID);
							$variations = $product->get_available_variations();
							$variation_price = array();
							//var_export($variations);
							foreach($variations as $variation){
								$variation_price[$variation['attributes']['attribute_pa_rvlvr-condition']] = $variation['display_price'];
							}
							//var_export($variation_price);
							echo "<li>";
								echo "<div class='row'>";			
								
									echo "<div class='col-sm-4 product'>";
										echo "<span class='hidden-sm-up'>" .  $item->name . " > </span><a class='' href='" . $product->get_permalink(). "'>";
										//var_export($item);
										echo $product->get_title();
										echo "</a>";
									echo "</div>";
										 
									if($product->product_type == 'variable' && $product->get_attribute('pa_rvlvr-condition')) {
										//var_export($product->get_available_variations());							
										foreach (rvlvr_config()['rvlvr_all_attributes'] as $attribute){
											if(strpos($attribute['attribute'], 'season') == false || $attribute['attribute'] == $season['attribute']){	
												
												//var_export($season);
												//echo $attribute['attribute'];
												//echo  $attribute['attribute'] . ": <br /> ";
												if(array_key_exists($attribute['attribute'], $variation_price)){
													echo "<div class='hidden-sm-up col-xs-8'>" . $attribute['title'] . "</div>";
													echo "<div class='col-sm-1 col-xs-4 attribute_price'>";
														echo  $variation_price[$attribute['attribute']] . ":-";
													echo "</div>";
												}
												else{
													echo "<div class='hidden-xs-down col-sm-1 col-xs-4 attribute_price'>";
														echo "-";
													echo "</div>";
												}
												
												//$prices[] = $variation['attributes']['attribute_pa_rvlvr-condition'] . "  <br /><b>" . $variation['display_price'] . "</b>";		
											}
										}
									}
									else{
										echo "<div class='col-xs-5'>"; 
											echo "<i>Produken är ej variabel eller har ej rätt attribut.</i>";
										echo "</div>"; 
									}
								echo "</div>";
							echo "</li>";
						}
						echo "</ul>";				
					echo "</li>";
				}
			}
		}

		echo "</ul>";
	echo "</div>";
	}
	echo "</div>";
	wp_reset_postdata();
	return ob_get_clean();
}




?>