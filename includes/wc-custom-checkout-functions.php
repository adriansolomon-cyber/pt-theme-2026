<?php

/**
 * Auto-apply coupon code in WooCommerce cart and show message
 */

/**
 * Helper functions
 */

function auto_voucher_enabled() {
    return (bool) get_field('enable_auto_apply_voucher_on_checkout', 'option');
}

/**
 * Simple helper: check if a product qualifies for the special voucher
 * based ONLY on product title and categories.
 *
 * @param int $product_id
 * @return bool
 */
function av_product_qualifies_simple( $product_id ) {

    if ( ! auto_voucher_enabled() ) return false;

    $product_id = (int) $product_id;
    if ( ! $product_id ) return false;

    $product = wc_get_product( $product_id );
    if ( ! $product ) return false;

    // Get categories
    $cats = wc_get_product_term_ids( $product_id, 'product_cat' );
    if ( ! is_array( $cats ) ) $cats = [];

    // RULE SET
    $keywords           = [ 'grandmaster' ];
    $allowed_categories = [ 2346 ];

    // Title match
    $title = strtolower( $product->get_name() );
    foreach ( $keywords as $k ) {
        if ( strpos( $title, $k ) !== false ) {
            return true;
        }
    }

    // Category match
    if ( array_intersect( $allowed_categories, $cats ) ) {
        return true;
    }

    // No match
    return false;
}


/**
 * Toggle — currently runs ONLY in admin.
 */
// function auto_voucher_enabled() {
//     return current_user_can('administrator'); // ADMIN ONLY MODE
// }
/**
 * Default voucher from ACF option.
 */
function av_get_default_voucher_code() {
    if ( ! auto_voucher_enabled() ) return '';
    if ( ! function_exists( 'get_field' ) ) return '';

    $code = get_field( 'coupon_code', 'option' );
    return is_string( $code ) ? strtolower( trim( $code ) ) : '';
}

/**
 * Special voucher code.
 */
function av_get_special_voucher_code() {
    return 'gm10';
}

/**
 * Detect REAL parent products in cart.
 *
 * Parent = NOT composite child AND NOT in categories 89 or 169.
 */
function av_get_parent_cart_products() {

    if ( ! auto_voucher_enabled() ) return [];
    if ( ! WC()->cart ) return [];

    $parents = [];

    foreach ( WC()->cart->get_cart() as $item_key => $item ) {

        $product_id = isset( $item['product_id'] ) ? (int) $item['product_id'] : 0;
        if ( ! $product_id ) continue;

        $product = wc_get_product( $product_id );
        if ( ! $product ) continue;

        // Get categories
        $cats = wc_get_product_term_ids( $product_id, 'product_cat' );
        $cats = is_array( $cats ) ? $cats : [];

        // Child rules
        $is_child_by_composite = ! empty( $item['composite_parent'] );
        $is_child_by_cat       = in_array( 89, $cats, true ) || in_array( 169, $cats, true );

        $is_parent = ! $is_child_by_composite && ! $is_child_by_cat;

        if ( ! $is_parent ) continue;

        $parents[] = [
            'product_id' => $product_id,
            'title'      => $product->get_name(),
            'categories' => $cats,
        ];
    }

    return $parents;
}

/**
 * Determine if cart qualifies for XMAS30.
 *
 * Conditions:
 *  - ≥ 2 parent products
 *  - OR title contains: "grandmaster", "my den", "evolution"
 *  - OR category in: 3219, 2980, 2345
 */
function av_cart_qualifies_for_special() {

    if ( ! auto_voucher_enabled() ) return false;
    if ( ! WC()->cart || WC()->cart->is_empty() ) return false;

    $parents = av_get_parent_cart_products();
    $count   = count( $parents );

    if ( $count === 0 ) return false;

    // Rule: 2+ parent products
    // if ( $count >= 2 ) return true;

    // Rule: single parent product → check title/category
    $keywords           = [ 'grandmaster' ];
    $allowed_categories = [ 2346 ];

    foreach ( $parents as $p ) {
        $title = strtolower( $p['title'] );
        $cats  = $p['categories'];

        // Keyword match
        foreach ( $keywords as $k ) {
            if ( strpos( $title, $k ) !== false ) {
                return true;
            }
        }

        // Category match
        if ( array_intersect( $allowed_categories, $cats ) ) {
            return true;
        }
    }

    return false;
}

/**
 * Ensure ONLY the last applied coupon remains.
 *
 * User rule:
 * "If user enters ANY custom coupon, remove existing and apply that one."
 */
add_action( 'woocommerce_applied_coupon', function( $applied_coupon_code ) {

    if ( ! auto_voucher_enabled() || ! WC()->cart ) return;

    $applied_coupon_code_lower = strtolower( $applied_coupon_code );

    foreach ( WC()->cart->get_applied_coupons() as $code ) {
        if ( strtolower( $code ) !== $applied_coupon_code_lower ) {
            WC()->cart->remove_coupon( $code );
        }
    }
} );

