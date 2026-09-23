<?php
function tracking_capture_to_session() {

    if (!function_exists('WC') || !WC()->session) {
        return;
    }

    $tracking_params = array(
        'gclid',
        'gbraid',
        'wbraid',
        'fbclid',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
    );

    foreach ($tracking_params as $param) {
        if (isset($_GET[$param]) && $_GET[$param] !== '') {
            WC()->session->set($param, sanitize_text_field(wp_unslash($_GET[$param])));
        }
    }
}

add_action('woocommerce_init', 'tracking_capture_to_session');

// 2. Save tracking params into order meta when order is created
function tracking_add_to_order_meta( $order ) {
    $tracking_params = array(
        'gclid',
        'gbraid',
        'wbraid',
        'fbclid',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
    );

    if ( function_exists( 'WC' ) && WC()->session ) {
        foreach ( $tracking_params as $param ) {
            $value = WC()->session->get( $param );

            if ( ! empty( $value ) ) {
                // Private/internal order meta
                $order->update_meta_data( '_' . $param, $value );

                // Public order meta so integrations can discover it more easily
                $order->update_meta_data( $param, $value );
            }
        }
    }
}
add_action( 'woocommerce_checkout_create_order', 'tracking_add_to_order_meta', 10, 1 );

// 4. Display tracking params in WooCommerce Admin Order Page
function tracking_display_in_admin_order_meta( $order ) {
    $display_params = array(
        'gclid'        => 'Google Click ID (GCLID)',
        'gbraid'       => 'Google GBRAID',
        'wbraid'       => 'Google WBRAID',
        'fbclid'       => 'Facebook Click ID (FBCLID)',
        'utm_source'   => 'UTM Source',
        'utm_medium'   => 'UTM Medium',
        'utm_campaign' => 'UTM Campaign',
        'utm_term'     => 'UTM Term',
    );

    foreach ( $display_params as $key => $label ) {
        $value = $order->get_meta( '_' . $key );

        if ( ! empty( $value ) ) {
            echo '<p><strong>' . esc_html( $label ) . ':</strong></p>';
            echo '<textarea readonly style="width:100%;min-height:40px;">' . esc_textarea( $value ) . '</textarea>';
        }
    }
}
add_action( 'woocommerce_admin_order_data_after_billing_address', 'tracking_display_in_admin_order_meta', 10, 1 );

// 5. Add tracking params to admin emails only (not customer)
function tracking_add_to_admin_email( $order, $sent_to_admin, $plain_text, $email ) {
    if ( ! $sent_to_admin ) {
        return;
    }

    $email_params = array(
        'gclid'        => 'Google Click ID (GCLID)',
        'gbraid'       => 'Google GBRAID',
        'wbraid'       => 'Google WBRAID',
        'fbclid'       => 'Facebook Click ID (FBCLID)',
        'utm_source'   => 'UTM Source',
        'utm_medium'   => 'UTM Medium',
        'utm_campaign' => 'UTM Campaign',
        'utm_term'     => 'UTM Term',
    );

    // Collect the non-empty rows first so we only render the wrapper when there
    // is something to show.
    $rows = array();
    foreach ( $email_params as $key => $label ) {
        $value = $order->get_meta( '_' . $key );
        if ( ! empty( $value ) ) {
            $rows[ $label ] = $value;
        }
    }

    if ( empty( $rows ) ) {
        return;
    }

    if ( $plain_text ) {
        foreach ( $rows as $label => $value ) {
            echo $label . ': ' . $value . "\n";
        }
        return;
    }

    // Inset the block by 24px so it lines up with the order-details and address
    // cards (which carry an extra padding: 0 24px on top of the body base).
    echo '<div style="padding: 0 24px;">';
    foreach ( $rows as $label => $value ) {
        echo '<p style="margin: 0 0 8px;"><strong>' . esc_html( $label ) . ':</strong> ' . esc_html( $value ) . '</p>';
    }
    echo '</div>';
}
add_action( 'woocommerce_email_after_order_table', 'tracking_add_to_admin_email', 10, 4 );

