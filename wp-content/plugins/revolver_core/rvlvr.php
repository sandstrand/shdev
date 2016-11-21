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




?>