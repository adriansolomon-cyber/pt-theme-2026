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

/**
 * Price-per-sale trail for a set of product IDs over the last N months.
 *
 * One row per order line item: the price the product sold at (per unit, INC
 * VAT — PT prices are VAT-inclusive) and the order date, so you can see how the
 * price moved through the year. "List" is the line subtotal (catalog price at
 * sale, before order-level discounts); "Paid" is after discounts/coupons.
 * Ordered by product then date. HPOS/legacy auto-detected.
 *
 * @param int[] $product_ids Product IDs.
 * @param int   $months      Look-back window in months (default 12).
 * @return array<int,array<string,string|null>>
 */
function pt_test_product_price_rows( array $product_ids, $months = 12 ) {
	global $wpdb;

	$product_ids = array_values( array_unique( array_filter( array_map( 'intval', $product_ids ) ) ) );
	if ( empty( $product_ids ) ) {
		return array();
	}
	$months = max( 1, (int) $months );
	$in     = implode( ',', $product_ids );

	$statuses  = array( 'wc-completed', 'wc-processing', 'wc-on-hold', 'wc-refunded' );
	$status_in = "'" . implode( "','", array_map( 'esc_sql', $statuses ) ) . "'";

	$hpos = class_exists( '\\Automattic\\WooCommerce\\Utilities\\OrderUtil' )
		&& \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();

	if ( $hpos ) {
		$orders_join  = "JOIN {$wpdb->prefix}wc_orders o ON o.id = oi.order_id";
		$orders_where = "o.type = 'shop_order' AND o.status IN ($status_in) AND o.date_created_gmt >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL %d MONTH)";
		$date_col     = 'o.date_created_gmt';
	} else {
		$orders_join  = "JOIN {$wpdb->posts} o ON o.ID = oi.order_id";
		$orders_where = "o.post_type = 'shop_order' AND o.post_status IN ($status_in) AND o.post_date >= DATE_SUB(NOW(), INTERVAL %d MONTH)";
		$date_col     = 'o.post_date';
	}

	// Gross (inc-VAT) unit price = (line amount + its tax) / qty. "List" uses the
	// pre-discount subtotal; "Paid" uses the post-discount total.
	$sql = "
		SELECT
			pm.meta_value AS product_id,
			p2.post_title AS product_name,
			oi.order_id   AS order_id,
			$date_col     AS order_date,
			CAST(qm.meta_value AS UNSIGNED) AS qty,
			ROUND( ( CAST(sm.meta_value AS DECIMAL(14,4)) + CAST(COALESCE(st.meta_value,0) AS DECIMAL(14,4)) )
				/ NULLIF(CAST(qm.meta_value AS DECIMAL(14,4)),0), 2 ) AS unit_list,
			ROUND( ( CAST(tm.meta_value AS DECIMAL(14,4)) + CAST(COALESCE(tt.meta_value,0) AS DECIMAL(14,4)) )
				/ NULLIF(CAST(qm.meta_value AS DECIMAL(14,4)),0), 2 ) AS unit_paid
		FROM {$wpdb->prefix}woocommerce_order_items oi
		JOIN {$wpdb->prefix}woocommerce_order_itemmeta pm
			ON pm.order_item_id = oi.order_item_id AND pm.meta_key = '_product_id'
		LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta qm
			ON qm.order_item_id = oi.order_item_id AND qm.meta_key = '_qty'
		LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta sm
			ON sm.order_item_id = oi.order_item_id AND sm.meta_key = '_line_subtotal'
		LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta st
			ON st.order_item_id = oi.order_item_id AND st.meta_key = '_line_subtotal_tax'
		LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta tm
			ON tm.order_item_id = oi.order_item_id AND tm.meta_key = '_line_total'
		LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta tt
			ON tt.order_item_id = oi.order_item_id AND tt.meta_key = '_line_tax'
		$orders_join
		LEFT JOIN {$wpdb->posts} p2 ON p2.ID = pm.meta_value
		WHERE pm.meta_value IN ($in)
			AND oi.order_item_type = 'line_item'
			AND $orders_where
		ORDER BY CAST(pm.meta_value AS UNSIGNED), $date_col
	";

	return $wpdb->get_results( $wpdb->prepare( $sql, $months ), ARRAY_A );
}

/**
 * Load the full product-ID list for the price audit from its data file.
 *
 * @return int[]
 */
function pt_price_audit_ids() {
	$file = get_stylesheet_directory() . '/includes/pt-price-audit-ids.php';
	if ( ! file_exists( $file ) ) {
		return array();
	}
	return array_values( array_unique( array_filter( array_map( 'intval', (array) include $file ) ) ) );
}

