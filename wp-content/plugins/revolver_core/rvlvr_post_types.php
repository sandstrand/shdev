<?php

/*
 * Post type stores:
 *
 */
 
// Register Custom Post Type: Store
function custom_post_type_store() {

	$labels = array(
		'name'                  => _x( 'Stores', 'Post Type General Name', 'rvlvr' ),
		'singular_name'         => _x( 'Store', 'Post Type Singular Name', 'rvlvr' ),
		'menu_name'             => __( 'Stores', 'rvlvr' ),
		'name_admin_bar'        => __( 'Stores', 'rvlvr' ),
		'archives'              => __( 'Stores', 'rvlvr' ),
		'parent_item_colon'     => __( '', 'rvlvr' ),
		'all_items'             => __( 'All stores', 'rvlvr' ),
		'add_new_item'          => __( 'Add new store', 'rvlvr' ),
		'add_new'               => __( 'Add new', 'rvlvr' ),
		'new_item'              => __( 'New store', 'rvlvr' ),
		'edit_item'             => __( 'Edit store', 'rvlvr' ),
		'update_item'           => __( 'Update store', 'rvlvr' ),
		'view_item'             => __( 'View store', 'rvlvr' ),
		'search_items'          => __( 'Search store', 'rvlvr' ),
		'not_found'             => __( 'Not found', 'rvlvr' ),
		'not_found_in_trash'    => __( 'Not found in Trash', 'rvlvr' ),
		'featured_image'        => __( 'Featured Image', 'rvlvr' ),
		'set_featured_image'    => __( 'Set featured image', 'rvlvr' ),
		'remove_featured_image' => __( 'Remove featured image', 'rvlvr' ),
		'use_featured_image'    => __( 'Use as featured image', 'rvlvr' ),
		'insert_into_item'      => __( 'Insert into store', 'rvlvr' ),
		'uploaded_to_this_item' => __( 'Uploaded to this store', 'rvlvr' ),
		'items_list'            => __( 'Stores list', 'rvlvr' ),
		'items_list_navigation' => __( 'Stores list navigation', 'rvlvr' ),
		'filter_items_list'     => __( 'Filter stores list', 'rvlvr' ),
	);
	$args = array(
		'label'                 => __( 'Store', 'rvlvr' ),
		'description'           => __( 'Post types for all Revolver stores', 'rvlvr' ),
		'labels'                => $labels,
		'supports'              => array( 'title', 'editor', 'thumbnail', 'custom-fields', ),
		'hierarchical'          => true,
		'public'                => true,
		'show_ui'               => true,
		'show_in_menu'          => true,
		'menu_position'         => 126,
		'menu_icon'             => 'dashicons-store',
		'show_in_admin_bar'     => true,
		'show_in_nav_menus'     => true,
		'can_export'            => true,
		'has_archive'           => true,		
		'exclude_from_search'   => false,
		'publicly_queryable'    => true,
		'capability_type'       => 'page',
	);
	register_post_type( 'store', $args );

}
add_action( 'init', 'custom_post_type_store', 0 );

add_action( 'admin_init', 'store_admin' );

