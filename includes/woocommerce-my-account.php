<?php 
add_filter('wp_nav_menu_objects', function ($items) {

    $parent_id = 196805; // your "My Account" parent item ID

    // If user is NOT logged in → remove parent + children
    if (!is_user_logged_in()) {

        foreach ($items as $key => $item) {

            // Remove PARENT
            if ($item->ID == $parent_id) {
                unset($items[$key]);
            }

            // Remove CHILDREN of that parent
            if ($item->menu_item_parent == $parent_id) {
                unset($items[$key]);
            }
        }
    }

    return $items;
});

/**
 * Add "Track Order" button to each order in My Account > Orders
 */
// add_filter( 'woocommerce_my_account_my_orders_actions', 'add_track_button_my_orders', 10, 2 );

// function add_track_button_my_orders( $actions, $order ) {

//         // Replace with your tracking page URL (can also add order ID as parameter)
//         $track_url = site_url( '/order-tracking/?order_id=' . $order->get_id() );

//         $actions['track'] = array(
//             'url'  => $track_url,
//             'name' => __( 'Track', 'woocommerce' ),
//         );

//     return $actions;
// }


// Remove specific My Account tabs
add_filter( 'woocommerce_account_menu_items', function( $items ) {

    // Remove "Downloads" tab
    unset( $items['downloads'] );

    // Remove "Payment Methods" tab
    unset( $items['payment-methods'] );

    return $items;
});

// Remove WooCommerce default Dashboard message
remove_action( 'woocommerce_account_dashboard', 'woocommerce_account_dashboard', 10 );

// Add custom Dashboard layout
add_action( 'woocommerce_account_dashboard', 'pt_custom_dashboard_with_saved_carts' );

function get_show_more_buttton(){
    $html ='
    <span class="open-order-summary">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="10" viewBox="0 0 18 10" fill="none">
            <g clip-path="url(#clip0_27_2229)">
                <path
                    d="M9.5502 7.76583C10.0264 8.20717 10.0071 8.96621 9.50916 9.38283C9.08889 9.73446 8.47166 9.71476 8.07467 9.33704L0.770513 2.3874C0.351312 1.98854 0.351311 1.32002 0.770513 0.921169C1.15754 0.552927 1.76415 0.548968 2.15595 0.912126L9.5502 7.76583Z"
                    fill="#3B333D" />
                <path
                    d="M15.6483 0.741838C16.05 0.331687 16.7078 0.323782 17.1193 0.724161C17.5352 1.12886 17.5403 1.79536 17.1306 2.20638L12.1564 7.19721C11.7686 7.58628 11.1412 7.5946 10.7432 7.21596C10.3345 6.82707 10.3231 6.17893 10.7178 5.77588L15.6483 0.741838Z"
                    fill="#3B333D" />
            </g>
            <defs>
                <clipPath id="clip0_27_2229">
                    <rect width="18" height="10" fill="white" />
                </clipPath>
            </defs>
        </svg>
    </span>';
    return $html;
}

