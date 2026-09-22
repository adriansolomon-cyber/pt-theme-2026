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
 *      page at many size URLs; we canonicalise to the CRAWLABLE per-size form
 *          /summerhouses/8-x-6/<slug>/
 *      making each size URL self-canonical (so it can be indexed on its own),
 *      and point the bare parent permalink /summerhouses/<slug>/ at the cheapest
 *      ("from") size, so the parent doesn't compete with its own children.
 *      NOTE: the older /summerhouses/8-x-6/f/<slug>/ form (still used by the ads
 *      feed) is dropped here because robots.txt blocks /f/ — see pt_seo_size_url()
 *      and the pt_seo_size_url_f_segment filter to restore it. Align the feed to
 *      the non-/f/ form + add 301s /f/ → non-/f/ as follow-ups.
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
 * Build the per-size URL for a product + size slug, reusing the product's OWN
 * permalink category segment (so it matches WooCommerce):
 *   /summerhouses/<slug>/ → /summerhouses/<size>/<slug>/
 *
 * The `/f/` segment was dropped from the CANONICAL + SITEMAP because robots.txt
 * blocks /f/ (it's the layered-nav filter plugin's namespace) — canonicalising
 * to a blocked URL de-indexes the catalogue. The crawlable /<cat>/<size>/<slug>/
 * form already resolves 200. Return true from `pt_seo_size_url_f_segment` to put
 * `/f/` back (e.g. if the whole model is reverted).
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
	$f_segment = apply_filters( 'pt_seo_size_url_f_segment', false, $product, $size_slug ) ? 'f/' : '';
	return home_url( $prefix . $size_slug . '/' . $f_segment . $prod_slug . '/' );
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

		// Force a fresh rebuild with /pt-products.xml?pt_flush=1 (e.g. after edits).
		if ( isset( $_GET['pt_flush'] ) ) {
			delete_transient( 'pt_products_sitemap_xml_v2' );
		}

		$xml = pt_seo_get_products_sitemap_xml();
		if ( ! headers_sent() ) {
			status_header( 200 ); // override the 404 WP set for this unmatched path.
			header( 'Content-Type: text/xml; charset=UTF-8' );
			header( 'X-Robots-Tag: noindex, follow', true );
			// Don't let Cloudflare / any proxy edge-cache the sitemap — a stale
			// (esp. empty) copy would be served to Google. Origin is already cheap
			// (12h transient). CDN-Cache-Control targets the edge specifically.
			header( 'Cache-Control: no-store, max-age=0, must-revalidate' );
			header( 'CDN-Cache-Control: no-store' );
			header( 'Cloudflare-CDN-Cache-Control: no-store' );
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
			'has_password'   => false, // skip password-protected products.
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
			'has_password'   => false, // skip password-protected products.
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

/* =========================================================================
 * ADMIN AUDIT: list every product that emits a /f/ canonical.
 * Read-only. Gated to manage_woocommerce. Reuses the exact helpers above so
 * the "emitted canonical" column matches what the page actually renders.
 *
 *   /?pt_f_audit=1     → HTML table
 *   /?pt_f_audit=csv   → CSV download
 * ========================================================================= */
add_action(
	'template_redirect',
	static function () {
		if ( empty( $_GET['pt_f_audit'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		if ( ! current_user_can( 'manage_woocommerce' ) || ! function_exists( 'wc_get_products' ) ) {
			return;
		}
		$mode = sanitize_key( wp_unslash( $_GET['pt_f_audit'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$products = wc_get_products(
			array(
				'type'   => 'composite',
				'status' => 'publish',
				'limit'  => -1,
				'return' => 'objects',
			)
		);

		$rows = array();
		foreach ( (array) $products as $product ) {
			$sizes = pt_seo_composite_sizes( $product );
			if ( empty( $sizes ) ) {
				continue; // no clean sizes → no /f/ canonical.
			}
			$rows[] = array(
				'id'        => (int) $product->get_id(),
				'name'      => (string) $product->get_name(),
				'url'       => (string) get_permalink( $product->get_id() ),
				'canonical' => (string) pt_seo_size_url( $product, $sizes[0]['slug'] ),
				'sizes'     => count( $sizes ),
			);
		}
		usort(
			$rows,
			static function ( $a, $b ) {
				return strcasecmp( $a['name'], $b['name'] );
			}
		);

		nocache_headers();

		if ( 'csv' === $mode ) {
			header( 'Content-Type: text/csv; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename="pt-f-canonical-products.csv"' );
			$out = fopen( 'php://output', 'w' );
			fputcsv( $out, array( 'ID', 'Name', 'Current URL', 'Emitted canonical', 'Sizes' ) );
			foreach ( $rows as $r ) {
				fputcsv( $out, array( $r['id'], $r['name'], $r['url'], $r['canonical'], $r['sizes'] ) );
			}
			fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			exit;
		}

		header( 'Content-Type: text/html; charset=utf-8' );
		echo '<!doctype html><meta charset="utf-8"><meta name="robots" content="noindex"><title>Per-size canonical audit</title>';
		echo '<style>body{font:14px/1.5 system-ui,sans-serif;margin:24px;color:#211e24}table{border-collapse:collapse;width:100%;margin-top:12px}th,td{border:1px solid #e2e2e2;padding:6px 10px;text-align:left;vertical-align:top}th{background:#f5f5f5}code{font-size:12px;word-break:break-all}.m{color:#666}a{color:#1f4e82}</style>';
		echo '<h1>Composite products with a per-size canonical</h1>';
		echo '<p class="m"><strong>' . count( $rows ) . '</strong> composite products with clean N&nbsp;x&nbsp;N sizes. <a href="' . esc_url( add_query_arg( 'pt_f_audit', 'csv' ) ) . '">Download CSV</a></p>';
		echo '<table><tr><th>#</th><th>ID</th><th>Product</th><th>Current URL</th><th>Emitted canonical</th><th>Sizes</th></tr>';
		$i = 0;
		foreach ( $rows as $r ) {
			$i++;
			echo '<tr><td>' . (int) $i . '</td><td>' . (int) $r['id'] . '</td>'
				. '<td>' . esc_html( $r['name'] ) . '</td>'
				. '<td><a href="' . esc_url( $r['url'] ) . '" target="_blank" rel="noopener">' . esc_html( $r['url'] ) . '</a></td>'
				. '<td><code>' . esc_html( $r['canonical'] ) . '</code></td>'
				. '<td>' . (int) $r['sizes'] . '</td></tr>';
		}
		echo '</table>';
		exit;
	}
);
