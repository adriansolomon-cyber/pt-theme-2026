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

// Server-side category grid rendering (PHP port of category.js).
require_once get_stylesheet_directory() . '/inc/category-render.php';
// Single-product dynamic helpers (from price, category-line name).
require_once get_stylesheet_directory() . '/inc/product-render.php';

// ── Back-office migration from the old theme (verbatim; loaded in phases) ──
require_once get_stylesheet_directory() . '/inc/order-statuses.php';   // custom WC order statuses

// Phase 2 — shared helper includes (verbatim from the old theme's includes/).
require_once get_stylesheet_directory() . '/includes/woocommerce-filters.php';
require_once get_stylesheet_directory() . '/includes/custom-functions.php';
require_once get_stylesheet_directory() . '/includes/wc-custom-checkout-functions.php';
require_once get_stylesheet_directory() . '/includes/woo-google-tracking-events-datalayer.php';
require_once get_stylesheet_directory() . '/includes/woo-google-ads-tracking.php';
require_once get_stylesheet_directory() . '/includes/woocommerce-my-account.php';
require_once get_stylesheet_directory() . '/includes/integrations/optimo/optimo-integrations-functions.php';
require_once get_stylesheet_directory() . '/includes/woo-lead-time-calculator-control.php';

// Phase 3 — remaining back-office, migrated verbatim. Root-level file so its
// __DIR__-relative includes (inc/*-email.php etc.) resolve as in the old theme.
require_once get_stylesheet_directory() . '/legacy-functions.php';

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
 * Disable WordPress's emoji rewriting. Its detection script converts characters like
 * "▶" (U+25B6, used for the play badges on the product page) into a coloured twemoji
 * image, which broke the design's white play triangles. The static prototype has no
 * such script, so removing it makes the glyphs render natively as designed.
 */
add_action(
	'init',
	function () {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		add_filter(
			'tiny_mce_plugins',
			function ( $plugins ) {
				return is_array( $plugins ) ? array_diff( $plugins, array( 'wpemoji' ) ) : $plugins;
			}
		);
		add_filter(
			'wp_resource_hints',
			function ( $urls, $relation_type ) {
				if ( 'dns-prefetch' === $relation_type ) {
					$urls = array_filter(
						$urls,
						function ( $url ) {
							return false === strpos( is_array( $url ) ? ( $url['href'] ?? '' ) : $url, 's.w.org' );
						}
					);
				}
				return $urls;
			},
			10,
			2
		);
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

		// --- Single product (single-product.php) ------------------------------
		if ( function_exists( 'is_product' ) && is_product() ) {
			if ( file_exists( $dir . '/assets/css/product.css' ) ) {
				wp_enqueue_style( 'pt-product', $uri . '/assets/css/product.css', array( 'pt-base' ), $ver( 'assets/css/product.css' ) );
			}
			if ( file_exists( $dir . '/assets/js/product.js' ) ) {
				wp_enqueue_script( 'pt-product', $uri . '/assets/js/product.js', array(), $ver( 'assets/js/product.js' ), true );
				// Hand product.js the current product id + site origin (same-origin config/specs API).
				wp_add_inline_script(
					'pt-product',
					'window.PT_WC_BASE=' . wp_json_encode( untrailingslashit( home_url() ) ) . ';'
					. 'window.PT_PRODUCT_ID=' . wp_json_encode( (string) get_queried_object_id() ) . ';',
					'before'
				);
			}
		}

		// --- Product-category archive (taxonomy-product_cat.php) --------------
		if ( function_exists( 'is_product_category' ) && is_product_category() ) {
			if ( file_exists( $dir . '/assets/css/category.css' ) ) {
				wp_enqueue_style( 'pt-category', $uri . '/assets/css/category.css', array( 'pt-base' ), $ver( 'assets/css/category.css' ) );
			}
			if ( file_exists( $dir . '/assets/js/category.js' ) ) {
				wp_enqueue_script( 'pt-category', $uri . '/assets/js/category.js', array(), $ver( 'assets/js/category.js' ), true );
				// Hand category.js the exact queried term slug + the site origin so its API
				// calls are same-origin (staging/live) rather than the hardcoded production URL.
				$term = get_queried_object();
				$slug = ( $term && isset( $term->slug ) ) ? $term->slug : '';
				wp_add_inline_script(
					'pt-category',
					'window.PT_WC_BASE=' . wp_json_encode( untrailingslashit( home_url() ) ) . ';'
					. 'window.PT_CATEGORY_SLUG=' . wp_json_encode( $slug ) . ';',
					'before'
				);
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
