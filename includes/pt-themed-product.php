<?php
/**
 * Themed product page — use-case "themes" (Workshop default + Cycle Hub / Mancave /
 * Home Gym / Secure Storage). Content lives in the standalone ACF group
 * "Themed Product Page" (acf-json/group_pt_themed_product.json). A theme is chosen
 * by ?theme=<slug>; with none the page is the normal PDP.
 *
 * Helpers only — rendering is in single-product.php / template parts.
 *
 * @package pt-theme-2026
 */

defined( 'ABSPATH' ) || exit;

/** The theme keys (ACF field-name prefixes) that the group defines, in order. */
function pt_themed_keys() {
	return (array) apply_filters( 'pt_themed_keys', array( 'cyclehub', 'mancave', 'homegym', 'securestorage' ) );
}

/** Is the themed layout enabled for this product? */
function pt_themed_enabled( $product_id ) {
	return function_exists( 'get_field' ) && (bool) get_field( 'tp_enable', (int) $product_id );
}

/**
 * The ENABLED themes for a product, for the switcher.
 *
 * @return array[] each: [ 'key' => string, 'slug' => string, 'name' => string ]
 */
function pt_themed_list( $product_id ) {
	if ( ! pt_themed_enabled( $product_id ) ) {
		return array();
	}
	$out = array();
	foreach ( pt_themed_keys() as $key ) {
		if ( ! get_field( "tp_{$key}_enable", (int) $product_id ) ) {
			continue;
		}
		$name = trim( (string) get_field( "tp_{$key}_name", (int) $product_id ) );
		if ( '' === $name ) {
			$name = ucwords( str_replace( array( '_', '-' ), ' ', $key ) );
		}
		$slug = trim( (string) get_field( "tp_{$key}_slug", (int) $product_id ) );
		if ( '' === $slug ) {
			$slug = sanitize_title( $name );
		}
		$out[] = array( 'key' => $key, 'slug' => $slug, 'name' => $name );
	}
	return $out;
}

/**
 * The active theme for the current request (from ?theme=), validated against the
 * product's enabled themes. Null for the default (no/invalid theme).
 *
 * @return array|null [ 'key', 'slug', 'name' ]
 */
function pt_themed_active( $product_id ) {
	$req = isset( $_GET['theme'] ) ? sanitize_title( wp_unslash( $_GET['theme'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only view param
	if ( '' === $req ) {
		return null;
	}
	foreach ( pt_themed_list( $product_id ) as $t ) {
		if ( $t['slug'] === $req ) {
			return $t;
		}
	}
	return null;
}

/** Full content for a theme key (banner, headline, gallery, features, checklist, why, popup). */
function pt_themed_data( $product_id, $key ) {
	$pid = (int) $product_id;
	$g   = function ( $f ) use ( $pid, $key ) {
		return get_field( "tp_{$key}_{$f}", $pid );
	};
	return array(
		'banner_image'       => (string) $g( 'banner_image' ),
		'banner_title'       => (string) $g( 'banner_title' ),
		'banner_subtitle'    => (string) $g( 'banner_subtitle' ),
		'headline_line1'     => (string) $g( 'headline_line1' ),
		'headline_highlight' => (string) $g( 'headline_highlight' ),
		'gallery'            => (array) ( $g( 'gallery' ) ?: array() ),   // rows: shot_label, shot_image
		'features'           => (array) ( $g( 'features' ) ?: array() ),  // rows: feat_image, feat_title, feat_body
		'checklist'          => (array) ( $g( 'checklist' ) ?: array() ), // rows: feature
		'why_heading'        => (string) $g( 'why_heading' ),
		'why_text'           => (string) $g( 'why_text' ),
		'popup_title'        => (string) $g( 'popup_title' ),
		'popup_image'        => (string) $g( 'popup_image' ),
		'popup_bullets'      => (array) ( $g( 'popup_bullets' ) ?: array() ), // rows: bullet
		'popup_cta_label'    => (string) $g( 'popup_cta_label' ),
		'popup_cta_link'     => (string) $g( 'popup_cta_link' ),
	);
}

/** Product URL for a theme slug ('' = the default, strips ?theme=). */
function pt_themed_url( $permalink, $slug = '' ) {
	return $slug ? add_query_arg( 'theme', rawurlencode( $slug ), $permalink ) : remove_query_arg( 'theme', $permalink );
}
