<?php
/**
 * Order received / thank-you — Project Timber styled.
 *
 * Overrides woocommerce/checkout/thankyou.php. Every WooCommerce hook is
 * preserved (woocommerce_before_thankyou, woocommerce_thankyou[_{gateway}], and
 * the order-details + customer-details tables the woocommerce_thankyou hook
 * renders), so composite line items, plugins and totals are unaffected — only
 * the surrounding shell is restyled to the theme's design. Rendered inside
 * page.php's <main class="wrap pt-page">, and checkout.css is already enqueued
 * here (is_checkout() is true on the order-received endpoint).
 *
 * @package pt-theme-2026
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="woocommerce-order ty">

	<?php if ( $order ) : ?>

		<?php do_action( 'woocommerce_before_thankyou', $order->get_id() ); ?>

		<?php if ( $order->has_status( 'failed' ) ) : ?>

			<div class="ty-hero ty-hero--fail">
				<div class="ty-badge ty-badge--fail" aria-hidden="true">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
				</div>
				<h1><?php esc_html_e( 'Payment unsuccessful', 'woocommerce' ); ?></h1>
				<p class="ty-sub"><?php esc_html_e( 'Unfortunately your order cannot be processed as the originating bank/merchant has declined your transaction. Please attempt your purchase again.', 'woocommerce' ); ?></p>
				<p class="ty-actions">
					<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="btn-primary"><?php esc_html_e( 'Try payment again', 'woocommerce' ); ?></a>
					<?php if ( is_user_logged_in() ) : ?>
						<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="ty-link"><?php esc_html_e( 'Go to my account', 'woocommerce' ); ?></a>
					<?php endif; ?>
				</p>
			</div>

		<?php else : ?>

			<div class="ty-hero">
				<div class="ty-badge" aria-hidden="true">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
				</div>
				<h1><?php esc_html_e( 'Thank you — your order is confirmed', 'woocommerce' ); ?></h1>
				<?php if ( $order->get_billing_email() ) : ?>
					<p class="ty-sub">
						<?php
						printf(
							/* translators: %s: customer billing email, wrapped in <b> */
							esc_html__( 'We\'ve sent a confirmation to %s. Our team will be in touch about your delivery.', 'woocommerce' ),
							'<b>' . esc_html( $order->get_billing_email() ) . '</b>'
						);
						?>
					</p>
				<?php else : ?>
					<p class="ty-sub"><?php esc_html_e( 'Your order has been received. Our team will be in touch about your delivery.', 'woocommerce' ); ?></p>
				<?php endif; ?>
			</div>

			<ul class="woocommerce-order-overview woocommerce-thankyou-order-details order_details">
				<li class="woocommerce-order-overview__order order">
					<span><?php esc_html_e( 'Order number', 'woocommerce' ); ?></span>
					<strong><?php echo esc_html( $order->get_order_number() ); ?></strong>
				</li>
				<li class="woocommerce-order-overview__date date">
					<span><?php esc_html_e( 'Date', 'woocommerce' ); ?></span>
					<strong><?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?></strong>
				</li>
				<?php if ( $order->get_billing_email() ) : ?>
					<li class="woocommerce-order-overview__email email">
						<span><?php esc_html_e( 'Email', 'woocommerce' ); ?></span>
						<strong><?php echo esc_html( $order->get_billing_email() ); ?></strong>
					</li>
				<?php endif; ?>
				<li class="woocommerce-order-overview__total total">
					<span><?php esc_html_e( 'Total', 'woocommerce' ); ?></span>
					<strong><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></strong>
				</li>
				<?php if ( $order->get_payment_method_title() ) : ?>
					<li class="woocommerce-order-overview__payment-method method">
						<span><?php esc_html_e( 'Payment method', 'woocommerce' ); ?></span>
						<strong><?php echo wp_kses_post( $order->get_payment_method_title() ); ?></strong>
					</li>
				<?php endif; ?>
			</ul>

		<?php endif; ?>

		<?php do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() ); ?>
		<?php do_action( 'woocommerce_thankyou', $order->get_id() ); ?>

		<?php if ( ! $order->has_status( 'failed' ) ) : ?>
			<div class="ty-foot">
				<a class="ty-continue" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Continue shopping', 'woocommerce' ); ?> <span aria-hidden="true">&rarr;</span></a>
			</div>
		<?php endif; ?>

	<?php else : ?>

		<div class="ty-hero">
			<div class="ty-badge" aria-hidden="true">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
			</div>
			<h1><?php esc_html_e( 'Thank you. Your order has been received.', 'woocommerce' ); ?></h1>
		</div>

	<?php endif; ?>

</div>