function store_admin() {
    add_meta_box( 'store_box',
        __('Store settings', 'rvlvr'),
        'display_store_box',
        'store', 'normal', 'high'
    );
}
function display_store_box( $post ) {

	$day_status = array( 0 => __('open', 'sporthyra'), 1 => __('closed', 'sporthyra'), 2 => __('special', 'sporthyra')) ?>			
	<label for="rvlvr_store_address"><?php _e( 'Adress', 'rvlvr' ); ?></label>
	<input type="text" name="rvlvr_store_address" value="<?php echo esc_html( get_post_meta( $post->ID, 'rvlvr_store_address', true ) )?>" /><br />
	<label for="rvlvr_store_zipcode"><?php _e( 'Postnummer', 'rvlvr' ); ?></label>
	<input type="text" name="rvlvr_store_zipcode" value="<?php echo esc_html( get_post_meta( $post->ID, 'rvlvr_store_zipcode', true ) )?>" /><br />
	<label for="rvlvr_store_city"><?php _e( 'Stad', 'rvlvr' ); ?></label>
	<input type="text" name="rvlvr_store_city" value="<?php echo esc_html( get_post_meta( $post->ID, 'rvlvr_store_city', true ) )?>" /><br />
	<label for="rvlvr_store_email"><?php _e( 'Public email', 'rvlvr' ); ?></label>
	<input type="text" name="rvlvr_store_public_email" value="<?php echo esc_html( get_post_meta( $post->ID, 'rvlvr_store_public_email', true ) )?>" /><br />
	<label for="rvlvr_store_order_email"><?php _e( 'Order email', 'rvlvr' ); ?></label>
	<input type="text" name="rvlvr_store_order_email" value="<?php echo esc_html( get_post_meta( $post->ID, 'rvlvr_store_order_email', true ) )?>" /><br />
	<label for="rvlvr_store_local_pickup_id"><?php _e( 'Local pickup ID', 'rvlvr' ); ?></label>
	<input type="text" name="rvlvr_store_local_pickup_id" value="<?php echo esc_html( get_post_meta( $post->ID, 'rvlvr_store_local_pickup_id', true ) )?>" /><br />
	<label for="rvlvr_store_phone"><?php _e( 'Telefon', 'rvlvr' ); ?></label>
	<input type="text" name="rvlvr_store_phone" value="<?php echo esc_html( get_post_meta( $post->ID, 'rvlvr_store_phone', true ) )?>" /><br />
	<label for="rvlvr_store_facebookurl"><?php _e( 'Facebook URL', 'rvlvr' ); ?></label>
	<input type="text" name="rvlvr_store_facebookurl" value="<?php echo esc_html( get_post_meta( $post->ID, 'rvlvr_store_facebookurl', true ) )?>" /><br />
	
	<label for="rvlvr_store_x"><?php _e( 'Koordinater X', 'rvlvr' ); ?></label>
	<input type="text" name="rvlvr_store_x" value="<?php echo esc_html( get_post_meta( $post->ID, 'rvlvr_store_x', true ) )?>" /><br />
	<label for="rvlvr_store_y"><?php _e( 'Koordinater Y', 'rvlvr' ); ?></label>
	<input type="text" name="rvlvr_store_y" value="<?php echo esc_html( get_post_meta( $post->ID, 'rvlvr_store_y', true ) )?>" /><br />
	<label for="rvlvr_store_type"><?php _e( 'Typ', 'rvlvr' ); ?></label>
	<select name="rvlvr_store_type">
		<option value="concept" <?php if(esc_html( get_post_meta( $post->ID, 'rvlvr_store_type', true ) ) == "concept" ) { echo "selected"; } ?> ><?php _e('Concept store'); ?></option>
		<option value="agent" <?php if(esc_html( get_post_meta( $post->ID, 'rvlvr_store_type', true ) ) == "agent" ) { echo "selected"; } ?> ><?php _e('Agent store'); ?></option>
	</select><br />
	
	<label for="rvlvr_store_status"><?php _e( 'Butiksstatus', 'rvlvr' ); ?></label>
	<select name="rvlvr_store_status">
		<option value="open" <?php if(esc_html( get_post_meta( $post->ID, 'rvlvr_store_status', true ) ) == "open" ) { echo "selected"; } ?> ><?php _e('Open'); ?></option>
		<option value="closed" <?php if(esc_html( get_post_meta( $post->ID, 'rvlvr_store_status', true ) ) == "closed" ) { echo "selected"; } ?> ><?php _e('Closed'); ?></option>		
		<option value="hidden" <?php if(esc_html( get_post_meta( $post->ID, 'rvlvr_store_status', true ) ) == "hidden" ) { echo "selected"; } ?> ><?php _e('Hidden'); ?></option>	
	</select><br />
	
	<label for="rvlvr_store_message"><?php _e( 'Butiksmeddelande', 'rvlvr' ); ?></label>
	<input type="text" name="rvlvr_store_message" value="<?php echo esc_html( get_post_meta( $post->ID, 'rvlvr_store_message', true ) )?>" /><br />
	
	
	<label for="rvlvr_store_status_monday"><?php _e( 'Mondays: ', 'rvlvr' ); ?></label>
	<select name="rvlvr_store_status_monday">
		<?php foreach($day_status as $id => $status){ ?>
			<option value="<?php echo $id; ?>" <?php if(esc_html( get_post_meta( $post->ID, 'rvlvr_store_status_monday', true ) ) == $id ) { echo "selected"; } ?> ><?php echo $status; ?></option>
		<?php } ?>
	</select>
	<label for="rvlvr_store_open_monday"><?php _e( 'Regular hours', 'rvlvr' ); ?></label>
	<input type="text" name="rvlvr_store_open_monday" value="<?php echo esc_html( get_post_meta( $post->ID, 'rvlvr_store_open_monday', true ) )?>" /> - 
	<input type="text" name="rvlvr_store_close_monday" value="<?php echo esc_html( get_post_meta( $post->ID, 'rvlvr_store_close_monday', true ) )?>" />
	<label for="rvlvr_store_message_monday"><?php _e( 'Special message', 'rvlvr' ); ?></label>
	<input type="text" name="rvlvr_store_message_monday" value="<?php echo esc_html( get_post_meta( $post->ID, 'rvlvr_store_message_monday', true ) )?>" />
	<br />	
	
	<label for="rvlvr_store_status_tuesday"><?php _e( 'Tuesdays: ', 'rvlvr' ); ?></label>
	<select name="rvlvr_store_status_tuesday">
		<?php foreach($day_status as $id => $status){ ?>
			<option value="<?php echo $id; ?>" <?php if(esc_html( get_post_meta( $post->ID, 'rvlvr_store_status_tuesday', true ) ) == $id ) { echo "selected"; } ?> ><?php echo $status; ?></option>
		<?php } ?>
	</select>
	<label for="rvlvr_store_open_tuesday"><?php _e( 'Regular hours', 'rvlvr' ); ?></label>
	<input type="text" name="rvlvr_store_open_tuesday" value="<?php echo esc_html( get_post_meta( $post->ID, 'rvlvr_store_open_tuesday', true ) )?>" /> - 
	<input type="text" name="rvlvr_store_close_tuesday" value="<?php echo esc_html( get_post_meta( $post->ID, 'rvlvr_store_close_tuesday', true ) )?>" />
	<label for="rvlvr_store_message_tuesday"><?php _e( 'Special message', 'rvlvr' ); ?></label>
	<input type="text" name="rvlvr_store_message_tuesday" value="<?php echo esc_html( get_post_meta( $post->ID, 'rvlvr_store_message_tuesday', true ) )?>" />
	<br />	
   
	<label for="rvlvr_store_status_wednesday"><?php _e( 'Wednesdays: ', 'rvlvr' ); ?></label>
	<select name="rvlvr_store_status_wednesday">
		<?php foreach($day_status as $id => $status){ ?>
			<option value="<?php echo $id; ?>" <?php if(esc_html( get_post_meta( $post->ID, 'rvlvr_store_status_wednesday', true ) ) == $id ) { echo "selected"; } ?> ><?php echo $status; ?></option>
		<?php } ?>
	</select>
	<label for="rvlvr_store_open_wednesday"><?php _e( 'Regular hours', 'rvlvr' ); ?></label>
	<input type="text" name="rvlvr_store_open_wednesday" value="<?php echo esc_html( get_post_meta( $post->ID, 'rvlvr_store_open_wednesday', true ) )?>" /> - 
	<input type="text" name="rvlvr_store_close_wednesday" value="<?php echo esc_html( get_post_meta( $post->ID, 'rvlvr_store_close_wednesday', true ) )?>" />
	<label for="rvlvr_store_message_wednesday"><?php _e( 'Special message', 'rvlvr' ); ?></label>
	<input type="text" name="rvlvr_store_message_wednesday" value="<?php echo esc_html( get_post_meta( $post->ID, 'rvlvr_store_message_wednesday', true ) )?>" />
	<br />	
    	
	<label for="rvlvr_store_status_thursday"><?php _e( 'Thursdays: ', 'rvlvr' ); ?></label>
	<select name="rvlvr_store_status_thursday">
		<?php foreach($day_status as $id => $status){ ?>
			<option value="<?php echo $id; ?>" <?php if(esc_html( get_post_meta( $post->ID, 'rvlvr_store_status_thursday', true ) ) == $id ) { echo "selected"; } ?> ><?php echo $status; ?></option>
		<?php } ?>
	</select>
	<label for="rvlvr_store_open_thursday"><?php _e( 'Regular hours', 'rvlvr' ); ?></label>
	<input type="text" name="rvlvr_store_open_thursday" value="<?php echo esc_html( get_post_meta( $post->ID, 'rvlvr_store_open_thursday', true ) )?>" /> - 
	<input type="text" name="rvlvr_store_close_thursday" value="<?php echo esc_html( get_post_meta( $post->ID, 'rvlvr_store_close_thursday', true ) )?>" />
	<label for="rvlvr_store_message_thursday"><?php _e( 'Special message', 'rvlvr' ); ?></label>
	<input type="text" name="rvlvr_store_message_thursday" value="<?php echo esc_html( get_post_meta( $post->ID, 'rvlvr_store_message_thursday', true ) )?>" />
	<br />	
   
	<label for="rvlvr_store_status_friday"><?php _e( 'Fridays: ', 'rvlvr' ); ?></label>
	<select name="rvlvr_store_status_friday">
		<?php foreach($day_status as $id => $status){ ?>
			<option value="<?php echo $id; ?>" <?php if(esc_html( get_post_meta( $post->ID, 'rvlvr_store_status_friday', true ) ) == $id ) { echo "selected"; } ?> ><?php echo $status; ?></option>
		<?php } ?>
	</select>
	<label for="rvlvr_store_open_friday"><?php _e( 'Regular hours', 'rvlvr' ); ?></label>
	<input type="text" name="rvlvr_store_open_friday" value="<?php echo esc_html( get_post_meta( $post->ID, 'rvlvr_store_open_friday', true ) )?>" /> - 
	<input type="text" name="rvlvr_store_close_friday" value="<?php echo esc_html( get_post_meta( $post->ID, 'rvlvr_store_close_friday', true ) )?>" />
	<label for="rvlvr_store_message_friday"><?php _e( 'Special message', 'rvlvr' ); ?></label>
	<input type="text" name="rvlvr_store_message_friday" value="<?php echo esc_html( get_post_meta( $post->ID, 'rvlvr_store_message_friday', true ) )?>" />
	<br />	
   
	<label for="rvlvr_store_status_saturday"><?php _e( 'Saturdays: ', 'rvlvr' ); ?></label>
	<select name="rvlvr_store_status_saturday">
		<?php foreach($day_status as $id => $status){ ?>
			<option value="<?php echo $id; ?>" <?php if(esc_html( get_post_meta( $post->ID, 'rvlvr_store_status_saturday', true ) ) == $id ) { echo "selected"; } ?> ><?php echo $status; ?></option>
		<?php } ?>
	</select>
	<label for="rvlvr_store_open_saturday"><?php _e( 'Regular hours', 'rvlvr' ); ?></label>
	<input type="text" name="rvlvr_store_open_saturday" value="<?php echo esc_html( get_post_meta( $post->ID, 'rvlvr_store_open_saturday', true ) )?>" /> - 
	<input type="text" name="rvlvr_store_close_saturday" value="<?php echo esc_html( get_post_meta( $post->ID, 'rvlvr_store_close_saturday', true ) )?>" />
	<label for="rvlvr_store_message_saturday"><?php _e( 'Special message', 'rvlvr' ); ?></label>
	<input type="text" name="rvlvr_store_message_saturday" value="<?php echo esc_html( get_post_meta( $post->ID, 'rvlvr_store_message_saturday', true ) )?>" />
	<br />	
   
   
	<label for="rvlvr_store_status_sunday"><?php _e( 'Sundays: ', 'rvlvr' ); ?></label>
	<select name="rvlvr_store_status_sunday">
		<?php foreach($day_status as $id => $status){ ?>
			<option value="<?php echo $id; ?>" <?php if(esc_html( get_post_meta( $post->ID, 'rvlvr_store_status_sunday', true ) ) == $id ) { echo "selected"; } ?> ><?php echo $status; ?></option>
		<?php } ?>
	</select>
	<label for="rvlvr_store_open_sunday"><?php _e( 'Regular hours', 'rvlvr' ); ?></label>
	<input type="text" name="rvlvr_store_open_sunday" value="<?php echo esc_html( get_post_meta( $post->ID, 'rvlvr_store_open_sunday', true ) )?>" /> - 
	<input type="text" name="rvlvr_store_close_sunday" value="<?php echo esc_html( get_post_meta( $post->ID, 'rvlvr_store_close_sunday', true ) )?>" />
	<label for="rvlvr_store_message_sunday"><?php _e( 'Special message', 'rvlvr' ); ?></label>
	<input type="text" name="rvlvr_store_message_sunday" value="<?php echo esc_html( get_post_meta( $post->ID, 'rvlvr_store_message_sunday', true ) )?>" />
	<br />	
   
   
	 


	<?php //var_export(rvlvr_get_store_hours($post)); //hours display debug ?>
	
<?php
}