/**
 * Validate XMAS30 when user enters it manually.
 */
add_filter( 'woocommerce_coupon_is_valid', function( $valid, $coupon ) {

    if ( ! auto_voucher_enabled() || ! WC()->cart ) return $valid;

    $code         = strtolower( $coupon->get_code() );
    $special_code = strtolower( av_get_special_voucher_code() );

    // If not xmas30 → allow
    if ( $code !== $special_code ) return $valid;

    // If qualifies → allow
    if ( av_cart_qualifies_for_special() ) return true;

    // NOT qualified
    $default_code = av_get_default_voucher_code();

    wc_add_notice(
        sprintf(
            __( 'Coupon %s is not valid for your cart. Default discount %s has been applied instead.', 'textdomain' ),
            strtoupper( $special_code ),
            strtoupper( $default_code )
        ),
        'error'
    );

    // Apply fallback
    if ( $default_code && ! WC()->cart->has_discount( $default_code ) ) {
        WC()->cart->apply_coupon( $default_code );
    }

    return false;
}, 10, 2 );

/**
 * MAIN AUTO-APPLY LOGIC (admin only)
 *
 * 1. If custom coupon exists → do nothing.
 * 2. If cart qualifies → apply XMAS30, remove default.
 * 3. Else → apply default.
 */
add_action( 'woocommerce_before_calculate_totals', function() {

    if ( ! auto_voucher_enabled() || ! WC()->cart ) return;
    if ( WC()->cart->is_empty() ) return;

    $default_code = av_get_default_voucher_code();
    $special_code = av_get_special_voucher_code();

    $applied       = WC()->cart->get_applied_coupons();
    $applied_lower = array_map( 'strtolower', $applied );

    $default_lower = strtolower( $default_code );
    $special_lower = strtolower( $special_code );

    /**
     * 1. CUSTOM COUPON? → let user override.
     */
    foreach ( $applied_lower as $code ) {
        if ( $code !== $default_lower && $code !== $special_lower ) {
            return; // user coupon wins
        }
    }

    /**
     * 2. If qualifies → apply XMAS30 exclusively.
     */
    if ( av_cart_qualifies_for_special() ) {

        if ( ! in_array( $special_lower, $applied_lower, true ) ) {
            WC()->cart->apply_coupon( $special_code );
        }

        if ( in_array( $default_lower, $applied_lower, true ) ) {
            WC()->cart->remove_coupon( $default_code );
        }

        return;
    }

    /**
     * 3. Not qualified → ensure xmas30 removed, apply default.
     */
    if ( in_array( $special_lower, $applied_lower, true ) ) {
        WC()->cart->remove_coupon( $special_code );
    }

    if ( $default_code && ! WC()->cart->has_discount( $default_code ) ) {
        WC()->cart->apply_coupon( $default_code );
    }

}, 10 );

add_action( 'woocommerce_before_cart', 'show_coupon_status_message' );
add_action( 'woocommerce_before_checkout_form', 'show_coupon_status_message' );

function show_coupon_status_message() {

    // Only run when enabled
    if ( ! auto_voucher_enabled() ) return;
    if ( ! WC()->cart ) return;

    $applied = WC()->cart->get_applied_coupons();
    if ( empty( $applied ) ) return;

    $default_code = av_get_default_voucher_code();
    $special_code = av_get_special_voucher_code();

    $default_lower = strtolower( $default_code );
    $special_lower = strtolower( $special_code );

    // Hidden marker: tells assets/js/wc-notices.js to keep this notice visible
    // permanently (no 10s auto-dismiss countdown), unlike every other notice.
    $persist = '<span class="pt-voucher-notice"></span>';

    // Case 1: Default voucher applied
    if ( $default_code && WC()->cart->has_discount( $default_code ) ) {
        wc_print_notice(
            $persist . sprintf(
                __('Great news! We automatically applied the "%s" discount to your order.', 'textdomain'),
                strtoupper( $default_code )
            ),
            'success'
        );
        return;
    }

    // Case 2: Special voucher (XMAS30) applied
    if ( $special_code && WC()->cart->has_discount( $special_code ) ) {
        wc_print_notice(
            $persist . sprintf(
                __('Your exclusive "%s" discount has been applied!', 'textdomain'),
                strtoupper( $special_code )
            ),
            'success'
        );
        return;
    }

    // Case 3: A custom coupon (user-entered) is active
    foreach ( $applied as $code ) {
        $lower = strtolower( $code );

        if ( $lower !== $default_lower && $lower !== $special_lower ) {
            wc_print_notice(
                $persist . sprintf(
                    __('Coupon "%s" is active on your order.', 'textdomain'),
                    strtoupper( $code )
                ),
                'success'
            );
            return;
        }
    }
}


/**
 * DEBUG OUTPUT — admin only.
 */
// add_action( 'wp', function() {

//     if ( ! auto_voucher_enabled() ) return;

//     if ( is_cart() || is_checkout() ) {

