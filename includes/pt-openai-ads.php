<?php
/**
 * PT — ChatGPT Ads (OpenAI) pixel + Conversions API.
 * =============================================================================
 * Self-contained, toggleable tracking module for OpenAI's ad surface inside
 * ChatGPT. Mirrors the dual browser+server pattern we use for Facebook: a
 * browser pixel and a server-to-server Conversions API (CAPI) call that dedupe
 * against each other via a shared, stable-per-order event id.
 *
 *   • Browser pixel (wp_head): loads oaiq.min.js, init()s with the Pixel ID,
 *     fires `page_viewed` site-wide, and `order_created` on the thank-you page.
 *   • CAPI (woocommerce_thankyou, server): POSTs the same `order_created` to
 *     https://bzr.openai.com/v1/events with the Bearer token — sent once per
 *     order, dedup-guarded, and echoing OpenAI's `oppref` attribution token.
 *
 * SECRETS / PUBLIC REPO:
 *   The Pixel ID is public (it ships to every browser) and lives in this file.
 *   The CAPI Bearer token is a SECRET and this repo is public, so it is NEVER
 *   stored here. Define it in wp-config.php (outside the repo):
 *
 *       define( 'PT_OPENAI_CAPI_TOKEN', 'sk-svcacct-…' );
 *
 *   If the constant is absent the browser pixel still fires and only the
 *   server-side CAPI call no-ops (logged when debug is on).
 *
 * Debug: define('PT_OPENAI_ADS_DEBUG', true) to log SDK activity in the browser
 * console (debug:true on init) and CAPI request/response to the PHP error log.
 *
 * Disable: comment its require in functions.php, or define PT_OPENAI_ADS=false.
 *
 * @package pt-theme-2026
 */

defined( 'ABSPATH' ) || exit;

if ( defined( 'PT_OPENAI_ADS' ) && ! PT_OPENAI_ADS ) {
	return;
}

// --- Config -----------------------------------------------------------------

/** Public Pixel ID (safe to commit — it ships to every browser). */
function pt_openai_pixel_id() {
	return 'ADpdSMPW8PLJcCpuD6X3hp';
}

/** Secret CAPI Bearer token — read from wp-config.php only, never committed. */
function pt_openai_capi_token() {
	return defined( 'PT_OPENAI_CAPI_TOKEN' ) ? (string) PT_OPENAI_CAPI_TOKEN : '';
}

/** Debug flag — logs to browser console + PHP error log when on. */
function pt_openai_debug() {
	return defined( 'PT_OPENAI_ADS_DEBUG' ) && PT_OPENAI_ADS_DEBUG;
}

// --- Shared helpers ---------------------------------------------------------

/**
 * Stable event id for an order, shared by the pixel and the CAPI call so
 * OpenAI dedupes the two. Generated once and persisted on the order.
 *
 * @param WC_Order $order Order.
 * @return string
 */
function pt_openai_event_id( $order ) {
	$id = (string) $order->get_meta( '_pt_openai_event_id' );
	if ( '' === $id ) {
		$id = 'oai_' . $order->get_id() . '_' . wp_generate_uuid4();
		$order->update_meta_data( '_pt_openai_event_id', $id );
		$order->save();
	}
	return $id;
}

/**
 * Build OpenAI's `order_created` data envelope from a WC order: contents[],
 * total amount in the lowest denomination (pence), and currency.
 *
 * Composite children are folded into their parent line (the parent carries the
 * name; the "W x D" size child supplies the variant name + id), matching how
 * the order summary and our Google purchase event treat composites.
 *
 * @param WC_Order $order Order.
 * @return array
 */
function pt_openai_order_data( $order ) {
	$contents = array();
	$current  = null;

	foreach ( $order->get_items() as $item ) {
		$product = $item->get_product();
		if ( ! $product ) {
			continue;
		}

		if ( 'composite' === $product->get_type() ) {
			if ( $current ) {
				$contents[] = $current;
			}
			$current = array(
				'id'           => (string) $product->get_id(),
				'name'         => $product->get_name(),
				'content_type' => 'product',
				'quantity'     => (int) $item->get_quantity(),
			);
		} elseif ( $current ) {
			// Child of the current composite. Promote the size child to variant.
			if ( preg_match( '/^\d+\s*x\s*\d+$/i', trim( $product->get_name() ) ) ) {
				$current['id']   = (string) $product->get_id();
				$current['name'] = $current['name'] . ' – ' . $product->get_name();
			}
		} else {
			// Standalone simple/bundle line (not part of a composite).
			$contents[] = array(
				'id'           => (string) $product->get_id(),
				'name'         => $product->get_name(),
				'content_type' => 'product',
				'quantity'     => (int) $item->get_quantity(),
			);
		}
	}
	if ( $current ) {
		$contents[] = $current;
	}

	return array(
		'type'     => 'contents',
		'contents' => $contents,
		'amount'   => (int) round( (float) $order->get_total() * 100 ), // pence
		'currency' => $order->get_currency(),
	);
}

