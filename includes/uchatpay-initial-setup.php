<?php
/**
 * Things that run before the default plugin functions.
 * 
 * Add UGX as a currency in WC
 * Enable the Ugandan Shillings currency symbol in WC
 */
if( ! function_exists( 'uchatpay_add_ugx_currency' ) ) {
	add_filter( 'woocommerce_currencies', 'uchatpay_add_ugx_currency' );
}

if( ! function_exists( 'uchatpay_add_ugx_currency_symbol' ) ) {
	add_filter('woocommerce_currency_symbol', 'uchatpay_add_ugx_currency_symbol', 10, 2);
}

/**
 * Add UGX Currency if that does not exist.
 *
 * @param array $currencies All old currencies.
 * @return array $currencies All new currencies + UGX.
 */
function uchatpay_add_ugx_currency( $currencies ) {
	$currencies['UGX'] = __( 'Ugandan Shillings', 'uchatpay-pay-woo' );
	return $currencies;
}

/**
 * Add Currency symbol for UGX if that does not exist.
 *
 * @param array $currencies All old Currency symbol.
 * @return array $currencies All new Currency symbol + UGX.
 */
function uchatpay_add_ugx_currency_symbol( $currency_symbol, $currency ) {
	switch( $currency ) {
		case 'UGX': $currency_symbol = 'UGX '; break;
	}
	return $currency_symbol;
}

/**
 * Adds plugin page links
 * 
 * @since 0.1.0
 * @param array $links all plugin links
 * @return array $links all plugin links + our custom links (i.e., "Settings")
 */
function wc_uchatpay_gateway_plugin_links( $links ) {

	$plugin_links = array(
		'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=uchatpay_payment' ) . '">' . __( 'Configure Gateway', 'uchatpay-pay-woo' ) . '</a>',
	);

	return array_merge( $plugin_links, $links );
}

add_filter( 'plugin_action_links_' . UCHATPAY_BASENAME, 'wc_uchatpay_gateway_plugin_links' );
