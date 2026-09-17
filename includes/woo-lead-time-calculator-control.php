<?php
/* ======================================================
 * 1. DATE LISTS
 * ====================================================== */

// Dates skipped when counting business days for lead time (e.g. factory closures)
function pt_get_lead_time_excluded_dates() {
    return [
        '2026-05-18',
        '2026-05-20',
    ];
}

// Dates blocked in the datepicker — customers cannot select these for delivery
function pt_get_blackout_dates() {
    return [
      
    ];
}

/* ======================================================
 * 2. CORE: CONVERT BUSINESS DAYS → REAL DATE
 * (single engine used by both product page + checkout)
 * ====================================================== */
// $extra_excluded: additional dates to skip during counting (e.g. blackout dates for standard products)
// Fast delivery passes nothing; standard products pass pt_get_blackout_dates()
function pt_date_from_business_days($days, $extra_excluded = []) {

    $tz      = new DateTimeZone('Europe/London');
    $now     = new DateTime('now', $tz);
    $skipped = array_merge(pt_get_lead_time_excluded_dates(), $extra_excluded);

    $dow_today = (int)$now->format('N');
    $ymd_today = $now->format('Y-m-d');

    // Block Monday delivery if order placed on weekend
    if ($dow_today === 6 || $dow_today === 7) {
        $days += 1;
    }

    $today_is_valid = $dow_today >= 1
                   && $dow_today <= 5
                   && !in_array($ymd_today, $skipped, true);

    // Reset to start of day so the returned date is always clean
    $date = clone $now;
    $date->setTime(0, 0, 0);

    $counted = $today_is_valid ? 1 : 0;

    if ($today_is_valid && $days === 1) {
        return $date;
    }

    while ($counted < $days) {
        $date->modify('+1 day');

        $dow = (int)$date->format('N');
        $ymd = $date->format('Y-m-d');

        if ($dow >= 1 && $dow <= 5 && !in_array($ymd, $skipped, true)) {
            $counted++;
        }
    }

    return $date;
}

/* ======================================================
 * 3. PRODUCT PAGE: DELIVERY DATE DISPLAY
 * ====================================================== */
function pt_delivery_date_calculator($product_id = null) {

    // Fast delivery: blackout dates do not apply — only holidays + weekends
    if ($product_id && get_field('include_fast_delivery', $product_id)) {
        $fast_days = (int) get_field('fast_delivery_days', $product_id) ?: 3;
        return pt_date_from_business_days($fast_days);
    }

    if ($product_id && trim(get_field('delivery_time', $product_id)) !== '') {
        $days = (int) get_field('delivery_time', $product_id);
    } else {
        $days = (int) get_field('global_delivery_days', 'option');
    }

    if ($days <= 0) {
        return new DateTime('now', new DateTimeZone('Europe/London'));
    }

    return pt_date_from_business_days($days, pt_get_blackout_dates());
}

/* ======================================================
 * 4. CHECKOUT: MIN PICKUP DATE
 * ====================================================== */
function pt_calculate_pickup_date() {

    $base_days = (int) (get_field('global_delivery_days', 'option') ?: 1);

    // // Cutoff at 11:55 — uncomment to re-enable
    // $tz  = new DateTimeZone('Europe/London');
    // $now = new DateTime('now', $tz);
    // $cutoff_minutes = (11 * 60) + 55;
    // $now_minutes    = ((int)$now->format('H') * 60) + (int)$now->format('i');
    // if ($now_minutes > $cutoff_minutes) $base_days++;

    return pt_date_from_business_days($base_days, pt_get_blackout_dates());
}

/* ======================================================
 * 5. CART: CHECK ASSEMBLY SERVICE (SIMPLE + COMPOSITE)
 * ====================================================== */
function pt_cart_has_assembly_service() {

    if (!WC()->cart) return false;

    $cart = WC()->cart->get_cart();

    foreach ($cart as $cart_item) {

        if (
            isset($cart_item['data']) &&
            stripos($cart_item['data']->get_name(), 'building assembly service') !== false
        ) {
            return true;
        }

        if (
            isset($cart_item['composite_children']) &&
            is_array($cart_item['composite_children'])
        ) {
            foreach ($cart_item['composite_children'] as $child_key) {
                if (isset($cart[$child_key]['data'])) {
                    $child = $cart[$child_key]['data'];
                    if (stripos($child->get_name(), 'building assembly service') !== false) {
                        return true;
                    }
                }
            }
        }
    }

    return false;
}

