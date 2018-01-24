<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use Svea\WebPay\Config\ConfigurationProvider;
use Svea\WebPay\Config\ConfigurationService;

class WC_Svea_Config_Test implements ConfigurationProvider {

	public function __construct($merchant_id, $secret_word, $client_nr, $password, $username) {
		$this->merchant_id = $merchant_id;
		$this->secret_word = $secret_word;
		$this->client_nr = $client_nr;
		$this->password = $password;
		$this->username = $username;
	}

	public function getEndPoint( $type ) {
		if ( $type == ConfigurationProvider::HOSTED_TYPE ) {
			return ConfigurationService::SWP_TEST_URL;
		} else if ( $type == ConfigurationProvider::INVOICE_TYPE || $type == ConfigurationProvider::PAYMENTPLAN_TYPE ) {
			return ConfigurationService::SWP_TEST_WS_URL;
		} else if ( $type == ConfigurationProvider::HOSTED_ADMIN_TYPE ) {
			return ConfigurationService::SWP_TEST_HOSTED_ADMIN_URL;
		} else if ( $type == ConfigurationProvider::ADMIN_TYPE ) {
            return ConfigurationService::SWP_TEST_ADMIN_URL;
        } else if( $type == ConfigurationProvider::PREPARED_URL ) {
        	return ConfigurationService::SWP_TEST_PREPARED_URL;
		} else {
			throw new Exception("Error Processing Request! The request did not match any request type. Accepted values: INVOICE, PAYMENTPLAN, HOSTED_ADMIN or HOSTED");
		}
	}

	public function getUsername( $type, $country ) {
		return $this->username;
	}

	public function getPassword( $type, $country ) {
		return $this->password;
	}

	public function getClientNumber( $type, $country ) {
		return $this->client_nr;
	}

	public function getMerchantId( $type, $country ) {
		return $this->merchant_id;
	}

	public function getSecret( $type, $country ) {
		return $this->secret_word;
	}

	public function getIntegrationPlatform() {
		return 'WooCommerce';
	}

	public function getIntegrationCompany() {
		return 'The Generation';
	}

	public function getIntegrationVersion() {
		if( class_exists( 'WC_SveaWebPay_Gateway' ) ) {
			return WC_SveaWebPay_Gateway::VERSION;
		}

		return '';
	}

	public function getCheckoutMerchantId() {
		return false;
	}

	public function getCheckoutSecret() {
		return false;
	}
}