add_action( 'save_post', 'add_store_fields', 10, 2 );

function add_store_fields( $post_id, $post ) {
    // Check post type for store
    if ( $post->post_type == 'store' ) {
        // Store data in post meta table if present in post data
        if ( isset( $_POST['rvlvr_store_address'] ) && $_POST['rvlvr_store_address'] != '' ) { update_post_meta( $post_id, 'rvlvr_store_address', $_POST['rvlvr_store_address'] ); }
		if ( isset( $_POST['rvlvr_store_zipcode'] ) && $_POST['rvlvr_store_zipcode'] != '' ) { update_post_meta( $post_id, 'rvlvr_store_zipcode', $_POST['rvlvr_store_zipcode'] ); }
		if ( isset( $_POST['rvlvr_store_city'] ) && $_POST['rvlvr_store_city'] != '' ) { update_post_meta( $post_id, 'rvlvr_store_city', $_POST['rvlvr_store_city'] ); }
		if ( isset( $_POST['rvlvr_store_x'] ) && $_POST['rvlvr_store_x'] != '' ) { update_post_meta( $post_id, 'rvlvr_store_x', $_POST['rvlvr_store_x'] ); }
		if ( isset( $_POST['rvlvr_store_y'] ) && $_POST['rvlvr_store_y'] != '' ) { update_post_meta( $post_id, 'rvlvr_store_y', $_POST['rvlvr_store_y'] ); }
		if ( isset( $_POST['rvlvr_store_type'] ) && $_POST['rvlvr_store_type'] != '' ) { update_post_meta( $post_id, 'rvlvr_store_type', $_POST['rvlvr_store_type'] ); }
		if ( isset( $_POST['rvlvr_store_public_email'] ) && $_POST['rvlvr_store_public_email'] != '' ) { update_post_meta( $post_id, 'rvlvr_store_public_email', $_POST['rvlvr_store_public_email'] ); }
		if ( isset( $_POST['rvlvr_store_order_email'] ) && $_POST['rvlvr_store_order_email'] != '' ) { update_post_meta( $post_id, 'rvlvr_store_order_email', $_POST['rvlvr_store_order_email'] ); } 
		if ( isset( $_POST['rvlvr_store_local_pickup_id'] ) && $_POST['rvlvr_store_local_pickup_id'] != '' ) { update_post_meta( $post_id, 'rvlvr_store_local_pickup_id', $_POST['rvlvr_store_local_pickup_id'] ); } 
		if ( isset( $_POST['rvlvr_store_phone'] ) && $_POST['rvlvr_store_phone'] != '' ) { update_post_meta( $post_id, 'rvlvr_store_phone', $_POST['rvlvr_store_phone'] ); }
		if ( isset( $_POST['rvlvr_store_facebookurl'] ) && $_POST['rvlvr_store_facebookurl'] != '' ) { update_post_meta( $post_id, 'rvlvr_store_facebookurl', $_POST['rvlvr_store_facebookurl'] ); }
		
		if ( isset( $_POST['rvlvr_store_status'] ) && $_POST['rvlvr_store_status'] != '' ) { update_post_meta( $post_id, 'rvlvr_store_status', $_POST['rvlvr_store_status'] ); }
		if ( isset( $_POST['rvlvr_store_message'] ) && $_POST['rvlvr_store_message'] != '' ) { update_post_meta( $post_id, 'rvlvr_store_message', $_POST['rvlvr_store_message'] ); }
		
		if ( isset( $_POST['rvlvr_store_open_monday'] ) && $_POST['rvlvr_store_open_monday'] != '' ) { update_post_meta( $post_id, 'rvlvr_store_open_monday', $_POST['rvlvr_store_open_monday'] ); }
		if ( isset( $_POST['rvlvr_store_open_tuesday'] ) && $_POST['rvlvr_store_open_tuesday'] != '' ) { update_post_meta( $post_id, 'rvlvr_store_open_tuesday', $_POST['rvlvr_store_open_tuesday'] ); }
		if ( isset( $_POST['rvlvr_store_open_wednesday'] ) && $_POST['rvlvr_store_open_wednesday'] != '' ) { update_post_meta( $post_id, 'rvlvr_store_open_wednesday', $_POST['rvlvr_store_open_wednesday'] ); }
		if ( isset( $_POST['rvlvr_store_open_thursday'] ) && $_POST['rvlvr_store_open_thursday'] != '' ) { update_post_meta( $post_id, 'rvlvr_store_open_thursday', $_POST['rvlvr_store_open_thursday'] ); }
		if ( isset( $_POST['rvlvr_store_open_friday'] ) && $_POST['rvlvr_store_open_friday'] != '' ) { update_post_meta( $post_id, 'rvlvr_store_open_friday', $_POST['rvlvr_store_open_friday'] ); }
		if ( isset( $_POST['rvlvr_store_open_saturday'] ) && $_POST['rvlvr_store_open_saturday'] != '' ) { update_post_meta( $post_id, 'rvlvr_store_open_saturday', $_POST['rvlvr_store_open_saturday'] ); }
		if ( isset( $_POST['rvlvr_store_open_sunday'] ) && $_POST['rvlvr_store_open_sunday'] != '' ) { update_post_meta( $post_id, 'rvlvr_store_open_sunday', $_POST['rvlvr_store_open_sunday'] ); }
		
		if ( isset( $_POST['rvlvr_store_close_monday'] ) && $_POST['rvlvr_store_close_monday'] != '' ) { update_post_meta( $post_id, 'rvlvr_store_close_monday', $_POST['rvlvr_store_close_monday'] ); }
		if ( isset( $_POST['rvlvr_store_close_tuesday'] ) && $_POST['rvlvr_store_close_tuesday'] != '' ) { update_post_meta( $post_id, 'rvlvr_store_close_tuesday', $_POST['rvlvr_store_close_tuesday'] ); }
		if ( isset( $_POST['rvlvr_store_close_wednesday'] ) && $_POST['rvlvr_store_close_wednesday'] != '' ) { update_post_meta( $post_id, 'rvlvr_store_close_wednesday', $_POST['rvlvr_store_close_wednesday'] ); }
		if ( isset( $_POST['rvlvr_store_close_thursday'] ) && $_POST['rvlvr_store_close_thursday'] != '' ) { update_post_meta( $post_id, 'rvlvr_store_close_thursday', $_POST['rvlvr_store_close_thursday'] ); }
		if ( isset( $_POST['rvlvr_store_close_friday'] ) && $_POST['rvlvr_store_close_friday'] != '' ) { update_post_meta( $post_id, 'rvlvr_store_close_friday', $_POST['rvlvr_store_close_friday'] ); }
		if ( isset( $_POST['rvlvr_store_close_saturday'] ) && $_POST['rvlvr_store_close_saturday'] != '' ) { update_post_meta( $post_id, 'rvlvr_store_close_saturday', $_POST['rvlvr_store_close_saturday'] ); }
		if ( isset( $_POST['rvlvr_store_close_sunday'] ) && $_POST['rvlvr_store_close_sunday'] != '' ) { update_post_meta( $post_id, 'rvlvr_store_close_sunday', $_POST['rvlvr_store_close_sunday'] ); }
  	 
  		if ( isset( $_POST['rvlvr_store_status_monday'] ) && $_POST['rvlvr_store_status_monday'] != '' ) { update_post_meta( $post_id, 'rvlvr_store_status_monday', $_POST['rvlvr_store_status_monday'] ); }
		if ( isset( $_POST['rvlvr_store_status_tuesday'] ) && $_POST['rvlvr_store_status_tuesday'] != '' ) { update_post_meta( $post_id, 'rvlvr_store_status_tuesday', $_POST['rvlvr_store_status_tuesday'] ); }
		if ( isset( $_POST['rvlvr_store_status_wednesday'] ) && $_POST['rvlvr_store_status_wednesday'] != '' ) { update_post_meta( $post_id, 'rvlvr_store_status_wednesday', $_POST['rvlvr_store_status_wednesday'] ); }
		if ( isset( $_POST['rvlvr_store_status_thursday'] ) && $_POST['rvlvr_store_status_thursday'] != '' ) { update_post_meta( $post_id, 'rvlvr_store_status_thursday', $_POST['rvlvr_store_status_thursday'] ); }
		if ( isset( $_POST['rvlvr_store_status_friday'] ) && $_POST['rvlvr_store_status_friday'] != '' ) { update_post_meta( $post_id, 'rvlvr_store_status_friday', $_POST['rvlvr_store_status_friday'] ); }
		if ( isset( $_POST['rvlvr_store_status_saturday'] ) && $_POST['rvlvr_store_status_saturday'] != '' ) { update_post_meta( $post_id, 'rvlvr_store_status_saturday', $_POST['rvlvr_store_status_saturday'] ); }
		if ( isset( $_POST['rvlvr_store_status_sunday'] ) && $_POST['rvlvr_store_status_sunday'] != '' ) { update_post_meta( $post_id, 'rvlvr_store_status_sunday', $_POST['rvlvr_store_status_sunday'] ); }
		
		if ( isset( $_POST['rvlvr_store_message_monday'] ) && $_POST['rvlvr_store_message_monday'] != '' ) { update_post_meta( $post_id, 'rvlvr_store_message_monday', $_POST['rvlvr_store_message_monday'] ); }
		if ( isset( $_POST['rvlvr_store_message_tuesday'] ) && $_POST['rvlvr_store_message_tuesday'] != '' ) { update_post_meta( $post_id, 'rvlvr_store_message_tuesday', $_POST['rvlvr_store_message_tuesday'] ); }
		if ( isset( $_POST['rvlvr_store_message_wednesday'] ) && $_POST['rvlvr_store_message_wednesday'] != '' ) { update_post_meta( $post_id, 'rvlvr_store_message_wednesday', $_POST['rvlvr_store_message_wednesday'] ); }
		if ( isset( $_POST['rvlvr_store_message_thursday'] ) && $_POST['rvlvr_store_message_thursday'] != '' ) { update_post_meta( $post_id, 'rvlvr_store_message_thursday', $_POST['rvlvr_store_message_thursday'] ); }
		if ( isset( $_POST['rvlvr_store_message_friday'] ) && $_POST['rvlvr_store_message_friday'] != '' ) { update_post_meta( $post_id, 'rvlvr_store_message_friday', $_POST['rvlvr_store_message_friday'] ); }
		if ( isset( $_POST['rvlvr_store_message_saturday'] ) && $_POST['rvlvr_store_message_saturday'] != '' ) { update_post_meta( $post_id, 'rvlvr_store_message_saturday', $_POST['rvlvr_store_message_saturday'] ); }
		if ( isset( $_POST['rvlvr_store_message_sunday'] ) && $_POST['rvlvr_store_message_sunday'] != '' ) { update_post_meta( $post_id, 'rvlvr_store_message_sunday', $_POST['rvlvr_store_message_sunday'] ); }
    }
}

