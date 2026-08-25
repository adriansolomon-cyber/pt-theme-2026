<?php
/**
 * Site header — output by get_header().
 *
 * Converted from partials/header.html (client-side data-include). Design markup
 * is preserved verbatim; only the hrefs are wired to WP/Woo URLs and the
 * <head>/<body> open now run wp_head() / wp_body_open().
 *
 * @package pt-theme-2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pt_cats = array(
	'garden-sheds'                => 'Garden Sheds',
	'summerhouses'                => 'Summerhouses',
	'garden-offices'              => 'Garden Offices',
	'garden-workshops'            => 'Garden Workshops',
	'insulated-garden-buildings'  => 'Insulated Garden Buildings',
	'log-cabins'                  => 'Log Cabins',
	'greenhouses'                 => 'Greenhouses',
);
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
	<?php wp_head(); ?>
	<?php
	// Tracking price for the current product: the URL size's price when a size is
	// in the URL (e.g. /12-x-8/…), otherwise the composite "from" (cheapest size)
	// price. The dataLayer cleaner below backfills it into single-item events (e.g.
	// view_item) where a composite parent reports 0.00. Emitted head-level so it's
	// set before any tracking event fires.
	$pt_dl        = array( 'price' => 0, 'item_id' => '', 'variant' => '', 'category' => '' );
	// Event-name suffix the Stape plugin appends when "custom event names" is on
	// ('' otherwise). get_data_layer_event_name('') returns exactly that suffix.
	// Our custom pushes (add_to_cart, remove_from_cart, view_cart, select_item…)
	// use it so they land on the same GTM triggers as the plugin's events.
	$pt_dl_suffix = '';
	if ( class_exists( 'GTM_Server_Side_Helpers' ) && method_exists( 'GTM_Server_Side_Helpers', 'get_data_layer_event_name' ) ) {
		$pt_dl_suffix = (string) GTM_Server_Side_Helpers::get_data_layer_event_name( '' );
	}
	if ( is_singular( 'product' ) && function_exists( 'pt_product_tracking_item' ) && function_exists( 'wc_get_product' ) ) {
		$pt_dl_prod = wc_get_product( get_queried_object_id() );
		if ( $pt_dl_prod ) {
			$pt_dl = pt_product_tracking_item( $pt_dl_prod );
		}
	}
	?>
	<!-- Google Tag Manager -->
	<script>
	window.dataLayer = window.dataLayer || [];
	window.PT_PRODUCT_DL = <?php echo wp_json_encode( $pt_dl ); ?>;
	window.PT_DL_SUFFIX = <?php echo wp_json_encode( $pt_dl_suffix ); ?>;
	window.ptDlEvent = function(n){ return n + (window.PT_DL_SUFFIX || ''); };

	(function() {
	    var originalPush = window.dataLayer.push;

	    function looksLikeSize(name) {
	        name = String(name || '').trim();
	        return /^\d+\s*x\s*\d+$/i.test(name);
	    }

	    function isLikelyPart(item) {
	        var category = String(item.item_category || '').trim().toLowerCase();
	        return category === 'parts';
	    }

	    function isParentProduct(item) {
	        var category = String(item.item_category || '').trim().toLowerCase();
	        // These are the "header" product rows — they define the building type
	        return category !== 'parts' && category !== 'bundles' && !looksLikeSize(item.item_name);
	    }

	    function filterPurchaseStape(obj) {
	        if (!obj || !obj.ecommerce || !Array.isArray(obj.ecommerce.items)) {
	            return obj;
	        }

	        var items = obj.ecommerce.items;
	        if (!items.length) return obj;

	        // Step 1: Find all parent product rows (the named building products)
	        var parentIndexes = [];
	        items.forEach(function(item, i) {
	            if (isParentProduct(item)) {
	                parentIndexes.push(i);
	            }
	        });

	        // If we can't identify parents, fall back to original behaviour
	        if (!parentIndexes.length) return obj;

	        // Step 2: Slice items into groups — each group starts at a parent index
	        var groups = parentIndexes.map(function(startIdx, groupNum) {
	            var endIdx = parentIndexes[groupNum + 1] !== undefined ?
	                parentIndexes[groupNum + 1] :
	                items.length;
	            return items.slice(startIdx, endIdx);
	        });

	        // Step 3: For each group, find the size bundle and build a clean item
	        var cleanItems = [];

	        groups.forEach(function(group) {
	            var parent = group[0];
	            var parentName = parent.item_name;
	            var parentCategory = parent.item_category;

	            // Find the size item: looks like "18 x 10", has price > 0, not a Part
	            var sizeCandidates = group.filter(function(item) {
	                var price = parseFloat(item.price || 0);
	                return looksLikeSize(item.item_name) && price > 0 && !isLikelyPart(item);
	            });

	            // Fallback: any size-looking item in the group
	            if (!sizeCandidates.length) {
	                sizeCandidates = group.filter(function(item) {
	                    return looksLikeSize(item.item_name);
	                });
	            }

	            if (sizeCandidates.length) {
	                var sizeItem = sizeCandidates[0];
	                cleanItems.push({
	                    item_id: sizeItem.item_id,
	                    item_name: parentName,
	                    item_variant: sizeItem.item_name,
	                    item_category: parentCategory,
	                    item_category2: parent.item_category2 || '',
	                    price: sizeItem.price,
	                    quantity: sizeItem.quantity || 1,
	                    google_business_vertical: 'retail',
	                });
	            }
	        });

	        if (cleanItems.length) {
	            obj.ecommerce.items = cleanItems;
	        }

	        return obj;
	    }

	    // Backfill a real price into single-item events (e.g. view_item) where a
	    // composite parent reports 0.00 — use the server-provided "from" price.
	    // Multi-item events already get the size price via filterPurchaseStape.
	    function backfillZeroPrice(obj) {
	        if (!obj || !obj.ecommerce || !Array.isArray(obj.ecommerce.items)) return obj;
	        var dl = (typeof window.PT_PRODUCT_DL === 'object' && window.PT_PRODUCT_DL) ? window.PT_PRODUCT_DL : null;
	        var single = (dl && typeof dl.price === 'number' && dl.price > 0) ? dl.price : 0;   // current product page
	        var listMap = (typeof window.PT_LIST_PRICES === 'object' && window.PT_LIST_PRICES) ? window.PT_LIST_PRICES : null; // category list
	        if (!single && !listMap) return obj;
	        var pid = (typeof window.PT_PRODUCT_ID !== 'undefined' && window.PT_PRODUCT_ID != null) ? String(window.PT_PRODUCT_ID) : '';
	        var patched = false;
	        obj.ecommerce.items.forEach(function(item) {
	            var price = parseFloat(item.price || 0);
	            if (price > 0) return;
	            // 1) Current product page (view_item): set price + point id/variant at the size.
	            if (single && (!pid || String(item.item_id) === pid)) {
	                item.price = single.toFixed(2);
	                if (dl.item_id) item.item_id = String(dl.item_id);
	                if (dl.variant) item.item_variant = String(dl.variant);
	                patched = true;
	                return;
	            }
	            // 2) Category list (view_item_list): set price by product id, keep the item as-is.
	            if (listMap) {
	                var lp = listMap[String(item.item_id)];
	                if (lp > 0) { item.price = (+lp).toFixed(2); patched = true; }
	            }
	        });
	        // Fix the event-level value too when a single-item event was zero.
	        if (patched && single && obj.ecommerce.items.length === 1) {
	            var v = parseFloat(obj.ecommerce.value || 0);
	            if (!v || v <= 0) obj.ecommerce.value = single.toFixed(2);
	        }
	        return obj;
	    }

	    window.dataLayer.push = function() {
	        var args = Array.prototype.slice.call(arguments).map(filterPurchaseStape).map(backfillZeroPrice);
	        return originalPush.apply(this, args);
	    };
	})();

	! function() {
	    "use strict";

	    function l(e) {
	        for (var t = e, r = 0, n = document.cookie.split(";"); r < n.length; r++) {
	            var o = n[r].split("=");
	            if (o[0].trim() === t) return o[1]
	        }
	    }

	    function s(e) {
	        return localStorage.getItem(e)
	    }

	    function u(e) {
	        return window[e]
	    }

	    function A(e, t) {
	        e = document.querySelector(e);
	        return t ? null == e ? void 0 : e.getAttribute(t) : null == e ? void 0 : e.textContent
	    }
	    var e = window,
	        t = document,
	        r = "script",
	        n = "dataLayer",
	        o = "https://order.projecttimber.com",
	        a = "",
	        i = "9blvxkoys",
	        c = "3px2=aWQ9R1RNLTUyNEw5N1I3&page=2",
	        g = "cookie",
	        v = "user_identifier",
	        E = "",
	        d = !1;
	    try {
	        var d = !!g && (m = navigator.userAgent, !!(m = new RegExp("Version/([0-9._]+)(.*Mobile)?.*Safari.*").exec(
	                m))) && 16.4 <= parseFloat(m[1]),
	            f = "stapeUserId" === g,
	            I = d && !f ? function(e, t, r) {
	                void 0 === t && (t = "");
	                var n = {
	                        cookie: l,
	                        localStorage: s,
	                        jsVariable: u,
	                        cssSelector: A
	                    },
	                    t = Array.isArray(t) ? t : [t];
	                if (e && n[e])
	                    for (var o = n[e], a = 0, i = t; a < i.length; a++) {
	                        var c = i[a],
	                            c = r ? o(c, r) : o(c);
	                        if (c) return c
	                    } else console.warn("invalid uid source", e)
	            }(g, v, E) : void 0;
	        d = d && (!!I || f)
	    } catch (e) {
	        console.error(e)
	    }
	    var m = e,
	        g = (m[n] = m[n] || [], m[n].push({
	            "gtm.start": (new Date).getTime(),
	            event: "gtm.js"
	        }), t.getElementsByTagName(r)[0]),
	        v = I ? "&bi=" + encodeURIComponent(I) : "",
	        E = t.createElement(r),
	        f = (d && (i = 8 < i.length ? i.replace(/([a-z]{8}$)/, "kp$1") : "kp" + i), !d && a ? a : o);
	    E.async = !0, E.src = f + "/" + i + ".js?" + c + v, null != (e = g.parentNode) && e.insertBefore(E, g)
	}();
	</script>
	<!-- End Google Tag Manager -->
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://order.projecttimber.com/ns.html?id=GTM-524L97R7" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
<!-- MediaHawk dynamic call tracking -->
<script type='text/javascript'>
var _mhct = _mhct || [];
_mhct.push(['mhCampaignID', 'VA-13595']);
! function() {
    var c = document.createElement('script');
    c.type = 'text/javascript', c.async = !0, c.src = '//www.dynamicnumbers.mediahawk.co.uk/mhct.min.js';
    var i = document.getElementsByTagName('script')[0];
    i.parentNode.insertBefore(c, i)
}();
</script>
<!-- End MediaHawk -->
<a class="skip" href="#main">Skip to content</a>

<?php
/*
 * Promo bar with live countdown. Driven by the same ACF Options fields the old
 * theme used (template-parts/countdown-template.php):
 *   enable_countdown        — master on/off toggle
 *   set_countdown_end_date  — target datetime (its saved value carries the
 *                             timezone, e.g. "…23:00:00 GMT+01:00", so strtotime
 *                             resolves the correct UK moment)
 *   countdown_secondary_text— the message (e.g. "10% off … with code GM10")
 *   coupon_code             — highlighted as a chip inside the message
 * Digits render server-side (no flash); the inline script re-ticks each second,
 * flips to .urgent under 72h, and reverts the bar to the free-delivery message
 * on expiry. Falls back to free-delivery when the countdown is off/expired.
 */
