<?php

/*
 * Outputs all stores of given type
 * Used in a lower sidebar 
 *
 */

//add_action( 'widgets_init', function(){ register_widget( 'rvlvr_social_widget' ); });

class rvlvr_social_widget extends WP_Widget {

    /**
     * Sets up the widgets name etc
     */
    public function __construct() {
        $widget_ops = array(
            'classname' => 'rvlvr_social_widget',
            'description' => 'Display social icons',
        );

        parent::__construct( 'rvlvr_social_widget', 'Revolver social icons', $widget_ops );
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
		echo "<div>";
			echo $instance['description'];
		echo "</div>";
		echo "<div>";
			echo "<a href='" . $instance['fburl'] . "'><i class='fa fa-facebook-square fa-5x' aria-hidden='true'></i></a>";
			echo "<a href='" . $instance['twurl'] . "'><i class='fa fa-twitter-square fa-5x' aria-hidden='true'></i></a>";
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
		$description = ! empty( $instance['description'] ) ? $instance['description'] : __( '', 'rvlvr' );
		$fburl = ! empty( $instance['fburl'] ) ? $instance['fburl'] : __( '', 'rvlvr' );
		$twurl = ! empty( $instance['twurl'] ) ? $instance['twurl'] : __( '', 'rvlvr' );
        ?>
            <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
			</p>
			<p>
            <label for="<?php echo $this->get_field_id( 'description' ); ?>"><?php _e( 'Call-to-action:' ); ?></label> 
			<textarea rows="5" class="widefat" id="<?php echo $this->get_field_id( 'description' ); ?>" name="<?php echo $this->get_field_name( 'description' ); ?>"><?php echo esc_attr( $description ); ?></textarea>
			</p>
			<p>
            <label for="<?php echo $this->get_field_id( 'fburl' ); ?>"><?php _e( 'Facebook URL:' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'fburl' ); ?>" name="<?php echo $this->get_field_name( 'fburl' ); ?>" type="text" value="<?php echo esc_attr( $fburl ); ?>">
			</p>
			<p>
            <label for="<?php echo $this->get_field_id( 'twurl' ); ?>"><?php _e( 'Twitter URL:' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'twurl' ); ?>" name="<?php echo $this->get_field_name( 'twurl' ); ?>" type="text" value="<?php echo esc_attr( $twurl ); ?>">
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


?>

