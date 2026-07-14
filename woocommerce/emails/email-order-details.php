<?php
/**
 * Order details table shown in emails.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $wpdb;
// Prior to saving the correct order totals via "save_post" hook, email notification is being sent.
// To override this, we need to force compute the correct order total so that
// email notification will contain correct order totals.
$order_total_not_formatted = 0;
$_order_total = trim( get_post_meta( $order->get_id(), '_order_total', true ) );
$order_total_not_formatted = $_order_total;
$_order_total = wc_price( $_order_total );
// Loop through order items
$subtotal = 0;
foreach ($order->get_items() as $item_id => $item) {
	// Get subtotal for the current item
	$item_subtotal = $item->get_subtotal();
	// Accumulate subtotal
	$subtotal += $item_subtotal;
}
$coupon_codes = $order->get_coupon_codes();
$original_value = $order_total_not_formatted;
if (!empty($coupon_codes)) {
	$increase_percent = 40; 
} else {
	$increase_percent = 0; 
}
// Calculate the amount of increase
$increase_amount = $subtotal * $increase_percent / 100; 
// Add the increase to the original value
$increased_value = $subtotal;
$_cart_discount = $increase_amount;
$_order_tax = wc_round_tax_total( (string)trim( get_post_meta( $order->get_id(), '_order_tax', true ) ) );
$_order_shipping = (float)trim( get_post_meta( $order->get_id(), '_order_shipping', true ) );
// $formatted_order_subtotal = ( $order_total_not_formatted + $_cart_discount ) - ( $_order_shipping + (float)$_order_tax );
$formatted_order_subtotal = wc_price($increased_value);
$currency_html = '<span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">' . get_woocommerce_currency_symbol() . '</span>';
$totals = $order->get_order_item_totals();
$trade_entered_order_number = get_post_meta( $order->get_id(), '_trade_order_reference_num', true );
$order_number = get_post_meta( $order->get_id(), '_order_number_formatted', true );
// Admin-only: current lead time based on the products in this order.
// An order can hold several products, so we keep the latest (slowest) date.
$order_lead_time_date = null;
if ( $sent_to_admin && function_exists( 'pt_delivery_date_calculator' ) ) {
	foreach ( $order->get_items() as $lead_item ) {
		$lead_product = $lead_item->get_product();
		if ( ! $lead_product ) {
			continue;
		}
		// delivery_time ACF field lives on the parent, not the variation.
		$lead_product_id = $lead_product->is_type( 'variation' ) ? $lead_product->get_parent_id() : $lead_product->get_id();
		$item_lead_date  = pt_delivery_date_calculator( $lead_product_id );
		if ( $item_lead_date && ( null === $order_lead_time_date || $item_lead_date > $order_lead_time_date ) ) {
			$order_lead_time_date = $item_lead_date;
		}
	}
}
?>
<?php do_action( 'woocommerce_email_before_order_table', $order, $sent_to_admin, $plain_text, $email ); ?>
<!-- <p>&nbsp;</p> -->
<style>
tfoot td.td {
    padding: 0 !important;
    width: 50% !important;
}
tfoot td.td.last {
    text-align: right !important;
}
.component_table_item_subtotal:after {
    display: none !important;
}
.last small {
    display: none !important;
}
.order-details-card{
    border-radius: 20px !important;
	background: #3B333D;
	padding: 24px;
    color: #fff;
    margin: 24px 0;
}
.order-roadmap-card{
    border-radius: 20px;
    background: rgba(59, 51, 61, 0.05);
	padding: 24px;
    margin: 24px 0;
}
.order-roadmap-card h3{
    font-size: 20px;
    margin: 0;
    margin-bottom: 8px;
    line-height: 28px; 
}
.order-roadmap-card .roadmap-list{
    flex-direction: column;
    display: flex;
    gap: 8px;
}
.order-roadmap-card .roadmap-list .item{
    display:flex;
    border-bottom: 1px solid rgba(59, 51, 61, 0.10);
    gap: 8px;
}
.order-roadmap-card .roadmap-list .item:last-child{
    border: none;
}
.text-column{
    flex-direction: column;
}
.order-roadmap-card .roadmap-list .item span{
    display:flex; 
    justify-content:center;
    align-items: center;
    width: 20px;
    height: 20px;
     background: rgba(255, 255, 0, 0.30);
    color: #3B333D;  
    border-radius: 500px;
}
.order-roadmap-card .roadmap-list .item span.active{
     color: rgba(255, 255, 0, 0.90);
    background: #3B333D; 
}
.order-roadmap-card .roadmap-list .item .text-column h4{font-size: 16px; line-height: 16px;}
.order-roadmap-card .roadmap-list .item .text-column p{font-size: 12px;;}
.order-roadmap-card .roadmap-list h4{
    margin: 0;
}
.order-roadmap-card .roadmap-list p div{
    display:flex;
    flex-direction: column;
}
.order-details-card p:last-child{
	margin-bottom: 0 !important;
}
.order-email-label{
    display: flex;
    color: #fff;
	font-weight: 700;
    justify-self: center;
	padding: 0 24px;
	margin: 0 auto;
	line-height: 24px;
}
.order-details-card time{
    display: block;
    font-size: 43px;
    color:  rgba(255, 255, 0, 0.90);
    text-align: center;
    font-weight: 600;
}
</style>

<!-- ===== INTRO CARD ===== -->
<table cellpadding="0" cellspacing="0" border="0" width="100%">
    <tr>
        <td style="padding: 0;">
            <table cellpadding="0" cellspacing="0" border="0" width="100%"
                style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; background: linear-gradient(73deg, rgba(178, 255, 157, 0.30) 0.12%, rgba(255, 255, 157, 0.30) 54.74%, rgba(255, 255, 0, 0.30) 109.01%); border-radius: 20px;">
                <tr>
                    <td align="center" style="padding: 24px 24px 0 24px;">
                        <!-- Order number pill -->
                        <table cellpadding="0" cellspacing="0" border="0"
                            style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; margin: 0 auto; display: inline-table;">
                            <tr>
                                <td align="center"
                                    style="background-color: #ffff00; border-radius: 99px; padding: 4px 24px; font-family: 'Work Sans', Arial, sans-serif; font-size: 14px; font-weight: 700; color: #3B333D; line-height: 24px; white-space: nowrap;">

                                      <?php if ( ! $sent_to_admin ) : ?>
                            ORDER <?php echo esc_html( $order->get_order_number() ); ?> RECEIVED
                        <?php else : ?>
                        <a class="link"
                            href="<?php echo esc_url( admin_url( 'post.php?post=' . $order->get_id() . '&action=edit' ) ); ?>"
                            style="color: #3b333d; text-decoration: none; font-size: 14px;font-weight: 700;">
                             ORDER  <?php echo esc_html( $order_number ); ?>  RECEIVED
                        </a>
                        <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 16px 24px 0 24px;">
                        <h1 style="margin: 0; font-family: 'Work Sans', Arial, sans-serif; font-size: 28px; font-weight: 700; color: #3B333D; line-height: 1.3;">
                            Welcome to the project.
                        </h1>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 12px 24px 0 24px;  min-height: 100px;">
                        <?php echo ! empty( $email_improvements_enabled ) ? '<div class="email-introduction">' : ''; ?>
                        <p style="margin: 0 0 12px 0; font-family: 'Work Sans', Arial, sans-serif; font-size: 16px; color: #3B333D; line-height: 1.6; font-style: normal;">
                            <?php printf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $order->get_billing_first_name() ) ); ?>
                        </p>
                        <?php if ( $order->needs_payment() ) : ?>
                            <p style="margin: 0 0 12px 0; font-family: 'Work Sans', Arial, sans-serif; font-size: 16px; color: #3B333D; line-height: 1.6; font-style: normal;">
                            <?php
                            if ( $order->has_status( 'failed' ) ) {
                                printf(
                                    wp_kses(
                                        /* translators: %1$s Site title, %2$s Order pay link */
                                        __( 'Sorry, your order on %1$s was unsuccessful. Your order details are below, with a link to try your payment again: %2$s', 'woocommerce' ),
                                        array( 'a' => array( 'href' => array() ) )
                                    ),
                                    esc_html( get_bloginfo( 'name', 'display' ) ),
                                    '<a href="' . esc_url( $order->get_checkout_payment_url() ) . '" style="color: #3B333D;">' . esc_html__( 'Pay for this order', 'woocommerce' ) . '</a>'
                                );
                            } else {
                                printf(
                                    wp_kses(
                                        /* translators: %1$s Site title, %2$s Order pay link */
                                        __( 'An order has been created for you on %1$s. Your order details are below, with a link to make payment when you\'re ready: %2$s', 'woocommerce' ),
                                        array( 'a' => array( 'href' => array() ) )
                                    ),
                                    esc_html( get_bloginfo( 'name', 'display' ) ),
                                    '<a href="' . esc_url( $order->get_checkout_payment_url() ) . '" style="color: #3B333D;">' . esc_html__( 'Pay for this order', 'woocommerce' ) . '</a>'
                                );
                            }
                            ?>
                            </p>
                        <?php else : ?>
                            <p style="margin: 0 0 12px 0; font-family: 'Work Sans', Arial, sans-serif; font-size: 16px; color: #3B333D; line-height: 1.6; font-style: normal;">
                                <?php
                                /* translators: %s Order date */
                                printf( esc_html__( 'Thank you for our order, we will process it soon. Here are the details of your order placed on %s:', 'woocommerce' ), esc_html( wc_format_datetime( $order->get_date_created() ) ) );
                                ?>
                            </p>
                        <?php endif; ?>
                        <?php echo ! empty( $email_improvements_enabled ) ? '</div>' : ''; ?>
                    </td>
                </tr>
                
            </table>
        </td>
    </tr>
