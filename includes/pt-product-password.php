<?php
/**
 * PT — enforce WordPress password protection on products.
 * =============================================================================
 * This theme renders the PDP through the Store API / custom templates, not
 * `the_content()`, so WordPress's built-in password gate never fires on its own.
 * The page gate lives in single-product.php; this file adds the server-side
 * companions so a locked product also can't be bought or indexed:
 *
 *   • Blocks adding a password-protected product to the basket.
 *   • Marks the locked page noindex (it only shows a password form).
 *
 * NOT covered here (lives in the config mu-plugin, deployed separately): the
 * /wp-json/timber/v1/* configurator API and the category grid still read from
 * there. Add the same post_password_required() check in that endpoint so the
 * raw product data can't be fetched around the gate — see the snippet the
 * theme docs / handoff note references.
 *
 * Disable: comment its require in functions.php, or define PT_PRODUCT_PASSWORD=false.
 *
 * @package pt-theme-2026
 */

defined( 'ABSPATH' ) || exit;

if ( defined( 'PT_PRODUCT_PASSWORD' ) && ! PT_PRODUCT_PASSWORD ) {
	return;
}

/**
 * Is this product locked for the current visitor? Checks the product itself and,
 * for a composite/bundle child, its parent composite (that's where the password
 * is normally set).
 *
 * @param int $product_id Product ID.
 * @return bool
 */
function pt_product_is_locked( $product_id ) {
	$product_id = (int) $product_id;
	if ( ! $product_id ) {
		return false;
	}
	if ( post_password_required( $product_id ) ) {
		return true;
	}
	// Composite child → check the parent composite too.
	$parent_id = (int) wp_get_post_parent_id( $product_id );
	if ( $parent_id && post_password_required( $parent_id ) ) {
		return true;
	}
	return false;
}

// --- Block add-to-cart for a locked product. ---
add_filter(
	'woocommerce_add_to_cart_validation',
	static function ( $passed, $product_id, $quantity = 0, $variation_id = 0 ) {
		if ( pt_product_is_locked( $product_id ) || ( $variation_id && pt_product_is_locked( $variation_id ) ) ) {
			if ( function_exists( 'wc_add_notice' ) ) {
				wc_add_notice( esc_html__( 'This product is password protected and can’t be added to the basket.', 'woocommerce' ), 'error' );
			}
			return false;
		}
		return $passed;
	},
	10,
	4
);

// --- noindex the locked page (it only exposes a password form). ---
add_filter(
	'wpseo_robots_array',
	static function ( $robots ) {
		if ( is_singular( 'product' ) && post_password_required( get_queried_object_id() ) ) {
			$robots['index'] = 'noindex';
		}
		return $robots;
	},
	11,
	1
);
