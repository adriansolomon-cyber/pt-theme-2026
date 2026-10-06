<?php
/**
 * "Double Glazing" feature badge.
 *
 * Ported from the old theme (theTimber/woocommerce/content-product.php), which
 * showed a badge for a hardcoded list of product IDs. Here it renders on the
 * category/shop card (bottom-right) and on the product page over the gallery
 * (top-right) for the same fixed list of products.
 *
 * Both the ID list and the image are filterable:
 *   - pt_double_glazing_badge_ids  — the product IDs that show the badge
 *   - pt_double_glazing_badge_img  — the badge image URL
 *
 * NOTE: the default image is the old PNG (reused as requested). It reads "FREE
 * Double Glazing"; to use a plain "Double Glazing" feature image, upload one and
 * point pt_double_glazing_badge_img at it (or change the URL below).
 *
 * @package pt-theme-2026
 */

defined( 'ABSPATH' ) || exit;

/**
 * Product IDs that show the Double Glazing badge (ported from the old theme's
 * $free_double_glazed_promo list). Filterable.
 *
 * @return int[]
 */
function pt_double_glazing_badge_ids() {
	return array_map( 'intval', (array) apply_filters( 'pt_double_glazing_badge_ids', array(
		210142, 9235, 13067, 9198, 67032, 15439, 74465, 62453, 60997, 21862,
		73034, 47821, 9618, 11434, 14204, 22397, 164029, 10550, 156094,
	) ) );
}

/**
 * The badge image URL. Defaults to the old theme's PNG; filter to swap.
 *
 * @return string
 */
function pt_double_glazing_badge_img() {
	return (string) apply_filters(
		'pt_double_glazing_badge_img',
		'https://www.projecttimber.com/wp-content/uploads/2024/03/free-double-glazing.png'
	);
}

/**
 * Whether a product should show the Double Glazing badge.
 *
 * @param int $product_id Product ID.
 * @return bool
 */
function pt_product_has_double_glazing_badge( $product_id ) {
	$product_id = (int) $product_id;
	return $product_id && in_array( $product_id, pt_double_glazing_badge_ids(), true );
}

/**
 * Badge <img> HTML for a given placement class, or '' when the product isn't
 * eligible or no image is configured.
 *
 * @param int    $product_id Product ID.
 * @param string $class      Wrapper class (placement-specific).
 * @return string
 */
function pt_double_glazing_badge_html( $product_id, $class = 'dgbadge' ) {
	if ( ! pt_product_has_double_glazing_badge( $product_id ) ) {
		return '';
	}
	$img = pt_double_glazing_badge_img();
	if ( '' === $img ) {
		return '';
	}
	return '<span class="' . esc_attr( $class ) . '"><img src="' . esc_url( $img )
		. '" alt="' . esc_attr__( 'Double glazing', 'pt' ) . '" loading="lazy"></span>';
}
