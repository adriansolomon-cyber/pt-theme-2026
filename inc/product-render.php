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

/** Walk a term up to its top-level ancestor (root of the category tree). */
function pt_term_root( $term ) {
	$guard = 0;
	while ( $term && 0 !== (int) $term->parent && $guard < 10 ) {
		$parent = get_term( (int) $term->parent, 'product_cat' );
		if ( ! $parent || is_wp_error( $parent ) ) {
			break;
		}
		$term = $parent;
		$guard++;
	}
	return $term;
}

/**
 * The product's "line" term — the category that names the product for headings
 * like "Build your {X}".
 *
 * Resolution order:
 *  1. Yoast SEO primary category, if an editor set one (the intended primary).
 *  2. Otherwise the first assigned category that isn't a promo/marketing category
 *     (Finance, Black Friday, Offers, Sale…), preferring a top-level term and
 *     resolved up to its root ancestor.
 *
 * The promo-category blocklist is filterable via 'pt_non_line_category_slugs'.
 */
function pt_product_line_term( $product_id ) {
	// 1) Yoast primary category — the editor-declared primary term. Used as-is.
	$primary_id = (int) get_post_meta( $product_id, '_yoast_wpseo_primary_product_cat', true );
	if ( $primary_id > 0 ) {
		$t = get_term( $primary_id, 'product_cat' );
		if ( $t && ! is_wp_error( $t ) ) {
			return $t;
		}
	}

	$terms = get_the_terms( $product_id, 'product_cat' );
	if ( ! $terms || is_wp_error( $terms ) ) {
		return null;
	}

	// 2) Drop promo / marketing categories so they can't be chosen as the line.
	$skip = apply_filters(
		'pt_non_line_category_slugs',
		array(
			'finance',
			'black-friday',
			'offers',
			'offer',
			'sale',
			'sales',
			'clearance',
			'deals',
			'deal',
			'featured',
			'new',
			'new-in',
			'bundles',
			'misc',
			'uncategorised',
			'uncategorized',
		)
	);
	$candidates = array();
	foreach ( $terms as $x ) {
		if ( ! in_array( $x->slug, $skip, true ) ) {
			$candidates[] = $x;
		}
	}
	if ( empty( $candidates ) ) {
		$candidates = $terms; // everything was blocklisted — a promo name beats a blank heading.
	}

	// Prefer an explicitly top-level candidate; else resolve the first up to its root.
	$pick = $candidates[0];
	foreach ( $candidates as $x ) {
		if ( 0 === (int) $x->parent ) {
			$pick = $x;
			break;
		}
	}
	return pt_term_root( $pick );
}

/**
 * Top-level / primary product-category NAME for a product, e.g. "Summerhouses".
 */
function pt_product_line_name( $product_id ) {
	$t = pt_product_line_term( $product_id );
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
