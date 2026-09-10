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
 * Wall-thickness 11mm↔16mm pairs for every published Grandmaster composite,
 * paired BY SIZE via the composite scenarios (the option names carry no size,
 * so name-matching can't pair them — the size↔option link lives in the
 * scenarios that timber-product-config.php already resolves).
 *
 * One row per composite×size: the size label, the 11mm option (standard, £0)
 * and the 16mm option (the upcharge), each with inc/ex-VAT price, plus the
 * target (16mm inc) the 11mm would move to and a shared-across-composites flag.
 *
 * @return array{rows:array<int,array<string,mixed>>,error:string}
 */
function pt_wall_pairs() {
	if ( ! function_exists( 'timber_pcfg_build' ) ) {
		return array( 'rows' => array(), 'error' => 'timber_pcfg_build() unavailable — the timber-product-config mu-plugin is not loaded, so scenarios cannot be read.' );
	}
	$gm_ids = get_posts( array(
		'post_type'   => 'product',
		'post_status' => 'publish',
		'numberposts' => -1,
		'fields'      => 'ids',
		'tax_query'   => array(
			'relation' => 'AND',
			array( 'taxonomy' => 'product_type', 'field' => 'slug', 'terms' => 'composite' ),
			array( 'taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => 'grandmaster' ),
		),
	) );
	$rows   = array();
	$seen11 = array();
	$debug  = array( 'gm_count' => count( $gm_ids ), 'first' => '' );
	$dbg_done = false;
	foreach ( $gm_ids as $gmid ) {
		$data = timber_pcfg_build( (int) $gmid );
		if ( ! $dbg_done ) {
			// Snapshot the first composite so an empty result can explain itself.
			if ( is_wp_error( $data ) ) {
				$debug['first'] = '#' . (int) $gmid . ' build error: ' . $data->get_error_message();
			} else {
				$comp_desc = array();
				foreach ( (array) ( isset( $data['components'] ) ? $data['components'] : array() ) as $c ) {
					$comp_desc[] = ( isset( $c['title'] ) ? $c['title'] : '?' ) . ' [key=' . ( isset( $c['key'] ) ? $c['key'] : '?' ) . ']';
				}
				$debug['first'] = '#' . (int) $gmid . ' components: ' . ( $comp_desc ? implode( ', ', $comp_desc ) : 'none' ) . ' · sizes=' . count( (array) ( isset( $data['sizes'] ) ? $data['sizes'] : array() ) );
			}
			$dbg_done = true;
		}
		if ( is_wp_error( $data ) || empty( $data['components'] ) ) {
			continue;
		}
		// Wall component id for this composite. Match the key OR any title
		// containing "wall" — the key is only 'wall' when the title is exactly
		// "Wall Thickness"; a stray space/wording gives 'wall_thickness' instead.
		$wall_cid = '';
		foreach ( (array) $data['components'] as $c ) {
			$ckey   = isset( $c['key'] ) ? (string) $c['key'] : '';
			$ctitle = isset( $c['title'] ) ? (string) $c['title'] : '';
			if ( 'wall' === $ckey || false !== stripos( $ckey, 'wall' ) || false !== stripos( $ctitle, 'wall' ) ) {
				$wall_cid = (string) $c['id'];
				break;
			}
		}
		if ( '' === $wall_cid ) {
			continue;
		}
		foreach ( (array) $data['sizes'] as $sz ) {
			$wallopts = isset( $sz['options'][ $wall_cid ] ) ? (array) $sz['options'][ $wall_cid ] : array();
			$o11 = null;
			$o16 = null;
			foreach ( $wallopts as $o ) {
				$nm = isset( $o['name'] ) ? (string) $o['name'] : '';
				if ( preg_match( '/11\s*mm/i', $nm ) ) {
					$o11 = $o;
				} elseif ( preg_match( '/16\s*mm/i', $nm ) ) {
					$o16 = $o;
				}
			}
			if ( ! $o11 && ! $o16 ) {
				continue;
			}
			$o11id = $o11 ? (int) $o11['id'] : 0;
			$o16id = $o16 ? (int) $o16['id'] : 0;
			$rows[] = array(
				'composite_id'   => (int) $gmid,
				'composite_name' => isset( $data['name'] ) ? (string) $data['name'] : '',
				'size'           => isset( $sz['name'] ) ? (string) $sz['name'] : '',
				'o11_id'         => $o11id,
				'o11_name'       => $o11 ? (string) $o11['name'] : '',
				'o11_inc'        => $o11id ? (float) pt_audit_product_price( $o11id ) : null,
				'o11_ex'         => $o11id ? (float) pt_audit_product_price_net( $o11id ) : null,
				'o16_id'         => $o16id,
				'o16_name'       => $o16 ? (string) $o16['name'] : '',
				'o16_inc'        => $o16id ? (float) pt_audit_product_price( $o16id ) : null,
				'o16_ex'         => $o16id ? (float) pt_audit_product_price_net( $o16id ) : null,
				'shared'         => ( $o11id && isset( $seen11[ $o11id ] ) ) ? 'yes' : '',
			);
			if ( $o11id ) {
				$seen11[ $o11id ] = true;
			}
		}
	}
	return array( 'rows' => $rows, 'error' => '', 'debug' => $debug );
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
		fputcsv( $out, array( 'product_id', 'product', 'parent_id', 'parent_title', 'parent_size_url', 'units', 'orders', 'first_price_incvat', 'first_date', 'last_price_incvat', 'last_date', 'lowest_list_incvat', 'lowest_sold_incvat', 'highest_list_incvat', 'highest_sold_incvat', 'change_list_incvat', 'change_sold_incvat', 'change_list_pct', 'change_sold_pct', 'listing_price_incvat', 'suggested_incvat', 'cogs_exvat', 'net_margin', 'net_margin_pct' ) );

		foreach ( array_chunk( $ids, 50 ) as $chunk ) {
			$agg = pt_aggregate_price_rows( pt_test_product_price_rows( $chunk ) );
			foreach ( $chunk as $pid ) {
				$pid     = (int) $pid;
				$par     = isset( $parent_map[ $pid ] ) ? $parent_map[ $pid ] : array( 'id' => '', 'title' => '', 'url' => '' );
				$listing = pt_audit_product_price( $pid );
				$cogs    = pt_audit_product_cogs( $pid );
				if ( ! isset( $agg[ $pid ] ) ) {
					// No sales in period — still list the product (with parent + listing + COGS), like the web view.
					fputcsv( $out, array( $pid, '', $par['id'], $par['title'], pt_audit_size_url( $par['url'], get_the_title( $pid ) ), 0, 0, '', '', '', '', '', '', '', '', '', '', '', '', number_format( $listing, 2, '.', '' ), '', number_format( $cogs, 2, '.', '' ), '', '' ) );
					continue;
				}
				$a          = $agg[ $pid ];
				$chg        = (float) $a['last_list'] - (float) $a['first_list'];    // directional last − first (list)
				$chgpct     = ( $a['first_list'] > 0 ) ? ( $chg / (float) $a['first_list'] * 100 ) : 0.0;
				$chg_sold   = (float) $a['last_sold'] - (float) $a['first_sold'];    // directional last − first (sold)
				$chgpct_sld = ( $a['first_sold'] > 0 ) ? ( $chg_sold / (float) $a['first_sold'] * 100 ) : 0.0;
				$margin     = (float) $a['last_sold_net'] - $cogs;                    // NET margin at last sold price
				$margin_pct = ( (float) $a['last_sold_net'] > 0 ) ? ( $margin / (float) $a['last_sold_net'] * 100 ) : 0.0;
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
						( isset( $a['suggested'] ) && (float) $a['suggested'] > 0 ) ? number_format( (float) $a['suggested'], 2, '.', '' ) : '',
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
		fputcsv( $out, array( 'product_id', 'product', 'parent_id', 'parent_title', 'parent_size_url', 'order_id', 'order_date', 'qty', 'list_incvat', 'sold_incvat', 'discount_incvat' ) );

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

/*
 * Wall-thickness export (admin only): one row per Grandmaster composite × size,
 * paired 11mm↔16mm BY SCENARIO (option names carry no size). Columns cover the
 * composite (parent), size, both option IDs/names, inc/ex-VAT prices, the 16mm
 * target the 11mm would move to, the delta and a shared flag.
 * ?export=wall. Runs before theme output and exits.
 */
if ( current_user_can( 'manage_woocommerce' )
	&& 'wall' === ( isset( $_GET['export'] ) ? sanitize_key( $_GET['export'] ) : '' )
	&& function_exists( 'wc_get_product' )
	&& ! headers_sent()
) {
	@set_time_limit( 0 );
	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="pt-wall-pairs-' . gmdate( 'Y-m-d' ) . '.csv"' );
	$out = fopen( 'php://output', 'w' );
	fputcsv( $out, array( 'composite_id', 'composite_name', 'size', 'wall11_id', 'wall11_name', 'wall11_inc', 'wall11_ex', 'wall16_id', 'wall16_name', 'wall16_inc', 'wall16_ex', 'new_11mm_inc', 'delta_inc', 'shared' ) );
	$res = pt_wall_pairs();
	foreach ( $res['rows'] as $r ) {
		$new11 = ( null !== $r['o16_inc'] ) ? number_format( (float) $r['o16_inc'], 2, '.', '' ) : '';
		$delta = ( null !== $r['o16_inc'] && null !== $r['o11_inc'] ) ? number_format( (float) $r['o16_inc'] - (float) $r['o11_inc'], 2, '.', '' ) : '';
		fputcsv( $out, array(
			$r['composite_id'],
			$r['composite_name'],
			$r['size'],
			$r['o11_id'] ?: '',
			$r['o11_name'],
			( null !== $r['o11_inc'] ) ? number_format( (float) $r['o11_inc'], 2, '.', '' ) : '',
			( null !== $r['o11_ex'] ) ? number_format( (float) $r['o11_ex'], 2, '.', '' ) : '',
			$r['o16_id'] ?: '',
			$r['o16_name'],
			( null !== $r['o16_inc'] ) ? number_format( (float) $r['o16_inc'], 2, '.', '' ) : '',
			( null !== $r['o16_ex'] ) ? number_format( (float) $r['o16_ex'], 2, '.', '' ) : '',
			$new11,
			$delta,
			$r['shared'],
		) );
	}
	fclose( $out );
	exit;
}

get_header();
?>
<main class="pt-test" style="max-width:min(1760px,96vw);margin:40px auto 80px;padding:0 24px;font-family:system-ui,Arial,sans-serif;overflow-x:auto;">
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

	// --- Parent-resolution diagnostic: ?parent_check=<size_product_id> ---
	// Shows a size product's own info (SKU, slug, post_parent, categories) and
	// EVERY composite that lists it as a "Size" option — so we can see why the
	// audit's parent map resolves the building it does (and if the size is shared).
	if ( isset( $_GET['parent_check'] ) && function_exists( 'wc_get_product' ) ) {
		$pcid = (int) $_GET['parent_check'];
		$prod = $pcid ? wc_get_product( $pcid ) : null;
		echo '<h1 style="margin:0 0 10px;font-size:24px;">Parent check — size product ' . (int) $pcid . '</h1>';
		if ( ! $prod ) {
			echo '<p style="color:#b00;">No product with that ID.</p>';
		} else {
			$post = get_post( $pcid );
			echo '<p><strong>' . esc_html( $prod->get_name() ) . '</strong> (type: ' . esc_html( $prod->get_type() ) . ')</p>';
			echo '<ul style="line-height:1.7;">';
			echo '<li>SKU: <code>' . esc_html( $prod->get_sku() ?: '—' ) . '</code></li>';
			echo '<li>Slug: <code>' . esc_html( $post ? $post->post_name : '—' ) . '</code></li>';
			$ppid = (int) wp_get_post_parent_id( $pcid );
			echo '<li>post_parent: ' . ( $ppid ? esc_html( (string) $ppid . ' — ' . get_the_title( $ppid ) ) : '<span style="color:#999;">none</span>' ) . '</li>';
			$cats = get_the_terms( $pcid, 'product_cat' );
			$cat_names = ( $cats && ! is_wp_error( $cats ) ) ? implode( ', ', wp_list_pluck( $cats, 'name' ) ) : '—';
			echo '<li>Categories: ' . esc_html( $cat_names ) . '</li>';
			echo '</ul>';

			// Every composite whose "Size" component lists this product.
			echo '<h2 style="margin:18px 0 8px;font-size:18px;">Composites listing this as a Size option</h2>';
			$composite_ids = get_posts( array(
				'post_type'   => 'product',
				'post_status' => 'publish',
				'numberposts' => -1,
				'fields'      => 'ids',
				'tax_query'   => array( array( 'taxonomy' => 'product_type', 'field' => 'slug', 'terms' => 'composite' ) ),
			) );
			$refs = array();
			foreach ( $composite_ids as $cpid ) {
				$composite = wc_get_product( $cpid );
				if ( ! $composite || ! is_callable( array( $composite, 'get_components' ) ) ) {
					continue;
				}
				foreach ( (array) $composite->get_components() as $comp ) {
					if ( ! is_callable( array( $comp, 'get_title' ) ) || 'size' !== strtolower( trim( (string) $comp->get_title() ) ) ) {
						continue;
					}
					$opts = is_callable( array( $comp, 'get_options' ) ) ? array_map( 'intval', (array) $comp->get_options() ) : array();
					if ( in_array( $pcid, $opts, true ) ) {
						$refs[] = array( 'id' => (int) $cpid, 'title' => $composite->get_name() );
					}
				}
			}
			if ( ! $refs ) {
				echo '<p style="color:#b00;">None — this product is not a Size option on any published composite. The audit falls back to the "W x D" name match, which can pick the wrong building.</p>';
			} else {
				echo '<ul style="line-height:1.7;">';
				foreach ( $refs as $r ) {
					echo '<li>#' . (int) $r['id'] . ' — ' . esc_html( $r['title'] ) . '</li>';
				}
				echo '</ul>';
				if ( count( $refs ) > 1 ) {
					echo '<p style="color:#b00;font-weight:700;">Shared across ' . count( $refs ) . ' composites — the parent map keeps whichever it walks first, so the building shown may be wrong.</p>';
				}
			}

			$map = pt_size_parent_map();
			echo '<p style="margin:14px 0 0;">Audit currently resolves parent → ' . ( isset( $map[ $pcid ] ) ? '<strong>' . esc_html( $map[ $pcid ]['title'] ) . '</strong> (#' . (int) $map[ $pcid ]['id'] . ')' : '<span style="color:#999;">none</span>' ) . '</p>';
		}
		get_footer();
		return;
	}

	// --- Composite size-options diagnostic: ?config_check=<composite_id_or_slug> ---
	// Lists the "Size" component's option products with post status, stock, whether
	// they're purchasable and their price — so we can see why a size that's present
	// in the backend config doesn't render on the frontend. Woo Composite hides an
	// option when its product is unpublished, non-purchasable, out of stock or has
	// no price; a size that's simply absent from the list was never added here.
	if ( isset( $_GET['config_check'] ) && function_exists( 'wc_get_product' ) ) {
		$raw = sanitize_text_field( wp_unslash( $_GET['config_check'] ) );
		$cid = ctype_digit( $raw ) ? (int) $raw : 0;
		if ( ! $cid ) {
			$maybe = get_page_by_path( $raw, OBJECT, 'product' );
			$cid   = $maybe ? (int) $maybe->ID : 0;
		}
		$comp = $cid ? wc_get_product( $cid ) : null;
		echo '<h1 style="margin:0 0 10px;font-size:24px;">Config check — composite ' . (int) $cid . '</h1>';
		if ( ! $comp || ! is_callable( array( $comp, 'get_components' ) ) ) {
			echo '<p style="color:#b00;">No composite product with that ID or slug.</p>';
		} else {
			echo '<p><strong>' . esc_html( $comp->get_name() ) . '</strong> (type: ' . esc_html( $comp->get_type() ) . ', ID #' . (int) $cid . ')</p>';
			$found_size = false;
			foreach ( (array) $comp->get_components() as $component ) {
				$ctitle = is_callable( array( $component, 'get_title' ) ) ? (string) $component->get_title() : '';
				if ( 'size' !== strtolower( trim( $ctitle ) ) ) {
					continue;
				}
				$found_size = true;
				$opts = is_callable( array( $component, 'get_options' ) ) ? array_map( 'intval', (array) $component->get_options() ) : array();
				echo '<h2 style="margin:18px 0 8px;font-size:18px;">Size options (' . count( $opts ) . ')</h2>';
				echo '<table style="border-collapse:collapse;width:100%;font-size:13px;"><thead><tr>'
					. '<th style="text-align:left;border-bottom:2px solid #333;padding:6px 8px;">ID</th>'
					. '<th style="text-align:left;border-bottom:2px solid #333;padding:6px 8px;">Name</th>'
					. '<th style="text-align:left;border-bottom:2px solid #333;padding:6px 8px;">Status</th>'
					. '<th style="text-align:left;border-bottom:2px solid #333;padding:6px 8px;">Stock</th>'
					. '<th style="text-align:left;border-bottom:2px solid #333;padding:6px 8px;">Purchasable</th>'
					. '<th style="text-align:right;border-bottom:2px solid #333;padding:6px 8px;">Price</th>'
					. '<th style="text-align:left;border-bottom:2px solid #333;padding:6px 8px;">Frontend?</th></tr></thead><tbody>';
				foreach ( $opts as $oid ) {
					$op   = wc_get_product( $oid );
					$post = get_post( $oid );
					$name = $op ? $op->get_name() : ( $post ? $post->post_title : '(product missing)' );
					if ( ! $op ) {
						echo '<tr><td style="padding:6px 8px;border-bottom:1px solid #eee;">' . (int) $oid . '</td>'
							. '<td colspan="6" style="padding:6px 8px;border-bottom:1px solid #eee;color:#b00;">product record missing — will not show</td></tr>';
						continue;
					}
					$status   = $post ? $post->post_status : '—';
					$stock    = $op->get_stock_status();
					$in_stock = $op->is_in_stock();
					$purch    = $op->is_purchasable();
					$price    = $op->get_price();
					$reasons  = array();
					if ( 'publish' !== $status ) {
						$reasons[] = 'status: ' . $status;
					}
					if ( ! $purch ) {
						$reasons[] = 'not purchasable';
					}
					if ( ! $in_stock ) {
						$reasons[] = 'out of stock';
					}
					if ( '' === $price || null === $price ) {
						$reasons[] = 'no price';
					}
					$shows = empty( $reasons );
					$rowbg = $shows ? '#eafbea' : '#fdecec';
					echo '<tr style="background:' . $rowbg . ';">'
						. '<td style="padding:6px 8px;border-bottom:1px solid #eee;">' . (int) $oid . '</td>'
						. '<td style="padding:6px 8px;border-bottom:1px solid #eee;">' . esc_html( $name ) . '</td>'
						. '<td style="padding:6px 8px;border-bottom:1px solid #eee;">' . esc_html( $status ) . '</td>'
						. '<td style="padding:6px 8px;border-bottom:1px solid #eee;">' . esc_html( $stock ) . '</td>'
						. '<td style="padding:6px 8px;border-bottom:1px solid #eee;">' . ( $purch ? 'yes' : 'no' ) . '</td>'
						. '<td style="padding:6px 8px;border-bottom:1px solid #eee;text-align:right;">' . ( '' === $price || null === $price ? '—' : esc_html( wc_price( $price ) ) ) . '</td>'
						. '<td style="padding:6px 8px;border-bottom:1px solid #eee;font-weight:700;color:' . ( $shows ? '#0a7d28' : '#b00' ) . ';">'
						. ( $shows ? 'shows' : 'hidden — ' . esc_html( implode( ', ', $reasons ) ) ) . '</td></tr>';
				}
				echo '</tbody></table>';
			}
			if ( ! $found_size ) {
				echo '<p style="color:#b00;">This composite has no component titled “Size”.</p>';
			}
		}
		get_footer();
		return;
	}

	// --- Wall-thickness repricing diagnostic (READ-ONLY): ?wall_check=1 ---
	// One row per Grandmaster composite × size, pairing 11mm↔16mm BY SCENARIO
	// (the option names carry no size, so the size↔option link comes from the
	// composite scenarios via pt_wall_pairs()). Shows the size, both options with
	// inc/ex-VAT prices, and the 16mm price each 11mm would move to under the
	// free-upgrade repricing. Read-only — verifies numbers before any real edit.
	if ( isset( $_GET['wall_check'] ) && function_exists( 'wc_get_product' ) ) {
		echo '<h1 style="margin:0 0 10px;font-size:24px;">Wall-thickness check — Grandmaster composites</h1>';
		echo '<p style="color:#666;margin:0 0 14px;">Read-only. Plan: raise each size&rsquo;s 11mm option (standard, £0) to its 16mm price, so upgrading to 16mm costs £0. Paired by scenario/size. <a href="' . esc_url( add_query_arg( array( 'export' => 'wall', 'wall_check' => false ) ) ) . '" style="font-weight:700;">⬇ Download CSV</a> (composite, size, both option IDs + prices).</p>';
		$res = pt_wall_pairs();
		if ( '' !== $res['error'] ) {
			echo '<p style="color:#b00;">' . esc_html( $res['error'] ) . '</p>';
		} elseif ( empty( $res['rows'] ) ) {
			echo '<p style="color:#b00;">No Grandmaster composites with a Wall Thickness component were found (check the category slug / component title).</p>';
			if ( ! empty( $res['debug'] ) ) {
				echo '<p style="color:#888;font-size:12px;">Debug — Grandmaster composites found: <strong>' . (int) $res['debug']['gm_count'] . '</strong>. First: ' . esc_html( (string) $res['debug']['first'] ) . '</p>';
			}
		} else {
			$changes = 0;
			echo '<table style="border-collapse:collapse;width:100%;font-size:13px;"><thead><tr style="text-align:left;border-bottom:2px solid #111;">';
			foreach ( array( 'Composite', 'Size', '11mm ID', '11mm inc', '16mm ID', '16mm inc', '16mm ex', '→ new 11mm inc', 'Δ inc' ) as $h ) {
				echo '<th style="padding:6px 10px;">' . esc_html( $h ) . '</th>';
			}
			echo '</tr></thead><tbody>';
			foreach ( $res['rows'] as $r ) {
				$o11_inc = $r['o11_inc'];
				$o16_inc = $r['o16_inc'];
				$new11   = ( null !== $o16_inc ) ? ( '£' . number_format( (float) $o16_inc, 2 ) ) : '<span style="color:#b00;">no 16mm</span>';
				$delta   = ( null !== $o16_inc && null !== $o11_inc ) ? ( (float) $o16_inc - (float) $o11_inc ) : null;
				if ( null !== $delta && abs( $delta ) > 0.005 ) {
					$changes++;
				}
				$sharedtag = ( 'yes' === $r['shared'] ) ? ' <span style="color:#b00;font-weight:700;">(shared!)</span>' : '';
				echo '<tr style="border-bottom:1px solid #eee;background:#fff8e6;">';
				echo '<td style="padding:6px 10px;">#' . (int) $r['composite_id'] . ' ' . esc_html( $r['composite_name'] ) . '</td>';
				echo '<td style="padding:6px 10px;font-weight:700;">' . esc_html( $r['size'] ) . '</td>';
				echo '<td style="padding:6px 10px;">' . ( $r['o11_id'] ? (int) $r['o11_id'] : '—' ) . $sharedtag . '</td>';
				echo '<td style="padding:6px 10px;">' . ( null !== $o11_inc ? '£' . esc_html( number_format( (float) $o11_inc, 2 ) ) : '—' ) . '</td>';
				echo '<td style="padding:6px 10px;">' . ( $r['o16_id'] ? (int) $r['o16_id'] : '—' ) . '</td>';
				echo '<td style="padding:6px 10px;">' . ( null !== $o16_inc ? '£' . esc_html( number_format( (float) $o16_inc, 2 ) ) : '—' ) . '</td>';
				echo '<td style="padding:6px 10px;color:#888;">' . ( null !== $r['o16_ex'] ? '£' . esc_html( number_format( (float) $r['o16_ex'], 2 ) ) : '—' ) . '</td>';
				echo '<td style="padding:6px 10px;font-weight:700;color:#06507a;">' . $new11 . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput -- built above with number_format/esc_html
				echo '<td style="padding:6px 10px;">' . ( null !== $delta ? esc_html( ( $delta >= 0 ? '+£' : '−£' ) . number_format( abs( $delta ), 2 ) ) : '—' ) . '</td>';
				echo '</tr>';
			}
			echo '</tbody></table>';
			echo '<p style="margin:12px 4px 0;color:#333;"><strong>' . (int) $changes . '</strong> size(s) whose 11mm price would change (→ its 16mm price). Any <span style="color:#b00;font-weight:700;">(shared!)</span> 11mm option is used by more than one composite — editing it affects all of them, so confirm first.</p>';
		}
		get_footer();
		return;
	}

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
			echo '<p style="color:#666;margin:0 0 14px;">Search results for <strong>' . count( $pt_slice ) . '</strong> ID' . ( 1 === count( $pt_slice ) ? '' : 's' ) . '. Prices per unit <strong>inc VAT</strong>; margin/COGS are net (ex VAT). Click a row&rsquo;s &#9654; to expand its sales.</p>';
		} else {
			$pt_pages = (int) max( 1, ceil( $pt_total / $pt_per_page ) );
			$pt_batch = isset( $_GET['batch'] ) ? max( 1, min( $pt_pages, (int) $_GET['batch'] ) ) : 1;
			$pt_slice = array_slice( $pt_all_ids, ( $pt_batch - 1 ) * $pt_per_page, $pt_per_page );
			echo '<p style="color:#666;margin:0 0 14px;">Per-product price movement. Real orders only (completed/processing/on-hold/refunded); prices are per unit <strong>inc VAT</strong>. Margin and COGS are net (ex VAT), since the VAT you collect isn&rsquo;t profit. Showing <strong>batch ' . (int) $pt_batch . ' of ' . (int) $pt_pages . '</strong> (' . count( $pt_slice ) . ' products). Click a row&rsquo;s &#9654; to expand its every sale inline (order, date, List/Sold, discount, coupon) — loaded on demand, so nothing extra runs until you click.</p>';
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
		foreach ( array( 'Product ID', 'Product', 'Parent product', 'Units', 'Orders', 'First £ (date)', 'Last £ (date)', 'Lowest £', 'Highest £', 'Change £', 'Change %', 'Listing £', 'Suggested £', 'COGS £' ) as $h ) {
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
				echo '<td style="padding:6px 10px;color:#bbb;">—</td>'; // Suggested £ (no sales → no signal)
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
			// Suggested £ = the price that earned the most revenue in the window
			// (same pick as the drill-down), with a delta vs the current listing.
			$sug_row = isset( $a['suggested'] ) ? (float) $a['suggested'] : 0.0;
			if ( $sug_row > 0 ) {
				$sug_sub = '';
				if ( $listing_row > 0 ) {
					$sug_d = $sug_row - $listing_row;
					if ( abs( $sug_d ) < 0.005 ) {
						$sug_sub = '<div style="color:#888;font-size:11px;">= listing</div>';
					} else {
						$sug_sub = '<div style="color:' . ( $sug_d > 0 ? '#0a7d28' : '#b00' ) . ';font-size:11px;">vs listing ' . ( $sug_d > 0 ? '+£' : '−£' ) . esc_html( number_format( abs( $sug_d ), 2 ) ) . '</div>';
					}
				}
				echo '<td style="padding:6px 10px;font-weight:700;color:#06507a;">£' . esc_html( number_format( $sug_row, 2 ) ) . $sug_sub . '</td>';
			} else {
				echo '<td style="padding:6px 10px;color:#bbb;">—</td>';
			}
			$cogs_row = pt_audit_product_cogs( $pid );
			if ( $cogs_row > 0 ) {
				$m_row  = (float) $a['last_sold_net'] - $cogs_row; // net margin at last sold price
				$mp_row = ( (float) $a['last_sold_net'] > 0 ) ? ( $m_row / (float) $a['last_sold_net'] * 100 ) : 0.0;
				echo '<td style="padding:6px 10px;color:#555;">£' . esc_html( number_format( $cogs_row, 2 ) ) . '<div style="color:#888;font-size:11px;">net margin £' . esc_html( number_format( $m_row, 2 ) ) . ' (' . esc_html( number_format( $mp_row, 1 ) ) . '%)</div></td>';
			} else {
				echo '<td style="padding:6px 10px;color:#bbb;">—</td>';
			}
			echo '</tr>';
			echo '<tr class="pt-detail-row" data-for="' . (int) $pid . '" hidden><td colspan="14" style="padding:0 10px 12px 34px;background:#fafafa;"><div class="pt-audit-detail"></div></td></tr>';
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
		echo '<td style="padding:9px 10px;"></td>'; // Suggested £
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
