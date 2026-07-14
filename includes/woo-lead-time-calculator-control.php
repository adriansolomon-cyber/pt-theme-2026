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
// from_size is only true if the parent also has include_fast_delivery enabled
function pt_resolve_composite_delivery_days($cart_item, $cart) {

    $parent_id = $cart_item['data']->get_id();
    $is_fast   = (bool) get_field('include_fast_delivery', $parent_id);

    foreach ($cart_item['composite_children'] as $child_key) {
        if (!isset($cart[$child_key]['data'])) continue;
        $child = $cart[$child_key]['data'];
        if (preg_match('/^\d+\s*x\s*\d+$/i', trim($child->get_title()))) {
            $days = (int) get_field('delivery_time', $child->get_id());
            if ($days > 0) return ['days' => $days, 'from_size' => $is_fast];
            break; // size found but empty — fall through to parent
        }
    }

    $days = (int) get_field('delivery_time', $parent_id);
    if ($days > 0) return ['days' => $days, 'from_size' => false];

    return ['days' => 0, 'from_size' => false];
}

// Simple/variable products: size-format title → ACF → product ACF
function pt_resolve_delivery_days($product) {
    $title = trim($product->get_title());

    if (preg_match('/^\d+\s*x\s*\d+$/i', $title)) {
        $days = (int) get_field('delivery_time', $product->get_id());
        if ($days > 0) return ['days' => $days, 'from_size' => true];
    }

    return ['days' => (int) get_field('delivery_time', $product->get_id()), 'from_size' => false];
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

function pt_get_min_pickup_date() {

    $result = pt_get_product_delivery_days_from_cart();

    // 1️⃣ Assembly service: always 35 business days, or max with product days if higher
    if (pt_cart_has_assembly_service()) {
        $days = is_null($result) ? 35 : max($result['days'], 35);
        return ['date' => pt_date_from_business_days($days, pt_get_blackout_dates()), 'from_size' => false];
    }

    // 2️⃣ Product-level delivery days (MAX across cart)
    if (!is_null($result)) {
        // Fast (from_size): skip blackout in count — only holidays + weekends apply
        // Standard: blackout dates are excluded from the business day count
        $blackout = $result['from_size'] ? [] : pt_get_blackout_dates();
        return ['date' => pt_date_from_business_days($result['days'], $blackout), 'from_size' => $result['from_size']];
    }

    // 3️⃣ Global cutoff fallback
    return ['date' => pt_calculate_pickup_date(), 'from_size' => false];
}

/* ======================================================
 * 8. CHECKOUT FIELD + DATEPICKER
 * ====================================================== */
add_action('woocommerce_after_order_notes', 'pt_render_pickup_date_field');
function pt_render_pickup_date_field($checkout) {

    /*
    // DEBUG — remove before going live
    $cart  = WC()->cart->get_cart();
    $debug = [];
    foreach ($cart as $key => $cart_item) {
        if (!isset($cart_item['data']) || isset($cart_item['composite_parent'])) continue;
        $product   = $cart_item['data'];
        $parent_id = $product->get_id();
        $entry = [
            'title'                 => $product->get_name(),
            'id'                    => $parent_id,
            'include_fast_delivery' => get_field('include_fast_delivery', $parent_id),
            'delivery_time'         => get_field('delivery_time', $parent_id),
            'children'              => [],
        ];
        if (isset($cart_item['composite_children'])) {
            foreach ($cart_item['composite_children'] as $child_key) {
                if (!isset($cart[$child_key]['data'])) continue;
                $child = $cart[$child_key]['data'];
                $entry['children'][] = [
                    'title'          => $child->get_title(),
                    'is_size'        => (bool) preg_match('/^\d+\s*x\s*\d+$/i', trim($child->get_title())),
                    'delivery_time'  => get_field('delivery_time', $child->get_id()),
                ];
            }
            $entry['resolved'] = pt_resolve_composite_delivery_days($cart_item, $cart);
        } else {
            $entry['resolved'] = pt_resolve_delivery_days($product);
        }
        $debug[] = $entry;
    }
    echo '<pre style="background:#111;color:#eee;padding:12px;font-size:11px;overflow:auto">';
    var_dump($debug);
    echo '<strong>Final:</strong> ';
    var_dump(pt_get_product_delivery_days_from_cart());
    echo '</pre>';
    // END DEBUG
    */

    $pickup        = pt_get_min_pickup_date();
    $min_date      = $pickup['date'];
    // Size-driven lead times: only grey out lead_time_excluded_dates + weekends, not blackout dates
    $disabled_dates = $pickup['from_size']
        ? pt_get_lead_time_excluded_dates()
        : pt_get_blackout_dates();

    $min_date_js  = esc_js($min_date->format('Y-m-d'));
    $holidays_js  = wp_json_encode($disabled_dates);
    
    echo '<div id="order_pickup_date_field">';
    echo '<h3>Delivery Info <span class="required">*</span></h3>';

    woocommerce_form_field('order_pickup_date', [
        'type'        => 'text',
        'required'    => true,
        'class'       => ['form-row-wide'],
        'id'          => 'datepicker',
        'autocomplete'=> 'off',
        'custom_attributes' => ['readonly' => 'readonly'],
        'placeholder' => 'Choose your preferred date',
    ], $checkout->get_value('order_pickup_date'));

    echo "<script>
    const minDate = new Date('{$min_date_js}T00:00:00');
    const holidays = {$holidays_js};

    function disableHoliday(date) {
        const ymd = jQuery.datepicker.formatDate('yy-mm-dd', date);
        const day = date.getDay();
        return [day !== 0 && day !== 6 && !holidays.includes(ymd)];
    }

    jQuery(function($){
        $('#datepicker').datepicker({
            minDate: minDate,
            defaultDate: minDate,
            beforeShowDay: disableHoliday,
            showButtonPanel: true
        });
    });
    </script>";

    echo '</div>';
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