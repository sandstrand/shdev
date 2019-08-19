<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use Svea\WebPay\Constant\DistributionType;
use Svea\WebPay\WebPay;
use Svea\WebPay\WebPayItem;
use Svea\WebPay\WebPayAdmin;

class WC_Gateway_Svea_Invoice extends WC_Payment_Gateway {
	
	/**
	 * Id of this gateway
	 *
	 * @var 	string
	 */
	const GATEWAY_ID = 'sveawebpay_invoice';

	/**
	 * Static instance of this class
	 *
     * @var WC_Gateway_Svea_Invoice
     */
    private static $instance = null;

    private static $log_enabled = false;
    private static $log = null;

    public static function init() {
        if ( is_null( self::$instance ) ) {
            $instance = new WC_Gateway_Svea_Invoice;
        }
        
        return self::$instance;
    }

	public function __construct() {
		if( is_null( self::$instance ) )
			self::$instance = $this;

		$this->supports = array(
			'products',
			'subscriptions',
			'subscription_cancellation',
			'subscription_suspension',
			'subscription_reactivation',
			'multiple_subscriptions',
			'subscription_amount_changes',
			'subscription_date_changes',
			'subscription_payment_method_change',
			'subscription_payment_method_change_customer',
			'subscription_payment_method_change_admin',
			//'refunds'
		);

		$this->id = self::GATEWAY_ID;

		$this->method_title = __( 'SveaWebPay Invoice Payment', WC_SveaWebPay_Gateway::PLUGIN_SLUG );
		$this->icon = apply_filters( 'woocommerce_sveawebpay_invoice_icon', 'https://cdn.svea.com/sveaekonomi/rgb_ekonomi_large.png' );
		$this->has_fields = true;

		$this->svea_invoice_currencies = array(
			'DKK' => 'DKK', // Danish Kroner
			'EUR' => 'EUR', // Euro
			'NOK' => 'NOK', // Norwegian Kroner
			'SEK' => 'SEK'  // Swedish Kronor
		);

		$this->init_form_fields();
		$this->init_settings();

		$this->title = __( $this->get_option( 'title' ), WC_SveaWebPay_Gateway::PLUGIN_SLUG );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_scheduled_subscription_payment_' . $this->id, array( $this, 'scheduled_subscription_payment' ), 10, 2 );
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'add_subscription_order_fields' ), 10, 1 );
		add_action( 'woocommerce_process_shop_subscription_meta', array( $this, 'save_subscription_meta' ), 10, 4 );

		$this->same_shipping_as_billing = $this->get_option( 'same_shipping_as_billing' ) === "yes";

		if( ! isset( WC()->customer ) ) {
			return;
		}

		$customer_country = strtolower( WC()->customer->get_billing_country() );

		//Merchant set fields
		$this->enabled = $this->get_option( 'enabled' );

		// Set logo by customer country
		$this->icon = $this->get_svea_invoice_logo_by_country( $customer_country );

		$wc_countries = new WC_Countries();

		$this->base_country = $wc_countries->get_base_country();

		$this->enabled_countries = is_array( $this->get_option( 'enabled_countries' ) ) ?
									$this->get_option( 'enabled_countries' ) : array();

		$this->selected_currency = get_woocommerce_currency();

		$this->testmode = $this->get_option( 'testmode_' . $customer_country ) === "yes";
		$this->distribution_type = $this->get_option( 'distribution_type_' . $customer_country );
		$this->username = $this->get_option( 'username_' . $customer_country );
		$this->password = $this->get_option( 'password_' . $customer_country );
		$this->client_nr = $this->get_option( 'client_nr_' . $customer_country );
		$this->invoice_fee_label = $this->get_option( 'invoice_fee_label_' . $customer_country );
		$this->invoice_fee = $this->get_option( 'invoice_fee_' . $customer_country );
		$this->invoice_fee_tax = $this->get_option( 'invoice_fee_tax_' . $customer_country );
		$this->invoice_fee_taxable = $this->get_option( 'invoice_fee_taxable_' . $customer_country ) === "yes";

		self::$log_enabled = $this->get_option( 'debug' ) === "yes";

		$config_class = $this->testmode ? "WC_Svea_Config_Test" : "WC_Svea_Config_Production";

		$this->config = new $config_class( false, false, $this->client_nr, $this->password, $this->username );
	
		$this->description = __( $this->get_option( 'description' ), WC_SveaWebPay_Gateway::PLUGIN_SLUG );
	}

	/**
	 * Get Svea Invoice logo depending on country
	 *
	 * @param string $country
	 *
	 * @return string URL of the invoice logo
	 */
	public function get_svea_invoice_logo_by_country( $country = '' ) {
        $default_logo = apply_filters( 'woocommerce_sveawebpay_invoice_icon', 'https://cdn.svea.com/sveaekonomi/rgb_ekonomi_large.png' );

        $country = strtoupper( $country );

        $logos = array(
            'SE' => 'https://cdn.svea.com/webpay/buttons/sv/Button_Invoice_PosWhite_SV.png',
            'NO' => 'https://cdn.svea.com/webpay/buttons/no/Button_Invoice_PosWhite_NO.png',
            'FI' => 'https://cdn.svea.com/webpay/buttons/fi/Button_Invoice_PosWhite_FI.png',
            'DE' => 'https://cdn.svea.com/webpay/buttons/de/Button_Invoice_PosWhite_DE.png',
        );

        $logo = $default_logo;

        if( isset( $logos[$country] ) ) {
            $logo = $logos[$country];
        }

        return apply_filters( 'woocommerce_sveawebpay_invoice_icon', $logo, $country );
    }

	/**
     * Logging method.
     * @param string $message
     */
    public static function log( $message ) {
        if ( self::$log_enabled ) {
            if ( is_null( self::$log ) ) {
                self::$log = new WC_Logger();
            }

            self::$log->add( self::GATEWAY_ID, $message );
        }
    }

	public static function display_admin_action_buttons() {
		?>
		<button type="button" class="button svea-credit-items"><?php _e( 'Credit via svea', WC_SveaWebPay_Gateway::PLUGIN_SLUG ); ?></button>
		<button type="button" class="button svea-deliver-items"><?php _e( 'Deliver via svea', WC_SveaWebPay_Gateway::PLUGIN_SLUG ); ?></button>
		<?php
	}

	public static function admin_functions_meta_box() {
		$order = new WC_Order( get_the_ID() );

		$deliver_nonce = wp_create_nonce( WC_SveaWebPay_Gateway_Admin_Functions::DELIVER_NONCE );
		$cancel_nonce = wp_create_nonce( WC_SveaWebPay_Gateway_Admin_Functions::CANCEL_NONCE );
		$credit_nonce = wp_create_nonce( WC_SveaWebPay_Gateway_Admin_Functions::CREDIT_NONCE );

		?>
		<a href="<?php echo admin_url( 'admin-post.php?action=svea_webpay_admin_deliver_order&order_id=' . get_the_ID() . '&security=' . $deliver_nonce ); ?>">
			<?php _e( 'Deliver order', WC_SveaWebPay_Gateway::PLUGIN_SLUG ); ?>
		</a><br>
		<a href="<?php echo admin_url( 'admin-post.php?action=svea_webpay_admin_cancel_order&order_id=' . get_the_ID() . '&security=' . $cancel_nonce ); ?>">
			<?php _e( 'Cancel order', WC_SveaWebPay_Gateway::PLUGIN_SLUG ); ?>
		</a><br>
		<a href="<?php echo admin_url( 'admin-post.php?action=svea_webpay_admin_credit_order&order_id=' . get_the_ID() . '&security=' . $credit_nonce ); ?>">
			<?php _e( 'Credit invoice', WC_SveaWebPay_Gateway::PLUGIN_SLUG ); ?>
		</a>
		<?php
	}

	/**
	 * Check whether or not this payment gateway is available
	 *
	 * @return boolean
	 */
	public function is_available() {
		if( ! parent::is_available() ) {
			return false;
		}

		if( ! is_admin() ) {
			if( ! $this->check_customer_country() ) {
				return false;
			} else if( ! $this->check_customer_currency() ) {
				return false;
			}
		}

		return true;
	}

	/**
     * Check if the current currency is supported
     *
     * @return  boolean
     */
	public function check_customer_currency() {
		$country_currency = WC_Gateway_Svea_Helper::get_country_currency( WC()->customer->get_billing_country() );

		if( ! isset( $country_currency[0] )
			|| $country_currency !== $this->selected_currency ) {
			return false;
		}

		return true;
	}

	/**
     * Check if the current currency is supported and enabled
     *
     * @return  boolean
     */
	public function check_customer_country() {
		$customer_country = strtoupper( WC()->customer->get_billing_country() );

		if( ! in_array( $customer_country, $this->enabled_countries ) ) {
			return false;
		}

		return true;
	}

	public function is_enabled() {
		if( $this->enabled != 'yes' ) {
			return false;
		}

		return true;
	}

	/**
	 * Display payment fields at checkout
	 *
	 * @return void
	 */
	public function payment_fields() {
		echo $this->description;

		$post_data = array();

		if( isset($_POST["post_data"]) ) {
			$values = explode("&", $_POST["post_data"]);
			if(count($values) > 0) {
				foreach($values as $value) {
					$explode = explode("=", $value);
					if(count($explode) !== 2)
						continue;
					$post_data[$explode[0]] = $explode[1];
				}
			}
		}

		$country = strtoupper( WC()->customer->get_billing_country() );

		$invoice_checkout_template = locate_template( 'woocommerce-gateway-svea-webpay/invoice/checkout.php' );

		if( $invoice_checkout_template == '' ) {
			$invoice_checkout_template = WC_SVEAWEBPAY_GATEWAY_PLUGIN_DIR . 'partials/invoice/checkout.php';
		}

		include( $invoice_checkout_template );
	}

	public function is_testmode() {
		return $this->testmode;
	}

	/**
	 * Adds a fee to the cart if the payment method is SveaWebPay Invoice
	 *
	 * @return void
	 */
	public function add_invoice_fee() {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}

		$fees_total = 0.0;

		foreach( WC()->cart->get_fees() as $fee ) {
			$fees_total += $fee->amount;
		}

		/**
		 * Calculate totals of the cart based on
		 * cart contents total, cart shipping total and cart fees total
		 */
		$cart_total = WC()->cart->cart_contents_total + WC()->cart->shipping_total + $fees_total;

		if ( isset( WC()->cart )
			&& strlen( (string) $this->invoice_fee ) > 0
			&& is_numeric( $this->invoice_fee )
			&& $cart_total > 0
			&& $this->invoice_fee > 0 ) {
			
			WC()->cart->add_fee( $this->invoice_fee_label, floatval( $this->invoice_fee ), $this->invoice_fee_taxable, '' );
		}
	}

	/**
	 * See if we can ship to a different address
	 *
	 * @return  boolean
	 */
	public function can_ship_to_different_address() {
		if( ! isset( $this->same_shipping_as_billing ) ) {
			return false;
		}

		return $this->same_shipping_as_billing == false;
	}

	/**
	 * Validates the data passed after the customer clicks confirm at checkout
	 *
	 * @return 	void
	 */
	public function checkout_validation_handler() {
		if( ! isset( $_POST["payment_method"] ) ) {
			return;
		}

		$payment_method = $_POST["payment_method"];

		if( $payment_method != $this->id ) {
			return;
		}

		if( isset( $_POST['billing_country'] ) ) {
			$customer_country = strtoupper( $_POST['billing_country'] );
		} else {
			$wc_countries = new WC_Countries();

			$customer_country = $wc_countries->get_base_country();
		}

		// If we can only ship to the billing address, prevent shipping address
		if( ! $this->can_ship_to_different_address() ) {
			$_POST["ship_to_different_address"] = '';
		}

		if( ! isset($_POST["iv_billing_customer_type"]) ) {
			wc_add_notice( __( 'Please select either company or individual customer type.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
			return;
		}

		$customer_type = $_POST["iv_billing_customer_type"];
		switch( $customer_country ) {
			case "SE":
			case "DK":
				$request = WebPay::getAddresses( $this->get_config( $customer_country ) );
				$request->setOrderTypeInvoice();
				$request->setCountryCode( $customer_country );
				if( $customer_type == "company" ) {
					if( ! isset($_POST["address_selector"]) ) {
						wc_add_notice( __( '<strong>Organisational number</strong> is a required field.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
						return;
					} else if( ! isset($_POST["iv_billing_org_number"]) || $_POST["iv_billing_org_number"] == "" ) {
						wc_add_notice( __( '<strong>Organisational number</strong> is a required field.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
						return;
					}
					$request->setCustomerIdentifier($_POST["iv_billing_org_number"])
							->getCompanyAddresses();
				} else if( $customer_type == "individual" ) {
					if( ! isset($_POST["iv_billing_ssn"]) || $_POST["iv_billing_ssn"] == "" ) {
						wc_add_notice( __( '<strong>Personal number</strong> is a required field.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error');
						return;
					}
					$request->setCustomerIdentifier($_POST["iv_billing_ssn"])
							->getIndividualAddresses();
				} else {
					wc_add_notice( __( 'Invalid customer type', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
					return;
				}
				$response = $request->doRequest();
				$result_code = $response->resultcode;


				if( $result_code == "Accepted" ) {
					if( isset($_POST["address_selector"]) && strlen( $_POST["address_selector"] ) > 0 ) {
						foreach($response->customerIdentity as $ci) {
							if($ci->addressSelector == $_POST["address_selector"]) {
								$selected_address = $ci;
								break;
							}
						}
					} else
						$selected_address = $response->customerIdentity[0];
					if( ! isset( $selected_address ) ) {
						wc_add_notice( __( 'We could not find your address in our database.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error');
						return;
					}
					if( $customer_type == "company" ) {
						$_POST["billing_company"] = $selected_address->fullName;
					}
					if( strlen( $selected_address->firstName ) > 0 )
						$_POST["billing_first_name"] = $selected_address->firstName;
					if( strlen( $selected_address->lastName ) > 0 )
						$_POST["billing_last_name"] = $selected_address->lastName;
					$_POST["billing_address_1"] = $selected_address->street;
					$_POST["billing_address_2"] = $selected_address->coAddress;
					$_POST["billing_postcode"] = $selected_address->zipCode;
					$_POST["billing_city"] = $selected_address->locality;
				} else if($result_code == "Error" || $result_code == "NoSuchEntity" ) {
					if( $customer_type == "company" ) {
						wc_add_notice( __( 'The <strong>Organisational number</strong> is not valid.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
					} else if( $customer_type == "individual" ) {
						wc_add_notice( __( 'The <strong>Personal number</strong> is not valid.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
					}
					return;
				} else {
					wc_add_notice( __( $_POST["iv_billing_customer_type"] == "company" ? "An unknown error occurred while trying to lookup your Organisational number."
					 : $_POST["iv_billing_customer_type"] == "individual" ? "An unknown error occurred while trying to lookup your Personal number."
					 : "An unknown error occurred." ), 'error');
					return;
				}
			break;
			case "NO":
				if( $customer_type == "company" ) {
					$request = WebPay::getAddresses( $this->get_config( $customer_country ) )
							->setOrderTypeInvoice()
							->setCountryCode($customer_country);

					if( ! isset( $_POST["address_selector"] ) ) {
						wc_add_notice( __( '<strong>Organisational number</strong> is a required field.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
						return;
					} else if( ! isset($_POST["iv_billing_org_number"]) || strlen( $_POST["iv_billing_org_number"] ) <= 0 ) {
						wc_add_notice( __( '<strong>Organisational number</strong> is a required field.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
						return;
					}

					$request->setCustomerIdentifier($_POST["iv_billing_org_number"])
							->getCompanyAddresses();

					$response = $request->doRequest();
					$result_code = $response->resultcode;

					if( $result_code == 'Accepted' ) {
						foreach( $response->customerIdentity as $ci ) {
							if( $ci->addressSelector == $_POST["address_selector"] ) {
								$selected_address = $ci;
								break;
							}
						}

						if( ! isset($selected_address) ) {
							wc_add_notice( __( '<strong>Organisational number</strong> is a required field.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
							return;
						}

						if( ! empty( $selected_address->firstName ) ) {
							$_POST["billing_first_name"] = $selected_address->firstName;
						}

						if( ! empty( $selected_address->lastName ) ) {
							$_POST["billing_last_name"] = $selected_address->lastName;
						}

						$_POST["billing_company"] = $selected_address->fullName;
						$_POST["billing_address_1"] = $selected_address->street;
						$_POST["billing_address_2"] = $selected_address->coAddress;
						$_POST["billing_postcode"] = $selected_address->zipCode;
						$_POST["billing_city"] = $selected_address->locality;
					} else if( $result_code == 'Error' || $result_code == 'NoSuchEntity' ) {
						wc_add_notice( __( $response['errormessage'], WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
					} else {
						wc_add_notice( __( "An unknown error occurred while trying to lookup your Organisational number.", WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
						return;
					}
				} else if( $customer_type == "individual" ) {
					if( ! isset($_POST["iv_billing_ssn"]) || $_POST["iv_billing_ssn"] == "" ) {
						wc_add_notice( __( '<strong>Personal number</strong> is a required field.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
						return;
					}
				} else {
					wc_add_notice( __( 'Invalid customer type', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
					return;
				}
			break;
			case "FI":
				if( $customer_type == "company" ) {
					if( ! isset($_POST["iv_billing_org_number"]) || $_POST["iv_billing_org_number"] == "" ) {
						wc_add_notice( __( '<strong>Organisational number</strong> is a required field.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
						return;
					} else if(strlen( $_POST["iv_billing_org_number"] ) < 8 ) {
						wc_add_notice( __( 'The <strong>Organisational number</strong> entered is not correct.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
						return;
					}
				} else if( $customer_type == "individual" ) {
					if( ! isset($_POST["iv_billing_ssn"]) || $_POST["iv_billing_ssn"] == "" ) {
						wc_add_notice( __( '<strong>Personal number</strong> is a required field.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
						return;
					}
				} else {
					wc_add_notice( __( 'Invalid customer type', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
					return;
				}
			break;
			case "NL":
				if( $customer_type == "company" ) {
					if( ! isset( $_POST["iv_billing_vat_number"] ) ) {
						wc_add_notice( __( '<strong>VAT number</strong> is a required field.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
						return;
					}
					if( ! isset( $_POST["billing_company"] ) || strlen( $_POST["billing_company"] ) <= 0 ) {
						wc_add_notice( __( '<strong>Company Name</strong> is a required field.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
						return;
					}
				} else if( $customer_type == "individual" ) {
					if( ! isset( $_POST["iv_billing_initials"] ) || strlen( $_POST["iv_billing_initials"] ) < 2 ) {
						wc_add_notice( __( '<strong>Initials</strong> is a required field.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
						return;
					}
					if( ! isset( $_POST["iv_birth_date_year"] ) || ! isset( $_POST["iv_birth_date_month"] ) || ! isset( $_POST["iv_birth_date_day"] ) ) {
						wc_add_notice( __( '<strong>Date of birth</strong> is a required field.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
						return;
					}
				} else {
					wc_add_notice( __( 'Invalid customer type', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
					return;
				}
			break;
			case "DE":
				if( $customer_type == "company" ) {
					if( ! isset($_POST["iv_billing_vat_number"]) ) {
						wc_add_notice( __( '<strong>VAT number</strong> is a required field.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
						return;
					}
					if( ! isset($_POST["billing_company"]) || strlen( $_POST["billing_company"] ) == 0 ) {
						wc_add_notice( __( '<strong>Company Name</strong> is a required field.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
						return;
					}
				} else if( $customer_type == "individual" ) {
					if( ! isset( $_POST["iv_birth_date_year"] ) || ! isset( $_POST["iv_birth_date_month"] ) || ! isset( $_POST["iv_birth_date_day"] ) ) {
						wc_add_notice( __( '<strong>Date of birth</strong> is a required field.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
						return;
					}
				} else {
					wc_add_notice( __( 'Invalid customer type', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
					return;
				}
			break;
		}
	}

	public function invoice_is_active_and_set() {
		if ( ! array_key_exists( get_woocommerce_currency(), $this->svea_invoice_currencies ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Display options in admin or an error if something is not right
	 *
	 * @return 	void
	 */
	public function admin_options() {
		?>
		<h3> <?php _e('SveaWebPay Invoice Payment', WC_SveaWebPay_Gateway::PLUGIN_SLUG)?> </h3>
		<p> <?php _e('Process invoice payments through SveaWebPay.', WC_SveaWebPay_Gateway::PLUGIN_SLUG)?> </p>
		<?php if ( $this->invoice_is_active_and_set() ): ?>
		<table class="form-table">
			<?php
			 	// Generate the HTML For the settings form.
	    		$this->generate_settings_html();
			?>
		</table>
		<h3><?php _e( 'Get Address Shortcode', WC_SveaWebPay_Gateway::PLUGIN_SLUG ); ?></h3>
		<p>
			<?php _e( 'If you want to move the Get-Address box on the checkout page you can do so with the shortcode', WC_SveaWebPay_Gateway::PLUGIN_SLUG ); ?> <code>[svea_get_address]</code>
		</p>
		<p>
			<?php _e( 'By using the shortcode on the checkout page, you automatically disable the Get-Address box in the gateways. You can only use the shortcode if you have a valid Svea Invoice account.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ); ?>
		</p>
		<p>
			<?php _e( 'The shortcode is usable for the countries in which you are using Svea Invoice, independentenly of the payment method.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ); ?>
		</p>
		<?php else : ?>
		<div class="inline error"><p><?php _e( 'Sveawebpay does not support your currency and or country.', WC_SveaWebPay_Gateway::PLUGIN_SLUG); ?></p></div>
		<?php
		endif;
	}

	/**
	 * Initialize our options for this gateway
	 *
	 * @return 	void
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
						'title' => __( 'Enable/disable', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
						'type' => 'checkbox',
						'label' => __('Enable SveaWebPay Invoice Payment', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => 'no'
				),
			'title' => array(
						'title' => __( 'Title', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
						'type' => 'text',
						'description' => __( 'This controls the title which the user sees during checkout', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
						'default' => __( 'Invoice 14 days', WC_SveaWebPay_Gateway::PLUGIN_SLUG )
				),
			'description' => array(
						'title' => __( 'Description', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
						'type' => 'textarea',
						'description' => __('This controls the description the user sees during checkout', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => __( 'Pay with invoice through Svea Ekonomi', WC_SveaWebPay_Gateway::PLUGIN_SLUG )
				),
			'enabled_countries' => array(
						'title' => __( 'Enabled countries', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
						'type' => 'multiselect',
						'description' => __( 'Choose the countries you want SveaWebPay Invoice Payment to be active in', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
						'options' => array(
          									'DK' => __('Denmark', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
          									'DE' => __('Germany', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
          									'FI' => __('Finland', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
          									'NL' => __('Netherlands', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
          									'NO' => __('Norway', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
          									'SE' => __('Sweden', WC_SveaWebPay_Gateway::PLUGIN_SLUG)
          								),
						'default' => ''
				),
			'same_shipping_as_billing' => array(
						'title' => __('Same shipping address as billing address', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'checkbox',
						'description' => __('If checked, billing address will override the shipping address', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => 'yes'
				),
			//Denmark
			'testmode_dk' => array(
						'title' => __( 'Test mode Denmark', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
						'type' => 'checkbox',
						'label' => __( 'Enable/disable test mode in Denmark', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
						'description' => __( 'When testing out the gateway, this option should be enabled', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
						'default' => 'no'
				),
			'distribution_type_dk' => array(
						'title' => __('Distribution type Denmark', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'select',
						'description' => __('This controls which distribution type you will be using for invoices in Denmark', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'options'	=> array(
											DistributionType::POST 	=> __( 'Post', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
											DistributionType::EMAIL => __( 'E-mail', WC_SveaWebPay_Gateway::PLUGIN_SLUG )
										),
						'default' => DistributionType::EMAIL
				),
			'username_dk' => array(
						'title' => __('Username Denmark', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'text',
						'description' => __('Username for invoice payments in Denmark', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => ''
				),
			'password_dk' => array(
						'title' => __('Password Denmark', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'password',
						'description' => __('Password for invoice payments in Denmark', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => ''
				),
			'client_nr_dk' => array(
						'title' => __('Client number Denmark', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'text',
						'description' => __('Client number for invoice payments in Denmark', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => ''
				),
			'invoice_fee_label_dk' => array(
						'title' => __('Invoice fee label Denmark', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'textarea',
						'description' => __('Label for the invoice fee for Denmark', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => 'Invoice fee Denmark'
				),
			'invoice_fee_dk' => array(
						'title' => __('Invoice fee Denmark', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'text',
						'description' => __('Invoice fee for Denmark, this should be entered exclusive of tax', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => ''
				),
			'invoice_fee_taxable_dk' => array(
						'title' => __('Invoice fee taxable Denmark', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'checkbox',
						'description' => __('If the invoice fee is taxable in Denmark, check the box.', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => ''
				),
			//Germany
			'testmode_de' => array(
						'title' => __('Test mode Germany', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'checkbox',
						'label' => __('Enable/disable test mode in Germany', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'description' => __( 'When testing out the gateway, this option should be enabled', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
						'default' => 'no'
				),
			'distribution_type_de' => array(
						'title' => __('Distribution type Germany', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'select',
						'description' => __('This controls which distribution type you will be using for invoices in Germany', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'options'	=> array(
											DistributionType::POST 	=> __( 'Post', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
											DistributionType::EMAIL => __( 'E-mail', WC_SveaWebPay_Gateway::PLUGIN_SLUG )
										),
						'default' => DistributionType::EMAIL
				),
			'username_de' => array(
						'title' => __('Username Germany', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'text',
						'description' => __('Username for invoice payments in Germany', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => ''
				),
			'password_de' => array(
						'title' => __('Password Germany', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'password',
						'description' => __('Password for invoice payments in Germany', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => ''
				),
			'client_nr_de' => array(
						'title' => __('Client number Germany', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'text',
						'description' => __('Client number for invoice payments in Germany', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => ''
				),
			'invoice_fee_label_de' => array(
						'title' => __('Invoice fee label Germany', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'textarea',
						'description' => __('Label for the invoice fee for Germany', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => 'Invoice fee Germany'
				),
			'invoice_fee_de' => array(
						'title' => __('Invoice fee Germany', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'text',
						'description' => __('Invoice fee for Germany, this should be entered exclusive of tax', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => ''
				),
			'invoice_fee_taxable_de' => array(
						'title' => __('Invoice fee taxable Germany', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'checkbox',
						'description' => __('If the invoice fee is taxable in Germany, check the box.'),
						'default' => ''
				),
			//Finland
			'testmode_fi' => array(
						'title' => __( 'Test mode Finland', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
						'type' => 'checkbox',
						'label' => __( 'Enable/disable test mode in Finland', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
						'description' => __( 'When testing out the gateway, this option should be enabled', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
						'default' => 'no'
				),
			'distribution_type_fi' => array(
						'title' => __('Distribution type Finland', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'select',
						'description' => __('This controls which distribution type you will be using for invoices in Finland', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'options'	=> array(
											DistributionType::POST 	=> __( 'Post', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
											DistributionType::EMAIL => __( 'E-mail', WC_SveaWebPay_Gateway::PLUGIN_SLUG )
										),
						'default' => DistributionType::EMAIL
				),
			'username_fi' => array(
						'title' => __('Username Finland', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'text',
						'description' => __('Username for invoice payments in Finland', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => ''
				),
			'password_fi' => array(
						'title' => __('Password Finland', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'password',
						'description' => __('Password for invoice payments in Finland', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => ''
				),
			'client_nr_fi' => array(
						'title' => __('Client number Finland', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'text',
						'description' => __('Client number for invoice payments in Finland', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => ''
				),
			'invoice_fee_label_fi' => array(
						'title' => __('Invoice fee label Finland', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'textarea',
						'description' => __('Label for the invoice fee for Finland', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => 'Invoice fee Finland'
				),
			'invoice_fee_fi' => array(
						'title' => __('Invoice fee Finland', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'text',
						'description' => __('Invoice fee for Finland, this should be entered exclusive of tax', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => ''
				),
			'invoice_fee_taxable_fi' => array(
						'title' => __('Invoice fee taxable Finland', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'checkbox',
						'description' => __('If the invoice fee is taxable in Finland, check the box.'),
						'default' => ''
				),
			//Netherlands
			'testmode_nl' => array(
						'title' => __('Test mode Netherlands', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'checkbox',
						'label' => __('Enable/disable test mode in Netherlands', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'description' => __( 'When testing out the gateway, this option should be enabled', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
						'default' => 'no'
				),
			'distribution_type_nl' => array(
						'title' => __('Distribution type Netherlands', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'select',
						'description' => __('This controls which distribution type you will be using for invoices in Netherlands', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'options'	=> array(
											DistributionType::POST 	=> __( 'Post', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
											DistributionType::EMAIL => __( 'E-mail', WC_SveaWebPay_Gateway::PLUGIN_SLUG )
										),
						'default' => DistributionType::EMAIL
				),
			'username_nl' => array(
						'title' => __('Username Netherlands', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'text',
						'description' => __('Username for invoice payments in Netherlands', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => ''
				),
			'password_nl' => array(
						'title' => __('Password Netherlands', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'password',
						'description' => __('Password for invoice payments in Netherlands', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => ''
				),
			'client_nr_nl' => array(
						'title' => __('Client number Netherlands', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'text',
						'description' => __('Client number for invoice payments in Netherlands', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => ''
				),
			'invoice_fee_label_nl' => array(
						'title' => __('Invoice fee label Netherlands', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'textarea',
						'description' => __('Label for the invoice fee for Netherlands', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => 'Invoice fee Netherlands'
				),
			'invoice_fee_nl' => array(
						'title' => __('Invoice fee Netherlands', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'text',
						'description' => __('Invoice fee for Netherlands, this should be entered exclusive of tax', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => ''
				),
			'invoice_fee_taxable_nl' => array(
						'title' => __('Invoice fee taxable Netherlands', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'checkbox',
						'description' => __('If the invoice fee is taxable in Netherlands, check the box.', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => ''
				),
			//Norway
			'testmode_no' => array(
						'title' => __('Test mode Norway', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'checkbox',
						'label' => __('Enable/disable test mode in Norway', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'description' => __( 'When testing out the gateway, this option should be enabled', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
						'default' => 'no'
				),
			'distribution_type_no' => array(
						'title' => __( 'Distribution type Norway', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
						'type' => 'select',
						'description' => __( 'This controls which distribution type you will be using for invoices in Norway', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
						'options'	=> array(
											DistributionType::POST 	=> __( 'Post', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
											DistributionType::EMAIL => __( 'E-mail', WC_SveaWebPay_Gateway::PLUGIN_SLUG )
										),
						'default' => DistributionType::EMAIL
				),
			'username_no' => array(
						'title' => __('Username Norway', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'text',
						'description' => __('Username for invoice payments in Norway', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => ''
				),
			'password_no' => array(
						'title' => __('Password Norway', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'password',
						'description' => __('Password for invoice payments in Norway', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => ''
				),
			'client_nr_no' => array(
						'title' => __('Client number Norway', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'text',
						'description' => __('Client number for invoice payments in Norway', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => ''
				),
			'invoice_fee_label_no' => array(
						'title' => __('Invoice fee label Norway', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'textarea',
						'description' => __('Label for the invoice fee for Norway', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => 'Invoice fee Norway'
				),
			'invoice_fee_no' => array(
						'title' => __('Invoice fee Norway', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'text',
						'description' => __('Invoice fee for Norway, this should be entered exclusive of tax', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => ''
				),
			'invoice_fee_taxable_no' => array(
						'title' => __('Invoice fee taxable Norway', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'checkbox',
						'description' => __('If the invoice fee is taxable in Norway, check the box.'),
						'default' => ''
				),
			//Sweden
			'testmode_se' => array(
						'title' => __( 'Test mode Sweden', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
						'type' => 'checkbox',
						'label' => __( 'Enable/disable test mode in Sweden', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
						'description' => __( 'When testing out the gateway, this option should be enabled', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
						'default' => 'no'
				),
			'distribution_type_se' => array(
						'title' => __( 'Distribution type Sweden', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
						'type' => 'select',
						'description' => __( 'This controls which distribution type you will be using for invoices in Sweden', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
						'options'	=> array(
											DistributionType::POST 	=> __( 'Post', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
											DistributionType::EMAIL => __( 'E-mail', WC_SveaWebPay_Gateway::PLUGIN_SLUG )
										),
						'default' => DistributionType::EMAIL
				),
			'username_se' => array(
						'title' => __('Username Sweden', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'text',
						'description' => __('Username for invoice payments in Sweden', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => ''
				),
			'password_se' => array(
						'title' => __('Password Sweden', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'password',
						'description' => __('Password for invoice payments in Sweden', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => ''
				),
			'client_nr_se' => array(
						'title' => __('Client number Sweden', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'text',
						'description' => __('Client number for invoice payments in Sweden', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => ''
				),
			'invoice_fee_label_se' => array(
						'title' => __('Invoice fee label Sweden', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'textarea',
						'description' => __('Label for the invoice fee for Sweden', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => 'Invoice fee Sweden'
				),
			'invoice_fee_se' => array(
						'title' => __('Invoice fee Sweden', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'text',
						'description' => __('Invoice fee for Sweden, this should be entered exclusive of tax', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => ''
				),
			'invoice_fee_taxable_se' => array(
						'title' => __('Invoice fee taxable Sweden', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'checkbox',
						'description' => __('If the invoice fee is taxable in Sweden, check the box.'),
						'default' => ''
				),
			'debug' => array(
				'title'       => __( 'Debug log', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable logging', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
				'default'     => 'no',
				'description' => sprintf( __( 'Log Svea events, such as payment requests, inside <code>%s</code>', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), wc_get_log_file_path( self::GATEWAY_ID ) )
			),
			'disable_order_sync' => array(
					'title' => __( 'Disable automatic order sync', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
					'type' => 'checkbox',
					'description' => __( "Disable automatic syncing of orders in WooCommerce to Svea. <br />
						If you enable this option, your refunded orders will not be refunded in Svea. <br />
						Your delivered orders will not be delivered in Svea and your cancelled orders will not be cancelled in Svea. <br />
						<strong>Don't touch this if you don't know what you're doing</strong>.", WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
					'default' => 'no'
				),
		);
	}/** @noinspection PhpUndefinedClassInspection */

	/**
	 * Fetches the distribution type for the provided country
	 *
	 * @param 	$country 	the country that we are getting distribution type from
	 * @return 	string 		the distribution type for the provided country 
	 */
	public function get_distribution_type( $country = '' ) {
		$country = strtolower( $country );
		return $this->get_option( 'distribution_type_' . $country );
	}

	/**
	 * Get the Svea configuration for the provided country
	 *
	 * @param 	mixed 	$country 	optional country 
	 * @return 	WC_Svea_Config_Test|WC_Svea_Config_Production 	svea configuration	
	 */
	public function get_config ( $country = null ) {
		if( ! is_null( $country ) ) {
			$country = strtolower( $country );

			$testmode = $this->get_option( 'testmode_' . $country ) === "yes";
			$username = $this->get_option( 'username_' . $country );
			$password = $this->get_option( 'password_' . $country );
			$client_nr = $this->get_option( 'client_nr_' . $country );

			$config_class = $testmode ? "WC_Svea_Config_Test" : "WC_Svea_Config_Production";

			return new $config_class(false, false, $client_nr, $password, $username);
		} else {
			if( ! isset( $this->config ) ) {
				if ($this->testmode) {
					$this->config = new WC_Svea_Config_Test();
				} else {
					$this->config = new WC_Svea_Config_Production();
				}
			}

			return $this->config;
		}
	}

	/**
	 * Process refunds
	 *
	 * @param 	string 	$order_id 	id of the order being refunded
	 * @param 	float 	$amount 	amount being refunded
	 * @param 	string 	$reason 	reason of the refund
	 *
	 * @return 	boolean
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$order = wc_get_order( $order_id );

		$svea_order_id = wc_get_order_item_meta( $order->get_id(), "svea_order_id" );

        if( ! $svea_order_id || strlen( (string) $svea_order_id ) <= 0 ) {
            return false;
        }

        $response = WebPayAdmin::queryOrder( $this->config )
                    ->setOrderId( $svea_order_id )
                    ->setCountryCode( $order->get_billing_country() )
                    ->queryInvoiceOrder()
                    ->doRequest();

        if( ! $response || ! isset( $response->accepted ) || ! $response->accepted ) {
            return new WP_Error( 'error', $response->errormessage );
        }

        if( ! isset( $response->numberedOrderRows ) || count( $response->numberedOrderRows ) === 0 ) {
        	return new WP_Error( 'no_items', __( 'Couldn\'t find any order rows in Svea', WC_SveaWebPay_Gateway::PLUGIN_SLUG ) );
        }

        $invoice_id = $response->numberedOrderRows[0]->invoiceId;

        if( is_null( $invoice_id ) )
        	return new WP_Error( 'not_delivered', __( 'You have to deliver the order at Svea first', WC_SveaWebPay_Gateway::PLUGIN_SLUG ) );

        $credit_name = __( 'Refund', WC_SveaWebPay_Gateway::PLUGIN_SLUG );

        if( strlen( $reason ) > 0 )
            $credit_name .= ': ' . $reason;

        $response = WebPayAdmin::creditOrderRows( $this->config )
        	->setInvoiceId( $invoice_id )
        	->setCountryCode( $order->get_billing_country() )
        	->setInvoiceDistributionType( $this->get_distribution_type() )
        	->addCreditOrderRow( 
	        	WebPayItem::orderRow()
	        		->setAmountExVat( (float) $amount )
                    ->setVatPercent( 0 )
	        		->setDiscountPercent( 0 )
	        		->setName( $credit_name )
	        		->setQuantity( 1 )
        	)
        	->creditInvoiceOrderRows()
        	->doRequest();

        if( ! $response || ! isset( $response->accepted ) || ! $response->accepted ) {
            new WP_Error( 'error', $response->errormessage );
        }

        return true;
	}

	/**
	 * This function handles the payment processing
	 *
	 * @param 	int 	$order_id 	id of the order being processed
	 * @return 	array 	an array containing the result of the payment
	 */
	public function process_payment( $order_id ) {
		$wc_order = wc_get_order( $order_id );

		$customer_first_name = $wc_order->get_billing_first_name();
		$customer_last_name = $wc_order->get_billing_last_name();
		$customer_address_1 = $wc_order->get_billing_address_1();
		$customer_address_2 = $wc_order->get_billing_address_2();
		$customer_zip_code = $wc_order->get_billing_postcode();
		$customer_city = $wc_order->get_billing_city();
		$customer_country = $wc_order->get_billing_country();
		$customer_email = $wc_order->get_billing_email();
		$customer_phone = $wc_order->get_billing_phone();
		$customer_company = $wc_order->get_billing_company();

		$config = $this->get_config();

		$subscriptions = false;

		/**
		 * We need to recalculate totals to include
		 * the invoice fee in the total amount in
		 * WooCommerce
		 */
		if( class_exists( 'WC_Subscriptions_Order' ) )  {
			if( ( wcs_order_contains_subscription( $wc_order )
				|| wcs_order_contains_switch( $wc_order )
				|| wcs_order_contains_resubscribe( $wc_order )
				|| wcs_order_contains_renewal( $wc_order ) ) ) {
				$subscriptions = wcs_get_subscriptions_for_order( $wc_order->get_id(), array( 'order_type' => 'any' ) );
			} else if( wcs_is_subscription( $wc_order->get_id() ) ) {
				$subscriptions = array( wcs_get_subscription( $wc_order->get_id() ) );
			}
		}

		/**
		 * Convert our WooCommerce order to Svea
		 */
		$svea_order = WC_Gateway_Svea_Helper::create_svea_order( $wc_order, $config );

		$svea_order
			->setClientOrderNumber( $order_id )
			->setCurrency( get_woocommerce_currency() )
			->setCountryCode( $customer_country )
			->setOrderDate( date("c") );

		switch( strtoupper( $customer_country ) ) {
			case "SE":
			case "DK":
			case "NO":
			case "FI":
				if( $_POST["iv_billing_customer_type"] == "company" ) {
					if( ! isset( $_POST["iv_billing_org_number"][0] ) ) {
						wc_add_notice( __( 'Organisation number is required.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
						return array(
				            'result'    => 'failure',
				        );
					}

					$customer_information = WebPayItem::companyCustomer()
						->setNationalIdNumber( $_POST["iv_billing_org_number"] );

					if( $subscriptions ) {
						foreach( $subscriptions as $subscription ) {
							update_post_meta( $subscription->get_id(), '_svea_iv_billing_org_number', $_POST["iv_billing_org_number"] );
						}
					}

					if( isset( $_POST["address_selector"] ) && strlen( $_POST["address_selector"] ) > 0 ) {
						$customer_information->setAddressSelector( $_POST["address_selector"] );

						if( $subscriptions ) {
							foreach( $subscriptions as $subscription ) {
								update_post_meta( $subscription->get_id(), '_svea_address_selector', $_POST["address_selector"] );
							}
						}
					}
				} else if( $_POST["iv_billing_customer_type"] == "individual" ) {
					if( ! isset( $_POST["iv_billing_ssn"][0] ) ) {
						wc_add_notice( __( 'Personal number is required.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
						return array(
				            'result'    => 'failure',
				        );
					}

					$customer_information = WebPayItem::individualCustomer()
												->setName( $customer_first_name, $customer_last_name )
												->setNationalIdNumber( $_POST["iv_billing_ssn"] );

					if( $subscriptions ) {
						foreach( $subscriptions as $subscription ) {
							update_post_meta( $subscription->get_id(), '_svea_iv_billing_ssn', $_POST["iv_billing_ssn"] );
						}
					}

				} else {
					wc_add_notice( __( 'Personal/organisation number is required.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
					return array(
			            'result'    => 'failure',
			        );
				}

				if( $subscriptions ) {
					foreach( $subscriptions as $subscription ) {
						update_post_meta( $subscription->get_id(), '_svea_iv_billing_customer_type', $_POST["iv_billing_customer_type"] );
					}
				}

				$customer_information->setStreetAddress( $customer_address_1 );
				break;
			case "NL":
				$exploded_zip_code = str_split( $customer_zip_code );
				$customer_zip_code = '';
				$lastChar = false;
				foreach($exploded_zip_code as $char) {
					if(is_numeric($lastChar) && !is_numeric($char)) 
						$customer_zip_code .= ' ' . $char;
					else
						$customer_zip_code .= $char;
					$lastChar = $char;
				}

				$company_name = $wc_order->get_billing_company();

				if( $_POST["iv_billing_customer_type"] == "company" ) {
					if( ! isset( $_POST["iv_billing_vat_number"][0] ) ) {
						wc_add_notice( __( 'VAT number is required.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
						return array(
				            'result'    => 'failure',
				        );
					}

					$customer_information = WebPayItem::companyCustomer()
											->setVatNumber( $_POST["iv_billing_vat_number"] )
											->setCompanyName($customer_company);

					if( $subscriptions ) {
						foreach( $subscriptions as $subscription ) {
							update_post_meta( $subscription->get_id(), '_svea_iv_billing_vat_number', $_POST["iv_billing_vat_number"] );
						}
					}

				} else if( $_POST["iv_billing_customer_type"] == "individual" ) {
					if( ! isset( $_POST["iv_billing_initials"][0] ) ) {
						wc_add_notice( __( 'Initials is a required field.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
						return array(
				            'result'    => 'failure',
				        );
					}

					if( ! isset( $_POST["iv_birth_date_year"][0] ) ) {
						wc_add_notice( __( 'Birth date year is a required field.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
						return array(
				            'result'    => 'failure',
				        );
					}

					if( ! isset( $_POST["iv_birth_date_month"][0] ) ) {
						wc_add_notice( __( 'Birth date month is a required field.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
						return array(
				            'result'    => 'failure',
				        );
					}

					if( ! isset( $_POST["iv_birth_date_day"][0] ) ) {
						wc_add_notice( __( 'Birth date day is a required field.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
						return array(
				            'result'    => 'failure',
				        );
					}

					$customer_information = WebPayItem::individualCustomer()
											->setName($customer_first_name, $customer_last_name)
											->setInitials( $_POST["iv_billing_initials"] )
											->setBirthDate(intval( $_POST["iv_birth_date_year"] ),
												intval( $_POST["iv_birth_date_month"] ),
												intval( $_POST["iv_birth_date_day"] ) );

					if( $subscriptions ) {
						foreach( $subscriptions as $subscription ) {
							update_post_meta( $subscription->get_id(), "_svea_iv_billing_initials", $_POST["iv_billing_initials"] );
							update_post_meta( $subscription->get_id(), "_svea_iv_birth_date_year", $_POST["iv_birth_date_year"] );
							update_post_meta( $subscription->get_id(), "_svea_iv_birth_date_month", $_POST["iv_birth_date_month"] );
							update_post_meta( $subscription->get_id(), "_svea_iv_birth_date_day", $_POST["iv_birth_date_day"] );
						}
					}
				}

				if( $subscriptions ) {		
					foreach( $subscriptions as $subscription ) {
						update_post_meta( $subscription->get_id(), '_svea_iv_billing_customer_type', $_POST["iv_billing_customer_type"] );
					}
				}

				$svea_address = Svea\WebPay\Helper\Helper::splitStreetAddress($customer_address_1);

				$customer_information->setStreetAddress($svea_address[1], $svea_address[2]);

			break;
			case "DE":
				$company_name = $wc_order->get_billing_company();

				if( $_POST["iv_billing_customer_type"] == "company" ) {
					if( ! isset( $_POST["iv_billing_vat_number"][0] ) ) {
						wc_add_notice( __( 'VAT number is required.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
						return array(
				            'result'    => 'failure',
				        );
					}

					$customer_information = WebPayItem::companyCustomer()
											->setCompanyName($customer_company)
											->setVatNumber( $_POST["iv_billing_vat_number"] );

					if( $subscriptions ) {
						foreach( $subscriptions as $subscription ) {
							update_post_meta( $subscription->get_id(), '_svea_iv_billing_vat_number', $_POST["iv_billing_vat_number"] );
						}
					}

				} else if( $_POST["iv_billing_customer_type"] == "individual" ) {
					if( ! isset( $_POST["iv_birth_date_year"][0] ) ) {
						wc_add_notice( __( 'Birth date year is a required field.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
						return array(
				            'result'    => 'failure',
				        );
					}

					if( ! isset( $_POST["iv_birth_date_month"][0] ) ) {
						wc_add_notice( __( 'Birth date month is a required field.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
						return array(
				            'result'    => 'failure',
				        );
					}

					if( ! isset( $_POST["iv_birth_date_day"][0] ) ) {
						wc_add_notice( __( 'Birth date day is a required field.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
						return array(
				            'result'    => 'failure',
				        );
					}

					$customer_information = WebPayItem::individualCustomer()
											->setName( $customer_first_name, $customer_last_name )
											->setBirthDate(intval( $_POST["iv_birth_date_year"] ),
												intval( $_POST["iv_birth_date_month"] ),
												intval( $_POST["iv_birth_date_day"] ) );

					if( $subscriptions ) {
						foreach( $subscriptions as $subscription ) {
							update_post_meta( $subscription->get_id(), '_svea_iv_birth_date_year', $_POST["iv_birth_date_year"] );
							update_post_meta( $subscription->get_id(), '_svea_iv_birth_date_month', $_POST["iv_birth_date_month"] );
							update_post_meta( $subscription->get_id(), '_svea_iv_birth_date_day', $_POST["iv_birth_date_day"] );
						}
					}
				}

				if( $subscriptions ) {
					foreach( $subscriptions as $subscription ) {
						update_post_meta( $subscription->get_id(), '_svea_iv_billing_customer_type', $_POST["iv_billing_customer_type"] );
					}
				}

				$svea_address = Svea\WebPay\Helper\Helper::splitStreetAddress( $customer_address_1 );

				$customer_information->setStreetAddress( $svea_address[1], $svea_address[2] );
			break;

			// Handle countries not supported by the payment gateway
			default:

			wc_add_notice( __( 'Country is not supported for invoice payments.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );

			return array(
	            'result'    => 'failure',
	        );
		}

		$return_url = $wc_order->get_checkout_order_received_url();

		if( isset( $_POST["iv_billing_customer_type"] ) && $_POST["iv_billing_customer_type"] == "company"
			&& ! $this->same_shipping_as_billing && strlen( $wc_order->get_shipping_first_name() ) > 0
			&& strlen( $wc_order->get_shipping_last_name() ) > 0 ) {
			$customer_reference = $wc_order->get_shipping_first_name() . ' ' . $wc_order->get_shipping_last_name();

			if( function_exists( 'mb_strlen' ) ) {
				if( mb_strlen( $customer_reference ) > 32 ) {
					$customer_reference = mb_substr( $customer_reference, 0, 29 ) . '...';
				}
			} else if( strlen( $customer_reference ) > 32 ) {
				$customer_reference = substr( $customer_reference, 0, 29 ) . '...';
			}

			$svea_order->setCustomerReference( $customer_reference );
		}

		/**
		 * Set customer information in the Svea Order
		 */
		$customer_information
			->setZipCode( $customer_zip_code )
			->setLocality( $customer_city )
			->setIpAddress( $_SERVER['REMOTE_ADDR'] )
			->setEmail( $customer_email )
			->setPhoneNumber( $customer_phone )	
			->setCoAddress( $customer_address_2 );

		$svea_order->addCustomerDetails( $customer_information );

		/**
		 * If we are hooked into WooCommerce subscriptions,
		 * see if any payment is required right now
		 */
		if( $subscriptions && $wc_order->get_total() <= 0 ) {
			$wc_order->payment_complete();

			// Remove cart
			WC()->cart->empty_cart();

			if( class_exists( 'WC_Subscriptions_Change_Payment_Gateway' ) ) {
				foreach( $subscriptions as $subscription ) {
					WC_Subscriptions_Change_Payment_Gateway::update_payment_method( $subscription, $this->id );
	        	}
			}

			return array(
				'result' => 'success',
				'redirect' => $return_url
			);
		}

		self::log( "Issuing payment for order: #" . $wc_order->get_id() );

		try {
			$response = $svea_order->useInvoicePayment()->doRequest();
		} catch( Exception $e ) {
			self::log( "Error message for order #" . $wc_order->get_id() . ", " . $e->getMessage() );
			wc_add_notice( __( 'An error occurred whilst connecting to Svea. Please contact the store owner and display this message.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );

			return array(
	            'result'    => 'failure',
	        );
		}

		if( ! $response || ! isset( $response->accepted ) || ! $response->accepted ) {
            if( isset ( $response->resultcode ) ) {
            	if( isset( $response->errormessage ) ) {
	            	self::log( "Error message for order #" . $wc_order->get_id() . ", " . $response->errormessage );
	            }

                wc_add_notice( WC_Gateway_Svea_Helper::get_svea_error_message( $response->resultcode ) , 'error' );
                $wc_order->add_order_note( 
                	sprintf( 
                		__( 'Customer received error: %s', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 
                		WC_Gateway_Svea_Helper::get_svea_error_message( $response->resultcode ) 
                	) 
                );

            } else {
            	self::log( "Unknown error occurred for payment with order number: #" . $wc_order->get_id() );
                wc_add_notice( __( 'An unknown error occurred. Please contact the store owner about this issue.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
            }

            return array(
	            'result'    => 'failure',
	        );
        }

        if( $subscriptions ) {
        	foreach( $subscriptions as $subscription ) {
        		if( $subscription->get_payment_method() != $this->id ) {
        			$subscription->set_payment_method( $this->id );
        			$subscription->save();
				}
        	}
        }

	    self::log( 'Payment complete for order #' . $wc_order->get_id() . ", " . $response->errormessage );

		$svea_order_id = $response->sveaOrderId;

		update_post_meta( $order_id, "_svea_order_id", $svea_order_id );

		$wc_order->payment_complete( $svea_order_id );

		// Remove cart
		WC()->cart->empty_cart();

		return array(
			'result' => 'success',
			'redirect' => $return_url
		);
	}

	/**
	 * Adds fields to subscription orders
	 *
	 * @return 	void
	 */
	public function add_subscription_order_fields( $subscription ) {
		if( ! function_exists( 'wcs_is_subscription' ) || ! wcs_is_subscription( $subscription ) ) {
			return;
		}

		$post_data = array(
			'iv_billing_customer_type'	=> get_post_meta( $subscription->get_id(), '_svea_iv_billing_customer_type', true ),
			'iv_billing_org_number'		=> get_post_meta( $subscription->get_id(), '_svea_iv_billing_org_number', true ),
			'iv_billing_ssn'			=> get_post_meta( $subscription->get_id(), '_svea_iv_billing_ssn', true ),
			'iv_billing_vat_number'		=> get_post_meta( $subscription->get_id(), '_svea_iv_billing_vat_number', true ),
			'iv_birth_date_year'		=> get_post_meta( $subscription->get_id(), '_svea_iv_birth_date_year', true ),
			'iv_birth_date_month'		=> get_post_meta( $subscription->get_id(), '_svea_iv_birth_date_month', true ),
			'iv_birth_date_day'			=> get_post_meta( $subscription->get_id(), '_svea_iv_birth_date_day', true ),
			'iv_billing_initials'		=> get_post_meta( $subscription->get_id(), '_svea_iv_billing_initials', true ),
		);

		include( WC_SVEAWEBPAY_GATEWAY_PLUGIN_DIR . 'partials/invoice/admin-subscription.php' );
	}

	/**
	 * Validate order fields passed in subscriptions
	 */
	public function validate_subscription_order_fields( $payment_method_id, $payment_meta, $subscription ) {
		return $payment_meta;
	}

	public function save_subscription_meta( $post_id, $post ) {
		if( isset( $_POST["wc_order_action"] ) && strlen( $_POST["wc_order_action"] ) > 0 )
			return;

		$subscription = wcs_get_subscription( $post_id );

		if( $_POST["_payment_method"] != $this->id )
			return;

		$customer_country = strtoupper( $_POST["_billing_country"] );
		$customer_type = $_POST["_iv_billing_customer_type"];

		switch( strtoupper( $customer_country ) ) {
			case "SE":
			case "DK":
			case "NO":
			case "FI":
				if( $customer_type == "company" ) {
					if( ! isset($_POST["_address_selector"]) ) {
						wcs_add_admin_notice( __( '<strong>Organisational number</strong> is a required field.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
						return;
					} else if( ! isset( $_POST["_iv_billing_org_number"] ) || strlen( $_POST["_iv_billing_org_number"] ) <= 0 ) {
						wcs_add_admin_notice( __( '<strong>Organisational number</strong> is a required field.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
						return;
					}
				} else if( $customer_type == "individual" ) {
					if( ! isset( $_POST["_iv_billing_ssn"] ) || strlen( $_POST["_iv_billing_ssn"] ) <= 0 ) {
						wcs_add_admin_notice( __( '<strong>Personal number</strong> is a required field.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error');
						return;
					}
				} else {
					wcs_add_admin_notice( __( 'Invalid customer type', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
					return;
				}
			break;
			case "NL":
				if( $customer_type == "company" ) {
					if( ! isset( $_POST["_iv_billing_vat_number"] ) ) {
						wcs_add_admin_notice( __( '<strong>VAT number</strong> is a required field.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
						return;
					}

					if( ! isset( $_POST["_billing_company"] ) || strlen( $_POST["_billing_company"] ) <= 0 ) {
						wcs_add_admin_notice( __( '<strong>Company Name</strong> is a required field.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
						return;
					}
				} else if( $customer_type == "individual" ) {
					if( ! isset( $_POST["_iv_billing_initials"] ) || strlen( $_POST["_iv_billing_initials"] ) < 2 ) {
						wcs_add_admin_notice( __( '<strong>Initials</strong> is a required field.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
						return;
					}

					if( ! isset( $_POST["_iv_birth_date_year"] ) || ! isset( $_POST["_iv_birth_date_month"] ) || ! isset( $_POST["_iv_birth_date_day"] ) ) {
						wcs_add_admin_notice( __( '<strong>Date of birth</strong> is a required field.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
						return;
					}

				} else {
					wcs_add_admin_notice( __( 'Invalid customer type', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
					return;
				}
			break;
			case "DE":
				if( $customer_type == "company" ) {
					if( ! isset($_POST["_iv_billing_vat_number"]) ) {
						wcs_add_admin_notice( __( '<strong>VAT number</strong> is a required field.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
						return;
					}

					if( ! isset($_POST["_billing_company"]) || strlen( $_POST["_billing_company"] ) <= 0 ) {
						wcs_add_admin_notice( __( '<strong>Company Name</strong> is a required field.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
						return;
					}

				} else if( $customer_type == "individual" ) {
					if( ! isset( $_POST["_iv_birth_date_year"] ) || ! isset( $_POST["_iv_birth_date_month"] ) || ! isset( $_POST["_iv_birth_date_day"] ) ) {
						wcs_add_admin_notice( __( '<strong>Date of birth</strong> is a required field.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
						return;
					}

				} else {
					wcs_add_admin_notice( __( 'Invalid customer type', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
					return;
				}
			break;
		}

		$fields = array(
			'_svea_iv_billing_customer_type'	=> '_iv_billing_customer_type',
			'_svea_address_selector'			=> '_address_selector',
			'_svea_iv_billing_ssn'				=> '_iv_billing_ssn',
			'_svea_iv_billing_initials'			=> '_iv_billing_initials',
			'_svea_iv_billing_vat_number'		=> '_iv_billing_vat_number',
			'_svea_iv_birth_date_day'			=> '_iv_birth_date_day',
			'_svea_iv_birth_date_month'			=> '_iv_birth_date_month',
			'_svea_iv_birth_date_year'			=> '_iv_birth_date_year',
			'_svea_iv_billing_org_number'		=> '_iv_billing_org_number',
		);

		foreach( $fields as $field_key => $field_value ) {
			if( ! isset( $_POST[$field_value] ) )
				continue;

			update_post_meta( $subscription->get_id(), $field_key, $_POST[$field_value] );
		}

	}

	/**
	 * Handles scheduled subscription payments
	 *
	 * @param 	float 		$amount_to_charge 	the amount that should be charged
	 * @param 	WC_Order 	$wc_order 			the original order
	 * @param 	int 		$product_id 		id of the subscription product
	 * @return 	void
	 */
	public function scheduled_subscription_payment( $amount_to_charge, $wc_order ) {

		self::log( "Scheduled subscription payment initiated" );

		/**
		 * Get the subscription from the WooCommerce order
		 */
		$subscriptions = wcs_get_subscriptions_for_order( $wc_order->get_id(), array( 'order_type' => 'any' ) );

		$subscription = array_shift( $subscriptions );

		$wc_original_order = false;

		if( $subscription->get_parent() !== false ) {
			$wc_original_order = $subscription->get_parent();
		}

		$customer_first_name = $wc_order->get_billing_first_name();
		$customer_last_name = $wc_order->get_billing_last_name();
		$customer_address_1 = $wc_order->get_billing_address_1();
		$customer_address_2 = $wc_order->get_billing_address_2();
		$customer_zip_code = $wc_order->get_billing_postcode();
		$customer_city = $wc_order->get_billing_city();
		$customer_country = $wc_order->get_billing_country();
		$customer_email = $wc_order->get_billing_email();
		$customer_phone = $wc_order->get_billing_phone();
		$customer_company = $wc_order->get_billing_company();

		$invoice_fee_label = $this->get_option( 'invoice_fee_label_' . strtolower( $customer_country ) );
		$invoice_fee = floatval( $this->get_option( 'invoice_fee_' . strtolower( $customer_country ) ) );
		$invoice_fee_taxable = $this->get_option( 'invoice_fee_taxable_' . strtolower( $customer_country ) ) === "yes";

		if ( strlen( $invoice_fee_label ) > 0 && is_numeric( $invoice_fee ) ) {
			$invoice_fee_exists = false;

			foreach( $wc_order->get_fees() as $fee ) {
				if( $fee->get_name() === $invoice_fee_label ) {
					$invoice_fee_exists = true;
					break;
				}
			}

			if ( $invoice_fee > 0 && ! $invoice_fee_exists ) {

				$fee = new WC_Order_item_Fee();
				$fee->set_name( $invoice_fee_label );
				$fee->set_total( $invoice_fee );
				$fee->set_order_id( $wc_order->get_id() );

				if( $invoice_fee_taxable ) {
					$invoice_tax_rates = WC_Tax::find_rates(
		                array( 
		                    'country'   => $wc_order->get_billing_country(),
		                    'state'     => $wc_order->get_billing_state(),
		                    'city'      => $wc_order->get_billing_city(),
		                    'postcode'  => $wc_order->get_billing_postcode(),
		                 ) 
		            );

		            $invoice_fee_tax = 0;

		            if( count( $invoice_tax_rates ) > 0 ) {
		                $invoice_tax_rate = array_shift( $invoice_tax_rates );

		                $fee_total = $invoice_fee * ( $invoice_tax_rate['rate'] / 100 + 1 );

		                $invoice_fee_tax = $fee_total - $invoice_fee;
		            } else {
		            	$invoice_fee_tax = 0;
		            }

		            $fee->set_tax_status( 'taxable' );
		            $fee->set_tax_class( '' );
		            $fee->set_total_tax( $invoice_fee_tax );
				}

			    $fee->save();

				$wc_order->add_item( $fee );

				/**
				 * We need WooCommerce to recalculate the totals after we have added our fee
				 */ 
				$wc_order->calculate_totals();
			}
		}

		$config = $this->get_config( $customer_country );

		/**
		 * Convert our WooCommerce order to Svea
		 */
		// $svea_order = WC_Gateway_Svea_Helper::create_svea_subscription_order( $wc_order, $config );
		// Use same helper function for both subscription payments and regular ones
		$svea_order = WC_Gateway_Svea_Helper::create_svea_order( $wc_order, $config );

		$svea_order
			->setClientOrderNumber( $wc_order->get_id() )
			->setCurrency( get_woocommerce_currency() )
			->setCountryCode( $customer_country )
			->setOrderDate( date("c") );

		/**
		 * Ensure that data is fetched from both the old and the new system
		 */
		$svea_data = array(
			'customer_type'		=> get_post_meta( $subscription->get_id(), '_svea_iv_billing_customer_type', true ),
			'address_selector'	=> get_post_meta( $subscription->get_id(), '_svea_address_selector', true ),
			'org_number'		=> get_post_meta( $subscription->get_id(), '_svea_iv_billing_org_number', true ),
			'initials'			=> get_post_meta( $subscription->get_id(), '_svea_iv_billing_initials', true ),
			'ssn'				=> get_post_meta( $subscription->get_id(), '_svea_iv_billing_ssn', true ),
			'vat_number'		=> get_post_meta( $subscription->get_id(), '_svea_iv_billing_vat_number', true ),
			'birth_date_year'	=> get_post_meta( $subscription->get_id(), '_svea_iv_birth_date_year', true ),
			'birth_date_month'	=> get_post_meta( $subscription->get_id(), '_svea_iv_birth_date_month', true ),
			'birth_date_day'	=> get_post_meta( $subscription->get_id(), '_svea_iv_birth_date_day', true ),
		);
	
		/**
		 * We want to move subscription by subscription to the new system
		 */
		// REMOVED AS OF VERSION 2.8.0 - THERE SHOULD BE NO ORDERS THAT HAVE THIS STRUCTURE LEFT
		// if( isset( $wc_original_order ) && $wc_original_order ) {
		// 	$svea_data_fields = array(
		// 		'svea_iv_billing_customer_type',
		// 		'svea_address_selector',
		// 		'svea_iv_billing_org_number',
		// 		'svea_iv_billing_initials',
		// 		'svea_iv_billing_ssn',
		// 		'svea_iv_billing_vat_number',
		// 		'svea_iv_birth_date_year',
		// 		'svea_iv_birth_date_month',
		// 		'svea_iv_birth_date_day',
		// 	);

		// 	$original_order_subscriptions = wcs_get_subscriptions_for_order( $wc_original_order->get_id() );

		// 	foreach( $svea_data_fields as $svea_data_field ) {
		// 		$old_field_value = wc_get_order_item_meta( $wc_original_order->get_id(), $svea_data_field );

		// 		if( $old_field_value ) {

		// 			foreach( $original_order_subscriptions as $original_order_subscription ) {
		// 				/**
		// 				 * Update the subscription if it does not already contain the data
		// 				 */
		// 				update_post_meta( 
		// 					$original_order_subscription->get_id(), 
		// 					'_' . $svea_data_field,
		// 					$old_field_value
		// 				);
		// 			}

		// 			wc_delete_order_item_meta( $wc_original_order->get_id(), $svea_data_field );
		// 		}
		// 	}
		// }

		switch( strtoupper( $customer_country ) ) {
			case "SE":
			case "DK":
			case "NO":
			case "FI":
				if( $svea_data['customer_type'] == "company" ) {
					$customer_information = WebPayItem::companyCustomer()
						->setNationalIdNumber( $svea_data['org_number'] );

					if( $svea_data['address_selector']
						&& strlen( (string) $svea_data['address_selector'] ) > 0 ) {
						$customer_information->setAddressSelector( $svea_data['address_selector'] );
					}
				} else if( $svea_data['customer_type'] == "individual" ) {
					$customer_information = WebPayItem::individualCustomer()
												->setName( $customer_first_name, $customer_last_name )
												->setNationalIdNumber( $svea_data['ssn'] );
				}

				$customer_information->setStreetAddress( $customer_address_1 );
				break;
			case "NL":
				$exploded_zip_code = str_split( $customer_zip_code );
				$customer_zip_code = '';
				$lastChar = false;
				foreach($exploded_zip_code as $char) {
					if( is_numeric( $lastChar ) && ! is_numeric( $char ) ) 
						$customer_zip_code .= ' ' . $char;
					else
						$customer_zip_code .= $char;
					$lastChar = $char;
				}

				$company_name = $wc_order->get_billing_company();

				if( $svea_data['customer_type'] == "company" ) {
					$customer_information = WebPayItem::companyCustomer()
											->setVatNumber( $svea_data['vat_number'] )
											->setCompanyName( $customer_company );
				} else if( $svea_data['customer_type'] == "individual" ) {
					$customer_information = WebPayItem::individualCustomer()
											->setName( $customer_first_name, $customer_last_name )
											->setInitials( $svea_data['initials'] )
											->setBirthDate(intval( $svea_data['birth_date_year'] ),
												intval( $svea_data['birth_date_month'] ),
												intval( $svea_data['birth_date_day'] ) );
				}

				$svea_address = Svea\WebPay\Helper\Helper::splitStreetAddress( $customer_address_1 );

				$customer_information->setStreetAddress( $svea_address[1], $svea_address[2] );

			break;
			case "DE":
				$company_name = $wc_order->get_billing_company();

				if( $svea_data['customer_type'] == "company" ) {
					$customer_information = WebPayItem::companyCustomer()
											->setCompanyName($customer_company)
											->setVatNumber( $svea_data['vat_number'] );

				} else if( $svea_data['customer_type'] == "individual" ) {
					$customer_information = WebPayItem::individualCustomer()
											->setName($customer_first_name, $customer_last_name)
											->setBirthDate(intval( $svea_data['birth_date_year'] ),
												intval( $svea_data['birth_date_month'] ),
												intval( $svea_data['birth_date_day'] ) );
				}

				$svea_address = Svea\WebPay\Helper\Helper::splitStreetAddress( $customer_address_1 );

				$customer_information->setStreetAddress( $svea_address[1], $svea_address[2] );
			break;
		}

		if( $svea_data['customer_type']
			&& ! $this->same_shipping_as_billing && strlen( $wc_order->get_shipping_first_name() ) > 0
			&& strlen( $wc_order->get_shipping_last_name() ) > 0 ) {
		    $customer_reference = $wc_order->get_shipping_first_name() . ' ' . $wc_order->get_shipping_last_name();

		    if( function_exists( 'mb_strlen' ) ) {
		        if( mb_strlen( $customer_reference ) > 32 ) {
		            $customer_reference = mb_substr( $customer_reference, 0, 29 ) . '...';
                }
            } else if( strlen( $customer_reference ) > 32 ) {
		        $customer_reference = substr( $customer_reference, 0, 29 ) . '...';
            }

			$svea_order->setCustomerReference( $customer_reference );
		}

		$customer_information
			->setZipCode( $customer_zip_code )
			->setLocality( $customer_city )
			->setIpAddress( $_SERVER['REMOTE_ADDR'] )
			->setEmail( $customer_email )
			->setPhoneNumber( $customer_phone )
			->setCoAddress( $customer_address_2 );

		$svea_order->addCustomerDetails( $customer_information );

		try {
			$response = $svea_order->useInvoicePayment()->doRequest();
		} catch( Exception $e ) {
			WC_Subscriptions_Manager::process_subscription_payment_failure_on_order( $wc_order );

			$wc_order->update_status( 'failed' );

			self::log( "Error: " . $e->getMessage() );

			$wc_order->add_order_note( __( 'Error occurred whilst processing subscription:', WC_SveaWebPay_Gateway::PLUGIN_SLUG ) . ' ' . $e->getMessage() );
			return;
		}

		/**
		 * See if the response was accepted and successful
		 */
		if( ! $response || ! isset( $response->accepted ) || ! $response->accepted ) {
			
			WC_Subscriptions_Manager::process_subscription_payment_failure_on_order( $wc_order );

			$wc_order->update_status( 'failed' );

			self::log( "Payment failed" );

			if( isset ( $response->resultcode ) ) {
				$wc_order->add_order_note( 
					sprintf( 
						__( 'Error occurred whilst processing subscription: %s', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 
						WC_Gateway_Svea_Helper::get_svea_error_message( $response->resultcode )
					) 
				);
            } else {
                $wc_order->add_order_note( 
                	sprintf( 
                		__( 'Error occurred whilst processing subscription: %s', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
                		__( 'An unknown error occurred. Please contact the store owner about this issue.', WC_SveaWebPay_Gateway::PLUGIN_SLUG )
                	) 
                );
            }

			return;
        }

        /**
         * Retrieve Svea's order id, we will use this to track
         * and administrate this order in the future
         */
		$svea_order_id = $response->sveaOrderId;

		WC_Subscriptions_Manager::process_subscription_payments_on_order( $wc_order );

		/** 
		 * Save Svea's order id on the newly created subscription order
		 * so that we can administrate it in the future
		 */
		update_post_meta( $wc_order->get_id(), "_svea_order_id", $svea_order_id );
		$wc_order->payment_complete( $svea_order_id );

		self::log( "Payment successful" );
	}

	/**
	 * Cancels the order in svea
	 *
	 * @param 	WC_Order 	$order 	the order being cancelled
	 * @param 	string 		$svea_order_id	id of the svea order
	 * @return 	array 		an array containing result and message
	 */
	public function cancel_order( $order, $svea_order_id ) {
		$config = $this->get_config( $order->get_billing_country() );

		$response = WebPayAdmin::cancelOrder( $config )
                    ->setCountryCode( $order->get_billing_country() )
                    ->setOrderId( $svea_order_id )
                    ->cancelInvoiceOrder()
                    ->doRequest();

        if( ! $response || ! isset( $response->accepted ) || ! $response->accepted ) {
            return array(
            	"success"	=> false,
            	"message"	=> $response->errormessage
            );
        }

        /**
         * The request was successful
         */
        $order->add_order_note(
            __( 'The order has been cancelled in Svea.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ) 
        );

        $order->update_status( 'cancelled' );

        return array(
        	"success"	=> true,
        	"message"	=> __( 'The order has been cancelled in Svea.', WC_SveaWebPay_Gateway::PLUGIN_SLUG )
        );
	}

	/**
	 * Credits the order in svea
	 *
	 * @param 	WC_Order 	$order 	the order being credited
	 * @param 	string 		$svea_order_id	id of the svea order
	 * @return 	array 		an array containing result and message
	 */
	public function credit_order( $order, $svea_order_id ) {
		$config = $this->get_config( $order->get_billing_country() );

		$response = WebPayAdmin::queryOrder($config)
                ->setOrderId( $svea_order_id )
                ->setCountryCode( $order->get_billing_country() )
                ->queryInvoiceOrder()
                ->doRequest();

        if( ! $response || ! isset( $response->accepted ) || ! $response->accepted ) {
        	return array(
        		"success"	=> false,
        		"message"	=> $response->errormessage
        	);
        }

        $numbered_order_rows = $response->numberedOrderRows;

       	$row_numbers = array();

        foreach( $numbered_order_rows as $row ) {
            $row_numbers[] = $row->rowNumber;
        }

        $invoice_ids = array();

        foreach( $numbered_order_rows as $numbered_order_row ) {
            if( is_null( $numbered_order_row->invoiceId ) ) {
            	return array(
            		"success"	=> false,
            		"message"	=> __( 'An invoice could not be found, deliver the order first', WC_SveaWebPay_Gateway::PLUGIN_SLUG )
            	);
            }

            if( ! isset( $invoice_ids[$numbered_order_row->invoiceId] ) ) {
                $invoice_ids[$numbered_order_row->invoiceId] = array('row_numbers' => array(), 'numbered_order_rows' => array());
            }

            $invoice_ids[$numbered_order_row->invoiceId]['row_numbers'][] = $numbered_order_row->rowNumber;
            $invoice_ids[$numbered_order_row->invoiceId]['numbered_order_rows'][] = $numbered_order_row;
        }

		foreach( $invoice_ids as $invoice_id => $data ) {
            $response = WebPayAdmin::creditOrderRows( $config )
                            ->setCountryCode( $order->get_billing_country() )
                            ->setRowsToCredit( $data["row_numbers"] )
                            ->addNumberedOrderRows( $data["numbered_order_rows"] )
                            ->setInvoiceId( $invoice_id )
                            ->setInvoiceDistributionType( $this->get_distribution_type( $order->get_billing_country() ) )
                            ->creditInvoiceOrderRows()
                            ->doRequest();


            if( ! $response || ! isset( $response->accepted ) || ! $response->accepted ) {
            	return array(
            		"success"	=> false,
            		"message"	=> $response->errormessage
            	);
            }
        }

        foreach( array_keys( $order->get_items( array( 'line_item', 'fee', 'shipping' ) ) ) as $order_item_id ) {
        	if( wc_get_order_item_meta( $order_item_id, 'svea_credited' ) )
                continue;
        	wc_add_order_item_meta( $order_item_id, 'svea_credited', date("Y-m-d H:i:s") );
        }

        /**
         * The request was successful
         */
        $number_of_rows = count( $row_numbers );

        $order->update_status( 'refunded' );

        if( $number_of_rows === 1 ) {
            $order->add_order_note( sprintf(
                __( '%d item has been credited in Svea.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
                $number_of_rows
            ) );

            return array(
            	"success"	=> true,
            	"message"	=> sprintf( 
            		__( '%d item has been credited in Svea.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 
            		$number_of_rows 
            	)
            );
        } else {
            $order->add_order_note( sprintf( 
                __( '%d items have been credited in Svea.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
                $number_of_rows
            ) );

            return array(
            	"success"	=> true,
            	"message"	=> sprintf( 
            		__( '%d items have been credited in Svea.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 
            		$number_of_rows 
            	)
            );
        }
	}

	/**
	 * Delivers the order in svea
	 *
	 * @param 	WC_Order 	$order 	the order being delivered
	 * @param 	string 		$svea_order_id	id of the svea order
	 * @param 	array 		$order_item_ids 	an optional array of order item ids
	 * @return 	array 		an array containing result and message
	 */
	public function deliver_order( $order, $svea_order_id, $order_item_ids = array() ) {

		$config = $this->get_config( $order->get_billing_country() );

		if( count( $order_item_ids ) > 0 ) {
			$response = WebPayAdmin::queryOrder($config)
                ->setOrderId( $svea_order_id )
                ->setCountryCode( $order->get_billing_country() )
                ->queryInvoiceOrder()
                ->doRequest();

            if( ! $response || ! isset( $response->accepted ) || ! $response->accepted ) {
            	return array(
            		"success"		=> false,
            		"message"	=> $response->errormessage
            	);
            }

            $numbered_order_rows = $response->numberedOrderRows;

            $order_items = $order->get_items( array( 'line_item', 'fee', 'shipping' ) );

	        $filtered_order_rows = array();

	        foreach( $order_items as $id => $item ) {
				if( wc_get_order_item_meta( $id, 'svea_credited' )
					|| ! in_array( $id, $order_item_ids ) )
	                continue;

	            $article_number = false;

	            if( $item->get_type() === "line_item" ) {
	            	$product = $item->get_product();

	            	if( $product->exists() && $product->get_sku() ) {
	            		$article_number = $product->get_sku();	
	            	} else {
	            		$article_number = $product->get_id();
	            	}
			    } else if( $item->get_type() === "shipping" ) {
			    	$article_number = $item->get_method_id();
			    } else if( $item->get_type() === "fee" ) {
			    	$article_number = sanitize_title( $item->get_name() );
			    }

		        foreach( $numbered_order_rows as $order_row ) {
	            	if( $order_row->articleNumber == $article_number ) {
	            		$filtered_order_rows[] = $order_row;
	            		break;
	            	}
	            }
	        }

            $numbered_order_rows = $filtered_order_rows;
            
            if( count( $numbered_order_rows ) === 0 ) {
            	return array(
            		"success"		=> false,
            		"message"	=> __( 'There are no order rows to deliver', WC_SveaWebPay_Gateway::PLUGIN_SLUG )
            	);
            }

            $row_numbers = array();

            foreach( $numbered_order_rows as $row ) {
                $row_numbers[] = $row->rowNumber;
            }

            $response = WebPayAdmin::deliverOrderRows( $config )
                        ->setOrderId( $svea_order_id )
                        ->setCountryCode( $order->get_billing_country() )
                        ->setInvoiceDistributionType( $this->get_distribution_type( $order->get_billing_country() ) )
                        ->setRowsToDeliver( $row_numbers )
                        ->addNumberedOrderRows( $numbered_order_rows )
                        ->deliverInvoiceOrderRows()
                        ->doRequest();
		} else {
			$response = WebPay::deliverOrder( $config )
                            ->setOrderId( $svea_order_id )
                            ->setCountryCode( $order->get_billing_country() )
                            ->setInvoiceDistributionType( $this->get_distribution_type( $order->get_billing_country() ) )
                            ->deliverInvoiceOrder()
                            ->doRequest();
		}

		if( ! $response || ! isset( $response->accepted ) || ! $response->accepted ) {
			return array(
        		"success"	=> false,
        		"message"	=> $response->errormessage
        	);
        }

		if( count( $order_item_ids ) > 0 ) {
			foreach( $order_item_ids as $order_item_id ) {
	        	if( wc_get_order_item_meta( $order_item_id, 'svea_delivered' ) )
	                continue;
	        	wc_add_order_item_meta( $order_item_id, 'svea_delivered', date( "Y-m-d H:i:s" ) );
	        }

	        $order_items = $order->get_items( array( 'line_item', 'fee', 'shipping' ) );

	        $order_items_delivered = 0;

	        foreach( $order_items as $order_item_id => $order_item ) {
	        	if( wc_get_order_item_meta( $order_item_id, 'svea_delivered' ) )
	        		++$order_items_delivered;
	        }

	        if( $order_items_delivered === count( $order_items ) ) {
	        	$order->update_status( 'completed' );
	        }

            /**
             * The request was successful
             */
            $number_of_rows = count( $row_numbers );

            if( $number_of_rows === 1 ) {
                $order->add_order_note( sprintf(
                    __( '%d item has been delivered in Svea.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
                    $number_of_rows
                ) );

                return array(
                	"success"	=> true,
                	"message"	=> sprintf(
	                    __( '%d item has been delivered in Svea.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
	                    $number_of_rows
	                )
                );
            } else {
                $order->add_order_note( sprintf( 
                    __( '%d items have been delivered in Svea.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
                    $number_of_rows
                ) );

                return array(
                	"success"	=> true,
                	"message"	=> sprintf( 
                		__( '%d items have been delivered in Svea.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 
                		$number_of_rows 
                	)
                );
            }
		} else {
			foreach( $order->get_items( array( 'line_item', 'fee', 'shipping' ) ) as $order_item_id => $order_item ) {
                wc_add_order_item_meta( $order_item_id, 'svea_delivered', date("Y-m-d H:i:s") );
            }

            $order->update_status( 'completed' );
            
            $order->add_order_note(
                __( 'All items have been delivered in Svea.', WC_SveaWebPay_Gateway::PLUGIN_SLUG )
            );

            return array(
            	"success"	=> true,
            	"message"	=> __( 'All items have been delivered in Svea.', WC_SveaWebPay_Gateway::PLUGIN_SLUG )
            );
		}
	}

	public function receipt_page( $order ) {
		echo '<p>' . __( 'Thank you for paying with SveaWebPay Invoice Payment', WC_SveaWebPay_Gateway::PLUGIN_SLUG ) . '</p>';
	}
}