//         echo '<pre style="background:#000;color:#0f0;padding:10px;font-size:11px;">';
//         // echo "PARENT PRODUCTS:\n";
//         // print_r( av_get_parent_cart_products() );
//         echo "\nQUALIFIES FOR XMAS30: ";
//         var_dump( av_cart_qualifies_for_special() );
//         echo "</pre>";

//     }
// });





/**
 * SHIPPING ADDRESS TOGGLE FUNCTIONALITY
 */
// 1. Force WooCommerce to show shipping address
add_filter('woocommerce_cart_needs_shipping_address', '__return_true');
// 2. Configure WooCommerce's default shipping toggle
add_filter('woocommerce_ship_to_different_address_checked', '__return_false');
// 3. Ensure shipping fields exist and remove company from billing
add_filter('woocommerce_checkout_fields', 'ensure_shipping_fields_exist');
function ensure_shipping_fields_exist($fields)
{
    // Remove company field from billing
    if (isset($fields['billing']['billing_company'])) {
        unset($fields['billing']['billing_company']);
    }

    $shipping_fields = array(
        'shipping_first_name' => array(
            'label'     => __('First name', 'woocommerce'),
            'required'  => false,
            'class'     => array('form-row-first'),
            'clear'     => false,
            'priority'  => 10
        ),
        'shipping_last_name' => array(
            'label'     => __('Last name', 'woocommerce'),
            'required'  => false,
            'class'     => array('form-row-last'),
            'clear'     => true,
            'priority'  => 20
        ),
        'shipping_country' => array(
            'label'     => __('Country / Region', 'woocommerce'),
            'required'  => false,
            'type'      => 'country',
            'class'     => array('form-row-wide'),
            'priority'  => 30
        ),
        'shipping_address_1' => array(
            'label'     => __('Street address', 'woocommerce'),
            'placeholder' => __('House number and street name', 'woocommerce'),
            'required'  => false,
            'class'     => array('form-row-wide'),
            'priority'  => 40
        ),
        'shipping_address_2' => array(
            'label'     => __('Apartment, suite, unit, etc.', 'woocommerce'),
            'placeholder' => __('Apartment, suite, unit, etc. (optional)', 'woocommerce'),
            'required'  => false,
            'class'     => array('form-row-wide'),
            'priority'  => 50
        ),
        'shipping_city' => array(
            'label'     => __('Town / City', 'woocommerce'),
            'required'  => false,
            'class'     => array('form-row-wide'),
            'priority'  => 60
        ),
        'shipping_state' => array(
            'label'     => __('County', 'woocommerce'),
            'required'  => false,
            'class'     => array('form-row-wide'),
            'priority'  => 70
        ),
        'shipping_postcode' => array(
            'label'     => __('Postcode', 'woocommerce'),
            'required'  => false,
            'class'     => array('form-row-wide'),
            'priority'  => 80
        ),
        'shipping_phone' => array(
            'label'     => __('Phone', 'woocommerce'),
            'required'  => false,
            'type'      => 'tel',
            'class'     => array('form-row-wide'),
            'priority'  => 90
        ),
        'shipping_email' => array(
            'label'     => __('Email address', 'woocommerce'),
            'required'  => false,
            'type'      => 'email',
            'class'     => array('form-row-wide'),
            'priority'  => 100
        ),
        'shipping_company' => array(
            'label'     => __('Company name', 'woocommerce'),
            'required'  => false,
            'class'     => array('form-row-wide'),
            'priority'  => 110
        )
    );

    // Ensure shipping fields are added to the checkout
    $fields['shipping'] = $shipping_fields;

    return $fields;
}
// 4. Add the "Copy billing address" button to the default shipping section
add_action('woocommerce_before_checkout_shipping_form', 'add_copy_billing_button');
function add_copy_billing_button()
{
?>
<div class="copy-billing-wrapper" style="display: none; margin-bottom: 15px;">
    <button type="button" class="copy-billing-button"
        style="padding: 8px 15px; background: #0073aa; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 12px;">Copy
        from billing address</button>
</div>
<?php
}
// 5. Add CSS and JavaScript to work with WooCommerce's default checkbox
add_action('wp_footer', 'shipping_toggle_scripts');
function shipping_toggle_scripts()
{
    // Only run on checkout page
    if (!is_checkout()) {
        return;
    }
?>
<style>
/* Coupon message styling */
.woocommerce-message {
    text-align: center;
    font-size: 14px;
    font-weight: 400;
    background-color: #d4edda !important;
    border-color: #c3e6cb !important;
    color: #155724 !important;
    padding: 15px 40px;
    margin-bottom: 20px;
    border-radius: 5px;
}

/* Style for copy billing button */
.copy-billing-wrapper {
    padding: 10px 0;
}

.copy-billing-button {
    transition: all 0.2s ease;
    border: none !important;
    outline: none !important;
}

.copy-billing-button:hover {
    background: #005a87 !important;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.copy-billing-button:active {
    transform: translateY(0);
}

/* Hide shipping fields initially */
.woocommerce-shipping-fields {
    display: none;
}

/* Show when WooCommerce adds the class */
.woocommerce-shipping-fields.shipping-different-address {
    display: block !important;
}
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Use WooCommerce's default checkbox ID
    var $defaultCheckbox = $('#ship-to-different-address-checkbox');
    var $copyWrapper = $('.copy-billing-wrapper');

    // Function to copy billing to shipping (with phone and email)
    function copyBillingToShipping() {
        // Check if required billing fields are filled
        var requiredBillingFields = ['billing_first_name', 'billing_last_name', 'billing_country',
            'billing_address_1', 'billing_city', 'billing_postcode'
        ];
        var missingFields = [];

        $.each(requiredBillingFields, function(index, field) {
            var value = $('#' + field).val();
            if (!value || value.trim() === '') {
                var label = $('label[for="' + field + '"]').text().replace('*', '').trim();
                if (!label) {
                    // Fallback labels if no label found
                    var fieldLabels = {
                        'billing_first_name': 'First name',
                        'billing_last_name': 'Last name',
                        'billing_country': 'Country',
                        'billing_address_1': 'Street address',
                        'billing_city': 'City',
                        'billing_postcode': 'Postcode'
                    };
                    label = fieldLabels[field] || field;
                }
                missingFields.push(label);
            }
        });

        // If there are missing required fields, show error message
        if (missingFields.length > 0) {
            // Remove any existing error messages
            $('.copy-billing-error').remove();

            var errorMessage = 'Please fill in the following billing fields first: ' + missingFields.join(', ');

            // Add error message after the copy button
            $('.copy-billing-wrapper').after(
                '<div class="copy-billing-error" style="color: #e74c3c; font-size: 12px; margin-top: 5px; padding: 8px; background: #ffeaea; border: 1px solid #f5c6cb; border-radius: 3px;">' +
                errorMessage + '</div>');

            // Remove error message after 5 seconds
            setTimeout(function() {
                $('.copy-billing-error').fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);

            return false; // Don't proceed with copying
        }

        // Remove any existing error messages
        $('.copy-billing-error').remove();

        var billingFields = {
            'billing_first_name': 'shipping_first_name',
            'billing_last_name': 'shipping_last_name',
            'billing_country': 'shipping_country',
            'billing_address_1': 'shipping_address_1',
            'billing_address_2': 'shipping_address_2',
            'billing_city': 'shipping_city',
            'billing_state': 'shipping_state',
            'billing_postcode': 'shipping_postcode',
            'billing_phone': 'shipping_phone',
            'billing_email': 'shipping_email'
        };

        $.each(billingFields, function(billing, shipping) {
            var billingValue = $('#' + billing).val();
            if (billingValue) {
                $('#' + shipping).val(billingValue);
            }
        });

        // Trigger change events on key fields that affect shipping calculations
        $('#shipping_country, #shipping_state, #shipping_postcode, #shipping_city').trigger('change');

        // Force checkout update to recalculate shipping
        $('body').trigger('update_checkout');

        return true; // Successfully copied
    }

    // Handle WooCommerce's default checkbox change
    $(document).on('change', '#ship-to-different-address-checkbox', function() {
        if ($(this).is(':checked')) {
            $copyWrapper.slideDown(200);
            // Focus on first shipping field after animation
            setTimeout(function() {
                $('.woocommerce-shipping-fields input:visible:first').focus();
            }, 300);
        } else {
            $copyWrapper.slideUp(200);
            // Clear shipping fields when hidden
            $('.woocommerce-shipping-fields').find(
                    'input[type="text"], input[type="email"], input[type="tel"], select, textarea')
                .not('[type="hidden"]')
                .val('')
                .trigger('change');
        }
    });

    // Add click handler for copy button
    $(document).on('click', '.copy-billing-button', function(e) {
        e.preventDefault();
        var copyResult = copyBillingToShipping();

        // Only show success message if copy was successful
        if (copyResult) {
            $(this).text('✓ Copied!').css('background', '#46b450');
            setTimeout(function() {
                $('.copy-billing-button').text('Copy from billing address').css('background',
                    '#0073aa');
            }, 2000);
        }
    });

    // Handle checkout updates
    $('body').on('updated_checkout', function() {
        // Recheck the state after checkout updates
        setTimeout(function() {
            if ($('#ship-to-different-address-checkbox').is(':checked')) {
                $copyWrapper.show();
            } else {
                $copyWrapper.hide();
            }
        }, 100);
    });

    // Show shipping fields if there are validation errors
    if ($('.woocommerce-shipping-fields .woocommerce-invalid').length > 0) {
        $('#ship-to-different-address-checkbox').prop('checked', true).trigger('change');
    }

    // Accessibility improvements
    $('#ship-to-different-address-checkbox').on('keydown', function(e) {
        if (e.key === ' ') {
            e.preventDefault();
            $(this).prop('checked', !$(this).prop('checked')).trigger('change');
        }
    });
});
</script>
<?php
}
// 6. Handle the shipping data processing
add_action('woocommerce_checkout_process', 'validate_shipping_toggle');
function validate_shipping_toggle()
{
    // If shipping to different address is not checked, clear shipping data
    if (empty($_POST['ship_to_different_address'])) {
        // Clear shipping fields from POST data
        $shipping_fields = array(
            'shipping_first_name',
            'shipping_last_name',
            'shipping_company',
            'shipping_address_1',
            'shipping_address_2',
            'shipping_city',
            'shipping_state',
            'shipping_postcode',
            'shipping_country',
            'shipping_phone',
            'shipping_email'
        );

        foreach ($shipping_fields as $field) {
            if (isset($_POST[$field])) {
                $_POST[$field] = '';
            }
        }
    }
}
// 7. Copy billing to shipping if shipping is not different (without company)
add_action('woocommerce_checkout_order_processed', 'copy_billing_to_shipping_if_same');
function copy_billing_to_shipping_if_same($order_id)
{
    if (empty($_POST['ship_to_different_address'])) {
        $order = wc_get_order($order_id);
        $order->set_shipping_first_name($order->get_billing_first_name());
        $order->set_shipping_last_name($order->get_billing_last_name());
        $order->set_shipping_company('');
        $order->set_shipping_address_1($order->get_billing_address_1());
        $order->set_shipping_address_2($order->get_billing_address_2());
        $order->set_shipping_city($order->get_billing_city());
        $order->set_shipping_state($order->get_billing_state());
        $order->set_shipping_postcode($order->get_billing_postcode());
        $order->set_shipping_country($order->get_billing_country());
        // Use proper setters instead of update_meta_data()
       $order->set_shipping_phone($order->get_billing_phone());
        if ( method_exists( $order, 'set_shipping_email' ) ) {
            $order->set_shipping_email($order->get_billing_email());
        } else {
            $order->update_meta_data('_shipping_email', $order->get_billing_email());
        }
        $order->save();
    }
}

