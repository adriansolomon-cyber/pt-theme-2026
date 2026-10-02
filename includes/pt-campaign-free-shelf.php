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
 * Should the weekend "free shelf" promo badge show for this product/context?
 * Active campaign + not an Insulated product. Pass 0 to use the queried product.
 *
 * @param int $product_id Product ID (0 = current queried object).
 * @return bool
 */
function pt_shelf_badge_eligible( $product_id = 0 ) {
	if ( ! pt_shelf_campaign_active() ) {
		return false;
	}
	$product_id = $product_id ? (int) $product_id : (int) get_queried_object_id();
	return ! pt_shelf_product_is_insulated( $product_id );
}

/**
 * The green "Weekend Only Deal — FREE 3ft Shelf Stack" promo badge (mockup 3B/4B).
 *
 * @return string
 */
function pt_shelf_weekend_badge_html() {
	return '<span class="pt-wkbadge"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 7h-2.2A3 3 0 0 0 12 4.4 3 3 0 0 0 6.2 7H4a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h7V7h2v5h7a1 1 0 0 0 1-1V8a1 1 0 0 0-1-1zM9 7a1 1 0 1 1 1-1v1H9zm6 0h-1V6a1 1 0 1 1 1 1zM4 13v7a1 1 0 0 0 1 1h6v-8H4zm9 8h6a1 1 0 0 0 1-1v-7h-7v8z"/></svg>Weekend Only Deal — FREE 3ft Shelf Stack</span>';
}

/**
 * The weekend campaign END as a Unix timestamp (for the JS countdown).
 *
 * @return int
 */
function pt_shelf_end_timestamp() {
	try {
		$end = new DateTime( PT_SHELF_UPGRADE_END, new DateTimeZone( 'Europe/London' ) );
		return $end->getTimestamp();
	} catch ( \Exception $e ) {
		return 0;
	}
}

/**
 * Live "offer ends in …" countdown markup. Starts hidden; the wp_footer ticker
 * reveals and fills it (and hides it again once the deadline passes).
 *
 * @return string
 */
function pt_shelf_countdown_html() {
	$ts = pt_shelf_end_timestamp();
	if ( ! $ts ) {
		return '';
	}
	return '<div class="pt-wkcountdown" data-ends="' . (int) $ts . '" hidden><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm1 10.6 3.3 1.9-.9 1.5L11 13V7h2z"/></svg> Offer ends in <b class="pt-wkcd-t">—</b></div>';
}

/**
 * Standalone weekend promo box (badge + countdown) — for category/product pages
 * where the shelf offer applies but NO other promo banner (Hobbyist 20% / cladding)
 * is showing, so the offer still gets a surface of its own.
 *
 * @return string
 */
function pt_shelf_weekend_box_html() {
	return '<div class="pt-wkbox">'
		. pt_shelf_weekend_badge_html()
		. '<div class="pt-wkbox-head">A free 3ft Shelf Stack with your building</div>'
		. '<div class="pt-wkbox-note">Add our <b>3ft Shelf Stack (4 shelves)</b> to any building and it&rsquo;s included <b>free</b> this weekend &mdash; worth having for tools, pots and tins. Applied automatically at checkout, no code needed.</div>'
		. pt_shelf_countdown_html()
		. '<div class="pt-wkbox-trust">FREE PRESSURE TREATMENT &middot; 25 YEAR ANTI-ROT GUARANTEE &middot; FREE DELIVERY ON SELECTED POSTCODES*</div>'
		. '</div>';
}

/**
 * One-second ticker that fills every .pt-wkcountdown on the page. Only emitted
 * while the campaign is active, so it is a no-op otherwise.
 */
add_action(
	'wp_footer',
	static function () {
		if ( ! pt_shelf_campaign_active() ) {
			return;
		}
		?>
<script>
(function(){
  function fmt(ms){
    if(ms<0)ms=0;
    var s=Math.floor(ms/1000),d=Math.floor(s/86400);s-=d*86400;
    var h=Math.floor(s/3600);s-=h*3600;var m=Math.floor(s/60);s-=m*60;
    return (d>0?d+'d ':'')+h+'h '+m+'m '+s+'s';
  }
  function tick(){
    var now=Date.now();
    document.querySelectorAll('.pt-wkcountdown[data-ends]').forEach(function(el){
      var ends=parseInt(el.getAttribute('data-ends'),10)*1000;
      if(isNaN(ends)||ends-now<=0){ el.hidden=true; return; }
      var t=el.querySelector('.pt-wkcd-t'); if(t) t.textContent=fmt(ends-now);
      el.hidden=false;
    });
  }
  tick(); setInterval(tick,1000);
})();
</script>
		<?php
	},
	50
);

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
