<?php
/**
 * Server-side category rendering — PHP port of assets/js/category.js's card,
 * facet and filter logic, so product-category archives render their grid on the
 * server (WooCommerce native). Output markup + data-attributes are identical to
 * what category.js produced, so the same CSS styles it and the same JS filters
 * and sorts it (in its "server-rendered mode").
 *
 * Primary data source is the mu-plugin's timber_catp_build($slug) (composite
 * "from" pricing, direct-members-only). If that isn't present, pt_cat_native_build()
 * produces the same shape with native WC getters so the theme still works standalone.
 *
 * @package pt-theme-2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Filter facets, in drawer order — mirrors FACETS in category.js. */
function pt_cat_facets() {
	return array(
		array( 'key' => 'range',        'attr' => 'Range',     'title' => 'Product range', 'open' => true ),
		array( 'key' => 'size',         'attr' => 'Size',      'title' => 'Size',          'open' => false ),
		array( 'key' => 'treatment',    'attr' => 'Treatment', 'title' => 'Treatment',     'open' => false ),
		array( 'key' => 'windows',      'attr' => 'Windows',   'title' => 'Windows',       'open' => false ),
		array( 'key' => 'roof',         'attr' => 'Style',     'title' => 'Roof style',    'open' => false ),
		array( 'key' => 'availability', 'attr' => null,        'title' => 'Availability',  'open' => false ),
	);
}

/** slugify() from category.js. */
function pt_cat_slugify( $s ) {
	$s = strtolower( (string) $s );
	$s = str_replace( '&amp;', '', $s );
	$s = preg_replace( '/[^a-z0-9]+/', '-', $s );
	return trim( $s, '-' );
}

/** fmt() from category.js — "£1,234". */
function pt_cat_fmt( $n ) {
	return '£' . number_format( round( (float) $n ), 0, '.', ',' );
}

/** facetLabel() from category.js. */
function pt_cat_facet_label( $key, $v ) {
	$s = str_replace( '&amp;', '&', (string) $v );
	if ( 'size' === $key ) {
		$s = preg_replace( '/\s*x\s*/i', ' × ', $s );
	}
	return $s;
}

/** attrOpts() — options of a named attribute in the {name,options} array. */
function pt_cat_attr_options( $attributes, $name ) {
	$name = strtolower( $name );
	foreach ( (array) $attributes as $a ) {
		if ( isset( $a['name'] ) && strtolower( $a['name'] ) === $name ) {
			return isset( $a['options'] ) ? (array) $a['options'] : array();
		}
	}
	return array();
}

/** sizeVal() — [area, w, h] parsed from a size label. */
function pt_cat_size_val( $name ) {
	preg_match_all( '/\d+(?:\.\d+)?/', (string) $name, $m );
	$n = array_map( 'floatval', $m[0] );
	$w = isset( $n[0] ) ? $n[0] : 0;
	$h = isset( $n[1] ) ? $n[1] : 0;
	return array( $w * $h, $w, $h );
}

/** compact() — "10 x 8" → "10×8". */
function pt_cat_compact( $name ) {
	$s = preg_replace( '/\s*x\s*/i', '×', (string) $name );
	return preg_replace( '/\s+/', '', $s );
}

/** facetValues() — [{slug,label}] for one facet of one product. */
function pt_cat_facet_values( $p, $f ) {
	$out = array();
	if ( ! empty( $f['attr'] ) ) {
		$opts = pt_cat_attr_options( isset( $p['attributes'] ) ? $p['attributes'] : array(), $f['attr'] );
		foreach ( $opts as $v ) {
			$out[] = array( 'slug' => pt_cat_slugify( $v ), 'label' => pt_cat_facet_label( $f['key'], $v ) );
		}
	} elseif ( 'availability' === $f['key'] ) {
		if ( ( isset( $p['stock_status'] ) ? $p['stock_status'] : '' ) === 'instock' ) {
			$out[] = array( 'slug' => 'instock', 'label' => 'In stock' );
		}
	}
	return $out;
}

