<?php
/**
 * Checkout Form — Project Timber 2026 redesign.
 *
 * Overrides woocommerce/checkout/form-checkout.php. Renders WooCommerce's REAL
 * checkout in the new 2-column design (co-grid): customer/billing/delivery fields
 * AND payment in the left column, order summary (review + totals) on the right.
 *
 * Payment and order-review are normally rendered together via the
 * woocommerce_checkout_order_review action; here they're split by calling
 * woocommerce_order_review() (right) and woocommerce_checkout_payment() (left)
 * directly so payment can live in the left column, as the design requires.
 * All other checkout hooks are preserved.
 *
 * @package pt-theme-2026
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_checkout_form', $checkout );

// If checkout registration is disabled and not logged in, the user cannot checkout.
if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'woocommerce' ) ) );
	return;
}
?>

<div class="co-head">
	<h1><?php esc_html_e( 'Checkout', 'woocommerce' ); ?></h1>
</div>

<form name="checkout" method="post" class="checkout woocommerce-checkout co-grid" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data" aria-label="<?php esc_attr_e( 'Checkout', 'woocommerce' ); ?>">

	<div class="co-form">
		<?php if ( $checkout->get_checkout_fields() ) : ?>

			<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>

			<div id="customer_details">
				<?php do_action( 'woocommerce_checkout_billing' ); ?>
				<?php do_action( 'woocommerce_checkout_shipping' ); ?>
			</div>

			<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>

		<?php endif; ?>

		<div id="payment_col" class="co-sec co-payment">
			<h2 class="co-payment-title"><?php esc_html_e( 'Payment', 'woocommerce' ); ?></h2>
			<?php woocommerce_checkout_payment(); ?>
		</div>
	</div>

	<aside class="co-summary">
		<div class="summary-card">
			<?php do_action( 'woocommerce_checkout_before_order_review_heading' ); ?>
			<h2 id="order_review_heading"><?php esc_html_e( 'Order summary', 'woocommerce' ); ?><a class="ed" href="<?php echo esc_url( wc_get_cart_url() ); ?>"><?php esc_html_e( 'Edit basket', 'woocommerce' ); ?></a></h2>
			<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>
			<div id="order_review" class="woocommerce-checkout-review-order">
				<?php woocommerce_order_review(); ?>
			</div>
			<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>
		</div>
	</aside>

</form>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
