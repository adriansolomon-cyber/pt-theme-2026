<?php
/**
 * Template Name: PT — Test scripts
 *
 * Admin-only debug scratchpad, migrated from the old theTimber theme
 * (test-script.php). Assign this template to a private/draft page and open it
 * while logged in as an administrator to run ad-hoc checks. NOTHING renders for
 * non-admins.
 *
 * Live check below: how many ACTIVE WooCommerce coupons (vouchers) exist —
 * published and not past their expiry date. Other historical debug helpers are
 * kept commented at the foot of the file as scaffolding; uncomment as needed.
 *
 * SECURITY: this file ships in a PUBLIC repo. NEVER paste a real API key here.
 * Read secrets from wp-config constants (e.g. PT_OPTIMO_API_KEY) instead.
 *
 * @package pt-theme-2026
 */

if ( ! current_user_can( 'manage_woocommerce' ) ) {
	// No output for anyone who can't manage the store.
	get_header();
	echo '<main class="pt-test" style="max-width:960px;margin:80px auto;padding:0 20px;">';
	echo '<p>Nothing to see here.</p>';
	echo '</main>';
	get_footer();
	return;
}

/**
 * Return every ACTIVE coupon (published + not expired).
 *
 * @return array<int,array{id:int,code:string,amount:string,type:string,expires:string,used:int,limit:string}>
 */
