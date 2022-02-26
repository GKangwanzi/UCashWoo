<?php
/**
 * UCashMoney Gateway Class
 */

/**
 * UCashMoney Gateway.
 *
 * Provides a UCashMoney Payment Gateway.
 *
 * @class       WC_Uchatpay_Gateway
 * @extends     WC_Payment_Gateway
 * @version     0.1.0
 */

if ( ! class_exists( 'WC_Uchatpay_Gateway' ) && class_exists( 'WC_Payment_Gateway' ) ) {
	class WC_Uchatpay_Gateway extends WC_Payment_Gateway {

		/**
		 * Constructor for the gateway.
		 */
		public function __construct() {

			// Setup general properties.
			$this->setup_properties();
			
			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();
	
			//Check if the gateway can be used
			if ( ! $this->is_valid_for_use() ) {
				$this->enabled = false;
			}
	
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
			add_filter( 'woocommerce_thankyou_order_received_text', array( $this, 'uchatpay_thank_you' ) );
			add_filter( 'woocommerce_payment_complete_order_status', array( $this, 'change_payment_complete_order_status' ), 10, 3 );
	
			// Customer Emails.
			add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );

		}
	
		/**
		 * Setup general properties for the gateway.
		 */
		protected function setup_properties() {
			$this->id                   = 'uchatpay_payment';
			$this->icon                 = apply_filters( 'woocommerce_uchatpay_icon', plugins_url('/images/mm-logo.png', dirname( __FILE__, 1 ) ) );
			$this->has_fields           = false;
			$this->method_title         = __( 'UCashMoney', 'uchatpay-pay-woo');
			$this->method_description   = __( 'UCashMoney allows customers to make payment using Mobile Money.', 'uchatpay-pay-woo');
	
			// Get settings.
			$this->title              = $this->get_option( 'title' );
			$this->description        = $this->get_option( 'description' );
			$this->instructions       = $this->get_option( 'instructions' );
			$this->enable_for_methods = $this->get_option( 'enable_for_methods', array() );
			$this->enable_for_virtual = $this->get_option( 'enable_for_virtual', 'yes' ) === 'yes';
			$this->api_key 			  = $this->get_option( 'api_key' );
			// Extra settings
			$this->webhook_url        = $this->get_option( 'webhook_url' );
			$this->domain_url         = get_bloginfo('url');
	
			//Check if the gateway can be used
			if ( ! $this->is_valid_for_use() ) {
				$this->enabled = false;
			}
		}
	
		/**
		 * Initialise Gateway Settings Form Fields.
		 */
		public function init_form_fields() {
			$this->form_fields = array(
				'enabled' => array(
					'title' => __( 'Enable/Disable', 'uchatpay-pay-woo'),
					'type' => 'checkbox',
					'label' => __( 'Enable or Disable UCashMoney', 'uchatpay-pay-woo'),
					'description' => __( 'Switch on UCashMoney payment option.', 'uchatpay-pay-woo'),
					'default' => 'no'
				),
				'title' => array(
					'title' => __( 'Title', 'uchatpay-pay-woo'),
					'type' => 'text',
					'default' => __( 'Mobile Payments', 'uchatpay-pay-woo'),
					'desc_tip' => true,
					'description' => __( 'This controls the title which the user sees during checkout.', 'uchatpay-pay-woo')
				),
				'description' => array(
					'title' => __( 'Checkout Description', 'uchatpay-pay-woo'),
					'type' => 'textarea',
					'default' => __( 'Pay using either your Mobile Phone on MTN Mobile Money or Airtel Money.', 'uchatpay-pay-woo'),
					'desc_tip' => true,
					'description' => __( 'This controls the description which the user sees during checkout.', 'uchatpay-pay-woo')
				),
				'instructions' => array(
					'title' => __( 'Customer Instructions', 'uchatpay-pay-woo'),
					'type' => 'textarea',
					'default' => __( 'Your order has been received. Please check your Mobile Money Account to complete the transaction.', 'uchatpay-pay-woo'),
					'desc_tip' => true,
					'description' => __( 'Instructions that will be added to the thank you page and order email', 'uchatpay-pay-woo')
				),
				'api_key' => array(
					'title'       => __( 'Ucatchapps API Key', 'uchatpay-pay-woo' ),
					'type'        => 'textarea',
					'desc_tip'    => true,
					'description' => __( 'API Key that will be added to the ucatchapp API request', 'uchatpay-pay-woo'),
					'placeholder' => __( 'Enter Ucatchapps API Key', 'uchatpay-pay-woo'),
					'default'     => ''
				),
				'webhook_url' => array(
					'title'   => __( 'INCOMING WEBHOOK URL', 'uchatpay-pay-woo' ),
					'label'   => sprintf( __( 'I have added <code>%s</code> as my WEBHOOK URL in UCashMoney Admin Dashboard.', 'text_domain' ), "$this->domain_url/wp-json/uchatpay/v1/payments" ),
					'type'    => 'checkbox',
					'default' => 'yes',
				),
				'enable_for_virtual' => array(
					'title'   => __( 'Accept for virtual orders', 'uchatpay-pay-woo' ),
					'label'   => __( 'Accept UCashMoney if the order is virtual', 'uchatpay-pay-woo' ),
					'type'    => 'checkbox',
					'default' => 'yes',
					
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
		 * Process the payment and return the result.
		 *
		 * @param int $order_id Order ID.
		 * @return array
		 */
		public function process_payment( $order_id ) {
			
			// Get Order Object.
			$order = wc_get_order( $order_id );
	
			// Get the currency code.
			$currency_code   = $order->get_currency();
			
			if ( $order->get_total() > 0 ) {
	
				// Mark as processing or on-hold (payment won't be taken until delivery).
				$order->update_status( apply_filters( 'uchatpay_woocommerce_process_payment_order_status', $order->has_downloadable_item() ? 'on-hold' : 'wc-pending', $order ), __( 'Payments pending clearing via mobile money account.', 'woocommerce' ) );
				
				// Api Payments query.
				$this->clear_payment_with_api( $order_id, $order );
	
			} else {
				$order->payment_complete();
			}
	
			// Remove cart.
			WC()->cart->empty_cart();
	
			// Return thankyou redirect.
			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $order ),
			);
			
		}
	
		/**
		 * Output for the order received page.
		 */
		public function thankyou_page() {
			if ( $this->instructions ) {
				echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) );
			}
		}
	
		public function uchatpay_thank_you() {
			$added_text = $this->instructions;
			return $added_text ;
		}
	
		/**
		 * Change payment complete order status to completed for COD orders.
		 *
		 * @since  3.1.0
		 * @param  string         $status Current order status.
		 * @param  int            $order_id Order ID.
		 * @param  WC_Order|false $order Order object.
		 * @return string
		 */
		public function change_payment_complete_order_status( $status, $order_id = 0, $order = false ) {
			if ( $order && 'uchatpay_payment' === $order->get_payment_method() ) {
				$status = 'wc-pending';
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
		
		/**
		 * Check if currency in use is allowed
		 */
		public function is_valid_for_use() {
			if( ! in_array( get_woocommerce_currency(), array( 'UGX' ) ) ) {
				$this->msg = 'Uchatpay does not support your store currency, set it to Ugandan Shillings UGX <a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=wc-settings&tab=general">here</a>';
				return false;    
			}
			return true;
		}
	
		public function clear_payment_with_api( $order_id, $order ) {
			
			$order_total  = intval( $order->get_total() );
			$phone_number = $_POST['uchatpay_payment_phone_number'];
			$phone_number = '256'.substr($phone_number, 1);
			

			$url = 'https://ucatchapps.com/apicatch/index.php?requestloading=1&phone='.$phone_number.'&amount='.$order_total.'&apikey='.$this->api_key;
			
			$arguments = array(
				'method'    => 'POST',
				'timeout'   => 60,
                'sslverify' => false
			);
			
			$response = wp_remote_get( esc_url_raw( $url ), $arguments );
	
			$response_body  = wp_remote_retrieve_body( $response );
			$status_reponse = json_decode( $response_body, true );
			$status_code    = wp_remote_retrieve_response_code( $response );
	
			if ( empty( $status_reponse ) || $status_code !== 200 ) {
				return new \WP_Error(
					'invalid_data',
					'Invalid data returned', 
					[
						'code' => $status_code,
						'body' => $response_body,
					]
				);
			}
			update_post_meta($order_id, 'load', $response_body );
			update_post_meta($order_id, 'transactionreference', $status_reponse['resuldatat']['TransactionReference'] );
			update_post_meta($order_id, 'transactionstatus', $status_reponse['resuldatat']['TransactionStatus'] );
			wc_add_notice( __( 'Thank you for shopping with us. Your account has been charged. Kindly complete the mobile transaction on your phone.', 'woocommerce' ), 'success' );
			
			return;

		}
	}
}
