<?php
/**
 * Project Timber 2026 — theme functions.
 *
 * Stage-1 scaffold: kept intentionally tiny and defensive so activation cannot
 * fatal. It only (a) declares WooCommerce support to silence the "theme does not
 * declare WooCommerce support" notice, and (b) enqueues the shared base
 * stylesheet. Full asset wiring, template parts and enqueues arrive in stage-2.
 *
 * @package pt-theme-2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

add_action(
	'after_setup_theme',
	function () {
		add_theme_support( 'title-tag' );
		add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'woocommerce' );
	}
);

add_action(
	'wp_enqueue_scripts',
	function () {
		$dir = get_stylesheet_directory();
		$uri = get_stylesheet_directory_uri();
		$ver = wp_get_theme()->get( 'Version' );

		// Only enqueue if the file is actually present, so a partial/renamed
		// asset tree can never cause a broken enqueue.
		if ( file_exists( $dir . '/assets/css/base.css' ) ) {
			wp_enqueue_style( 'pt-base', $uri . '/assets/css/base.css', array(), $ver );
		}
	}
);
