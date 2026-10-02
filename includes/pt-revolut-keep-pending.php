<?php
/**
 * Keep unpaid Revolut orders as "pending payment".
 *
 * The Revolut gateway cancels pending/on-hold orders when its payment order is
 * cancelled or expires — via the webhook handler ("Order cancelled via Webhook.",
 * wc-revolut-helper-trait.php) and the return/state-check handler. We send payment
 * links and chase payment, so those orders must survive: this reverts a Revolut-
 * driven cancellation of an UNPAID order back to pending, and mutes the admin
 * "Cancelled order" email for it so it isn't noise.
 *
 * WooCommerce has no filter to veto a status change, so a revert is the standard,
 * update-safe approach — nothing in the Revolut plugin is edited. Delete this file
 * (and its require in functions.php) to restore default behaviour.
 *
 * Scope guards:
 *   - Unpaid orders only (a paid order is never touched).
 *   - Revolut payment methods only.
 *   - NOT when a shop manager/admin is the actor — a manual cancel in wp-admin must
 *     stick; only the gateway's automated cancellations (webhook / customer return,
 *     which run without that capability) are reverted.
 *
 * Note: for made-to-order buildings stock isn't managed, so the cancel→pending
 * transition doesn't thrash stock. Pending orders no longer auto-clear (paired with
 * the hold-stock filter in legacy-functions.php) — that's the intended behaviour:
 * chase payment, don't auto-cancel.
 *
 * @package pt-theme-2026
 */

defined( 'ABSPATH' ) || exit;

/** An unpaid Revolut order that the gateway shouldn't be allowed to auto-cancel. */
function pt_revolut_is_chaseable( $order ) {
	return $order instanceof WC_Order
		&& ! $order->is_paid()
		&& false !== stripos( (string) $order->get_payment_method(), 'revolut' );
}

/** True when the current actor is a shop manager/admin (a manual cancel must stick). */
function pt_revolut_actor_is_staff() {
	return function_exists( 'current_user_can' ) && current_user_can( 'edit_shop_orders' );
}

/**
 * Revert a Revolut-driven cancellation of an unpaid order back to pending.
 *
 * @param int           $order_id Order ID.
 * @param WC_Order|null $order    Order object (WC passes it on this hook).
 */
function pt_revolut_keep_pending( $order_id, $order = null ) {
	static $reverting = array();

	if ( ! $order instanceof WC_Order ) {
		$order = wc_get_order( $order_id );
	}
	if ( ! pt_revolut_is_chaseable( $order ) || pt_revolut_actor_is_staff() ) {
		return;
	}
	if ( isset( $reverting[ $order_id ] ) ) {
		return; // guard against re-entry
	}
	$reverting[ $order_id ] = true;
	$order->update_status( 'pending', 'Kept as pending payment — Revolut cancellation reverted so the payment link stays live.' );
	unset( $reverting[ $order_id ] );
}
add_action( 'woocommerce_order_status_cancelled', 'pt_revolut_keep_pending', 5, 2 );

/**
 * Mute the admin "Cancelled order" email for those unpaid Revolut orders (they're
 * reverted to pending immediately, so the notice would be misleading noise). A real
 * staff cancel still gets the email.
 *
 * @param string   $recipient Email recipient(s).
 * @param WC_Order $order     Order.
 * @return string
 */
function pt_revolut_mute_cancelled_email( $recipient, $order ) {
	if ( pt_revolut_is_chaseable( $order ) && ! pt_revolut_actor_is_staff() ) {
		return '';
	}
	return $recipient;
}
add_filter( 'woocommerce_email_recipient_cancelled_order', 'pt_revolut_mute_cancelled_email', 10, 2 );
