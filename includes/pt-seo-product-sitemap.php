<?php
/**
 * PT SEO — custom product sitemap, per-size canonicals, and bundle/parts noindex.
 * =============================================================================
 *
 * WHAT THIS DOES (four independent pieces, each individually switchable):
 *
 *   A. NOINDEX the machinery products that should never be in Google's index:
 *      every `bundle` (size/config child, e.g. /bundles/16mm-shiplap-7/) and
 *      everything in the `parts` category (/parts/…). Yoast already keeps these
 *      out of the sitemap, but nothing tells Google to DROP the ~33k already
 *      indexed — this does (noindex,follow).
 *
 *   B. Per-size CANONICALS (the "Fork 1" model). Each composite renders the same
 *      page at many size URLs, e.g.
 *          /summerhouses/8-x-6/f/<slug>/   (this is what the ADS FEED uses)
 *      We make each size URL self-canonical (so it can be indexed on its own),
 *      and point the bare parent permalink /summerhouses/<slug>/ at the cheapest
 *      ("from") size, so the parent doesn't compete with its own children.
 *
 *   C. Per-size TITLE differentiation — prefixes the size onto the <title> on a
 *      size URL so the indexed pages read distinctly ("8 x 6 – Cannes…").
 *
 *   D. A custom PRODUCT sitemap at /pt-products.xml listing the size (feed) URLs
 *      for every composite + the permalinks of genuine simple products (parts
 *      excluded). It is injected into Yoast's existing sitemap_index.xml, and
 *      Yoast's own (bundle/parts-bloated, 34-mostly-empty-slots) product sitemap
 *      is removed from the index. Same sitemap_index.xml URL → no GSC change.
 *
 * ENABLE / DISABLE
 *   • Whole module: comment out its require in functions.php, OR
 *     define('PT_SEO_MODULE', false) in wp-config.php.
 *   • A single piece: return false from its filter, e.g.
 *       add_filter('pt_seo_enable_noindex',   '__return_false');
 *       add_filter('pt_seo_enable_canonical', '__return_false');
 *       add_filter('pt_seo_enable_title',     '__return_false');
 *       add_filter('pt_seo_enable_sitemap',   '__return_false');
 *
 * Keeps Yoast for all on-page meta (titles, descriptions, canonical output, OG,
 * schema) — this only STEERS Yoast via its documented filters, plus serves one
 * extra sitemap file.
 *
 * @package pt-theme-2026
 */

defined( 'ABSPATH' ) || exit;

// Master switch (default on). Set PT_SEO_MODULE=false in wp-config.php to kill it.
if ( defined( 'PT_SEO_MODULE' ) && ! PT_SEO_MODULE ) {
	return;
}

/** Small helper: is a given piece enabled? Default true, filterable per piece. */
function pt_seo_enabled( $piece ) {
	return (bool) apply_filters( 'pt_seo_enable_' . $piece, true );
}

/* =========================================================================
 * SHARED: resolve a composite's size options.
 * Mirrors inc/product-render.php (fast mu-plugin path → native Size component).
 * Returns a list of ['id','name','slug','price'] for options whose name is a
 * clean "W x H" size, sorted cheapest-first. Cached per product per request.
 * ========================================================================= */
function pt_seo_composite_sizes( $product ) {
	static $cache = array();

	if ( ! $product || ! is_callable( array( $product, 'get_id' ) ) || ! function_exists( 'wc_get_product' ) ) {
		return array();
	}
	$pid = (int) $product->get_id();
	if ( isset( $cache[ $pid ] ) ) {
		return $cache[ $pid ];
	}

	$option_ids = array();
	if ( function_exists( 'timber_catp_size_options' ) ) {
		$option_ids = (array) timber_catp_size_options( $product );
	}
	if ( empty( $option_ids ) && is_callable( array( $product, 'get_components' ) ) ) {
		foreach ( (array) $product->get_components() as $component ) {
			$title = ( is_object( $component ) && is_callable( array( $component, 'get_title' ) ) ) ? (string) $component->get_title() : '';
			if ( 'size' === strtolower( trim( $title ) ) ) {
				$option_ids = is_callable( array( $component, 'get_options' ) ) ? (array) $component->get_options() : array();
				break;
			}
		}
	}

	$sizes = array();
	$seen  = array();
	foreach ( $option_ids as $oid ) {
		$o = wc_get_product( (int) $oid );
		if ( ! $o ) {
			continue;
		}
		$slug = str_replace( ' ', '-', strtolower( trim( (string) $o->get_name() ) ) );
		// Only accept clean "12-x-8" style slugs — these are the URLs the router
		// resolves and the feed uses; skip anything else (labels, extras, dupes).
		if ( ! preg_match( '/^\d+-x-\d+$/', $slug ) || isset( $seen[ $slug ] ) ) {
			continue;
		}
		$seen[ $slug ] = true;
		$sizes[]       = array(
			'id'    => (int) $o->get_id(),
			'name'  => (string) $o->get_name(),
			'slug'  => $slug,
			'price' => (float) $o->get_price(),
		);
	}

	// Cheapest first, so element 0 is the "from" / base size.
	usort(
		$sizes,
		static function ( $a, $b ) {
			if ( $a['price'] === $b['price'] ) {
				return 0;
			}
			return ( $a['price'] < $b['price'] ) ? -1 : 1;
		}
	);

	$cache[ $pid ] = $sizes;
	return $sizes;
}

