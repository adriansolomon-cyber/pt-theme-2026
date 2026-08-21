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