// --- oppref capture (attribution) -------------------------------------------
// The pixel drops OpenAI's `oppref` into the first-party `__oppref` cookie on
// landing. Persist it on the order at checkout so the server-side CAPI call can
// echo it back (OpenAI's attribution key) even if the cookie is gone later.
add_action(
	'woocommerce_checkout_order_processed',
	static function ( $order_id ) {
		if ( empty( $_COOKIE['__oppref'] ) ) {
			return;
		}
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}
		$order->update_meta_data( '_pt_openai_oppref', sanitize_text_field( wp_unslash( $_COOKIE['__oppref'] ) ) );
		$order->save();
	},
	10,
	1
);

// --- Browser pixel: base init + page_viewed, site-wide ----------------------
add_action(
	'wp_head',
	static function () {
		$pixel = pt_openai_pixel_id();
		if ( '' === $pixel ) {
			return;
		}
		$debug = pt_openai_debug() ? 'true' : 'false';
		?>
<!-- OpenAI (ChatGPT Ads) pixel -->
<script>
!function(w,d,s,u){if(w.oaiq)return;var q=function(){q.q.push(arguments)};q.q=[];w.oaiq=q;var j=d.createElement(s);j.async=1;j.src=u;var f=d.getElementsByTagName(s)[0];f.parentNode.insertBefore(j,f)}(window,document,"script","https://bzrcdn.openai.com/sdk/oaiq.min.js");
oaiq("init",{pixelId:<?php echo wp_json_encode( $pixel ); ?>,debug:<?php echo $debug; ?>});
oaiq("measure","page_viewed",{type:"contents"});
</script>
<!-- /OpenAI pixel -->
		<?php
	},
	5
);

// --- Thank-you page: pixel order_created + server-side CAPI ------------------
add_action( 'woocommerce_thankyou', 'pt_openai_thankyou_conversion', 20, 1 );

/**
 * Fire the OpenAI `order_created` conversion — browser pixel every load (OpenAI
 * dedupes on the stable event id) and the server-side CAPI exactly once.
 *
 * @param int $order_id Order ID.
 */
function pt_openai_thankyou_conversion( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return;
	}

	$event_id = pt_openai_event_id( $order );
	$data     = pt_openai_order_data( $order );

	// Browser pixel — event id rides in the data object for pixel↔CAPI dedup.
	$pixel_payload         = $data;
	$pixel_payload['event_id'] = $event_id;
	?>
<!-- OpenAI order_created (pixel) -->
<script>
window.oaiq && oaiq("measure","order_created",<?php echo wp_json_encode( $pixel_payload ); ?>);
</script>
<!-- /OpenAI order_created -->
	<?php

	// Server-side CAPI — once per order (own guard; ?pt_retrack=1 re-fires for QA).
	if ( $order->get_meta( '_pt_openai_capi_sent' ) && ! ( function_exists( 'pt_tracking_retrack_bypass' ) && pt_tracking_retrack_bypass() ) ) {
		return;
	}
	pt_openai_send_capi( $order, $event_id, $data );
}

/**
 * POST an `order_created` event to the OpenAI Conversions API.
 *
 * @param WC_Order $order    Order.
 * @param string   $event_id Shared event id (must match the pixel's event_id).
 * @param array    $data     Order data envelope from pt_openai_order_data().
 */
function pt_openai_send_capi( $order, $event_id, $data ) {
	$token = pt_openai_capi_token();
	if ( '' === $token ) {
		if ( pt_openai_debug() ) {
			error_log( 'PT OpenAI CAPI: skipped, PT_OPENAI_CAPI_TOKEN not defined in wp-config.php.' );
		}
		return;
	}

	$oppref = (string) $order->get_meta( '_pt_openai_oppref' );
	if ( '' === $oppref && ! empty( $_COOKIE['__oppref'] ) ) {
		$oppref = sanitize_text_field( wp_unslash( $_COOKIE['__oppref'] ) );
	}

	$event = array(
		'id'            => $event_id,
		'type'          => 'order_created',
		'timestamp_ms'  => (int) round( microtime( true ) * 1000 ),
		'source_url'    => $order->get_checkout_order_received_url(),
		'action_source' => 'web',
		'data'          => $data,
	);
	if ( '' !== $oppref ) {
		$event['oppref'] = $oppref; // OpenAI attribution key from the __oppref cookie.
	}

	$url  = add_query_arg( 'pid', pt_openai_pixel_id(), 'https://bzr.openai.com/v1/events' );
	$body = array(
		'validate_only' => false,
		'events'        => array( $event ),
	);

	$response = wp_remote_post(
		$url,
		array(
			'timeout'  => 5,
			'blocking' => true,
			'headers'  => array(
				'Authorization' => 'Bearer ' . $token,
				'Content-Type'  => 'application/json',
			),
			'body'     => wp_json_encode( $body ),
		)
	);

	if ( is_wp_error( $response ) ) {
		if ( pt_openai_debug() ) {
			error_log( 'PT OpenAI CAPI error: ' . $response->get_error_message() );
		}
		return; // Do not mark sent — allow a later refresh to retry.
	}

	$code = (int) wp_remote_retrieve_response_code( $response );
	if ( pt_openai_debug() ) {
		error_log( 'PT OpenAI CAPI [' . $code . ']: ' . wp_remote_retrieve_body( $response ) );
	}

	// Mark sent only on a 2xx so transient failures can retry on the next load.
	if ( $code >= 200 && $code < 300 ) {
		$order->update_meta_data( '_pt_openai_capi_sent', '1' );
		$order->save();
	}
}