function pt_get_all_active_coupons() {
	$posts = get_posts(
		array(
			'post_type'      => 'shop_coupon',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);

	$active = array();
	$now    = time();

	foreach ( $posts as $post ) {
		$coupon  = new WC_Coupon( $post->ID );
		$expires = $coupon->get_date_expires();

		// Skip expired coupons.
		if ( $expires && $expires->getTimestamp() < $now ) {
			continue;
		}

		$limit = $coupon->get_usage_limit();

		$active[] = array(
			'id'      => $coupon->get_id(),
			'code'    => $coupon->get_code(),
			'amount'  => $coupon->get_amount(),
			'type'    => $coupon->get_discount_type(),
			'expires' => $expires ? $expires->date_i18n( 'Y-m-d' ) : '—',
			'used'    => (int) $coupon->get_usage_count(),
			'limit'   => $limit ? (string) $limit : '∞',
		);
	}

	return $active;
}

/**
 * Sales lookup for a set of product IDs over the last N months.
 *
 * Counts units sold (SUM of line-item _qty), the number of distinct orders, and
 * the order IDs each product appears in. Matches line items by `_product_id`
 * (this is where composite/bundle SIZE sub-products are stored) across real
 * orders only (completed, processing, on-hold, refunded). Auto-detects HPOS vs
 * legacy post-based order storage.
 *
 * @param int[] $product_ids Product IDs to look up.
 * @param int   $months      Look-back window in months (default 12).
 * @return array<int,array{product_id:string,product_name:string,orders:string,units_sold:string,order_ids:string}>
 */
function pt_test_product_sales( array $product_ids, $months = 12 ) {
	global $wpdb;

	$product_ids = array_values( array_unique( array_filter( array_map( 'intval', $product_ids ) ) ) );
	if ( empty( $product_ids ) ) {
		return array();
	}
	$months = max( 1, (int) $months );
	$in     = implode( ',', $product_ids ); // Integer-sanitised, safe to inline.

	$statuses  = array( 'wc-completed', 'wc-processing', 'wc-on-hold', 'wc-refunded' );
	$status_in = "'" . implode( "','", array_map( 'esc_sql', $statuses ) ) . "'";

	$hpos = class_exists( '\\Automattic\\WooCommerce\\Utilities\\OrderUtil' )
		&& \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();

	if ( $hpos ) {
		$orders_join  = "JOIN {$wpdb->prefix}wc_orders o ON o.id = oi.order_id";
		$orders_where = "o.type = 'shop_order' AND o.status IN ($status_in) AND o.date_created_gmt >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL %d MONTH)";
	} else {
		$orders_join  = "JOIN {$wpdb->posts} o ON o.ID = oi.order_id";
		$orders_where = "o.post_type = 'shop_order' AND o.post_status IN ($status_in) AND o.post_date >= DATE_SUB(NOW(), INTERVAL %d MONTH)";
	}

	// Order-ID lists can be long; lift the GROUP_CONCAT cap so none are truncated.
	$wpdb->query( 'SET SESSION group_concat_max_len = 1000000' );

	$sql = "
		SELECT
			pm.meta_value                                          AS product_id,
			p2.post_title                                          AS product_name,
			COUNT(DISTINCT oi.order_id)                            AS orders,
			SUM(CAST(qm.meta_value AS UNSIGNED))                   AS units_sold,
			GROUP_CONCAT(DISTINCT oi.order_id ORDER BY oi.order_id) AS order_ids
		FROM {$wpdb->prefix}woocommerce_order_items oi
		JOIN {$wpdb->prefix}woocommerce_order_itemmeta pm
			ON pm.order_item_id = oi.order_item_id AND pm.meta_key = '_product_id'
		LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta qm
			ON qm.order_item_id = oi.order_item_id AND qm.meta_key = '_qty'
		$orders_join
		LEFT JOIN {$wpdb->posts} p2 ON p2.ID = pm.meta_value
		WHERE pm.meta_value IN ($in)
			AND oi.order_item_type = 'line_item'
			AND $orders_where
		GROUP BY pm.meta_value, p2.post_title
		ORDER BY units_sold DESC
	";

	// Only $months is a bound parameter; the IN list is already integer-safe.
	return $wpdb->get_results( $wpdb->prepare( $sql, $months ), ARRAY_A );
}


/*
 * CSV export (admin only), streamed in 50-ID batches so PHP memory stays flat.
 * Two shapes, both covering EVERY audited product:
 *   ?export=summary → mirrors the on-screen per-product table (one row/product).
 *   ?export=detail  → the granular per-sale trail (one row per sale).
 * Runs before any theme output and exits. Reached only by admins (the gate at
 * the top of this template returns for everyone else).
 */
$pt_export = isset( $_GET['export'] ) ? sanitize_key( $_GET['export'] ) : '';
if ( current_user_can( 'manage_woocommerce' )
	&& in_array( $pt_export, array( 'summary', 'detail' ), true )
	&& function_exists( 'wc_get_product' )
	&& ! headers_sent()
) {
	// Scope: the searched IDs when ?ids= is present, else the full audit list.
	$ids = pt_price_audit_ids();
	if ( isset( $_GET['ids'] ) && '' !== trim( (string) $_GET['ids'] ) ) {
		$sids = array();
		foreach ( preg_split( '/[\s,]+/', (string) $_GET['ids'] ) as $tok ) {
			$tok = (int) trim( $tok );
			if ( $tok > 0 ) {
				$sids[] = $tok;
			}
		}
		if ( $sids ) {
			$ids = array_values( array_unique( $sids ) );
		}
	}
	$parent_map = pt_size_parent_map();

	// A full-list export can run a while; don't let PHP time out mid-stream.
	@set_time_limit( 0 );
	if ( function_exists( 'wp_raise_memory_limit' ) ) {
		wp_raise_memory_limit( 'admin' );
	}

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="pt-price-audit-' . $pt_export . '-' . gmdate( 'Y-m-d' ) . '.csv"' );
	$out = fopen( 'php://output', 'w' );

	if ( 'summary' === $pt_export ) {
		// Column-for-column mirror of the on-screen table: each list figure with
		// its "sold" sub-line as an adjacent column, plus COGS and margin (£/%).
		fputcsv( $out, array( 'product_id', 'product', 'parent_id', 'parent_title', 'parent_size_url', 'units', 'orders', 'first_price_exvat', 'first_date', 'last_price_exvat', 'last_date', 'lowest_list_exvat', 'lowest_sold_exvat', 'highest_list_exvat', 'highest_sold_exvat', 'change_list_exvat', 'change_sold_exvat', 'change_list_pct', 'change_sold_pct', 'listing_price_exvat', 'cogs_exvat', 'margin_exvat', 'margin_pct' ) );

		foreach ( array_chunk( $ids, 50 ) as $chunk ) {
			$agg = pt_aggregate_price_rows( pt_test_product_price_rows( $chunk ) );
			foreach ( $chunk as $pid ) {
				$pid     = (int) $pid;
				$par     = isset( $parent_map[ $pid ] ) ? $parent_map[ $pid ] : array( 'id' => '', 'title' => '', 'url' => '' );
				$listing = pt_audit_product_price( $pid );
				$cogs    = pt_audit_product_cogs( $pid );
				if ( ! isset( $agg[ $pid ] ) ) {
					// No sales in period — still list the product (with parent + listing + COGS), like the web view.
					fputcsv( $out, array( $pid, '', $par['id'], $par['title'], pt_audit_size_url( $par['url'], get_the_title( $pid ) ), 0, 0, '', '', '', '', '', '', '', '', '', '', '', '', number_format( $listing, 2, '.', '' ), number_format( $cogs, 2, '.', '' ), '', '' ) );
					continue;
				}
				$a          = $agg[ $pid ];
				$chg        = (float) $a['last_list'] - (float) $a['first_list'];    // directional last − first (list)
				$chgpct     = ( $a['first_list'] > 0 ) ? ( $chg / (float) $a['first_list'] * 100 ) : 0.0;
				$chg_sold   = (float) $a['last_sold'] - (float) $a['first_sold'];    // directional last − first (sold)
				$chgpct_sld = ( $a['first_sold'] > 0 ) ? ( $chg_sold / (float) $a['first_sold'] * 100 ) : 0.0;
				$margin     = (float) $a['last_sold'] - $cogs;                        // margin at last sold price
				$margin_pct = ( (float) $a['last_sold'] > 0 ) ? ( $margin / (float) $a['last_sold'] * 100 ) : 0.0;
				fputcsv(
					$out,
					array(
						$pid,
						$a['name'],
						$par['id'],
						$par['title'],
						pt_audit_size_url( $par['url'], $a['name'] ),
						(int) $a['units'],
						count( $a['orders'] ),
						number_format( (float) $a['first_list'], 2, '.', '' ),
						substr( (string) $a['first_date'], 0, 10 ),
						number_format( (float) $a['last_list'], 2, '.', '' ),
						substr( (string) $a['last_date'], 0, 10 ),
						number_format( (float) $a['min'], 2, '.', '' ),
						number_format( (float) $a['min_sold'], 2, '.', '' ),
						number_format( (float) $a['max'], 2, '.', '' ),
						number_format( (float) $a['max_sold'], 2, '.', '' ),
						number_format( $chg, 2, '.', '' ),
						number_format( $chg_sold, 2, '.', '' ),
						number_format( $chgpct, 1, '.', '' ),
						number_format( $chgpct_sld, 1, '.', '' ),
						number_format( $listing, 2, '.', '' ),
						number_format( $cogs, 2, '.', '' ),
						$cogs > 0 ? number_format( $margin, 2, '.', '' ) : '',
						$cogs > 0 ? number_format( $margin_pct, 1, '.', '' ) : '',
					)
				);
			}
			unset( $agg );
			if ( ob_get_level() > 0 ) {
				@ob_flush();
			}
			@flush();
		}
	} else {
		// Detail — one row per sale (matches the drill-down trail), with parent.
		fputcsv( $out, array( 'product_id', 'product', 'parent_id', 'parent_title', 'parent_size_url', 'order_id', 'order_date', 'qty', 'list_cost_exvat', 'sold_total_exvat', 'discount_exvat' ) );

		foreach ( array_chunk( $ids, 50 ) as $chunk ) {
			$rows = pt_test_product_price_rows( $chunk );
			foreach ( $rows as $r ) {
				$pid  = (int) $r['product_id'];
				$par  = isset( $parent_map[ $pid ] ) ? $parent_map[ $pid ] : array( 'id' => '', 'title' => '', 'url' => '' );
				$disc = (float) $r['unit_list'] - (float) $r['unit_paid'];
				fputcsv(
					$out,
					array(
						$r['product_id'],
						$r['product_name'],
						$par['id'],
						$par['title'],
						pt_audit_size_url( $par['url'], $r['product_name'] ),
						$r['order_id'],
						substr( (string) $r['order_date'], 0, 10 ),
						(int) $r['qty'],
						number_format( (float) $r['unit_list'], 2, '.', '' ),
						number_format( (float) $r['unit_paid'], 2, '.', '' ),
						number_format( $disc, 2, '.', '' ),
					)
				);
			}
			unset( $rows );
			if ( ob_get_level() > 0 ) {
				@ob_flush();
			}
			@flush();
		}
	}

	fclose( $out );
	exit;
}

get_header();
?>
<main class="pt-test" style="max-width:1000px;margin:80px auto;padding:0 20px;font-family:system-ui,Arial,sans-serif;">
	<?php
	/*
	 * Dormant for now. Uncomment this block to render the active-voucher count.
	 *
	if ( ! class_exists( 'WC_Coupon' ) ) {
		echo '<p><strong>WooCommerce is not active.</strong></p>';
	} else {
		$coupons = pt_get_all_active_coupons();
		printf(
			'<h1 style="margin:0 0 8px;font-size:28px;">Active vouchers: %d</h1>',
			count( $coupons )
		);
		echo '<p style="color:#666;margin:0 0 24px;">Published coupons that are not past their expiry date.</p>';

		if ( $coupons ) {
			echo '<table style="width:100%;border-collapse:collapse;font-size:14px;">';
			echo '<thead><tr style="text-align:left;border-bottom:2px solid #111;">';
			foreach ( array( 'Code', 'Type', 'Amount', 'Expires', 'Used', 'Limit', 'ID' ) as $h ) {
				echo '<th style="padding:8px 10px;">' . esc_html( $h ) . '</th>';
			}
			echo '</tr></thead><tbody>';
			foreach ( $coupons as $c ) {
				echo '<tr style="border-bottom:1px solid #e5e5e5;">';
				echo '<td style="padding:8px 10px;font-weight:600;">' . esc_html( $c['code'] ) . '</td>';
				echo '<td style="padding:8px 10px;">' . esc_html( $c['type'] ) . '</td>';
				echo '<td style="padding:8px 10px;">' . esc_html( $c['amount'] ) . '</td>';
				echo '<td style="padding:8px 10px;">' . esc_html( $c['expires'] ) . '</td>';
				echo '<td style="padding:8px 10px;">' . esc_html( (string) $c['used'] ) . '</td>';
				echo '<td style="padding:8px 10px;">' . esc_html( $c['limit'] ) . '</td>';
				echo '<td style="padding:8px 10px;color:#999;">' . esc_html( (string) $c['id'] ) . '</td>';
				echo '</tr>';
			}
			echo '</tbody></table>';
		} else {
			echo '<p>No active coupons found.</p>';
		}
	}
	*/

	// --- Special-offer / "grandmaster" diagnostic: ?special_check=<product_id> ---
	// Explains WHY a product is (or isn't) flagged for the special-offer badge —
	// its categories, their ancestors, the ACF special-offer set, and the match.
	if ( isset( $_GET['special_check'] ) && function_exists( 'pt_product_in_special_category' ) ) {
		$scid = (int) $_GET['special_check'];
		$prod = $scid ? wc_get_product( $scid ) : null;
		echo '<h1 style="margin:0 0 10px;font-size:24px;">Special-offer check — product ' . (int) $scid . '</h1>';
		if ( ! $prod ) {
			echo '<p style="color:#b00;">No product with that ID.</p>';
		} else {
			$special = function_exists( 'pt_campaign_special_cat_ids' ) ? pt_campaign_special_cat_ids() : array();
			$raw     = function_exists( 'get_field' ) ? (string) get_field( 'special_offer_category_includes', 'option' ) : '';
			$cats    = wc_get_product_term_ids( $scid, 'product_cat' );
			$cats    = is_array( $cats ) ? $cats : array();

			echo '<p><strong>' . esc_html( $prod->get_name() ) . '</strong> (type: ' . esc_html( $prod->get_type() ) . ')</p>';
			echo '<p style="margin:0 0 4px;"><strong>Flagged special-offer:</strong> ' . ( pt_product_in_special_category( $scid ) ? '<span style="color:#b00;font-weight:700;">YES</span>' : '<span style="color:#080;font-weight:700;">no</span>' ) . '</p>';
			echo '<p style="color:#666;margin:0 0 16px;">ACF <code>special_offer_category_includes</code> = <code>' . esc_html( '' !== $raw ? $raw : '(empty)' ) . '</code> → term IDs: <code>' . esc_html( $special ? implode( ', ', $special ) : '(none)' ) . '</code></p>';

			echo '<table style="border-collapse:collapse;font-size:13px;"><thead><tr style="text-align:left;border-bottom:2px solid #111;">';
			foreach ( array( 'Category (this product)', 'Term ID', 'Ancestors (id — name)', 'Matches special?' ) as $h ) {
				echo '<th style="padding:6px 10px;">' . esc_html( $h ) . '</th>';
			}
			echo '</tr></thead><tbody>';
			if ( ! $cats ) {
				echo '<tr><td colspan="4" style="padding:6px 10px;color:#888;">Product has no product_cat terms.</td></tr>';
			}
			foreach ( $cats as $cid ) {
				$term      = get_term( (int) $cid, 'product_cat' );
				$ancestors = get_ancestors( (int) $cid, 'product_cat' );
				$anc_txt   = array();
				foreach ( $ancestors as $aid ) {
					$at        = get_term( (int) $aid, 'product_cat' );
					$anc_txt[] = ( $at && ! is_wp_error( $at ) ) ? ( $aid . ' — ' . $at->name ) : (string) $aid;
				}
				$chain    = array_merge( array( (int) $cid ), array_map( 'intval', $ancestors ) );
				$hit      = array_intersect( $special, $chain );
				echo '<tr style="border-bottom:1px solid #eee;">';
				echo '<td style="padding:6px 10px;">' . esc_html( ( $term && ! is_wp_error( $term ) ) ? $term->name . ' (' . $term->slug . ')' : '#' . $cid ) . '</td>';
				echo '<td style="padding:6px 10px;color:#999;">' . (int) $cid . '</td>';
				echo '<td style="padding:6px 10px;color:#555;">' . esc_html( $anc_txt ? implode( ', ', $anc_txt ) : '—' ) . '</td>';
				echo '<td style="padding:6px 10px;">' . ( $hit ? '<span style="color:#b00;font-weight:700;">via ' . esc_html( implode( ',', $hit ) ) . '</span>' : '—' ) . '</td>';
				echo '</tr>';
			}
			echo '</tbody></table>';
			echo '<p style="color:#888;font-size:12px;margin:14px 0 0;">A red match means this category (or one of its ancestors) is in the special-offer set — that\'s why the badge shows. Fix by removing the wrong category from the product, or by correcting the ACF special-offer list / the category parentage.</p>';
		}
		get_footer();
		return;
	}

	// --- Price audit — paginated per-product summary + CSV export ---------
	$pt_start_lbl = pt_price_audit_start_label();
	$pt_per_page  = 50;
	$pt_all_ids   = pt_price_audit_ids();

	if ( ! function_exists( 'wc_get_product' ) ) {
		echo '<p><strong>WooCommerce is not active.</strong></p>';
	} elseif ( empty( $pt_all_ids ) ) {
		echo '<p><strong>No product IDs loaded.</strong> Add them to <code>includes/pt-price-audit-ids.php</code>.</p>';
	} elseif ( isset( $_GET['product'] ) && (int) $_GET['product'] > 0 ) {
		// Single-product drill-down: full per-sale trail (any product id).
		pt_render_price_detail( array( (int) $_GET['product'] ) );
	} else {
		// Optional search: filter the audit to specific IDs (comma-separated).
		$pt_search_raw = isset( $_GET['ids'] ) ? (string) $_GET['ids'] : '';
		$pt_search_ids = array();
		foreach ( preg_split( '/[\s,]+/', $pt_search_raw ) as $tok ) {
			$tok = (int) trim( $tok );
			if ( $tok > 0 ) {
				$pt_search_ids[] = $tok;
			}
		}
		$pt_search_ids = array_values( array_unique( $pt_search_ids ) );
		$pt_is_search  = ! empty( $pt_search_ids );
		$pt_total      = count( $pt_all_ids );

		printf( '<h1 style="margin:0 0 10px;font-size:26px;">Price audit — %s products, since %s</h1>', esc_html( number_format( $pt_total ) ), esc_html( $pt_start_lbl ) );

		// Search box (GET). Submitting keeps you on this page with ?ids=...
		echo '<form method="get" style="margin:0 0 16px;display:flex;gap:8px;align-items:center;flex-wrap:wrap;">';
		echo '<input type="text" name="ids" value="' . esc_attr( $pt_search_raw ) . '" placeholder="Find product IDs — comma-separated (e.g. 7469, 7755)" style="flex:1;min-width:280px;padding:9px 12px;border:1px solid #bbb;border-radius:6px;font-size:14px;">';
		echo '<button type="submit" style="background:#111;color:#fff;border:0;padding:10px 16px;border-radius:6px;font-size:14px;cursor:pointer;">Search</button>';
		if ( $pt_is_search ) {
			echo '<a href="' . esc_url( remove_query_arg( array( 'ids', 'batch', 'export' ) ) ) . '" style="color:#06c;text-decoration:none;font-size:14px;">Clear</a>';
		}
		echo '</form>';

		if ( $pt_is_search ) {
			$pt_slice = $pt_search_ids;
			$pt_pages = 1;
			$pt_batch = 1;
			echo '<p style="color:#666;margin:0 0 14px;">Search results for <strong>' . count( $pt_slice ) . '</strong> ID' . ( 1 === count( $pt_slice ) ? '' : 's' ) . '. Prices are the order line&rsquo;s <strong>Cost (ex VAT)</strong> per unit. Click a row&rsquo;s &#9654; to expand its sales.</p>';
		} else {
			$pt_pages = (int) max( 1, ceil( $pt_total / $pt_per_page ) );
			$pt_batch = isset( $_GET['batch'] ) ? max( 1, min( $pt_pages, (int) $_GET['batch'] ) ) : 1;
			$pt_slice = array_slice( $pt_all_ids, ( $pt_batch - 1 ) * $pt_per_page, $pt_per_page );
			echo '<p style="color:#666;margin:0 0 14px;">Per-product price movement. Real orders only (completed/processing/on-hold/refunded); prices are the order line&rsquo;s <strong>Cost (listing, ex VAT)</strong> per unit, matching the order screen. Showing <strong>batch ' . (int) $pt_batch . ' of ' . (int) $pt_pages . '</strong> (' . count( $pt_slice ) . ' products). Click a row&rsquo;s &#9654; to expand its every sale inline (order, date, List/Sold = Cost/Total, discount, coupon) — loaded on demand, so nothing extra runs until you click.</p>';
		}

		// CSV export links — scoped to the search set when searching, else all IDs.
		$pt_exp_summary = $pt_is_search ? add_query_arg( array( 'export' => 'summary', 'ids' => $pt_search_raw ) ) : add_query_arg( array( 'export' => 'summary' ) );
		$pt_exp_detail  = $pt_is_search ? add_query_arg( array( 'export' => 'detail', 'ids' => $pt_search_raw ) ) : add_query_arg( array( 'export' => 'detail' ) );
		$pt_exp_label   = $pt_is_search ? 'these ' . count( $pt_slice ) . ' result' . ( 1 === count( $pt_slice ) ? '' : 's' ) : 'all ' . (int) $pt_total;
		echo '<p style="margin:0 0 22px;">'
			. '<a href="' . esc_url( $pt_exp_summary ) . '" style="display:inline-block;background:#111;color:#fff;padding:9px 14px;border-radius:6px;text-decoration:none;font-size:14px;">&#8595; Totals CSV — one row per product (' . esc_html( $pt_exp_label ) . ')</a> '
			. '<a href="' . esc_url( $pt_exp_detail ) . '" style="display:inline-block;background:#fff;color:#111;border:1px solid #111;padding:8px 14px;border-radius:6px;text-decoration:none;font-size:14px;margin-left:8px;">&#8595; Every-sale CSV</a> '
			. '<span style="color:#999;font-size:12px;">may take a minute</span></p>';

		// One bounded query for this batch's IDs, aggregated per product in PHP.
		$agg           = pt_aggregate_price_rows( pt_test_product_price_rows( $pt_slice ) );
		$pt_parent_map = pt_size_parent_map();

		// Small helper: the linked parent-product cell for a size id. The link
		// carries the size (…/category/24-x-8/slug/) so it opens on that size.
		$pt_parent_cell = static function ( $pid, $size_name ) use ( $pt_parent_map ) {
			$par = isset( $pt_parent_map[ (int) $pid ] ) ? $pt_parent_map[ (int) $pid ] : null;
			if ( ! $par ) {
				return '<td style="padding:6px 10px;color:#bbb;">—</td>';
			}
			$url = pt_audit_size_url( $par['url'], $size_name );
			return '<td style="padding:6px 10px;"><a href="' . esc_url( $url ) . '" target="_blank" rel="noopener" style="color:#06c;text-decoration:none;">' . esc_html( $par['title'] ) . '</a></td>';
		};

		echo '<table style="width:100%;border-collapse:collapse;font-size:13px;">';
		echo '<thead><tr style="text-align:left;border-bottom:2px solid #111;">';
		foreach ( array( 'Product ID', 'Product', 'Parent product', 'Units', 'Orders', 'First £ (date)', 'Last £ (date)', 'Lowest £', 'Highest £', 'Change £', 'Change %', 'Listing £', 'COGS £' ) as $h ) {
			echo '<th style="padding:7px 10px;vertical-align:top;">' . esc_html( $h ) . '</th>';
		}
		echo '</tr></thead><tbody>';

		$tot_units  = 0;
		$tot_orders = 0;
		$tot_chg    = 0.0;
		$pct_sum    = 0.0;
		$pct_n      = 0;
		$pt_red_pct = 15; // % increase (last vs first) at/above which Change is flagged red.

		foreach ( $pt_slice as $pid ) {
			$detail_url = esc_url( add_query_arg( array( 'product' => (int) $pid ) ) );
			if ( ! isset( $agg[ $pid ] ) ) {
				echo '<tr style="border-bottom:1px solid #eee;color:#aaa;">';
				echo '<td style="padding:6px 10px;"><a href="' . $detail_url . '" style="color:#999;">' . esc_html( (string) $pid ) . '</a></td>';
				echo '<td style="padding:6px 10px;color:#bbb;">—</td>';
				echo $pt_parent_cell( $pid, get_the_title( (int) $pid ) );
				$listing_ns = pt_audit_product_price( $pid );
				$cogs_ns    = pt_audit_product_cogs( $pid );
				echo '<td style="padding:6px 10px;" colspan="8">no sales in period</td>';
				echo '<td style="padding:6px 10px;color:#555;">' . ( $listing_ns > 0 ? '£' . esc_html( number_format( $listing_ns, 2 ) ) : '<span style="color:#bbb;">—</span>' ) . '</td>';
				echo '<td style="padding:6px 10px;color:#555;">' . ( $cogs_ns > 0 ? '£' . esc_html( number_format( $cogs_ns, 2 ) ) : '<span style="color:#bbb;">—</span>' ) . '</td></tr>';
				continue;
			}
			$a    = $agg[ $pid ];
			// Directional change: last sale price vs first sale price over the window.
			$chg    = (float) $a['last_list'] - (float) $a['first_list'];
			$base   = (float) $a['first_list'];
			$chgpct = ( $base > 0 ) ? ( $chg / $base * 100 ) : 0.0;

			// Colour: lower now → yellow; much higher now (≥ threshold) → red.
			$chg_style = 'padding:6px 10px;font-weight:700;';
			if ( $chg < -0.005 ) {
				$chg_style .= 'background:#fff4c2;color:#7a5c00;';
			} elseif ( $chgpct >= $pt_red_pct ) {
				$chg_style .= 'background:#b00020;color:#fff;';
			} elseif ( $chg > 0.005 ) {
				$chg_style .= 'color:#333;';
			} else {
				$chg_style .= 'color:#999;';
			}
			if ( abs( $chg ) < 0.005 ) {
				$chg_amt_txt = '£0.00';
				$chg_pct_txt = '0%';
			} else {
				$chg_amt_txt = ( $chg >= 0 ? '+£' : '−£' ) . number_format( abs( $chg ), 2 );
				$chg_pct_txt = ( $chg >= 0 ? '+' : '−' ) . number_format( abs( $chgpct ), 1 ) . '%';
			}

			// Sold (Total) directional change — the sub-line under the List figures.
			$chg_sold      = (float) $a['last_sold'] - (float) $a['first_sold'];
			$base_sold     = (float) $a['first_sold'];
			$chg_sold_pct  = ( $base_sold > 0 ) ? ( $chg_sold / $base_sold * 100 ) : 0.0;
			if ( abs( $chg_sold ) < 0.005 ) {
				$chg_sold_amt_txt = '£0.00';
				$chg_sold_pct_txt = '0%';
			} else {
				$chg_sold_amt_txt = ( $chg_sold >= 0 ? '+£' : '−£' ) . number_format( abs( $chg_sold ), 2 );
				$chg_sold_pct_txt = ( $chg_sold >= 0 ? '+' : '−' ) . number_format( abs( $chg_sold_pct ), 1 ) . '%';
			}

			$tot_units  += (int) $a['units'];
			$tot_orders += count( $a['orders'] );
			$tot_chg    += $chg;
			if ( $base > 0 ) {
				$pct_sum += $chgpct;
				$pct_n++;
			}

			echo '<tr style="border-bottom:1px solid #eee;">';
			echo '<td style="padding:6px 10px;white-space:nowrap;"><a href="#" class="pt-expand" data-pid="' . (int) $pid . '" style="color:#06c;font-weight:600;text-decoration:none;">&#9654; ' . esc_html( (string) $pid ) . '</a> <a href="' . $detail_url . '" title="Open full page" style="color:#bbb;text-decoration:none;font-size:11px;">&#8599;</a></td>';
			echo '<td style="padding:6px 10px;">' . esc_html( $a['name'] ? $a['name'] : '(deleted product)' ) . '</td>';
			echo $pt_parent_cell( $pid, $a['name'] );
			echo '<td style="padding:6px 10px;font-weight:700;">' . esc_html( (string) $a['units'] ) . '</td>';
			echo '<td style="padding:6px 10px;">' . esc_html( (string) count( $a['orders'] ) ) . '</td>';
			echo '<td style="padding:6px 10px;white-space:nowrap;">£' . esc_html( number_format( (float) $a['first_list'], 2 ) ) . ' <span style="color:#999;">' . esc_html( substr( (string) $a['first_date'], 0, 10 ) ) . '</span></td>';
			echo '<td style="padding:6px 10px;white-space:nowrap;">£' . esc_html( number_format( (float) $a['last_list'], 2 ) ) . ' <span style="color:#999;">' . esc_html( substr( (string) $a['last_date'], 0, 10 ) ) . '</span></td>';
			echo '<td style="padding:6px 10px;">£' . esc_html( number_format( (float) $a['min'], 2 ) ) . '<div style="color:#888;font-size:11px;">sold £' . esc_html( number_format( (float) $a['min_sold'], 2 ) ) . '</div></td>';
			echo '<td style="padding:6px 10px;">£' . esc_html( number_format( (float) $a['max'], 2 ) ) . '<div style="color:#888;font-size:11px;">sold £' . esc_html( number_format( (float) $a['max_sold'], 2 ) ) . '</div></td>';
			echo '<td style="' . $chg_style . '">' . esc_html( $chg_amt_txt ) . '<div style="font-weight:400;font-size:11px;opacity:.8;">sold ' . esc_html( $chg_sold_amt_txt ) . '</div></td>';
			echo '<td style="' . $chg_style . '">' . esc_html( $chg_pct_txt ) . '<div style="font-weight:400;font-size:11px;opacity:.8;">sold ' . esc_html( $chg_sold_pct_txt ) . '</div></td>';
			$listing_row = pt_audit_product_price( $pid );
			echo '<td style="padding:6px 10px;color:#555;">' . ( $listing_row > 0 ? '£' . esc_html( number_format( $listing_row, 2 ) ) : '<span style="color:#bbb;">—</span>' ) . '</td>';
			$cogs_row = pt_audit_product_cogs( $pid );
			if ( $cogs_row > 0 ) {
				$m_row  = (float) $a['last_sold'] - $cogs_row;
				$mp_row = ( (float) $a['last_sold'] > 0 ) ? ( $m_row / (float) $a['last_sold'] * 100 ) : 0.0;
				echo '<td style="padding:6px 10px;color:#555;">£' . esc_html( number_format( $cogs_row, 2 ) ) . '<div style="color:#888;font-size:11px;">margin £' . esc_html( number_format( $m_row, 2 ) ) . ' (' . esc_html( number_format( $mp_row, 1 ) ) . '%)</div></td>';
			} else {
				echo '<td style="padding:6px 10px;color:#bbb;">—</td>';
			}
			echo '</tr>';
			echo '<tr class="pt-detail-row" data-for="' . (int) $pid . '" hidden><td colspan="13" style="padding:0 10px 12px 34px;background:#fafafa;"><div class="pt-audit-detail"></div></td></tr>';
		}

		// TOTAL row (this batch): summed units/orders, net £ change, average % change.
		$avg_pct = $pct_n ? ( $pct_sum / $pct_n ) : 0.0;
		echo '<tr style="border-top:2px solid #111;font-weight:700;background:#f7f7f7;">';
		echo '<td style="padding:9px 10px;" colspan="3">' . ( $pt_is_search ? 'TOTAL (results)' : 'TOTAL (this batch)' ) . '</td>';
		echo '<td style="padding:9px 10px;">' . esc_html( (string) $tot_units ) . '</td>';
		echo '<td style="padding:9px 10px;">' . esc_html( (string) $tot_orders ) . '</td>';
		echo '<td style="padding:9px 10px;" colspan="4"></td>';
		echo '<td style="padding:9px 10px;">' . esc_html( ( $tot_chg >= 0 ? '+£' : '−£' ) . number_format( abs( $tot_chg ), 2 ) ) . '</td>';
		echo '<td style="padding:9px 10px;">' . esc_html( ( $avg_pct >= 0 ? '+' : '−' ) . number_format( abs( $avg_pct ), 1 ) . '% avg' ) . '</td>';
		echo '<td style="padding:9px 10px;"></td>'; // Listing £
		echo '<td style="padding:9px 10px;"></td>'; // COGS £
		echo '</tr>';

		echo '</tbody></table>';
		echo '<p style="color:#888;font-size:12px;margin:8px 0 0;">Change = last sale price vs first, over the window. <span style="background:#fff4c2;color:#7a5c00;padding:1px 6px;border-radius:3px;">yellow</span> = cheaper now than it was; <span style="background:#b00020;color:#fff;padding:1px 6px;border-radius:3px;">red</span> = ' . (int) $pt_red_pct . '%+ higher now.</p>';

		// Pagination (only in the full listing, not in search results).
		if ( ! $pt_is_search ) {
			echo '<div style="display:flex;gap:14px;align-items:center;margin:20px 0 0;font-size:14px;">';
			if ( $pt_batch > 1 ) {
				echo '<a href="' . esc_url( add_query_arg( array( 'batch' => $pt_batch - 1 ) ) ) . '" style="text-decoration:none;">&larr; Prev</a>';
			}
			echo '<span style="color:#666;">Batch ' . (int) $pt_batch . ' of ' . (int) $pt_pages . '</span>';
			if ( $pt_batch < $pt_pages ) {
				echo '<a href="' . esc_url( add_query_arg( array( 'batch' => $pt_batch + 1 ) ) ) . '" style="text-decoration:none;">Next &rarr;</a>';
			}
			echo '</div>';
		}

		// Click-to-expand: fetch one product's full per-sale trail via AJAX.
		$pt_ajax  = admin_url( 'admin-ajax.php' );
		$pt_nonce = wp_create_nonce( 'pt_price_audit' );
		?>
		<script>
		(function () {
			var CFG = { ajax: <?php echo wp_json_encode( $pt_ajax ); ?>, nonce: <?php echo wp_json_encode( $pt_nonce ); ?> };
			document.addEventListener('click', function (e) {
				var t = e.target.closest ? e.target.closest('.pt-expand') : null;
				if (!t) { return; }
				e.preventDefault();
				var pid = t.getAttribute('data-pid');
				var row = document.querySelector('.pt-detail-row[data-for="' + pid + '"]');
				if (!row) { return; }
				var box = row.querySelector('.pt-audit-detail');
				if (row.hidden) {
					row.hidden = false;
					t.innerHTML = '&#9660; ' + pid;
					if (!row.getAttribute('data-loaded')) {
						box.innerHTML = '<p style="color:#888;padding:8px;">Loading&hellip;</p>';
						var fd = new FormData();
						fd.append('action', 'pt_price_audit_detail');
						fd.append('nonce', CFG.nonce);
						fd.append('product_id', pid);
						fetch(CFG.ajax, { method: 'POST', credentials: 'same-origin', body: fd })
							.then(function (r) { return r.json(); })
							.then(function (j) {
								if (j && j.success) { box.innerHTML = j.data.html; row.setAttribute('data-loaded', '1'); }
								else { box.innerHTML = '<p style="color:#b00;padding:8px;">Failed to load.</p>'; }
							})
							.catch(function () { box.innerHTML = '<p style="color:#b00;padding:8px;">Error loading.</p>'; });
					}
				} else {
					row.hidden = true;
					t.innerHTML = '&#9654; ' + pid;
				}
			});
		})();
		</script>
		<?php
	}
	?>
</main>
<?php
get_footer();

/* ---------------------------------------------------------------------------
 * SCRATCHPAD — historical debug helpers ported from the old theme.
 * All commented out. Uncomment (and call) one at a time while logged in as an
 * admin. Remember: NEVER hardcode a real API key here — read it from a
 * wp-config constant, e.g. defined('PT_OPTIMO_API_KEY') ? PT_OPTIMO_API_KEY : ''.
 * -------------------------------------------------------------------------

// --- Optimo delivery-completion state -------------------------------------
// $apikey = defined('PT_OPTIMO_API_KEY') ? PT_OPTIMO_API_KEY : '';
// function optimo_get_completion_state1($orderNo, $apikey) {
//     $url = add_query_arg(['key' => $apikey, 'orderNo' => $orderNo],
//         'https://api.optimoroute.com/v1/get_completion_details');
//     $resp = wp_remote_get($url, ['timeout' => 20]);
//     if (is_wp_error($resp)) return 'unknown';
//     $decoded = json_decode(wp_remote_retrieve_body($resp), true);
//     $root = is_array($decoded) && isset($decoded[0]) ? $decoded[0] : $decoded;
//     if (!is_array($root) || empty($root['orders'][0])) return 'unknown';
//     $o = $root['orders'][0];
//     if (!empty($o['code']) && $o['code'] === 'ERR_ORD_NOT_FOUND') return 'not_found';
//     if (!empty($o['data']['status']) && $o['data']['status'] === 'success') return 'delivered';
//     return 'not_delivered';
// }
// var_dump(optimo_get_completion_state1('HPY93151', $apikey));

// --- Order → GA4 item conversion debug -------------------------------------
// (see git history / old-theme test-script.php for the full debug_order_conversion())

// --- Order received URL -----------------------------------------------------
// function get_order_received_url($order_id) {
//     $order = wc_get_order($order_id);
//     return $order ? $order->get_checkout_order_received_url() : null;
// }
// echo get_order_received_url(217466);

--------------------------------------------------------------------------- */
