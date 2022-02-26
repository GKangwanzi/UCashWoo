<?php
/**
 * 
 * Plugin Name: UCashMoney for WooCommerce
 * Plugin URI: https://cash.ucatchapps.com
 * Author: UCatch Technologies Ltd
 * Author URI: https://ucatchapps.com
 * Description: UCashMoney enables businesses to receive Mobile Money Payments from their customers. Supports MTN Mobile Money and Airtel Money.
 * Version: 1.0.1
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text-Domain: ucashmoney-woo
 * 
 * WC requires at least: 3.0
 * WC tested up to: 4.3.0
 */ 

// Basic Security to avoid brute access to file.
defined( 'ABSPATH' ) or exit;

// Check if WooCommerce is installed.
// if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) return;

// Define constants to be used.
if( ! defined( 'UCHATPAY_BASENAME' ) ) {
	define( 'UCHATPAY_BASENAME', plugin_basename( __FILE__ ) );
}

if( ! defined( 'UCHATPAY_DIR_PATH' ) ) {
	define( 'UCHATPAY_DIR_PATH', plugin_dir_path( __FILE__ ) );
}

// When plugin is loaded. Call init functions.
add_action( 'plugins_loaded', 'uchatpay_payment_init' );
add_filter( 'woocommerce_payment_gateways', 'uchatpay_payment_gateway_add_to_woo');

/**
 * Add the gateway class.
 * Add function helpers.
 * 
 * @return void
 */
function uchatpay_payment_init() {
	require_once UCHATPAY_DIR_PATH . 'includes/uchatpay-initial-setup.php';
	require_once UCHATPAY_DIR_PATH . 'includes/class-uchatpay-gateway.php';
	require_once UCHATPAY_DIR_PATH . 'includes/uchatpay-checkout-page.php';
	require_once UCHATPAY_DIR_PATH . 'includes/uchatpay-payments-cpt.php';
	require_once UCHATPAY_DIR_PATH . 'includes/uchatpay-rest-api-endpoint.php';
}

/**
 * Add Payment gateway to Woocommerce.
 *
 * @param array $gateways Existing Gateways in WC.
 * @return array $gateways Existing Gateways in WC + Uchatpay.
 */
function uchatpay_payment_gateway_add_to_woo( $gateways ) {
    $gateways[] = 'WC_Uchatpay_Gateway';
    return $gateways;
}