/**
 * Map each composite SIZE-option product ID → its parent composite (id, title,
 * url). Sizes aren't linked by post_parent; they're the "Size" component's
 * options on the parent composite. We walk every composite (~60) once and cache
 * the reverse map, so resolving a size's parent is a array lookup thereafter.
 *
 * @return array<int,array{id:int,title:string,url:string}>
 */
function pt_size_parent_map() {
	$cached = get_transient( 'pt_size_parent_map_v1' );
	if ( is_array( $cached ) ) {
		return $cached;
	}

	$map = array();
	if ( ! function_exists( 'wc_get_product' ) ) {
		return $map;
	}

	$composite_ids = get_posts(
		array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'numberposts'    => -1,
			'fields'         => 'ids',
			'tax_query'      => array(
				array(
					'taxonomy' => 'product_type',
					'field'    => 'slug',
					'terms'    => 'composite',
				),
			),
		)
	);

	foreach ( $composite_ids as $cpid ) {
		$composite = wc_get_product( $cpid );
		if ( ! $composite || ! is_callable( array( $composite, 'get_components' ) ) ) {
			continue;
		}
		$parent = array(
			'id'    => (int) $cpid,
			'title' => $composite->get_name(),
			'url'   => (string) get_permalink( $cpid ),
		);
		foreach ( (array) $composite->get_components() as $comp ) {
			if ( ! is_callable( array( $comp, 'get_title' ) ) || 'size' !== strtolower( trim( (string) $comp->get_title() ) ) ) {
				continue; // Only the Size component's options are size products.
			}
			if ( ! is_callable( array( $comp, 'get_options' ) ) ) {
				continue;
			}
			foreach ( (array) $comp->get_options() as $oid ) {
				$oid = (int) $oid;
				if ( $oid > 0 && ! isset( $map[ $oid ] ) ) {
					$map[ $oid ] = $parent;
				}
			}
		}
	}

	set_transient( 'pt_size_parent_map_v1', $map, 6 * HOUR_IN_SECONDS );
	return $map;
}

/**
 * Render the per-sale price trail for one (or a few) product IDs — the detailed
 * drill-down. One row per sale, oldest first, with change markers and spread.
 *
 * @param int[] $ids    Product IDs.
 * @param int   $months Look-back window.
 */