/**
 * Build the feed-style size URL for a product + size slug, by reusing the
 * product's OWN permalink category segment (so it matches WooCommerce and the
 * ads feed exactly): /summerhouses/<slug>/ → /summerhouses/<size>/f/<slug>/.
 */
function pt_seo_size_url( $product, $size_slug ) {
	// get_permalink() needs a post ID / WP_Post — a WC_Product object returns false.
	$pid       = ( is_object( $product ) && is_callable( array( $product, 'get_id' ) ) ) ? (int) $product->get_id() : (int) $product;
	$permalink = $pid ? get_permalink( $pid ) : false;
	if ( ! $permalink ) {
		return '';
	}
	$path = (string) wp_parse_url( $permalink, PHP_URL_PATH ); // /summerhouses/<slug>/
	$segs = array_values( array_filter( explode( '/', $path ), 'strlen' ) );
	if ( empty( $segs ) ) {
		return '';
	}
	$prod_slug = array_pop( $segs );          // <slug>
	$cat_path  = implode( '/', $segs );        // summerhouses (or nested a/b)
	$prefix    = $cat_path ? '/' . $cat_path . '/' : '/';
	return home_url( $prefix . $size_slug . '/f/' . $prod_slug . '/' );
}

/** The current request's size slug, if it names one (e.g. /…/12-x-8/f/…). */
function pt_seo_request_size() {
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	if ( is_string( $uri ) && preg_match( '/(\d+-x-\d+)/', $uri, $m ) ) {
		return $m[1];
	}
	return '';
}

/* =========================================================================
 * A. NOINDEX bundles + parts (deindex the machinery Google still holds).
 * ========================================================================= */
add_filter(
	'wpseo_robots_array',
	static function ( $robots ) {
		if ( ! pt_seo_enabled( 'noindex' ) || ! is_singular( 'product' ) || ! function_exists( 'wc_get_product' ) ) {
			return $robots;
		}
		$product = wc_get_product( get_queried_object_id() );
		if ( ! $product ) {
			return $robots;
		}
		$is_part = has_term( 'parts', 'product_cat', $product->get_id() );
		if ( $product->is_type( 'bundle' ) || $is_part ) {
			$robots['index'] = 'noindex'; // keep follow so link equity flows to the parent.
		}
		return $robots;
	},
	10,
	1
);

/* =========================================================================
 * B. Per-size canonical (Fork 1): size URL → itself; bare parent → base size.
 * ========================================================================= */
function pt_seo_composite_canonical( $canonical ) {
	if ( ! pt_seo_enabled( 'canonical' ) || ! is_singular( 'product' ) || ! function_exists( 'wc_get_product' ) ) {
		return $canonical;
	}
	$product = wc_get_product( get_queried_object_id() );
	if ( ! $product || ! $product->is_type( 'composite' ) ) {
		return $canonical;
	}
	$sizes = pt_seo_composite_sizes( $product );
	if ( empty( $sizes ) ) {
		return $canonical; // no clean sizes → leave Yoast's default (parent permalink).
	}

	$valid = wp_list_pluck( $sizes, 'slug' );
	$req   = pt_seo_request_size();
	if ( $req && in_array( $req, $valid, true ) ) {
		$url = pt_seo_size_url( $product, $req );   // self-canonical size page.
		return $url ? $url : $canonical;
	}

	// Bare parent permalink (no size in URL) → cheapest ("from") size.
	$url = pt_seo_size_url( $product, $sizes[0]['slug'] );
	return $url ? $url : $canonical;
}
add_filter( 'wpseo_canonical', 'pt_seo_composite_canonical', 10, 1 );
add_filter( 'wpseo_opengraph_url', 'pt_seo_composite_canonical', 10, 1 ); // keep og:url in step.

