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

<?php
$pt_cart_items = $pt_cart->get_cart();

/*
 * Bundle / composite children are SEPARATE cart line items linked to a parent
 * container (Product Bundles → 'bundled_by', Composites → 'composite_parent',
 * Mix-and-Match → 'mnm_container'). Like the mini-cart, we render only the parent
 * as a product card and fold its children into the "Your configuration" list —
 * otherwise every bundled option (Size, Wall Thickness, Floor…) shows as its own
 * £0.00 card. Index children by their parent cart key first.
 */
$pt_children = array();
foreach ( $pt_cart_items as $pt_ck => $pt_ci ) {
	$pt_pk = '';
	foreach ( array( 'bundled_by', 'composite_parent', 'mnm_container' ) as $pt_rel ) {
		if ( ! empty( $pt_ci[ $pt_rel ] ) ) {
			$pt_pk = $pt_ci[ $pt_rel ];
			break;
		}
	}
	if ( $pt_pk ) {
		$pt_children[ $pt_pk ][ $pt_ck ] = $pt_ci;
	}
}

/** Normalise a dimension like "8 x 8" / "8x8" → "8 × 8" for display. */
$pt_norm_dims = function ( $s ) {
	return preg_replace( '/(\d)\s*[x×]\s*(\d)/u', '$1 × $2', (string) $s );
};
?>

<?php do_action( 'woocommerce_review_order_before_cart_contents' ); ?>

<?php foreach ( $pt_cart_items as $cart_item_key => $cart_item ) : ?>
	<?php
	// Skip children — they're listed under their parent, never as their own card.
	if ( ! empty( $cart_item['bundled_by'] ) || ! empty( $cart_item['composite_parent'] ) || ! empty( $cart_item['mnm_container'] ) ) {
		continue;
	}

	$_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );

	if ( ! $_product || ! $_product->exists() || $cart_item['quantity'] <= 0 || ! apply_filters( 'woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
		continue;
	}

	$product_id   = $_product->get_id();
	$product_name = apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key );
	$thumbnail    = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image( 'woocommerce_thumbnail' ), $cart_item, $cart_item_key );
	$line_total   = apply_filters( 'woocommerce_cart_item_subtotal', $pt_cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key );
	$range        = function_exists( 'pt_product_line_name' ) ? pt_product_line_name( $product_id ) : '';

	// Build the configuration rows. Prefer this item's bundled/composite children
	// (title → selection); fall back to variation / add-on item-data for plain items.
	$cfg_rows = array();
	$children = isset( $pt_children[ $cart_item_key ] ) ? $pt_children[ $cart_item_key ] : array();

	if ( $children ) {
		foreach ( $children as $child ) {
			$child_product = isset( $child['data'] ) ? $child['data'] : null;
			if ( ! $child_product ) {
				continue;
			}
			$label = '';
			// Product Bundles: the container product resolves the bundled-item title.
			if ( ! empty( $child['bundled_item_id'] ) && is_callable( array( $_product, 'get_bundled_item' ) ) ) {
				$bi = $_product->get_bundled_item( $child['bundled_item_id'] );
				if ( $bi && is_callable( array( $bi, 'get_title' ) ) ) {
					$label = $bi->get_title();
				}
			}
			$value = $pt_norm_dims( $child_product->get_name() );
			$qty   = isset( $child['quantity'] ) ? (int) $child['quantity'] : 1;

			// Per-option price column (design: £X.XX for priced options, "Included"
			// for chosen zero-cost options, "—" for a "None" selection).
			$raw    = isset( $child['line_subtotal'] ) ? (float) $child['line_subtotal'] : 0;
			$name_lc = strtolower( trim( wp_strip_all_tags( $child_product->get_name() ) ) );
			if ( '' === $name_lc || 'none' === $name_lc || '-' === $name_lc || '—' === $name_lc ) {
				$price = '—';
				$free  = true;
			} elseif ( $raw > 0 ) {
				$price = wp_kses_post( $pt_cart->get_product_subtotal( $child_product, $qty ) );
				$free  = false;
			} else {
				$price = esc_html__( 'Included', 'woocommerce' );
				$free  = true;
			}

			if ( $qty > 1 ) {
				$value .= ' × ' . $qty;
			}
			$cfg_rows[] = array( 'k' => esc_html( $label ), 'v' => esc_html( $value ), 'p' => $price, 'free' => $free );
		}
	} else {
		$item_data = apply_filters( 'woocommerce_get_item_data', array(), $cart_item );
		foreach ( $item_data as $data ) {
			$key = isset( $data['key'] ) ? $data['key'] : '';
			$val = ( isset( $data['display'] ) && '' !== $data['display'] ) ? $data['display'] : ( isset( $data['value'] ) ? $data['value'] : '' );
			if ( '' === trim( wp_strip_all_tags( (string) $key ) ) && '' === trim( wp_strip_all_tags( (string) $val ) ) ) {
				continue;
			}
			$cfg_rows[] = array( 'k' => wp_kses_post( $key ), 'v' => wp_kses_post( $val ), 'p' => '', 'free' => true );
		}
	}
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
			<a class="rm" href="<?php echo esc_url( wc_get_cart_remove_url( $cart_item_key ) ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Remove %s from basket', 'woocommerce' ), wp_strip_all_tags( $product_name ) ) ); ?>"><?php esc_html_e( 'Remove', 'woocommerce' ); ?></a>
		</div>
	</div>

	<?php if ( ! empty( $cfg_rows ) ) : ?>
		<details class="cfg" open>
			<summary><?php esc_html_e( 'Your configuration', 'woocommerce' ); ?> <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg></summary>
			<div class="rows">
				<?php foreach ( $cfg_rows as $row ) : ?>
					<div class="crow">
						<span class="k"><?php echo $row['k']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — pre-escaped above. ?></span>
						<span class="v"><?php echo $row['v']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — pre-escaped above. ?></span>
						<?php if ( '' !== $row['p'] ) : ?>
							<span class="p<?php echo ! empty( $row['free'] ) ? ' free' : ''; ?>"><?php echo $row['p']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — pre-escaped above. ?></span>
						<?php endif; ?>
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

	<?php
	// Applied coupons / discounts (green saving line), placed under Subtotal.
	// NOT gated on wc_coupons_enabled(): this theme auto-applies vouchers
	// programmatically (av_* in wc-custom-checkout-functions.php), which still
	// discounts the total even when the coupon UI is switched off — so render
	// whatever is actually applied to the cart.
	foreach ( $pt_cart->get_coupons() as $code => $coupon ) :
		?>
		<div class="ln discount coupon-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
			<span><?php wc_cart_totals_coupon_label( $coupon ); ?></span>
			<b><?php wc_cart_totals_coupon_html( $coupon ); ?></b>
		</div>
		<?php
	endforeach;
	?>

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