// 6. Expose tracking params in WooCommerce REST API orders
function add_tracking_fields_to_rest_order( $response, $order, $request ) {
    $tracking_keys = array(
        'gclid',
        'gbraid',
        'wbraid',
        'fbclid',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
    );

    $data = $response->get_data();

    $meta_lookup = array();

    if ( ! empty( $data['meta_data'] ) && is_array( $data['meta_data'] ) ) {
        foreach ( $data['meta_data'] as $meta ) {
            if ( isset( $meta->key ) ) {
                $meta_lookup[ $meta->key ] = isset( $meta->value ) ? $meta->value : '';
            } elseif ( is_array( $meta ) && isset( $meta['key'] ) ) {
                $meta_lookup[ $meta['key'] ] = isset( $meta['value'] ) ? $meta['value'] : '';
            }
        }
    }

    foreach ( $tracking_keys as $key ) {
        if ( ! empty( $meta_lookup[ $key ] ) ) {
            $data[ $key ] = $meta_lookup[ $key ];
        } elseif ( ! empty( $meta_lookup[ '_' . $key ] ) ) {
            $data[ $key ] = $meta_lookup[ '_' . $key ];
        }
    }

    $response->set_data( $data );

    return $response;
}
add_filter( 'woocommerce_rest_prepare_shop_order_object', 'add_tracking_fields_to_rest_order', 20, 3 );

function add_tracking_to_webhook_payload( $payload, $resource, $resource_id, $webhook ) {
    if ( 'order' !== $resource ) {
        return $payload;
    }

    $order = wc_get_order( $resource_id );

    if ( ! $order ) {
        return $payload;
    }

    $api_params = array(
        'gclid',
        'gbraid',
        'wbraid',
        'fbclid',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
    );

    foreach ( $api_params as $param ) {
        $value = $order->get_meta( '_' . $param );

        if ( ! empty( $value ) ) {
            $payload[ $param ] = $value;
        }
    }

    return $payload;
}
add_filter( 'woocommerce_webhook_payload', 'add_tracking_to_webhook_payload', 10, 4 );

/**
 * 8. Fix the WooCommerce order-attribution cookie lifetime.
 *
 * Something in production (a Code Snippets / WPCode entry or a production
 * mu-plugin — NOT this theme) localises `wc_order_attribution.params.lifetime`
 * as 0.00001 months (~26 seconds). The sourcebuster attribution cookie therefore
 * expires almost immediately, so by the time an order is placed the click source
 * is gone and the order is recorded as "Direct" — under-crediting every paid
 * channel.
 *
 * WooCommerce prints the localised `wc_order_attribution` object (the
 * `wc-order-attribution-js-extra` block) BEFORE any `before` inline script on the
 * same handle, and before sourcebuster initialises. So a `before` inline can
 * safely correct params.lifetime in time for sbjs to read the right value.
 *
 * This only changes a number that WooCommerce's own tracking uses; it does not
 * start any tracking of its own, so it inherits whatever consent gating already
 * governs order attribution. Default 6 months = WooCommerce's own default.
 * Filter: pt_order_attribution_lifetime_months (return 0 to disable this fix).
 */
function pt_fix_order_attribution_lifetime() {
    if ( is_admin() ) {
        return;
    }
    if ( ! wp_script_is( 'wc-order-attribution', 'registered' )
        && ! wp_script_is( 'wc-order-attribution', 'enqueued' ) ) {
        return;
    }

    $months = (float) apply_filters( 'pt_order_attribution_lifetime_months', 6 );
    if ( $months <= 0 ) {
        return;
    }

    $js = 'try{if(window.wc_order_attribution&&wc_order_attribution.params){'
        . 'wc_order_attribution.params.lifetime=' . wp_json_encode( $months ) . ';'
        . '}}catch(e){}';

    wp_add_inline_script( 'wc-order-attribution', $js, 'before' );
}
add_action( 'wp_enqueue_scripts', 'pt_fix_order_attribution_lifetime', 99 );