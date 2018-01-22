<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div class="svea-fields svea-invoice-fields svea-fields-admin">
    <div class="customer-type-container">
        <?php 
            woocommerce_form_field( '_iv_billing_customer_type', array(
                'type'          => 'select',
                'required'      => false,
                'class'         => array('form-row-wide'),
                'options'       => array(
                                        'false'         => __('- Choose customer type -', 'sveawebpay'),
                                        'company'       => __('Company', 'sveawebpay'),
                                        'individual'    => __('Individual', 'sveawebpay')
                                    ),
                'label'         => __('Customer Type', 'sveawebpay') . ' <abbr class="required" title="required">*</abbr>'
            ), isset( $post_data['iv_billing_customer_type'] ) ? $post_data['iv_billing_customer_type'] : false); 
        ?>
    </div>
    <div class="organisation-number-container">
        <?php 
            woocommerce_form_field( '_iv_billing_org_number', array(
                'type'          => 'text',
                'required'      => false,
                'class'         => array('form-row-wide'),
                'label'         => __('Organisation number', 'sveawebpay') . ' <abbr class="required" title="required">*</abbr>'
            ), isset( $post_data['iv_billing_org_number'] ) ? $post_data['iv_billing_org_number'] : null); 
        ?>
    </div>
    <div class="personal-number-container">
        <?php 
            woocommerce_form_field( '_iv_billing_ssn', array(
                'type'          => 'text',
                'required'      => false,
                'class'         => array('form-row-wide'),
                'label'         => __('Personal number', 'sveawebpay') . ' <abbr class="required" title="required">*</abbr>'
            ), isset( $post_data['iv_billing_ssn'] ) ? $post_data['iv_billing_ssn'] : null); 
        ?>
    </div>
    <div class="svea-get-address-button-container">
        <a class="svea-get-address-button" href="#"><?php _e('Get address', WC_SveaWebPay_Gateway::PLUGIN_SLUG ); ?></a>
    </div>
    <div class="org-address-selector-container">
        <p class="form-row form-row-wide">
            <select class="org-address-selector"></select>
        </p>
        <input type="hidden" name="_address_selector" class="address-selector" />
    </div>
    <div class="vat-number-container">
        <?php 
            woocommerce_form_field( '_iv_billing_vat_number', array(
                'type'          => 'text',
                'required'      => false,
                'class'         => array('form-row-wide'),
                'label'         => __('VAT number', 'sveawebpay') . ' <abbr class="required" title="required">*</abbr>'
            ), isset( $post_data['iv_billing_vat_number'] ) ? $post_data['iv_billing_vat_number'] : null); 
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

            woocommerce_form_field( '_iv_birth_date_year', array(
                'type'          => 'select',
                'required'      => false,
                'class'         => array('form-row-wide birth-date-year'),
                'label'         => __('Year', 'sveawebpay') . ' <abbr class="required" title="required">*</abbr>',
                'options'       => $years
            ), isset( $post_data['iv_birth_date_year'] ) ? $post_data['iv_birth_date_year'] : null); 

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

            woocommerce_form_field( '_iv_birth_date_month', array(
                'type'          => 'select',
                'required'      => false,
                'class'         => array('form-row-wide birth-date-month'),
                'label'         => __('Month', 'sveawebpay') . ' <abbr class="required" title="required">*</abbr>',
                'options'       => $months
            ), isset( $post_data['iv_birth_date_month'] ) ? $post_data['iv_birth_date_month'] : null);

            $days = array_combine(range(1, 31), range(1, 31));

            woocommerce_form_field( '_iv_birth_date_day', array(
                'type'          => 'select',
                'required'      => false,
                'class'         => array('form-row-wide birth-date-day'),
                'label'         => __('Day', 'sveawebpay') . ' <abbr class="required" title="required">*</abbr>',
                'options'       => $days
            ), isset( $post_data['iv_birth_date_day'] ) ? $post_data['iv_birth_date_day'] : null);
        ?>
    </div>
    <div class="initials-container">
        <?php 
            woocommerce_form_field( '_iv_billing_initials', array(
                'type'          => 'text',
                'required'      => false,
                'class'         => array('form-row-wide'),
                'label'         => __('Initials', 'sveawebpay') . ' <abbr class="required" title="required">*</abbr>'
            ), isset( $post_data['iv_billing_initials'] ) ? $post_data['iv_billing_initials'] : null); 
        ?>
    </div>
</div>