/* ======================================================
 * 6. CART: GET MAX PRODUCT DELIVERY DAYS
 * ====================================================== */

// Each resolver returns ['days' => int, 'from_size' => bool].
// from_size = true means the days came from a size product (N x N title),
// which bypasses pt_advance_past_blackout — only weekends + lead_time_excluded_dates apply.

// Composite products resolved as a unit: size child → parent → 0
// from_size (fast delivery) is read from the SIZE child — the include_fast_delivery
// flag lives on the size product, NOT the parent.
function pt_resolve_composite_delivery_days($cart_item, $cart) {

    $parent_id   = $cart_item['data']->get_id();
    $parent_days = (int) get_field('delivery_time', $parent_id);

    // Lead time may be set on the size child, the parent, or both.
    $size_days = 0;
    $size_fast = false;
    foreach ($cart_item['composite_children'] as $child_key) {
        if (!isset($cart[$child_key]['data'])) continue;
        $child = $cart[$child_key]['data'];
        if (preg_match('/^\d+\s*x\s*\d+$/i', trim($child->get_title()))) {
            $size_days = (int) get_field('delivery_time', $child->get_id());
            $size_fast = (bool) get_field('include_fast_delivery', $child->get_id()); // per-SIZE flag
            break;
        }
    }

    // Consider BOTH values — the greater lead time paces the item.
    $days = max($size_days, $parent_days);

    // No specific lead time on this building → fall back to the global default
    // (the standard lead time). This is a building, so it must still pace the order
    // (never 0). Not fast.
    if ($days <= 0) {
        return ['days' => (int) get_field('global_delivery_days', 'option'), 'from_size' => false];
    }

    // Fast (48h) only when the size is ticked AND its own lead time is the one that
    // applies (the parent doesn't impose a longer one).
    $from_size = $size_fast && $size_days > 0 && $size_days >= $parent_days;
    return ['days' => $days, 'from_size' => $from_size];
}

// Simple/variable products: days from the product's delivery_time; from_size (fast
// delivery) requires its own include_fast_delivery tick.
function pt_resolve_delivery_days($product) {
    $days = (int) get_field('delivery_time', $product->get_id());
    if ($days <= 0) return ['days' => 0, 'from_size' => false];
    $is_fast = (bool) get_field('include_fast_delivery', $product->get_id());
    return ['days' => $days, 'from_size' => $is_fast];
}

// Returns ['days' => int, 'from_size' => bool] or null.
// from_size is only true if ALL products in the cart are fast-delivery eligible.
// A single non-fast product causes blackout dates to apply for the whole checkout.
function pt_get_product_delivery_days_from_cart() {
    if (!WC()->cart) return null;

    $cart      = WC()->cart->get_cart();
    $max_days  = null;
    $all_fast  = true;

    foreach ($cart as $cart_item) {
        if (!isset($cart_item['data'])) continue;
        if (isset($cart_item['composite_parent'])) continue; // already handled via the parent item

        if (isset($cart_item['composite_children']) && is_array($cart_item['composite_children'])) {
            $result = pt_resolve_composite_delivery_days($cart_item, $cart);
        } else {
            $result = pt_resolve_delivery_days($cart_item['data']);
        }

        $is_composite = isset($cart_item['composite_children']) && is_array($cart_item['composite_children']);

        if ($result['days'] > 0) {
            $max_days = is_null($max_days) ? $result['days'] : max($max_days, $result['days']);
        }

        // Composite products always vote on all_fast even if days=0 (size with no delivery_time set)
        // Non-composite products with days=0 have no delivery config and are ignored
        if ($result['days'] > 0 || $is_composite) {
            if (!$result['from_size']) {
                $all_fast = false;
            }
        }
    }

    return is_null($max_days) ? null : ['days' => $max_days, 'from_size' => $all_fast];
}
/* ======================================================
 * 7. FINAL MIN PICKUP DATE (ORDER OF PRECEDENCE)
 * ====================================================== */

/**
 * Extra working days added to the lead time for surcharge delivery zones.
 * Zone C (Scottish Highlands & Islands) = +5 working days. The zone is read from
 * the WooCommerce shipping zone that matches the customer's shipping package, so
 * it follows whatever postcodes are configured under "UK | Zone C" in
 * WooCommerce → Shipping. Filter `pt_delivery_zone_extra_days` to change the
 * amount or add other zones.
 *
 * @return int Extra working days (0 when none apply).
 */