function pt_render_price_detail( array $ids, $months = 12 ) {
	$rows = pt_test_product_price_rows( $ids, $months );

	echo '<p style="margin:0 0 16px;"><a href="' . esc_url( remove_query_arg( 'product' ) ) . '" style="text-decoration:none;">← Back to all products</a></p>';
	echo '<h1 style="margin:0 0 8px;font-size:24px;">Price trail — product ' . esc_html( implode( ', ', array_map( 'intval', $ids ) ) ) . '</h1>';

	// Parent composite (which building this size belongs to).
	$parent_map = pt_size_parent_map();
	foreach ( array_map( 'intval', $ids ) as $iid ) {
		if ( isset( $parent_map[ $iid ] ) ) {
			$par = $parent_map[ $iid ];
			echo '<p style="margin:0 0 8px;font-size:15px;">Parent product: <a href="' . esc_url( $par['url'] ) . '" target="_blank" rel="noopener" style="color:#06c;">' . esc_html( $par['title'] ) . '</a> <span style="color:#999;">(#' . (int) $par['id'] . ')</span></p>';
		}
	}

	echo '<p style="color:#666;margin:0 0 20px;">One row per sale, oldest first. Per unit, <strong>inc VAT</strong>. <strong>List</strong> = price at add-to-cart (pre-coupon); <strong>Sold</strong> = the real price charged; <strong>Disc</strong> = List − Sold. ▲ marks a change from the previous sale.</p>';

	if ( ! $rows ) {
		echo '<p>No sales in period.</p>';
		return;
	}

	// Spread per product (highest − lowest list price).
	$spread = array();
	foreach ( $rows as $r ) {
		$pid = (int) $r['product_id'];
		$lv  = (float) $r['unit_list'];
		if ( ! isset( $spread[ $pid ] ) ) {
			$spread[ $pid ] = array( 'min' => $lv, 'max' => $lv );
		} else {
			$spread[ $pid ]['min'] = min( $spread[ $pid ]['min'], $lv );
			$spread[ $pid ]['max'] = max( $spread[ $pid ]['max'], $lv );
		}
	}

	echo '<table style="width:100%;border-collapse:collapse;font-size:13px;">';
	echo '<thead><tr style="text-align:left;border-bottom:2px solid #111;">';
	foreach ( array( 'Product ID', 'Product', 'Order ID', 'Order date', 'Qty', 'List £', 'Sold £ (real)', 'Disc £', 'Lowest £', 'Highest £', 'Diff £' ) as $h ) {
		echo '<th style="padding:7px 10px;vertical-align:top;">' . esc_html( $h ) . '</th>';
	}
	echo '</tr></thead><tbody>';

	$prev_pid  = null;
	$prev_list = null;
	foreach ( $rows as $r ) {
		$pid  = (int) $r['product_id'];
		$list = number_format( (float) $r['unit_list'], 2 );
		$paid = number_format( (float) $r['unit_paid'], 2 );

		$new_group = ( $pid !== $prev_pid );
		if ( $new_group ) {
			$prev_list = null;
		}
		$changed = ( ! $new_group && null !== $prev_list && $list !== $prev_list );

		echo '<tr style="' . ( $new_group ? 'border-top:2px solid #bbb;' : 'border-bottom:1px solid #eee;' ) . '">';
		echo '<td style="padding:6px 10px;color:#999;">' . esc_html( (string) $pid ) . '</td>';
		echo '<td style="padding:6px 10px;">' . esc_html( $r['product_name'] ? $r['product_name'] : '(deleted product)' ) . '</td>';
		echo '<td style="padding:6px 10px;">' . esc_html( (string) $r['order_id'] ) . '</td>';
		echo '<td style="padding:6px 10px;white-space:nowrap;">' . esc_html( substr( (string) $r['order_date'], 0, 10 ) ) . '</td>';
		echo '<td style="padding:6px 10px;">' . esc_html( (string) (int) $r['qty'] ) . '</td>';
		echo '<td style="padding:6px 10px;font-weight:700;' . ( $changed ? 'background:#fff4c2;' : '' ) . '">£' . esc_html( $list ) . ( $changed ? ' ▲' : '' ) . '</td>';
		echo '<td style="padding:6px 10px;color:#555;">£' . esc_html( $paid ) . '</td>';

		$disc = (float) $r['unit_list'] - (float) $r['unit_paid'];
		if ( $disc > 0.005 ) {
			echo '<td style="padding:6px 10px;font-weight:700;color:#b00;">−£' . esc_html( number_format( $disc, 2 ) ) . '</td>';
		} else {
			echo '<td style="padding:6px 10px;color:#999;">£0.00</td>';
		}

		if ( $new_group && isset( $spread[ $pid ] ) ) {
			$diff = $spread[ $pid ]['max'] - $spread[ $pid ]['min'];
			echo '<td style="padding:6px 10px;">£' . esc_html( number_format( $spread[ $pid ]['min'], 2 ) ) . '</td>';
			echo '<td style="padding:6px 10px;">£' . esc_html( number_format( $spread[ $pid ]['max'], 2 ) ) . '</td>';
			echo '<td style="padding:6px 10px;font-weight:700;color:' . ( $diff > 0 ? '#b00' : '#999' ) . ';">£' . esc_html( number_format( $diff, 2 ) ) . '</td>';
		} else {
			echo '<td></td><td></td><td></td>';
		}
		echo '</tr>';

		$prev_pid  = $pid;
		$prev_list = $list;
	}
	echo '</tbody></table>';
}

/**
 * Aggregate per-sale price rows into one summary per product — the exact shape
 * the on-screen table and the summary CSV both render. Rows must be date-asc
 * (as pt_test_product_price_rows returns them) so first/last read correctly.
 *
 * @param array $rows Rows from pt_test_product_price_rows().
 * @return array<int,array<string,mixed>> Keyed by product id.
 */
