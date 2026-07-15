<?php
/**
 * Checkout shipping form — Project Timber 2026 redesign.
 *
 * Overrides woocommerce/checkout/form-shipping.php. Renders the "Deliver to a
 * different address?" toggle in the design's .co-altaddr style (bordered box +
 * custom checkbox), while KEEPING WooCommerce's real #ship-to-different-address-checkbox
 * input (name="ship_to_different_address"). That preserves both WC core's slide
 * toggle of the address fields AND the old theme's shipping behaviour developed in
 * includes/wc-custom-checkout-functions.php (copy-from-billing button via
 * woocommerce_before_checkout_shipping_form, clear-on-uncheck, validation reveal,
 * order-processing copy). All checkout hooks and the field loop are preserved.
 *
 * @package pt-theme-2026
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="woocommerce-shipping-fields co-altaddr-wc">
	<?php if ( true === WC()->cart->needs_shipping_address() ) : ?>

		<h3 id="ship-to-different-address">
			<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
				<input id="ship-to-different-address-checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" <?php checked( apply_filters( 'woocommerce_ship_to_different_address_checked', 'shipping' === get_option( 'woocommerce_ship_to_destination' ) ? 1 : 0 ), 1 ); ?> type="checkbox" name="ship_to_different_address" value="1" />
				<span class="altaddr-box"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5L20 7"/></svg></span>
				<span class="altaddr-txt"><?php esc_html_e( 'Deliver to a different address?', 'woocommerce' ); ?></span>
			</label>
		</h3>

		<div class="shipping_address">

			<?php do_action( 'woocommerce_before_checkout_shipping_form', $checkout ); ?>

			<div class="woocommerce-shipping-fields__field-wrapper">
				<?php
				$fields = $checkout->get_checkout_fields( 'shipping' );

				foreach ( $fields as $key => $field ) {
					woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
				}
				?>
			</div>

			<?php do_action( 'woocommerce_after_checkout_shipping_form', $checkout ); ?>

		</div>

	<?php endif; ?>
</div>

<div class="woocommerce-additional-fields">
	<?php do_action( 'woocommerce_before_order_notes', $checkout ); ?>

	<?php if ( apply_filters( 'woocommerce_enable_order_notes_field', 'yes' === get_option( 'woocommerce_enable_order_comments', 'yes' ) ) ) : ?>

		<?php if ( ! WC()->cart->needs_shipping() || wc_ship_to_billing_address_only() ) : ?>
			<h3><?php esc_html_e( 'Additional information', 'woocommerce' ); ?></h3>
		<?php endif; ?>

		<div class="woocommerce-additional-fields__field-wrapper">
			<?php foreach ( $checkout->get_checkout_fields( 'order' ) as $key => $field ) : ?>
				<?php woocommerce_form_field( $key, $field, $checkout->get_value( $key ) ); ?>
			<?php endforeach; ?>
		</div>

	<?php endif; ?>

	<?php do_action( 'woocommerce_after_order_notes', $checkout ); ?>
</div>