function pt_delivery_zone_extra_days() {
    $extra = 0;

    if ( function_exists('WC') && WC() && WC()->cart && class_exists('WC_Shipping_Zones') ) {
        $packages = WC()->cart->get_shipping_packages();
        if ( ! empty($packages) ) {
            $zone = WC_Shipping_Zones::get_zone_matching_package( reset($packages) );
            if ( $zone ) {
                $name = (string) $zone->get_zone_name();
                // "UK | Zone C" etc. — match the "Zone C" token, case-insensitive.
                if ( preg_match('/zone\s*c\b/i', $name) ) {
                    $extra = 5;
                }
                $extra = (int) apply_filters('pt_delivery_zone_extra_days', $extra, $zone, $name);
            }
        }
    }

    return max(0, $extra);
}

function pt_get_min_pickup_date() {

    $result = pt_get_product_delivery_days_from_cart();
    $extra  = pt_delivery_zone_extra_days();

    // Resolve the base business-day count and whether it's a fast (from_size) lead time.
    if (pt_cart_has_assembly_service()) {
        // 1️⃣ Assembly service: always 35 business days, or the product days if higher.
        $days      = is_null($result) ? 35 : max($result['days'], 35);
        $from_size = false;
    } elseif (!is_null($result)) {
        // 2️⃣ Product-level delivery days (MAX across cart).
        $days      = (int) $result['days'];
        $from_size = (bool) $result['from_size'];
    } else {
        // 3️⃣ Global cutoff fallback.
        $days      = (int) (get_field('global_delivery_days', 'option') ?: 1);
        $from_size = false;
    }

    // Surcharge zones (e.g. Highlands & Islands) add working days and cancel the
    // 48h fast promise — those areas can't be reached in 48 hours.
    if ($extra > 0) {
        $days      += $extra;
        $from_size  = false;
    }

    // Fast keeps blackout dates out of the count; everything else respects them.
    $blackout = $from_size ? [] : pt_get_blackout_dates();

    return [
        'date'       => pt_date_from_business_days($days, $blackout),
        'from_size'  => $from_size,
        'extra_days' => $extra,
    ];
}

/* ======================================================
 * 8. CHECKOUT FIELD + DATEPICKER
 * ====================================================== */
/**
 * Size product IDs eligible for the "48h" pill on the configurator card. A size
 * qualifies when it has "Include fast delivery" ticked AND its own delivery_time is
 * set and isn't overridden by a longer parent lead time — the same rule the checkout
 * uses for from_size. (product.js reads the injected window.PT_FAST_SIZES.)
 */
function pt_fast_delivery_size_ids( $product_id ) {
    $out = array();
    if ( ! function_exists( 'get_field' ) ) return $out;
    $product = function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : null;
    if ( ! $product ) return $out;
    $parent_days = (int) get_field( 'delivery_time', $product_id );
    if ( $product->is_type( 'composite' ) && is_callable( array( $product, 'get_components' ) ) ) {
        foreach ( (array) $product->get_components() as $component ) {
            $title = ( is_object( $component ) && is_callable( array( $component, 'get_title' ) ) ) ? strtolower( trim( (string) $component->get_title() ) ) : '';
            if ( 'size' !== $title ) continue;
            $opts = is_callable( array( $component, 'get_options' ) ) ? (array) $component->get_options() : array();
            foreach ( $opts as $oid ) {
                $sd = (int) get_field( 'delivery_time', (int) $oid );
                if ( (bool) get_field( 'include_fast_delivery', (int) $oid ) && $sd > 0 && $sd >= $parent_days ) {
                    $out[] = (int) $oid;
                }
            }
        }
    } elseif ( (bool) get_field( 'include_fast_delivery', $product_id ) && $parent_days > 0 ) {
        $out[] = (int) $product_id;
    }
    return array_values( array_unique( $out ) );
}