$pt_free_delivery = 'FREE DELIVERY — <b>selected postcodes*</b>';
// The promo bar is only pinned/sticky on product pages (above the subnav). On all
// other pages it scrolls away as before.
$pt_promo_sticky  = ( function_exists( 'is_product' ) && is_product() ) ? ' promo-sticky' : '';
$pt_cd_enabled    = function_exists( 'get_field' ) ? (bool) get_field( 'enable_countdown', 'option' ) : false;
$pt_cd_raw        = ( $pt_cd_enabled && function_exists( 'get_field' ) ) ? (string) get_field( 'set_countdown_end_date', 'option' ) : '';
$pt_cd_end_ts     = ( '' !== trim( $pt_cd_raw ) ) ? strtotime( $pt_cd_raw ) : false;

if ( $pt_cd_end_ts && $pt_cd_end_ts > time() ) :
	// Message from ACF (countdown_secondary_text). It may contain markup such as
	// <b class="gm">GM10</b> to render the code as a chip, so allow safe HTML
	// (wp_kses_post) instead of escaping it. Falls back to the original copy.
	$pt_cd_msg_raw = (string) get_field( 'countdown_secondary_text', 'option' );
	if ( '' === trim( $pt_cd_msg_raw ) ) {
		$pt_cd_msg_raw = '10% off the Grandmaster range with code <b class="gm">GM10</b>';
	}
	$pt_cd_msg = wp_kses_post( $pt_cd_msg_raw );

	// Real UTC time() on both sides (PHP initial render + JS Date.now) so the
	// first tick doesn't jump.
	$pt_left = max( 0, $pt_cd_end_ts - time() );
	$pt_d = (int) floor( $pt_left / 86400 );
	$pt_h = (int) floor( ( $pt_left % 86400 ) / 3600 );
	$pt_m = (int) floor( ( $pt_left % 3600 ) / 60 );
	$pt_s = (int) ( $pt_left % 60 );
	$pt_urgent = ( $pt_left < 72 * 3600 ) ? ' urgent' : '';
