<?php
/**
 * Customer processing order email
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email );
$payment_method = get_post_meta( $order->get_id(), '_payment_method', true );?>

<div class="top_heading">
	<?php
	if ( $payment_method == 'v12retailfinance' ) {
		echo'<p style="margin: 10px 0; padding: 0; font-family: jr, sans-serif !important; font-weight: normal; text-align: center; color: #3b333d;">Thank you for your order</p>';
	} else {
		echo get_option( 'ec_vanilla_customer_processing_order_heading' );
	}
	?>
</div>

<h1>THANK YOU FOR YOUR ORDER</h1>

<p style="text-align: center;">your building is now being manufactured</p>

<a href="tel:01777802300"><img src="https://www.projecttimber.com/wp-content/uploads/2021/07/cta_call_us.png"/></a>
<br/><br/>
<div>
<!--img src="https://www.projecttimber.com/wp-content/uploads/2021/07/evolution_music_room.png" width="100%"/-->
</div>
</div>
<div class="mainbg">
<div class="roundwrapper">
<div class="round">
<p>Hi <?php echo ucwords( $order->get_billing_first_name() );?>,</p>
<?php
$order_customer = get_user_by( 'id', $order->get_user_id() );

// If customer is trade
if( !empty( array_intersect( $order_customer->roles, TRADE_ACCOUNTS ) ) ) {

	// If customer is trade w/ collection
	$user_is_collector = get_user_meta( $order->get_user_id(), 'user_is_collector', true );

	if( $user_is_collector ) {

		echo '<p>Should you have any questions, please feel free to call us on 01636 370600 or email us.</p>
		<p>Your order will be ready for collection on ' . date_format( date_create( get_next_business_day(2) ), "l j F" ) . '.</p>
		<p>On receipt of your building, please fully check all components are present and you have the correct size(s).</p>
		<p>Thanks,<br>
		The Project Timber Team</p>';

	} else {
		echo '<p>Should you have any questions, please feel free to call us on 01636 370600 or email us.</p>
		<p>We will be in touch shortly to arrange delivery. You do not have to be in to take your delivery. If you won’t be in, please let us know if you would like your item leaving somewhere, although it is preferable for us to get a signature on delivery where possible.</p>
		<p>On receipt of your building, please fully check all components are present and you have the correct size(s).</p>
		<p>Thanks,<br>
		The Project Timber Team</p>';
	}

} else {
	if ( $payment_method <> 'v12retailfinance' ) {
		echo get_option( 'ec_vanilla_customer_processing_order_main_text' );
	}
}
?>
</div>
</div>

</div>
<img class="verano" src="https://www.projecttimber.com/wp-content/uploads/2021/07/verano_email_order_new.png" />
<div>*Product image shows - Pressure Treated Verano Summerhouse</div>
<div class="whatyouorder">
<h1>HERE'S WHAT YOU ORDERED:</h1>
</div>
<?php

/**
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
//do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/**
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
//do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * @hooked WC_Emails::email_footer() Output the email footer
 */
?>
<a href="mailto:sales@projecttimber.co.uk"><img class="sendemail" src="https://www.projecttimber.com/wp-content/uploads/2021/07/send_us_an_email.png" /></a>

<p><a href="https://www.trustpilot.com/evaluate/www.projecttimber.com" class="link">Review your buying experience to enter our prize draw</a></p>
<p>If you have any questions about your building, please email us at <a href="mailto:sales@projecttimber.co.uk" class="link">sales@projecttimber.co.uk</a> or visit our <a href="https://www.projecttimber.com/faq/" class="link">FAQ Page</a>. We also have a dedicated phone line available to answer any and all queries you may have. Feel free to give us a call on 01777 802 300.</p>
<p><a href="https://www.facebook.com/projecttimber" style="margin-right:10px"><img src="https://www.projecttimber.com/wp-content/themes/projecttimber/images/socialicons/facebook.png"></a>
<a href="https://www.instagram.com/projecttimber" style="margin-right:10px"><img src="https://www.projecttimber.com/wp-content/themes/projecttimber/images/socialicons/instagram.png"></a>
<a href="https://twitter.com/project_timber" style="margin-right:10px"><img src="https://www.projecttimber.com/wp-content/themes/projecttimber/images/socialicons/twitter.png"></a>
<a href="https://www.youtube.com/channel/UCI2f3ZXJJoN2igKPES_ig5A"><img src="https://www.projecttimber.com/wp-content/themes/projecttimber/images/socialicons/youtube.png"></a></p>
<ol>
        <li class="crumb"><a href="https://www.projecttimber.com/">Project Timber</a></li>
		<li class="crumb">|</li>
        <li class="crumb"><a href="https://www.projecttimber.com/garden-offices/">Products</a></li>
		<li class="crumb">|</li>
        <li class="crumb"><a href="https://www.projecttimber.com/faq/">FAQs</a></li>
		<li class="crumb">|</li>
		<li class="crumb"><a href="https://www.projecttimber.com/blog/">Blogs</a></li>
    </ol>
<?php
do_action( 'woocommerce_email_footer', $email );