/** facetData() — the data-f-* attribute string for a card. */
function pt_cat_facet_data_attr( $p ) {
	$out = array();
	foreach ( pt_cat_facets() as $f ) {
		$slugs = array();
		foreach ( pt_cat_facet_values( $p, $f ) as $v ) {
			$slugs[] = $v['slug'];
		}
		if ( $slugs ) {
			$out[] = 'data-f-' . $f['key'] . '="' . esc_attr( implode( ' ', $slugs ) ) . '"';
		}
	}
	return $out ? ' ' . implode( ' ', $out ) : '';
}

/** sizesLine() — "3 sizes · 10×6–10×10". */
function pt_cat_sizes_line( $p ) {
	$opts = pt_cat_attr_options( isset( $p['attributes'] ) ? $p['attributes'] : array(), 'size' );
	if ( empty( $opts ) ) {
		return '';
	}
	usort(
		$opts,
		function ( $a, $b ) {
			$A = pt_cat_size_val( $a );
			$B = pt_cat_size_val( $b );
			return ( $A[0] <=> $B[0] ) ?: ( $A[1] <=> $B[1] );
		}
	);
	$n  = count( $opts );
	$lo = pt_cat_compact( $opts[0] );
	$hi = pt_cat_compact( $opts[ $n - 1 ] );
	return $n . ' size' . ( $n > 1 ? 's' : '' ) . ' · ' . ( $lo === $hi ? $lo : $lo . '–' . $hi );
}

/** cardHTML() — one product card, matching category.js output. */
function pt_cat_card_html( $p ) {
	$imgs = array();
	foreach ( (array) ( isset( $p['images'] ) ? $p['images'] : array() ) as $im ) {
		if ( ! empty( $im['src'] ) ) {
			$imgs[] = $im['src'];
		}
	}
	$img0   = isset( $imgs[0] ) ? $imgs[0] : '';
	$img1   = isset( $imgs[1] ) ? $imgs[1] : $img0;
	$price  = isset( $p['price'] ) ? (float) $p['price'] : 0.0;
	$name   = isset( $p['name'] ) ? $p['name'] : '';
	$href   = isset( $p['permalink'] ) ? $p['permalink'] : '#';
	$sizes  = pt_cat_sizes_line( $p );
	$facets = pt_cat_facet_data_attr( $p );

	// Campaign display discount for this product (visual only; the real money-off is
	// the auto-applied coupon at checkout). 0 when no campaign applies.
	$pid   = isset( $p['id'] ) ? (int) $p['id'] : 0;
	$disc  = ( $pid && function_exists( 'pt_product_discount_pct' ) ) ? (float) pt_product_discount_pct( $pid ) : 0.0;
	$dcode = ( $disc > 0 && $pid && function_exists( 'pt_product_discount_code' ) ) ? (string) pt_product_discount_code( $pid ) : '';

	if ( $price > 0 && $disc > 0 ) {
		$now        = $price - ( $price * $disc / 100 );
		$price_html = 'From <span class="was">' . esc_html( pt_cat_fmt( $price ) ) . '</span> <b class="now">' . esc_html( pt_cat_fmt( $now ) ) . '</b>';
	} elseif ( $price > 0 ) {
		$price_html = 'From <b>' . esc_html( pt_cat_fmt( $price ) ) . '</b>';
	} else {
		$price_html = 'View options';
	}

	$badge = ( $disc > 0 )
		? '<div class="pbadge"><b>' . esc_html( round( $disc ) ) . '% off</b>' . ( '' !== $dcode ? '<span>Code ' . esc_html( $dcode ) . '</span>' : '' ) . '</div>'
		: '';

	$h  = '<a class="prod" href="' . esc_url( $href ) . '" data-price="' . esc_attr( $price ) . '"' . $facets . '>';
	$h .= '<div class="ph duo">';
	if ( $img0 ) {
		$h .= '<img class="pimg" src="' . esc_url( $img0 ) . '" alt="' . esc_attr( $name ) . '">';
	}
	if ( $img1 ) {
		$h .= '<img class="pscene" src="' . esc_url( $img1 ) . '" alt="" aria-hidden="true">';
	}
	$h .= $badge;
	$h .= '</div>';
	$h .= '<div class="pbody"><h3>' . esc_html( $name ) . '</h3><div class="pprice">' . $price_html . '</div>';
	if ( $sizes ) {
		$h .= '<div class="psizes">' . esc_html( $sizes ) . '</div>';
	}
	$h .= '</div></a>';
	return $h;
}