// Add template

add_filter( 'template_include', 'include_template_function', 1 );

function include_template_function( $template_path ) {
    if ( get_post_type() == 'store' ) {
        if ( is_single() ) {
            // checks if the file exists in the theme first,
            // otherwise serve the file from the plugin
            if ( $theme_file = locate_template( array ( '/single-store.php' ) ) ) {
                $template_path = $theme_file;
            } else {
                $template_path = locate_template( array ( '/single.php' ) );
            }
        }
    }
    return $template_path;
}

/* 
 * Build store hours
 *
 */
function rvlvr_get_store_status($store){
	$customs = get_post_custom($store->id);
	$status = array();
	$status = array('store_status' => $customs['rvlvr_store_status'][0], 'store_message' => $customs['rvlvr_store_message'][0]);
	//var_export($status);
	return $status;
}

function rvlvr_get_store_days($store){
	if($store->post_type == 'store' ) {
		$days = array('sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday');
		$customs = get_post_custom($store->id);
		$hours = array();
		foreach($days as $day){
			$hours[] = array('day' => $day, 'status' => $customs['rvlvr_store_status_' . $day][0], 'opens' => $customs['rvlvr_store_open_' . $day][0] , 'closes' => $customs['rvlvr_store_close_' . $day][0] , 'message' => $customs['rvlvr_store_message_' . $day][0]);
			
		}
		$debug = false;
		if($debug == true ){
			var_export($hours);
		}
		return $hours;
		
	}
}
function rvlvr_get_store_hours_condensed($post){
	if($post->post_type == 'store' ) {
		$days = array('sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday');
		$customs=get_post_custom(get_the_id());
		$hours = array();
		foreach($days as $day){
			$hours[] = array($day, $customs['rvlvr_store_status_' . $day][0], $customs['rvlvr_store_open_' . $day][0] . $customs['rvlvr_store_close_' . $day][0] . $customs['rvlvr_store_message_' . $day][0]);
			
		
		}
		$hours2=array();
		
		
		if (isset(
			$customs['rvlvr_store_open_monday']) && 
			$customs['rvlvr_store_open_monday'][0] . $customs['rvlvr_store_close_monday'][0]  == $customs['rvlvr_store_open_tuesday'][0] . $customs['rvlvr_store_close_tuesday'][0] && 
			$customs['rvlvr_store_open_monday'][0] . $customs['rvlvr_store_close_monday'][0]  == $customs['rvlvr_store_open_wednesday'][0] .$customs['rvlvr_store_close_wednesday'][0]  && 
			$customs['rvlvr_store_open_monday'][0] . $customs['rvlvr_store_close_monday'][0]  == $customs['rvlvr_store_open_thursday'][0] . $customs['rvlvr_store_close_thursday'][0] && 
			$customs['rvlvr_store_open_monday'][0] . $customs['rvlvr_store_close_monday'][0]  == $customs['rvlvr_store_open_friday'][0] . $customs['rvlvr_store_close_friday'][0] ) { 
			
			$hours2[__('Mon-Fri', 'rvlvr')] = $customs['rvlvr_store_open_monday'][0] . " - " . $customs['rvlvr_store_close_monday'][0] ;
		} 
		elseif (isset($customs['rvlvr_store_open_monday']) && 
			$customs['rvlvr_store_open_monday'][0] . $customs['rvlvr_store_close_monday'][0] == $customs['rvlvr_store_open_tuesday'][0] . $customs['rvlvr_store_close_tuesday'][0]  && 
			$customs['rvlvr_store_open_monday'][0] . $customs['rvlvr_store_close_monday'][0] == $customs['rvlvr_store_open_wednesday'][0] . $customs['rvlvr_store_close_wednesday'][0]  && 
			$customs['rvlvr_store_open_monday'][0] . $customs['rvlvr_store_close_monday'][0] == $customs['rvlvr_store_open_thursday'][0] . $customs['rvlvr_store_close_thursday'][0]) { 

			$hours2[__('Mon-Thu', 'rvlvr')] = $customs['rvlvr_store_open_monday'][0] . " - " . $customs['rvlvr_store_close_monday'][0] ;
			$hours2[__('Fri', 'rvlvr')] = $customs['rvlvr_store_open_friday'][0] . " - " . $customs['rvlvr_store_close_friday'][0] ;
		}
		elseif (isset($customs['rvlvr_store_open_monday'])){ 
			$hours2[__('Mon', 'rvlvr')] = $customs['rvlvr_store_open_monday'][0] . " - " . $customs['rvlvr_store_close_monday'][0];
			$hours2[__('Tue', 'rvlvr')] = $customs['rvlvr_store_open_tuesday'][0] . " - " . $customs['rvlvr_store_close_tuesday'][0];
			$hours2[__('Wed', 'rvlvr')] = $customs['rvlvr_store_open_wednesday'][0] . " - " . $customs['rvlvr_store_close_wednesday'][0];
			$hours2[__('Thu', 'rvlvr')] = $customs['rvlvr_store_open_thursday'][0] . " - " . $customs['rvlvr_store_close_thursday'][0];
			$hours2[__('Fri', 'rvlvr')] = $customs['rvlvr_store_open_friday'][0] . " - " . $customs['rvlvr_store_close_friday'][0];
		}
		if(isset($customs['rvlvr_store_open_monday'])){
			$hours2[__('Sat', 'rvlvr')] = $customs['rvlvr_store_open_saturday'][0] . " - " . $customs['rvlvr_store_close_saturday'][0];
			$hours2[__('Sun', 'rvlvr')] = $customs['rvlvr_store_open_sunday'][0] . " - " . $customs['rvlvr_store_close_sunday'][0];
		}
	
	} else {
		$hours2 = __('Err: Not found', 'rvlvr');
	}
	return $hours2;
}
function rvlvr_get_store_open_status($post){
	date_default_timezone_set("Europe/Stockholm");
	
	$time_of_day = date('H:i');
		
	$day_of_tomorrow = date('w', strtotime(date('Y-m-d') . ' +1 day'));
	$day_of_week = date('w', strtotime(date('Y-m-d')));
	$days = array('sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday');
	$customs = get_post_custom($post->id);
	$hours = rvlvr_get_store_days($post);
	
	$meta_match_today = "rvlvr_store_open_" . array('sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday')[$day_of_week];
	$meta_match_tomorrow = "rvlvr_store_open_" . array('sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday')[$day_of_tomorrow];
	
	$hours_today = get_post_custom(get_the_id())[$meta_match_today][0];
	$hours_tomorrow = get_post_custom(get_the_id())[$meta_match_tomorrow][0];
	
	$debug = false;
	if($debug == true ){
		echo "time of day: " . $time_of_day . "<br />";
		echo "day of week: " . $day_of_week . "<br />";
		echo "day of tomorrow: " . $day_of_tomorrow . "<br />";
		//var_export($hours);
		var_export(rvlvr_get_store_days($post));
		echo "<br /><br />";
	}
	
	$store_status = rvlvr_get_store_status($post);
	
	if($store_status['store_status'] == 'closed' || $store_status['store_status']== 'hidden'){
		$first = $store_status['store_message'];
	
	}
	else{	
	
	// Today has letters
	if(preg_match('/[a-z]/i', $hours_today)){
		
		echo "error in input";

	}
		
	// Today has no letters
	else{
		// Today is open
		if( $hours[$day_of_week]['status'] == 0 && $time_of_day > date( "H:i", strtotime($hours[$day_of_week]['opens'])) && $time_of_day < date( "H:i", strtotime($hours[$day_of_week]['closes']))) {
			$first = __("Vi har öppet idag till ", 'rvlvr') . $hours[$day_of_week]['closes'];
			// Tomorrow is open
			if($hours[$day_of_tomorrow]['status'] == 0){
				$second = __("Imorgon: ",'rvlvr') . $hours[$day_of_tomorrow]['opens'] . " - " . $hours[$day_of_week]['closes'];
			}
			elseif($hours[$day_of_tomorrow]['status'] == 2){
				$second = __("Imorgon: ", 'rvlvr') . $hours[$day_of_tomorrow]['message'];
			}
		}	
		// Is not yet open
		elseif($time_of_day < date( "H:i", strtotime($hours[$day_of_week]['opens']))) {
			$first = __('Öppet idag: ', 'rvlvr') . $hours[$day_of_week]['opens'] . " - " . $hours[$day_of_week]['closes'];		
			// Tomorrow is open
			if($hours[$day_of_tomorrow]['status'] == 0){
				$second = __("Imorgon: ") . $hours[$day_of_tomorrow]['opens'] . " - " . $hours[$day_of_tomorrow]['closes'];
			}	
			elseif($hours[$day_of_tomorrow]['status'] == 2){
				$second = __("Imorgon:" , 'rvlvr') . $hours[$day_of_tomorrow]['message'];
			}
		}
		// Is closed
		elseif($time_of_day > date( "H:i", strtotime($hours[$day_of_week]['closes']))){
			// Tomorrow is open
			if($hours[$day_of_tomorrow]['status'] == 0){
				$first = __("Öppet imorgon: ", 'rvlvr') .  $hours[$day_of_tomorrow]['opens'] . " - " . $hours[$day_of_tomorrow]['closes'];
			}	
			elseif($hours[$day_of_tomorrow]['status'] == 2){
				$first = __("Öppet imorgon: ", 'rvlvr') . $hours[$day_of_tomorrow]['message'];
			}
			else{
			
			}
						
		}
		
		else{
			echo "Something's fishy";
		}		

	}
	
	}
	if($store_status['store_status'] == 'closed' || $store_status['store_status']== 'hidden'){
		echo "<div class='row'>";
			echo "<div class='col-xs-12'>";
				if(isset($first)){ echo "<span class='hours_first'>" . $first . "</span>"; }
				if(isset($second)){ echo "<span class='hours_second'>" . $second . "</span>"; }
			echo "</div>";
		echo "</div>";
	}
	else{
		echo "<div class='row'>";
			echo "<div class='col-sm-8 col-lg-12'>";
				if(isset($first)){ echo "<span class='hours_first'>" . $first . "</span>"; }
				if(isset($second)){ echo "<span class='hours_second'>" . $second . "</span>"; }
				echo "<span class='hours_welcome'>"	. __('Välkommen in!', 'rvlvr') . "</span>";
			echo "</div>";
			echo "<div class='col-sm-4 hidden-lg-up'>";
				echo "<span class='hours_second'>" . __('Ordinarie öppettider', 'rlvlr') . "</span>";
				echo "<table>";
					foreach(rvlvr_get_store_hours_condensed($post) as $key => $value){
						if(isset($key)){ echo "<tr><td>" . $key . " </td><td>&nbsp;&nbsp; " . $value . "</td></tr>"; }
					}
				echo "</table>";
			echo "</div>";
		echo "</div>";
	}
	
}
/*
 * Build store lists from type meta
 * 
 */
 
