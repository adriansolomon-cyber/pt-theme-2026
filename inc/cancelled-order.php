<?php
/*
This script will allow you to send a custom email from anywhere within wordpress
but using the woocommerce template so that your emails look the same.
Created by craig@123marbella.com on 27th of July 2017
Put the script below into a function  or anywhere you want to send a custom email
*/

function get_custom_email_html( $order, $heading = false, $mailer ) {

	$template = 'emails/cancelled-order.php';

	return wc_get_template_html( $template, array(
		'order'         => $order,
		'email_heading' => $heading,
		'sent_to_admin' => false,
		'plain_text'    => false,
		'email'         => $mailer
	) );

}

// load the mailer class
$mailer = WC()->mailer();

//format the email
$recipient = $order->get_billing_email();
$subject = __("Project Timber Order Cancellation", 'theme_name');
$content = get_custom_email_html( $order, $subject, $mailer );
$headers = array('Content-Type: text/html; charset=UTF-8');
$headers[] = 'Bcc: davecanilao@projecttimber.co.uk';

//send the email through wordpress
$mailer->send( $recipient, $subject, $content, $headers );