/**
 * Force user to set pickup date if using Paypal as payment mothod.
 */
add_action('wp_footer', 'validate_pickup_date_on_paypal_selection');
function validate_pickup_date_on_paypal_selection()
{
    if (is_checkout()) {
    ?>
<script>
jQuery(document).ready(function($) {
    function validatePickupDate() {
        // Check if datepicker element exists
        var pickupDate = $('#datepicker');
        if (pickupDate.length === 0) {
            console.log('Datepicker element not found - skipping validation');
            return false;
        }

        var pickupDateValue = pickupDate.val();
        var pickupDateField = $('#order_pickup_date_field');

        // Check if the parent field container exists
        if (pickupDateField.length === 0) {
            console.log('Pickup date field container not found - skipping validation');
            return false;
        }

        if (!pickupDateValue) {
            console.log('No pickup date - showing message and focusing datepicker');

            // Remove any existing message first
            pickupDateField.find('.pickup-date-message').remove();

            // Append message to the datepicker field
            pickupDateField.append(
                '<div class="pickup-date-message" style="color: red; margin-top: 5px; font-size: 14px;">Please select a delivery date to continue with PayPal payment.</div>'
            );

            // Check if element has offset before scrolling
            if (pickupDate.offset()) {
                // Scroll to the datepicker
                $('html, body').animate({
                    scrollTop: pickupDate.offset().top - 300
                }, 500);
            }

            // Remove message after 10 seconds
            setTimeout(function() {
                pickupDateField.find('.pickup-date-message').fadeOut(300, function() {
                    $(this).remove();
                });
            }, 10000);

            return false;
        }

        return true;
    }

    // Listen for payment method changes
    $(document).on('change', 'input[name="payment_method"]', function() {
        if ($(this).val() === 'ppcp-gateway') {
            console.log('PayPal selected via change event');
            validatePickupDate();
        }
    });

    // Remove message when user selects a date (only if datepicker exists)
    if ($('#datepicker').length > 0) {
        $('#datepicker').on('change', function() {
            if ($(this).val()) {
                $('#order_pickup_date_field .pickup-date-message').fadeOut(300, function() {
                    $(this).remove();
                });
            }
        });
    }

    // Optional: Add a more robust check for when the checkout form is updated
    $(document.body).on('updated_checkout', function() {
        // Re-bind events if datepicker is dynamically loaded
        if ($('#datepicker').length > 0 && !$('#datepicker').data('events-bound')) {
            $('#datepicker').on('change', function() {
                if ($(this).val()) {
                    $('#order_pickup_date_field .pickup-date-message').fadeOut(300, function() {
                        $(this).remove();
                    });
                }
            }).data('events-bound', true);
        }
    });
});
</script>
<?php
    }
}

