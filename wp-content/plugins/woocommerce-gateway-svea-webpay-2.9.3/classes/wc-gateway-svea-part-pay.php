<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use Svea\WebPay\WebPay;
use Svea\WebPay\WebPayItem;
use Svea\WebPay\WebPayAdmin;
use Svea\WebPay\Constant\DistributionType;

class WC_Gateway_Svea_Part_Pay extends WC_Payment_Gateway {
	
	/**
	 * Format of the transition for part payment campaigns
	 *
	 * @var 	string
	 */
	const PART_PAYMENT_TRANSIENT_FORMAT = 'sveawebpay-part-pay-campaigns-%s';

	/**
	 * Id of this gateway
	 *
	 * @var 	string
	 */
	const GATEWAY_ID = 'sveawebpay_part_pay';

	/**
	 * Static instance of this class
	 *
     * @var WC_Gateway_Svea_Part_Pay
     */
    private static $instance = null;

    private static $log_enabled = false;
    private static $log = null;

    public static function init() {
        if ( is_null( self::$instance ) ) {
            $instance = new WC_Gateway_Svea_Part_Pay;
        }
        return self::$instance;
    }

	public function __construct() {
		if( is_null( self::$instance ) )
			self::$instance = $this;

		global $woocommerce;

		$this->id = self::GATEWAY_ID;

		$this->method_title = __( 'SveaWebPay Part Payment', WC_SveaWebPay_Gateway::PLUGIN_SLUG );
		$this->icon = apply_filters( 'woocommerce_sveawebpay_part_pay_icon', 'https://cdn.svea.com/sveaekonomi/rgb_ekonomi_large.png' );
		$this->has_fields = true;

		$this->svea_part_pay_currencies = array(
			'DKK' => 'DKK', // Danish Kroner
			'EUR' => 'EUR', // Euro
			'NOK' => 'NOK', // Norwegian Kroner
			'SEK' => 'SEK'  // Swedish Kronor
		);

		$this->init_form_fields();
		$this->init_settings();

		$this->title = __( $this->get_option( 'title' ), WC_SveaWebPay_Gateway::PLUGIN_SLUG );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'clear_part_payment_plans_cache' ) );

		if( ! isset( WC()->customer ) ) {
			return;
		}

		$customer_country = strtolower( WC()->customer->get_billing_country() );

		$this->enabled = $this->get_option('enabled');

		// Set logo by customer country
		$this->icon = $this->get_svea_part_pay_logo_by_country( $customer_country );

		$wc_countries = new WC_Countries();

		$this->base_country = $wc_countries->get_base_country();

		$this->enabled_countries = is_array( $this->get_option( 'enabled_countries' ) ) ?
									$this->get_option( 'enabled_countries' ) : array();

		$this->selected_currency = get_woocommerce_currency();

		$this->same_shipping_as_billing = $this->get_option('same_shipping_as_billing') === "yes";

		$this->testmode = $this->get_option('testmode_' . $customer_country) === "yes";
		$this->username = $this->get_option('username_' . $customer_country);
		$this->password = $this->get_option('password_' . $customer_country);
		$this->client_nr = $this->get_option('client_nr_' . $customer_country);
		$this->display_product_widget = $this->get_option( 'display_product_widget' ) === 'yes';
		self::$log_enabled = $this->get_option( 'debug' ) === "yes";

		$config_class = $this->testmode ? "WC_Svea_Config_Test" : "WC_Svea_Config_Production";

		$this->config = new $config_class( false, false, $this->client_nr, $this->password, $this->username );

		$this->description = __( $this->get_option( 'description' ), WC_SveaWebPay_Gateway::PLUGIN_SLUG );
	}

	/**
	 * Part payment widget used on the product page if activated
     *
     * @return void
	 */
	public function product_part_payment_widget() {
        if( ! $this->display_product_widget ) {
            return;
        }

        global $product;

        $product_types = apply_filters( 'woocommerce_sveawebpay_part_pay_widget_product_types', array( 'simple', 'variable' ) );

        if( ! $product->is_type( $product_types ) ) {
            return;
        }

        $price = $product->get_price();

		if( ! empty( WC()->customer->get_billing_country() ) ) {
			$customer_country = strtoupper( WC()->customer->get_billing_country() );
		} else {
			$wc_countries = new WC_Countries();

			$customer_country = $wc_countries->get_base_country();
		}

		$country_currency = WC_Gateway_Svea_Helper::get_country_currency( $customer_country );

		if( ! isset( $country_currency[0] )
		    || $country_currency !== $this->selected_currency ) {
			return;
		}

        $campaigns = $this->get_payment_plans( $customer_country );

		if( empty( $campaigns ) ) {
		    return;
        }

		$formattedCampaigns = new stdClass();
		$formattedCampaigns->campaignCodes = $campaigns;

        $payment_plan_prices = Svea\WebPay\Helper\Helper::paymentPlanPricePerMonth( $price, $formattedCampaigns, false );

        $lowest_price_per_month = false;

        foreach( $payment_plan_prices->values as $price_per_month ) {
            if( ! isset( $price_per_month['pricePerMonth'] ) ) {
                continue;
            }

            if( $lowest_price_per_month === false || $price_per_month['pricePerMonth'] < $lowest_price_per_month ) {
                $lowest_price_per_month = $price_per_month['pricePerMonth'];
            }
        }

        if( $lowest_price_per_month === false || $lowest_price_per_month <= 0 ) {
            return;
        }

        $svea_icon = $this->get_svea_part_pay_logo_by_country( $customer_country );

        ?>
        <p class="svea-part-payment-widget"><img src="<?php echo esc_url( $svea_icon ); ?>" /><?php printf( __( 'Part pay from %s/month', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), wc_price( round( $lowest_price_per_month ) ) ); ?></p>
        <?php
    }

	/**
     * Get Svea Part Pay logo depending on country
     *
	 * @param string $country
	 *
	 * @return string URL of the part pay logo
	 */
	public function get_svea_part_pay_logo_by_country( $country = '' ) {
		$default_logo = apply_filters( 'woocommerce_sveawebpay_part_pay_icon', 'https://cdn.svea.com/sveaekonomi/rgb_ekonomi_large.png' );

		$country = strtoupper( $country );

		$logos = array(
			'SE' => 'https://cdn.svea.com/webpay/buttons/sv/Button_Paymentplan_PosWhite_SV.png',
			'NO' => 'https://cdn.svea.com/webpay/buttons/no/Button_Paymentplan_PosWhite_NO.png',
			'FI' => 'https://cdn.svea.com/webpay/buttons/fi/Button_Paymentplan_PosWhite_FI.png',
			'DE' => 'https://cdn.svea.com/webpay/buttons/de/Button_Paymentplan_PosWhite_DE.png',
		);

		$logo = $default_logo;

		if( isset( $logos[$country] ) ) {
			$logo = $logos[$country];
		}

		return apply_filters( 'woocommerce_sveawebpay_part_pay_icon', $logo, $country );
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

	/**
     * Get payment plans by country
     *
	 * @param string $country The country to get campaigns from
	 *
	 * @return array List of payment plan campaigns
	 */
    public function get_payment_plans( $country ) {
        $country = strtoupper( $country );

	    /**
	     * Get campaigns from cache to save bandwidth
	     * and loading time
	     */
	    $campaigns = get_transient( sprintf( self::PART_PAYMENT_TRANSIENT_FORMAT, $country ) );

	    if( $campaigns === false ) {
		    self::log( "No Payment Plans in cache, fetching Payment Plans from Svea." );

		    try {
			    $campaignsRequest = WebPay::getPaymentPlanParams( $this->get_config() );

			    $campaignsRequest->setCountryCode( $country );

			    $campaignsResponse = $campaignsRequest->doRequest();
			    $campaignsResponse->country = $country;

			    if( isset( $campaignsResponse->campaignCodes ) ) {
			        $campaigns = $campaignsResponse->campaignCodes;
                } else {
			        $campaigns = array();
                }

			    if( $campaignsResponse->accepted ) {
				    /**
				     * Cache the campaigns from the response for 1 hour
				     */
				    set_transient( sprintf( self::PART_PAYMENT_TRANSIENT_FORMAT, $country ), $campaigns, 60 * 60 );
				    self::log( "Successfully fetched payment plans." );
			    } else if( isset( $campaignsResponse->errormessage[0] ) ) {
				    self::log( "Error when fetching payment plans: " . $campaignsResponse->errormessage );
				    $campaigns = array();
			    }
		    } catch (Exception $e) {
			    self::log( 'Received error: ' . $e->getMessage() );

			    $campaigns = array();
		    }

	    }

	    return $campaigns;
    }

	/**
	 * Display payment fields at checkout
	 *
	 * @return void
	 */
	public function payment_fields() {
		echo $this->description;

		$post_data = array();

		if( isset( $_POST["post_data"] ) ) {
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

		$part_pay_checkout_template = locate_template( 'woocommerce-gateway-svea-webpay/part-pay/checkout.php' );

		if( $part_pay_checkout_template == '' ) {
			$part_pay_checkout_template = WC_SVEAWEBPAY_GATEWAY_PLUGIN_DIR . 'partials/part-pay/checkout.php';
		}

		include( $part_pay_checkout_template );

		$campaigns = $this->get_payment_plans( $country );

		$total = WC()->cart->total;

		if( function_exists( 'is_checkout_pay_page' ) && is_checkout_pay_page() ) {
			if( isset( $_GET["key"] ) ) {
				$wc_order = new WC_Order( wc_get_order_id_by_order_key( $_GET["key"] ) );

				$total = $wc_order->get_total();
			}
		}

		if( count( $campaigns ) > 0 ) :

			$options = array();

			uasort( $campaigns, function( $ca, $cb ) {
				if( $ca->paymentPlanType === "InterestAndAmortizationFree"
					&& $cb->paymentPlanType !== "InterestAndAmortizationFree" )
					return -1;
				else if( $ca->paymentPlanType !== "InterestAndAmortizationFree"
					&& $cb->paymentPlanType === "InterestAndAmortizationFree" )
					return 1;

				if( $ca->contractLengthInMonths == $cb->contractLengthInMonths )
					return 0;


				return ( $ca->contractLengthInMonths < $cb->contractLengthInMonths ) ? -1 : 1;
			} );

			$formattedCampaigns = new stdClass();
			$formattedCampaigns->campaignCodes = $campaigns;

			$pricedCampaigns = Svea\WebPay\Helper\Helper::paymentPlanPricePerMonth( $total, $formattedCampaigns, false );

			foreach( $pricedCampaigns->values as $campaign ) {

				$campaignData = false;

				foreach( $campaigns as $cdata ) {
					if( $cdata->campaignCode == $campaign["campaignCode"] ) {
						$campaignData = $cdata;
						break;
					}
				}

				if( $campaignData === false )
					continue;

				if( $campaignData->paymentPlanType === "InterestAndAmortizationFree" ) {
					$paymentDate = strtotime("+ " . $campaignData->contractLengthInMonths . " months");

					$campaignDescription = sprintf( 
						__( "Buy now, pay %s in %s", WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
						strip_tags( wc_price( $campaign["pricePerMonth"] ) ),
						date_i18n( "F Y", $paymentDate )
					);

					if( isset( $campaignData->initialFee ) && $campaignData->initialFee > 0 ) {
						$campaignDescription .= sprintf(
							" + %s %s",
							strip_tags( wc_price( $campaignData->initialFee ) ),
							__( 'initial fee', WC_SveaWebPay_Gateway::PLUGIN_SLUG )
						);
					}

				} else {
					$campaignDescription = sprintf( 
						"%s. %s / %s",
						$campaignData->description,
						strip_tags( wc_price( $campaign["pricePerMonth"] ) ),
						__( 'month', WC_SveaWebPay_Gateway::PLUGIN_SLUG )
					);

					if( $campaignData->initialFee > 0 ) {
						$campaignDescription .= sprintf(
							" + %s %s",
							strip_tags( wc_price( $campaignData->initialFee ) ),
							__( 'initial fee', WC_SveaWebPay_Gateway::PLUGIN_SLUG )
						);
					}
				}

				$options[$campaign["campaignCode"]] = $campaignDescription;

			}

			if( count( $options ) > 0 ) : ?>
			<div class="part-payment-plans">
				<h3><?php _e('Part Payment Plans', WC_SveaWebPay_Gateway::PLUGIN_SLUG); ?></h3>
				<?php
		            woocommerce_form_field( 'part_payment_plan', array(
		                'type'          => 'radio',
		                'required'      => false,
		                'class'         => array( 'form-row-wide' ),
		                'options'       => $options
		            ), isset( $post_data["part_payment_plan"] ) ? $post_data["part_payment_plan"] : false); 
	            ?>
	        </div>
	        <?php else : ?>
	        <div class="part-payment-plans">
	        	<p><?php _e( 'There are no payment plans available for your order total.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ); ?></p>
	        </div>
	        <?php endif;

        endif;
	}

	public static function display_admin_action_buttons() {
		?>
		<button type="button" class="button svea-credit-items"><?php _e( 'Credit via svea', WC_SveaWebPay_Gateway::PLUGIN_SLUG ); ?></button>
		<?php
	}

	public static function admin_functions_meta_box() {
		$order = new WC_Order( get_the_ID() );

		$deliver_nonce = wp_create_nonce( WC_SveaWebPay_Gateway_Admin_Functions::DELIVER_NONCE );
		$cancel_nonce = wp_create_nonce( WC_SveaWebPay_Gateway_Admin_Functions::CANCEL_NONCE );

		?>
		<a href="<?php echo admin_url( 'admin-post.php?action=svea_webpay_admin_deliver_order&order_id=' . get_the_ID() . '&security=' . $deliver_nonce ); ?>">
			<?php _e( 'Deliver order', WC_SveaWebPay_Gateway::PLUGIN_SLUG ); ?>
		</a><br>
		<a href="<?php echo admin_url( 'admin-post.php?action=svea_webpay_admin_cancel_order&order_id=' . get_the_ID() . '&security=' . $cancel_nonce ); ?>">
			<?php _e( 'Cancel order', WC_SveaWebPay_Gateway::PLUGIN_SLUG ); ?>
		</a>
		<?php
	}

	/**
	 * Check whether or not this payment gateway is available
	 *
	 * @return 	boolean
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
			} else if( ! $this->check_customer_type() ) {
			 	return false;
			} else if( ! $this->check_payment_plans() ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Check if there are any available payment plans for the customer
	 *
	 * @return 	boolean
	 */
	public function check_payment_plans() {
		$customer_country = WC()->customer->get_billing_country();

		$total = WC()->cart->subtotal + WC()->cart->shipping_total + WC()->cart->shipping_tax_total -
		         ( WC()->cart->get_cart_discount_total() + WC()->cart->get_cart_discount_tax_total() );

		if( function_exists( 'is_checkout_pay_page' ) && is_checkout_pay_page() ) {
			if( isset( $_GET['key'] ) ) {
				$wc_order = wc_get_order( wc_get_order_id_by_order_key( $_GET['key'] ) );

				$total = $wc_order->get_total();
			}
		}

		$campaigns = $this->get_payment_plans( $customer_country );

		foreach( $campaigns as $campaign ) {
			if( floatval( $campaign->fromAmount ) <= $total
				&& floatval( $campaign->toAmount ) >= $total ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if the current currency is the same as the currency for the customer
	 *
	 * @return 	boolean
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
	 * Check if the current country is enabled
	 *
	 * @return 	boolean
	 */
	public function check_customer_country() {
		$customer_country = strtoupper( WC()->customer->get_billing_country() );

		if( ! in_array( $customer_country, $this->enabled_countries ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if the current customer type is valid for this
	 * payment method
	 *
	 * @return 	boolean
	 */
	public function check_customer_type() {
		if( ! isset( $_POST['post_data'] )
		|| ! WC_SveaWebPay_Gateway_Shortcodes::is_using_get_address_shortcode() ) {
			return true;
		}

		$post_data = array();
		parse_str( $_POST['post_data'], $post_data );

		if( ! isset( $post_data['iv_billing_customer_type'] ) ) {
			return true;
		}

		if( $post_data['iv_billing_customer_type'] != 'company' ) {
			return true;
		}

		return false;
	}

	/**
	 * Clears transients containing part payment plans 
	 *
	 * @return 	void
	 */
	public function clear_part_payment_plans_cache() {
		/**
		 * List all available countries for Svea and clear cache
		 * for all of them
		 */
		$available_countries = array( "SE", "DK", "NO", "FI", "DE", "NL" );

		foreach( $available_countries as $country ) {
			/**
			 * Delete the transient to clear out the cache
			 */
			delete_transient( sprintf( self::PART_PAYMENT_TRANSIENT_FORMAT, $country ) );
		}
	}

	/**
	 * Initialize form fields for this payment gateway
	 *
	 * @return 	void
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
						'title' => __('Enable/disable', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'checkbox',
						'label' => __('Enable SveaWebPay Part Payments', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => 'no'
				),
			'title' => array(
						'title' => __('Title',WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'text',
						'description' => __('This controls the title which the user sees during checkout', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => __('Part payment', WC_SveaWebPay_Gateway::PLUGIN_SLUG)
				),
			'description' => array(
						'title' => __('Description', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'textarea',
						'description' => __('This controls the description the user sees during checkout', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => __( 'Pay with part payments through Svea Ekonomi', WC_SveaWebPay_Gateway::PLUGIN_SLUG )
				),
			'enabled_countries' => array(
						'title' => __('Enabled countries', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'multiselect',
						'description' => __('Choose the countries you want SveaWebPay Part Payment to be enabled in', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
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
			'display_product_widget' => array(
				'title' => __( 'Display product part payment widget', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
				'type' => 'checkbox',
				'description' => __( 'Display a widget on the product page which suggests a part payment plan for the customer to use to buy the product.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
				'default' => 'no',
			),
			'product_widget_position' => array(
			    'title' => __( 'Product part payment widget position', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
                'type' => 'select',
                'description' => __( 'The position of the part payment widget on the product page. Is only displayed if the widget is activated.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
                'default' => 15,
                'options' => array(
                    '15' => __( 'Between price and excerpt', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
                    '25' => __( 'Between excerpt and add to cart', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
                    '35' => __( 'Between add to cart and product meta', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
                )
            ),
			'same_shipping_as_billing' => array(
						'title' => __('Same shipping address as billing address', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'checkbox',
						'description' => __('If checked, billing address will override the shipping address', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => 'yes'
				),
			//Denmark
			'testmode_dk' => array(
						'title' => __('Test mode Denmark', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'checkbox',
						'label' => __('Enable/disable test mode in Denmark', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => 'no'
				),
			'username_dk' => array(
						'title' => __('Username Denmark', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'text',
						'description' => __('Username for part payments in Denmark', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => ''
				),
			'password_dk' => array(
						'title' => __('Password Denmark', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'password',
						'description' => __('Password for part payments in Denmark', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => ''
				),
			'client_nr_dk' => array(
						'title' => __('Client number Denmark', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'text',
						'description' => __('Client number for part payments in Denmark', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => ''
				),
			//Germany
			'testmode_de' => array(
						'title' => __('Test mode Germany', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'checkbox',
						'label' => __('Enable/disable test mode in Germany', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => 'no'
				),
			'username_de' => array(
						'title' => __('Username Germany', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'text',
						'description' => __('Username for part payments in Germany', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => ''
				),
			'password_de' => array(
						'title' => __('Password Germany', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'password',
						'description' => __('Password for part payments in Germany', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => ''
				),
			'client_nr_de' => array(
						'title' => __('Client number Germany', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'text',
						'description' => __('Client number for part payments in Germany', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => ''
				),
			//Finland
			'testmode_fi' => array(
						'title' => __('Test mode Finland', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'checkbox',
						'label' => __( 'Enable/disable test mode in Finland', WC_SveaWebPay_Gateway::PLUGIN_SLUG ),
						'default' => 'no'
				),
			'username_fi' => array(
						'title' => __('Username Finland', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'text',
						'description' => __('Username for part payments in Finland', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => ''
				),
			'password_fi' => array(
						'title' => __('Password Finland', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'password',
						'description' => __('Password for part payments in Finland', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => ''
				),
			'client_nr_fi' => array(
						'title' => __('Client number Finland', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'text',
						'description' => __('Client number for part payments in Finland', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => ''
				),
			//Netherlands
			'testmode_nl' => array(
						'title' => __('Test mode Netherlands', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'checkbox',
						'label' => __('Enable/disable test mode in Netherlands', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => 'no'
				),
			'username_nl' => array(
						'title' => __('Username Netherlands', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'text',
						'description' => __('Username for part payments in Netherlands', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => ''
				),
			'password_nl' => array(
						'title' => __('Password Netherlands', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'password',
						'description' => __('Password for part payments in Netherlands', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => ''
				),
			'client_nr_nl' => array(
						'title' => __('Client number Netherlands', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'text',
						'description' => __('Client number for part payments in Netherlands', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => ''
				),
			//Norway
			'testmode_no' => array(
						'title' => __('Test mode Norway', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'checkbox',
						'label' => __('Enable/disable test mode in Norway', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => 'no'
				),
			'username_no' => array(
						'title' => __('Username Norway', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'text',
						'description' => __('Username for part payments in Norway', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => ''
				),
			'password_no' => array(
						'title' => __('Password Norway', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'password',
						'description' => __('Password for part payments in Norway', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => ''
				),
			'client_nr_no' => array(
						'title' => __('Client number Norway', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'text',
						'description' => __('Client number for part payments in Norway', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => ''
				),
			//Sweden
			'testmode_se' => array(
						'title' => __('Test mode Sweden', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'checkbox',
						'label' => __('Enable/disable test mode in Sweden', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => 'no'
				),
			'username_se' => array(
						'title' => __('Username Sweden', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'text',
						'description' => __('Username for part payments in Sweden', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => ''
				),
			'password_se' => array(
						'title' => __('Password Sweden', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'password',
						'description' => __('Password for part payments in Sweden', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'default' => ''
				),
			'client_nr_se' => array(
						'title' => __('Client number Sweden', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
						'type' => 'text',
						'description' => __('Client number for part payments in Sweden', WC_SveaWebPay_Gateway::PLUGIN_SLUG),
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
	 * Validates custom fields for Part Payments
	 *
	 * @return void
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

		if( ! isset( $_POST["part_payment_plan"] ) ) {
			wc_add_notice( __( '<strong>Part Payment Plan</strong> is a required field.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
			return;
		}

		switch( strtoupper( $customer_country ) ) {
			case "SE":
			case "DK":
				$request = WebPay::getAddresses( $this->get_config( $customer_country ) );
				$request->setOrderTypePaymentPlan();

				if( ! isset($_POST["pp_billing_ssn"]) || $_POST["pp_billing_ssn"] == "" ) {
					wc_add_notice( __( '<strong>Personal number</strong> is a required field.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
					return;
				} else {
					$request->setIndividual($_POST["pp_billing_ssn"]);
				}

				$request->setCountryCode( $customer_country );
				$response = $request->doRequest();
				$result_code = $response->resultcode;

				if( $result_code == "Accepted" ) {
					$customer_identity = $response->customerIdentity[0];

					$_POST["billing_address_1"] = $customer_identity->street;
					$_POST["billing_address_2"] = $customer_identity->coAddress;
					$_POST["billing_postcode"] = $customer_identity->zipCode;
					$_POST["billing_city"] = $customer_identity->locality;

				} else if( $result_code == "Error" || $result_code == "NoSuchEntity" ) {
					wc_add_notice( __( 'The <strong>Personal number</strong> is not valid.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
					return;
				} else {
					wc_add_notice( __( $_POST["pp_billing_customer_type"] == "company" ? "An unknown error occurred while trying to lookup your Organisational number."
					 : $_POST["pp_billing_customer_type"] == "individual" ? "An unknown error occurred while trying to lookup your Personal number."
					 : "An unknown error occurred." ), 'error');
					return;
				}
			break;
			case "NO":
				if( ! isset($_POST["pp_billing_ssn"]) || $_POST["pp_billing_ssn"] == "" ) {
					wc_add_notice( __( '<strong>Personal number</strong> is a required field.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
					return;
				} else if( ! is_numeric($_POST["pp_billing_ssn"]) ) {
					wc_add_notice( __( 'A <strong>Personal number</strong> can only contain digits.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
					return;
				} else if( strlen( $_POST["pp_billing_ssn"] ) != 11 ) {
					wc_add_notice( __( 'The <strong>Personal number</strong> entered is not correct.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
					return;
				}

			break;
			case "FI":
				if( ! isset($_POST["pp_billing_ssn"]) || $_POST["pp_billing_ssn"] == "" ) {
					wc_add_notice( __( '<strong>Personal number</strong> is a required field.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
					return;
				} else if( strlen( $_POST["pp_billing_ssn"] ) < 10 ) {
					wc_add_notice( __( 'The <strong>Personal number</strong> entered is not correct.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
					return;
				}

			break;
			case "NL":
				if( ! isset($_POST["pp_billing_initials"]) || strlen( $_POST["pp_billing_initials"] ) < 2 ) {
					wc_add_notice( __( '<strong>Initials</strong> is a required field.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
					return;
				}

				if( ! isset($_POST["pp_birth_date_year"]) || ! isset( $_POST["pp_birth_date_month"] ) || ! isset($_POST["pp_birth_date_day"]) ) {
					wc_add_notice( __( '<strong>Date of birth</strong> is a required field.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
					return;
				}

			break;
			case "DE":
				if( ! isset($_POST["pp_birth_date_year"]) || ! isset($_POST["pp_birth_date_month"]) || ! isset($_POST["pp_birth_date_day"]) ) {
					wc_add_notice( __( '<strong>Date of birth</strong> is a required field.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
					return;
				}

			break;
		}
	}

	public function add_payment_plans() {
		global $woocommerce;

		$current_gateway = WC_Gateway_Svea_Helper::get_current_gateway();

		if( get_class($current_gateway) !== get_class($this) )
			return;

		$wc_part_pay_gateway = WC_Gateway_Svea_Part_Pay::init();
		$enabled_countries = $wc_part_pay_gateway->get_option('enabled_countries');

		if( ! isset( $enabled_countries ) || $enabled_countries === false
			|| count( $enabled_countries ) <= 0 ) {
			return;
		}

		$customer_country = $woocommerce->customer->get_country();

		/**
		 * Get campaigns from cache to save bandwidth
		 * and loading time
		 */
		$campaigns = get_transient( sprintf( self::PART_PAYMENT_TRANSIENT_FORMAT, $customer_country ) );

		if($campaigns === false) {

			try {
				$campaignsRequest = WebPay::getPaymentPlanParams( $this->get_config() );

				$campaignsRequest->setCountryCode($customer_country);

				$campaignsResponse = $campaignsRequest->doRequest();
				$campaignsResponse->country = $customer_country;

				$campaigns = $campaignsResponse->campaignCodes;

				/**
				 * Cache the campaigns from the response for 1 hour
				 */
				set_transient( sprintf( self::PART_PAYMENT_TRANSIENT_FORMAT, $customer_country ), $campaigns, 60 * 60 );
			} catch(Exception $e) {
				$campaigns = array();
			}
		}

		$total = WC()->cart->total;

		if( count($campaigns) <= 0 )
			return;

		$options = array();

		foreach($campaigns as $campaign) {
			if($campaign->fromAmount > $total || $campaign->toAmount < $total) 
				continue;

			$options[$campaign->campaignCode] = $campaign->description;
		}
		?>
		<div class="part-payment-plans">
			<h3><?php _e('Part Payment Plans', WC_SveaWebPay_Gateway::PLUGIN_SLUG); ?></h3>
			<?php
	            woocommerce_form_field( 'part_payment_plan', array(
	                'type'          => 'radio',
	                'required'      => false,
	                'class'         => array('form-row-wide'),
	                'options'       => $options
	            )); 
            ?>
        </div>
        <?php

		//include( WC_SVEAWEBPAY_GATEWAY_PLUGIN_DIR . '/partials/part-payment-plans.php' );
	}

	public function part_pay_is_active_and_set() {
		if ( ! array_key_exists( get_woocommerce_currency(), $this->svea_part_pay_currencies ) ) {
			return false;
		}

		return true;
	}

	public function admin_options() {
		?>
		<h3><?php _e( 'SveaWebPay Part Payment', WC_SveaWebPay_Gateway::PLUGIN_SLUG ); ?></h3>
		<p><?php _e( 'Process part payments through SveaWebPay.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ); ?></p>
		<?php if ( $this->part_pay_is_active_and_set() ): ?>
		<table class="form-table">
		<?php
		 	// Generate the HTML For the settings form.
    		$this->generate_settings_html();
		?>
		</table>
		<?php else : ?>
		<div class="inline error"><p><?php _e( 'SveaWebPay Part Payment does not support your currency and/or country.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ); ?></p></div>
		<?php
		endif;
	}

	public function get_config ( $country = null ) {
		if( ! is_null( $country ) ) {

			$country = strtolower( $country );

			$testmode = $this->get_option( 'testmode_' . $country ) === "yes";
			$username = $this->get_option( 'username_' . $country );
			$password = $this->get_option( 'password_' . $country );
			$client_nr = $this->get_option( 'client_nr_' . $country );

			$config_class = $testmode ? "WC_Svea_Config_Test" : "WC_Svea_Config_Production";

			return new $config_class( false, false, $client_nr, $password, $username );
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

	public function process_payment( $order_id ) {
		self::log( "Payment processing started" );

		if( ! isset( $_POST["part_payment_plan"] ) ) {
			wc_add_notice( __( 'Part payment plan must be selected.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
			self::log( "Payment plan wasn't selected" );

			return array(
                'result'    => 'failure',
            );
		}

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

		$config = $this->get_config();

		$svea_order = WC_Gateway_Svea_Helper::create_svea_order( $wc_order, $config );

		$svea_order
			->setCountryCode($customer_country)
			->setClientOrderNumber($order_id)
			->setOrderDate(date("c"));

		$customer_information = WebPayItem::individualCustomer();

		switch( strtoupper( $customer_country ) ) {
			case "SE":
			case "DK":
			case "NO":
			case "FI":
				if( ! isset( $_POST["pp_billing_ssn"][0] ) ) {
					wc_add_notice( __( 'Personal number is required.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
					return array(
			            'result'    => 'failure',
			        );
				}

				$customer_information->setNationalIdNumber( $_POST["pp_billing_ssn"] );
				$customer_information->setStreetAddress( $customer_address_1 );
				break;
			case "NL":
				if( ! isset( $_POST["pp_billing_initials"][0] ) ) {
					wc_add_notice( __( 'Initials is a required field.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
					return array(
			            'result'    => 'failure',
			        );
				}

				if( ! isset( $_POST["pp_birth_date_year"][0] ) ) {
					wc_add_notice( __( 'Birth date year is a required field.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
					return array(
			            'result'    => 'failure',
			        );
				}
				if( ! isset( $_POST["pp_birth_date_month"][0] ) ) {
					wc_add_notice( __( 'Birth date month is a required field.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
					return array(
			            'result'    => 'failure',
			        );
				}

				if( ! isset( $_POST["pp_birth_date_day"][0] ) ) {
					wc_add_notice( __( 'Birth date day is a required field.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
					return array(
			            'result'    => 'failure',
			        );
				}

				$exploded_zip_code = str_split($customer_zip_code);
				$customer_zip_code = '';
				$lastChar = false;
				foreach($exploded_zip_code as $char) {
					if(is_numeric($lastChar) && !is_numeric($char)) 
						$customer_zip_code .= ' ' . $char;
					else
						$customer_zip_code .= $char;
					$lastChar = $char;
				}

				$customer_information->setInitials( $_POST["pp_billing_initials"] )
									->setBirthDate( intval( $_POST["pp_birth_date_year"] ),
										intval( $_POST["pp_birth_date_month"] ),
										intval( $_POST["pp_birth_date_day"] ) );

				$svea_address = Svea\WebPay\Helper\Helper::splitStreetAddress( $customer_address_1 );

				$customer_information->setStreetAddress( $svea_address[1], $svea_address[2] );
			break;
			case "DE":
				if( ! isset( $_POST["pp_birth_date_year"][0] ) ) {
					wc_add_notice( __( 'Birth date year is a required field.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
					return array(
			            'result'    => 'failure',
			        );
				}
				if( ! isset( $_POST["pp_birth_date_month"][0] ) ) {
					wc_add_notice( __( 'Birth date month is a required field.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
					return array(
			            'result'    => 'failure',
			        );
				}

				if( ! isset( $_POST["pp_birth_date_day"][0] ) ) {
					wc_add_notice( __( 'Birth date day is a required field.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
					return array(
			            'result'    => 'failure',
			        );
				}

				$customer_information->setBirthDate( intval( $_POST["pp_birth_date_year"] ),
										intval( $_POST["pp_birth_date_month"] ),
										intval( $_POST["pp_birth_date_day"] ) );

				$svea_address = Svea\WebPay\Helper\Helper::splitStreetAddress( $customer_address_1 );

				$customer_information->setStreetAddress( $svea_address[1], $svea_address[2] );
			break;
		}

		$customer_information
			->setZipCode( $customer_zip_code )
			->setLocality( $customer_city )
			->setName( $customer_first_name, $customer_last_name )
			->setIpAddress( $_SERVER['REMOTE_ADDR'] )
			->setEmail( $customer_email )
			->setPhoneNumber( $customer_phone )	
			->setCoAddress( $customer_address_2 );

		$svea_order->addCustomerDetails($customer_information);	

		$return_url = $wc_order->get_checkout_order_received_url();

		$request = $svea_order->usePaymentPlanPayment( $_POST["part_payment_plan"] );

		try {
			$response = $request->doRequest();
		} catch( Exception $e ) {
            wc_add_notice( __( 'An unknown error occurred. Please contact the store owner about this issue.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
			$wc_order->add_order_note( sprintf( __( 'Error occurred whilst processing payment: %s', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), $e->getMessage() ) );
			self::log( "Error: " . $e->getMessage() );

			return array(
	            'result'    => 'failure',
	        );
		}

		if( ! $response || ! isset( $response->accepted ) || ! $response->accepted ) {
            if( isset ( $response->resultcode ) ) {
                wc_add_notice( WC_Gateway_Svea_Helper::get_svea_error_message( $response->resultcode ) , 'error' );
                $wc_order->add_order_note( 
                	sprintf( 
                		__( 'Customer received error: %s', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 
                		WC_Gateway_Svea_Helper::get_svea_error_message( $response->resultcode ) 
                	) 
                );

                self::log( "Payment failed" );

            } else {
                wc_add_notice( __( 'An unknown error occurred. Please contact the store owner about this issue.', WC_SveaWebPay_Gateway::PLUGIN_SLUG ), 'error' );
            }

            return array(
	            'result'    => 'failure',
	        );
        }

		$svea_order_id = $response->sveaOrderId;

		update_post_meta( $order_id, "_svea_order_id", $svea_order_id );

		$wc_order->payment_complete( $svea_order_id );

		// Remove cart
		WC()->cart->empty_cart();

		self::log( "Payment successful" );

		return array(
			'result' => 'success',
			'redirect' => $return_url
		);	
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
                    ->cancelPaymentPlanOrder()
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
	 * Delivers the order in svea
	 *
	 * @param 	WC_Order 	$order 	the order being delivered
	 * @param 	string 		$svea_order_id	id of the svea order
	 * @return 	array 		an array containing result and message
	 */
	public function deliver_order( $order, $svea_order_id ) {
		$config = $this->get_config( $order->get_billing_country() );

        $response = WebPay::deliverOrder( $config )
                    ->setOrderId( $svea_order_id )
                    ->setCountryCode( $order->get_billing_country() )
                    ->setInvoiceDistributionType( DistributionType::POST )
                    ->deliverPaymentPlanOrder()
                    ->doRequest();

        if( ! $response || ! isset( $response->accepted ) || ! $response->accepted ) {
        	return array(
        		"success"	=> false,
        		"message"	=> $response->errormessage
        	);
        }

        foreach( $order->get_items( array( 'line_item', 'fee', 'shipping' ) ) as $order_item_id => $order_item ) {
            wc_add_order_item_meta( $order_item_id, 'svea_delivered', date("Y-m-d H:i:s") );
        }

        $order->update_status( 'completed' );

        /**
         * The request was successful
         */
        $order->add_order_note(
            __( 'All items have been delivered in Svea.', WC_SveaWebPay_Gateway::PLUGIN_SLUG )
        );

        return array(
        	"success"	=> true,
        	"message"	=> __( 'All items have been delivered in Svea.', WC_SveaWebPay_Gateway::PLUGIN_SLUG )
        );
	}
}