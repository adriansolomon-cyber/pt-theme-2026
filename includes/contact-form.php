<?php
/**
 * Contact-page enquiry form handler.
 *
 * The form (templates/page-contact.php) posts to admin-post.php with action
 * "pt_contact". Works without JavaScript (redirects back with a status flag);
 * assets/js/contact.js progressively enhances it to submit via fetch and show
 * the inline "Thanks" confirmation without a reload.
 *
 * Enquiries are emailed to PT_CONTACT_TO with Reply-To set to the enquirer.
 * Spam is filtered with a nonce + a honeypot field. Nothing is stored.
 *
 * @package pt-theme-2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'PT_CONTACT_TO' ) ) {
	define( 'PT_CONTACT_TO', 'sales@projecttimber.co.uk' );
}

/**
 * Fire a Meta "Lead" for a successful enquiry/callback.
 *
 * Sends the server-side (CAPI) copy immediately via the FB CAPI plugin, and returns a
 * payload so the browser can fire the deduplicated Pixel copy with the SAME event_id
 * (the form JS reads it from the AJAX response). No-ops cleanly if the plugin is
 * inactive. Consent is handled inside the plugin: without marketing consent the CAPI
 * copy sends IP/UA only and the browser copy is held by fbq('consent','revoke').
 *
 * @param string $lead_type 'contact' | 'callback'.
 * @param array  $c         Contact fields: email, phone, first_name, last_name.
 * @return array ['event_id'=>string,'custom_data'=>array] for the browser, or [] if unavailable.
 */
function pt_fire_lead_event( $lead_type, $c ) {
	if ( ! function_exists( 'fb_capi_send_event' ) || ! function_exists( 'fb_capi_get_event_id' ) ) {
		return array();
	}

	// Persist contact so the plugin hashes name too (and benefits later events).
	if ( function_exists( 'fb_capi_store_session_contact' ) ) {
		fb_capi_store_session_contact( array(
			'email'      => $c['email'] ?? '',
			'phone'      => $c['phone'] ?? '',
			'first_name' => $c['first_name'] ?? '',
			'last_name'  => $c['last_name'] ?? '',
		) );
	}

	$event_id    = fb_capi_get_event_id( 'Lead', $lead_type . '_' . microtime( true ) );
	$custom_data = array(
		'content_name'     => $lead_type,
		'content_category' => 'lead',
		'currency'         => 'GBP',
	);

	// Estimated lead value — OFF by default (an un-qualified enquiry has no revenue yet).
	// Set one with: add_filter( 'pt_lead_value', fn( $v, $type ) => 250.0, 10, 2 );
	$value = (float) apply_filters( 'pt_lead_value', 0, $lead_type );
	if ( $value > 0 ) {
		$custom_data['value'] = $value;
	}

	fb_capi_send_event( 'Lead', $custom_data, $event_id, $c['email'] ?? '', $c['phone'] ?? '' );

	return array(
		'event_id'    => $event_id,
		'custom_data' => $custom_data,
	);
}

/**
 * Send the JSON response (AJAX) or redirect back with a status flag (no-JS).
 *
 * @param bool   $ajax    Whether this is an AJAX request.
 * @param bool   $ok      Success.
 * @param string $message Message shown to the user on error (or empty).
 */
function pt_contact_respond( $ajax, $ok, $message = '', $data = null ) {
	if ( $ajax ) {
		if ( $ok ) {
			wp_send_json_success( $data );
		}
		wp_send_json_error( array( 'message' => $message ) );
	}

	$back = wp_get_referer() ? wp_get_referer() : home_url( '/contact/' );
	$back = remove_query_arg( array( 'pt_contact', 'pt_msg' ), $back );
	$back = add_query_arg( 'pt_contact', $ok ? 'sent' : 'err', $back );
	if ( ! $ok && '' !== $message ) {
		$back = add_query_arg( 'pt_msg', rawurlencode( $message ), $back );
	}
	wp_safe_redirect( $back . '#cForm' );
	exit;
}

/**
 * Validate + process a Contact form submission.
 */
