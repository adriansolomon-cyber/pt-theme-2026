<?php
/**
 * Save & Share Cart — theme integration.
 *
 * The "Save & Share Cart" plugin (HighAddons) renders its Save/Share button ONLY
 * via WooCommerce's standard cart-page, mini-cart and checkout hooks, and only
 * loads its assets + popup on is_cart() || is_checkout(). This theme replaces the
 * cart with a custom Store-API drawer (template-parts/cart-drawer.php) and routes
 * customers drawer -> checkout, so:
 *
 *   - the cart-page button (woocommerce_cart_actions) is effectively unreachable
 *     (the drawer's CTA goes straight to checkout, /cart/ is bypassed),
 *   - the mini-cart button (woocommerce_after_mini_cart) never fires because the
 *     drawer is custom markup and never calls woocommerce_mini_cart(),
 *   - the checkout button (woocommerce_review_order_before_submit) is only wired
 *     up by the plugin when its own "wsc_enable_share_cart_btn_checkout" option is
 *     'yes' — and that hook decision is made at plugin-load, before this theme.
 *
 * The checkout page IS the reachable place: the plugin's own is_checkout() gating
 * already loads its assets + popup there, and checkout.css already styles/positions
 * the .wsc_* button next to Place Order. So we surface the button at checkout here,
 * regardless of the plugin's admin toggle, and without duplicating it if the plugin
 * already added it.
 *
 * @package pt-theme-2026
 */

defined( 'ABSPATH' ) || exit;

// Only act when the plugin is active.
if ( class_exists( 'WSC_Share_Cart_Frontend' ) ) {

	/**
	 * wsc_share_cart_html() short-circuits to an empty string on checkout unless
	 * this option is 'yes'. Force it on the FRONTEND only (leave the real stored
	 * value visible in wp-admin so the setting still reflects reality there).
	 */
	$pt_wsc_force_checkout = function ( $value ) {
		return is_admin() ? $value : 'yes';
	};
	add_filter( 'option_wsc_enable_share_cart_btn_checkout', $pt_wsc_force_checkout );
	add_filter( 'default_option_wsc_enable_share_cart_btn_checkout', $pt_wsc_force_checkout );

	/**
	 * When a shared cart is retrieved (?share-cart=ID), the plugin loads it into the
	 * session and then redirects to the CART page unless wsc_redirect_customers is
	 * 'checkout'. This theme has no standalone cart experience (drawer -> checkout),
	 * so send retrieved carts straight to checkout — where the loaded session cart is
	 * shown by the order review — matching the old theme's behaviour. Frontend only.
	 */
	$pt_wsc_redirect_checkout = function ( $value ) {
		return is_admin() ? $value : 'checkout';
	};
	add_filter( 'option_wsc_redirect_customers', $pt_wsc_redirect_checkout );
	add_filter( 'default_option_wsc_redirect_customers', $pt_wsc_redirect_checkout );

	/**
	 * Render the button before the Place Order submit (WooCommerce's default
	 * payment.php fires this — the theme's form-checkout.php calls
	 * woocommerce_checkout_payment(), so it runs). The plugin registers the same
	 * hook itself when its checkout option was already 'yes' at load; only add ours
	 * when it hasn't, so the button is never rendered twice.
	 */
	if ( false === has_action( 'woocommerce_review_order_before_submit', array( 'WSC_Share_Cart_Frontend', 'wsc_share_cart_button' ) ) ) {
		add_action( 'woocommerce_review_order_before_submit', array( 'WSC_Share_Cart_Frontend', 'wsc_share_cart_button' ) );
	}
}