/* =========================================================================
 * C. Per-size <title> differentiation, so indexed size pages read distinctly.
 * ========================================================================= */
add_filter(
	'wpseo_title',
	static function ( $title ) {
		if ( ! pt_seo_enabled( 'title' ) || ! is_singular( 'product' ) || ! function_exists( 'wc_get_product' ) ) {
			return $title;
		}
		$size = pt_seo_request_size();
		if ( '' === $size ) {
			return $title;
		}
		$product = wc_get_product( get_queried_object_id() );
		if ( ! $product || ! $product->is_type( 'composite' ) ) {
			return $title;
		}
		$label = str_replace( '-', ' ', $size ); // "12-x-8" → "12 x 8".
		// Don't double-up if the size already appears in the title.
		if ( false !== stripos( $title, $label ) ) {
			return $title;
		}
		return $label . ' – ' . $title;
	},
	10,
	1
);

/* =========================================================================
 * D. Custom product sitemap at /pt-products.xml, injected into Yoast's index.
 * ========================================================================= */

/** Remove Yoast's own (bloated) product sitemap from its index + generation. */
add_filter(
	'wpseo_sitemap_exclude_post_type',
	static function ( $excluded, $post_type ) {
		if ( pt_seo_enabled( 'sitemap' ) && 'product' === $post_type ) {
			return true;
		}
		return $excluded;
	},
	10,
	2
);

/** Add our product sitemap line to Yoast's sitemap_index.xml. */
add_filter(
	'wpseo_sitemap_index',
	static function ( $links ) {
		if ( ! pt_seo_enabled( 'sitemap' ) ) {
			return $links;
		}
		$loc     = home_url( '/pt-products.xml' );
		$lastmod = get_option( 'pt_products_sitemap_lastmod' );
		$lastmod = $lastmod ? gmdate( 'c', (int) $lastmod ) : gmdate( 'c' );
		return $links . '<sitemap><loc>' . esc_url( $loc ) . '</loc><lastmod>' . esc_html( $lastmod ) . '</lastmod></sitemap>';
	},
	10,
	1
);

/**
 * Serve /pt-products.xml (named without "-sitemap" so it bypasses Yoast's route).
 * On template_redirect (not init) so WooCommerce's product post type + product_type
 * taxonomy — registered on init:5 — are available to the sitemap query. Priority 0
 * so it runs before the theme's own routing redirects.
 */
add_action(
	'template_redirect',
	static function () {
		if ( ! pt_seo_enabled( 'sitemap' ) ) {
			return;
		}
		$path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';
		if ( 'pt-products.xml' !== trim( $path, '/' ) ) {
			return;
		}

		// TEMP DIAGNOSTIC: /pt-products.xml?pt_debug=1 bypasses caches and reports
		// where the build finds zero. Remove once the sitemap is confirmed populated.
		if ( isset( $_GET['pt_debug'] ) ) {
			delete_transient( 'pt_products_sitemap_xml_v2' );
			$dbg_comp = function_exists( 'get_posts' ) ? (array) get_posts(
				array(
					'post_type'      => 'product',
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'no_found_rows'  => true,
					'tax_query'      => array( array( 'taxonomy' => 'product_type', 'field' => 'slug', 'terms' => array( 'composite' ) ) ), // phpcs:ignore
				)
			) : array();
			$dbg_terms = array();
			if ( taxonomy_exists( 'product_type' ) ) {
				foreach ( (array) get_terms( array( 'taxonomy' => 'product_type', 'hide_empty' => false ) ) as $t ) {
					if ( is_object( $t ) ) {
						$dbg_terms[] = $t->slug . ':' . $t->count;
					}
				}
			}
			$sample_id  = ! empty( $dbg_comp ) ? (int) $dbg_comp[0] : 0;
			$sample_pl  = $sample_id ? get_permalink( $sample_id ) : '(none)';
			$sample_sz  = ( $sample_id && function_exists( 'wc_get_product' ) ) ? count( pt_seo_composite_sizes( wc_get_product( $sample_id ) ) ) : 0;
			status_header( 200 );
			header( 'Content-Type: text/plain; charset=UTF-8' );
			echo "wc_get_product=" . ( function_exists( 'wc_get_product' ) ? 'yes' : 'no' ) . "\n";
			echo "product_type taxonomy exists=" . ( taxonomy_exists( 'product_type' ) ? 'yes' : 'no' ) . "\n";
			echo "product_type terms=" . implode( ', ', $dbg_terms ) . "\n";
			echo "composite query count=" . count( $dbg_comp ) . "\n";
			echo "sample composite id=" . $sample_id . "\n";
			echo "sample permalink=" . $sample_pl . "\n";
			echo "sample size count=" . $sample_sz . "\n";
			echo "timber_catp_size_options exists=" . ( function_exists( 'timber_catp_size_options' ) ? 'yes' : 'no' ) . "\n";
			exit;
		}

		$xml = pt_seo_get_products_sitemap_xml();
		if ( ! headers_sent() ) {
			status_header( 200 ); // override the 404 WP set for this unmatched path.
			header( 'Content-Type: text/xml; charset=UTF-8' );
			header( 'X-Robots-Tag: noindex, follow', true );
		}
		echo $xml; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — each URL is esc_url'd during build.
		exit;
	},
	0
);