function pt_contact_handle() {
	$ajax = ! empty( $_POST['pt_ajax'] );

	// CSRF.
	$nonce = isset( $_POST['pt_contact_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['pt_contact_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'pt_contact' ) ) {
		pt_contact_respond( $ajax, false, 'Security check failed — please reload the page and try again.' );
	}

	// Honeypot: a real user leaves this hidden field empty. Pretend success.
	if ( ! empty( $_POST['pt_website'] ) ) {
		pt_contact_respond( $ajax, true );
	}

	$name  = isset( $_POST['cName'] ) ? sanitize_text_field( wp_unslash( $_POST['cName'] ) ) : '';
	$email = isset( $_POST['cEmail'] ) ? sanitize_email( wp_unslash( $_POST['cEmail'] ) ) : '';
	$phone = isset( $_POST['cPhone'] ) ? sanitize_text_field( wp_unslash( $_POST['cPhone'] ) ) : '';
	$prod  = isset( $_POST['cProd'] ) ? sanitize_text_field( wp_unslash( $_POST['cProd'] ) ) : '';
	$msg   = isset( $_POST['cMsg'] ) ? sanitize_textarea_field( wp_unslash( $_POST['cMsg'] ) ) : '';
	$time  = isset( $_POST['cTime'] ) ? sanitize_text_field( wp_unslash( $_POST['cTime'] ) ) : '';

	if ( '' === $name || '' === $email || ! is_email( $email ) ) {
		pt_contact_respond( $ajax, false, 'Please enter your name and a valid email address.' );
	}

	$time_labels = array(
		'asap' => 'As soon as possible',
		'am'   => 'Any weekday, 8:30am – 1pm',
		'pm'   => 'Any weekday afternoon, 1pm – 5pm',
		'eve'  => 'Any weekday evening, 5pm – 7pm',
	);
	$time_label = isset( $time_labels[ $time ] ) ? $time_labels[ $time ] : '';

	$lines = array(
		'Name: ' . $name,
		'Email: ' . $email,
		'Phone: ' . ( '' !== $phone ? $phone : '—' ),
		'Product of interest: ' . ( '' !== $prod ? $prod : '—' ),
		'Best time to call back: ' . ( '' !== $time_label ? $time_label : '—' ),
		'',
		'Message:',
		'' !== $msg ? $msg : '(no message)',
		'',
		'—',
		'Sent from the website contact form (' . home_url( '/contact/' ) . ').',
	);

	$subject = 'Website enquiry — ' . $name;
	$headers = array(
		'Content-Type: text/plain; charset=UTF-8',
		'Reply-To: ' . $name . ' <' . $email . '>',
	);

	// Capture the underlying transport error (shown to admins only, for diagnostics).
	$mail_error = '';
	add_action(
		'wp_mail_failed',
		function ( $err ) use ( &$mail_error ) {
			$mail_error = is_wp_error( $err ) ? $err->get_error_message() : 'unknown error';
		}
	);

	$sent = wp_mail( PT_CONTACT_TO, $subject, implode( "\n", $lines ), $headers );

	if ( ! $sent ) {
		$msg = "Sorry — we couldn't send your message. Please call us on 01777 801214.";
		// Surface the real reason to logged-in admins only (safe for visitors).
		if ( '' !== $mail_error && current_user_can( 'manage_options' ) ) {
			$msg .= ' [admin debug: ' . $mail_error . ']';
		}
		pt_contact_respond( $ajax, false, $msg );
	}

	// Genuine enquiry (nonce + honeypot + validation passed, email sent) → Meta Lead.
	$np   = preg_split( '/\s+/', trim( $name ), 2 );
	$lead = pt_fire_lead_event( 'contact', array(
		'email'      => $email,
		'phone'      => $phone,
		'first_name' => $np[0] ?? '',
		'last_name'  => $np[1] ?? '',
	) );

	pt_contact_respond( $ajax, true, '', $lead ? array( 'lead' => $lead ) : null );
}
add_action( 'admin_post_pt_contact', 'pt_contact_handle' );
add_action( 'admin_post_nopriv_pt_contact', 'pt_contact_handle' );

