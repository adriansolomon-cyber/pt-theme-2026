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
	                normal !important; text-align: center; color: #3b333d;">Project Timber's Delivery Update
	            </p>
	            <p style="margin: 10px 0;padding: 0;font-family: jr,
					sans-serif !important;font-weight: normal !important; text-align: center;color: #3b333d;">&nbsp;
				</p>
	        </div>
			
			<div style="text-align: left;">
			
			<p>Good Day <?php echo $billing_first_name . ' ' . $billing_last_name; ?>,</p>

			<p>We hope you are well and safe.</p>

			<p>Your item is now scheduled to be delivered on <u><strong><?php echo $final_delivery_date; ?></strong></u>.</p>

			<p>Our driver is set to call en route with an ETA.</p>

			<p>We offer a kerbside delivery as per our terms and conditions and the building is delivered by one man, please make prior arrangements to delivery if you need support to transport the building into your property.</p>

			<p>In the event that you will not be available to take your delivery, please let us know at the earliest convenience so we can either reschedule or organized a safe place to leave your building. However, it is preferable that someone is available to sign for the delivery.</p>

			<p>On receipt of your building, please fully check all components are present and correct sizes before attempting assembly or hiring any third parties to assemble as we cannot be held responsible for any unexpected costs incurred in this regard.</p>

			<p>We also practice social distancing as per our guidelines. If you want to have a non-contact delivery, please advise our team and our driver so we can fulfill it.</p>

			<p>Disclaimer: For any unavoidable changes on the delivery date, we will get in touch with you immediately.</p>

			<p>Thank you for your patience and understanding of these hard times.</p>


			<p>Thanks,<br>
			Project Timber Team</p>
			
			</div>
	    </td>
	</tr>

	<?php include "sales-profit-report-footer.php"; ?>