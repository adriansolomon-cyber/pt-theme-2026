<?php
/**
 * Free shelf upgrade campaign (weekend) — cart/checkout side.
 *
 * Makes the "3ft Shelf Stack (4 Shelves)" shelf option cost the customer £0 WITHOUT
 * editing any price: for each NON-Insulated composite in the cart that has that shelf
 * option selected, a VAT-correct negative fee equal to the option's real price is
 * applied — netting its contribution back out. It is an OPT-IN add-on (default
 * "None"), so the fee only appears when the customer actually chooses the shelf.
 * Shown as a "3ft Shelf Stack — Free upgrade" line in the cart/checkout totals.
 *
 * Gated: live during the weekend date window (Europe/London), OR always for admins
 * (manage_woocommerce) so the campaign can be previewed/tested before it goes live.
 * The configurator side (£0 display + badge) lives in product.js via
 * window.PT_SHELF_UPGRADE. Mirrors includes/pt-campaign-wall-upgrade.php.
 *
 * @package pt-theme-2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Weekend window (Europe/London, inclusive of both days). One-line edit to move it;
// override in wp-config.php to change without a deploy.
if ( ! defined( 'PT_SHELF_UPGRADE_START' ) ) {
	define( 'PT_SHELF_UPGRADE_START', '2026-10-03 00:00:00' );
}
if ( ! defined( 'PT_SHELF_UPGRADE_END' ) ) {
	define( 'PT_SHELF_UPGRADE_END', '2026-10-04 23:59:59' );
}

/** Insulated product_cat id to EXCLUDE (filterable). The term or any descendant. */
function pt_shelf_insulated_cat_id() {
	return (int) apply_filters( 'pt_shelf_insulated_cat_id', 1639 );
}

/** Is the weekend window currently open (Europe/London)? */
function pt_shelf_window_open() {
	try {
		$tz    = new DateTimeZone( 'Europe/London' );
		$now   = new DateTime( 'now', $tz );
		$start = new DateTime( PT_SHELF_UPGRADE_START, $tz );
		$end   = new DateTime( PT_SHELF_UPGRADE_END, $tz );
	} catch ( \Exception $e ) {
		return false;
	}
	return ( $now >= $start && $now <= $end );
}

/**
 * Whether the free-shelf campaign should act for the CURRENT viewer: live for all
 * inside the window, otherwise admins only (preview/test).
 *
 * @return bool
 */
function pt_shelf_campaign_active() {
	return pt_shelf_window_open() || current_user_can( 'manage_woocommerce' );
}

/**
 * The free-shelf TARGET option test. Matches "3ft Shelf Stack (4 Shelves)" by name
 * (buildings carry this option under the same name), never other shelf options.
 *
 * @param string $name Option/child product name.
 * @return bool
 */
function pt_shelf_is_target_name( $name ) {
	$name = (string) $name;
	return (bool) ( preg_match( '/3\s*ft/i', $name )
		&& preg_match( '/shelf/i', $name )
		&& preg_match( '/4\s*shel/i', $name ) );
}

/**
 * Whether a product is in the Insulated range — the excluded category term or any
 * descendant of it. Scopes the campaign to "all products EXCEPT Insulated".
 *
 * @param int $product_id Product ID.
 * @return bool
 */
function pt_shelf_product_is_insulated( $product_id ) {
	$product_id = (int) $product_id;
	$target     = pt_shelf_insulated_cat_id();
	if ( ! $product_id || ! $target || ! function_exists( 'get_the_terms' ) ) {
		return false;
	}
	$terms = get_the_terms( $product_id, 'product_cat' );
	if ( ! $terms || is_wp_error( $terms ) ) {
		return false;
	}
	foreach ( $terms as $t ) {
		if ( (int) $t->term_id === $target ) {
			return true;
		}
		foreach ( get_ancestors( $t->term_id, 'product_cat' ) as $aid ) {
			if ( (int) $aid === $target ) {
				return true;
			}
		}
	}
	return false;
}

/**
 * Apply the free-shelf discount as a VAT-correct negative fee.
 *
 * @param WC_Cart $cart Cart being calculated.
 */
function pt_shelf_free_upgrade_fee( $cart ) {
	// Don't touch admin-area order editing; only the storefront cart/checkout.
	if ( is_admin() && ! wp_doing_ajax() ) {
		return;
	}
	if ( ! is_a( $cart, 'WC_Cart' ) || ! pt_shelf_campaign_active() ) {
		return;
	}

	$items       = $cart->get_cart();
	$discount_ex = 0.0;

	foreach ( $items as $item ) {
		// Composite CONTAINER items only (they carry the children list).
		if ( empty( $item['composite_children'] ) || ! is_array( $item['composite_children'] ) || empty( $item['data'] ) ) {
			continue;
		}
		// "All products EXCEPT Insulated".
		if ( pt_shelf_product_is_insulated( $item['data']->get_id() ) ) {
			continue;
		}
		$pqty = max( 1, (int) $item['quantity'] );

		foreach ( $item['composite_children'] as $ckey ) {
			if ( empty( $items[ $ckey ]['data'] ) ) {
				continue;
			}
			$child = $items[ $ckey ]['data'];
			if ( ! pt_shelf_is_target_name( $child->get_name() ) ) {
				continue;
			}
			// Read the option's REAL price fresh (composite children can carry a £0 cart
			// price under per-item pricing), ex VAT for a taxable fee.
			$fresh = wc_get_product( $child->get_id() );
			if ( ! $fresh ) {
				continue;
			}
			$ex = (float) wc_get_price_excluding_tax( $fresh );
			if ( $ex > 0 ) {
				$discount_ex += $ex * $pqty;
			}
		}
	}

	if ( $discount_ex > 0.005 ) {
		// Negative + taxable → WooCommerce also removes the matching VAT, so the
		// customer's inc-VAT total drops by the shelf's full inc-VAT price.
		$cart->add_fee( '3ft Shelf Stack — Free upgrade', -1 * $discount_ex, true, '' );
	}
}
add_action( 'woocommerce_cart_calculate_fees', 'pt_shelf_free_upgrade_fee', 20, 1 );