</table>
<!-- ===== END INTRO CARD ===== -->

<table cellpadding="0" cellspacing="0" border="0" width="100%">
    <tr>
        <td class="order_items_table_holder" style="padding:0px;">
            <table cellspacing="0" cellpadding="0" border="0" width="100%"
                style="margin: 0 auto; max-width: 600px; text-align: center; font-family: 'Work Sans', sans-serif; border-spacing: 0; border-collapse: collapse;">
             <!-- Delivery Date Card Row -->
                <tr>
                    <td style="padding:0;">
                        <table cellpadding="0" cellspacing="0" border="0" width="100%"
                            style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; background-color: #3B333D; border-radius: 20px; margin: 24px 0;">
                            <tr>
                                <td align="center" style="padding: 24px;">
                                    <p style="margin: 0 0 8px 0; font-family: 'Work Sans', Arial, sans-serif; font-size: 14px; font-weight: 700; color: #ffffff; line-height: 24px; letter-spacing: 0.05em;">
                                        PREFERRED DELIVERY DATE
                                    </p>
                                    <p style="margin: 0 0 12px 0; font-family: 'Work Sans', Arial, sans-serif; font-size: 43px; font-weight: 600; color: #ffff00; line-height: 1.2;">
                                        <?php echo esc_html( date( 'jS F Y', strtotime( get_post_meta( $order->get_id(), '_from_delivery_date', true ) ) ) ); ?>
                                    </p>
                                    <?php if ( $sent_to_admin && $order_lead_time_date ) : ?>
                                    <p style="margin: 0 0 4px 0; font-family: 'Work Sans', Arial, sans-serif; font-size: 14px; font-weight: 700; color: #ffffff; line-height: 20px; letter-spacing: 0.05em;">
                                        CURRENT LEAD TIME
                                    </p>
                                    <p style="margin: 0 0 12px 0; font-family: 'Work Sans', Arial, sans-serif; font-size: 24px; font-weight: 600; color: #ffff00; line-height: 1.2;">
                                        <?php echo esc_html( $order_lead_time_date->format( 'jS F Y' ) ); ?>
                                    </p>
                                    <?php endif; ?>
                                    <p style="margin: 0; font-family: 'Work Sans', Arial, sans-serif; font-size: 15px; color: #ffffff; line-height: 1.6;">
                                        Look out for an email with your guaranteed date soon. We'll do our best to accommodate your request, though logistics may require flexibility.
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <!-- Delivery Roadmap Card Row -->
                <tr>
                    <td style="padding: 0 0 24px 0;">
                        <table cellpadding="0" cellspacing="0" border="0" width="100%"
                            style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; background-color: #f4f3f4; border-radius: 20px;" >
                            <tr>
                                <td style="padding: 24px;">
                                    <!-- Heading -->
                                    <p style="margin: 0 0 16px 0; font-family: 'Work Sans', Arial, sans-serif; font-size: 20px; font-weight: 600; color: #3B333D; line-height: 28px; text-align: left;">
                                        Your Roadmap
                                    </p>
                                    <!-- Item 1 — active -->
                                    <table cellpadding="0" cellspacing="0" border="0" width="100%"
                                        style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; border-bottom: 1px solid #e0dde0; text-align: left;">
                                        <tr>
                                            <td valign="top" width="36" style="padding: 0 12px 12px 0; text-align: center;">
                                                <table cellpadding="0" cellspacing="0" border="0"
                                                    style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
                                                    <tr>
                                                        <td align="center" valign="middle" width="24" height="24"
                                                            style="width: 24px; height: 24px; background-color: #3B333D; border-radius: 500px; font-family: 'Work Sans', Arial, sans-serif; font-size: 13px; font-weight: 700; color: #ffff00; line-height: 24px; text-align: center; padding: 0 !important;">
                                                            1
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                            <td valign="top" style="padding: 0 0 12px 0; text-align: left;">
                                                <p style="margin: 0 0 4px 0; font-family: 'Work Sans', Arial, sans-serif; font-size: 16px; font-weight: 600; color: #3B333D; line-height: 1.4;">Processing (Current Stage)</p>
                                                <p style="margin: 0 0 8px 0; font-family: 'Work Sans', Arial, sans-serif; font-size: 14px; color: #3B333D; line-height: 1.5;">Our warehouse will now be working on your building!</p>
                                            </td>
                                        </tr>
                                    </table>
                                    <!-- Item 2 -->
                                    <table cellpadding="0" cellspacing="0" border="0" width="100%"
                                        style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; border-bottom: 1px solid #e0dde0;">
                                        <tr>
                                            <td valign="top" width="36" style="padding: 12px 12px 12px 0; text-align: left;">
                                                <table cellpadding="0" cellspacing="0" border="0"
                                                    style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
                                                    <tr>
                                                        <td align="center" valign="middle" width="24" height="24"
                                                            style="width: 24px; height: 24px; background-color: #ffff99; border-radius: 500px; font-family: 'Work Sans', Arial, sans-serif; font-size: 13px; font-weight: 700; color: #3B333D; line-height: 24px; text-align: center; padding: 0 !important;">
                                                            2
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                            <td valign="top" style="padding: 12px 0 12px 0; text-align: left;">
                                                <p style="margin: 0 0 4px 0; font-family: 'Work Sans', Arial, sans-serif; font-size: 16px; font-weight: 600; color: #3B333D; line-height: 1.4;">Date Confirmation</p>
                                                <p style="margin: 0 0 8px 0; font-family: 'Work Sans', Arial, sans-serif; font-size: 14px; color: #3B333D; line-height: 1.5;">You'll receive a shipping confirmation email with your delivery date. While our team will do our best to stick to this date, please be aware that delivery dates can change. We'll keep you updated and reach out for confirmation.</p>
                                            </td>
                                        </tr>
                                    </table>
                                    <!-- Item 3 — no border -->
                                    <table cellpadding="0" cellspacing="0" border="0" width="100%"
                                        style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
                                        <tr>
                                            <td valign="top" width="36" style="padding: 12px 12px 0 0; text-align: left;">
                                                <table cellpadding="0" cellspacing="0" border="0"
                                                    style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
                                                    <tr>
                                                        <td align="center" valign="middle" width="24" height="24"
                                                            style="width: 24px; height: 24px; background-color: #ffff99; border-radius: 500px; font-family: 'Work Sans', Arial, sans-serif; font-size: 13px; font-weight: 700; color: #3B333D; line-height: 24px; text-align: center; padding: 0 !important;">
                                                            3
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                            <td valign="top" style="padding: 12px 0 0 0; text-align: left;">
                                                <p style="margin: 0 0 4px 0; font-family: 'Work Sans', Arial, sans-serif; font-size: 16px; font-weight: 600; color: #3B333D; line-height: 1.4;">Delivery Day</p>
                                                <p style="margin: 0; font-family: 'Work Sans', Arial, sans-serif; font-size: 14px; color: #3B333D; line-height: 1.5;">Your assembly instructions will be attached to the email you receive, on the delivery day.</p>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="padding:0;">
                        <a href="https://www.youtube.com/@projecttimber/videos">
                        <img src="https://www.projecttimber.com/wp-content/uploads/2026/04/Youtube-Banner-V2.png"
                            alt="Project Timber"
                            width="600"
                            style="display: block; width: 100%; max-width: 600px; height: auto; border: 0; outline: none;" />
                        </a>
                    </td>
                </tr>
                <!-- Customer Order Number Row (Conditional) -->
                <?php if ( $trade_entered_order_number ) : ?>
                <tr>
                    <td class="order-table-heading" colspan="2"
                        style="padding: 10px 0; text-align: center; font-size: 2rem; font-weight: 600;">
                        <span
                            style="font-size: 1rem; font-weight: 400;"><?php _e( 'Customer Order No.', 'email-control' ); ?></span><br />
                        <?php echo esc_html( $trade_entered_order_number ); ?>
                    </td>
                </tr>
                <?php endif; ?>
            </table>
            <table cellspacing="0" cellpadding="0" class="order_items_table" border="0" width="100%"
                style="padding: 15px; margin-top: 24px; border: 1px solid #818286; border-radius: 25px;">
                <?php if ( FALSE ) { ?>
                <thead>
                    <tr>
                        <th scope="col" class="order_items_table_th_style order_items_table_td order_items_table_td_top"
                            width="70%"><?php _e( 'Product', 'email-control' ); ?></th>
                        <th scope="col"
                            class="order_items_table_th_style order_items_table_td order_items_table_td_top">
                            <?php _e( 'Quantity', 'email-control' ); ?></th>
                        <th scope="col" class="order_items_table_th_style order_items_table_td order_items_table_td_top"
                            style="text-align:right"><?php _e( 'Price', 'email-control' ); ?></th>
                    </tr>
                </thead>
                <?php } ?>
                <tbody>
                    <?php echo wc_get_email_order_items( $order, array(
						'show_sku'      => $sent_to_admin,
						'show_image'    => FALSE,
						'image_size'    => array( 70, 70 ),
						'plain_text'    => $plain_text,
						'sent_to_admin' => $sent_to_admin
					) ); ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td class="td" style="border:0;width:50%;" scope="row" colspan="2">&nbsp;</td>
                    </tr>
                    <?php
                        /**
                         * Order totals (Subtotal, Shipping, Tax, Discount, Total, Payment method)
                         * Fully handled by WooCommerce – no manual calculations
                         */
                        $item_totals = $order->get_order_item_totals();
                        if ( $item_totals ) :
                            foreach ( $item_totals as $key => $total ) :
                                // SHIPPING
                                if ( $key === 'shipping' ) : 
                                ?>
                                        <tr class="order_items_table_total_row order_items_table_total_row_shipping">
                                            <td class="td" scope="row" style="border:0;width:50%;">
                                                <strong><?php echo esc_html( $total['label'] ); ?></strong>
                                            </td>
                                            <td>&nbsp;</td>
                                            <td class="td last" style="border:0;width:50%;text-align:right;">
                                                <?php 
                                                $shipping_total = (float) $order->get_shipping_total();
                                                $shipping_tax   = (float) $order->get_shipping_tax();
                                                $shipping_gross = $shipping_total + $shipping_tax;
                                                if ( $shipping_total <= 0 ) {
                                                    echo esc_html__( 'Free delivery', 'email-control' );
                                                } else {
                                                    echo wc_price(
                                                        $shipping_gross ,
                                                        [ 'currency' => $order->get_currency() ]
                                                    );
                                                }
                                            ?>
                                            </td>
                                        </tr>
                                        <?php
                                // PAYMENT METHOD (custom labels)
                                elseif ( $key === 'payment_method' ) : ?>
                                        <tr class="order_items_table_total_row order_items_table_total_row_payment">
                                            <td class="td" scope="row" style="border:0;width:50%;">
                                                <strong><?php esc_html_e( 'Payment method:', 'email-control' ); ?></strong>
                                            </td>
                                            <td>&nbsp;</td>
                                            <td class="td last" style="border:0;width:50%;text-align:right;">
                                                <?php
                                            switch ( $total['value'] ) {
                                                case 'PayPal':
                                                    echo 'Via PayPal';
                                                    break;
                                                case 'Omni Retail Finance':
                                                    echo 'Omni Retail Finance';
                                                    break;
                                                case 'Worldpay':
                                                    echo 'Via Worldpay';
                                                    break;
                                                case 'Bank Transfer':
                                                    echo 'Via Bank Transfer';
                                                    break;
                                                case 'Credit Cards':
                                                    echo 'Via Credit Cards';
                                                    break;
                                                case 'Pay360 (Card Payment or Apple Pay)':
                                                    echo 'Via Pay360';
                                                    break;
                                                case 'Apply for Finance with Deko':
                                                    echo 'Apply for Finance with Deko';
                                                    break;
                                                default:
                                                    echo esc_html( ucwords( $total['value'] ) );
                                            }
                                            ?>
                                            </td>
                                        </tr>
                                        <?php
                                // ALL OTHER TOTALS (Subtotal, Discount, Tax, Total)
                                else : ?>
                                        <tr class="order_items_table_total_row">
                                            <td class="td" scope="row" style="border:0;width:50%;">
                                                <strong><?php echo wp_kses_post( $total['label'] ); ?></strong>
                                            </td>
                                            <td>&nbsp;</td>
                                            <td class="td last" style="border:0;width:50%;text-align:right; font-weight: 600;">
                                                <?php echo wp_kses_post( $total['value'] ); ?>
                                            </td>
                                        </tr>
                                        <?php
                                endif;
                            endforeach;
                        endif;
                        // Customer note
                        if ( $order->get_customer_note() ) : ?>
                                        <tr>
                                            <td class="td" scope="row" style="border:0;width:50%;">
                                                <?php esc_html_e( 'Note:', 'woocommerce' ); ?>
                                            </td>
                                            <td>&nbsp;</td>
                                            <td class="td last" style="border:0;width:50%;">
                                                <?php echo wp_kses( nl2br( wptexturize( $order->get_customer_note() ) ), [] ); ?>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    </tfoot>
                                </table>
                                </table>
                            </td>
                        </tr>
                    </table>
                    <!-- <p>&nbsp;</p> -->
                    <?php
                    ob_start();
                    do_action( 'woocommerce_email_after_order_table', $order, $sent_to_admin, $plain_text, $email );
                    $check_content = ob_get_clean();
                    if ( '' !== $check_content ) { ?>
                    <?php echo $check_content; ?>
                    <p>&nbsp;</p>
                    <table cellpadding="0" cellspacing="0" border="0" width="100%">
                        <tr>
                            <td class="divider-line" align="center" valign="top">&nbsp;
                                <!-- Divider -->
                            </td>
                        </tr>
                    </table>
                    <p>&nbsp;</p>
                    <?php } ?>