?>
<div class="promo<?php echo $pt_urgent . $pt_promo_sticky; ?>" id="promoBar"
     data-cd-target="<?php echo esc_attr( $pt_cd_end_ts * 1000 ); ?>"
     data-cd-fallback="<?php echo esc_attr( $pt_free_delivery ); ?>">
  <div class="promo-in">
    <span class="promo-msg"><?php echo $pt_cd_msg; // already escaped above ?></span>
    <span class="promo-cd" id="promoCd" role="timer" aria-label="Offer ending soon">
      <span class="lab">ends in</span>
      <span class="u"><b id="cdD"><?php echo $pt_d; ?></b><i>d</i></span><span class="c">:</span>
      <span class="u"><b id="cdH"><?php printf( '%02d', $pt_h ); ?></b><i>h</i></span><span class="c">:</span>
      <span class="u"><b id="cdM"><?php printf( '%02d', $pt_m ); ?></b><i>m</i></span><span class="c">:</span>
      <span class="u"><b id="cdS"><?php printf( '%02d', $pt_s ); ?></b><i>s</i></span>
    </span>
  </div>
</div>
<script>
(function(){
  var bar=document.getElementById('promoBar'); if(!bar) return;
  var target=parseInt(bar.getAttribute('data-cd-target'),10); if(!target) return;
  var D=document.getElementById('cdD'),H=document.getElementById('cdH'),M=document.getElementById('cdM'),S=document.getElementById('cdS');
  var fallback=bar.getAttribute('data-cd-fallback')||'';
  function p(n){ return (n<10?'0':'')+n; }
  function tick(){
    var diff=target-Date.now();
    if(diff<=0){
      clearInterval(iv);
      if(fallback){ bar.classList.remove('urgent'); bar.innerHTML='<div class="promo-in"><span class="promo-msg">'+fallback+'</span></div>'; }
      else { bar.style.display='none'; }
      return;
    }
    var s=Math.floor(diff/1000), d=Math.floor(s/86400); s-=d*86400;
    var h=Math.floor(s/3600); s-=h*3600; var m=Math.floor(s/60); s-=m*60;
    if(D)D.textContent=d; if(H)H.textContent=p(h); if(M)M.textContent=p(m); if(S)S.textContent=p(s);
    bar.classList.toggle('urgent', diff < 72*3600*1000);
  }
  tick(); var iv=setInterval(tick,1000);
})();
</script>
<?php else : ?>
<div class="promo<?php echo $pt_promo_sticky; ?>"><?php echo wp_kses_post( $pt_free_delivery ); ?></div>
<?php endif; ?>
<?php if ( '' !== $pt_promo_sticky ) : ?>
<script>
/* PRODUCT PAGES ONLY: keep the sticky header/subnav sitting BELOW the pinned
   promo bar. Publishes the promo height as --pt-promo-h AND sets `top` inline on
   the sticky nav elements — the inline style overrides even a stale/cached
   base.css, so this can't be defeated by CDN caching. Only touches elements that
   are actually sticky (the mobile header is position:relative, so it's left
   alone). Recomputes on resize/wrap and when the countdown reverts/expires. */
