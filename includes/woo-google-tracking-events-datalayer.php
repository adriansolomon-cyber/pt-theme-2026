<?php
add_action('woocommerce_thankyou', 'google_order_conversion');
function google_order_conversion($order_id) {

    $order = wc_get_order($order_id);
    if (!$order) return;

    $order->update_meta_data('_pt_purchase_tracked', '1');
    $order->save();

    $gtag_items   = [];
    $current_item = [];

    foreach ($order->get_items() as $item_id => $item) {
        $product = $item->get_product();
        if (!$product) continue;

        $type = $product->get_type();

        if ('composite' === $type) {
            if (!empty($current_item)) {
                $gtag_items[] = $current_item;
            }

            $item_category = null;
            $categories = get_the_terms($product->get_id(), 'product_cat');
            if (!empty($categories) && !is_wp_error($categories)) {
                foreach ($categories as $cat) {
                    $item_category = $cat->name;
                }
            }

            $current_item = [
                'item_id'                  => (string) $product->get_id(),
                'id'                  => (string) $product->get_id(),
                'item_name'                => $product->get_name(),
                'item_variant'             => null,
                'item_category'            => $item_category,
                'price'                    => 0,
                'quantity'                 => (int) $item->get_quantity(),
                'google_business_vertical' => 'retail',
            ];

        } elseif (!empty($current_item)) {
            $child_price  = $product->get_price();
            $current_item['price'] += $child_price;

            if (preg_match('/^\d+\s*x\s*\d+$/i', trim($product->get_name()))) {
                $current_item['item_id']      = (string) $product->get_id();
                $current_item['item_variant'] = $product->get_name();
            }
        }
    }

    if (!empty($current_item)) {
        $gtag_items[] = $current_item;
    }

    $order_total    = number_format((float) $order->get_total(), 2, '.', '');
    $order_tax      = number_format((float) $order->get_total_tax(), 2, '.', '');
    $order_shipping = number_format((float) $order->get_shipping_total(), 2, '.', '');
    $order_coupon   = implode(', ', $order->get_coupon_codes());
    $tx_id          = $order->get_order_number();

    // Calculate customer lifetime value
    $customer_id = $order->get_customer_id();
    $ltv = (float) $order->get_total();
    if ($customer_id) {
        $ltv = (float) wc_get_customer_total_spent($customer_id);
    }
    $customer_ltv = number_format($ltv, 2, '.', '');

    // Normalize user data
    $billing_email = strtolower(trim((string) $order->get_billing_email()));
    // Gmail normalization
    if (preg_match('/^(.+)@(gmail\.com|googlemail\.com)$/', $billing_email, $matches)) {
        $billing_email = str_replace('.', '', $matches[1]) . '@' . $matches[2];
    }

    $raw_phone     = preg_replace('/[^\d+]/', '', (string) $order->get_billing_phone());
    $billing_phone = (strpos($raw_phone, '+') === 0)
        ? $raw_phone
        : '+44' . ltrim($raw_phone, '0');

    $billing_first_name = strtolower(trim((string) $order->get_billing_first_name()));
    $billing_last_name  = strtolower(trim((string) $order->get_billing_last_name()));
    $billing_street     = strtolower(trim((string) $order->get_billing_address_1()));
    $billing_city       = strtolower(trim((string) $order->get_billing_city()));
    $billing_postcode   = strtolower(trim((string) $order->get_billing_postcode()));
    $billing_country    = strtolower(trim((string) $order->get_billing_country()));

    // SHA-256 hashed values — only used for Google Ads gtag call
    $hashed_email      = hash('sha256', $billing_email);
    $hashed_phone      = hash('sha256', $billing_phone);
    $hashed_first_name = hash('sha256', $billing_first_name);
    $hashed_last_name  = hash('sha256', $billing_last_name);
    $hashed_street     = hash('sha256', $billing_street);
?>
<script>
window.dataLayer = window.dataLayer || [];

function gtag() {
    dataLayer.push(arguments);
}

// Hashed — for Google Ads enhanced conversions
gtag('set', 'user_data', {
    sha256_email_address: '<?php echo $hashed_email; ?>',
    sha256_phone_number: '<?php echo $hashed_phone; ?>',
    address: {
        sha256_first_name: '<?php echo $hashed_first_name; ?>',
        sha256_last_name: '<?php echo $hashed_last_name; ?>',
        sha256_street: '<?php echo $hashed_street; ?>',
        city: '<?php echo esc_js($billing_city); ?>',
        postal_code: '<?php echo esc_js($billing_postcode); ?>',
        country: '<?php echo esc_js($billing_country); ?>'
    }
});

dataLayer.push({
    ecommerce: null
});

// Raw — for GTM/GA4 (GTM User-Provided Data variable handles hashing)
dataLayer.push({
    event: 'purchase',
    user_data: {
        email: '<?php echo esc_js($billing_email); ?>',
        phone_number: '<?php echo esc_js($billing_phone); ?>',
        address: {
            first_name: '<?php echo esc_js($billing_first_name); ?>',
            last_name: '<?php echo esc_js($billing_last_name); ?>',
            street: '<?php echo esc_js($billing_street); ?>',
            city: '<?php echo esc_js($billing_city); ?>',
            postal_code: '<?php echo esc_js($billing_postcode); ?>',
            country: '<?php echo esc_js($billing_country); ?>'
        }
    },
    ecommerce: {
        transaction_id: '<?php echo esc_js($tx_id); ?>',
        value: <?php echo $order_total; ?>,
        currency: 'GBP',
        tax: <?php echo $order_tax; ?>,
        shipping: <?php echo $order_shipping; ?>,
        coupon: '<?php echo esc_js($order_coupon); ?>',
        affiliation: 'Project Timber',
        customer_lifetime_value: <?php echo $customer_ltv; ?>,
        items: <?php echo wp_json_encode($gtag_items); ?>
    }
});
</script>
<?php
}