/** buildFilters() — the drawer's facet groups. */
function pt_cat_filters_html( $products ) {
	$html = '';
	foreach ( pt_cat_facets() as $f ) {
		$counts = array();
		$labels = array();
		foreach ( $products as $p ) {
			$seen = array();
			foreach ( pt_cat_facet_values( $p, $f ) as $v ) {
				if ( isset( $seen[ $v['slug'] ] ) ) {
					continue;
				}
				$seen[ $v['slug'] ]   = 1;
				$counts[ $v['slug'] ] = ( isset( $counts[ $v['slug'] ] ) ? $counts[ $v['slug'] ] : 0 ) + 1;
				$labels[ $v['slug'] ] = $v['label'];
			}
		}
		$keys = array_keys( $counts );
		if ( ! $keys ) {
			continue;
		}
		if ( 'size' === $f['key'] ) {
			usort(
				$keys,
				function ( $a, $b ) use ( $labels ) {
					$A = pt_cat_size_val( $labels[ $a ] );
					$B = pt_cat_size_val( $labels[ $b ] );
					return ( $A[0] <=> $B[0] ) ?: ( $A[1] <=> $B[1] );
				}
			);
		} else {
			usort(
				$keys,
				function ( $a, $b ) use ( $labels ) {
					return strcasecmp( (string) $labels[ $a ], (string) $labels[ $b ] );
				}
			);
		}
		$opts = '';
		foreach ( $keys as $k ) {
			$opts .= '<label class="fopt"><input type="checkbox" value="' . esc_attr( $k ) . '"> ' . esc_html( $labels[ $k ] ) . ' <span class="ct">' . (int) $counts[ $k ] . '</span></label>';
		}
		$html .= '<details class="fgroup"' . ( ! empty( $f['open'] ) ? ' open' : '' ) . '><summary>' . esc_html( $f['title'] ) . '</summary><div class="opts" data-filter="' . esc_attr( $f['key'] ) . '">' . $opts . '</div></details>';
	}
	return $html;
}

/** paragraphsHTML() — plain text → <p> blocks. */
function pt_cat_paragraphs_html( $text ) {
	$parts = preg_split( '/\r?\n\s*\r?\n/', trim( (string) $text ) );
	$out   = '';
	foreach ( $parts as $p ) {
		$p = trim( $p );
		if ( '' !== $p ) {
			$out .= '<p>' . nl2br( esc_html( $p ) ) . '</p>';
		}
	}
	return $out;
}

/** Intro lede text — renderDescription()'s truncated summary of the category description. */
function pt_cat_intro_desc( $desc ) {
	$desc = trim( (string) $desc );
	if ( '' === $desc ) {
		return '';
	}
	if ( preg_match( '/<(p|h[1-6]|ul|ol|div|section|br)\b/i', $desc ) ) {
		$t = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $desc ) ) );
		return ( mb_strlen( $t ) > 240 ) ? ( preg_replace( '/\s+\S*$/', '', mb_substr( $t, 0, 237 ) ) . '…' ) : $t;
	}
	$parts = preg_split( '/\r?\n\s*\r?\n/', $desc );
	return trim( $parts[0] );
}

/**
 * "From" price for a category card: the cheapest starting price.
 *  - variable  → min variation price
 *  - composite → cheapest Size-component option price (composites report 0 at the
 *                parent level, which is why cards showed "View options")
 *  - else      → own price
 */
function pt_cat_product_from_price( $product ) {
	if ( ! is_object( $product ) ) {
		return 0.0;
	}
	if ( $product->is_type( 'variable' ) ) {
		return (float) $product->get_variation_price( 'min', true );
	}
	if ( $product->is_type( 'composite' ) && is_callable( array( $product, 'get_components' ) ) ) {
		$min = null;
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
				if ( $pr > 0 && ( null === $min || $pr < $min ) ) {
					$min = $pr;
				}
			}
		}
		if ( null !== $min ) {
			return $min;
		}
	}
	return (float) $product->get_price();
}

/**
 * Build one product's card-data array (the shape pt_cat_card_html() expects):
 * id, name, permalink, up-to-2 images, composite-aware "from" price, attributes
 * (for facets) and stock status. Returns null for a non-product. Reused by the
 * category grid and the product search results page.
 */
