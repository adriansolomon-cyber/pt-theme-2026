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
 * Silence WordPress 6.7's "_load_textdomain_just_in_time was called incorrectly"
 * notices. Several third-party WooCommerce extensions (cost-of-goods,
 * order-status-manager, PIP, sequential-order-numbers-pro, …) load their text
 * domains before the `init` hook, which WP 6.7+ flags. It's plugin-side and not
 * fixable from the theme, so suppress just this one doing_it_wrong check — every
 * other doing_it_wrong warning still fires. Registered here (before the includes
 * below) so it is in place as early as the theme can hook it.
 */
add_filter(
	'doing_it_wrong_trigger_error',
	function ( $trigger, $function_name = '' ) {
		return ( '_load_textdomain_just_in_time' === $function_name ) ? false : $trigger;
	},
	10,
	2
);

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
			// mini-cart.js reads the WooCommerce Store API nonce from this global,
			// plus whether cart/checkout prices are shown incl. tax so its subtotal
			// and discount lines match the checkout summary (Store API returns those
			// two ex-tax while the grand total is incl-tax — mixing them looks wrong).
			wp_add_inline_script(
				'pt-mini-cart',
				'window.wcStoreApiNonce=' . wp_json_encode( wp_create_nonce( 'wc_store_api' ) ) . ';'
					. 'window.PT_CART_INCL_TAX=' . ( 'incl' === get_option( 'woocommerce_tax_display_cart' ) ? 'true' : 'false' ) . ';',
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
				// Bestseller sizes (legacy ACF field 'best_seller_sizes'): a list of size names
				// flagged as bestsellers. product.js uses it to build the size filter
				// ("Bestsellers" + by depth). Normalise to a flat array of strings.
				$pt_best     = function_exists( 'get_field' ) ? get_field( 'best_seller_sizes', get_queried_object_id() ) : null;
				$pt_best_arr = array();
				if ( is_array( $pt_best ) ) {
					foreach ( $pt_best as $pt_b ) {
						if ( is_string( $pt_b ) ) {
							$pt_best_arr[] = trim( $pt_b );
						} elseif ( is_array( $pt_b ) ) {
							$pt_v = reset( $pt_b );
							if ( is_string( $pt_v ) ) {
								$pt_best_arr[] = trim( $pt_v );
							}
						}
					}
				} elseif ( is_string( $pt_best ) && '' !== trim( $pt_best ) ) {
					$pt_best_arr = array_map( 'trim', preg_split( '/[\r\n,]+/', $pt_best ) );
				}
				$pt_best_arr = array_values( array_filter( $pt_best_arr ) );

				// Hand product.js the current product id + site origin (same-origin config/specs API).
				// Campaign display discount for THIS product (0 = none). Visual only —
				// the real money-off is the auto-applied coupon at checkout.
				$pt_disc = function_exists( 'pt_product_discount_pct' ) ? (float) pt_product_discount_pct( get_queried_object_id() ) : 0.0;

				wp_add_inline_script(
					'pt-product',
					'window.PT_WC_BASE=' . wp_json_encode( untrailingslashit( home_url() ) ) . ';'
					. 'window.PT_PRODUCT_ID=' . wp_json_encode( (string) get_queried_object_id() ) . ';'
					. 'window.PT_BEST_SIZES=' . wp_json_encode( $pt_best_arr ) . ';'
					. 'window.PT_DISCOUNT_PCT=' . wp_json_encode( $pt_disc ) . ';',
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
				// Campaign display discount for this category page (0 = none) + the code
				// shown on the on-card discount badge.
				$pt_cat_disc = ( function_exists( 'pt_term_discount_pct' ) && $term && isset( $term->term_id ) ) ? (float) pt_term_discount_pct( $term->term_id ) : 0.0;
				$pt_cat_code = ( function_exists( 'pt_term_discount_code' ) && $term && isset( $term->term_id ) ) ? (string) pt_term_discount_code( $term->term_id ) : '';
				wp_add_inline_script(
					'pt-category',
					'window.PT_WC_BASE=' . wp_json_encode( untrailingslashit( home_url() ) ) . ';'
					. 'window.PT_CATEGORY_SLUG=' . wp_json_encode( $slug ) . ';'
					. 'window.PT_DISCOUNT_PCT=' . wp_json_encode( $pt_cat_disc ) . ';'
					. 'window.PT_DISCOUNT_CODE=' . wp_json_encode( $pt_cat_code ) . ';',
					'before'
				);
			}
		}

		// --- Product search results (search.php) — reuses the category card grid ---
		if ( is_search() ) {
			if ( file_exists( $dir . '/assets/css/category.css' ) ) {
				wp_enqueue_style( 'pt-category', $uri . '/assets/css/category.css', array( 'pt-base' ), $ver( 'assets/css/category.css' ) );
			}
		}
	}
);

