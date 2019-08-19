<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div class="svea-fields svea-invoice-fields">
    <div class="customer-type-container">
        <?php
            $should_hide_customer_type = ( $country == "SE" || $country == "DK" || $country == "NO" ) && WC_SveaWebPay_Gateway_Shortcodes::is_using_get_address_shortcode();

            if( $should_hide_customer_type ) : ?>
            <input type="hidden" value="<?php echo isset( $post_data['iv_billing_customer_type'] ) ? $post_data['iv_billing_customer_type'] : ''; ?>"
                name="iv_billing_customer_type" />
            <?php
            else :

            woocommerce_form_field( 'iv_billing_customer_type', array(
                'type'          => 'select',
                'required'      => false,
                'class'         => array('form-row-wide hidden'),
                'options'       => array(
                                        //'false'         => __('- Choose customer type -', 'sveawebpay'),
                                        'individual'    => __('Individual', 'sveawebpay'),
                                        //'company'       => __('Company', 'sveawebpay'),
                                    ),
                'label'         => ''
                /*	Replaced by shdev
            	'type'          => 'select',
                'required'      => false,
                'class'         => array('form-row-wide'),
                'options'       => array(
                                        'false'         => __('- Choose customer type -', 'sveawebpay'),
                                        'individual'    => __('Individual', 'sveawebpay'),
                                        'company'       => __('Company', 'sveawebpay'),
                                    ),
                'label'         => __('Customer Type', 'sveawebpay') . ' <abbr class="required" title="required">*</abbr>'
                
                */
            ), isset( $post_data['iv_billing_customer_type']) ? $post_data['iv_billing_customer_type'] : false );

            endif;
        ?>
    </div>
    <?php if( $country == "SE" || $country == "DK"
            || $country == "FI" || $country == "NO" ): ?>
    <div class="organisation-number-container">
        <?php
            $should_hide_org_number = ( $country == "SE" || $country == "DK" || $country == "NO" ) && WC_SveaWebPay_Gateway_Shortcodes::is_using_get_address_shortcode();

            if( $should_hide_org_number ) : ?>
            <input type="hidden" value="<?php echo isset( $post_data['iv_billing_org_number'] ) ? $post_data['iv_billing_org_number'] : ''; ?>"
                name="iv_billing_org_number" />
            <?php
            else : 
            woocommerce_form_field( 'iv_billing_org_number', array(
                'type'          => 'text',
                'required'      => false,
                'class'         => array( 'form-row-wide hidden' ),
                'label'         => __( 'Organisation number', WC_SveaWebPay_Gateway::PLUGIN_SLUG ) . ' <abbr class="required" title="required">*</abbr>'
            ), isset( $post_data['iv_billing_org_number'] ) ? $post_data['iv_billing_org_number'] : null);
            endif;
        ?>
    </div>
    <div class="personal-number-container">
        <?php
            $should_hide_ssn = ( $country == "SE" || $country == "DK" || $country == "NO" ) && WC_SveaWebPay_Gateway_Shortcodes::is_using_get_address_shortcode();
			/*echo get_current_user_id();
			echo "<pre>";
			var_export( get_user_meta(get_current_user_id())['billing_personnr'][0]);
			echo "</pre>";*/
			$pnr='';
			if( is_user_logged_in() == true){
				if (isset(get_user_meta(get_current_user_id())['billing_personnr'][0])){
					//echo get_user_meta(get_current_user_id())['billing_personnr'][0];
					$pnr = get_user_meta(get_current_user_id())['billing_personnr'][0];
				}
			}
            if( $should_hide_ssn ) : ?>
            <input type="hidden" value="<?php echo isset( $post_data['iv_billing_ssn'] ) ? $post_data['iv_billing_ssn'] : '' ; ?>"
                name="iv_billing_ssn" />
            <?php
            else :
            woocommerce_form_field( 'iv_billing_ssn', array(
                'type'          => 'text',
                'required'      => false,
                'class'         => array('form-row-wide'),
                'default'       => $pnr,
                'label'         => __( 'Personal number', WC_SveaWebPay_Gateway::PLUGIN_SLUG ) . ' <abbr class="required" title="required">*</abbr>'
            ), isset( $post_data['iv_billing_ssn'] ) ? $post_data['iv_billing_ssn'] : null); 
            endif;
        ?>
    </div>
    <?php endif;
    
    $should_hide_get_address = ( $country == "SE" || $country == "DK" || $country == "NO" ) && WC_SveaWebPay_Gateway_Shortcodes::is_using_get_address_shortcode();

    if( ! $should_hide_get_address ) : ?>
    <div class="svea-get-address-button-container">
        <a class="svea-get-address-button" href="#"><?php _e('Get address', WC_SveaWebPay_Gateway::PLUGIN_SLUG ); ?></a>
    </div>
    <?php endif;

    if( $country == "SE" || $country == "DK" || $country == "NO" ) : ?>
    <div class="org-address-selector-container">
        <?php if( ! WC_SveaWebPay_Gateway_Shortcodes::is_using_get_address_shortcode() ) : ?>
        <p class="form-row form-row-wide">
            <select name="address_selector" class="org-address-selector"></select>
        </p>
        <?php else : ?>
        <input type="hidden" name="address_selector" class="address-selector" />
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php if( $country == "NL" || $country == "DE" ): ?>
    <div class="vat-number-container">
        <?php 
            woocommerce_form_field( 'iv_billing_vat_number', array(
                'type'          => 'text',
                'required'      => false,
                'class'         => array('form-row-wide'),
                'label'         => __('VAT number', 'sveawebpay') . ' <abbr class="required" title="required">*</abbr>'
            ), isset($post_data['iv_billing_vat_number']) ? $post_data['iv_billing_vat_number'] : null); 
        ?>
    </div>
    <div class="birth-date-container">
        <?php _e('Date of birth', 'sveawebpay'); ?>
        <?php
            $current_year = intval(date('Y'));

            $years = array_combine(
                range($current_year, $current_year - 100),
                range($current_year, $current_year - 100)
            );

            woocommerce_form_field( 'iv_birth_date_year', array(
                'type'          => 'select',
                'required'      => false,
                'class'         => array('form-row-wide birth-date-year'),
                'label'         => __('Year', 'sveawebpay') . ' <abbr class="required" title="required">*</abbr>',
                'options'       => $years
            ), isset($post_data['iv_birth_date_year']) ? $post_data['iv_birth_date_year'] : null); 

            $months = array(
                "1" => __( "January", "sveawebpay" ), 
                "2" => __( "February", "sveawebpay" ), 
                "3" => __( "Mars", "sveawebpay" ), 
                "4" => __( "April", "sveawebpay" ), 
                "5" => __( "May", "sveawebpay" ), 
                "6" => __( "June", "sveawebpay" ), 
                "7" => __( "July", "sveawebpay" ),
                "8" => __( "August", "sveawebpay" ), 
                "9" => __( "September", "sveawebpay" ), 
                "10" => __( "October", "sveawebpay" ), 
                "11" => __( "November", "sveawebpay" ), 
                "12" => __( "December", "sveawebpay" )
            );

            woocommerce_form_field( 'iv_birth_date_month', array(
                'type'          => 'select',
                'required'      => false,
                'class'         => array('form-row-wide birth-date-month'),
                'label'         => __('Month', 'sveawebpay') . ' <abbr class="required" title="required">*</abbr>',
                'options'       => $months
            ), isset($post_data['iv_birth_date_month']) ? $post_data['iv_birth_date_month'] : null);

            $days = array_combine(range(1, 31), range(1, 31));

            woocommerce_form_field( 'iv_birth_date_day', array(
                'type'          => 'select',
                'required'      => false,
                'class'         => array('form-row-wide birth-date-day'),
                'label'         => __('Day', 'sveawebpay') . ' <abbr class="required" title="required">*</abbr>',
                'options'       => $days
            ), isset($post_data['iv_birth_date_day']) ? $post_data['iv_birth_date_day'] : null);
        ?>
    </div>
    <?php endif; ?>
    <?php if( $country == "NL" ): ?>
    <div class="initials-container">
        <?php 
            woocommerce_form_field( 'iv_billing_initials', array(
                'type'          => 'text',
                'required'      => false,
                'class'         => array('form-row-wide'),
                'label'         => __('Initials', 'sveawebpay') . ' <abbr class="required" title="required">*</abbr>'
            ), isset($post_data['iv_billing_initials']) ? $post_data['iv_billing_initials'] : null); 
        ?>
    </div>
    <?php endif; ?>
</div>