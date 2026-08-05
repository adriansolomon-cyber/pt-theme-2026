<?php
/**
 * Order-status customer email handlers.
 *
 * Sends templated emails on WooCommerce order-status transitions:
 *   - status → planned / palletways : "Delivery Update"        (inc/custom-emails/delivery-email.php)
 *   - status → completed            : "Assembly Instructions"  (inc/custom-emails/assembly-email.php)
 *
 * Both templates are self-contained: they declare their own `global $wpdb` and
 * gather all their data from `$order`, so each handler only needs a valid
 * `$order` in scope before `require`.
 *
 * The delivery handler was previously the ~470-line inline
 * adjust_planned_order_send_pdf() in legacy-functions.php; its inline HTML was a
 * byte-for-byte copy of delivery-email.php, so it now simply renders that
 * template instead of duplicating it.
 *
 * @package pt-theme-2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render a custom-email template to a string and send it to the order's billing email.
 *
 * @param WC_Order $order         The order (must be a valid object).
 * @param string   $template_slug Template basename in inc/custom-emails/ (without the -email.php suffix).
 * @param string   $subject       Email subject line.
 * @return bool Whether wp_mail() accepted the message.
 */
function pt_send_order_status_email( $order, $template_slug, $subject ) {

	// The templates call date_create( _final_delivery_date )->format() with no
	// guard of their own, so an empty/unparseable delivery date would throw a
	// fatal *inside* the template. Bail (and note the order) before requiring it.
	$raw_date = $order->get_meta( '_final_delivery_date', true );
	if ( empty( $raw_date ) || false === date_create( (string) $raw_date ) ) {
		$order->add_order_note( '🚫 ' . $subject . ' not sent: final delivery date is missing or invalid.', false, true );
		return false;
	}

	$path = get_stylesheet_directory() . '/inc/custom-emails/' . $template_slug . '-email.php';
	if ( ! file_exists( $path ) ) {
		$order->add_order_note( '🚫 ' . $subject . ' not sent: template "' . $template_slug . '" not found.', false, true );
		return false;
	}

	// $order is in scope for the template; the template declares its own global $wpdb.
	ob_start();
	require $path;
	$body = ob_get_clean();

	// A template may bail early via `return`, producing no output. Don't send a
	// blank email or report false success.
	if ( '' === trim( (string) $body ) ) {
		$order->add_order_note( '🚫 ' . $subject . ' not sent: template produced no content.', false, true );
		return false;
	}

	$headers = array(
		'Content-Type: text/html; charset=UTF-8',
		'From: Project Timber <deliveries@projecttimber.co.uk>',
		'Reply-To: <deliveries@projecttimber.co.uk>',
		'Bcc: deliveries@projecttimber.co.uk, samuel.weeks@projecttimber.co.uk, Richard.Charnock@projecttimber.co.uk, LS@projecttimber.co.uk, william.walton@projecttimber.co.uk',
	);

	$sent = wp_mail( $order->get_billing_email(), $subject, $body, $headers );
	$order->add_order_note(
		$sent ? $subject . ' notification sent.' : '🚫 ' . $subject . ' failed to send.',
		false,
		true
	);

	return $sent;
}

/**
 * Delivery-update email — fires when an order enters "planned" or "palletways".
 *
 * (Formerly adjust_planned_order_send_pdf() in legacy-functions.php. Name kept
 * so any external references / documented behaviour stay valid.)
 *
 * @param int $order_id
 */
function adjust_planned_order_send_pdf( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return;
	}

	// Idempotency: send the delivery email only once per order, even though this
	// fires on BOTH the 'planned' and 'palletways' transitions. (The old code had
	// no such guard and could send twice.) Remove this block to restore that.
	if ( $order->get_meta( '_pt_delivery_email_sent', true ) ) {
		return;
	}

	if ( pt_send_order_status_email( $order, 'delivery', "Project Timber's Delivery Update and Assembly Instructions" ) ) {
		$order->update_meta_data( '_pt_delivery_email_sent', current_time( 'mysql' ) );
		$order->save();
	}
}
add_action( 'woocommerce_order_status_planned',    'adjust_planned_order_send_pdf', 10, 1 );
add_action( 'woocommerce_order_status_palletways', 'adjust_planned_order_send_pdf', 10, 1 );

/**
 * Assembly-instructions email — fires when an order is marked "completed".
 *
 * @param int $order_id
 */
