<?php
/**
 * Campaign: 10% off Paint Colour & Colour Trim options (on top of any live discount).
 *
 * Mirrors the free-shelf / wall-upgrade engine: prices are NEVER touched. The
 * configurator shows each PRICED paint/trim option as `~~£X~~ £Y` (Y = X minus the
 * live display discount, then an extra 10%), and checkout nets the extra 10% out with a
 * VAT-correct negative fee — so it STACKS on top of the site-wide coupon (e.g. GET20).
 *
 * Paint/trim options are matched by their COMPONENT title (/paint|trim/i), not by the
 * option name (colour names don't contain "paint"/"trim"). Opt-in: only the option the
 * customer actually chose is discounted.
 *
 * Date-gated (Europe/London) + admin preview: live for everyone inside the window, and
 * always for admins (manage_woocommerce) so it can be tested before go-live.
 *
 * @package pt-theme-2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Public window. PLACEHOLDER — far future so NO customer sees it yet; admins preview
// regardless. Set these to the real promo dates to schedule the public go-live.
if ( ! defined( 'PT_PAINT_TRIM_START' ) ) {
	define( 'PT_PAINT_TRIM_START', '2099-01-01 00:00:00' );
}
if ( ! defined( 'PT_PAINT_TRIM_END' ) ) {
	define( 'PT_PAINT_TRIM_END', '2099-01-02 23:59:59' );
}

/** The discount percentage off paint/trim options (filterable). */
function pt_paint_trim_pct() {
	return (float) apply_filters( 'pt_paint_trim_pct', 10 );
}

/** Is the public window currently open (Europe/London)? */
function pt_paint_trim_window_open() {
	try {
		$tz    = new DateTimeZone( 'Europe/London' );
		$now   = new DateTime( 'now', $tz );
		$start = new DateTime( PT_PAINT_TRIM_START, $tz );
		$end   = new DateTime( PT_PAINT_TRIM_END, $tz );
	} catch ( \Exception $e ) {
		return false;
	}
	return ( $now >= $start && $now <= $end );
}

/**
 * Whether the paint/trim promo should act for the CURRENT viewer: live for all inside
 * the window, otherwise admins only (preview/test).
 *
 * To hard-stop the promo for EVERYONE (incl. admin preview) between runs, add
 * `return false;` as the first line below — the same kill-switch the weekend deal uses.
 *
 * @return bool
 */
function pt_paint_trim_campaign_active() {
	return pt_paint_trim_window_open() || current_user_can( 'manage_woocommerce' );
}

/**
 * Whether a composite COMPONENT title is a Paint Colour / Colour Trim step.
 *
 * @param string $title Component title (any case).
 * @return bool
 */
function pt_paint_trim_is_colour_title( $title ) {
	return (bool) preg_match( '/paint|trim/i', (string) $title );
}

/** The promo END as a Unix timestamp (for any JS countdown). */
function pt_paint_trim_end_timestamp() {
	try {
		$end = new DateTime( PT_PAINT_TRIM_END, new DateTimeZone( 'Europe/London' ) );
		return $end->getTimestamp();
	} catch ( \Exception $e ) {
		return 0;
	}
}

/**
 * Apply the paint/trim 10% discount as a VAT-correct negative fee.
 *
 * For each composite container in the cart, find child options whose COMPONENT is a
 * Paint Colour / Colour Trim step (by title) and that carry a real upcharge, and remove
 * `pct%` of their ex-VAT price. A negative taxable fee also drops the matching VAT, so
 * the customer's inc-VAT total falls by pct% of the option's inc-VAT price. Stacks on
 * top of any coupon already in the cart.
 *
 * @param WC_Cart $cart Cart being calculated.
 */
function pt_paint_trim_fee( $cart ) {
	// Storefront cart/checkout only — never admin-area order editing.
	if ( is_admin() && ! wp_doing_ajax() ) {
		return;
	}
	if ( ! is_a( $cart, 'WC_Cart' ) || ! pt_paint_trim_campaign_active() ) {
		return;
	}
	$pct = (float) pt_paint_trim_pct();
	if ( $pct <= 0 ) {
		return;
	}

	$items       = $cart->get_cart();
	$discount_ex = 0.0;

	foreach ( $items as $item ) {
		// Composite CONTAINER items only (they carry the children list).
		if ( empty( $item['composite_children'] ) || ! is_array( $item['composite_children'] ) || empty( $item['data'] ) ) {
			continue;
		}
		$parent = $item['data'];

		// Map component id => lowercased title, to spot the paint/trim steps.
		$title_map  = array();
		$components = ( is_object( $parent ) && is_callable( array( $parent, 'get_components' ) ) ) ? $parent->get_components() : array();
		if ( is_array( $components ) ) {
			foreach ( $components as $cid => $comp ) {
				if ( is_object( $comp ) && is_callable( array( $comp, 'get_title' ) ) ) {
					$title_map[ (string) $cid ] = strtolower( trim( (string) $comp->get_title() ) );
				}
			}
		}

		$pqty = max( 1, (int) $item['quantity'] );

		foreach ( $item['composite_children'] as $ckey ) {
			if ( empty( $items[ $ckey ]['data'] ) ) {
				continue;
			}
			$comp_id = isset( $items[ $ckey ]['composite_item'] ) ? (string) $items[ $ckey ]['composite_item'] : '';
			$title   = isset( $title_map[ $comp_id ] ) ? $title_map[ $comp_id ] : '';
			if ( ! pt_paint_trim_is_colour_title( $title ) ) {
				continue;
			}
			// Read the option's REAL price fresh, ex VAT for a taxable fee.
			$fresh = wc_get_product( $items[ $ckey ]['data']->get_id() );
			if ( ! $fresh ) {
				continue;
			}
			$ex = (float) wc_get_price_excluding_tax( $fresh );
			if ( $ex > 0 ) {
				$discount_ex += $ex * ( $pct / 100 ) * $pqty;
			}
		}
	}

	if ( $discount_ex > 0.005 ) {
		$cart->add_fee( 'Paint & Trim promo — ' . (int) round( $pct ) . '% off', -1 * $discount_ex, true, '' );
	}
}
add_action( 'woocommerce_cart_calculate_fees', 'pt_paint_trim_fee' );