/**
 * Admin: add a live search + "selected first" ordering to the native product-category
 * checklist on the product edit screen (assets/js/admin-cat-search.js). Progressive
 * enhancement only — the checkboxes/inputs are untouched, so saving is unaffected.
 */
add_action(
	'admin_enqueue_scripts',
	function ( $hook ) {
		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'product' !== $screen->post_type ) {
			return;
		}
		$dir  = get_stylesheet_directory();
		$file = $dir . '/assets/js/admin-cat-search.js';
		if ( file_exists( $file ) ) {
			wp_enqueue_script(
				'pt-admin-cat-search',
				get_stylesheet_directory_uri() . '/assets/js/admin-cat-search.js',
				array(),
				wp_get_theme()->get( 'Version' ) . '.' . filemtime( $file ),
				true
			);
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
 * Checkout notices: hide the blue "info" notices on page load, keep the rest.
 *
 * WooCommerce's admin "Customer matched zone" shipping-debug message is a
 * 'notice' (info) type that showed on load. Strip that type right before
 * WooCommerce outputs them, but KEEP 'success' (green) notices — e.g. "coupon
 * applied" — and 'error' notices so both stay visible to the shopper.
 */
add_action(
	'woocommerce_before_checkout_form',
	function () {
		if ( ! function_exists( 'wc_get_notices' ) || ! function_exists( 'wc_set_notices' ) || ! WC()->session ) {
			return;
		}
		$notices = wc_get_notices();      // grouped by type
		unset( $notices['notice'] );      // drop info only; keep 'success' (green) + 'error'
		wc_set_notices( $notices );
	},
	5   // before woocommerce_output_all_notices (priority 10)
);

/**
 * Hide the "Customer matched zone …" shipping-debug notice from shoppers.
 *
 * That line is emitted only when WooCommerce's Shipping "debug mode" is enabled
 * (WooCommerce → Settings → Shipping → Shipping options → Enable debug mode). It's an
 * admin diagnostic and shows on EVERY calculation (cart, checkout, AJAX recalcs), which
 * is why type-stripping alone didn't catch it. Force debug mode off on the front end so
 * the notice is never generated — while leaving it untouched in wp-admin so you can
 * still debug there. The genuine "no delivery available for your area" message is a
 * separate, non-debug message and still shows when an address matches no shippable zone.
 */
add_filter(
	'option_woocommerce_shipping_debug_mode',
	function ( $value ) {
		// Keep the real setting inside wp-admin (non-AJAX); silence it everywhere shoppers see it.
		if ( is_admin() && ! wp_doing_ajax() ) {
			return $value;
		}
		return 'no';
	}
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
 * Match the checkout fields to the mockup (projecttimber-checkout.html):
 *
 *  Billing  → First | Last, Country / Region, Address line 1, Apartment,
 *             Town/City | County, Postcode. (Email/Phone kept at their default
 *             position; they are required and not part of the address block.)
 *  Shipping → First | Last, Address line 1, Town/City | Postcode. Country kept
 *             (hidden, base-country default) so shipping still resolves; the other
 *             non-design fields are hidden but left in the form.
 *
 * Runs at priority 20 — AFTER the old theme's ensure_shipping_fields_exist()
 * (priority 10) — so it re-lays-out those fields. Only class / priority / clear /
 * label are changed; nothing is removed, so submission and validation are unchanged.
 */
add_filter(
	'woocommerce_checkout_fields',
	function ( $fields ) {

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

		// ── Billing — lay out the address block to the mockup grid. ──
		if ( ! empty( $fields['billing'] ) && is_array( $fields['billing'] ) ) {
			$billing = &$fields['billing'];
			$set( $billing, 'billing_first_name', array( 'form-row-first' ), 10, false, __( 'First name', 'woocommerce' ) );
			$set( $billing, 'billing_last_name', array( 'form-row-last' ), 20, true, __( 'Last name', 'woocommerce' ) );
			$set( $billing, 'billing_country', array( 'form-row-wide' ), 30, true, __( 'Country / Region', 'woocommerce' ) );
			$set( $billing, 'billing_address_1', array( 'form-row-wide' ), 40, true, __( 'Address line 1', 'woocommerce' ) );
			$set( $billing, 'billing_address_2', array( 'form-row-wide' ), 50, true, __( 'Apartment, suite, unit, etc.', 'woocommerce' ) );
			$set( $billing, 'billing_city', array( 'form-row-first' ), 60, false, __( 'Town / City', 'woocommerce' ) );
			$set( $billing, 'billing_state', array( 'form-row-last' ), 70, true, __( 'County', 'woocommerce' ) );
			$set( $billing, 'billing_postcode', array( 'form-row-first' ), 80, true, __( 'Postcode', 'woocommerce' ) );
			unset( $billing );
		}

		// ── Shipping (Deliver to a different address). ──
		if ( ! empty( $fields['shipping'] ) && is_array( $fields['shipping'] ) ) {
			$shipping = &$fields['shipping'];
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
					$existing                = isset( $shipping[ $k ]['class'] ) ? (array) $shipping[ $k ]['class'] : array();
					$shipping[ $k ]['class'] = array_merge( $existing, array( 'pt-ship-hidden' ) );
				}
			}
			unset( $shipping );
		}

		// ── Order notes → the mockup's "Special instructions" field. ──
		if ( ! empty( $fields['order']['order_comments'] ) ) {
			$fields['order']['order_comments']['label']       = __( 'Special instructions', 'woocommerce' );
			$fields['order']['order_comments']['placeholder'] = __( 'Curbside access notes — e.g. parking, narrow road, or where to set down the delivery…', 'woocommerce' );
		}

		return $fields;
	},
	20
);

/**
 * Klaviyo renders its SMS consent disclosure at the bottom of the billing form
 * (woocommerce_after_checkout_billing_form). Our form-billing.php moves it up into the
 * Contact block instead, so drop the default placement to avoid it showing twice.
 * No-op when the Klaviyo plugin isn't active.
 */
add_action(
	'woocommerce_checkout_before_customer_details',
	function () {
		remove_filter( 'woocommerce_after_checkout_billing_form', 'kl_sms_compliance_text' );
	}
);

/**
 * Enqueue the WooCommerce notice auto-dismiss handler + its countdown-bar CSS
 * on checkout + cart. Every notice shows for 10s with a filling bar then
 * collapses away, except the voucher/discount message which stays permanently.
 */
add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! function_exists( 'is_checkout' ) || ! ( is_checkout() || is_cart() ) ) {
			return;
		}
		$dir     = get_stylesheet_directory();
		$uri     = get_stylesheet_directory_uri();
		$version = wp_get_theme()->get( 'Version' );

		$css = $dir . '/assets/css/wc-notices.css';
		if ( file_exists( $css ) ) {
			wp_enqueue_style(
				'pt-wc-notices',
				$uri . '/assets/css/wc-notices.css',
				array(),
				$version . '.' . filemtime( $css )
			);
		}

		$file = $dir . '/assets/js/wc-notices.js';
		if ( file_exists( $file ) ) {
			wp_enqueue_script(
				'pt-wc-notices',
				$uri . '/assets/js/wc-notices.js',
				array(),
				$version . '.' . filemtime( $file ),
				true
			);
		}
	},
	20
);
