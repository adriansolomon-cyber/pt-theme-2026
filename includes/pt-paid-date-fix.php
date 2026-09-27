<?php
/**
 * Admin tool: fix orders whose `date_paid` was wrongly backfilled to a bulk-edit day.
 *
 * When old orders that had an EMPTY `date_paid` are bulk-marked completed/processing,
 * WooCommerce stamps `date_paid = now`. With Analytics set to "Date paid", those old
 * orders then pile onto that day (the "fake pump"). This page lists exactly those
 * orders — `date_paid` on the target day but `date_created` earlier — and lets an
 * admin set each one's payment date back to its REAL date (the "→ Processing" order
 * note time, else the order creation date), one click per order.
 *
 * Admin-only (manage_woocommerce). One-time cleanup — safe to remove after use.
 * WooCommerce → Paid-date fix.  Override the day with ?pt_pdf_day=YYYY-MM-DD.
 *
 * @package pt-theme-2026
 */

defined( 'ABSPATH' ) || exit;

/** The day the bulk edit stamped onto date_paid. Filter or ?pt_pdf_day= to change. */
function pt_pdf_target_day() {
	$day = isset( $_GET['pt_pdf_day'] ) ? sanitize_text_field( wp_unslash( $_GET['pt_pdf_day'] ) ) : '2026-09-25';
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $day ) ) {
		$day = '2026-09-25';
	}
	return (string) apply_filters( 'pt_paid_date_fix_day', $day );
}

/** Affected orders: date_paid within the target day, but created before it. */
function pt_pdf_get_affected_orders( $limit = 1000 ) {
	$day   = pt_pdf_target_day();
	$start = strtotime( $day . ' 00:00:00' );
	$end   = strtotime( $day . ' 23:59:59' );

	$orders = wc_get_orders( array(
		'limit'     => $limit,
		'type'      => 'shop_order',
		'date_paid' => $start . '...' . $end,
		'orderby'   => 'date',
		'order'     => 'ASC',
		'return'    => 'objects',
	) );

	$out = array();
	foreach ( (array) $orders as $o ) {
		if ( ! $o instanceof WC_Order ) {
			continue;
		}
		$created = $o->get_date_created();
		$paid    = $o->get_date_paid();
		if ( ! $created || ! $paid ) {
			continue;
		}
		// Only genuinely-old orders (created before the target day).
		if ( $created->getTimestamp() >= $start ) {
			continue;
		}
		$out[] = $o;
	}
	return $out;
}

/**
 * The REAL payment date for an order: the earliest "changed … to Processing" order
 * note (the true payment moment), falling back to the order's creation date.
 *
 * @return WC_DateTime|null
 */
function pt_pdf_real_paid_date( $order ) {
	$notes = wc_get_order_notes( array(
		'order_id' => $order->get_id(),
		'orderby'  => 'date_created',
		'order'    => 'ASC',
	) );
	foreach ( (array) $notes as $n ) {
		if ( isset( $n->content ) && stripos( $n->content, 'to Processing' ) !== false ) {
			if ( isset( $n->date_created ) && is_a( $n->date_created, 'WC_DateTime' ) ) {
				return $n->date_created;
			}
			if ( ! empty( $n->date_created ) ) {
				$ts = strtotime( (string) $n->date_created );
				if ( $ts ) {
					return new WC_DateTime( "@$ts" );
				}
			}
		}
	}
	return $order->get_date_created() ?: null;
}

/** Register the admin page under WooCommerce. */
add_action( 'admin_menu', function () {
	add_submenu_page(
		'woocommerce',
		'Paid-date fix',
		'Paid-date fix',
		'manage_woocommerce',
		'pt-paid-date-fix',
		'pt_pdf_render_page'
	);
} );

