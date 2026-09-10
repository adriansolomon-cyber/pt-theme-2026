<?php
/**
 * Wall free-upgrade campaign (Grandmaster) — cart/checkout side.
 *
 * Makes the 16mm wall option cost the customer £0 WITHOUT editing any price:
 * for each Grandmaster composite in the cart that has the 16mm wall selected,
 * a VAT-correct negative fee equal to the 16mm wall option's real price is
 * applied — nets the wall's contribution back out so the customer pays £0 for
 * it, shown as a "16mm Wall Thickness — Free upgrade" line in the cart drawer
 * and checkout totals. The wall itself already renders as "Included" in the
 * per-component breakdown (composite children are £0 line items).
 *
 * Gated: live for everyone once PT_WALL_UPGRADE_LIVE (constant) or the
 * pt_wall_upgrade_live option is on; always on for admins (manage_woocommerce)
 * so the campaign can be previewed before it goes public. The configurator side
 * (16mm default, £0 display, badge) lives in product.js via window.PT_WALL_UPGRADE.
 *
 * @package pt-theme-2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ── CAMPAIGN LIVE SWITCH ──────────────────────────────────────────────────
// true  = the 16mm wall free-upgrade is LIVE for all customers.
// false = admins-only preview (the campaign still shows for manage_woocommerce).
// Guarded so a define() in wp-config.php still overrides this (wp-config loads first).
if ( ! defined( 'PT_WALL_UPGRADE_LIVE' ) ) {
	define( 'PT_WALL_UPGRADE_LIVE', true );
}

/**
 * Whether the wall free-upgrade campaign should act for the CURRENT viewer:
 * live for all when the switch is on, otherwise admins only (preview).
 *
 * @return bool
 */
function pt_wall_campaign_active() {
	$live = defined( 'PT_WALL_UPGRADE_LIVE' )
		? (bool) PT_WALL_UPGRADE_LIVE
		: ( function_exists( 'get_option' ) ? (bool) get_option( 'pt_wall_upgrade_live', false ) : false );
	return $live || current_user_can( 'manage_woocommerce' );
}

/**
 * Apply the free-upgrade discount as a VAT-correct negative fee.
 *
 * @param WC_Cart $cart Cart being calculated.
 */
function pt_wall_free_upgrade_fee( $cart ) {
	// Don't touch admin-area order editing; only the storefront cart/checkout.
	if ( is_admin() && ! wp_doing_ajax() ) {
		return;
	}
	if ( ! is_a( $cart, 'WC_Cart' ) || ! function_exists( 'pt_is_grandmaster_product' ) || ! pt_wall_campaign_active() ) {
		return;
	}

	$items       = $cart->get_cart();
	$discount_ex = 0.0;

	foreach ( $items as $item ) {
		// Composite CONTAINER items only (they carry the children list).
		if ( empty( $item['composite_children'] ) || ! is_array( $item['composite_children'] ) || empty( $item['data'] ) ) {
			continue;
		}
		if ( ! pt_is_grandmaster_product( $item['data']->get_id() ) ) {
			continue;
		}
		$pqty = max( 1, (int) $item['quantity'] );

		foreach ( $item['composite_children'] as $ckey ) {
			if ( empty( $items[ $ckey ]['data'] ) ) {
				continue;
			}
			$child = $items[ $ckey ]['data'];
			$name  = (string) $child->get_name();
			// The 16mm WALL option only — never a 16mm elsewhere (floor is T&G, roof is felt).
			if ( ! preg_match( '/16\s*mm/i', $name ) || ! preg_match( '/shiplap|cladding/i', $name ) ) {
				continue;
			}
			// Read the option's REAL price from a fresh product (composite children can
			// carry a £0 cart price under per-item pricing), ex VAT for a taxable fee.
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
		// customer's inc-VAT total drops by the wall's full inc-VAT price.
		$cart->add_fee( '16mm Wall Thickness — Free upgrade', -1 * $discount_ex, true, '' );
	}
}
add_action( 'woocommerce_cart_calculate_fees', 'pt_wall_free_upgrade_fee', 20, 1 );
