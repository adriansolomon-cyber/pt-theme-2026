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

		// --- Checkout (page.php + woocommerce/checkout/form-checkout.php) -----
		if ( function_exists( 'is_checkout' ) && is_checkout() ) {
			if ( file_exists( $dir . '/assets/css/checkout.css' ) ) {
				wp_enqueue_style( 'pt-checkout', $uri . '/assets/css/checkout.css', array( 'pt-base' ), $ver( 'assets/css/checkout.css' ) );
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

/**
 * Tidy the address bar after the configurator's composite add-to-cart.
 *
 * product.js builds …/checkout/?add-to-cart=ID&wccp_component_selection[..]=..&…
 * WooCommerce processes that request on wp_loaded, so by template_redirect the
 * configured building is already in the basket. We then redirect to the SAME URL
 * minus the long query string, leaving a clean address bar. (The param is gone on
 * the redirected request, so this can't loop or re-add.)
 */
add_action(
	'template_redirect',
	function () {
		if ( wp_doing_ajax() || is_admin() ) {
			return;
		}
		if ( ! empty( $_GET['add-to-cart'] ) && isset( $_GET['wccp_component_selection'] ) ) {
			wp_safe_redirect( esc_url_raw( remove_query_arg( array_keys( $_GET ) ) ) );
			exit;
		}
	},
	20
);

/**
 * Checkout notices: hide informational notices on page load, keep errors.
 *
 * On the checkout the carried-over "added to basket" (success) and WooCommerce's
 * admin "Customer matched zone" shipping-debug (notice) messages showed on load.
 * Strip those two types right before WooCommerce outputs them, but KEEP 'error'
 * notices so validation problems still appear after the customer submits.
 */
add_action(
	'woocommerce_before_checkout_form',
	function () {
		if ( ! function_exists( 'wc_get_notices' ) || ! function_exists( 'wc_set_notices' ) || ! WC()->session ) {
			return;
		}
		$notices = wc_get_notices();                       // grouped by type
		unset( $notices['success'], $notices['notice'] );  // drop info/success, keep 'error'
		wc_set_notices( $notices );
	},
	5   // before woocommerce_output_all_notices (priority 10)
);

/**
 * Remove WooCommerce's default "Have a coupon? Click here to enter your code"
 * toggle above the checkout form. The redesign puts the promo field inside the
 * order-summary card (woocommerce/checkout/review-order.php), so the toggle is a
 * redundant second coupon UI. Runs on init, after WC registers its template hooks.
 */
add_action(
	'init',
	function () {
		remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );
	}
);

/**
 * Match the "Deliver to a different address" (shipping) fields to the checkout
 * mockup structure (projecttimber-checkout.html): First | Last, Address line 1,
 * Town/City | Postcode. Runs at priority 20 — AFTER the old theme's
 * ensure_shipping_fields_exist() (priority 10) — so it re-lays-out those fields.
 *
 * Fields not in the design (Country, Apartment, County, Phone, Email, Company) are
 * hidden via a class, not removed, so form submission / validation is unchanged.
 * Country is defaulted to the store base country and kept (hidden) so shipping still
 * resolves. Billing is untouched.
 */
add_filter(
	'woocommerce_checkout_fields',
	function ( $fields ) {
		if ( empty( $fields['shipping'] ) || ! is_array( $fields['shipping'] ) ) {
			return $fields;
		}

		$shipping = &$fields['shipping'];

		$set = function ( &$group, $key, $class, $priority, $clear, $label = null ) {
			if ( ! isset( $group[ $key ] ) ) {
				return;
			}
			$group[ $key ]['class']    = (array) $class;
			$group[ $key ]['priority'] = $priority;
			$group[ $key ]['clear']    = (bool) $clear;
			if ( null !== $label ) {
				$group[ $key ]['label'] = $label;
			}
		};

		// Visible fields — laid out to match the mockup grid.
		$set( $shipping, 'shipping_first_name', array( 'form-row-first' ), 10, false, __( 'First name', 'woocommerce' ) );
		$set( $shipping, 'shipping_last_name', array( 'form-row-last' ), 20, true, __( 'Last name', 'woocommerce' ) );
		$set( $shipping, 'shipping_address_1', array( 'form-row-wide' ), 40, true, __( 'Address line 1', 'woocommerce' ) );
		$set( $shipping, 'shipping_city', array( 'form-row-first' ), 60, false, __( 'Town / City', 'woocommerce' ) );
		$set( $shipping, 'shipping_postcode', array( 'form-row-last' ), 70, true, __( 'Postcode', 'woocommerce' ) );

		// Keep the base country on the (hidden) country field so shipping still resolves.
		if ( isset( $shipping['shipping_country'] ) ) {
			$base = function_exists( 'wc_get_base_location' ) ? wc_get_base_location() : array();
			$shipping['shipping_country']['default'] = ( is_array( $base ) && ! empty( $base['country'] ) ) ? $base['country'] : 'GB';
		}

		// Fields not in the design — hidden (kept in the form so nothing breaks).
		foreach ( array( 'shipping_country', 'shipping_address_2', 'shipping_state', 'shipping_phone', 'shipping_email', 'shipping_company' ) as $k ) {
			if ( isset( $shipping[ $k ] ) ) {
				$existing              = isset( $shipping[ $k ]['class'] ) ? (array) $shipping[ $k ]['class'] : array();
				$shipping[ $k ]['class'] = array_merge( $existing, array( 'pt-ship-hidden' ) );
			}
		}

		return $fields;
	},
	20
);

/**
 * Enqueue the WooCommerce notice auto-hide handler on checkout + cart.
 * (Hides info/success notices on load, auto-dismisses later ones, keeps errors.)
 */
add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! function_exists( 'is_checkout' ) || ! ( is_checkout() || is_cart() ) ) {
			return;
		}
		$dir = get_stylesheet_directory();
		$file = $dir . '/assets/js/wc-notices.js';
		if ( file_exists( $file ) ) {
			wp_enqueue_script(
				'pt-wc-notices',
				get_stylesheet_directory_uri() . '/assets/js/wc-notices.js',
				array(),
				wp_get_theme()->get( 'Version' ) . '.' . filemtime( $file ),
				true
			);
		}
	},
	20
);
