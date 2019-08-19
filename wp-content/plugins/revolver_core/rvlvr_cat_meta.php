<?php
// Adapted form online solution https://php.quicoto.com/add-metadata-categories-wordpress/
// Handles editing and saving seasons for product categories

function rvlvr_season_edit_season_category_field( $term ){
	
	$seasons = rvlvr_get_seasons('all');
	//var_export($seasons);
	if(isset($term->term_id)){
	
		$term_id = $term->term_id;
	
		$term_meta = get_option( "taxonomy_$term_id" );         
		$rvlvr_tax_seasons = get_option( "rvlvr_tax_seasons_$term_id" );
		//$term_season = get_option( "rvlvr_cat_tax_$term
	}
	else{
		$term_meta = NULL;
		$rvlvr_tax_seasons = NULL;
	}
	//$rvlvr_tax_seasons = get_option( "rvlvr_tax_seasons_$term_id" );
	//var_export($rvlvr_tax_seasons);
?>
	<tr class="form-field">
        <th scope="row">
	
			<label for="term_meta[season]"><?php echo _e('Seasons') ?></label>
            <td><?php
				foreach($seasons as $season){  ?>
					
					<input type='checkbox' name='rvlvr_tax_season_<?= $season['ID'] ?>' value='<?= $season['ID'] ?>' <?=($rvlvr_tax_seasons && in_array($season['ID'], $rvlvr_tax_seasons)) ? 'checked': ''?>> <?= $season['title'] ?><br>				
				<?php }
				?>
            </td>
        </th>
    </tr>
<?php
} 

// Save the field
    
function rvlvr_get_tax_seasons($term_id){
	return get_option( "rvlvr_tax_seasons_$term_id" );
}
	
function rvlvr_season_save_tax_meta( $term_id ){ 

	$seasons = rvlvr_get_seasons('all');
	$metadata = array();
	foreach($seasons as $season){
	
		$postdata = "rvlvr_tax_season_" . $season['ID'];
		if( isset ( $_POST[$postdata])){
			$metadata[] = intval($_POST[$postdata]);
		}	
	}
	update_option( "rvlvr_tax_seasons_$term_id", $metadata ); 
} 
	 
// Add and save for edit categories

add_action( 'product_cat_edit_form_fields', 'rvlvr_season_edit_season_category_field' ); 	
add_action( 'edited_product_cat', 'rvlvr_season_save_tax_meta', 10, 2 ); 
    

// Add and save for new categories
 
add_action( 'product_cat_add_form_fields', 'rvlvr_season_edit_season_category_field' );
add_action( 'create_product_cat', 'rvlvr_season_save_tax_meta', 10, 2 );     
 
// Add column to Category list
	
	function rvlvr_season_season_category_columns($columns)
	{
	    return array_merge($columns, 
	              array('season' =>  __('Season')));
	}
	
	add_filter('manage_edit-product_cat_columns' , 'rvlvr_season_season_category_columns'); 
	
// Add the value to the column
 
function rvlvr_season_season_category_columns_values( $deprecated, $column_name, $term_id) {
 
	if($column_name === 'season'){ 
		//echo rvlvr_get_tax_seasons($term_id);
		$term_meta = get_option( "rvlvr_tax_seasons_$term_id" );
		//var_export($term_meta);
		if($term_meta){
			foreach($term_meta as $season){
				echo get_the_title($season) . ", ";
			}
		}
		
		
		
		/*if($term_meta['season'] === 0){	
			echo _e('Both');
		}elseif($term_meta['season'] === 1){
			echo _e('Winter');
		}elseif($term_meta['season'] === 2){
			echo _e('Summer');
		}*/

	}
}
 
add_action( 'manage_product_cat_custom_column' , 'rvlvr_season_season_category_columns_values', 10, 3 );	
?>