function rvlvr_get_stores($type){
	$args = array(
	'posts_per_page'   => 20,
	'meta_key'         => 'rvlvr_store_type',
	'meta_value'       => $type,
	'post_type'        => 'store',
	'post_status'      => 'publish',
	'suppress_filters' => true 
);
	$posts_array = get_posts( $args );
	
	return $posts_array;
}
 
/*
 * Widgets
 *
 */
 
/*
 * Single store widget
 *
 */

add_action( 'widgets_init', function(){
	register_widget( 'rvlvr_store_widget' );
});

class rvlvr_store_widget extends WP_Widget {

    /**
     * Sets up the widgets name etc
     */
    public function __construct() {
        $widget_ops = array(
            'classname' => 'rvlvr_store_widget',
            'description' => 'Display store details from meta data',
        );

        parent::__construct( 'rvlvr_store_widget', 'Revolver store details', $widget_ops );
    }

    /**
     * Outputs the content of the widget
     *
     * @param array $args
     * @param array $instance
     */
    public function widget( $args, $instance ) {
        $post = get_post();
		if($post->post_type == 'store' ) {
			$customs=get_post_custom(get_the_id());
			
			// outputs the content of the widget
			echo $args['before_widget'];
			if ( ! empty( $instance['title'] ) ) {
				echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title'];
			}
			echo "<h4 class='secondary'>";
			echo __('till vår butik', 'rvlvr');
			echo "</h4>";
			
			echo "<div>";
			echo "<b>" . __( 'Ordinare öppettider', 'rvlvr' ) . "</b>";
			echo "<table>";
			foreach(rvlvr_get_store_hours_condensed($post) as $key => $value){
				if(isset($key)){ echo "<tr><td>" . $key . " </td><td>&nbsp;&nbsp; " . $value . "</td></tr>"; }
			}
			echo "</table>";
			echo "</div>";
			
			echo "<div>";
			echo "<b>" . get_the_title() . "</b><br />";
			if(isset($customs['rvlvr_store_address'])){ echo $customs['rvlvr_store_address'][0] . "<br />"; }
			if(isset($customs['rvlvr_store_zipcode'])){ echo $customs['rvlvr_store_zipcode'][0]; }
			if(isset($customs['rvlvr_store_city'])){ echo $customs['rvlvr_store_city'][0]; }
			echo "</div>";
			echo "<div>";
			if(isset($customs['rvlvr_store_phone'])){ echo $customs['rvlvr_store_phone'][0] . "<br />"; }
			if(isset($customs['rvlvr_store_email'])){ echo "<a class='secondary' href='mailto:" . $customs['rvlvr_store_email'][0] . "'>" . $customs['rvlvr_store_email'][0] . "</a>"; }
			echo "</div>";
			echo $args['after_widget'];
		}
		
    }