/**
 * Validate delivery date has been selected.
 */
add_action('woocommerce_checkout_process', 'order_pickup_date_validate');
function order_pickup_date_validate()
{
    // Check if set, if its not set add an error.
    if (empty($_POST['order_pickup_date'])) {
        wc_add_notice(__('Delivery date is a required field.'), 'error');
        return;
    }

    // Additional validation for PayPal specifically
    if (isset($_POST['payment_method']) && $_POST['payment_method'] === 'ppcp-gateway') {
        $pickup_date = sanitize_text_field($_POST['order_pickup_date']);
        if (empty($pickup_date)) {
            wc_add_notice(__('Delivery date is required for PayPal payments.'), 'error');
        }
    }
}



add_action('wp_footer', function () {
	if ( ! is_checkout() ) return;
	?>

<script>
(function() {

    let zoneApplied = false;
    let originalBeforeShowDay = null;
    let originalMinDate = null;

    function getPicker() {
        if (!window.jQuery) return null;

        const $picker = jQuery('#datepicker');
        if (!$picker.length || typeof $picker.datepicker !== 'function') {
            return null;
        }
        return $picker;
    }

    function checkZoneWarning() {
        const zoneActive = !!document.querySelector('.pt-zone-warning');
        const leadMsg = document.querySelector('.lead-time-msg');
        const picker = getPicker();

        // Toggle message purely based on zone state
        if (leadMsg) {
            leadMsg.style.display = zoneActive ? '' : 'none';
        }

        if (!picker) return;

        // Cache originals ONCE
        if (originalBeforeShowDay === null) {
            originalBeforeShowDay = picker.datepicker('option', 'beforeShowDay');
        }
        if (originalMinDate === null) {
            originalMinDate = picker.datepicker('option', 'minDate');
        }

        const forcedMinDate = new Date(2026, 0, 6); // 6 January 2026

        // 🔒 APPLY override
        if (zoneActive && !zoneApplied) {

            picker.datepicker('option', 'minDate', forcedMinDate);
            picker.datepicker('option', 'beforeShowDay', function(date) {
                return [date >= forcedMinDate];
            });

            zoneApplied = true;
        }

        // 🔓 RESTORE original behavior
        if (!zoneActive && zoneApplied) {

            picker.datepicker('option', 'minDate', originalMinDate);
            picker.datepicker('option', 'beforeShowDay', originalBeforeShowDay);

            zoneApplied = false;
        }
    }

    // Initial check
    checkZoneWarning();

    // Observe Woo fragment updates
    const observer = new MutationObserver(checkZoneWarning);
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });

})();
</script>

