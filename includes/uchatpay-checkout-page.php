<?php
/**
 * Defaults for the UCash checkout page.
 */

add_filter( 'woocommerce_gateway_description', 'uchatpay_billing_phone_fields', 20, 2 );
add_action( 'woocommerce_checkout_process', 'uchatpay_billing_phone_fields_validation', 20, 1 );
add_action( 'woocommerce_checkout_update_order_meta', 'uchatpay_billing_phone_save_field' );
add_action( 'woocommerce_admin_order_data_after_billing_address', 'uchatpay_billing_phone_show_field_admin_order', 10, 1 );

/**
 * Check if the phone number for billing is filled.
 *
 * @param object $order Order Object.
 * @return void
 */
function uchatpay_billing_phone_fields_validation( $order ) {

    if ( 'uchatpay_payment' === $_POST['payment_method'] ) {
    
        $uchatpay_payment_phone_number = '256'.$_POST['uchatpay_payment_phone_number'];
    
        // Error the Phone number
        if( ! isset( $uchatpay_payment_phone_number ) || empty( $uchatpay_payment_phone_number ) ) {
            wc_add_notice( 'Please enter the Phone Number for Billing (Format: 0702123456 )', 'error' );
            return;
        }
    
        $accepted_numbers = ['25670', '25678', '25677', '25676', '25675'];
        if( !in_array(substr( $uchatpay_payment_phone_number, 5 ), $accepted_numbers) && 12 !== strlen( $uchatpay_payment_phone_number ) && ! is_numeric( $uchatpay_payment_phone_number ) ) {
            wc_add_notice( 'Please enter Airtel Money number e.g 0702123456 )', 'error' );
        }

    }

}

/**
 * Set up billing number for the payment gateway.
 *
 * @param array $description Fields added in the gateway platform.
 * @param int $payment_id    Order Payment ID.
 * @return void
 */
function uchatpay_billing_phone_fields( $description, $payment_id ) {

    if ( 'uchatpay_payment' !== $payment_id ) {
        return $description;
    }

    ob_start();
    
    // Billing number Field.
    woocommerce_form_field(
        'uchatpay_payment_phone_number',
        array(
            'type' => 'text',
            'label' =>__( 'Enter Phone Number e.g 0702123456', 'uchatpay-pay-woo' ),
            'class' => array( 'form-row', 'form-row-wide', 'card-number' ),
            'required' => true,
        )
    );

    $description .= ob_get_clean();
    
    return $description;
}
				
function uchatpay_billing_phone_save_field( $order_id ) {
    
    $order       = new WC_Order( $order_id );
    $order_total = intval( $order->get_total() );
    
    if ( $_POST['uchatpay_payment_phone_number'] ) {
        update_post_meta( $order_id, 'uchatpay_payment_phone_number', esc_attr( $_POST['uchatpay_payment_phone_number'] ) );
    }
    if ( $_POST['uchatpay_payment_phone_number'] ) {
        update_post_meta( $order_id, 'uchatpay_external_reference', esc_attr( $_POST['uchatpay_payment_phone_number'] . $order_id . $order_total ) );
    }
}
   
function uchatpay_billing_phone_show_field_admin_order( $order ) {    
   $order_id = $order->get_id();

//    if ( get_post_meta( $order_id, 'uchatpay_payment_phone_number', true ) ) {
//        echo '<p><strong>UCHATPAY Payment number:</strong> ' . get_post_meta( $order_id, 'uchatpay_payment_phone_number', true ) . '</p>';
//        echo '<p><strong>UCHATPAY External Reference:</strong> ' . get_post_meta( $order_id, 'uchatpay_external_reference', true ) . '</p>';
//    }
}