function pt_custom_dashboard_with_saved_carts() {
    $user_id = get_current_user_id();

    /* ==============================
     *  LAST ORDER
     * ============================== */
    echo '<h2>Orders</h2>';

    $orders = wc_get_orders([
        'customer_id' => $user_id,
        'limit'       => 1,
        'orderby'     => 'date',
        'order'       => 'DESC',
    ]);
    $show_more_btn = get_show_more_buttton();
    if ( ! empty( $orders ) ) {
        $order = $orders[0];
        echo '<table class="shop_table shop_table_responsive my_account_orders">';
        echo '<thead><tr><th>Order</th><th>Date</th><th>Status</th><th>Total</th><th>Actions</th></tr></thead><tbody>';
        echo '<tr class="woocommerce-orders-table__row">';
        echo '<th data-title="Order" class="order main-row">#' . esc_html( $order->get_order_number() ) .   $show_more_btn .'
         
        </th>';
        echo '<td data-title="Date">' . esc_html( $order->get_date_created()->date_i18n( 'd/m/Y' ) ) . '</td>';
        echo '<td data-title="Status">' . esc_html( wc_get_order_status_name( $order->get_status() ) ) . '</td>';
        echo '<td data-title="Total">' . wp_kses_post( $order->get_formatted_order_total() ) . '</td>';
        echo '<td data-title="Actions"><a href="' . esc_url( $order->get_view_order_url() ) . '" class="button button-submit">View</a></td>';
        echo '</tr></tbody></table>';
    } else {
        echo '<p>No orders yet.</p>';
    }

    /* ==============================
     *  BILLING + SHIPPING
     * ============================== */
    $billing  = wc_get_account_formatted_address( 'billing' );
    $shipping = wc_get_account_formatted_address( 'shipping' );

    echo '<div class="u-columns woocommerce-Addresses col2-set addresses two-col-on-desk">';
    echo '<div class="u-column1 col-1 woocommerce-Address">';
    
    echo '<header><h2>Billing Address</h2></header>';
    echo '<address>';
    echo $billing ? wp_kses_post( $billing ) : '<p>You have not set up a billing address yet.</p>';
    echo '</address>';
    echo '</div>';

    echo '<div class="u-column2 col-2 woocommerce-Address">';
    echo '<header><h2>Shipping Address</h2></header>';
    echo '<address>';
    echo $shipping ? wp_kses_post( $shipping ) : '<p>You have not set up a shipping address yet.</p>';
    echo '</address>';
    echo '</div>';
    echo '</div>';

    /* ==============================
     *  SAVED CARTS
     * ============================== */
    echo '<h2>Saved Carts</h2>';

    $saved_carts = get_posts([
        'post_type'      => 'saved-carts',
        'post_status'    => 'publish',
        'numberposts'    => -1,
        'author'         => $user_id,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ]);
    $show_more_btn = get_show_more_buttton();

    if ( ! empty( $saved_carts ) ) {
        echo '<table class="shop_table shop_table_responsive my_account_saved_carts">';
        echo '<thead><tr><th>Cart ID</th><th>Cart Name</th><th>Date</th><th>Actions</th></tr></thead><tbody>';

        foreach ( $saved_carts as $cart ) {
            $cart_id   = $cart->ID;
            $cart_name = get_the_title( $cart_id );
            $cart_date = get_the_date( 'Y-m-d', $cart_id );
            $view_link = add_query_arg( 'share-cart', $cart_id, home_url() );

            echo '<tr class="woocommerce-orders-table__row">';
            echo '<th data-title="Cart ID" class="main-row">' . esc_html( $cart_id ) . $show_more_btn .'</th>';
            echo '<td data-title="Cart Name"> ' . esc_html( $cart_name ?: 'Untitled' ) . '</td>';
            echo '<td data-title="Date">' . esc_html( $cart_date ) . '</td>';
            echo '<td data-title="Actions">
            	<span class="wsc_remove_cart" title="Remove" data-cart_id="'.  esc_html( $cart_id ) .'">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18" fill="none">
                        <path d="M4.1027 4.51149V13.897C4.1026 14.165 4.15532 14.4304 4.25785 14.6781C4.36037 14.9257 4.51068 15.1507 4.70021 15.3402C4.88973 15.5298 5.11474 15.6801 5.36238 15.7826C5.61002 15.8851 5.87543 15.9378 6.14345 15.9377H11.857C12.398 15.9377 12.9169 15.7228 13.2995 15.3402C13.682 14.9577 13.897 14.4388 13.897 13.8977V4.51074M2.4707 4.51149H15.5297" stroke="#3B333D" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M6.55176 4.51125V3.2865C6.55156 3.12565 6.58311 2.96633 6.6446 2.81769C6.70609 2.66905 6.79631 2.53401 6.91008 2.4203C7.02386 2.30659 7.15896 2.21645 7.30764 2.15506C7.45632 2.09366 7.61565 2.06221 7.77651 2.0625H10.2245C10.3854 2.06221 10.5447 2.09366 10.6934 2.15506C10.8421 2.21645 10.9772 2.30659 11.0909 2.4203C11.2047 2.53401 11.2949 2.66905 11.3564 2.81769C11.4179 2.96633 11.4495 3.12565 11.4493 3.2865V4.51125M7.36851 12.744V8.66475M10.6333 12.744V8.66475" stroke="#3B333D" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    </span>
					<a href="'.esc_url( $view_link ) .'" title="Checkout">
                        <span class="wsc_retrieve_cart">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="18" viewBox="0 0 22 18" fill="none">
                            <path d="M2 2H3.91322C4.3399 2 4.71958 2.27073 4.85863 2.67412L5.72929 5.2M5.72929 5.2L8.07066 11.9925C8.20971 12.3959 8.58939 12.6667 9.01607 12.6667H15.7591C16.1722 12.6667 16.5427 12.4127 16.6917 12.0275L18.8068 6.56084C19.0604 5.90555 18.5768 5.2 17.8742 5.2H5.72929Z" stroke="#3B333D" stroke-linecap="round" stroke-linejoin="round"/>
                            <circle cx="13.8333" cy="15.3333" r="0.833333" stroke="#3B333D"/>
                            <circle cx="9.83333" cy="15.3333" r="0.833333" stroke="#3B333D"/>
                        </svg>
                        </span>
                    </a>
            </td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    } else {
        echo '<p>No saved carts found.</p>';
    }
}




/**
 * 1. Replace Saved Carts menu item URL
 */
add_filter( 'woocommerce_account_menu_items', function( $items ) {

    $new = [];

    foreach ( $items as $endpoint => $label ) {

        if ( $endpoint === 'wsc-share-cart' ) {
            // rename endpoint key so WooCommerce stops linking to the plugin url
            $new['saved-carts-link'] = $label;
        } else {
            $new[$endpoint] = $label;
        }
    }

    return $new;
});


add_filter( 'woocommerce_get_endpoint_url', function( $url, $endpoint ) {

    if ( $endpoint === 'saved-carts-link' ) {
        return home_url( '/my-account/my-saved-carts/' );
    }

    return $url;

}, 10, 2 );



/**
 * 2. Create new endpoint: /my-account/my-saved-carts/
 */
add_action( 'init', function() {
    add_rewrite_endpoint( 'my-saved-carts', EP_ROOT | EP_PAGES );
});



/**
 * 3. Redirect old endpoint to the new one
 */
add_action( 'template_redirect', function() {
    if ( is_wc_endpoint_url( 'wsc-share-cart' ) ) {
        wp_safe_redirect( home_url( '/my-account/my-saved-carts/' ) );
        exit;
    }
});



/**
 * 4. Render EXACT table structure (your version)
 */
add_action( 'woocommerce_account_my-saved-carts_endpoint', function() {

    $user_id = get_current_user_id();

    $saved_carts = get_posts([
        'post_type'      => 'saved-carts',
        'post_status'    => 'publish',
        'numberposts'    => -1,
        'meta_key'       => '_customer_user',
        'meta_value'     => $user_id,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ]);

    // If your theme/plugin defines the show more button, use it
    $show_more_btn = function_exists('get_show_more_buttton')
                     ? get_show_more_buttton()
                     : '';

    echo '<h2>Saved Carts</h2>';

    if ( empty( $saved_carts ) ) {
        echo '<p>No saved carts found.</p>';
        return;
    }

    echo '<table class="shop_table shop_table_responsive my_account_saved_carts">';
    echo '<thead><tr>
            <th>Cart ID</th>
            <th>Cart Name</th>
            <th>Date</th>
            <th>Actions</th>
          </tr></thead><tbody>';

    foreach ( $saved_carts as $cart ) {

        $cart_id   = $cart->ID;
        $cart_name = get_the_title( $cart_id );
        $cart_date = get_the_date( 'Y-m-d', $cart_id );
        $view_link = add_query_arg( 'share-cart', $cart_id, home_url() );

        echo '<tr class="woocommerce-orders-table__row">';

        echo '<th data-title="Cart ID" class="main-row">'
                . esc_html( $cart_id )
                . $show_more_btn .
             '</th>';

        echo '<td data-title="Cart Name">'
                . esc_html( $cart_name ?: 'Untitled' ) .
             '</td>';

        echo '<td data-title="Date">'
                . esc_html( $cart_date ) .
             '</td>';

        echo '<td data-title="Actions">

					<span class="wsc_remove_cart" title="Remove" data-cart_id="'.  esc_html( $cart_id ) .'">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18" fill="none">
                        <path d="M4.1027 4.51149V13.897C4.1026 14.165 4.15532 14.4304 4.25785 14.6781C4.36037 14.9257 4.51068 15.1507 4.70021 15.3402C4.88973 15.5298 5.11474 15.6801 5.36238 15.7826C5.61002 15.8851 5.87543 15.9378 6.14345 15.9377H11.857C12.398 15.9377 12.9169 15.7228 13.2995 15.3402C13.682 14.9577 13.897 14.4388 13.897 13.8977V4.51074M2.4707 4.51149H15.5297" stroke="#3B333D" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M6.55176 4.51125V3.2865C6.55156 3.12565 6.58311 2.96633 6.6446 2.81769C6.70609 2.66905 6.79631 2.53401 6.91008 2.4203C7.02386 2.30659 7.15896 2.21645 7.30764 2.15506C7.45632 2.09366 7.61565 2.06221 7.77651 2.0625H10.2245C10.3854 2.06221 10.5447 2.09366 10.6934 2.15506C10.8421 2.21645 10.9772 2.30659 11.0909 2.4203C11.2047 2.53401 11.2949 2.66905 11.3564 2.81769C11.4179 2.96633 11.4495 3.12565 11.4493 3.2865V4.51125M7.36851 12.744V8.66475M10.6333 12.744V8.66475" stroke="#3B333D" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    </span>
					<a href="'.esc_url( $view_link ) .'" title="Checkout">
                        <span class="wsc_retrieve_cart">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="18" viewBox="0 0 22 18" fill="none">
                            <path d="M2 2H3.91322C4.3399 2 4.71958 2.27073 4.85863 2.67412L5.72929 5.2M5.72929 5.2L8.07066 11.9925C8.20971 12.3959 8.58939 12.6667 9.01607 12.6667H15.7591C16.1722 12.6667 16.5427 12.4127 16.6917 12.0275L18.8068 6.56084C19.0604 5.90555 18.5768 5.2 17.8742 5.2H5.72929Z" stroke="#3B333D" stroke-linecap="round" stroke-linejoin="round"/>
                            <circle cx="13.8333" cy="15.3333" r="0.833333" stroke="#3B333D"/>
                            <circle cx="9.83333" cy="15.3333" r="0.833333" stroke="#3B333D"/>
                        </svg>
                        </span>
                    </a>
					</td>
              </td>';

        echo '</tr>';
    }

    echo '</tbody></table>';
});