/** Cached XML for the product sitemap (12h; rebuilt on product save). */
function pt_seo_get_products_sitemap_xml() {
	$cached = get_transient( 'pt_products_sitemap_xml_v2' );
	if ( is_string( $cached ) && '' !== $cached ) {
		return $cached;
	}
	$xml = pt_seo_build_products_sitemap_xml();
	set_transient( 'pt_products_sitemap_xml_v2', $xml, 12 * HOUR_IN_SECONDS );
	update_option( 'pt_products_sitemap_lastmod', time(), false );
	return $xml;
}

/** Build the product sitemap: composite size (feed) URLs + real simple products. */
function pt_seo_build_products_sitemap_xml() {
	$empty = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
		. '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>' . "\n";
	if ( ! function_exists( 'wc_get_product' ) ) {
		return $empty; // WooCommerce not loaded — serve a valid empty sitemap, never fatal.
	}

	$urls = array();

	// --- Composites → one entry per clean size URL (base size included). ---
	// Query the product_type taxonomy directly (robust regardless of whether
	// wc_get_products maps the custom "composite" type in this WC version).
	$composite_ids = get_posts(
		array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'tax_query'      => array(  // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => 'product_type',
					'field'    => 'slug',
					'terms'    => array( 'composite' ),
				),
			),
		)
	);

	foreach ( $composite_ids as $cid ) {
		$product = wc_get_product( $cid );
		if ( ! $product ) {
			continue;
		}
		$lastmod = get_post_modified_time( 'c', true, $cid );
		$sizes   = pt_seo_composite_sizes( $product );
		if ( empty( $sizes ) ) {
			// No resolvable sizes → fall back to the plain permalink so it's not lost.
			$urls[] = array( 'loc' => get_permalink( $product ), 'lastmod' => $lastmod );
			continue;
		}
		foreach ( $sizes as $s ) {
			$loc = pt_seo_size_url( $product, $s['slug'] );
			if ( $loc ) {
				$urls[] = array( 'loc' => $loc, 'lastmod' => $lastmod );
			}
		}
	}

	// --- Genuine simple products (exclude the parts machinery). ---
	$simple_ids = get_posts(
		array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'tax_query'      => array(  // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				'relation' => 'AND',
				array(
					'taxonomy' => 'product_type',
					'field'    => 'slug',
					'terms'    => array( 'simple' ),
				),
				array(
					'taxonomy' => 'product_cat',
					'field'    => 'slug',
					// Exclude the component / add-on / junk buckets — parts, felt &
					// assembly extras (misc), stray bundle-cat items, and uncategorised.
					'terms'    => array( 'parts', 'misc', 'bundles', 'uncategorized' ),
					'operator' => 'NOT IN',
				),
			),
		)
	);
	foreach ( $simple_ids as $sid ) {
		$urls[] = array( 'loc' => get_permalink( $sid ), 'lastmod' => get_post_modified_time( 'c', true, $sid ) );
	}

	// --- Serialise. ---
	$out  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	$out .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
	$seen = array();
	foreach ( $urls as $u ) {
		if ( empty( $u['loc'] ) || isset( $seen[ $u['loc'] ] ) ) {
			continue;
		}
		$seen[ $u['loc'] ] = true;
		$out              .= "\t<url>\n";
		$out              .= "\t\t<loc>" . esc_url( $u['loc'] ) . "</loc>\n";
		if ( ! empty( $u['lastmod'] ) ) {
			$out .= "\t\t<lastmod>" . esc_html( $u['lastmod'] ) . "</lastmod>\n";
		}
		$out .= "\t</url>\n";
	}
	$out .= '</urlset>' . "\n";
	return $out;
}

/** Flush the cached sitemap when a product changes. */
add_action(
	'save_post_product',
	static function () {
		delete_transient( 'pt_products_sitemap_xml_v2' );
	}
);
