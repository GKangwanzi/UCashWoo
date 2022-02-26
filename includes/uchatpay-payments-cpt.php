<?php
/**
 * Uchatpay Payments Custom Post Type for the client follow up.
 */
function uchatpay_setup_post_type() {
    $args = array(
        'public'    => true,
        'label'     => __( 'MPayments', 'textdomain' ),
        'menu_icon' => 'dashicons-analytics',
        'supports'  => array( 'title' ),
        'capabilities' => array(
            'create_posts' => 'false', // false < WP 4.5, credit @Ewout
          ),
        'map_meta_cap' => 'false', // Set to `false`, if users are not allowed to edit/delete existing posts
    );
    register_post_type( 'uchatpayments', $args );
}

add_action( 'init', 'uchatpay_setup_post_type' );

// Disable Add new Posts.
function disable_new_posts() {
    // Hide sidebar link
    global $submenu;
    unset($submenu['edit.php?post_type=uchatpayments'][10]);

    // Hide link on listing page
    if (isset($_GET['post_type']) && $_GET['post_type'] == 'uchatpayments') {
        echo '<style type="text/css">
        #favorite-actions, .page-title-action, .add-new-h2, .tablenav { display:none; }
        </style>';
    }
}

// add_action('admin_menu', 'disable_new_posts');

// Add metabox
function uchatpay_add_custom_box() {
    $screens = ['uchatpayments'];
    foreach ($screens as $screen) {
        add_meta_box(
            'uchatpay_payments_box',           // Unique ID
            'Payment Information',  // Box title
            'uchatpay_show_admin_boxes',  // Content callback, must be of type callable
            $screen                   // Post type
        );
    }
}

add_action( 'add_meta_boxes', 'uchatpay_add_custom_box' );

// Show admin side boxes
function uchatpay_show_admin_boxes( $post ) {
    ?>

    <label for="apipaymentreceipt">API Payment Receipt</label><br>
    <input readonly type="text" name="apipaymentreceipt" id="apipaymentreceipt" class="widefat" value="<?php echo get_post_meta($post->ID,'apipaymentreceipt', true); ?>"><br><br>
    
    <label for="msdn">MSDN</label><br>
    <input readonly type="text" name="msdn" id="msdn" class="widefat" value="<?php echo get_post_meta($post->ID,'msdn', true); ?>"><br><br>

    <label for="amount">Amount</label><br>
    <input readonly type="text" name="amount" id="amount" class="widefat" value="<?php echo get_post_meta($post->ID,'amount', true); ?>"><br><br>

    <label for="transactionreference">Transaction Reference</label><br>
    <input readonly type="text" name="transactionreference" id="transactionreference" class="widefat" value="<?php echo get_post_meta($post->ID,'transactionreference', true); ?>"><br><br>

    <label for="transactionid">Transaction ID</label><br>
    <input readonly type="text" name="transactionid" id="transactionid" class="widefat" value="<?php echo get_post_meta($post->ID,'transactionid', true); ?>"><br><br>
    <?php
}

function uchatpay_custom_columns_list( $columns ) {
     
    unset( $columns['author'] );

    $columns['apipaymentreceipt'] = 'API Payment';
    $columns['orderID']         = 'Order ID';
	$columns['msdn']          = 'MSDN';
	$columns['amount']        = 'Amount';
     
    return $columns;
}

add_filter( 'manage_uchatpayments_posts_columns', 'uchatpay_custom_columns_list' );
add_filter( 'manage_uchatpayments_posts_custom_column', 'uchatpay_add_custom_column_data', 10, 2 );

function uchatpay_add_custom_column_data( $column, $post_id ) {
    switch ( $column ) {
        case 'apipaymentreceipt' :
            echo get_post_meta( $post_id, 'apipaymentreceipt', true );
            break;
        case 'msdn' :
            echo get_post_meta( $post_id, 'msdn', true );
            break;
        case 'amount' :
            echo get_post_meta( $post_id, 'amount', true );
            break;
        case 'orderID' :
            $order_id = get_post_meta( $post_id, 'orderID', true );
            echo '<a href="' . admin_url() . 'post.php?post=' . $order_id . '&action=edit">Order #' . $order_id . '</a>';
            break;
    }
}
