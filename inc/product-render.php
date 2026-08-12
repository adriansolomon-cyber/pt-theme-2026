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
	if ( $product->is_type( 'variable' ) ) {
		return (float) $product->get_variation_price( 'min', true );
	}
	if ( $product->is_type( 'composite' ) ) {
		$min = INF;

		// Fast path: mu-plugin size-option resolver when present.
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

		// Native fallback: walk the "Size" component directly (composites report 0
		// at the parent level, so without this the page shows "From £—"). This keeps
		// the single-product page self-sufficient without the mu-plugin — same logic
		// as pt_cat_product_from_price() used by the category grid.
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

/**
 * Cached "from" price for a product. Composite from-pricing walks every size
 * option (many product loads), which is too slow for the interactive search
 * typeahead to repeat each keystroke. Cache per product, keyed by a global
 * version that bumps whenever any product is saved (see pt_from_price_cache_ver).
 */
function pt_from_price_cache_ver() {
	return (int) get_option( 'pt_fp_ver', 1 );
}
function pt_product_from_price_cached( $product ) {
	if ( ! is_object( $product ) || ! is_callable( array( $product, 'get_id' ) ) ) {
		return 0.0;
	}
	$key    = 'pt_fp_' . pt_from_price_cache_ver() . '_' . (int) $product->get_id();
	$cached = get_transient( $key );
	if ( false !== $cached ) {
		return (float) $cached;
	}
	$price = function_exists( 'pt_cat_product_from_price' )
		? (float) pt_cat_product_from_price( $product )
		: (float) pt_product_from_price( $product );
	set_transient( $key, $price, 12 * HOUR_IN_SECONDS );
	return $price;
}

/**
 * Tracking item data (dataLayer view_item) for the CURRENT request.
 *
 * For a composite: if the URL names a size (e.g. 12-x-8, incl. the /f/ path)
 * that size's sub-product supplies the id, variant and price; otherwise the
 * cheapest size does. The price is the campaign-DISCOUNTED size price, rounded
 * to whole £ (matching the on-page price + the site's whole-pound convention),
 * so view_item and add_to_cart agree. `category` is the parent's primary
 * product category. Mirrors fix_composite_product_price_schema(). Not cached —
 * the result depends on the request URL.
 *
 * @param WC_Product $product Product.
 * @return array{price:float,item_id:string,variant:string,category:string}
 */
function pt_product_tracking_item( $product ) {
	$out = array(
		'price'    => 0.0,
		'item_id'  => '',
		'variant'  => '',
		'category' => '',
	);
	if ( ! $product || ! function_exists( 'wc_get_product' ) || ! is_callable( array( $product, 'get_id' ) ) ) {
		return $out;
	}
	$out['item_id']  = (string) $product->get_id(); // parent by default.
	$out['category'] = pt_product_primary_category_name( $product );

	if ( ! $product->is_type( 'composite' ) ) {
		$out['price'] = pt_apply_tracking_discount( $product, (float) pt_product_from_price( $product ) );
		return $out;
	}

	// Size in the URL? e.g. /summerhouses/12-x-8/f/slug/ or /12-x-8/slug/.
	$url_size = '';
	$uri      = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
	if ( preg_match( '/(\d+-x-\d+)/', $uri, $m ) ) {
		$url_size = $m[1];
	}

	// Collect the size-option product ids (fast mu-plugin path, else the Size component).
	$size_ids = function_exists( 'timber_catp_size_options' ) ? (array) timber_catp_size_options( $product ) : array();
	if ( empty( $size_ids ) && is_callable( array( $product, 'get_components' ) ) ) {
		foreach ( (array) $product->get_components() as $component ) {
			$title = ( is_object( $component ) && is_callable( array( $component, 'get_title' ) ) ) ? (string) $component->get_title() : '';
			if ( 'size' === strtolower( trim( $title ) ) ) {
				$size_ids = is_callable( array( $component, 'get_options' ) ) ? (array) $component->get_options() : array();
				break;
			}
		}
	}

	$min          = INF;
	$min_id       = '';
	$min_name     = '';
	$matched      = null;
	$matched_id   = '';
	$matched_name = '';
	foreach ( $size_ids as $oid ) {
		$o = wc_get_product( (int) $oid );
		if ( ! $o ) {
			continue;
		}
		$p = (float) $o->get_price();
		if ( $p <= 0 ) {
			continue;
		}
		if ( $p < $min ) {
			$min      = $p;
			$min_id   = (string) $o->get_id();
			$min_name = $o->get_name();
		}
		if ( '' !== $url_size && null === $matched ) {
			$name_slug = str_replace( ' ', '-', $o->get_name() ); // "12 x 8" -> "12-x-8".
			if ( $name_slug === $url_size || false !== strpos( $name_slug, $url_size ) ) {
				$matched      = $p;
				$matched_id   = (string) $o->get_id();
				$matched_name = $o->get_name();
			}
		}
	}

	$raw = 0.0;
	if ( null !== $matched ) {
		$raw            = $matched;
		$out['item_id'] = $matched_id;
		$out['variant'] = $matched_name;
	} elseif ( INF !== $min ) {
		$raw            = $min;
		$out['item_id'] = $min_id;
		$out['variant'] = $min_name;
	} else {
		$raw = (float) pt_product_from_price( $product ); // item_id stays parent.
	}

	$out['price'] = pt_apply_tracking_discount( $product, $raw );
	return $out;
}

/**
 * Apply the campaign display discount to a tracking price and round to whole £.
 * Keeps view_item / add_to_cart consistent with the on-page (rounded) price.
 *
 * @param WC_Product $product Product (for its discount %).
 * @param float      $price   Regular price.
 * @return float
 */
function pt_apply_tracking_discount( $product, $price ) {
	$price = (float) $price;
	if ( $price <= 0 ) {
		return 0.0;
	}
	$pct = ( function_exists( 'pt_product_discount_pct' ) && is_object( $product ) && is_callable( array( $product, 'get_id' ) ) )
		? (float) pt_product_discount_pct( $product->get_id() )
		: 0.0;
	if ( $pct > 0 ) {
		$price = $price * ( 1 - $pct / 100 );
	}
	return (float) round( $price ); // whole £, matches the displayed price.
}

/**
 * Primary product-category name (first non-"uncategorized" term), used as
 * item_category in tracking. Empty string when none.
 *
 * @param WC_Product $product Product.
 * @return string
 */
function pt_product_primary_category_name( $product ) {
	if ( ! is_object( $product ) || ! is_callable( array( $product, 'get_id' ) ) ) {
		return '';
	}
	$terms = get_the_terms( $product->get_id(), 'product_cat' );
	if ( ! $terms || is_wp_error( $terms ) ) {
		return '';
	}
	foreach ( $terms as $t ) {
		if ( isset( $t->slug ) && 'uncategorized' !== $t->slug ) {
			return (string) $t->name;
		}
	}
	return isset( $terms[0]->name ) ? (string) $terms[0]->name : '';
}

/**
 * Convenience: just the tracking price. @see pt_product_tracking_item().
 *
 * @param WC_Product $product Product.
 * @return float
 */
function pt_product_tracking_price( $product ) {
	$item = pt_product_tracking_item( $product );
	return (float) $item['price'];
}

/**
 * On product-category pages, output window.PT_LIST_PRICES = { productId: price }
 * so the dataLayer cleaner can backfill the Stape view_item_list, whose composite
 * items otherwise report 0.00 (get_price() = 0 for composites). Matches the
 * plugin's list query (limit 10, title ASC) and uses the cached, discounted
 * "from" price. Emitted at wp_footer priority 1 — before the plugin's push (10).
 */
add_action( 'wp_footer', 'pt_output_list_prices', 1 );
function pt_output_list_prices() {
	if ( ! function_exists( 'is_product_category' ) || ! is_product_category() ) {
		return;
	}
	if ( ! function_exists( 'wc_get_products' ) || ! function_exists( 'pt_product_from_price_cached' ) ) {
		return;
	}
	$term = get_queried_object();
	if ( ! ( $term instanceof WP_Term ) ) {
		return;
	}

	$products = wc_get_products(
		array(
			'product_category_id' => $term->term_id,
			'limit'               => 10,
			'orderby'             => 'title',
			'order'               => 'ASC',
		)
	);

	$map = array();
	foreach ( (array) $products as $p ) {
		if ( ! is_object( $p ) || ! is_callable( array( $p, 'get_id' ) ) ) {
			continue;
		}
		$price = pt_apply_tracking_discount( $p, (float) pt_product_from_price_cached( $p ) );
		if ( $price > 0 ) {
			$map[ (string) $p->get_id() ] = $price;
		}
	}
	if ( empty( $map ) ) {
		return;
	}
	echo '<script>window.PT_LIST_PRICES=' . wp_json_encode( $map ) . ';</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Bump the from-price cache version on any product change. Composite prices
 * depend on their size-option products, so a version bump (rather than deleting
 * one key) is the simple correct way to invalidate all of them at once.
 */
function pt_bump_from_price_cache_ver() {
	update_option( 'pt_fp_ver', pt_from_price_cache_ver() + 1, false );
}
// Tie invalidation to real edits (save_post_product / new product) — NOT
// woocommerce_update_product, which also fires on stock decrements during orders
// and would needlessly bust the cache on a busy store. 12h TTL is the backstop.
add_action( 'save_post_product', 'pt_bump_from_price_cache_ver' );
add_action( 'woocommerce_new_product', 'pt_bump_from_price_cache_ver' );

/**
 * Per-size technical-drawing images for the Specifications section, from the
 * product's ACF "dynamic_sliders" repeater (each tab has tab_name + an images
 * repeater of { image }). We use the "Tech Specs"-style tab and key each image
 * by the size parsed from its filename/title (e.g. "…12x8…" → "12x8"), so the
 * specs diagram can switch with the selected size.
 *
 * Returns array( '12x8' => url, '12x6' => url, … ) — empty when none.
 */
function pt_spec_size_images( $product_id ) {
	if ( ! function_exists( 'get_field' ) ) {
		return array();
	}
	$sliders = get_field( 'dynamic_sliders', $product_id );
	if ( ! is_array( $sliders ) || empty( $sliders ) ) {
		return array();
	}

	// Prefer a "spec"-named tab; fall back to every tab so it still works if the
	// tab is named differently — only size-parseable images are ever added.
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
			// Resolve URL + a haystack to parse the size from (handles the image
			// field's url / array / id return formats).
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
			// Size like 12x8 / 12 x 8 (× too). Not "-" — filenames use "01-1" suffixes.
			if ( preg_match( '/(\d+)\s*[x×]\s*(\d+)/i', $hay, $m ) ) {
				$key = $m[1] . 'x' . $m[2];
				if ( ! isset( $map[ $key ] ) ) { // first (preferred tab) wins
					$map[ $key ] = $url;
				}
			}
		}
	}
	return $map;
}

/** "From £1,234" (or empty string if no price). */
function pt_product_from_price_html( $product ) {
	$p = pt_product_from_price( $product );
	return $p > 0 ? 'From £' . number_format( round( $p ), 0, '.', ',' ) : '';
}

/**
 * "From" price as display HTML, with the campaign discount applied when active:
 *   no discount → "From £1,234"
 *   discount    → 'From <span class="was">£1,234</span><span class="now">£1,111</span>'
 * Mirrors the configurator's was/now treatment (.was/.now styles already exist).
 * Output is safe markup — echo through wp_kses_post(). Empty string if no price.
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
