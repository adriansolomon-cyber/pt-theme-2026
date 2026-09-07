<?php
/**
 * PT — Price audit data layer + AJAX detail endpoint.
 * =============================================================================
 * Shared functions for the price-audit tool rendered by templates/page-test.php:
 * the per-sale query, the ID list loader, the size→parent-composite map, the
 * per-product aggregator, and the standalone drill-down renderer. Kept here (not
 * in the page template) so the AJAX handler below is registered on every load —
 * admin-ajax.php never includes the template.
 *
 * All read-only and admin-gated at the point of use. Prices are inc VAT.
 *
 * @package pt-theme-2026
 */

defined( 'ABSPATH' ) || exit;

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

/**
 * Coupon code(s) applied to each of the given orders, keyed by order ID.
 * One cheap query against the order-items table (coupon line items).
 *
 * @param int[] $order_ids Order IDs.
 * @return array<int,string> order_id => "CODE1, CODE2"
 */
function pt_price_audit_order_coupons( array $order_ids ) {
	global $wpdb;
	$order_ids = array_values( array_unique( array_filter( array_map( 'intval', $order_ids ) ) ) );
	if ( empty( $order_ids ) ) {
		return array();
	}
	$in   = implode( ',', $order_ids );
	$rows = $wpdb->get_results(
		"SELECT order_id, GROUP_CONCAT(order_item_name ORDER BY order_item_name SEPARATOR ', ') AS coupons
		 FROM {$wpdb->prefix}woocommerce_order_items
		 WHERE order_item_type = 'coupon' AND order_id IN ($in)
		 GROUP BY order_id",
		ARRAY_A
	);
	$map = array();
	foreach ( (array) $rows as $r ) {
		$map[ (int) $r['order_id'] ] = (string) $r['coupons'];
	}
	return $map;
}

/**
 * AJAX: return the full per-sale trail for ONE product as an HTML table.
 * Admin-only, nonce-checked. Loaded on demand when a row is expanded, so the
 * main page never queries every product's sales up front.
 */
add_action( 'wp_ajax_pt_price_audit_detail', 'pt_price_audit_detail_ajax' );
function pt_price_audit_detail_ajax() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_send_json_error( 'forbidden', 403 );
	}
	check_ajax_referer( 'pt_price_audit', 'nonce' );

	$pid = isset( $_POST['product_id'] ) ? (int) $_POST['product_id'] : 0;
	if ( ! $pid || ! function_exists( 'wc_get_product' ) ) {
		wp_send_json_error( 'bad request', 400 );
	}

	$months  = 12;
	$rows    = pt_test_product_price_rows( array( $pid ), $months );
	$coupons = pt_price_audit_order_coupons( array_map( static function ( $r ) {
		return (int) $r['order_id'];
	}, $rows ) );

	ob_start();
	if ( ! $rows ) {
		echo '<p style="margin:8px 4px;color:#888;">No sales in the last ' . (int) $months . ' months.</p>';
	} else {
		echo '<table style="width:100%;border-collapse:collapse;font-size:12.5px;margin:6px 0 2px;">';
		echo '<thead><tr style="text-align:left;border-bottom:1px solid #ccc;color:#555;">';
		foreach ( array( 'Order ID', 'Order date', 'Qty', 'List £', 'Sold £', 'Disc £', 'Coupon' ) as $h ) {
			echo '<th style="padding:5px 8px;">' . esc_html( $h ) . '</th>';
		}
		echo '</tr></thead><tbody>';

		$prev_list = null;
		foreach ( $rows as $r ) {
			$oid     = (int) $r['order_id'];
			$list    = number_format( (float) $r['unit_list'], 2 );
			$paid    = number_format( (float) $r['unit_paid'], 2 );
			$disc    = (float) $r['unit_list'] - (float) $r['unit_paid'];
			$changed = ( null !== $prev_list && $list !== $prev_list );
			$coupon  = isset( $coupons[ $oid ] ) ? $coupons[ $oid ] : '';

			echo '<tr style="border-bottom:1px solid #f0f0f0;">';
			echo '<td style="padding:5px 8px;">' . esc_html( (string) $oid ) . '</td>';
			echo '<td style="padding:5px 8px;white-space:nowrap;">' . esc_html( substr( (string) $r['order_date'], 0, 10 ) ) . '</td>';
			echo '<td style="padding:5px 8px;">' . esc_html( (string) (int) $r['qty'] ) . '</td>';
			echo '<td style="padding:5px 8px;font-weight:700;' . ( $changed ? 'background:#fff4c2;' : '' ) . '">£' . esc_html( $list ) . ( $changed ? ' ▲' : '' ) . '</td>';
			echo '<td style="padding:5px 8px;color:#555;">£' . esc_html( $paid ) . '</td>';
			if ( $disc > 0.005 ) {
				echo '<td style="padding:5px 8px;font-weight:700;color:#b00;">−£' . esc_html( number_format( $disc, 2 ) ) . '</td>';
			} else {
				echo '<td style="padding:5px 8px;color:#999;">£0.00</td>';
			}
			echo '<td style="padding:5px 8px;">' . ( '' !== $coupon ? '<span style="background:#eef;border:1px solid #ccd;border-radius:4px;padding:1px 6px;">' . esc_html( $coupon ) . '</span>' : '<span style="color:#bbb;">—</span>' ) . '</td>';
			echo '</tr>';

			$prev_list = $list;
		}
		echo '</tbody></table>';
		echo '<p style="color:#888;font-size:11.5px;margin:6px 4px 2px;">' . count( $rows ) . ' sale' . ( count( $rows ) === 1 ? '' : 's' ) . '. Last sale: ' . esc_html( substr( (string) end( $rows )['order_date'], 0, 10 ) ) . '. ▲ = list-price change from the previous sale.</p>';
	}
	$html = ob_get_clean();

	wp_send_json_success( array( 'html' => $html, 'count' => count( $rows ) ) );
}