    /**
     * Outputs the options form on admin
     *
     * @param array $instance The widget options
     */
    public function form( $instance ) {
        // outputs the options form on admin
        $title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'New title', 'rvlvr' );
        ?>
            <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
            </p>
        <?php
    }

    /**
     * Processing widget options on save
     *
     * @param array $new_instance The new options
     * @param array $old_instance The previous options
     */
    public function update( $new_instance, $old_instance ) {
        // processes widget options to be saved
        foreach( $new_instance as $key => $value )
        {
            $updated_instance[$key] = sanitize_text_field($value);
        }

        return $updated_instance;
    }
}

/*
 * All stores widget
 * Used in a subset of sidebars
 */

add_action( 'widgets_init', function(){
	register_widget( 'rvlvr_stores_widget' );
});

class rvlvr_stores_widget extends WP_Widget {

    /**
     * Sets up the widgets name etc
     */
    public function __construct() {
        $widget_ops = array(
            'classname' => 'rvlvr_stores_widget',
            'description' => 'Display all stores by type',
        );

        parent::__construct( 'rvlvr_stores_widget', 'Revolver store listing', $widget_ops );
    }

    /**
     * Outputs the content of the widget
     *
     * @param array $args
     * @param array $instance
     */
    public function widget( $args, $instance ) {
        		
		// outputs the content of the widget
		echo $args['before_widget'];
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title'];
		}
		echo "<h4 class='secondary'>";
		echo __('for pickup', 'rvlvr');
		echo "</h4>";
		echo "<div>";
		_e('Find a store close to you');
		echo "</div>";
		echo "<div>";
		echo "<b>" . __('Concept stores', 'rvlvr' ) . "</b>";
		echo "<ul>";
		//var_export(rvlvr_get_stores('concept'));
		foreach(rvlvr_get_stores('concept') as $store){
			echo "<li><a class='secondary' href='" . get_permalink($store->ID) . "' >" . $store->post_title . "</a></li>";
		}
		echo "</ul></div>";
		echo "<div>";
		echo "<b>" . __('Agent stores', 'rvlvr' ) . "</b>";
		echo "<ul>";
		foreach(rvlvr_get_stores('agent') as $store){
			echo "<li><a class='secondary' href='" . get_permalink($store->ID) . "' >" . $store->post_title . "</a></li>";
		}
		echo "</ul>";
		echo "</div>";
		echo $args['after_widget'];
		
    }

    /**
     * Outputs the options form on admin
     *
     * @param array $instance The widget options
     */
    public function form( $instance ) {
        // outputs the options form on admin
        $title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'New title', 'rvlvr' );
        ?>
            <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
            </p>
        <?php
    }

    /**
     * Processing widget options on save
     *
     * @param array $new_instance The new options
     * @param array $old_instance The previous options
     */
    public function update( $new_instance, $old_instance ) {
        // processes widget options to be saved
        foreach( $new_instance as $key => $value )
        {
            $updated_instance[$key] = sanitize_text_field($value);
        }

        return $updated_instance;
    }
}

/*
 * Outputs all stores of given type
 * Used in a lower sidebar 
 *
 */

add_action( 'widgets_init', function(){
	register_widget( 'rvlvr_stores_selective_widget' );
});

class rvlvr_stores_selective_widget extends WP_Widget {

    /**
     * Sets up the widgets name etc
     */
    public function __construct() {
        $widget_ops = array(
            'classname' => 'rvlvr_stores_selective_widget',
            'description' => 'Display all stores of given type',
        );

        parent::__construct( 'rvlvr_stores_selective_widget', 'Revolver store type listing', $widget_ops );
    }

