<?php
	error_reporting(E_ALL);
	global $wpdb; 
	include "sales-profit-report-header.php";

	$final_delivery_date 	= trim( get_post_meta( $order->get_id(), '_final_delivery_date', true ) );
	$final_delivery_date 	= date_create( $final_delivery_date );
	$final_delivery_date 	= $final_delivery_date->format('l j F Y');
	$billing_first_name		= $order->get_billing_first_name();
	$billing_last_name		= $order->get_billing_last_name();
	
	$query_items = 'SELECT * FROM wp_woocommerce_order_items WHERE order_id = "'.$order->get_id().'"';
    $meta_items = $wpdb->get_results($query_items, OBJECT);
	
	$items = $order->get_items();
    
    foreach ( $items as $item ) {
        
        $productid = $item['product_id'];
        
        break;
    }
    
    $product_id = 0;
    
    foreach ($meta_items as $indxitem => $item) {
		
	    if($indxitem == 1) {
		    $product_size = $item->order_item_name;
			$order_item_id = $item->order_item_id;
			
			$order_query_items = "SELECT meta_value AS product_id FROM wp_woocommerce_order_itemmeta WHERE meta_key =  '_product_id' AND order_item_id = " . $order_item_id;
			$order_meta_items = $wpdb->get_results($order_query_items, OBJECT);
			
			$product_id = $order_meta_items[0]->product_id;
			
			$pdf = get_field('evolution_pdf_instruction', $product_id);
			$evo_pdfLink = $pdf[0]['pdf'];
	    }
		
		if( strpos( strtolower( $item->order_item_name ), 'upvc' ) !== false ) {
			$upvc_pdf = get_field('evolution_upvc_pdf_instruction', $product_id);
			$evo_upvc_pdfLink = $upvc_pdf[0]['pdf'];
			break;
		}
    }
	
	$size = explode('x', $product_size);
	
    $pdfs = get_field('pdf_instruction_gables', $productid);
    
    
    foreach($pdfs as $instruction) {
	    if($size[1] == $instruction['size_gable']) {
	       $pdfLink = $instruction['pdf'];
	        break;
	    }
	}
    
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
	                normal !important; text-align: center; color: #3b333d;">Delivery Email
	            </p>
	            <p style="margin: 10px 0;padding: 0;font-family: jr,
					sans-serif !important;font-weight: normal !important; text-align: center;color: #3b333d;">&nbsp;
				</p>
	        </div>
			
			<div style="text-align: left;">

			<p>Good Day <?php echo $billing_first_name . ' ' . $billing_last_name; ?>,</p>

			<p>Thank you once again for placing your order with us. We appreciate your custom and hope you will love your new building.</p>

			<p>We will now be delivering your item on <u><strong><?php echo $final_delivery_date; ?></strong></u>.</p>

			<p>Our driver will call en route with an ETA.</p>

			<p>We offer a curbside delivery as per our terms and conditions and the building is delivered by one man, please make prior arrangements to delivery if you need support to transport the building into your property.</p>

			<p>In the event that you will not be available to take your delivery, please let us know at the earliest convenience so we can either reschedule or organized a safe place to leave your building. However, it is preferable that someone is available to sign for the delivery.</p>

			<p>On receipt of your building, please fully check all components are present and correct sizes before attempting assembly or hiring any third parties to assemble as we cannot be held responsible for any unexpected costs incurred in this regard.</p>
			
			<p>We would be extremely grateful if you would be spending a few minutes of your time to rate our service. Kindly click the link please, https://www.trustpilot.com/evaluate/www.projecttimber.com</p>
			
			<p>Be safe always!</p>
			
			<?php if( $evo_pdfLink || $pdfLink ) : ?>
			<p style="font-size: 18px;">Building Assembly Instructions (PLEASE PRINT) <a href="<?php echo $evo_pdfLink ? $evo_pdfLink : $pdfLink; ?>">Download</a></p>
			<?php endif; ?>
			
			<?php if( $evo_upvc_pdfLink ) : ?>
			<p style="font-size: 18px;">Building Assembly Instructions for uPVC (PLEASE PRINT) <a href="<?php echo $evo_upvc_pdfLink; ?>">Download</a></p>
			<?php endif; ?>

			<p>Thanks,<br>
			Logistics Team</p>
			
			</div>
	    </td>
	</tr>

	<?php include "sales-profit-report-footer.php"; ?>