<?php
/**
 * Single-product SETUP (shared).
 *
 * Extracted from single-product.php so the content previewer
 * (product-preview/single-product-preview.php, via ?pid= on a Page) reuses the exact
 * same rendering. Expects $pt_pid to be set; defines $pt_name/$pt_product/
 * $pt_line/$pt_from and the $pt_f/$pt_show/$pt_has_rows helpers,
 * $pt_hero_img, and generates the product JSON-LD.
 *
 * @package pt-theme-2026
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! isset( $pt_pid ) ) { $pt_pid = get_queried_object_id(); }
$pt_name    = get_the_title( $pt_pid );                       // product title (e.g. "Consort Summerhouse")
// Short name for the sub-nav: everything before the first spaced dash. Use the
// RAW post title — get_the_title() texturizes the dash into &#8211; which a
// character-class split would miss.
$pt_name_short = trim( preg_split( '/\s+[-–—]\s+/u', get_post_field( 'post_title', $pt_pid ), 2 )[0] );
if ( '' === $pt_name_short ) {
	$pt_name_short = $pt_name;
}
$pt_product = function_exists( 'wc_get_product' ) ? wc_get_product( $pt_pid ) : null;
$pt_line    = pt_product_line_singular( $pt_pid );            // singular category (e.g. "Summerhouse")
if ( '' === $pt_line ) {
	$pt_line = $pt_name;
}
$pt_from = $pt_product ? pt_product_from_price_display( $pt_product ) : '';
if ( '' === $pt_from ) {
	$pt_from = 'From £—';
}
// --- Editable content (ACF field group "Product Page Content") -------------
// pt_f() returns an ACF field value, or the supplied static fallback when the
// field is empty / ACF is inactive — so a product with no content filled still
// renders exactly the original design. pt_has_rows() guards repeater loops.
$pt_f = function ( $name, $fallback = '' ) use ( $pt_pid ) {
	if ( ! function_exists( 'get_field' ) ) {
		return $fallback;
	}
	$v = get_field( $name, $pt_pid );
	if ( null === $v || '' === $v || false === $v || array() === $v ) {
		return $fallback;
	}
	return $v;
};
$pt_has_rows = function ( $name ) use ( $pt_pid ) {
	return function_exists( 'have_rows' ) && have_rows( $name, $pt_pid );
};
// pt_show() controls per-product section visibility (ACF "Section visibility"
// true_false fields). Defaults to shown when the field is unset / ACF inactive,
// so existing products keep every section until an editor turns one off.
$pt_show = function ( $name, $default = true ) use ( $pt_pid ) {
	if ( ! function_exists( 'get_field' ) ) {
		return $default;
	}
	$v = get_field( $name, $pt_pid );
	if ( null === $v || '' === $v ) {
		return $default;
	}
	return (bool) $v;
};

$pt_hero_img = $pt_f(
	'hero_image',
	has_post_thumbnail( $pt_pid )
		? get_the_post_thumbnail_url( $pt_pid, 'large' )
		: 'https://www.projecttimber.com/wp-content/uploads/2026/06/My_Den_Composite_Garden_Office-scaled.webp'
);

// Configurator preview initial image — the product's own image (featured, else
// ACF product_image_1, else hero), not a My Den placeholder.
$pt_cfg_img = has_post_thumbnail( $pt_pid ) ? get_the_post_thumbnail_url( $pt_pid, 'large' ) : '';
if ( '' === $pt_cfg_img ) {
	$pt_cfg_img = function_exists( 'get_field' ) ? get_field( 'product_image_1', $pt_pid ) : '';
}
if ( ! is_string( $pt_cfg_img ) || '' === $pt_cfg_img ) {
	$pt_cfg_img = $pt_hero_img;
}

// Product structured data (JSON-LD). This custom template fires none of WooCommerce's
// single-product hooks, so WC never generates it — trigger it here. WooCommerce outputs
// the assembled markup on wp_footer (footer.php calls wp_footer), and the migrated
// fix_composite_product_price_schema filter (legacy-functions.php) applies during generation.
if ( $pt_product && function_exists( 'WC' ) && WC()->structured_data ) {
	$GLOBALS['product'] = $pt_product;                 // WC generator reads the global $product
	WC()->structured_data->generate_product_data();
}