    /**
     * Outputs the content of the widget
     *
     * @param array $args
     * @param array $instance
     */
    public function widget( $args, $instance ) {
        		
		// outputs the content of the widget
		echo $args['before_widget'];
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title'];
		}
		echo "<div><ul>";
		//var_export(rvlvr_get_stores('concept'));
		foreach(rvlvr_get_stores($instance['store_type']) as $store){
			echo "<li><a class='secondary' href='" . get_permalink($store->ID) . "' >" . $store->post_title . "</a></li>";
		}
		echo "</ul></div>";
		echo $args['after_widget'];
		
    }

    /**
     * Outputs the options form on admin
     *
     * @param array $instance The widget options
     */
    public function form( $instance ) {
        // outputs the options form on admin
        $title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'New title', 'rvlvr' );
		$type = ! empty( $instance['store_type'] ) ? $instance['store_type'] : __( 'Type string', 'rvlvr' );
        ?>
            <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
			</p>
			<p>
            <label for="<?php echo $this->get_field_id( 'store_type' ); ?>"><?php _e( 'Store type:' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'store_type' ); ?>" name="<?php echo $this->get_field_name( 'store_type' ); ?>" type="text" value="<?php echo esc_attr( $type ); ?>" />
			</p>
        <?php
    }

    /**
     * Processing widget options on save
     *
     * @param array $new_instance The new options
     * @param array $old_instance The previous options
     */
    public function update( $new_instance, $old_instance ) {
        // processes widget options to be saved
        foreach( $new_instance as $key => $value )
        {
            $updated_instance[$key] = sanitize_text_field($value);
        }

        return $updated_instance;
    }
}




































/*
 * Custom post type spots
 *
 *
 *
 *
 */
 
// Register Custom Post Type: spot
function custom_post_type_spot() {

	$labels = array(
		'name'                  => _x( 'Spots', 'Post Type General Name', 'rvlvr' ),
		'singular_name'         => _x( 'Spot', 'Post Type Singular Name', 'rvlvr' ),
		'menu_name'             => __( 'Spots', 'rvlvr' ),
		'name_admin_bar'        => __( 'Spots', 'rvlvr' ),
		'archives'              => __( 'Spots', 'rvlvr' ),
		'parent_item_colon'     => __( '', 'rvlvr' ),
		'all_items'             => __( 'All spots', 'rvlvr' ),
		'add_new_item'          => __( 'Add new spot', 'rvlvr' ),
		'add_new'               => __( 'Add new', 'rvlvr' ),
		'new_item'              => __( 'New spot', 'rvlvr' ),
		'edit_item'             => __( 'Edit spot', 'rvlvr' ),
		'update_item'           => __( 'Update spot', 'rvlvr' ),
		'view_item'             => __( 'View spot', 'rvlvr' ),
		'search_items'          => __( 'Search spot', 'rvlvr' ),
		'not_found'             => __( 'Not found', 'rvlvr' ),
		'not_found_in_trash'    => __( 'Not found in Trash', 'rvlvr' ),
		'featured_image'        => __( 'Featured Image', 'rvlvr' ),
		'set_featured_image'    => __( 'Set featured image', 'rvlvr' ),
		'remove_featured_image' => __( 'Remove featured image', 'rvlvr' ),
		'use_featured_image'    => __( 'Use as featured image', 'rvlvr' ),
		'insert_into_item'      => __( 'Insert into spot', 'rvlvr' ),
		'uploaded_to_this_item' => __( 'Uploaded to this spot', 'rvlvr' ),
		'items_list'            => __( 'Spots list', 'rvlvr' ),
		'items_list_navigation' => __( 'Spots list navigation', 'rvlvr' ),
		'filter_items_list'     => __( 'Filter spots list', 'rvlvr' ),
	);
	$args = array(
		'label'                 => __( 'Store', 'rvlvr' ),
		'description'           => __( 'Post types for all Revolver stores', 'rvlvr' ),
		'labels'                => $labels,
		'supports'              => array( 'title', 'thumbnail', ),
		'hierarchical'          => false,
		'public'                => false,
		'show_ui'               => true,
		'show_in_menu'          => true,
		'menu_position'         => 125,
		'menu_icon'             => 'dashicons-screenoptions',
		'show_in_admin_bar'     => false,
		'show_in_nav_menus'     => true,
		'can_export'            => false,
		'has_archive'           => false,		
		'exclude_from_search'   => true,
		'publicly_queryable'    => false,
		'capability_type'       => 'page',
	);
	register_post_type( 'spot', $args );

}
add_action( 'init', 'custom_post_type_spot', 0 );

add_action( 'admin_init', 'spot_admin' );

function spot_admin() {
    add_meta_box( 'spot_box',
        __('Settings for spot', 'rvlvr'),
        'display_spot_box',
        'spot', 'normal', 'high'
    );
}
function display_spot_box( $post ) {
?>			
	<label for="rvlvr_spot_url"><?php _e( 'Where does the spot lead? Full URL', 'rvlvr' ); ?></label>
	<input type="text" name="rvlvr_spot_url" value="<?php echo esc_html( get_post_meta( $post->ID, 'rvlvr_spot_url', true ) )?>" /><br />

	
<?php
}

add_action( 'save_post', 'add_spot_fields', 10, 2 );

function add_spot_fields( $post_id, $post ) {
    // Check post type for store
    if ( $post->post_type == 'spot' ) {
        // Store data in post meta table if present in post data
        if ( isset( $_POST['rvlvr_spot_url'] ) && $_POST['rvlvr_spot_url'] != '' ) { update_post_meta( $post_id, 'rvlvr_spot_url', $_POST['rvlvr_spot_url'] ); }	
    }
}

/**
  * Shortcode to output spots
  */

add_shortcode( 'rvlvr_spots', 'rvlvr_spots_func' );

function rvlvr_spots_func($atts) {

	extract(shortcode_atts(array(
		'per_page'      => '12',
		'columns'       => '4',
		'orderby' => 'date',
		'order' => 'desc',
		'category'=> ''
	), $atts));
	$args = array(
		'post_type'     => 'spot',
		'post_status' => 'publish',
		'posts_per_page' => $per_page,
		'orderby' => $orderby,
		'order' => $order
	
	);
	ob_start();
	$spots = new WP_Query( $args );
	if ( $spots->have_posts() ) :
		echo "<ul>";	
		while ( $spots->have_posts() ) : $spots->the_post();
			$customs=get_post_custom(get_the_id());
			echo "<li class='col-md-3 col-sm-6'><a href='" . $customs['rvlvr_spot_url'][0] . "'>" . get_the_post_thumbnail() . "</a></li>";
        endwhile; // end of the loop.
		echo "</ul>";
	endif;	
	wp_reset_postdata();
	return '<div class="row rvlvr_spots">' . ob_get_clean() . '</div>';
}

















/*
 * Custom post type seasons
 *
 *
 *
 *
 */
 
// Register Custom Post Type: Season
function custom_post_type_season() {

	$labels = array(
		'name'                  => _x( 'Season', 'Post Type General Name', 'rvlvr' ),
		'singular_name'         => _x( 'Seasons', 'Post Type Singular Name', 'rvlvr' ),
		'menu_name'             => __( 'Seasons', 'rvlvr' ),
		'name_admin_bar'        => __( 'Seasons', 'rvlvr' ),
		'archives'              => __( 'Seasons', 'rvlvr' ),
		'parent_item_colon'     => __( '', 'rvlvr' ),
		'all_items'             => __( 'All seasons', 'rvlvr' ),
		'add_new_item'          => __( 'Add new season', 'rvlvr' ),
		'add_new'               => __( 'Add new', 'rvlvr' ),
		'new_item'              => __( 'New season', 'rvlvr' ),
		'edit_item'             => __( 'Edit season', 'rvlvr' ),
		'update_item'           => __( 'Update season', 'rvlvr' ),
		'view_item'             => __( 'View season', 'rvlvr' ),
		'search_items'          => __( 'Search season', 'rvlvr' ),
		'not_found'             => __( 'Not found', 'rvlvr' ),
		'not_found_in_trash'    => __( 'Not found in Trash', 'rvlvr' ),
		'featured_image'        => __( 'Featured Image', 'rvlvr' ),
		'set_featured_image'    => __( 'Set featured image', 'rvlvr' ),
		'remove_featured_image' => __( 'Remove featured image', 'rvlvr' ),
		'use_featured_image'    => __( 'Use as featured image', 'rvlvr' ),
		'insert_into_item'      => __( 'Insert into season', 'rvlvr' ),
		'uploaded_to_this_item' => __( 'Uploaded to this season', 'rvlvr' ),
		'items_list'            => __( 'Seasons list', 'rvlvr' ),
		'items_list_navigation' => __( 'Seasons list navigation', 'rvlvr' ),
		'filter_items_list'     => __( 'Filter seasons list', 'rvlvr' ),
	);
	$args = array(
		'label'                 => __( 'Season', 'rvlvr' ),
		'description'           => __( 'Post types for all Revolver seasons', 'rvlvr' ),
		'labels'                => $labels,
		'supports'              => array( 'title', 'thumbnail', 'attachments', 'custom-fields'),
		'hierarchical'          => false,
		'public'                => false,
		'show_ui'               => true,
		'show_in_menu'          => true,
		'menu_position'         => 126,
		'menu_icon'             => 'dashicons-palmtree',
		'show_in_admin_bar'     => false,
		'show_in_nav_menus'     => true,
		'can_export'            => false,
		'has_archive'           => false,		
		'exclude_from_search'   => true,
		'publicly_queryable'    => false,
		'capability_type'       => 'page',
	);
	register_post_type( 'season', $args );

}
add_action( 'init', 'custom_post_type_season', 0 );