(function(){
  var p=document.querySelector('.promo'); if(!p) return;
  function apply(){
    var h = p.offsetHeight;
    document.documentElement.style.setProperty('--pt-promo-h', h+'px');
    var nav = document.querySelectorAll('.mainhead, .subnav');
    for(var i=0;i<nav.length;i++){
      if(getComputedStyle(nav[i]).position === 'sticky'){ nav[i].style.top = h+'px'; }
      else { nav[i].style.top = ''; }
    }
  }
  apply();
  if(window.ResizeObserver){ new ResizeObserver(apply).observe(p); }
  document.addEventListener('DOMContentLoaded', apply);
  window.addEventListener('load', apply);
  window.addEventListener('resize', apply);
})();
</script>
<?php endif; ?>

<!-- Phone numbers — single source of truth: Phone_Numbers.md (website default = 01777 553392). -->
<header class="mainhead">
  <button class="menu" aria-label="Open menu" aria-expanded="false"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 6h18M3 12h18M3 18h18"/></svg></button>
  <a class="logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="Project Timber home"><img src="https://www.projecttimber.com/wp-content/themes/theTimber/assets/images/tplogo.svg" alt="Project Timber"></a>
  <button class="search" type="button" aria-label="Search Project Timber"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3" stroke-linecap="round"/></svg> Search Project Timber</button>
  <div class="icons">
    <button class="ic searchic" type="button" aria-label="Search"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3" stroke-linecap="round"/></svg></button>
    <button class="ic supporttrigger" type="button" aria-label="Customer support"><img src="https://www.projecttimber.com/wp-content/uploads/2026/06/proicons_chat.png" alt=""></button>
    <?php // Account icon hidden (2026-08-11). To restore, uncomment the link and set its href to the pt_account_url() value. NOTE: PHP inside an HTML comment still executes, so the href is kept static (#) here to avoid calling pt_account_url() while the icon is hidden. ?>
    <!-- <a class="ic" href="#" aria-label="My account"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg></a> -->
    <button class="ic cartopen" type="button" aria-label="Basket"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/><path d="M3 4h2l2.3 12.3a1.5 1.5 0 0 0 1.5 1.2h8.6a1.5 1.5 0 0 0 1.5-1.2L22 8H6"/></svg><span class="badge cartbadge">0</span></button>
  </div>
</header>

<!-- Nav → top-level WooCommerce product-category archives (slugs match the live projecttimber.com URLs). -->
<nav class="primnav" id="primnav"><ul>
<?php foreach ( $pt_cats as $pt_slug => $pt_label ) : ?>
  <li><a href="<?php echo esc_url( home_url( '/' . $pt_slug . '/' ) ); ?>"><?php echo esc_html( $pt_label ); ?></a></li>
<?php endforeach; ?>
</ul></nav>

<div class="hsearch" id="hsearch" hidden>
  <form class="wrap" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
    <input type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="Search Project Timber" aria-label="Search Project Timber">
    <input type="hidden" name="post_type" value="product">
  </form>
</div>