function pt_send_assembly_email_on_completed( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return;
	}

	// Idempotency: send the assembly email only once per order, so re-saving or
	// re-completing the order doesn't re-email the customer.
	if ( $order->get_meta( '_pt_assembly_email_sent', true ) ) {
		return;
	}

	if ( pt_send_order_status_email( $order, 'assembly', 'Your Building Assembly Instructions' ) ) {
		$order->update_meta_data( '_pt_assembly_email_sent', current_time( 'mysql' ) );
		$order->save();
	}
}
add_action( 'woocommerce_order_status_completed', 'pt_send_assembly_email_on_completed', 10, 1 );

/* ---------------------------------------------------------------------------
 * Manual/admin "Send Email" feature (order edit screen).
 *
 * pt_add_email_meta_boxes()      registers the "Email" meta box.
 * pt_add_other_fields_for_email() renders the template dropdown + Send button.
 * pt_send_email_template()       (below) handles the submission.
 *
 * The dropdown values here are the source of truth for pt_send_email_template()'s
 * whitelist. (covid-19 is intentionally commented out / disabled in the UI.)
 * ------------------------------------------------------------------------- */

// Adding Meta container admin shop_order pages
add_action('add_meta_boxes', 'pt_add_email_meta_boxes');
if (! function_exists('pt_add_email_meta_boxes')) {
    function pt_add_email_meta_boxes()
    {
        add_meta_box('woocommerce-custom-email', __('Email', 'woocommerce'), 'pt_add_other_fields_for_email', 'shop_order', 'side', 'high');
    }
}

// Adding Meta field in the meta container admin shop_order pages
if (! function_exists('pt_add_other_fields_for_email')) {
    function pt_add_other_fields_for_email()
    {
        global $post;

        echo '<p style="border-bottom:solid 1px #eee;padding-bottom:13px;">
            <select name="order_email_template" id="order_email_template">
                <option value="">Select Templates</option>
                <optgroup label="Send Email">
                    <option value="delivery">Delivery</option>
                    <option value="assembly">Assembly</option>
                    <!--option value="covid-19">Covid-19</option-->
                    <option value="rdm">RDM</option>
                    <option value="trustpilot">Trustpilot</option>
                    <option value="delivery-apologizes">Delivery Apologizes</option>
                </optgroup>
            </select>
            <button type="submit" class="rdm button-primary">Send Email</button></p>';
    }
}

/**
 * Manual/admin email sender.
 *
 * Fires when a shop order is saved. If staff selected a template in the order
 * meta box, render it and email the customer. This is the admin counterpart to
 * the automatic status handlers above and shares the same inc/custom-emails/
 * templates via pt_send_order_status_email().
 *
 * Migrated from legacy-functions.php with three fixes:
 *   1. Template path — the old code required inc/<tpl>-email.php, but the files
 *      live in inc/custom-emails/, so every manual send fatally errored.
 *   2. Local File Inclusion — the old code concatenated the raw $_POST value
 *      straight into a require() path. Now whitelisted.
 *   3. Capability check added (current_user_can('edit_shop_orders')).
 */
function pt_send_email_template() {

	if ( empty( $_POST['order_email_template'] ) ) {
		return;
	}

	if ( ! is_user_logged_in() || ! current_user_can( 'edit_shop_orders' ) ) {
		return;
	}

	// Whitelist: allowed template slug => subject. Closes the LFI risk and
	// guarantees the template file exists (all six live in inc/custom-emails/).
	$allowed = array(
		'delivery'            => "Project Timber's Delivery Update",
		'assembly'            => 'Your Building Assembly Instructions',
		'delivery-apologizes' => "Project Timber's Delivery Update",
		'trustpilot'          => 'Trustpilot',
		'covid-19'            => "Project Timber's Delivery Update",
		'rdm'                 => "Project Timber's Spares Delivery Update",
	);

	$template = sanitize_key( wp_unslash( $_POST['order_email_template'] ) );
	if ( ! isset( $allowed[ $template ] ) ) {
		return;
	}

	$order_id = isset( $_POST['post_ID'] ) ? (int) $_POST['post_ID'] : 0;

	// Persist the submitted delivery date now: the generic save handler runs at a
	// later priority, so the template (which reads it back) would otherwise see a
	// stale/empty value.
	if ( isset( $_POST['_final_delivery_date'] ) ) {
		update_post_meta( $order_id, '_final_delivery_date', sanitize_text_field( wp_unslash( $_POST['_final_delivery_date'] ) ) );
	}

	// Reload so the order object's meta reflects what we just saved (the shared
	// helper's date guard reads $order->get_meta()).
	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return;
	}

	pt_send_order_status_email( $order, $template, $allowed[ $template ] );
}
add_action( 'woocommerce_process_shop_order_meta', 'pt_send_email_template' );