function pt_aggregate_price_rows( array $rows ) {
	$agg = array();
	foreach ( $rows as $r ) {
		$pid = (int) $r['product_id'];
		$lv  = (float) $r['unit_list'];
		if ( ! isset( $agg[ $pid ] ) ) {
			$agg[ $pid ] = array(
				'name'       => $r['product_name'],
				'units'      => 0,
				'orders'     => array(),
				'first_list' => $lv,
				'first_date' => $r['order_date'],
				'last_list'  => $lv,
				'last_date'  => $r['order_date'],
				'min'        => $lv,
				'max'        => $lv,
			);
		}
		$agg[ $pid ]['units']                   += (int) $r['qty'];
		$agg[ $pid ]['orders'][ $r['order_id'] ] = true;
		$agg[ $pid ]['last_list']                = $lv; // rows are date-ascending
		$agg[ $pid ]['last_date']                = $r['order_date'];
		$agg[ $pid ]['min']                      = min( $agg[ $pid ]['min'], $lv );
		$agg[ $pid ]['max']                      = max( $agg[ $pid ]['max'], $lv );
	}
	return $agg;
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
	$ids        = pt_price_audit_ids();
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
		// Same columns as the on-screen table (dates broken out for the sheet),
		// plus the parent composite (title + URL) each size belongs to.
		fputcsv( $out, array( 'product_id', 'product', 'parent_id', 'parent_title', 'parent_url', 'units', 'orders', 'first_price_incvat', 'first_date', 'last_price_incvat', 'last_date', 'lowest_incvat', 'highest_incvat', 'diff_incvat' ) );

		foreach ( array_chunk( $ids, 50 ) as $chunk ) {
			$agg = pt_aggregate_price_rows( pt_test_product_price_rows( $chunk, 12 ) );
			foreach ( $chunk as $pid ) {
				$pid = (int) $pid;
				$par = isset( $parent_map[ $pid ] ) ? $parent_map[ $pid ] : array( 'id' => '', 'title' => '', 'url' => '' );
				if ( ! isset( $agg[ $pid ] ) ) {
					// No sales in period — still list the product (with parent), like the web view.
					fputcsv( $out, array( $pid, '', $par['id'], $par['title'], $par['url'], 0, 0, '', '', '', '', '', '', '' ) );
					continue;
				}
				$a    = $agg[ $pid ];
				$diff = $a['max'] - $a['min'];
				fputcsv(
					$out,
					array(
						$pid,
						$a['name'],
						$par['id'],
						$par['title'],
						$par['url'],
						(int) $a['units'],
						count( $a['orders'] ),
						number_format( (float) $a['first_list'], 2, '.', '' ),
						substr( (string) $a['first_date'], 0, 10 ),
						number_format( (float) $a['last_list'], 2, '.', '' ),
						substr( (string) $a['last_date'], 0, 10 ),
						number_format( (float) $a['min'], 2, '.', '' ),
						number_format( (float) $a['max'], 2, '.', '' ),
						number_format( $diff, 2, '.', '' ),
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
		fputcsv( $out, array( 'product_id', 'product', 'parent_id', 'parent_title', 'parent_url', 'order_id', 'order_date', 'qty', 'list_incvat', 'sold_incvat', 'discount_incvat' ) );

		foreach ( array_chunk( $ids, 50 ) as $chunk ) {
			$rows = pt_test_product_price_rows( $chunk, 12 );
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
						$par['url'],
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

	// --- Price audit — paginated per-product summary + CSV export ---------
	$pt_months   = 12;
	$pt_per_page = 50;
	$pt_all_ids  = pt_price_audit_ids();

	if ( ! function_exists( 'wc_get_product' ) ) {
		echo '<p><strong>WooCommerce is not active.</strong></p>';
	} elseif ( empty( $pt_all_ids ) ) {
		echo '<p><strong>No product IDs loaded.</strong> Add them to <code>includes/pt-price-audit-ids.php</code>.</p>';
	} elseif ( isset( $_GET['product'] ) && in_array( (int) $_GET['product'], $pt_all_ids, true ) ) {
		// Single-product drill-down: full per-sale trail.
		pt_render_price_detail( array( (int) $_GET['product'] ), $pt_months );
	} else {
		// Paginated per-product aggregate (50 products per batch).
		$pt_total = count( $pt_all_ids );
		$pt_pages = (int) max( 1, ceil( $pt_total / $pt_per_page ) );
		$pt_batch = isset( $_GET['batch'] ) ? max( 1, min( $pt_pages, (int) $_GET['batch'] ) ) : 1;
		$pt_slice = array_slice( $pt_all_ids, ( $pt_batch - 1 ) * $pt_per_page, $pt_per_page );

		printf( '<h1 style="margin:0 0 6px;font-size:26px;">Price audit — %d products, last %d months</h1>', (int) $pt_total, (int) $pt_months );
		echo '<p style="color:#666;margin:0 0 14px;">Per-product price movement. Real orders only (completed/processing/on-hold/refunded); prices per unit <strong>inc VAT</strong>. Showing <strong>batch ' . (int) $pt_batch . ' of ' . (int) $pt_pages . '</strong> (' . count( $pt_slice ) . ' products). Click a product ID for its full per-sale trail.</p>';

		echo '<p style="margin:0 0 22px;">'
			. '<a href="' . esc_url( add_query_arg( array( 'export' => 'summary' ) ) ) . '" style="display:inline-block;background:#111;color:#fff;padding:9px 14px;border-radius:6px;text-decoration:none;font-size:14px;">&#8595; Download summary CSV (this table, all ' . (int) $pt_total . ')</a> '
			. '<a href="' . esc_url( add_query_arg( array( 'export' => 'detail' ) ) ) . '" style="display:inline-block;background:#fff;color:#111;border:1px solid #111;padding:8px 14px;border-radius:6px;text-decoration:none;font-size:14px;margin-left:8px;">&#8595; Full per-sale CSV</a> '
			. '<span style="color:#999;font-size:12px;">may take a minute</span></p>';

		// One bounded query for this batch's IDs, aggregated per product in PHP.
		$agg           = pt_aggregate_price_rows( pt_test_product_price_rows( $pt_slice, $pt_months ) );
		$pt_parent_map = pt_size_parent_map();

		// Small helper: the linked parent-product cell for a size id.
		$pt_parent_cell = static function ( $pid ) use ( $pt_parent_map ) {
			$par = isset( $pt_parent_map[ (int) $pid ] ) ? $pt_parent_map[ (int) $pid ] : null;
			if ( ! $par ) {
				return '<td style="padding:6px 10px;color:#bbb;">—</td>';
			}
			return '<td style="padding:6px 10px;"><a href="' . esc_url( $par['url'] ) . '" target="_blank" rel="noopener" style="color:#06c;text-decoration:none;">' . esc_html( $par['title'] ) . '</a></td>';
		};

		echo '<table style="width:100%;border-collapse:collapse;font-size:13px;">';
		echo '<thead><tr style="text-align:left;border-bottom:2px solid #111;">';
		foreach ( array( 'Product ID', 'Product', 'Parent product', 'Units', 'Orders', 'First £ (date)', 'Last £ (date)', 'Lowest £', 'Highest £', 'Diff £' ) as $h ) {
			echo '<th style="padding:7px 10px;vertical-align:top;">' . esc_html( $h ) . '</th>';
		}
		echo '</tr></thead><tbody>';

		foreach ( $pt_slice as $pid ) {
			$detail_url = esc_url( add_query_arg( array( 'product' => (int) $pid ) ) );
			if ( ! isset( $agg[ $pid ] ) ) {
				echo '<tr style="border-bottom:1px solid #eee;color:#aaa;">';
				echo '<td style="padding:6px 10px;"><a href="' . $detail_url . '" style="color:#999;">' . esc_html( (string) $pid ) . '</a></td>';
				echo '<td style="padding:6px 10px;color:#bbb;">—</td>';
				echo $pt_parent_cell( $pid );
				echo '<td style="padding:6px 10px;" colspan="7">no sales in period</td></tr>';
				continue;
			}
			$a    = $agg[ $pid ];
			$diff = $a['max'] - $a['min'];
			echo '<tr style="border-bottom:1px solid #eee;">';
			echo '<td style="padding:6px 10px;"><a href="' . $detail_url . '" style="color:#06c;font-weight:600;text-decoration:none;">' . esc_html( (string) $pid ) . '</a></td>';
			echo '<td style="padding:6px 10px;">' . esc_html( $a['name'] ? $a['name'] : '(deleted product)' ) . '</td>';
			echo $pt_parent_cell( $pid );
			echo '<td style="padding:6px 10px;font-weight:700;">' . esc_html( (string) $a['units'] ) . '</td>';
			echo '<td style="padding:6px 10px;">' . esc_html( (string) count( $a['orders'] ) ) . '</td>';
			echo '<td style="padding:6px 10px;white-space:nowrap;">£' . esc_html( number_format( (float) $a['first_list'], 2 ) ) . ' <span style="color:#999;">' . esc_html( substr( (string) $a['first_date'], 0, 10 ) ) . '</span></td>';
			echo '<td style="padding:6px 10px;white-space:nowrap;">£' . esc_html( number_format( (float) $a['last_list'], 2 ) ) . ' <span style="color:#999;">' . esc_html( substr( (string) $a['last_date'], 0, 10 ) ) . '</span></td>';
			echo '<td style="padding:6px 10px;">£' . esc_html( number_format( (float) $a['min'], 2 ) ) . '</td>';
			echo '<td style="padding:6px 10px;">£' . esc_html( number_format( (float) $a['max'], 2 ) ) . '</td>';
			echo '<td style="padding:6px 10px;font-weight:700;color:' . ( $diff > 0.005 ? '#b00' : '#999' ) . ';">£' . esc_html( number_format( $diff, 2 ) ) . '</td>';
			echo '</tr>';
		}
		echo '</tbody></table>';

		// Pagination.
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
