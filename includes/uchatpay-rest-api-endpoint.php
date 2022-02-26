<?php
/**
 * Get the order ID for the Paid Shop Order.
 *
 * @param integer  $uchatID
 * @return array $meta_query Array of Meta ID, Order ID, Meta key, Meta Value.
 */
function uchatpay_order_external_ref_metakey( $uchatID ) {

    global $wpdb, $table_prefix;

	$table      = $table_prefix . 'postmeta';
    $meta_query = $wpdb->get_results("SELECT * FROM $table WHERE meta_key = 'transactionreference' AND meta_value = '$uchatID'", ARRAY_A );
	
	return $meta_query;
}

/**
 * Handle all the Webhook payments.
 */
add_action( 'rest_api_init', 'uchatpay_payments_endpoint' );

/**
 * Set up REST Route.
 * @function permission_callback - If it is to be public.
 * @return void
 */
function uchatpay_payments_endpoint() {
	register_rest_route(
		'uchatpay/v1',
		'payments',
		array(
			'methods'             => 'POST',
			'callback'            => 'uchatpay_endpoint_cb',
			'permission_callback' => '__return_true',
		)
	);
}

function uchatpay_endpoint_cb( $request_data ) {
    
	// Fetching values from API
	$parameters = $request_data->get_params();
	// custom meta values
	$uchatID  = $parameters['transactionreference'] ;
	
	// Clear the order with the particular External ID.
    $external_ref_metakey = uchatpay_order_external_ref_metakey( $uchatID );
	$order_id             = $external_ref_metakey[0]['post_id'];
	$order_meta           = $external_ref_metakey[0]['meta_value'];
    
    if ( empty( $order_id ) || $order_id != true ) {
        return;
    }

	$order = new WC_Order( $order_id );
	$order->payment_complete();
	$order->update_status( 'completed' );
	$order->add_order_note('Payment was successful!');

	/**
	 * Create Transaction Post Object 
	 * Create post type to be manually verified later.
	 */
	$post_title = 'Payment for Order #' . $order_id;
	$post_type  = 'uchatpayments';

	$uchatpay_post = array(
		'post_title'  => $post_title,
		'post_status' => 'publish',
		'post_type'   => $post_type,
	);

	$uchatpay_post_id = wp_insert_post( $uchatpay_post );

	// Set Custom Metabox
	update_post_meta( $uchatpay_post_id, 'apipaymentreceipt', $parameters['apipaymentreceipt'] );
	update_post_meta( $uchatpay_post_id, 'msdn', $parameters['msdn'] );
	update_post_meta( $uchatpay_post_id, 'amount', $parameters['amount'] );
	update_post_meta( $uchatpay_post_id, 'transactionreference', $parameters['transactionreference'] );
	update_post_meta( $uchatpay_post_id, 'transactionid', $parameters['transactionid'] );
	
	$data = new WP_REST_Response(
		array(
			'status' => 'Received'
		)
	);

	$data->set_status(200);
		
	return $data;
}
