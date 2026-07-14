<?php
/**
 * Customer processing order email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-processing-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 3.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<!-- <p style="font-size: 2rem;
    font-family: 'Work Sans', sans-serif;
    text-align: center;
    font-weight: 600;
    line-height: 35px;
    margin: auto;">THANK YOU <br>FOR YOUR ORDER!<p> -->


<?php

/*
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );
?>


<!-- <p style="font-size: 1.5rem;
    font-weight: 600;
    text-align: center;
    background: #F6F6F6;
    padding: 15px;
    margin-bottom: 0;">WHAT COMES NEXT?</p>
<table style="width:100%;background: #F6F6F6;    margin-bottom: 20px;">
<tr>
	<td><span style="font-size: 1rem;
    font-weight: 600;
    text-align: center;
    background: #ffff00;
    padding: 15px 20px;
    border-radius: 50%;">1</span></td>
	<td>Our warehouse will now be working on your building!</td>
</tr>
<tr>
	<td><span style="font-size: 1rem;
    font-weight: 600;
    text-align: center;
    background: #ffff00;
    padding: 15px 20px;
    border-radius: 50%;">2</span></td>
	<td>You will receive a shipping confirmation email confirming your delivery date.*</td>
</tr>
<tr>
	<td><span style="font-size: 1rem;
    font-weight: 600;
    text-align: center;
    background: #ffff00;
    padding: 15px 20px;
    border-radius: 50%;">3</span></td>
	<td>Your assembly instructions will be attached to the email you receive, confirming your delivery date.</td>
</tr>
</table> -->

<?php

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
// if ( $additional_content ) {
// 	echo wp_kses_post( wpautop( wptexturize('<p style="text-align:center;margin-top: 20px;">'.$additional_content.'</p>' ) ) );
// }

?>

<!-- <p style="text-align:center;margin: 0;">01777 553472</p>
<p style="text-align:center;margin: 0;">sales@projecttimber.co.uk</p>
<p style="text-align:center;margin:0 0 30px;">www.facebook.com/projecttimber</p>

<p style="text-align:center;"><a href="https://www.projecttimber.com/" style="padding:15px;text-align:center;background: #ffff00; font-weight:600;text-decoration: none;">VISIT OUR WEBSITE</a></p> -->

<?php


/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );
