<?php
/**
 * Preview dependencies (portable fallbacks).
 *
 * The previewer reuses helpers that normally live in the new theme
 * (inc/product-render.php). When the product-preview/ folder is dropped into a
 * DIFFERENT active theme (e.g. the current live "theTimber"), those helpers are
 * not loaded and the preview fatals with "Call to undefined function …".
 *
 * This file defines just the helpers the previewer needs, each guarded by
 * function_exists() so it is a no-op when the new theme is active (no redeclare
 * conflict) and a safe fallback everywhere else. Kept in sync with
 * inc/product-render.php.
 *
 * pt_product_discount_pct() is intentionally NOT bundled — its campaign chain is
 * deep and every caller already guards it with function_exists(), so without it
 * the preview simply shows undiscounted prices (acceptable for a content preview).
 *
 * @package pt-theme-2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'pt_product_from_price' ) ) {
	/**
	 * "From" price for a product (composite → cheapest size option, variable →
	 * lowest variation, else product price). Returns a float (0 if unavailable).
	 */
	function pt_product_from_price( $product ) {
		if ( ! $product || ! function_exists( 'wc_get_product' ) ) {
			return 0.0;
		}
		if ( $product->is_type( 'variable' ) ) {
			return (float) $product->get_variation_price( 'min', true );
		}
		if ( $product->is_type( 'composite' ) ) {
			$min = INF;

			if ( function_exists( 'timber_catp_size_options' ) ) {
				foreach ( (array) timber_catp_size_options( $product ) as $oid ) {
					$o = wc_get_product( (int) $oid );
					if ( $o ) {
						$p = (float) $o->get_price();
						if ( $p > 0 && $p < $min ) {
							$min = $p;
						}
					}
				}
			}

			if ( INF === $min && is_callable( array( $product, 'get_components' ) ) ) {
				foreach ( (array) $product->get_components() as $component ) {
					$title = ( is_object( $component ) && is_callable( array( $component, 'get_title' ) ) ) ? (string) $component->get_title() : '';
					if ( 'size' !== strtolower( trim( $title ) ) ) {
						continue;
					}
					$opts = is_callable( array( $component, 'get_options' ) ) ? (array) $component->get_options() : array();
					foreach ( $opts as $oid ) {
						$op = wc_get_product( (int) $oid );
						if ( ! $op ) {
							continue;
						}
						$pr = (float) $op->get_price();
						if ( $pr > 0 && $pr < $min ) {
							$min = $pr;
						}
					}
				}
			}

			if ( INF !== $min ) {
				return $min;
			}
			return (float) $product->get_price();
		}
		return (float) $product->get_price();
	}
}

if ( ! function_exists( 'pt_product_from_price_html' ) ) {
	/** "From £1,234" (or empty string if no price). */
	function pt_product_from_price_html( $product ) {
		$p = pt_product_from_price( $product );
		return $p > 0 ? 'From £' . number_format( round( $p ), 0, '.', ',' ) : '';
	}
}

if ( ! function_exists( 'pt_product_from_price_display' ) ) {
	/**
	 * "From" price as display HTML, with the campaign discount applied when the
	 * discount helper is available (else just the plain from-price).
	 */
	function pt_product_from_price_display( $product ) {
		$p = pt_product_from_price( $product );
		if ( $p <= 0 ) {
			return '';
		}
		$gbp = function ( $n ) {
			return '£' . number_format( round( $n ), 0, '.', ',' );
		};
		$pct = ( $product && function_exists( 'pt_product_discount_pct' ) ) ? (float) pt_product_discount_pct( $product->get_id() ) : 0.0;
		if ( $pct > 0 ) {
			$d = $p - ( $p * $pct / 100 );
			return 'From <span class="was">' . $gbp( $p ) . '</span><span class="now">' . $gbp( $d ) . '</span>';
		}
		return 'From ' . $gbp( $p );
	}
}

if ( ! function_exists( 'pt_spec_size_images' ) ) {
	/**
	 * Per-size technical-drawing images for the Specifications section, from the
	 * product's ACF "dynamic_sliders" repeater. Returns array( '12x8' => url, … ).
	 */
	function pt_spec_size_images( $product_id ) {
		if ( ! function_exists( 'get_field' ) ) {
			return array();
		}
		$sliders = get_field( 'dynamic_sliders', $product_id );
		if ( ! is_array( $sliders ) || empty( $sliders ) ) {
			return array();
		}

		$preferred = array();
		$others    = array();
		foreach ( $sliders as $tab ) {
			$name = isset( $tab['tab_name'] ) ? strtolower( (string) $tab['tab_name'] ) : '';
			if ( false !== strpos( $name, 'spec' ) ) {
				$preferred[] = $tab;
			} else {
				$others[] = $tab;
			}
		}

		$map = array();
		foreach ( array_merge( $preferred, $others ) as $tab ) {
			$images = ( isset( $tab['images'] ) && is_array( $tab['images'] ) ) ? $tab['images'] : array();
			foreach ( $images as $row ) {
				$img = isset( $row['image'] ) ? $row['image'] : null;
				if ( ! $img ) {
					continue;
				}
				if ( is_array( $img ) ) {
					$url = isset( $img['url'] ) ? $img['url'] : '';
					$hay = trim( ( isset( $img['filename'] ) ? $img['filename'] : '' ) . ' ' . ( isset( $img['title'] ) ? $img['title'] : '' ) . ' ' . ( isset( $img['alt'] ) ? $img['alt'] : '' ) . ' ' . $url );
				} elseif ( is_numeric( $img ) ) {
					$url = wp_get_attachment_url( (int) $img );
					$hay = get_the_title( (int) $img ) . ' ' . $url;
				} else {
					$url = (string) $img;
					$hay = $url;
				}
				if ( ! $url ) {
					continue;
				}
				if ( preg_match( '/(\d+)\s*[x×]\s*(\d+)/i', $hay, $m ) ) {
					$key = $m[1] . 'x' . $m[2];
					if ( ! isset( $map[ $key ] ) ) {
						$map[ $key ] = $url;
					}
				}
			}
		}
		return $map;
	}
}

if ( ! function_exists( 'pt_term_root' ) ) {
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
}

if ( ! function_exists( 'pt_product_line_term' ) ) {
	/** The product's "line" term (Yoast primary, else first non-promo category root). */
	function pt_product_line_term( $product_id ) {
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
			$candidates = $terms;
		}

		$pick = $candidates[0];
		foreach ( $candidates as $x ) {
			if ( 0 === (int) $x->parent ) {
				$pick = $x;
				break;
			}
		}
		return pt_term_root( $pick );
	}
}

if ( ! function_exists( 'pt_product_line_name' ) ) {
	/** Top-level / primary product-category NAME for a product, e.g. "Summerhouses". */
	function pt_product_line_name( $product_id ) {
		$t = pt_product_line_term( $product_id );
		return $t ? $t->name : '';
	}
}

if ( ! function_exists( 'pt_singularize' ) ) {
	/** Naive singular for plural category names ("Summerhouses" → "Summerhouse"). */
	function pt_singularize( $s ) {
		$s = trim( (string) $s );
		if ( '' === $s || preg_match( '/ss$/i', $s ) ) {
			return $s;
		}
		return preg_replace( '/s$/i', '', $s );
	}
}

if ( ! function_exists( 'pt_product_line_singular' ) ) {
	/** The product's category "line" name, singularised, for headings like "Build your {X}". */
	function pt_product_line_singular( $product_id ) {
		return pt_singularize( pt_product_line_name( $product_id ) );
	}
}