/**
 * Validate + process a "Request a callback" submission (global support-widget
 * modal, template-parts/callback-modal.php). Name + phone required; there is no
 * email field, so no Reply-To. Emails PT_CONTACT_TO. Nonce + honeypot guarded.
 */
function pt_callback_handle() {
	$ajax = ! empty( $_POST['pt_ajax'] );

	$respond = function ( $ok, $message = '', $data = null ) use ( $ajax ) {
		if ( $ajax ) {
			if ( $ok ) {
				wp_send_json_success( $data );
			}
			wp_send_json_error( array( 'message' => $message ) );
		}
		$back = wp_get_referer() ? wp_get_referer() : home_url( '/' );
		$back = remove_query_arg( array( 'pt_callback', 'pt_msg' ), $back );
		$back = add_query_arg( 'pt_callback', $ok ? 'sent' : 'err', $back );
		if ( ! $ok && '' !== $message ) {
			$back = add_query_arg( 'pt_msg', rawurlencode( $message ), $back );
		}
		wp_safe_redirect( $back );
		exit;
	};

	$nonce = isset( $_POST['pt_callback_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['pt_callback_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'pt_callback' ) ) {
		$respond( false, 'Security check failed — please reload the page and try again.' );
	}

	// Honeypot.
	if ( ! empty( $_POST['pt_website'] ) ) {
		$respond( true );
	}

	$name  = isset( $_POST['pcbName'] ) ? sanitize_text_field( wp_unslash( $_POST['pcbName'] ) ) : '';
	$phone = isset( $_POST['pcbPhone'] ) ? sanitize_text_field( wp_unslash( $_POST['pcbPhone'] ) ) : '';
	$prod  = isset( $_POST['pcbProd'] ) ? sanitize_text_field( wp_unslash( $_POST['pcbProd'] ) ) : '';
	$time  = isset( $_POST['pcbTime'] ) ? sanitize_text_field( wp_unslash( $_POST['pcbTime'] ) ) : '';

	if ( '' === $name || '' === $phone ) {
		$respond( false, 'Please enter your name and a contact number.' );
	}

	$time_labels = array(
		'asap' => 'As soon as possible',
		'am'   => 'Any weekday, 8:30am – 1pm',
		'pm'   => 'Any weekday afternoon, 1pm – 5pm',
		'eve'  => 'Any weekday evening, 5pm – 7pm',
	);
	$time_label = isset( $time_labels[ $time ] ) ? $time_labels[ $time ] : '';
	$source     = isset( $_POST['pt_source'] ) ? esc_url_raw( wp_unslash( $_POST['pt_source'] ) ) : home_url( '/' );

	$lines = array(
		'Name: ' . $name,
		'Phone: ' . $phone,
		'Product of interest: ' . ( '' !== $prod ? $prod : '—' ),
		'Best time to call back: ' . ( '' !== $time_label ? $time_label : '—' ),
		'',
		'—',
		'Callback request from the website support widget.',
		'Page: ' . $source,
	);

	$subject = 'Callback request — ' . $name;
	$headers = array( 'Content-Type: text/plain; charset=UTF-8' );

	$mail_error = '';
	add_action(
		'wp_mail_failed',
		function ( $err ) use ( &$mail_error ) {
			$mail_error = is_wp_error( $err ) ? $err->get_error_message() : 'unknown error';
		}
	);

	$sent = wp_mail( PT_CONTACT_TO, $subject, implode( "\n", $lines ), $headers );

	if ( ! $sent ) {
		$err = "Sorry — we couldn't send your request. Please call us on 01777 553392.";
		if ( '' !== $mail_error && current_user_can( 'manage_options' ) ) {
			$err .= ' [admin debug: ' . $mail_error . ']';
		}
		$respond( false, $err );
	}

	// Genuine callback request (validation passed, email sent) → Meta Lead. No email field.
	$np   = preg_split( '/\s+/', trim( $name ), 2 );
	$lead = pt_fire_lead_event( 'callback', array(
		'phone'      => $phone,
		'first_name' => $np[0] ?? '',
		'last_name'  => $np[1] ?? '',
	) );

	$respond( true, '', $lead ? array( 'lead' => $lead ) : null );
}
add_action( 'admin_post_pt_callback', 'pt_callback_handle' );
add_action( 'admin_post_nopriv_pt_callback', 'pt_callback_handle' );

