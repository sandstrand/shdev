<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div class="svea-fields svea-part-pay-fields">
    <?php if( $country == "SE" || $country == "DK"
            || $country == "FI" || $country == "NO" ): ?>
    <div class="personal-number-container">
        <?php
            $should_hide_ssn = ( $country == "SE" || $country == "DK" || $country == "NO" ) && WC_SveaWebPay_Gateway_Shortcodes::is_using_get_address_shortcode();

            if( $should_hide_ssn ) : ?>
            <input type="hidden" value="<?php echo isset( $post_data['pp_billing_ssn'] ) ? $post_data['pp_billing_ssn'] : ''; ?>"
                name="pp_billing_ssn" />
            <?php
            else :
        		$pnr='';
				if( is_user_logged_in() == true){
					if (isset(get_user_meta(get_current_user_id())['billing_personnr'][0])){
						//echo get_user_meta(get_current_user_id())['billing_personnr'][0];
						$pnr = get_user_meta(get_current_user_id())['billing_personnr'][0];
					}
				}

            woocommerce_form_field( 'pp_billing_ssn', array(
                'type'          => ( $should_hide_ssn ? 'hidden' : 'text' ),
                'required'      => false,
                'class'         => array('form-row-wide a'),
                'placeholder'       => $pnr,
                'default'       => $pnr,
                'label'         => __('Personal number', 'sveawebpay') . ' <abbr class="required" title="required">*</abbr>'
            ), isset( $post_data['pp_billing_ssn'] ) ? $post_data['pp_billing_ssn'] : $pnr);
            endif;
        ?>
    </div>
    <?php endif;

    $should_hide_get_address = ( $country == "SE" || $country == "DK" ) && WC_SveaWebPay_Gateway_Shortcodes::is_using_get_address_shortcode();

    if( ! $should_hide_get_address ) : ?>
    <div class="svea-get-address-button-container">
        <a class="svea-get-address-button" href="#"><?php _e('Get address', WC_SveaWebPay_Gateway::PLUGIN_SLUG ); ?></a>
    </div>
    <?php endif; ?>
    <?php if( $country == "NL" || $country == "DE" ): ?>
    <div class="birth-date-container">
        <?php _e('Date of birth', 'sveawebpay'); ?>
        <?php
            
            $current_year = intval( date( 'Y' ) );

            $years = array_combine(
                range( $current_year, $current_year - 100 ),
                range( $current_year, $current_year - 100 )
            );

            woocommerce_form_field( 'pp_birth_date_year', array(
                'type'          => 'select',
                'required'      => false,
                'class'         => array('form-row-wide birth-date-year'),
                'label'         => __('Year', 'sveawebpay') . ' <abbr class="required" title="required">*</abbr>',
                'options'       => $years
            ), isset( $post_data['pp_birth_date_year'] ) ? $post_data['pp_birth_date_year'] : false ); 

            $months = array(
                "1" => __("January", "sveawebpay"), 
                "2" => __("February", "sveawebpay"), 
                "3" => __("Mars", "sveawebpay"), 
                "4" => __("April", "sveawebpay"), 
                "5" => __("May", "sveawebpay"), 
                "6" => __("June", "sveawebpay"), 
                "7" => __("July", "sveawebpay"),
                "8" => __("August", "sveawebpay"), 
                "9" => __("September", "sveawebpay"), 
                "10" => __("October", "sveawebpay"), 
                "11" => __("November", "sveawebpay"), 
                "12" => __("December", "sveawebpay")
            );

            woocommerce_form_field( 'pp_birth_date_month', array(
                'type'          => 'select',
                'required'      => false,
                'class'         => array('form-row-wide birth-date-month'),
                'label'         => __('Month', 'sveawebpay') . ' <abbr class="required" title="required">*</abbr>',
                'options'       => $months
            ), isset($post_data['pp_birth_date_month']) ? $post_data['pp_birth_date_month'] : false);

            $days = array_combine(range(1, 31), range(1, 31));

            woocommerce_form_field( 'pp_birth_date_day', array(
                'type'          => 'select',
                'required'      => false,
                'class'         => array('form-row-wide birth-date-day'),
                'label'         => __('Day', 'sveawebpay') . ' <abbr class="required" title="required">*</abbr>',
                'options'       => $days
            ), isset($post_data['pp_birth_date_day']) ? $post_data['pp_birth_date_day'] : false);
        ?>
    </div>
    <?php endif; ?>
    <?php if( $country == "NL" ): ?>
    <div class="initials-container">
        <?php 
            woocommerce_form_field( 'pp_billing_initials', array(
                'type'          => 'text',
                'required'      => false,
                'class'         => array('form-row-wide'),
                'label'         => __('Initials', 'sveawebpay') . ' <abbr class="required" title="required">*</abbr>'
            ), isset($post_data['pp_billing_initials']) ? $post_data['pp_billing_initials'] : false); 
        ?>
    </div>
    <?php endif; ?>
</div>