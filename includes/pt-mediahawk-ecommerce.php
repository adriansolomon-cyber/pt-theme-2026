<?php
/**
 * Mediahawk eCommerce conversion (dm1) — fire once per PAID order on the
 * order-received (thank-you) page.
 *
 * AUDIT (Oct 2026): dm1 was not firing anywhere in the theme/plugins/mu-plugins.
 * Mediahawk's loader is inline in header.php (site-wide), so window.mhct is present
 * on the thank-you page. This fires mhct.trigger('dm1') once per paid order.
 *
 * VALUE PENDING: Mediahawk's "create conversion with code" doc confirms dm1 is a
 * "Sum" conversion but does NOT document how the amount is passed to mhct.trigger,
 * and we do not guess. The value is computed + ready below (ex-VAT, ex-shipping, to
 * match margin reporting) but is NOT yet passed — see the TODO in the script. Adrian
 * to confirm the exact syntax with Mediahawk (Amber Jarvis-Brown,
 * clientservices@mediahawk.co.uk); then we add the argument and remove this note.
 *
 * Guards:
 *   - Paid orders only (is_paid) — the thank-you page is also reachable by
 *     failed/pending orders, which must NOT fire.
 *   - Once per order — the _mh_dm1_sent meta blocks refreshes/revisits (set before
 *     output: a closed tab can lose the event, which is preferable to double-counting).
 *   - Guarded + try/catch — never breaks the thank-you page if Mediahawk is
 *     missing/ad-blocked.
 *
 * Task: "Mediahawk eCommerce conversion (dm1): audit, then fire with order value."
 *
 * @package pt-theme-2026
 */

defined( 'ABSPATH' ) || exit;

/**
 * Fire the Mediahawk dm1 "eCommerce" conversion on the order-received page.
 *
 * @param int $order_id Order ID (passed by woocommerce_thankyou).
 */
function pt_mediahawk_dm1_on_thankyou( $order_id ) {
	$order_id = absint( $order_id );
	if ( ! $order_id ) {
		return;
	}
	$order = wc_get_order( $order_id );
	if ( ! $order instanceof WC_Order ) {
		return;
	}
	// Paid orders only — never fire for failed / pending / cancelled.
	if ( ! $order->is_paid() ) {
		return;
	}
	// Never fire for staff (admin / shop manager). Stops opening an order's
	// order-received URL in wp-admin, or an admin browsing, from logging a conversion.
	if ( current_user_can( 'manage_woocommerce' ) ) {
		return;
	}
	// Only a JUST-completed purchase, never an OLD order being revisited (e.g. a
	// customer reopening a months-old order-received link from an email — and, right
	// after deploy, any old paid order whose thank-you page has never been viewed,
	// since none are marked sent yet). Anchor on payment time (fallback: creation);
	// skip if older than the window (default 2 hours, filterable).
	$window = (int) apply_filters( 'pt_mh_dm1_recent_seconds', 2 * HOUR_IN_SECONDS );
	$when   = $order->get_date_paid() ? $order->get_date_paid() : $order->get_date_created();
	if ( ! $when || ( time() - $when->getTimestamp() ) > $window ) {
		return;
	}
	// Once per order — guard refreshes and revisits.
	if ( $order->get_meta( '_mh_dm1_sent' ) ) {
		return;
	}

	// Value for the Sum conversion: order total minus shipping, ex-VAT (÷1.2) — the
	// same basis as margin reporting. Computed + ready, but NOT yet passed (Step 2).
	$value = max( 0, ( (float) $order->get_total() - (float) $order->get_shipping_total() ) / 1.2 );
	$value = wc_format_decimal( $value, 2 );

	// Mark sent BEFORE output so a refresh can't re-fire (double-counting is worse
	// than a rare lost event from a tab closed mid-render).
	$order->update_meta_data( '_mh_dm1_sent', current_time( 'mysql' ) );
	$order->save();
	?>
	<script>
	(function () {
		try {
			if ( typeof window.mhct !== 'undefined' && typeof window.mhct.trigger === 'function' ) {
				window.mhct.trigger( 'dm1' );
				/* TODO (pending Mediahawk's Sum-value syntax): pass the order value —
				   ex-VAT, ex-shipping = <?php echo esc_js( $value ); ?> — once confirmed,
				   e.g. window.mhct.trigger('dm1', <?php echo esc_js( $value ); ?>); */
			}
		} catch ( e ) { /* never break the thank-you page */ }
	})();
	</script>
	<?php
}
add_action( 'woocommerce_thankyou', 'pt_mediahawk_dm1_on_thankyou', 20 );