<?php
});

        function postcode_restriction_validation($data, $errors) {
            $postcode = isset($data['billing_postcode']) ? $data['billing_postcode'] : '';
            $patterns = get_field('postcode', 'option'); // ACF field
            
            if (!empty($postcode) && $patterns) {
                foreach ($patterns as $pattern) {
                    if (preg_match('/^' . preg_quote($pattern['code'], '/') . '/i', $postcode)) {
                        $errors->add('validation', sprintf(__('Postcodes starting with "%s" are not accepted.'), $pattern['code']));
                        break;
                    }
                }
            }
        }
add_action('woocommerce_after_checkout_validation', 'postcode_restriction_validation', 10, 2);

/**
 * Fetches the official IANA TLD list and caches it for 7 days.
 * Returns an array of valid TLDs in lowercase.
 */
function wc_get_valid_tlds() {

    $cached = get_transient( 'wc_iana_tld_list' );

    if ( $cached ) return $cached;

    $response = wp_remote_get( 'https://data.iana.org/TLD/tlds-alpha-by-domain.txt', [
        'timeout' => 10,
    ] );

    if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
        return [ 'com', 'net', 'org', 'io', 'co', 'uk', 'de', 'fr', 'eu', 'info', 'biz', 'me', 'tv' ];
    }

    $body  = wp_remote_retrieve_body( $response );
    $lines = explode( "\n", trim( $body ) );
    $tlds  = [];

    foreach ( $lines as $line ) {
        $line = trim( $line );
        if ( empty( $line ) || strpos( $line, '#' ) === 0 ) continue;
        $tlds[] = strtolower( $line );
    }

    set_transient( 'wc_iana_tld_list', $tlds, DAY_IN_SECONDS * 7 );

    return $tlds;
}

/**
 * Server-side validation — blocks order if email is invalid.
 */
add_action( 'woocommerce_checkout_process', 'wc_validate_checkout_email' );

function wc_validate_checkout_email() {

    $email = isset( $_POST['billing_email'] ) ? sanitize_email( trim( $_POST['billing_email'] ) ) : '';

    if ( empty( $email ) || ! is_email( $email ) ) {
        wc_add_notice(
            __( 'Please enter a valid email address.', 'wc-email-validation' ),
            'error'
        );
        return;
    }

    $domain = substr( strrchr( $email, '@' ), 1 );
    $tld    = strtolower( substr( strrchr( $domain, '.' ), 1 ) );
    $tlds   = wc_get_valid_tlds();

    if ( ! in_array( $tld, $tlds, true ) ) {
        wc_add_notice(
            sprintf(
                __( '"%s" is not a valid email domain extension. Please use a real email address.', 'wc-email-validation' ),
                esc_html( $tld )
            ),
            'error'
        );
        return;
    }

    if ( ! wc_email_domain_is_valid( $domain ) ) {
        wc_add_notice(
            sprintf(
                __( 'The email domain "%s" does not appear to exist. Please use a real email address.', 'wc-email-validation' ),
                esc_html( $domain )
            ),
            'error'
        );
    }
}