add_action('wp_footer', 'push_begin_checkout_from_datalayer');

function push_begin_checkout_from_datalayer() {
    if (!is_checkout() || is_order_received_page()) return;
    ?>
<script>
jQuery(document).ready(function($) {

    if (typeof dataLayer === 'undefined') return;

    const existingPush = dataLayer.find(function(item) {
        return item.ecommerce && item.ecommerce.items;
    });

    if (!existingPush) return;

    window.dataLayer.push({
        event: 'begin_checkout',
        ecomm_pagetype: 'checkout',
        ecommerce: {
            currency: existingPush.ecommerce.currency,
            value: existingPush.ecommerce.value,
            items: existingPush.ecommerce.items
        },
        user_data: existingPush.user_data || {}
    });

});
</script>
<?php
}
/**
 * Customizes the items array for the begin_checkout event using the available hooks.
 * This function intercepts the product list before the datalayer is built.
 */
add_filter('datalayer_before_checkout_loop_items', 'customize_datalayer_items_list', 10, 1);

/**
 * Customizes the items array for the purchase event using the available hooks.
 * This function intercepts the order items list before the datalayer is built.
 */
add_filter('datalayer_before_order_loop_items', 'customize_datalayer_items_list', 10, 1);

function customize_datalayer_items_list($products)
{

    // For the 'purchase' event, the hook provides the WC_Order object. We need to get the items from it.
    // For the 'begin_checkout' event, it provides the array of cart items directly.
    $items_list = is_a($products, 'WC_Order') ? $products->get_items() : $products;

    // The structure of the cart array and order array is different, so we need to handle them.
    if (is_array($items_list) && count($items_list) > 1) {

        // Get the keys of the array so we can access the first and second items reliably.
        $keys = array_keys($items_list);
        $first_item_key = $keys[0];
        $second_item_key = $keys[1];

        // Get the product objects
        $composite_product_item = $items_list[$first_item_key];
        $price_component_item = $items_list[$second_item_key];

        $composite_product = is_a($composite_product_item, 'WC_Order_Item') ? $composite_product_item->get_product() : $composite_product_item['product'];
        $price_component = is_a($price_component_item, 'WC_Order_Item') ? $price_component_item->get_product() : $price_component_item['product'];

        // Check if we have valid product objects
        if ($composite_product && $price_component) {
            // Get the price from the second item
            $price_to_use = $price_component->get_price();

            // Set this price on the main composite product object in memory
            $composite_product->set_price($price_to_use);

            // On the purchase hook, we must also update the item subtotal in the order item itself
            if (is_a($composite_product_item, 'WC_Order_Item_Product')) {
                $composite_product_item->set_subtotal($price_to_use * $composite_product_item->get_quantity());
                $composite_product_item->set_total($price_to_use * $composite_product_item->get_quantity());
            }

            // Return an array containing ONLY the modified first item.
            // This ensures the plugin's loop only processes our modified product.
            if (is_a($products, 'WC_Order')) {
                // For the purchase event, we can't modify the items directly this way.
                // It's better to use the JavaScript solution for older plugin versions for the purchase event.
                // However, let's keep the checkout logic.
                // The provided code shows no safe hook to modify purchase items total value this way.
                // The JS solution is the most reliable fallback.
                return $products; // Return original for purchase event to avoid breaking it.
            } else {
                return [$first_item_key => $composite_product_item];
            }
        }
    }

    // If something goes wrong or there's only one item, return the original list.
    return $products;
}

/**
 * Injects a JavaScript fix into the footer of the Thank You page
 * to correctly format the purchase event datalayer for composite products.
 */
add_action('wp_footer', 'add_purchase_event_datalayer_fix');

function add_purchase_event_datalayer_fix()
{

    // We only want this script to run on the "Order Received" (Thank You) page.
    if (! is_order_received_page()) {
        return;
    }

    // Using a HEREDOC to safely embed the JavaScript
    $javascript_fix = <<<EOD
<script>
// This script specifically targets the 'purchase' event dataLayer push
// to ensure only the main composite product is tracked.
document.addEventListener('DOMContentLoaded', function() {

// Don't run this logic if it has been run before on the same page load
if (window.customPurchaseListenerAttached) {
return;
}

// A more robust way to wait for the dataLayer to be available.
var originalPush = window.dataLayer && window.dataLayer.push;

if (typeof originalPush === 'function') {
window.dataLayer.push = function() {
    var eventData = arguments[0]; // Get the object being pushed

    // Check if it's the 'purchase' event we want to modify
    if (eventData && eventData.event === 'purchase' && eventData.ecommerce && eventData.ecommerce.items) {
        
        var items = eventData.ecommerce.items;

        // Ensure there are enough items to perform the logic
        if (items.length > 1) {
            var compositeProduct = items[0];
            var priceComponent = items[1];

            // 1. Set the correct price on the main product
            if (typeof priceComponent.price !== 'undefined') {
                compositeProduct.price = priceComponent.price;
            }

            // 2. Clean up the extra categories
            var primaryCategory = compositeProduct.item_category || '';
            for (var key in compositeProduct) {
                if (key.startsWith('item_category') && key !== 'item_category') {
                    delete compositeProduct[key];
                }
            }
            compositeProduct.item_category = primaryCategory;

            // 3. Replace the entire items array with just our modified product
            eventData.ecommerce.items = [compositeProduct];
        }
    }
    
    // Call the original dataLayer.push function with the (potentially modified) data
    return originalPush.apply(window.dataLayer, arguments);
};

window.customPurchaseListenerAttached = true;
}
});
</script>
EOD;

    echo $javascript_fix;
}