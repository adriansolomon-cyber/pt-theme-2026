<?php
/**
 * Order review — Project Timber 2026 redesign.
 *
 * Overrides woocommerce/checkout/review-order.php. Instead of WooCommerce's default
 * product TABLE, this renders the design's order-summary card (projecttimber-checkout.html
 * → .co-summary): per-item thumb + qty badge + range + title + price, a collapsible
 * "Your configuration" breakdown (composite / variation selections), a promo field,
 * the totals block (subtotal, shipping, total, VAT) and the assurance line — all
 * populated from the real WooCommerce cart.
 *
 * This markup lives inside #order_review, which WooCommerce replaces via the
 * update_order_review AJAX fragment, so totals refresh when shipping/coupons change.
 * The outer .summary-card + "Order summary" heading come from form-checkout.php.
 *
 * Standard review-order hooks are preserved so dependent plugins still fire.
 *
 * @package pt-theme-2026
 */

defined( 'ABSPATH' ) || exit;

$pt_cart = ( function_exists( 'WC' ) && WC()->cart ) ? WC()->cart : null;
if ( ! $pt_cart ) {
	return;
}
?>

<?php do_action( 'woocommerce_review_order_before_cart_contents' ); ?>

<?php foreach ( $pt_cart->get_cart() as $cart_item_key => $cart_item ) : ?>
	<?php
	$_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );

	if ( ! $_product || ! $_product->exists() || $cart_item['quantity'] <= 0 || ! apply_filters( 'woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
		continue;
	}

	$product_id   = $_product->get_id();
	$product_name = apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key );
	$thumbnail    = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image( 'woocommerce_thumbnail' ), $cart_item, $cart_item_key );
	$line_total   = apply_filters( 'woocommerce_cart_item_subtotal', $pt_cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key );
	$range        = function_exists( 'pt_product_line_name' ) ? pt_product_line_name( $product_id ) : '';

	// Configuration rows: composite / variation / add-on selections shown under the name.
	$item_data = apply_filters( 'woocommerce_get_item_data', array(), $cart_item );
	?>
	<div class="sum-item">
		<div class="thumb">
			<?php echo wp_kses_post( $thumbnail ); ?>
			<span class="qty"><?php echo esc_html( $cart_item['quantity'] ); ?></span>
		</div>
		<div class="si">
			<?php if ( $range ) : ?>
				<div class="rng"><?php echo esc_html( $range ); ?></div>
			<?php endif; ?>
			<h3><?php echo wp_kses_post( $product_name ); ?></h3>
			<div class="pr"><?php echo wp_kses_post( $line_total ); ?></div>
		</div>
	</div>

	<?php if ( ! empty( $item_data ) ) : ?>
		<details class="cfg" open>
			<summary><?php esc_html_e( 'Your configuration', 'woocommerce' ); ?> <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg></summary>
			<div class="rows">
				<?php foreach ( $item_data as $data ) : ?>
					<?php
					$key = isset( $data['key'] ) ? $data['key'] : '';
					$val = isset( $data['display'] ) && '' !== $data['display'] ? $data['display'] : ( isset( $data['value'] ) ? $data['value'] : '' );
					if ( '' === trim( wp_strip_all_tags( (string) $key ) ) && '' === trim( wp_strip_all_tags( (string) $val ) ) ) {
						continue;
					}
					?>
					<div class="crow">
						<span class="k"><?php echo wp_kses_post( $key ); ?></span>
						<span class="v"><?php echo wp_kses_post( $val ); ?></span>
					</div>
				<?php endforeach; ?>
			</div>
		</details>
	<?php endif; ?>
<?php endforeach; ?>

<?php do_action( 'woocommerce_review_order_after_cart_contents' ); ?>

<?php if ( wc_coupons_enabled() ) : ?>
	<form class="co-promo checkout_coupon woocommerce-form-coupon" method="post">
		<input type="text" name="coupon_code" id="coupon_code" class="input-text" placeholder="<?php esc_attr_e( 'Promo code', 'woocommerce' ); ?>" value="" aria-label="<?php esc_attr_e( 'Promo code', 'woocommerce' ); ?>" />
		<button type="submit" class="button" name="apply_coupon" value="<?php esc_attr_e( 'Apply', 'woocommerce' ); ?>"><?php esc_html_e( 'Apply', 'woocommerce' ); ?></button>
	</form>
<?php endif; ?>

<div class="sum-tot">
	<div class="ln">
		<span><?php esc_html_e( 'Subtotal', 'woocommerce' ); ?></span>
		<b><?php echo wp_kses_post( $pt_cart->get_cart_subtotal() ); ?></b>
	</div>

	<?php do_action( 'woocommerce_review_order_before_shipping' ); ?>

	<?php if ( $pt_cart->needs_shipping() && $pt_cart->show_shipping() ) : ?>
		<div class="ln">
			<span><?php esc_html_e( 'Shipping', 'woocommerce' ); ?></span>
			<b><?php echo wp_kses_post( $pt_cart->get_cart_shipping_total() ); ?></b>
		</div>
	<?php endif; ?>

	<?php do_action( 'woocommerce_review_order_after_shipping' ); ?>

	<?php foreach ( $pt_cart->get_fees() as $fee ) : ?>
		<div class="ln">
			<span><?php echo esc_html( $fee->name ); ?></span>
			<b><?php echo wp_kses_post( wc_cart_totals_fee_html( $fee ) ); ?></b>
		</div>
	<?php endforeach; ?>

	<?php do_action( 'woocommerce_review_order_before_order_total' ); ?>

	<div class="grand">
		<span class="l"><?php esc_html_e( 'Total', 'woocommerce' ); ?></span>
		<span class="v"><?php echo wp_kses_post( $pt_cart->get_total() ); ?></span>
	</div>

	<?php do_action( 'woocommerce_review_order_after_order_total' ); ?>

	<?php if ( wc_tax_enabled() && $pt_cart->get_total_tax() > 0 ) : ?>
		<div class="vat">
			<?php
			/* translators: %s: formatted VAT amount. */
			printf( esc_html__( 'Includes %s VAT', 'woocommerce' ), wp_kses_post( wc_price( $pt_cart->get_total_tax() ) ) );
			?>
		</div>
	<?php endif; ?>

	<div class="ship-note"><?php esc_html_e( 'Delivery is free to selected postcodes; other areas are calculated from your delivery postcode.', 'woocommerce' ); ?></div>
</div>

<div class="sum-assure">
	<div class="a"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7 3v5c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6z"/><path d="M9 12l2 2 4-4"/></svg> <?php esc_html_e( 'Made in Britain · up to 25-year anti-rot guarantee', 'woocommerce' ); ?></div>
</div>