/**
 * Checks domain has MX or A record, and blocks disposable providers.
 */
function wc_email_domain_is_valid( $domain ) {

    if ( empty( $domain ) ) return false;

    $blocked_domains = apply_filters( 'wc_email_blocked_domains', [
        'mailinator.com',
        'guerrillamail.com',
        'trashmail.com',
        'yopmail.com',
        'tempmail.com',
        'throwam.com',
        'sharklasers.com',
        'dispostable.com',
    ] );

    if ( in_array( strtolower( $domain ), $blocked_domains, true ) ) {
        return false;
    }

    if ( function_exists( 'checkdnsrr' ) ) {
        return checkdnsrr( $domain, 'MX' ) || checkdnsrr( $domain, 'A' );
    }

    return true;
}

/**
 * Enqueue styles + JS for client-side inline email validation.
 */
add_action( 'woocommerce_after_checkout_form', 'wc_checkout_email_js_validation' );

function wc_checkout_email_js_validation() {

    $tlds      = wc_get_valid_tlds();
    $tlds_json = wp_json_encode( $tlds );

    ?>
    <style>
    /* ── Email field wrapper ── */
    .wc-email-field-wrap {
        position: relative;
        display: flex;
        align-items: center;
    }

    .wc-email-field-wrap #billing_email {
        padding-right: 42px; /* room for the icon */
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    /* Invalid state */
    .wc-email-field-wrap.wc-email--invalid #billing_email {
        border-color: #e02b27 !important;
        outline: none;
    }

    /* Valid state */
    .wc-email-field-wrap.wc-email--valid #billing_email {
        border-color: #00fe6c !important;
        outline: none;
    }

    /* Icon bubble */
    .wc-email-icon {
        position: absolute;
        right: 0;
        bottom: 20px;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
        font-weight: 700;
        line-height: 1;
        flex-shrink: 0;
        transition: opacity 0.2s ease, transform 0.2s ease;
        opacity: 0;
        transform: scale(0.6);
        pointer-events: none;
    }

    .wc-email-icon.wc-email-icon--visible {
        opacity: 1;
        transform: scale(0.8);
    }

    .wc-email-icon--invalid {
        background: #e02b27;
        color: #fff;
    }

    .wc-email-icon--valid {
        background: #00fe6c;
        color: #fff;
    }

    /* Inline error text */
    .wc-email-inline-error {
        display: block;
        margin-top: 5px;
        font-size: 13px;
        color: #e02b27;
        min-height: 18px;
        transition: opacity 0.2s ease;
        line-height: 1.33;
    }
    .wc-email-icon--valid {
    background: transparent !important;
}
    </style>

    <script type="text/javascript">
    (function($) {
        'use strict';

        var VALID_TLDS  = <?php echo $tlds_json; ?>;
        var EMAIL_REGEX = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        /* ── Core validation logic ── */
        function validateEmail(value) {
            if ( !value ) {
                return { ok: false, msg: 'Please enter an email address.' };
            }
            if ( !EMAIL_REGEX.test(value) ) {
                return { ok: false, msg: 'Please enter a valid email address (e.g. you@example.com).' };
            }
            var domain = value.split('@')[1] || '';
            var tld    = domain.split('.').pop().toLowerCase();
            if ( VALID_TLDS.indexOf(tld) === -1 ) {
                return { ok: false, msg: '".' + tld + '" is not a recognised domain extension. Please use a real email address.' };
            }
            return { ok: true, msg: '' };
        }

        /* ── Build the icon + inline error elements once the DOM is ready ── */
        $(function() {
            var $field = $('#billing_email');
            if ( !$field.length ) return;

            // Wrap the input so we can position the icon relative to it
            $field.wrap('<span class="wc-email-field-wrap"></span>');
            var $wrap = $field.closest('.wc-email-field-wrap');

            // Icon bubble
            var $icon = $('<span class="wc-email-icon" aria-hidden="true"></span>').appendTo($wrap);

            // Inline error message (inserted after the wrapper, inside the .form-row)
            var $error = $('<span class="wc-email-inline-error" role="alert"></span>').insertAfter($wrap);

            /* ── Apply / clear visual state ── */
         function applyState(result, isEmpty) {
    $wrap.removeClass('wc-email--invalid wc-email--valid');
    $icon.removeClass('wc-email-icon--invalid wc-email-icon--valid wc-email-icon--visible').empty();
    $error.text('');

    if ( isEmpty ) return; // no state while field is blank

    if ( result.ok ) {
        $wrap.addClass('wc-email--valid');
        $icon.addClass('wc-email-icon--valid wc-email-icon--visible').html(
            '<svg width=28" height="28" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg">' +
                '<mask id="mask0_779_3580" style="mask-type:luminance" maskUnits="userSpaceOnUse" x="0" y="0" width="15" height="15">' +
                    '<path d="M7.25 13.5C8.07091 13.501 8.88393 13.3398 9.64235 13.0257C10.4008 12.7115 11.0896 12.2506 11.6694 11.6694C12.2506 11.0896 12.7115 10.4008 13.0257 9.64235C13.3398 8.88393 13.501 8.07091 13.5 7.25C13.501 6.4291 13.3398 5.61608 13.0257 4.85766C12.7115 4.09924 12.2506 3.41037 11.6694 2.83063C11.0896 2.24943 10.4008 1.78851 9.64235 1.47435C8.88393 1.1602 8.07091 0.998992 7.25 1C6.4291 0.998992 5.61608 1.1602 4.85766 1.47435C4.09924 1.78851 3.41037 2.24943 2.83063 2.83063C2.24943 3.41037 1.78851 4.09924 1.47435 4.85766C1.1602 5.61608 0.998992 6.4291 1 7.25C0.998992 8.07091 1.1602 8.88393 1.47435 9.64235C1.78851 10.4008 2.24943 11.0896 2.83063 11.6694C3.41037 12.2506 4.09924 12.7115 4.85766 13.0257C5.61608 13.3398 6.4291 13.501 7.25 13.5Z" fill="white" stroke="white" stroke-width="2" stroke-linejoin="round"/>' +
                '</mask>' +
                '<g mask="url(#mask0_779_3580)">' +
                    '<path d="M0 0H15V15H0V0Z" fill="#00FE6C"/>' +
                    '<line x1="6" y1="9.29289" x2="10.2929" y2="5" stroke="white" stroke-linecap="round"/>' +
                    '<line x1="3.70711" y1="7" x2="5" y2="8.29289" stroke="white" stroke-linecap="round"/>' +
                '</g>' +
            '</svg>'
        );
    } else {
        $wrap.addClass('wc-email--invalid');
        $icon.addClass('wc-email-icon--invalid wc-email-icon--visible').html('&#33;'); // !
        $error.text(result.msg);
    }
}

            /* ── Real-time validation: typing (debounced) + blur (immediate) ── */
            var debounceTimer;

            $field.on('input blur', function(e) {
                clearTimeout(debounceTimer);
                var value = $field.val().trim();
                var delay = (e.type === 'blur') ? 0 : 500;

                debounceTimer = setTimeout(function() {
                    applyState( validateEmail(value), value === '' );
                }, delay);
            });

            /* ── Block Place Order + scroll to field on failure ── */
            $(document.body).on('checkout_place_order', function() {
                var value  = $field.val().trim();
                var result = validateEmail(value);

                applyState(result, value === '');

                if ( !result.ok ) {
                    $('html, body').animate({
                        scrollTop: $wrap.offset().top - 120
                    }, 400);
                    return false;
                }

                return true;
            });
        });

    }(jQuery));
    </script>
    <?php
}

