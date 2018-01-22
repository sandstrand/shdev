<?php if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div class="svea-get-address-button-container get-address-shortcode">
	<div class="customer-type-container">
		<input type="radio" class="input-radio" value="individual" name="svea_get_address_customer_type" id="svea_get_address_customer_type_individual" checked="true" />
		<label class="radio" for="svea_get_address_customer_type_individual">
			<?php _e( 'Individual', WC_SveaWebPay_Gateway::PLUGIN_SLUG ); ?>
		</label>
		<input type="radio" class="input-radio" value="company" name="svea_get_address_customer_type" id="svea_get_address_customer_type_company" />
		<label class="radio" for="svea_get_address_customer_type_company">
			<?php _e( 'Company', WC_SveaWebPay_Gateway::PLUGIN_SLUG ); ?>
		</label>
	</div>
	<div class="svea-get-address-button-inner">
		<div class="organisation-number-container">
			<label for="svea_billing_org_number">
				<?php _e( 'Organisation number', WC_SveaWebPay_Gateway::PLUGIN_SLUG ); ?>
			</label>
			<input type="text" class="input-text" name="svea_billing_org_number" id="svea_billing_org_number" />
			<div class="org-address-selector-container">
				<select class="org-address-selector"></select>
			</div>
		</div>
		<div class="personal-number-container">
			<label for="svea_billing_ssn">
				<?php _e( 'Personal number', WC_SveaWebPay_Gateway::PLUGIN_SLUG ); ?>
			</label>
			<input type="text" class="input-text" name="svea_billing_ssn" id="svea_billing_ssn" />
		</div>
		<a class="svea-get-address-button" href="#"><?php _e( 'Get address', WC_SveaWebPay_Gateway::PLUGIN_SLUG ); ?></a>
	</div>
</div>
