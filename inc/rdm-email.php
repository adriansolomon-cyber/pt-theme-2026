<?php
	error_reporting(E_ALL);
	include "sales-profit-report-header.php";

	$final_delivery_date 	= trim( get_post_meta( $order->get_id(), '_final_delivery_date', true ) );
	$final_delivery_date 	= date_create( $final_delivery_date );
	$final_delivery_date 	= $final_delivery_date->format('l j F Y');
	$billing_first_name		= $order->get_billing_first_name();
	$billing_last_name		= $order->get_billing_last_name();
	?>

	<tr style="color:
	    #3b333d; font-family: jr, Open Sans, sans-serif; font-size: 16px;
	    line-height: 1.5em;">
	    <td class="body_content" align="center" valign="top" style="color: #3b333d; font-family: jr, Open Sans, sans-serif
	        !important; font-size: 16px; line-height: 1.5em;
	        font-weight: normal !important; text-align: center; margin:
	        0; padding: 30px 0 0 !important;">
	        <div class="top_heading" style="font-family: jr, Open Sans, sans-serif !important; font-weight:
	            normal !important; color: #3b333d; font-size: 30px;
	            letter-spacing: -1px; line-height: 30px; margin: 0; padding:
	            0 0 10px !important;">
	            <p style="margin: 10px 0; padding:
	                0; font-family: jr, Open Sans, sans-serif !important; font-weight:
	                normal !important; text-align: center; color: #3b333d;">Project Timber's Spares Delivery Update
	            </p>
	            <p style="margin: 10px 0;padding: 0;font-family: jr,
					sans-serif !important;font-weight: normal !important; text-align: center;color: #3b333d;">&nbsp;
				</p>
	        </div>
			
			<div style="text-align: left;">
			
			<p>Good Day <?php echo $billing_first_name . ' ' . $billing_last_name; ?>,

			<p>Hope you are doing well.</p>

			<p>We are now delivering the spare parts for your order on <u><strong><?php echo $final_delivery_date; ?></strong></u>.</p>

			<p>If you have any other questions please kindly ring us at 01777802300.</p>

			<p>Thanks,<br>
			Project Timber</p>
			
			</div>
	    </td>
	</tr>

	<?php include "sales-profit-report-footer.php"; ?>