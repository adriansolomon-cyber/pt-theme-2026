<?php
/**
 * Project Timber 2026 — theme functions.
 *
 * Stage-2 (WP conversion) in progress. Homepage is now a real template
 * (front-page.php + header.php/footer.php + template-parts/). Assets are
 * enqueued here (replacing the prototype's include.js / partials-data.js).
 * Kept defensive so activation cannot fatal with or without WooCommerce.
 *
 * @package pt-theme-2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

/**
 * Theme supports.
 */
add_action(
	'after_setup_theme',
	function () {
		add_theme_support( 'title-tag' );
		add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'woocommerce' );
	}
);

/**
 * Enqueue styles & scripts.
 *
 * base.css + the mini-cart are site-wide chrome; home.css/home.js load only on
 * the front page. Versions use the theme version + filemtime so a push busts
 * the cache. Each enqueue is guarded on file existence so a moved/renamed asset
 * can never break the page.
 */
add_action(
	'wp_enqueue_scripts',
	function () {
		$dir = get_stylesheet_directory();
		$uri = get_stylesheet_directory_uri();
		$base_ver = wp_get_theme()->get( 'Version' );

		$ver = function ( $rel ) use ( $dir, $base_ver ) {
			$path = $dir . '/' . $rel;
			return file_exists( $path ) ? $base_ver . '.' . filemtime( $path ) : $base_ver;
		};

		// --- Site-wide chrome -------------------------------------------------
		if ( file_exists( $dir . '/assets/css/base.css' ) ) {
			wp_enqueue_style( 'pt-base', $uri . '/assets/css/base.css', array(), $ver( 'assets/css/base.css' ) );
		}

		if ( file_exists( $dir . '/assets/js/mini-cart.js' ) ) {
			wp_enqueue_script( 'pt-mini-cart', $uri . '/assets/js/mini-cart.js', array(), $ver( 'assets/js/mini-cart.js' ), true );
			// mini-cart.js reads the WooCommerce Store API nonce from this global.
			wp_add_inline_script(
				'pt-mini-cart',
				'window.wcStoreApiNonce=' . wp_json_encode( wp_create_nonce( 'wc_store_api' ) ) . ';',
				'before'
			);
		}

		// --- Homepage only ----------------------------------------------------
		if ( is_front_page() ) {
			if ( file_exists( $dir . '/assets/css/home.css' ) ) {
				wp_enqueue_style( 'pt-home', $uri . '/assets/css/home.css', array( 'pt-base' ), $ver( 'assets/css/home.css' ) );
			}
			if ( file_exists( $dir . '/assets/js/home.js' ) ) {
				wp_enqueue_script( 'pt-home', $uri . '/assets/js/home.js', array(), $ver( 'assets/js/home.js' ), true );
			}
		}
	}
);

/**
 * URL helpers used in the templates. Fall back to sensible defaults when
 * WooCommerce is not active so templates never call an undefined function.
 */
if ( ! function_exists( 'pt_account_url' ) ) {
	function pt_account_url() {
		if ( function_exists( 'wc_get_page_permalink' ) ) {
			$url = wc_get_page_permalink( 'myaccount' );
			if ( $url ) {
				return $url;
			}
		}
		return home_url( '/my-account/' );
	}
}

if ( ! function_exists( 'pt_checkout_url' ) ) {
	function pt_checkout_url() {
		if ( function_exists( 'wc_get_checkout_url' ) ) {
			$url = wc_get_checkout_url();
			if ( $url ) {
				return $url;
			}
		}
		return home_url( '/checkout/' );
	}
}
