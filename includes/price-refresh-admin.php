<?php
/**
 * Global "Refresh all prices" — one admin-bar button that flushes every price cache
 * site-wide at once (not just the product you're on).
 *
 * Prices are cached in three places; each is versioned by an option, and bumping that
 * option rotates ALL of its keys, so a single bump of each = a full, global flush:
 *   1. Configurator /config payloads  → option `timber_pcfg_gen` (read by the mu-plugin
 *      timber-product-config.php). Bumped here directly — same option the mu-plugin uses,
 *      so this needs no mu-plugin redeploy.
 *   2. Category "from" prices          → `pt_bump_from_price_cache_ver()` (option pt_fp_ver).
 *   3. Category grid product lists      → `pt_cat_bump_cache_version()` (option pt_catn_ver).
 *
 * After a bump, the next load of any product/category rebuilds fresh. The configurator
 * client never caches and bypasses the CDN (?_ts), so product prices update on next load.
 *
 * @package pt-theme-2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Who may flush the price caches: shop managers / anyone who can edit products. */
function pt_can_refresh_prices() {
	return current_user_can( 'manage_woocommerce' ) || current_user_can( 'edit_products' );
}

/**
 * Bump every price-cache version option in one shot. Returns nothing — callers just
 * need the side effect. Safe to call repeatedly; each call only rotates the versions.
 */
function pt_refresh_all_prices() {
	// 1. Configurator config cache (mu-plugin's global generation counter).
	update_option( 'timber_pcfg_gen', (int) get_option( 'timber_pcfg_gen', 1 ) + 1, false );
	// 2. Category "from" price cache (theme).
	if ( function_exists( 'pt_bump_from_price_cache_ver' ) ) {
		pt_bump_from_price_cache_ver();
	}
	// 3. Category grid list cache (theme).
	if ( function_exists( 'pt_cat_bump_cache_version' ) ) {
		pt_cat_bump_cache_version();
	}
}

/** Admin-bar button — available on both the front-end and wp-admin, everywhere. */
add_action( 'admin_bar_menu', function ( $bar ) {
	if ( ! pt_can_refresh_prices() ) {
		return;
	}
	$url = wp_nonce_url( admin_url( 'admin-post.php?action=pt_refresh_all_prices' ), 'pt_refresh_all_prices' );
	$bar->add_node( array(
		'id'    => 'pt-refresh-all-prices',
		'title' => '↻ Refresh all prices',
		'href'  => $url,
		'meta'  => array( 'title' => 'Clear cached prices for EVERY product & category (configurator + listings)' ),
	) );
}, 100 );

/** Handle the click: verify, bump all caches, redirect back to where they were. */
function pt_handle_refresh_all_prices() {
	if ( ! pt_can_refresh_prices() ) {
		wp_die( esc_html__( 'You are not allowed to refresh prices.', 'pt' ), 403 );
	}
	check_admin_referer( 'pt_refresh_all_prices' );

	pt_refresh_all_prices();

	$back = wp_get_referer();
	if ( ! $back ) {
		$back = admin_url();
	}
	wp_safe_redirect( add_query_arg( 'pt_prices_refreshed', '1', $back ) );
	exit;
}
add_action( 'admin_post_pt_refresh_all_prices', 'pt_handle_refresh_all_prices' );

/** Confirmation notice inside wp-admin. */
add_action( 'admin_notices', function () {
	if ( isset( $_GET['pt_prices_refreshed'] ) && pt_can_refresh_prices() ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<div class="notice notice-success is-dismissible"><p>'
			. esc_html__( 'Prices refreshed for every product and category.', 'pt' )
			. '</p></div>';
	}
} );

/** Lightweight confirmation toast on the front-end (mirrors the wp-admin notice). */
add_action( 'wp_footer', function () {
	if ( ! isset( $_GET['pt_prices_refreshed'] ) || ! pt_can_refresh_prices() ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}
	?>
	<div id="pt-prices-refreshed-toast" role="status" style="position:fixed;left:50%;bottom:24px;transform:translateX(-50%);z-index:99999;background:#211e24;color:#fff;padding:12px 18px;border-radius:999px;box-shadow:0 8px 24px rgba(0,0,0,.25);font:600 14px/1 system-ui,sans-serif;">All prices refreshed</div>
	<script>setTimeout(function(){var t=document.getElementById('pt-prices-refreshed-toast');if(t){t.style.transition='opacity .4s';t.style.opacity='0';setTimeout(function(){t.remove();},450);}},2600);</script>
	<?php
} );
