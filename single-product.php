<?php
/**
 * Single product (PDP) — thin wrapper.
 *
 * The setup and body markup live in shared partials (inc/single-product-setup.php
 * and inc/single-product-body.php) so the self-contained content previewer
 * (inc/single-product-preview.php, reached via ?pt_preview=ID) can reuse the exact
 * same rendering. This wrapper keeps the live page identical: setup → header →
 * body → footer, with header/footer/support/cart-drawer from get_header()/
 * get_footer() and product.css/product.js enqueued in functions.php.
 *
 * @package pt-theme-2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pt_pid = get_queried_object_id();

require get_stylesheet_directory() . '/inc/single-product-setup.php';

get_header();
require get_stylesheet_directory() . '/inc/single-product-body.php';
get_footer();