//add_action( 'admin_init', 'season_admin' );

add_action( 'admin_init', 'season_admin' );

function season_admin() {
    add_meta_box( 'season_box',
        __('Settings for season', 'rvlvr'),
        'media_selector_settings_page_callback',
        'season', 'normal', 'high'
    );
}

//function register_media_selector_settings_page() {
	//add_submenu_page( 'options-general.php', 'Media Selector', 'Media Selector', 'manage_options', 'media-selector', 'media_selector_settings_page_callback' );
//}

function media_selector_settings_page_callback($post) {

	// Save attachment ID
	/*if ( isset( $_POST['submit_image_selector'] ) && isset( $_POST['rvlvr_season_bg'] ) ) :
		update_option( 'media_selector_attachment_id', absint( $_POST['rvlvr_season_bg'] ) );
	endif;
*/	
	wp_enqueue_media();
	
	
	//var_export(get_post_meta($post->ID, 'rvlvr_season_bg'));
	?>
		
	
	<?php
	
		_e('Current background images. Find and remove the "rvlvr_season_bg" meta key with the responding ID to remove image from rooster', 'rvlvr');
		echo "<div class='clearfix'></div><br />";
		$bgs = get_post_meta($post->ID, 'rvlvr_season_bg');
		if(!$bgs){
			echo "<i>" . __('No images added', 'rvlvr') . "</i>";
		}
		else{
			foreach(get_post_meta($post->ID, 'rvlvr_season_bg') as $img_id){
				echo "<div style='float:left;margin-right:10px; border: solid 1px #ddd;'>";
				echo "<img id='' src='" . wp_get_attachment_url( $img_id ) . "' height='100'><br />";
				echo " ID:" . $img_id;
				echo "</div>";
			}
		}
	?>
	
	
		<div class='clearfix'></div>
		<br />
		<?php _e('Add new image', 'rvlvr'); ?>
		<div style="margin-top:10px; margin-bottom: 10px;" class='image-preview-wrapper'>
			<img style="border: solid 1px #ddd; max-height: 100px;" id='image-preview' src='' height=''>
		</div>
		<input id="upload_image_button" type="button" class="button" value="<?php _e( 'Add image', 'rvlvr' ); ?>" />
		<input type='hidden' name='rvlvr_season_bg' id='image_attachment_id' value=''>
		<input type="submit" name="submit_image_selector" value="<?php _e('Save', 'rvlvr'); ?>" class="button-primary">
		<hr style="clear:both;" />
		<label for="rvlvr_season_expires"><?php _e( 'Season expires', 'rvlvr' ); ?></label>
		<input type="text" name="rvlvr_season_expires" value="<?=(get_post_meta( $post->ID, 'rvlvr_season_expires', true )) ? esc_html( get_post_meta( $post->ID, 'rvlvr_season_expires', true ) ) : 'YYY-MM-DD'?>" /><br />
		<label for="rvlvr_season_attribute"><?php _e( 'Season attribute shortcode', 'rvlvr' ); ?></label>
		<input type="text" name="rvlvr_season_attribute" value="<?=(get_post_meta( $post->ID, 'rvlvr_season_attribute', true )) ? esc_html( get_post_meta( $post->ID, 'rvlvr_season_attribute', true ) ) : ''?>" /><br />

		
	<?php

}

add_action( 'save_post', 'add_season_fields', 10, 2 );

function add_season_fields( $post_id, $post ) {
    // Check post type for season
    if ( $post->post_type == 'season' ) {
        // Store data in post meta table if present in post data
        if ( isset( $_POST['rvlvr_season_bg'] ) && $_POST['rvlvr_season_bg'] != '' ) { add_post_meta( $post_id, 'rvlvr_season_bg', $_POST['rvlvr_season_bg'] ); }
		if ( isset( $_POST['rvlvr_season_expires'] ) && $_POST['rvlvr_season_expires'] != '' ) { update_post_meta( $post_id, 'rvlvr_season_expires', $_POST['rvlvr_season_expires'] ); }
		if ( isset( $_POST['rvlvr_season_attribute'] ) && $_POST['rvlvr_season_attribute'] != '' ) { update_post_meta( $post_id, 'rvlvr_season_attribute', $_POST['rvlvr_season_attribute'] ); }
		}
}




//move to enqueue
add_action( 'admin_footer', 'media_selector_print_scripts' );

function media_selector_print_scripts() {
	if(get_post_type(get_the_ID()) == 'season' ){
		$my_saved_attachment_post_id = get_option( 'media_selector_attachment_id', 0 );
		//wp_enqueue_script( 'media_selector',  plugin_dir_url( __FILE__ ) . '/js/media_selector.js');
		?>
		<script>
		jQuery( document ).ready( function( $ ) {

			// Uploading files
			var file_frame;
			var wp_media_post_id = wp.media.model.settings.post.id; // Store the old id
			var set_to_post_id = <?php echo $my_saved_attachment_post_id; ?>; // Set this
		
			jQuery('#upload_image_button').on('click', function( event ){

					event.preventDefault();

					// If the media frame already exists, reopen it.
					if ( file_frame ) {
						// Set the post ID to what we want
						file_frame.uploader.uploader.param( 'post_id', set_to_post_id );
						// Open frame
						file_frame.open();
						return;
					} else {
						// Set the wp.media post id so the uploader grabs the ID we want when initialised
						wp.media.model.settings.post.id = set_to_post_id;
					}

					// Create the media frame.
					file_frame = wp.media.frames.file_frame = wp.media({
						title: 'Select a image to upload',
						button: {
							text: 'Use this image',
						},
						multiple: false	// Set to true to allow multiple files to be selected
					});

					// When an image is selected, run a callback.
					file_frame.on( 'select', function() {
						// We set multiple to false so only get one image from the uploader
						attachment = file_frame.state().get('selection').first().toJSON();

						// Do something with attachment.id and/or attachment.url here
						$( '#image-preview' ).attr( 'src', attachment.url ).css( 'width', 'auto' );
						$( '#image_attachment_id' ).val( attachment.id );

						// Restore the main post ID
						wp.media.model.settings.post.id = wp_media_post_id;
					});

						// Finally, open the modal
						file_frame.open();
				});

				// Restore the main ID when the add media button is pressed
				jQuery( 'a.add_media' ).on( 'click', function() {
					wp.media.model.settings.post.id = wp_media_post_id;
				});
		
		});
		</script>
	<?php
	}
}
?>