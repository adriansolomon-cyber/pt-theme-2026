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

	// --- Size-product sales — last 12 months ------------------------------
	$pt_sales_months = 12;
	$pt_sales_ids    = array(
		16261, 22040, 18432, 102075, 111948, 14795, 122926, 56961, 58078,
		63159, 67573, 16048, 17292, 102162, 16616, 69686, 18610, 16553,
		45308, 46710, 49119, 77662,
	);

	if ( ! function_exists( 'wc_get_product' ) ) {
		echo '<p><strong>WooCommerce is not active.</strong></p>';
	} else {
		$pt_rows        = pt_test_product_sales( $pt_sales_ids, $pt_sales_months );
		$pt_total_units = 0;
		$pt_total_ords  = 0;
		$pt_found_ids   = array();

		printf(
			'<h1 style="margin:0 0 8px;font-size:26px;">Size-product sales — last %d months</h1>',
			(int) $pt_sales_months
		);
		echo '<p style="color:#666;margin:0 0 20px;">Units sold and orders for each product ID. Real orders only (completed, processing, on-hold, refunded). Matched on <code>_product_id</code>.</p>';

		echo '<table style="width:100%;border-collapse:collapse;font-size:14px;">';
		echo '<thead><tr style="text-align:left;border-bottom:2px solid #111;">';
		foreach ( array( 'Product ID', 'Product', 'Units sold', 'Orders', 'Order IDs' ) as $h ) {
			echo '<th style="padding:8px 10px;vertical-align:top;">' . esc_html( $h ) . '</th>';
		}
		echo '</tr></thead><tbody>';

		foreach ( $pt_rows as $r ) {
			$pt_found_ids[]  = (int) $r['product_id'];
			$pt_total_units += (int) $r['units_sold'];
			$pt_total_ords  += (int) $r['orders'];
			echo '<tr style="border-bottom:1px solid #e5e5e5;">';
			echo '<td style="padding:8px 10px;color:#999;">' . esc_html( $r['product_id'] ) . '</td>';
			echo '<td style="padding:8px 10px;">' . esc_html( $r['product_name'] ? $r['product_name'] : '(deleted product)' ) . '</td>';
			echo '<td style="padding:8px 10px;font-weight:700;">' . esc_html( (string) (int) $r['units_sold'] ) . '</td>';
			echo '<td style="padding:8px 10px;">' . esc_html( (string) (int) $r['orders'] ) . '</td>';
			echo '<td style="padding:8px 10px;color:#555;font-size:12px;word-break:break-all;">' . esc_html( (string) $r['order_ids'] ) . '</td>';
			echo '</tr>';
		}

		echo '</tbody><tfoot><tr style="border-top:2px solid #111;font-weight:700;">';
		echo '<td style="padding:10px;" colspan="2">TOTAL</td>';
		echo '<td style="padding:10px;">' . esc_html( (string) $pt_total_units ) . '</td>';
		echo '<td style="padding:10px;" colspan="2">' . esc_html( (string) $pt_total_ords ) . ' order rows</td>';
		echo '</tr></tfoot></table>';

		// Flag any requested IDs that had no sales in the window.
		$pt_missing = array_values( array_diff( array_map( 'intval', $pt_sales_ids ), $pt_found_ids ) );
		if ( $pt_missing ) {
			echo '<p style="margin:18px 0 0;color:#b00;"><strong>No sales in period (' . count( $pt_missing ) . '):</strong> ' . esc_html( implode( ', ', $pt_missing ) ) . '</p>';
		}
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
