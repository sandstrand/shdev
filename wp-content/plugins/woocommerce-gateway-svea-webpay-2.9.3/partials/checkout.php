<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div class="svea-fields">
    <div class="part-pay-campaign">
    </div>
    <div class="svea-customer-type-container">
        <?php 
            woocommerce_form_field( 'billing_customer_type', array(
                'type'          => 'select',
                'required'      => false,
                'class'         => array('form-row-wide'),
                'options'       => array(
                                        'false'         => __('- Choose customer type -', 'sveawebpay'),
                                        'company'       => __('Company', 'sveawebpay'),
                                        'individual'    => __('Individual', 'sveawebpay')
                                    ),
                'label'         => __('Customer Type', 'sveawebpay') . ' <abbr class="required" title="required">*</abbr>'
            ), false); 
        ?>
    </div>
    <div class="svea-organisation-number-container">
        <?php 
            woocommerce_form_field( 'billing_org_number', array(
                'type'          => 'text',
                'required'      => false,
                'class'         => array('form-row-wide'),
                'label'         => __('Organisation number', 'sveawebpay') . ' <abbr class="required" title="required">*</abbr>'
            )); 
        ?>
    </div>
    <div class="svea-personal-number-container">
        <?php 
            woocommerce_form_field( 'billing_ssn', array(
                'type'          => 'text',
                'required'      => false,
                'class'         => array('form-row-wide'),
                'label'         => __('Personal number', 'sveawebpay') . ' <abbr class="required" title="required">*</abbr>'
            )); 
        ?>
    </div>
    <div class="svea-get-address-button-container">
        <a class="svea-get-address-button" href="#"><?php _e('Get address', WC_SveaWebPay_Gateway::PLUGIN_SLUG ); ?></a>
    </div>
    <input type="hidden" name="address_selector" class="address-selector" />
    <div class="svea-org-address-selector-container">
        <p class="form-row form-row-wide">
            <select class="org-address-selector"></select>
        </p>
    </div>
    <div class="svea-vat-number-container">
        <?php 
            woocommerce_form_field( 'billing_vat_number', array(
                'type'          => 'text',
                'required'      => false,
                'class'         => array('form-row-wide'),
                'label'         => __('VAT number', 'sveawebpay') . ' <abbr class="required" title="required">*</abbr>'
            )); 
        ?>
    </div>
    <div class="svea-birth-date-container">
        <?php _e('Date of birth', 'sveawebpay'); ?>
        <?php
            $current_year = intval(date('Y'));

            $years = array_combine(
                range($current_year, $current_year - 100),
                range($current_year, $current_year - 100)
            );

            woocommerce_form_field( 'birth_date_year', array(
                'type'          => 'select',
                'required'      => false,
                'class'         => array('form-row-wide'),
                'label'         => __('Year', 'sveawebpay') . ' <abbr class="required" title="required">*</abbr>',
                'options'       => $years
            )); 

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

            woocommerce_form_field( 'birth_date_month', array(
                'type'          => 'select',
                'required'      => false,
                'class'         => array('form-row-wide'),
                'label'         => __('Month', 'sveawebpay') . ' <abbr class="required" title="required">*</abbr>',
                'options'       => $months
            ));

            $days = array_combine(range(1, 31), range(1, 31));

            woocommerce_form_field( 'birth_date_day', array(
                'type'          => 'select',
                'required'      => false,
                'class'         => array('form-row-wide'),
                'label'         => __('Day', 'sveawebpay') . ' <abbr class="required" title="required">*</abbr>',
                'options'       => $days
            ));
        ?>
    </div>
    <div class="svea-initials-container">
        <?php 
            woocommerce_form_field( 'billing_initials', array(
                'type'          => 'text',
                'required'      => false,
                'class'         => array('form-row-wide'),
                'label'         => __('Initials', 'sveawebpay') . ' <abbr class="required" title="required">*</abbr>'
            )); 
        ?>
    </div>
</div>