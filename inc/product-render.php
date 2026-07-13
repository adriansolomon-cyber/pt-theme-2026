<?php
/**
 * Single-product helpers — dynamic bits for single-product.php (design-only pass).
 *
 * @package pt-theme-2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * "From" price for a product:
 *  - composite  → cheapest size option (uses the mu-plugin's size-option resolver
 *    when present; else the product's own price),
 *  - variable   → lowest variation price,
 *  - otherwise  → the product's price.
 * Returns a float (0 if unavailable).
 */
function pt_product_from_price( $product ) {
	if ( ! $product || ! function_exists( 'wc_get_product' ) ) {
		return 0.0;
	}
	if ( $product->is_type( 'composite' ) && function_exists( 'timber_catp_size_options' ) ) {
		$min = INF;
		foreach ( (array) timber_catp_size_options( $product ) as $oid ) {
			$o = wc_get_product( (int) $oid );
			if ( $o ) {
				$p = (float) $o->get_price();
				if ( $p > 0 && $p < $min ) {
					$min = $p;
				}
			}
		}
		if ( INF !== $min ) {
			return $min;
		}
		return (float) $product->get_price();
	}
	if ( $product->is_type( 'variable' ) ) {
		return (float) $product->get_variation_price( 'min', true );
	}
	return (float) $product->get_price();
}

/** "From £1,234" (or empty string if no price). */
function pt_product_from_price_html( $product ) {
	$p = pt_product_from_price( $product );
	return $p > 0 ? 'From £' . number_format( round( $p ), 0, '.', ',' ) : '';
}

/**
 * Top-level product-category NAME for a product (walks up from the first assigned
 * term to its root ancestor, or prefers an explicitly top-level term). e.g. "Summerhouses".
 */
function pt_product_line_name( $product_id ) {
	$terms = get_the_terms( $product_id, 'product_cat' );
	if ( ! $terms || is_wp_error( $terms ) ) {
		return '';
	}
	$t = $terms[0];
	foreach ( $terms as $x ) {
		if ( 0 === (int) $x->parent ) {
			$t = $x;
			break;
		}
	}
	$guard = 0;
	while ( $t && 0 !== (int) $t->parent && $guard < 10 ) {
		$parent = get_term( (int) $t->parent, 'product_cat' );
		if ( ! $parent || is_wp_error( $parent ) ) {
			break;
		}
		$t = $parent;
		$guard++;
	}
	return $t ? $t->name : '';
}

/** Naive singular for the store's plural category names ("Summerhouses" → "Summerhouse"). */
function pt_singularize( $s ) {
	$s = trim( (string) $s );
	if ( '' === $s || preg_match( '/ss$/i', $s ) ) {
		return $s;
	}
	return preg_replace( '/s$/i', '', $s );
}

/** The product's category "line" name, singularised, for headings like "Build your {X}". */
function pt_product_line_singular( $product_id ) {
	return pt_singularize( pt_product_line_name( $product_id ) );
}
