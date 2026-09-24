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
 * 8. Extend the WooCommerce order-attribution cookie lifetime.
 *
 * WooCommerce's OWN default (Automattic\WooCommerce\Internal\Orders\
 * OrderAttributionController, since 8.5.0) is 0.00001 months (~26 seconds):
 *
 *     $lifetime = (float) apply_filters( 'wc_order_attribution_cookie_lifetime_months', 0.00001 );
 *
 * That value deliberately makes the sourcebuster attribution cookies last only
 * for the current session, so by the time an order is placed the click source is
 * usually gone and the order records as "Direct" — under-crediting every paid
 * channel. We extend it to 6 months via WooCommerce's official filter, which also
 * makes the localised `wc_order_attribution.params.lifetime` render as 6 at the
 * source (no JS override needed).
 *
 * 6-month cookies are fine for consent: the consent integration still controls
 * `allowTracking`, which gates whether sourcebuster runs at all.
 *
 * Tune via pt_order_attribution_lifetime_months (return 0 to leave WooCommerce's
 * session-only default in place).
 */
function pt_order_attribution_cookie_lifetime( $months ) {
    $ours = (float) apply_filters( 'pt_order_attribution_lifetime_months', 6 );
    return $ours > 0 ? $ours : $months;
}
add_filter( 'wc_order_attribution_cookie_lifetime_months', 'pt_order_attribution_cookie_lifetime' );

/**
 * 9. Ad click IDs (gclid, gbraid, hsa_*, mh_* …) → saved on the order.
 *
 * WooCommerce order attribution (and the session capture in section 1 above) keep
 * the SOURCE and utm_* only — never the click IDs themselves. WooCommerce reads
 * gclid merely to guess "google / cpc" when UTMs are missing; it stores nothing.
 * And any param that lives only on a cached landing page, or that a redirect
 * strips before /checkout/, never reaches PHP at all.
 *
 * So capture the IDs CLIENT-SIDE from the landing URL into a first-party cookie —
 * JS runs even on a fully edge-cached page — then read that cookie when the order
 * is created. The cookie persists across add-to-cart and checkout, so the click
 * that started the visit survives even though the URL no longer carries it.
 *
 * Consent: gated on the same `allowTracking` signal WooCommerce order attribution
 * uses (which reflects the WP Consent API / consent banner). Values are saved as
 * PUBLIC `pt_<key>` meta so they appear in the WooCommerce REST API `meta_data`
 * (n8n etc. can read them) — an underscore-only key would be hidden — plus a
 * private `_pt_<key>` mirror. Uses the first value of each param, which de-dupes
 * a doubled tracking template (?gclid=x&gclid=x). Filter the key list with
 * pt_click_id_keys.
 */
function pt_click_id_keys() {
    return apply_filters( 'pt_click_id_keys', array(
        'gclid', 'gbraid', 'wbraid', 'gad_source', 'gad_campaignid', 'fbclid', 'msclkid', 'ttclid',
        'mh_campaignid', 'mh_adgroupid', 'mh_keyword', 'mh_matchtype', 'mh_network',
        'hsa_acc', 'hsa_cam', 'hsa_grp', 'hsa_ad', 'hsa_src', 'hsa_tgt', 'hsa_kw', 'hsa_mt', 'hsa_net', 'hsa_ver',
    ) );
}

// Client-side capture into a first-party cookie (runs even on cached pages).
function pt_click_ids_capture_script() {
    if ( is_admin() ) {
        return;
    }
    $keys = wp_json_encode( array_values( pt_click_id_keys() ) );
    ?>
<script>
(function () {
  try {
    var wca = window.wc_order_attribution;
    if (wca && wca.params && wca.params.allowTracking === false) return; // respect marketing consent
  } catch (e) {}
  var KEYS = <?php echo $keys; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_json_encode ?>;
  var p, d = {}, hit = false;
  try { p = new URLSearchParams(location.search); } catch (e) { return; }
  KEYS.forEach(function (k) { var v = p.get(k); if (v) { d[k] = String(v).slice(0, 255); hit = true; } }); // get() = first value → de-dupes
  if (!hit) return;                       // no new click on this page → keep the existing cookie
  d.landing = location.pathname;
  d.ts = new Date().toISOString();
  try {
    document.cookie = 'pt_click=' + encodeURIComponent(JSON.stringify(d)) +
      '; path=/; max-age=' + (60 * 60 * 24 * 90) + '; SameSite=Lax; Secure';
  } catch (e) {}
})();
</script>
    <?php
}
add_action( 'wp_footer', 'pt_click_ids_capture_script', 99 );

// Save the captured click IDs onto the order (public + private meta).
function pt_save_click_ids_to_order( $order ) {
    if ( empty( $_COOKIE['pt_click'] ) ) {
        return;
    }
    $data = json_decode( wp_unslash( $_COOKIE['pt_click'] ), true ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- values validated + sanitized below
    if ( ! is_array( $data ) ) {
        return;
    }
    $allowed = array_flip( pt_click_id_keys() );
    foreach ( $data as $k => $v ) {
        if ( ! is_string( $k ) || ! isset( $allowed[ $k ] ) || ! is_scalar( $v ) ) {
            continue;
        }
        $val = sanitize_text_field( (string) $v );
        if ( '' === $val ) {
            continue;
        }
        $order->update_meta_data( 'pt_' . $k, $val );   // public → visible in REST meta_data
        $order->update_meta_data( '_pt_' . $k, $val );  // private mirror
    }
    if ( ! empty( $data['landing'] ) && is_string( $data['landing'] ) ) {
        $order->update_meta_data( '_pt_landing', sanitize_text_field( $data['landing'] ) );
    }
}
add_action( 'woocommerce_checkout_create_order', 'pt_save_click_ids_to_order', 20, 1 );                       // classic checkout
add_action( 'woocommerce_store_api_checkout_update_order_from_request', 'pt_save_click_ids_to_order', 20, 1 ); // block checkout

// Admin order screen: show the captured click IDs under the billing address.
function pt_display_click_ids_admin( $order ) {
    $rows = '';
    foreach ( pt_click_id_keys() as $k ) {
        $v = $order->get_meta( 'pt_' . $k );
        if ( '' !== $v && null !== $v ) {
            $rows .= '<p style="margin:0 0 4px;"><strong>' . esc_html( $k ) . ':</strong> ' . esc_html( $v ) . '</p>';
        }
    }
    if ( '' !== $rows ) {
        echo '<div style="margin-top:8px;"><p style="margin:0 0 6px;"><strong>Ad click IDs</strong></p>' . $rows . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped per row above
    }
}
add_action( 'woocommerce_admin_order_data_after_billing_address', 'pt_display_click_ids_admin', 20, 1 );