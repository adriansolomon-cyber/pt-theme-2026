<?php

//add_filter('woocommerce_product_tabs', '__return_empty_array', 98);
add_filter('woocommerce_single_product_carousel_options', 'ud_update_woo_flexslider_options');
// Ensure cart contents update when products are added to the cart via AJAX (place the following in functions.php)
add_filter('add_to_cart_fragments', 'woocommerce_header_add_to_cart_fragment');
add_action('init', 'pt_update_woocommerce_version');
//add_filter('woocommerce_composite_front_end_params', 'sw_cp_display_total_string');

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