function pt_cat_product_entry( $product ) {
	if ( ! is_object( $product ) || ! is_callable( array( $product, 'get_id' ) ) ) {
		return null;
	}
	$pid   = (int) $product->get_id();
	$price = pt_cat_product_from_price( $product );

	$imgs = array();
	$main = $product->get_image_id();
	if ( $main ) {
		$u = wp_get_attachment_image_url( $main, 'woocommerce_single' );
		if ( $u ) {
			$imgs[] = array( 'src' => $u );
		}
	}
	foreach ( (array) $product->get_gallery_image_ids() as $g ) {
		if ( count( $imgs ) >= 2 ) {
			break;
		}
		$u = wp_get_attachment_image_url( $g, 'woocommerce_single' );
		if ( $u ) {
			$imgs[] = array( 'src' => $u );
		}
	}

	$attrs = array();
	foreach ( (array) $product->get_attributes() as $attr ) {
		if ( ! is_object( $attr ) || ! is_callable( array( $attr, 'get_name' ) ) ) {
			continue;
		}
		$nm = function_exists( 'wc_attribute_label' ) ? wc_attribute_label( $attr->get_name(), $product ) : $attr->get_name();
		if ( is_callable( array( $attr, 'is_taxonomy' ) ) && $attr->is_taxonomy() ) {
			$terms   = wc_get_product_terms( $pid, $attr->get_name(), array( 'fields' => 'names' ) );
			$options = is_array( $terms ) ? array_values( $terms ) : array();
		} else {
			$options = array_values( (array) $attr->get_options() );
		}
		$attrs[] = array( 'name' => (string) $nm, 'options' => array_map( 'strval', $options ) );
	}

	return array(
		'id'           => $pid,
		'name'         => wp_strip_all_tags( $product->get_name() ),
		'permalink'    => get_permalink( $pid ),
		'images'       => $imgs,
		'price'        => $price,
		'attributes'   => $attrs,
		'stock_status' => $product->get_stock_status(),
	);
}

/**
 * Native fallback build (same shape as the mu-plugin's timber_catp_build) using
 * WC getters, so the category grid renders even without the mu-plugin — including
 * proper composite "from" pricing (pt_cat_product_from_price). Cached per term.
 */
function pt_cat_native_build( $term ) {
	$empty = array( 'category' => array( 'name' => '', 'description' => '' ), 'products' => array() );
	if ( ! $term || is_wp_error( $term ) || ! function_exists( 'wc_get_product' ) ) {
		return $empty;
	}

	// Cache the (price-resolving) build — composite from-pricing walks every size
	// option, so recomputing on every request is wasteful.
	$cache_key = 'pt_catn_' . (int) $term->term_id;
	if ( ! isset( $_GET['nocache'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$cached = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}
	}

	$q = new WP_Query(
		array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'orderby'        => 'menu_order title',
			'order'          => 'ASC',
			'tax_query'      => array(
				array(
					'taxonomy'         => 'product_cat',
					'field'            => 'term_id',
					'terms'            => (int) $term->term_id,
					'include_children' => false,
				),
			),
		)
	);
	$products = array();
	foreach ( (array) $q->posts as $pid ) {
		$entry = pt_cat_product_entry( wc_get_product( (int) $pid ) );
		if ( $entry ) {
			$products[] = $entry;
		}
	}
	$result = array(
		'category' => array(
			'id'          => (int) $term->term_id,
			'name'        => $term->name,
			'slug'        => $term->slug,
			'description' => term_description( $term->term_id, 'product_cat' ),
		),
		'products' => $products,
	);
	set_transient( $cache_key, $result, HOUR_IN_SECONDS );
	return $result;
}

/**
 * Resolve category data for the current term: mu-plugin build if available,
 * else native. Returns array( 'category' => [...], 'products' => [...] ).
 */
function pt_cat_get_data( $term ) {
	$slug = ( $term && isset( $term->slug ) ) ? $term->slug : '';
	if ( $slug && function_exists( 'timber_catp_build' ) ) {
		$built = timber_catp_build( $slug );
		if ( ! is_wp_error( $built ) && is_array( $built ) ) {
			return $built;
		}
	}
	return pt_cat_native_build( $term );
}