/**
 * Validate + process a "Book a showsite visit" submission (homepage booking
 * modal, front-page.php). The date + time are chosen in a JS calendar, so this
 * form is submitted via fetch only. Name + email required; the chosen slot
 * (ssDate/ssTime) is passed as hidden fields. Emails PT_CONTACT_TO with
 * Reply-To set to the visitor. Nonce + honeypot guarded.
 */
function pt_showsite_handle() {
	$ajax = ! empty( $_POST['pt_ajax'] );

	$respond = function ( $ok, $message = '' ) use ( $ajax ) {
		if ( $ajax ) {
			if ( $ok ) {
				wp_send_json_success();
			}
			wp_send_json_error( array( 'message' => $message ) );
		}
		$back = wp_get_referer() ? wp_get_referer() : home_url( '/' );
		$back = remove_query_arg( array( 'pt_showsite', 'pt_msg' ), $back );
		$back = add_query_arg( 'pt_showsite', $ok ? 'sent' : 'err', $back );
		if ( ! $ok && '' !== $message ) {
			$back = add_query_arg( 'pt_msg', rawurlencode( $message ), $back );
		}
		wp_safe_redirect( $back );
		exit;
	};

	$nonce = isset( $_POST['pt_showsite_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['pt_showsite_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'pt_showsite' ) ) {
		$respond( false, 'Security check failed — please reload the page and try again.' );
	}

	// Honeypot.
	if ( ! empty( $_POST['pt_website'] ) ) {
		$respond( true );
	}

	$name  = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
	$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
	$date  = isset( $_POST['ssDate'] ) ? sanitize_text_field( wp_unslash( $_POST['ssDate'] ) ) : '';
	$time  = isset( $_POST['ssTime'] ) ? sanitize_text_field( wp_unslash( $_POST['ssTime'] ) ) : '';

	if ( '' === $name || '' === $email || ! is_email( $email ) ) {
		$respond( false, 'Please enter your name and a valid email address.' );
	}
	if ( '' === $date || '' === $time ) {
		$respond( false, 'Please choose a date and time for your visit.' );
	}

	$source = isset( $_POST['pt_source'] ) ? esc_url_raw( wp_unslash( $_POST['pt_source'] ) ) : home_url( '/' );

	$lines = array(
		'Name: ' . $name,
		'Email: ' . $email,
		'Phone: ' . ( '' !== $phone ? $phone : '—' ),
		'',
		'Requested visit: ' . $date . ' at ' . $time,
		'',
		'—',
		'Showsite visit request from the website booking form.',
		'Page: ' . $source,
	);

	$subject = 'Showsite visit request — ' . $name;
	$headers = array(
		'Content-Type: text/plain; charset=UTF-8',
		'Reply-To: ' . $name . ' <' . $email . '>',
	);

	$mail_error = '';
	add_action(
		'wp_mail_failed',
		function ( $err ) use ( &$mail_error ) {
			$mail_error = is_wp_error( $err ) ? $err->get_error_message() : 'unknown error';
		}
	);

	$sent = wp_mail( PT_CONTACT_TO, $subject, implode( "\n", $lines ), $headers );

	if ( ! $sent ) {
		$err = "Sorry — we couldn't send your booking request. Please call us on 01777 553392.";
		if ( '' !== $mail_error && current_user_can( 'manage_options' ) ) {
			$err .= ' [admin debug: ' . $mail_error . ']';
		}
		$respond( false, $err );
	}

	$respond( true );
}
add_action( 'admin_post_pt_showsite', 'pt_showsite_handle' );
add_action( 'admin_post_nopriv_pt_showsite', 'pt_showsite_handle' );