/** Render the review table. */
function pt_pdf_render_page() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}
	$day     = pt_pdf_target_day();
	$orders  = pt_pdf_get_affected_orders();
	$nonce   = wp_create_nonce( 'pt_pdf' );
	$tz_note = wc_timezone_string();
	?>
	<div class="wrap">
		<h1>Paid-date fix</h1>
		<p style="max-width:820px;">
			Orders whose <strong>payment date</strong> is <code><?php echo esc_html( $day ); ?></code>
			but that were <strong>created earlier</strong> — i.e. old orders whose <code>date_paid</code>
			got backfilled to that day by a bulk status change. Setting the payment date back to the
			real date removes them from that day's Analytics (which reports on <em>Date paid</em>).
			Timezone: <code><?php echo esc_html( $tz_note ); ?></code>.
			<br><strong>Back up the database before making changes.</strong>
		</p>
		<p><strong><?php echo count( $orders ); ?></strong> order(s) affected on <?php echo esc_html( $day ); ?>.</p>

		<table class="widefat striped" style="max-width:1100px;">
			<thead>
				<tr>
					<th>Order</th>
					<th>Status</th>
					<th>Created</th>
					<th>Current payment date</th>
					<th>Real payment date</th>
					<th>Total</th>
					<th style="width:180px;">Action</th>
				</tr>
			</thead>
			<tbody>
			<?php if ( ! $orders ) : ?>
				<tr><td colspan="7">No affected orders found for <?php echo esc_html( $day ); ?>.</td></tr>
			<?php endif; ?>
			<?php foreach ( $orders as $o ) :
				$id       = $o->get_id();
				$created  = $o->get_date_created();
				$paid     = $o->get_date_paid();
				$real     = pt_pdf_real_paid_date( $o );
				$fmt      = 'd/m/Y H:i';
				?>
				<tr id="pt-pdf-row-<?php echo esc_attr( $id ); ?>">
					<td><a href="<?php echo esc_url( admin_url( 'post.php?post=' . $id . '&action=edit' ) ); ?>" target="_blank">#<?php echo esc_html( $o->get_order_number() ); ?></a></td>
					<td><?php echo esc_html( wc_get_order_status_name( $o->get_status() ) ); ?></td>
					<td><?php echo $created ? esc_html( $created->date_i18n( $fmt ) ) : '—'; ?></td>
					<td class="pt-pdf-current" style="color:#b32d2e;font-weight:600;"><?php echo $paid ? esc_html( $paid->date_i18n( $fmt ) ) : '—'; ?></td>
					<td class="pt-pdf-real" style="color:#1a7a1a;font-weight:600;"><?php echo $real ? esc_html( $real->date_i18n( $fmt ) ) : '—'; ?></td>
					<td><?php echo wp_kses_post( $o->get_formatted_order_total() ); ?></td>
					<td>
						<button type="button" class="button button-primary pt-pdf-fix"
							data-order="<?php echo esc_attr( $id ); ?>"
							<?php echo $real ? '' : 'disabled'; ?>>
							Set real payment date
						</button>
						<span class="pt-pdf-msg" style="margin-left:6px;"></span>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>

	<script>
	(function(){
		var nonce = <?php echo wp_json_encode( $nonce ); ?>;
		document.querySelectorAll('.pt-pdf-fix').forEach(function(btn){
			btn.addEventListener('click', function(){
				var id = btn.getAttribute('data-order');
				var row = document.getElementById('pt-pdf-row-' + id);
				var msg = row ? row.querySelector('.pt-pdf-msg') : null;
				if ( ! window.confirm('Set order #' + id + ' payment date to its real date?') ) return;
				btn.disabled = true;
				if (msg) msg.textContent = 'Saving…';
				var body = new URLSearchParams();
				body.set('action', 'pt_pdf_fix');
				body.set('nonce', nonce);
				body.set('order_id', id);
				fetch(ajaxurl, { method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: body.toString() })
					.then(function(r){ return r.json(); })
					.then(function(res){
						if (res && res.success) {
							var cur = row ? row.querySelector('.pt-pdf-current') : null;
							if (cur) { cur.textContent = res.data.new_paid; cur.style.color = '#1a7a1a'; }
							if (msg) msg.textContent = '✔ Fixed';
							btn.textContent = 'Done';
						} else {
							if (msg) msg.textContent = '✖ ' + ((res && res.data && res.data.msg) || 'Failed');
							btn.disabled = false;
						}
					})
					.catch(function(){ if (msg) msg.textContent = '✖ Error'; btn.disabled = false; });
			});
		});
	})();
	</script>
	<?php
}

/** Apply the fix for one order (server-side recompute — never trust a client date). */
add_action( 'wp_ajax_pt_pdf_fix', function () {
	check_ajax_referer( 'pt_pdf', 'nonce' );
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_send_json_error( array( 'msg' => 'Permission denied.' ) );
	}
	$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
	$order    = $order_id ? wc_get_order( $order_id ) : null;
	if ( ! $order ) {
		wp_send_json_error( array( 'msg' => 'Order not found.' ) );
	}

	$real = pt_pdf_real_paid_date( $order );
	if ( ! $real ) {
		wp_send_json_error( array( 'msg' => 'No real date resolvable.' ) );
	}

	$order->set_date_paid( $real->getTimestamp() ); // WC_DateTime timestamp is UTC — correct.
	$order->add_order_note( 'Payment date corrected from bulk-edit day to ' . $real->date_i18n( 'd/m/Y H:i' ) . ' (pt-paid-date-fix).', false, false );
	$order->save();

	// Re-sync this order's Analytics stats row immediately.
	if ( class_exists( '\Automattic\WooCommerce\Admin\Schedulers\OrdersScheduler' ) ) {
		\Automattic\WooCommerce\Admin\Schedulers\OrdersScheduler::import( $order_id );
	}

	$paid = $order->get_date_paid();
	wp_send_json_success( array(
		'order_id' => $order_id,
		'new_paid' => $paid ? $paid->date_i18n( 'd/m/Y H:i' ) : '',
	) );
} );
