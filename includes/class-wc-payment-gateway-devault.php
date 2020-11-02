<?php
/**
 * DeVault Payments Gateway.
 *
 * Provides a DeVault Payments Payment Gateway.
 *
 * @class       WC_Gateway_devault
 * @extends     WC_Payment_Gateway
 * @version     0.1.0
 * @package     WooCommerce/Classes/Payment
 */

class WC_Gateway_devault extends WC_Payment_Gateway {
	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		// Setup general properties.
		$this->setup_properties();

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Get settings.
		$this->title            		 = $this->get_option( 'title' );
		$this->store_default_address = $this->get_option( 'store_default_address' );
		$this->disposable_addresses	 = $this->get_option( 'disposable_addresses' );
		$this->description        	 = $this->get_option( 'description' );
		$this->instructions       	 = $this->get_option( 'instructions' );
		$this->enable_for_methods 	 = $this->get_option( 'enable_for_methods', array() );
		$this->enable_for_virtual 	 = $this->get_option( 'enable_for_virtual', 'yes' ) === 'yes';
		$this->devault_timeout     	 = $this->get_option( 'devault_timeout' );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
		add_filter( 'woocommerce_payment_complete_order_status', array( $this, 'change_payment_complete_order_status' ), 10, 3 );

		// Customer Emails.
		add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
	}

	/**
	 * Setup general properties for the gateway.
	 */
	protected function setup_properties() {
		$this->id                 = 'devault';
		$this->icon               = apply_filters( 'woocommerce_devault_icon', plugin_dir_url(__FILE__).'../assets/DVT-Logo-SVG-Horizontal-Dark.svg' );
		$this->method_title       = __( 'DeVault Payments', 'devault-payments-woo' );
		$this->method_description = __( 'Have your customers pay with devault  Payments.', 'devault-payments-woo' );
		$this->devault_timeout    = __( 'Payment timeout' );
		$this->price_in_dvt		  	= 0;
		$this->has_fields         = false;
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'            => array(
				'title'       => __( 'Enable/Disable', 'devault-payments-woo' ),
				'label'       => __( 'Enable DeVault Payments', 'devault-payments-woo' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no',
			),
			'title'              => array(
				'title'       => __( 'Title', 'devault-payments-woo' ),
				'type'        => 'text',
				'description' => __( 'Payment method description that the customer will see on your checkout.', 'devault-payments-woo' ),
				'default'     => __( 'DeVault Payments', 'devault-payments-woo' ),
				'desc_tip'    => true,
			),
			'description'        => array(
				'title'       => __( 'Description', 'devault-payments-woo' ),
				'type'        => 'textarea',
				'description' => __( 'DeVault Payment method description that the customer will see on your website.', 'devault-payments-woo' ),
				'default'     => __( 'DeVault Payments before delivery.', 'devault-payments-woo' ),
				'desc_tip'    => true,
			),
			'instructions'       => array(
				'title'       => __( 'Instructions', 'devault-payments-woo' ),
				'type'        => 'textarea',
				'description' => __( 'Additional instructions text.', 'devault-payments-woo' ),
				'default'     => __( 'DeVault Payments before delivery.', 'devault-payments-woo' ),
				'desc_tip'    => true,
			),
			'enable_for_methods' => array(
				'title'             => __( 'Enable for shipping methods', 'devault-payments-woo' ),
				'type'              => 'multiselect',
				'class'             => 'wc-enhanced-select',
				'css'               => 'width: 400px;',
				'default'           => '',
				'description'       => __( 'If devault is only available for certain methods, set it up here. Leave blank to enable for all methods.', 'devault-payments-woo' ),
				'options'           => $this->load_shipping_method_options(),
				'desc_tip'          => true,
				'custom_attributes' => array(
					'data-placeholder' => __( 'Select shipping methods', 'devault-payments-woo' ),
				),
			),
			'enable_for_virtual' => array(
				'title'   => __( 'Accept for virtual orders', 'devault-payments-woo' ),
				'label'   => __( 'Accept devault if the order is virtual', 'devault-payments-woo' ),
				'type'    => 'checkbox',
				'default' => 'yes',
			),
			'store_default_address'  => array(
			'title'       => __( 'Your stores default DeVault address', 'devault-payments-woo' ),
			'type'        => 'text',
			'description' => __( 'This is your fallback DeVault address.', 'devault-payments-woo' ),
			'default'     => __( 'enter your stores default DeVault address here.', 'devault-payments-woo' ),
			'desc_tip'    => true,
			),
			'disposable_addresses'  => array(
			'title'       => __( 'List of disposable DeVault addresses.', 'devault-payments-woo' ),
			'type'        => 'textarea',
			'description' => __( 'enter a list of disposable devault addresses separated by "/" here.', 'devault-payments-woo' ),
			'default'     => __( 'List of "/" separated disposable addresses.', 'devault-payments-woo' ),
			'desc_tip'    => true,
			),
			'devault_timeout'  => array(
			'title'       => __( 'Payment timeout', 'devault-payments-woo' ),
			'type'        => 'text',
			'description' => __( 'Set timeout in seconds.', 'devault-payments-woo' ),
			'default'     => __( '300', 'devault-payments-woo' ),
			'desc_tip'    => true,
			),
		);
	}

	/**
	 * Check If The Gateway Is Available For Use.
	 *
	 * @return bool
	 */
	public function is_available() {
		$order          = null;
		$needs_shipping = false;

		// Test if shipping is needed first.
		if ( WC()->cart && WC()->cart->needs_shipping() ) {
			$needs_shipping = true;
		} elseif ( is_page( wc_get_page_id( 'checkout' ) ) && 0 < get_query_var( 'order-pay' ) ) {
			$order_id = absint( get_query_var( 'order-pay' ) );
			$order    = wc_get_order( $order_id );

			// Test if order needs shipping.
			if ( 0 < count( $order->get_items() ) ) {
				foreach ( $order->get_items() as $item ) {
					$_product = $item->get_product();
					if ( $_product && $_product->needs_shipping() ) {
						$needs_shipping = true;
						break;
					}
				}
			}
		}

		$needs_shipping = apply_filters( 'woocommerce_cart_needs_shipping', $needs_shipping );

		// Virtual order, with virtual disabled.
		if ( ! $this->enable_for_virtual && ! $needs_shipping ) {
			return false;
		}

		// Only apply if all packages are being shipped via chosen method, or order is virtual.
		if ( ! empty( $this->enable_for_methods ) && $needs_shipping ) {
			$order_shipping_items            = is_object( $order ) ? $order->get_shipping_methods() : false;
			$chosen_shipping_methods_session = WC()->session->get( 'chosen_shipping_methods' );

			if ( $order_shipping_items ) {
				$canonical_rate_ids = $this->get_canonical_order_shipping_item_rate_ids( $order_shipping_items );
			} else {
				$canonical_rate_ids = $this->get_canonical_package_rate_ids( $chosen_shipping_methods_session );
			}

			if ( ! count( $this->get_matching_rates( $canonical_rate_ids ) ) ) {
				return false;
			}
		}

		return parent::is_available();
	}

	/**
	 * Checks to see whether or not the admin settings are being accessed by the current request.
	 *
	 * @return bool
	 */
	private function is_accessing_settings() {
		if ( is_admin() ) {
			// phpcs:disable WordPress.Security.NonceVerification
			if ( ! isset( $_REQUEST['page'] ) || 'wc-settings' !== $_REQUEST['page'] ) {
				return false;
			}
			if ( ! isset( $_REQUEST['tab'] ) || 'checkout' !== $_REQUEST['tab'] ) {
				return false;
			}
			if ( ! isset( $_REQUEST['section'] ) || 'devault' !== $_REQUEST['section'] ) {
				return false;
			}
			// phpcs:enable WordPress.Security.NonceVerification

			return true;
		}

		return false;
	}

	/**
	 * Loads all of the shipping method options for the enable_for_methods field.
	 *
	 * @return array
	 */
	private function load_shipping_method_options() {
		// Since this is expensive, we only want to do it if we're actually on the settings page.
		if ( ! $this->is_accessing_settings() ) {
			return array();
		}

		$data_store = WC_Data_Store::load( 'shipping-zone' );
		$raw_zones  = $data_store->get_zones();

		foreach ( $raw_zones as $raw_zone ) {
			$zones[] = new WC_Shipping_Zone( $raw_zone );
		}

		$zones[] = new WC_Shipping_Zone( 0 );

		$options = array();
		foreach ( WC()->shipping()->load_shipping_methods() as $method ) {

			$options[ $method->get_method_title() ] = array();

			// Translators: %1$s shipping method name.
			$options[ $method->get_method_title() ][ $method->id ] = sprintf( __( 'Any &quot;%1$s&quot; method', 'devault-payments-woo' ), $method->get_method_title() );

			foreach ( $zones as $zone ) {

				$shipping_method_instances = $zone->get_shipping_methods();

				foreach ( $shipping_method_instances as $shipping_method_instance_id => $shipping_method_instance ) {

					if ( $shipping_method_instance->id !== $method->id ) {
						continue;
					}

					$option_id = $shipping_method_instance->get_rate_id();

					// Translators: %1$s shipping method title, %2$s shipping method id.
					$option_instance_title = sprintf( __( '%1$s (#%2$s)', 'devault-payments-woo' ), $shipping_method_instance->get_title(), $shipping_method_instance_id );

					// Translators: %1$s zone name, %2$s shipping method instance name.
					$option_title = sprintf( __( '%1$s &ndash; %2$s', 'devault-payments-woo' ), $zone->get_id() ? $zone->get_zone_name() : __( 'Other locations', 'devault-payments-woo' ), $option_instance_title );

					$options[ $method->get_method_title() ][ $option_id ] = $option_title;
				}
			}
		}

		return $options;
	}

	/**
	 * Converts the chosen rate IDs generated by Shipping Methods to a canonical 'method_id:instance_id' format.
	 *
	 * @since  3.4.0
	 *
	 * @param  array $order_shipping_items  Array of WC_Order_Item_Shipping objects.
	 * @return array $canonical_rate_ids    Rate IDs in a canonical format.
	 */
	private function get_canonical_order_shipping_item_rate_ids( $order_shipping_items ) {

		$canonical_rate_ids = array();

		foreach ( $order_shipping_items as $order_shipping_item ) {
			$canonical_rate_ids[] = $order_shipping_item->get_method_id() . ':' . $order_shipping_item->get_instance_id();
		}

		return $canonical_rate_ids;
	}

	/**
	 * Converts the chosen rate IDs generated by Shipping Methods to a canonical 'method_id:instance_id' format.
	 *
	 * @since  3.4.0
	 *
	 * @param  array $chosen_package_rate_ids Rate IDs as generated by shipping methods. Can be anything if a shipping method doesn't honor WC conventions.
	 * @return array $canonical_rate_ids  Rate IDs in a canonical format.
	 */
	private function get_canonical_package_rate_ids( $chosen_package_rate_ids ) {

		$shipping_packages  = WC()->shipping()->get_packages();
		$canonical_rate_ids = array();

		if ( ! empty( $chosen_package_rate_ids ) && is_array( $chosen_package_rate_ids ) ) {
			foreach ( $chosen_package_rate_ids as $package_key => $chosen_package_rate_id ) {
				if ( ! empty( $shipping_packages[ $package_key ]['rates'][ $chosen_package_rate_id ] ) ) {
					$chosen_rate          = $shipping_packages[ $package_key ]['rates'][ $chosen_package_rate_id ];
					$canonical_rate_ids[] = $chosen_rate->get_method_id() . ':' . $chosen_rate->get_instance_id();
				}
			}
		}

		return $canonical_rate_ids;
	}

	/**
	 * Indicates whether a rate exists in an array of canonically-formatted rate IDs that activates this gateway.
	 *
	 * @since  3.4.0
	 *
	 * @param array $rate_ids Rate ids to check.
	 * @return boolean
	 */
	private function get_matching_rates( $rate_ids ) {
		// First, match entries in 'method_id:instance_id' format. Then, match entries in 'method_id' format by stripping off the instance ID from the candidates.
		return array_unique( array_merge( array_intersect( $this->enable_for_methods, $rate_ids ), array_intersect( $this->enable_for_methods, array_unique( array_map( 'wc_get_string_before_colon', $rate_ids ) ) ) ) );
	}

	public static function get_dvt_price(){
		if( get_woocommerce_currency() == 'DVT' ) {
			return 1;
		} else {
			$json = shell_exec( 'curl -X GET "https://api.coingecko.com/api/v3/simple/price?ids=devault&vs_currencies='.get_woocommerce_currency().'" -H "accept: application/json"');
			$decode = json_decode($json,true);
			$woo_currency_price = $decode['devault'][''.strtolower( get_woocommerce_currency() ).''];
			$devault_val = floatval ( $woo_currency_price );
			return $devault_val;
			}
		}

	// calculate order total dvt value + rand to make unique
	public static function calc_dvt_total( $total ){
		$total_val = floatval ( $total );
		$devault_val = floatval ( WC_Gateway_devault::get_dvt_price() );
		$price_in_dvt = number_format( ( $total_val / $devault_val ) , 3,'.', '' );
		return $price_in_dvt;
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( $order->get_total() > 0 ) {
			if ($this->devault_payment_processing( $order ) ){
				return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url( $order ),
					);
				}
		}
	}

	public function devault_payment_processing( $order ) {
		$txid 				= esc_attr( $_POST['txid'] );
		$total 				= esc_attr( $_POST['dvttotal'] ) * 100000000;
//		$amount 			= ($this->calc_dvt_total($order->get_total() ) * 100000000);

		// set store adderss
		if ( strlen( $this->store_default_address ) == 50  ) {
			$store_address	= substr( $this->store_default_address, 8 );
			} else { $store_address = $this->store_default_address ;};

		//check if txid is valid, need proper test method
		if( strlen($txid) != 64){
			$error_message .= "total: " . $total . " , txid: " . $txid . " , verified: " . $verified . "store: " . $store_address;
			wc_add_notice( __('txid not valid', 'dvtpay-payments-woo') . $error_message, 'error' );
			return false;
		}

		// set vars etc to curl tx from bitdb
		$json_url			= 'curl https://bitdb.exploredvt.com/q/';
		$query 				= array(
								"v" => 3,
								"q" => array(
								"find" => array(
									"tx.h"    => $txid ),
								"limit" => 1
								)
							);

		$b64 				  = base64_encode( json_encode( $query, JSON_UNESCAPED_SLASHES ));
		$json_url 		.= $b64;
		$json 				= shell_exec( $json_url );
		$decode 			= json_decode( $json, true );
		$verified			= 0;

		// check if amount and storeaddress is in tx

		// loop thru unconfirmed if on
		$outputs = $decode["u"][0]["out"];
		echo  'no outputs: '.( count( $outputs )) ;
		for($x=0; $x< count( $outputs ); $x++ ) {
			echo 'output #: '. $x.'  out.e.v: '.$outputs[$x]["e"]["v"].' - ';
			if(  ( ( (int)$outputs[$x]["e"]["v"]) == (int)$total ) && ( $outputs[$x]["e"]["a"] == $store_address ) ){
					$verified = 1;
					$opaddy 	=  $outputs[$x]["e"]["a"];
					$opamount =  $outputs[$x]["e"]["v"];
					break;
				}
			}
		echo 'verified: '.$verified;
		if ( $verified  == 0 ){
			$error_message .= "total: " . $total . " amount: ".$amount. ", tx amount ". $opamount . " txid: " . $txid . " ,tx addyerified: " .$opaddy . "store: " . $store_address;
			wc_add_notice( __('Payment was not verified.', 'dvtpay-payments-woo') . $error_message, 'error' );
			return false;
			}

		if ( $verified  == 1){
			$order->update_status('pending-payment', __('Awaiting DeVault payment', 'dvtpay-payments-woo'),true);
			//$order->payment_complete();
			// Remove cart.
			WC()->cart->empty_cart();
			// Return thankyou redirect.
			return true;
			}
	}

	/**
	 * Output for the order received page.
	 */
	public function thankyou_page() {
		if ( $this->instructions ) {
			echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) );
		}
	}

	/**
	 * Change payment complete order status to completed for devault orders.
	 *
	 * @since  3.1.0
	 * @param  string         $status Current order status.
	 * @param  int            $order_id Order ID.
	 * @param  WC_Order|false $order Order object.
	 * @return string
	 */
	public function change_payment_complete_order_status( $status, $order_id = 0, $order = false ) {
		if ( $order && 'devault' === $order->get_payment_method() ) {
			$status = 'completed';
		}
		return $status;
	}

	/**
	 * Add content to the WC emails.
	 *
	 * @param WC_Order $order Order object.
	 * @param bool     $sent_to_admin  Sent to admin.
	 * @param bool     $plain_text Email format: plain text or HTML.
	 */
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		if ( $this->instructions && ! $sent_to_admin && $this->id === $order->get_payment_method() ) {
			echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) . PHP_EOL );
			}
		}

}// end class
