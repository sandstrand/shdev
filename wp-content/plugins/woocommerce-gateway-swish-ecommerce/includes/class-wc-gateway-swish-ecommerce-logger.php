<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Gateway_Swish_Ecommerce_Logger class.
 *
 * Uses WC Logger to log events.
 *
 */
class WC_Gateway_Swish_Ecommerce_Logger {

	/** @var WC_Logger Logger instance */
	private $logger = false;

	/**
	 * Logging function.
	 *
	 * @param string $message Error message.
	 * @param string $level   Error level.
	 */
	public function log( $message, $level = 'info' ) {
		if ( $this->log_enabled() ) {
			if ( empty( $this->logger ) ) {
				$this->logger = wc_get_logger();
			}

			$this->logger->log( $level, $message, array( 'source' => 'swish-ecommerce' ) );
		}
	}

	/**
	 * Checks if logging is enabled in plugin settings.
	 *
	 * @return bool
	 */
	private function log_enabled() {
		$settings = get_option( 'woocommerce_redlight_swish-ecommerce_settings' );

		return ( null !== $settings['debug'] && 'yes' === $settings['debug'] );
	}

}