add_action('woocommerce_after_order_notes', 'pt_render_pickup_date_field');
function pt_render_pickup_date_field($checkout) {
    echo pt_pickup_date_field_html( $checkout ? $checkout->get_value('order_pickup_date') : '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML built + escaped in helper.
}

/**
 * Re-render the pickup-date field on every AJAX order-review refresh, so its
 * earliest selectable date always reflects the current postcode's delivery zone
 * (Zone C adds working days). WooCommerce replaces the matching selector in the
 * DOM; pt_pickup_datepicker_script() re-inits the datepicker on updated_checkout.
 */
add_filter('woocommerce_update_order_review_fragments', 'pt_pickup_date_field_fragment');
function pt_pickup_date_field_fragment($fragments) {
    $selected = '';
    if ( isset($_POST['post_data']) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- read-only, WooCommerce's own AJAX payload.
        parse_str( wp_unslash($_POST['post_data']), $pd ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
        if ( ! empty($pd['order_pickup_date']) ) {
            $selected = wc_clean($pd['order_pickup_date']);
        }
    }
    $fragments['#order_pickup_date_field'] = pt_pickup_date_field_html($selected);
    return $fragments;
}

/**
 * Markup for the checkout "Preferred delivery date" field, returned as a string
 * so it can be echoed on first render AND served as an AJAX fragment. Carries no
 * <script> of its own — the datepicker is initialised from the data-pt-*
 * attributes by pt_pickup_datepicker_script().
 *
 * @param string $selected Currently chosen date value to preserve across refreshes.
 * @return string
 */
function pt_pickup_date_field_html($selected = '') {

    $pickup         = pt_get_min_pickup_date();
    $min_date       = $pickup['date'];
    $extra_days     = (int) $pickup['extra_days'];
    // Size-driven lead times: only grey out lead_time_excluded_dates + weekends, not blackout dates
    $disabled_dates = $pickup['from_size']
        ? pt_get_lead_time_excluded_dates()
        : pt_get_blackout_dates();

    $min_date_js = $min_date->format('Y-m-d');
    $holidays_js = wp_json_encode( array_values( $disabled_dates ) );

    ob_start();

    echo '<div id="order_pickup_date_field">';
    echo '<h3>' . esc_html__( 'Delivery', 'woocommerce' ) . '</h3>';
    echo '<p class="pt-delivery-hint">' . esc_html__( "Pick a preferred date — we'll confirm the final delivery window with you.", 'woocommerce' ) . '</p>';

    woocommerce_form_field('order_pickup_date', [
        'type'        => 'text',
        'required'    => true,
        'label'       => __( 'Preferred delivery date', 'woocommerce' ),
        'class'       => ['form-row-wide'],
        'id'          => 'datepicker',
        'autocomplete'=> 'off',
        'custom_attributes' => [
            'readonly'         => 'readonly',
            'data-pt-min'      => $min_date_js,
            'data-pt-holidays' => $holidays_js,
        ],
        'placeholder' => 'Choose your preferred date',
    ], $selected);

    // 48h fast-delivery line below the calendar — only when the resolved order lead
    // time is itself a fast (from_size) one. If any item's lead time (size OR parent)
    // is longer, from_size is false: the calendar's min date reflects that greater
    // lead time and no fast message is shown.
    if ( ! empty( $pickup['from_size'] ) ) {
        $fast_min = esc_html( $min_date->format( 'D j M' ) ); // e.g. "Mon 18 Sep"
        echo '<div class="co-fastline"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M13 2 4 14h6l-1 8 9-12h-6z"/></svg>'
            . '<span class="co-fastline-t"><b>' . esc_html__( 'Dispatched from 48 hours · in stock at Parry Works', 'woocommerce' ) . '</b>'
            . '<small>' . sprintf(
                /* translators: %s is the earliest delivery date, e.g. "Mon 18 Sep". */
                esc_html__( 'Choose the earliest date (%s) or any date after — order by 12pm.', 'woocommerce' ),
                $fast_min
            ) . '</small></span></div>';
    }

    // Surcharge-zone note (Scottish Highlands & Islands = Zone C): the earliest date
    // already includes the extra working days; explain why to the customer.
    if ( $extra_days > 0 ) {
        $zone_min = esc_html( $min_date->format( 'D j M' ) ); // e.g. "Mon 25 Sep"
        echo '<div class="co-zoneline"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s-7-5.2-7-11a7 7 0 0 1 14 0c0 5.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>'
            . '<span class="co-zoneline-t"><b>' . esc_html(
                sprintf(
                    /* translators: %d is the number of extra working days. */
                    _n( 'Highlands & Islands delivery · %d extra working day', 'Highlands & Islands delivery · %d extra working days', $extra_days, 'woocommerce' ),
                    $extra_days
                )
            ) . '</b>'
            . '<small>' . sprintf(
                /* translators: %s is the earliest delivery date, e.g. "Mon 25 Sep". */
                esc_html__( 'Deliveries to your area take a little longer — the earliest date (%s) already includes this.', 'woocommerce' ),
                $zone_min
            ) . '</small></span></div>';
    }

    echo '</div>';

    return ob_get_clean();
}

/**
 * Initialise — and re-initialise after each AJAX order-review update — the
 * jQuery UI datepicker from the field's data-pt-* attributes. Centralising it
 * here (instead of an inline script inside the fragment) keeps a single source
 * of truth for the calendar's min date and greyed-out days.
 */
add_action('wp_footer', 'pt_pickup_datepicker_script');
function pt_pickup_datepicker_script() {
    if ( ! function_exists('is_checkout') || ! is_checkout() ) {
        return;
    }
    ?>
<script>
(function () {
    function ptInitPicker() {
        if (!window.jQuery) return;
        var $ = jQuery, $p = $('#datepicker');
        if (!$p.length || typeof $p.datepicker !== 'function') return;

        var min = $p.attr('data-pt-min');
        if (!min) return;

        var holidays = [];
        try { holidays = JSON.parse($p.attr('data-pt-holidays') || '[]'); } catch (e) {}

        var minDate = new Date(min + 'T00:00:00');
        function disableHoliday(date) {
            var ymd = $.datepicker.formatDate('yy-mm-dd', date);
            var day = date.getDay();
            return [day !== 0 && day !== 6 && holidays.indexOf(ymd) === -1];
        }

        if ($p.hasClass('hasDatepicker')) {
            $p.datepicker('option', { minDate: minDate, defaultDate: minDate, beforeShowDay: disableHoliday });
        } else {
            $p.datepicker({ minDate: minDate, defaultDate: minDate, beforeShowDay: disableHoliday, showButtonPanel: true });
        }

        // Drop a previously chosen date that is now earlier than the new minimum.
        var val = $p.val();
        if (val) {
            var d = null;
            try { d = $.datepicker.parseDate($p.datepicker('option', 'dateFormat'), val); } catch (e) {}
            if (d && d < minDate) { $p.val(''); }
        }
    }

    if (window.jQuery) {
        jQuery(function () { ptInitPicker(); });
        jQuery(document.body).on('updated_checkout', ptInitPicker);
    }
})();
</script>
    <?php
}
// function svg_icon_package() {
//     return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
//         <path d="M12 3l8 4.5v9L12 21l-8-4.5v-9L12 3z"/>
//         <line x1="12" y1="12" x2="12" y2="21"/>
//         <path d="M12 12L4 7.5"/>
//         <path d="M12 12l8-4.5"/>
//         <path d="M8 5.25l8 4.5"/>
//     </svg>';
// }


// function delivery_message_checkout_page() {
//     echo '
//     <div class="delivery-notice" role="status" aria-label="Delivery information">
//         <div class="notice-icon">
//           <img src="https://www.projecttimber.com/wp-content/uploads/2026/05/Delivery-infoV2.svg"
//                 width="36"
//                 height="36"
//                  alt="Delivery"
//                  style="display:block; border:0; outline:none; text-decoration:none; margin: 0;">
//         </div>
//         <div class="notice-text">
//             <div class="notice-title">
//                 Delivery information
//             </div>
//             <p class="notice-body">
//                 We\'re so grateful for the incredible demand! All orders are being
//                 <strong>freshly made just for you</strong>. Expect delivery within
//                 <strong>4 to 5 weeks</strong> — we will contact you nearer the time to book an exact day that is convenient for you.
//             </p>
//         </div>
//     </div>';
// }

// // 3. Hook it into the checkout order review — above the subtotal
// add_action( 'woocommerce_after_order_notes', 'delivery_message_checkout_page' );

// /**
//  * Add an order note flagging the special lead time on order creation.
//  */
// add_action('woocommerce_checkout_order_processed', 'pt_add_lead_time_order_note', 20, 1);
// function pt_add_lead_time_order_note($order_id) {
//     if (!$order_id) return;

//     $order = wc_get_order($order_id);
//     if (!$order) return;

//     $order->add_order_note(
//         'Order with special lead time 10–25 business days.',
//         false, // false = private/internal note (true = customer-facing)
//         false  // false = system-added (not "by user")
//     );
// }