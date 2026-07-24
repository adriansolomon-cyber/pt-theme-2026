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
 * Build the "From £X" (discount-aware) markup for a card-data entry.
 * Mirrors pt_cat_card_html()'s price block.
 */
function pt_search_suggest_price_html( $entry ) {
	$price = isset( $entry['price'] ) ? (float) $entry['price'] : 0.0;
	$pid   = isset( $entry['id'] ) ? (int) $entry['id'] : 0;
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

	$query = new WP_Query(
		array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			's'                   => $q,
			'posts_per_page'      => 6,
			'ignore_sticky_posts' => true,
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
		)
	);

	$items = array();
	foreach ( (array) $query->posts as $post ) {
		$entry = pt_cat_product_entry( wc_get_product( $post->ID ) );
		if ( ! $entry ) {
			continue;
		}
		$items[] = array(
			'name'       => $entry['name'],
			'url'        => $entry['permalink'],
			'img'        => ! empty( $entry['images'][0]['src'] ) ? $entry['images'][0]['src'] : '',
			'price_html' => pt_search_suggest_price_html( $entry ),
		);
	}

	$out['items'] = $items;
	$out['total'] = (int) $query->found_posts;
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
