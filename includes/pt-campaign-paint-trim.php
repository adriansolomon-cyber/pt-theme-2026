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
 * Whether the promo window includes a Saturday or Sunday — drives the "Weekend deal"
 * vs "Week deal" tag so the wording tracks the configured dates, not a hard-coded string.
 *
 * @return bool
 */
function pt_paint_trim_window_spans_weekend() {
	try {
		$tz    = new DateTimeZone( 'Europe/London' );
		$start = new DateTime( PT_PAINT_TRIM_START, $tz );
		$end   = new DateTime( PT_PAINT_TRIM_END, $tz );
	} catch ( \Exception $e ) {
		return false;
	}
	if ( $end < $start ) {
		return false;
	}
	// Walk each calendar day in the window (bounded to 31 iterations).
	$day = new DateTime( $start->format( 'Y-m-d' ), $tz );
	for ( $i = 0; $i < 31 && $day <= $end; $i++ ) {
		if ( (int) $day->format( 'N' ) >= 6 ) { // 6 = Sat, 7 = Sun
			return true;
		}
		$day->modify( '+1 day' );
	}
	return false;
}

/** Short deal tag: "Weekend deal" when the window spans a weekend, else "Week deal" (filterable). */
function pt_paint_trim_tag_label() {
	$label = pt_paint_trim_window_spans_weekend() ? 'Weekend deal' : 'Week deal';
	return (string) apply_filters( 'pt_paint_trim_tag_label', $label );
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
			// Read the option's REAL price fresh. The configurator discounts the
			// VAT-INCLUSIVE price and rounds each option to whole £ (round60); mirror
			// that here so the checkout fee matches the configurator, then convert back
			// to ex VAT for the taxable fee — WC re-adds the VAT, so the DISPLAYED amount
			// lands on the rounded whole-£ figure (e.g. −£6, not −£6.30).
			$fresh = wc_get_product( $items[ $ckey ]['data']->get_id() );
			if ( ! $fresh ) {
				continue;
			}
			$ex   = (float) wc_get_price_excluding_tax( $fresh );
			$incl = (float) wc_get_price_including_tax( $fresh );
			if ( $incl <= 0 ) {
				continue;
			}
			// Per-option extra saving = round60(after site discount) − round60(after the
			// extra paint/trim %), on the VAT-inclusive price — identical to the
			// configurator's "Save £X" so the config total and checkout total agree.
			$site_pct = ( is_object( $parent ) && function_exists( 'pt_product_discount_pct' ) ) ? (float) pt_product_discount_pct( $parent->get_id() ) : 0.0;
			$disc_p   = $incl * ( 1 - $site_pct / 100 );
			$promo_p  = $disc_p - $incl * ( $pct / 100 );
			$r_disc   = function_exists( 'pt_round_price_60' ) ? (float) pt_round_price_60( $disc_p ) : (float) round( $disc_p );
			$r_promo  = function_exists( 'pt_round_price_60' ) ? (float) pt_round_price_60( $promo_p ) : (float) round( $promo_p );
			$fee_incl = max( 0.0, $r_disc - $r_promo ) * $pqty;
			if ( $fee_incl > 0 ) {
				$rate         = ( $ex > 0 ) ? ( $incl / $ex ) : 1.2; // ≈1.20 for standard-rated; back out VAT for the taxable fee
				$discount_ex += ( $rate > 0 ) ? ( $fee_incl / $rate ) : $fee_incl;
			}
		}
	}

	if ( $discount_ex > 0.005 ) {
		$cart->add_fee( 'Paint & Trim promo — ' . (int) round( $pct ) . '% off', -1 * $discount_ex, true, '' );
	}
}
add_action( 'woocommerce_cart_calculate_fees', 'pt_paint_trim_fee' );

/** The promo pill label (filterable). */
function pt_paint_trim_label() {
	$pct = (int) round( pt_paint_trim_pct() );
	return (string) apply_filters( 'pt_paint_trim_label', 'Extra ' . $pct . '% off Paint &amp; Colour Trim' );
}

/** The promo pill — a charcoal label sitting inside the yellow sale box (like the shelf pill). */
function pt_paint_trim_badge_html() {
	return '<span class="pt-wkbadge pt-ptbadge"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20 7h-2.2A3 3 0 0 0 12 4.4 3 3 0 0 0 6.2 7H4a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h7V7h2v5h7a1 1 0 0 0 1-1V8a1 1 0 0 0-1-1zM9 7a1 1 0 1 1 1-1v1H9zm6 0h-1V6a1 1 0 1 1 1 1zM4 13v7a1 1 0 0 0 1 1h6v-8H4zm9 8h6a1 1 0 0 0 1-1v-7h-7v8z"/></svg>' . pt_paint_trim_label() . '</span>';
}

/**
 * Live "offer ends in …" countdown markup (filled by the footer ticker). Reuses the
 * weekend deal's .pt-wkcountdown styling/ticker; the ticker hides implausibly-distant
 * ends (placeholder dates) so an unscheduled promo never shows a silly countdown.
 */
function pt_paint_trim_countdown_html() {
	$ts = pt_paint_trim_end_timestamp();
	// Admin preview with no live window yet (placeholder dates) — show a sample 2-day
	// countdown so the visual can be reviewed. A real public window shows its real end.
	if ( ! pt_paint_trim_window_open() && current_user_can( 'manage_woocommerce' ) ) {
		$ts = time() + 2 * DAY_IN_SECONDS;
	}
	if ( ! $ts ) {
		return '';
	}
	return '<div class="pt-wkcountdown" data-ends="' . (int) $ts . '" hidden><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm1 10.6 3.3 1.9-.9 1.5L11 13V7h2z"/></svg> Offer ends in <b class="pt-wkcd-t">—</b></div>';
}

/** The paint/trim promo label + countdown, to drop INSIDE a yellow sale box. */
function pt_paint_trim_box_inner_html() {
	if ( ! pt_paint_trim_campaign_active() ) {
		return '';
	}
	return pt_paint_trim_badge_html() . pt_paint_trim_countdown_html();
}

/**
 * One-second ticker that fills every .pt-wkcountdown on the page. Only emitted while the
 * promo is active, and a no-op if the weekend deal already printed its own identical
 * ticker (guarded by window.__ptCountdownTicker). Hides ends more than ~400 days out so
 * a placeholder/unscheduled window shows no countdown.
 */
add_action(
	'wp_footer',
	static function () {
		if ( ! pt_paint_trim_campaign_active() ) {
			return;
		}
		?>
<script>
(function(){
  if(window.__ptCountdownTicker) return; window.__ptCountdownTicker=1;
  function fmt(ms){ if(ms<0)ms=0; var s=Math.floor(ms/1000),d=Math.floor(s/86400);s-=d*86400;
    var h=Math.floor(s/3600);s-=h*3600;var m=Math.floor(s/60);s-=m*60;
    return (d>0?d+'d ':'')+h+'h '+m+'m '+s+'s'; }
  function tick(){ var now=Date.now();
    document.querySelectorAll('.pt-wkcountdown[data-ends]').forEach(function(el){
      var ends=parseInt(el.getAttribute('data-ends'),10)*1000;
      if(isNaN(ends)||ends-now<=0||(ends-now)>400*86400000){ el.hidden=true; return; }
      var t=el.querySelector('.pt-wkcd-t'); if(t) t.textContent=fmt(ends-now); el.hidden=false;
    });
  }
  tick(); setInterval(tick,1000);
})();
</script>
		<?php
	},
	50
);
