<?php
// Uses the Options API to create options page 

add_action( 'admin_menu', 'rvlvr_add_admin_menu' );
add_action( 'admin_init', 'rvlvr_settings_init' );

function rvlvr_add_admin_menu(  ) { 

	add_menu_page( 'Revolver', 'Revolver', 'manage_options', 'revolver', 'rvlvr_options_page' );
	
}

function rvlvr_settings_init(  ) { 
	
	register_setting( 'pluginPage', 'rvlvr_settings' );

	add_settings_section(
		'rvlvr_pluginPage_section', 
		__( 'Navigation and products', 'rvlvr' ), 
		'rvlvr_settings_section_callback', 
		'pluginPage'
	);
	add_settings_field( 
		'rvlvr_select_season', 
		__( 'Select the current season', 'rvlvr' ), 
		'rvlvr_select_season_render', 
		'pluginPage', 
		'rvlvr_pluginPage_section' 
	);
	add_settings_field( 
		'rvlvr_text_field_equipment_cat', 
		__( 'CategoryID for sesonal equipment, rent', 'rvlvr' ), 
		'rvlvr_text_field_equipment_cat_render', 
		'pluginPage', 
		'rvlvr_pluginPage_section' 
	);
	add_settings_field( 
		'rvlvr_text_field_equipment_cat_slug', 
		__( 'CategorySlug for sesonal equipment, rent', 'rvlvr' ), 
		'rvlvr_text_field_equipment_cat_slug_render', 
		'pluginPage', 
		'rvlvr_pluginPage_section' 
	);

	add_settings_field( 
		'rvlvr_text_field_product_cat', 
		__( 'CategoryID for products, buy', 'rvlvr' ), 
		'rvlvr_text_field_product_cat_render', 
		'pluginPage', 
		'rvlvr_pluginPage_section' 
	);
	add_settings_field( 
		'rvlvr_text_field_product_cat_slug', 
		__( 'CategorySlug for products, buy', 'rvlvr' ), 
		'rvlvr_text_field_product_cat_slug_render', 
		'pluginPage', 
		'rvlvr_pluginPage_section' 
	);
	add_settings_field( 
		'rvlvr_text_field_delivery', 
		__( 'Leveranstid Leverans / Pickup', 'rvlvr' ), 
		'rvlvr_text_field_delivery_render', 
		'pluginPage', 
		'rvlvr_pluginPage_section' 
	);

	add_settings_field( 
		'rvlvr_store_map', 
		__( 'Attachment ID för karta', 'rvlvr' ), 
		'rvlvr_text_field_page_bg_render', 
		'pluginPage', 
		'rvlvr_pluginPage_section' 
	);
}

function rvlvr_select_season_render(  ) { 

	$options = get_option( 'rvlvr_settings' );
	
	// Fetch all seasons and check for current
	//var_export(rvlvr_get_seasons('current'));
	?>
	<select name='rvlvr_settings[rvlvr_select_season]'>
		<?php foreach(rvlvr_get_seasons('all') as $season){  ?>	
				<option value='<?= $season['ID'] ?>' <?=(rvlvr_get_seasons('current')[0]['ID'] == $season['ID']) ? 'selected' : '' ?>><?= $season['title'] ?></option>
		<?php }	?>
	</select>
	
<?php

}

function rvlvr_text_field_equipment_cat_render(  ) { 

	$options = get_option( 'rvlvr_settings' );
	?>
	<input type='text' name='rvlvr_settings[rvlvr_equipment_cat]' value='<?php echo $options['rvlvr_equipment_cat']; ?>'>
	<?php

}

function rvlvr_text_field_equipment_cat_slug_render(  ) { 

	$options = get_option( 'rvlvr_settings' );
	?>
	<input type='text' name='rvlvr_settings[rvlvr_equipment_cat_slug]' value='<?php echo $options['rvlvr_equipment_cat_slug']; ?>'>
	<?php

}


function rvlvr_text_field_product_cat_render(  ) { 

	$options = get_option( 'rvlvr_settings' );
	?>
	<input type='text' name='rvlvr_settings[rvlvr_products_cat]' value='<?php echo $options['rvlvr_products_cat']; ?>'>
	<?php

}

function rvlvr_text_field_product_cat_slug_render(  ) { 

	$options = get_option( 'rvlvr_settings' );
	?>
	<input type='text' name='rvlvr_settings[rvlvr_products_cat_slug]' value='<?php echo $options['rvlvr_products_cat_slug']; ?>'>
	<?php

}

function rvlvr_text_field_delivery_render(  ) { 

	$options = get_option( 'rvlvr_settings' );
	?>
	<input type='text' name='rvlvr_settings[rvlvr_delivery_shipping]' value='<?php echo $options['rvlvr_delivery_shipping']; ?>'>
		<input type='text' name='rvlvr_settings[rvlvr_delivery_pickup]' value='<?php echo $options['rvlvr_delivery_pickup']; ?>'>
	<?php

}

function rvlvr_text_field_page_bg_render(  ) { 

	$options = get_option( 'rvlvr_settings' );
	?>
		<input type='text' name='rvlvr_settings[rvlvr_store_map]' value='<?php echo $options['rvlvr_store_map']; ?>'>
		
		

	<?php
		//echo get_current_screen()->id;

}


function rvlvr_text_field_3_render(  ) { 



}




function rvlvr_settings_section_callback(  ) { 

	echo __( 'Settings for Revolver store; seasons, and init settings etc.', 'rvlvr' );

}


function rvlvr_options_page(  ) { 
	
	?>
	<form action='options.php' method='post'>

		<?php
		settings_fields( 'pluginPage' );
		do_settings_sections( 'pluginPage' );
		submit_button();

		?>

	</form>
	<?php

}

?>