<?php

//add_filter('woocommerce_product_tabs', '__return_empty_array', 98);
add_filter('woocommerce_single_product_carousel_options', 'ud_update_woo_flexslider_options');
// Ensure cart contents update when products are added to the cart via AJAX (place the following in functions.php)
add_filter('add_to_cart_fragments', 'woocommerce_header_add_to_cart_fragment');
add_action('init', 'pt_update_woocommerce_version');

remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40);
remove_action('woocommerce_sidebar', 'woocommerce_get_sidebar', 10);
remove_action('woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20);

add_filter('woocommerce_composite_add_to_cart_form_settings', 'disable_relative_to_default', 10, 2);

function disable_relative_to_default($settings, $composite) {

    foreach ($settings['price_display_data'] as $component => $value) {
        if ('relative' === $settings['price_display_data'][$component]['format']) {
            $settings['price_display_data'][$component]['is_relative_to_default'] = 'no';
        }
    }

    return $settings;
}



//remove_action( 'woocommerce_composite_before_components_paged', 'wc_cp_pagination', 15, 2); 
add_action('woocommerce_composite_after_components_paged', 'wc_cp_paginationr', 51, 2);

function wc_cp_paginationr($components, $product) {

    $layout_variation = $product->get_composite_layout_style_variation();

    if ('componentized' !== $layout_variation) {

        wc_get_template('single-product/composite-pagination.php', array(
            'product' => $product,
            'product_id' => $product->get_id(),
            'components' => $components
                ), '', WC_CP()->plugin_path() . '/templates/');
    }
}

// add_action( 'pre_get_posts', function ( $query ) {
//     if ( is_product() && 'product' == get_post_type() ) {
//         // It's the main query for a category archive.
//         // Let's change the query for category archives.
//         $query->set( 'posts_per_page', 50 );
//     }
//   } );


add_filter('woocommerce_add_to_cart_redirect', 'redirect_checkout_add_cart');

function redirect_checkout_add_cart() {
    return wc_get_checkout_url();
}

function ud_update_woo_flexslider_options($options) {

    $options['directionNav'] = true;
    $options['controlNav'] = true;


    return $options;
}


function woocommerce_header_add_to_cart_fragment($fragments) {
    global $woocommerce;

    ob_start();
    ?>
    <a class="cart-contents" href="<?php echo $woocommerce->cart->get_cart_url(); ?>" title="<?php _e('View your shopping cart', 'woothemes'); ?>"><?php echo sprintf(_n('%d item', '%d items', $woocommerce->cart->cart_contents_count, 'woothemes'), $woocommerce->cart->cart_contents_count); ?> - <?php echo $woocommerce->cart->get_cart_total(); ?></a>
    <?php
    $fragments['a.cart-contents'] = ob_get_clean();

    return $fragments;
}
/**
 * Cart / checkout thumbnail: show the SELECTED SIZE's image, not the parent.
 *
 * A composite building in the cart is a container line item with child line
 * items (size, floor, windows…). The size child is the sub-product whose title
 * is "N x N" and it carries its own product image. This swaps the container's
 * thumbnail for the size child's image so the basket/checkout shows the exact
 * size the customer configured. Falls back to the parent image when the size
 * child has no image of its own. Applies to the WC cart page and the custom
 * checkout review-order.php (both run the woocommerce_cart_item_thumbnail
 * filter). The cart drawer is handled client-side in assets/js/mini-cart.js.
 *
 * @param string $thumbnail      Existing thumbnail HTML (parent image).
 * @param array  $cart_item      Cart item (the composite container).
 * @param string $cart_item_key  Cart item key.
 * @return string
 */
add_filter( 'woocommerce_cart_item_thumbnail', 'pt_composite_size_thumbnail', 10, 3 );
function pt_composite_size_thumbnail( $thumbnail, $cart_item, $cart_item_key ) {

    if ( empty( $cart_item['composite_children'] ) || ! is_array( $cart_item['composite_children'] ) ) {
        return $thumbnail;
    }
    if ( ! WC()->cart ) {
        return $thumbnail;
    }

    $cart = WC()->cart->get_cart();

    foreach ( $cart_item['composite_children'] as $child_key ) {
        if ( empty( $cart[ $child_key ]['data'] ) ) {
            continue;
        }
        $child = $cart[ $child_key ]['data'];
        // Size sub-products are titled "N x N" (e.g. "12 x 8").
        if ( preg_match( '/^\d+\s*x\s*\d+$/i', trim( $child->get_title() ) ) ) {
            if ( $child->get_image_id() ) {
                return $child->get_image( 'woocommerce_thumbnail' );
            }
            break; // size found but has no image — keep the parent thumbnail.
        }
    }

    return $thumbnail;
}

/**
 * Round coupon / voucher discounts to whole pounds.
 *
 * Product prices are whole pounds, so the site reads as "rounded" — but a
 * percentage voucher (e.g. 10% of £1,751 = £175.10) reintroduces pennies, which
 * then show as floating decimals on the discount line and order total at
 * checkout / in the cart drawer. Rounding the discount itself (not just the
 * display) keeps the discount, the total AND the charged amount whole and
 * consistent — no display-vs-charge mismatch. Applies to every coupon,
 * including the auto-applied vouchers (av_* in wc-custom-checkout-functions.php).
 *
 * @param float      $discount           Discount amount for this line/item.
 * @param float      $discounting_amount Amount being discounted.
 * @param array|null $cart_item          Cart item (null for order-level).
 * @param bool       $single             Whether this is a single-item amount.
 * @param WC_Coupon  $coupon             The coupon.
 * @return float
 */
add_filter( 'woocommerce_coupon_get_discount_amount', 'pt_round_coupon_discount_amount', 10, 5 );
function pt_round_coupon_discount_amount( $discount, $discounting_amount, $cart_item, $single, $coupon ) {
    return round( (float) $discount );
}

/**
 * Force WooCommerce Database Update
 */
function pt_update_woocommerce_version() {
    if (class_exists('WooCommerce')) {
        global $woocommerce;
        if (version_compare(get_option('woocommerce_db_version', null), $woocommerce->version, '!=')) {
            update_option('woocommerce_db_version', $woocommerce->version);
            if (!wc_update_product_lookup_tables_is_running()) {
                wc_update_product_lookup_tables();
            }
        }
    }
}