/**
 * "Secure 256-bit SSL encrypted payment" reassurance note under the Place Order
 * button (design-files/projecttimber-checkout.html → .co-secure). WooCommerce core
 * doesn't output this, so add it after the submit button. Styled by
 * assets/css/checkout.css (.co-secure).
 */
add_action(
    'woocommerce_review_order_after_submit',
    function () {
        ?>
        <div class="co-secure"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg> <?php esc_html_e( 'Secure 256-bit SSL encrypted payment', 'woocommerce' ); ?></div>
        <?php
    },
    20
);

/**
 * Place-order button: show "Place order · <total> →" like the design
 * (design-files/projecttimber-checkout.html). WooCommerce's button only prints
 * "Place order", so rewrite its label on load and after every AJAX order-review
 * update (updated_checkout) so the total never goes stale. Only the visible
 * label changes — the button name/value used for submission is untouched.
 */
add_action(
    'wp_footer',
    function () {
        if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ) {
            return;
        }
        ?>
        <script>
        (function () {
          var ARROW = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>';
          function label() {
            var btn = document.getElementById('place_order');
            if (!btn) return;
            var base = btn.getAttribute('data-pt-label');
            if (base == null) { base = (btn.textContent || 'Place order').trim() || 'Place order'; btn.setAttribute('data-pt-label', base); }
            var t = document.querySelector('#order_review .grand .v')
                 || document.querySelector('#order_review .order-total .woocommerce-Price-amount');
            var total = t ? t.textContent.trim() : '';
            btn.innerHTML = base + (total ? (' · ' + total) : '') + ' ' + ARROW;
          }
          if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', label);
          else label();
          if (window.jQuery) jQuery(document.body).on('updated_checkout', label);
        })();
        </script>
        <?php
    }
);