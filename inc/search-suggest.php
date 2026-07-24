<?php
/**
 * Live search suggestions endpoint — GET /wp-json/pt/v1/search?q=<term>
 *
 * Powers the header typeahead. Returns up to 6 configurable products matching
 * the term, built with the SAME helpers as the search results / category grid
 * (pt_cat_product_entry + composite from-pricing + campaign discount), so the
 * preview matches the full results page exactly. Excludes catalog-hidden
 * products and the simple size sub-products / bundles.
 *
 * @package pt-theme-2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'pt/v1',
			'/search',
			array(
				'methods'             => 'GET',
				'callback'            => 'pt_search_suggest_rest',
				'permission_callback' => '__return_true',
				'args'                => array(
					'q' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}
);

/**
 * Title-only product search. The default WP search scans post_title + excerpt +
 * post_content across the whole (very large) product catalogue before the
 * tax_query drops simple/bundle rows — slow and noisy. When our queries set the
 * pt_title_only flag, restrict the search SQL to post_title only: much faster
 * and more relevant. Scoped by the global flag so it never affects other search.
 */
add_filter(
	'posts_search',
	function ( $search, $wp_query ) {
		if ( empty( $GLOBALS['pt_search_title_only'] ) || '' === $search ) {
			return $search;
		}
		global $wpdb;
		$terms = ! empty( $wp_query->query_vars['search_terms'] ) ? (array) $wp_query->query_vars['search_terms'] : array();
		if ( empty( $terms ) ) {
			return $search;
		}
		$sql = '';
		$and = '';
		foreach ( $terms as $term ) {
			$like = '%' . $wpdb->esc_like( $term ) . '%';
			$sql .= $and . $wpdb->prepare( "{$wpdb->posts}.post_title LIKE %s", $like );
			$and  = ' AND ';
		}
		return $sql ? " AND ({$sql}) " : $search;
	},
	10,
	2
);

/**
 * Run a product search (title-only, configurable products only, catalog-visible)
 * and return the WP_Query. Shared by the typeahead endpoint and search.php so
 * both behave identically.
 */
function pt_product_search( $term, $per_page = 6, $paged = 1, $count_total = true ) {
	$args = array(
		'post_type'           => 'product',
		'post_status'         => 'publish',
		's'                   => $term,
		'posts_per_page'      => (int) $per_page,
		'paged'               => max( 1, (int) $paged ),
		'ignore_sticky_posts' => true,
		'no_found_rows'       => ! $count_total, // skip row count when the caller won't paginate
		'tax_query'           => array(
			'relation' => 'AND',
			array(
				'taxonomy' => 'product_visibility',
				'field'    => 'name',
				'terms'    => array( 'exclude-from-search' ),
				'operator' => 'NOT IN',
			),
			array(
				'taxonomy' => 'product_type',
				'field'    => 'slug',
				'terms'    => array( 'simple', 'bundle' ),
				'operator' => 'NOT IN',
			),
		),
	);

	$GLOBALS['pt_search_title_only'] = true;   // enable the posts_search filter for THIS query
	$query                           = new WP_Query( $args );
	$GLOBALS['pt_search_title_only'] = false;
	return $query;
}

/**
 * Build the "From £X" (discount-aware) markup for a card-data entry.
 * Mirrors pt_cat_card_html()'s price block.
 */
function pt_search_suggest_price_html( $price, $pid ) {
	$price = (float) $price;
	$pid   = (int) $pid;
	$disc  = ( $pid && function_exists( 'pt_product_discount_pct' ) ) ? (float) pt_product_discount_pct( $pid ) : 0.0;

	if ( $price > 0 && $disc > 0 ) {
		$now = $price - ( $price * $disc / 100 );
		return 'From <span class="was">' . esc_html( pt_cat_fmt( $price ) ) . '</span> <b class="now">' . esc_html( pt_cat_fmt( $now ) ) . '</b>';
	}
	if ( $price > 0 ) {
		return 'From <b>' . esc_html( pt_cat_fmt( $price ) ) . '</b>';
	}
	return 'View options';
}

/**
 * REST callback: return { q, total, url, items:[ {name, url, img, price_html} ] }.
 */
function pt_search_suggest_rest( WP_REST_Request $req ) {
	$q   = trim( (string) $req->get_param( 'q' ) );
	$out = array(
		'q'     => $q,
		'total' => 0,
		'url'   => '',
		'items' => array(),
	);

	if ( mb_strlen( $q ) < 2 || ! function_exists( 'wc_get_product' ) ) {
		return rest_ensure_response( $out );
	}

	$cache_key = 'pt_ss_' . md5( strtolower( $q ) );
	$cached    = get_transient( $cache_key );
	if ( is_array( $cached ) ) {
		return rest_ensure_response( $cached );
	}

	// no_found_rows: skip the SQL_CALC_FOUND_ROWS total — the typeahead doesn't use it.
	$query = pt_product_search( $q, 6, 1, false );

	$items = array();
	foreach ( (array) $query->posts as $post ) {
		$product = wc_get_product( $post->ID );
		if ( ! $product ) {
			continue;
		}
		$pid    = (int) $post->ID;
		// Lightweight: only the thumbnail + a cached from-price — skip the gallery
		// and attribute build that pt_cat_product_entry does (unused in the preview).
		$img_id = $product->get_image_id();
		$img    = $img_id ? wp_get_attachment_image_url( $img_id, 'woocommerce_thumbnail' ) : '';
		$items[] = array(
			'name'       => wp_strip_all_tags( $product->get_name() ),
			'url'        => get_permalink( $pid ),
			'img'        => $img ? $img : '',
			'price_html' => pt_search_suggest_price_html( pt_product_from_price_cached( $product ), $pid ),
		);
	}

	$out['items'] = $items;
	$out['total'] = count( $items );
	$out['url']   = add_query_arg(
		array(
			's'         => rawurlencode( $q ),
			'post_type' => 'product',
		),
		home_url( '/' )
	);

	set_transient( $cache_key, $out, 30 ); // brief cache smooths fast typing
	return rest_ensure_response( $out );
}
