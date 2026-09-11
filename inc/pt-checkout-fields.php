<?php
/**
 * Custom checkout fields — Phone 2 + Delivery Instructions.
 *
 * Recreates the two custom billing fields previously provided by the Checkout
 * Field Editor plugin (now deactivated), preserving their meta keys so existing
 * order data still reads/writes consistently:
 *   - billing_phone_2      → order meta `_billing_phone_2`      (Phone 2)
 *   - special_instructions → order meta `_special_instructions` (Delivery Instructions)
 *
 * Registering them in the theme gives full control of their placement to match
 * the checkout design (Phone 2 inline beside Phone in Contact; Delivery
 * Instructions in the Delivery section). WooCommerce's native order-notes field
 * is removed so there is no duplicate instructions box.
 *
 * @package pt-theme-2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the two custom fields; pair Phone / Phone 2 as two columns.
 * Priority 30 → runs AFTER the theme's field-layout filter (priority 20).
 *
 * @param array $fields Checkout fields.
 * @return array
 */
function pt_register_custom_checkout_fields( $fields ) {
	// Phone becomes the left column; Phone 2 the right column (Contact block).
	if ( isset( $fields['billing']['billing_phone'] ) ) {
		$fields['billing']['billing_phone']['class'] = array( 'form-row-first' );
		$fields['billing']['billing_phone']['clear'] = false;
	}
	$fields['billing']['billing_phone_2'] = array(
		'type'         => 'tel',
		'label'        => __( 'Phone 2', 'woocommerce' ),
		'placeholder'  => __( 'Additional phone number', 'woocommerce' ),
		'required'     => false,
		'class'        => array( 'form-row-last' ),
		'clear'        => true,
		'priority'     => 105,
		'autocomplete' => 'tel',
	);

	// Delivery Instructions replaces the native order-notes box (removed here) so
	// there is only one instructions field, shown in the Delivery section.
	unset( $fields['order']['order_comments'] );
	$fields['order']['special_instructions'] = array(
		'type'        => 'textarea',
		'label'       => __( 'Special instructions', 'woocommerce' ),
		'placeholder' => __( 'Curbside access notes — e.g. parking, narrow road, or where to set down the delivery…', 'woocommerce' ),
		'required'    => false,
		'class'       => array( 'form-row-wide', 'notes' ),
		'priority'    => 10,
	);

	return $fields;
}
add_filter( 'woocommerce_checkout_fields', 'pt_register_custom_checkout_fields', 30 );

/**
 * Persist the custom fields to the same order meta keys the editor used.
 *
 * @param int $order_id Order ID.
 */
function pt_save_custom_checkout_fields( $order_id ) {
	// WooCommerce verifies the checkout nonce before this fires.
	// phpcs:disable WordPress.Security.NonceVerification.Missing
	if ( isset( $_POST['billing_phone_2'] ) ) {
		update_post_meta( $order_id, '_billing_phone_2', sanitize_text_field( wp_unslash( $_POST['billing_phone_2'] ) ) );
	}
	if ( isset( $_POST['special_instructions'] ) ) {
		update_post_meta( $order_id, '_special_instructions', sanitize_textarea_field( wp_unslash( $_POST['special_instructions'] ) ) );
	}
	// phpcs:enable WordPress.Security.NonceVerification.Missing
}
add_action( 'woocommerce_checkout_update_order_meta', 'pt_save_custom_checkout_fields' );

/**
 * Read a custom value, falling back to the no-underscore key (older editor data).
 *
 * @param WC_Order|int $order Order or ID.
 * @param string       $key   Base meta key (without leading underscore).
 * @return string
 */
function pt_get_order_custom_meta( $order, $key ) {
	$id  = is_a( $order, 'WC_Order' ) ? $order->get_id() : (int) $order;
	$val = get_post_meta( $id, '_' . $key, true );
	if ( '' === $val ) {
		$val = get_post_meta( $id, $key, true );
	}
	return (string) $val;
}

/**
 * Show both fields on the admin order screen, under the billing address.
 *
 * @param WC_Order $order Order.
 */
function pt_admin_show_custom_checkout_fields( $order ) {
	$phone2 = pt_get_order_custom_meta( $order, 'billing_phone_2' );
	$notes  = pt_get_order_custom_meta( $order, 'special_instructions' );
	if ( '' !== $phone2 ) {
		echo '<p><strong>' . esc_html__( 'Phone 2', 'woocommerce' ) . ':</strong> ' . esc_html( $phone2 ) . '</p>';
	}
	if ( '' !== $notes ) {
		echo '<p><strong>' . esc_html__( 'Delivery Instructions', 'woocommerce' ) . ':</strong><br>' . nl2br( esc_html( $notes ) ) . '</p>';
	}
}
add_action( 'woocommerce_admin_order_data_after_billing_address', 'pt_admin_show_custom_checkout_fields' );

/**
 * Include both fields in order emails.
 *
 * @param WC_Order $order         Order.
 * @param bool     $sent_to_admin Sent to admin.
 * @param bool     $plain_text    Plain-text email.
 * @param WC_Email $email         Email object.
 */
function pt_email_show_custom_checkout_fields( $order, $sent_to_admin, $plain_text, $email ) {
	$phone2 = pt_get_order_custom_meta( $order, 'billing_phone_2' );
	$notes  = pt_get_order_custom_meta( $order, 'special_instructions' );
	if ( '' === $phone2 && '' === $notes ) {
		return;
	}
	if ( $plain_text ) {
		if ( '' !== $phone2 ) {
			echo "\n" . esc_html__( 'Phone 2', 'woocommerce' ) . ': ' . esc_html( $phone2 ) . "\n";
		}
		if ( '' !== $notes ) {
			echo esc_html__( 'Delivery Instructions', 'woocommerce' ) . ': ' . esc_html( $notes ) . "\n";
		}
		return;
	}
	echo '<div style="margin-bottom:20px">';
	if ( '' !== $phone2 ) {
		echo '<p><strong>' . esc_html__( 'Phone 2', 'woocommerce' ) . ':</strong> ' . esc_html( $phone2 ) . '</p>';
	}
	if ( '' !== $notes ) {
		echo '<p><strong>' . esc_html__( 'Delivery Instructions', 'woocommerce' ) . ':</strong><br>' . nl2br( esc_html( $notes ) ) . '</p>';
	}
	echo '</div>';
}
add_action( 'woocommerce_email_after_order_table', 'pt_email_show_custom_checkout_fields', 20, 4 );
