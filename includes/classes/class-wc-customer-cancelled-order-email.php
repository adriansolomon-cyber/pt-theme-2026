<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WC_Customer_Cancelled_Order_Email extends WC_Email {

    public function __construct() {
        $this->id             = 'customer_cancelled_order';
        $this->title          = 'Customer Cancelled Order';
        $this->description    = 'Cancellation email sent to the customer when an order is cancelled.';
        $this->heading        = 'Your Order Has Been Cancelled';
        $this->subject        = 'Your Order #{order_number} Has Been Cancelled';
        $this->template_html  = 'emails/customer-cancelled-order.php';
        $this->template_plain = 'emails/plain/customer-cancelled-order.php';
        $this->template_base  = get_stylesheet_directory() . '/woocommerce/';
        $this->customer_email = true;

        $this->placeholders = array(
            '{order_number}' => '',
            '{order_date}'   => '',
        );

        add_action( 'woocommerce_order_status_changed', array( $this, 'trigger' ), 10, 3 );

        parent::__construct();
    }

    public function trigger( $order_id, $old_status = '', $new_status = 'cancelled' ) {
        $this->setup_locale();

        // Only proceed if new status is cancelled
        if ( $new_status !== 'cancelled' ) {
            $this->restore_locale();
            return;
        }

        $order = wc_get_order( $order_id );

        if ( is_a( $order, 'WC_Order' ) ) {
            $this->object                         = $order;
            $this->recipient                      = $order->get_billing_email();
            $this->placeholders['{order_number}'] = $order->get_order_number();
            $this->placeholders['{order_date}']   = wc_format_datetime( $order->get_date_created() );
        }

        if ( $this->is_enabled() && $this->get_recipient() ) {
            $result = $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );

            $order->add_order_note(
                'Customer cancellation email ' . ( $result ? '✅ sent to: ' : '❌ FAILED to: ' ) . $this->get_recipient()
            );
        } else {
            $order->add_order_note(
                '❌ Customer cancellation email not sent. Enabled: ' . ( $this->is_enabled() ? 'yes' : 'no' ) . ' | Recipient: ' . $this->get_recipient()
            );
        }

        $this->restore_locale();
    }

    public function get_content_html() {
        return wc_get_template_html( $this->template_html, array(
            'order'         => $this->object,
            'email_heading' => $this->get_heading(),
            'sent_to_admin' => false,
            'plain_text'    => false,
            'email'         => $this,
        ), '', $this->template_base );
    }

    public function get_content_plain() {
        return wc_get_template_html( $this->template_plain, array(
            'order'         => $this->object,
            'email_heading' => $this->get_heading(),
            'sent_to_admin' => false,
            'plain_text'    => true,
            'email'         => $this,
        ), '', $this->template_base );
    }

    public function get_subject() {
        return apply_filters( 'woocommerce_email_subject_' . $this->id, $this->format_string( $this->subject ), $this->object, $this );
    }

    public function get_heading() {
        return apply_filters( 'woocommerce_email_heading_' . $this->id, $this->format_string( $this->heading ), $this->object, $this );
    }
}