<?php
	error_reporting(E_ALL);
	global $wpdb;

	$isMyDen 				= false;
	$isCannes 				= false;
	$isAlpine				= false;
    
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

		if (preg_match("/\bmy den\b/i", $item['name'], $matches)) {
			$isMyDen = true;
		} 
	
		if (preg_match("/\bcanne\b/i", $item['name'], $matches)) {
			$isCannes = true;
		}

		if (preg_match("/\balpine\b/i", $item['name'], $matches)) {
			$isAlpine = true;
		}

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
			//$evo_pdfLink = $pdf[0]['pdf'];
			$evo_pdfLink = $pdf[0]['flipbook'];
	    }

		if( strpos( strtolower( $item->order_item_name ), 'upvc' ) !== false ) {
			$upvc_pdf = get_field('evolution_upvc_pdf_instruction', $product_id);
			//$evo_upvc_pdfLink = $upvc_pdf[0]['pdf'];
			$evo_upvc_pdfLink = $upvc_pdf[0]['flipbook'];

			break;
		}
    }

	$size = explode(' ', $product_size);

    $pdfs = get_field('pdf_instruction_gables', $productid);


    foreach($pdfs as $instruction) {
		$idx = $isAlpine ? 0 : 2;
	    if($size[$idx] == $instruction['size_gable']) {
	       //$pdfLink = $instruction['pdf'];
		   $pdfLink = $instruction['flipbook'];
	        break;
	    }
	}

	?>

<!DOCTYPE html>
<html dir="ltr">

	<head>

		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>Project Timber<img src="https://s.w.org/images/core/emoji/17.0.2/72x72/2122.png" alt="™" class="wp-smiley" style="height: 1em; max-height: 1em;" /></title>

		<!--[if mso]>
		<style type="text/css">
		body, table, td { font-family: Arial, sans-serif !important; }
		</style>
		<![endif]-->

		

		<!--
			NOTE: Google Fonts via <link> are stripped by most email clients including
			Gmail web. Work Sans is used here as a best-effort for clients that support
			it (Apple Mail, Outlook macOS). All other clients will fall back to Arial.
			Do NOT rely on web fonts for layout or sizing.
		-->
		<link rel="preconnect" href="https://fonts.googleapis.com">
		<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
		<link href="https://fonts.googleapis.com/css2?family=Work+Sans:ital,wght@0,100..900;1,100..900&amp;display=swap" rel="stylesheet">

	<style type="text/css">@media screen and (max-width: 600px){#body_content td.mobile-pad{padding: 16px !important;}}.component_table_item_subtotal:after{display: inline-block; width: 1em; height: 1em; background: url("data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAzODQgNTEyIj48cGF0aCBkPSJNMzM2LjEgMzc2LjFsLTEyOCAxMjhDMjA0LjMgNTA5LjcgMTk4LjIgNTEyIDE5MS4xIDUxMnMtMTIuMjgtMi4zNDQtMTYuOTctNy4wMzFsLTEyOC0xMjhjLTkuMzc1LTkuMzc1LTkuMzc1LTI0LjU2IDAtMzMuOTRzMjQuNTYtOS4zNzUgMzMuOTQgMEwxNjggNDMwLjFWNDhoLTE0NEMxMC43NSA0OCAwIDM3LjI1IDAgMjRTMTAuNzUgMCAyNCAwSDE5MmMxMy4yNSAwIDI0IDEwLjc1IDI0IDI0djQwNi4xbDg3LjAzLTg3LjAzYzkuMzc1LTkuMzc1IDI0LjU2LTkuMzc1IDMzLjk0IDBTMzQ2LjMgMzY3LjYgMzM2LjEgMzc2LjF6Ii8+PC9zdmc+"); background-repeat: no-repeat; background-position: right; background-size: contain; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; transform: rotate(90deg); content: ""; margin: 0 0 0 8px; opacity: .25;}@media only screen and (max-width: 620px){#template_container,#template_header_table{width: 100% !important;}.header-logo-cell{padding: 16px 16px 0 16px !important;}.header-icons-cell{padding: 16px 16px 0 16px !important; text-align: left !important;}.social-icon{margin-right: 8px !important;}}.component_table_item_subtotal:after{display: none !important;}</style></head>

	<body leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0" style='text-align: center; font-family: "Work Sans",Arial,sans-serif; background-color: #f5f5f5; margin: 0; padding: 0; width: 100%;' bgcolor="#f5f5f5" width="100%">

		<!-- Wrapper table — full width background -->
		<table border="0" cellpadding="0" cellspacing="0" width="100%" id="bodyTable" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; background-color: #f5f5f5; display: table; margin: 0; padding: 0; width: 100%;" bgcolor="#f5f5f5">
			<tr>
				<td align="center" valign="top" style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 24px 0;'>

					<!-- Main container: 600px wide -->
					<table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_container" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; background-color: #ffffff; border: none; max-width: 600px; box-shadow: 0 1px 4px rgba(0,0,0,.1); border-radius: 3px;" bgcolor="#ffffff">
					<tbody style="">
						<!-- ===== HEADER ROW ===== -->
						<tr>
							<td valign="top" style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 24px 24px 0 24px;'>

								<!--
									Header bar: logo left, social icons right.
									We use a nested table instead of flexbox/div layout
									because flexbox is not supported in email clients.
									background-color is a solid yellow fallback — gradients
									and border-radius are ignored by Outlook.
								-->
								<table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_header_table" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; background-color: #ffff00; border-radius: 12px;" bgcolor="#ffff00">
									<tr>

										<!-- Logo cell -->
										<td valign="middle" class="header-logo-cell" style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 16px 0 16px 20px; width: auto;'>
                                            <a href="https://projecttimber.com/">
											    <img src="https://www.projecttimber.com/wp-content/uploads/2026/04/ProjectTimber-Logo-2-1.png" alt="Project Timber&#x2122;" width="150" style="font-size: 14px; font-weight: bold; text-transform: capitalize; vertical-align: middle; margin-right: 10px; text-decoration: none; display: block; max-width: 150px; height: auto; border: 0; outline: none;" border="0">										
                                            </a>
                                        </td>

										<!-- Social icons cell — right-aligned -->
										<td valign="middle" align="right" class="header-icons-cell" style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 16px 20px 16px 0;'>

											<!--
												Icons in a table row so spacing is consistent
												across all clients (gap/flex not supported).
											-->
											<table border="0" cellpadding="0" cellspacing="0" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; display: inline-table;">
												<tr>
													<td style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 0 10px 0 0;'>
														<a href="https://www.facebook.com/projecttimber" style="color: #454530; font-weight: normal; text-decoration: none;">
                       										 <img src="https://www.stg-projecttimbercom-staging.kinsta.cloud/wp-content/themes/theTimber/assets/images/social-logos/v2/fb-v2.svg" width="24" height="24" class="social-icon" style="font-size: 14px; font-weight: bold; text-transform: capitalize; vertical-align: middle; margin-right: 10px; max-width: 100%; text-decoration: none; display: block; border: 0; outline: none; width: 24px; height: 24px;" border="0">
														</a>
													</td>
													<td style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 0 10px 0 0;'>
														<a href="https://www.instagram.com/projecttimber/" style="color: #454530; font-weight: normal; text-decoration: none;">
															 <img src="https://www.stg-projecttimbercom-staging.kinsta.cloud/wp-content/themes/theTimber/assets/images/social-logos/v2/youtube-v2.svg" width="24" height="24" class="social-icon" style="font-size: 14px; font-weight: bold; text-transform: capitalize; vertical-align: middle; margin-right: 10px; max-width: 100%; text-decoration: none; display: block; border: 0; outline: none; width: 24px; height: 24px;" border="0">
														</a>
													</td>
													<td style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 0;'>
														<a href="https://www.youtube.com/@projecttimber/videos" style="color: #454530; font-weight: normal; text-decoration: none;">
															 <img src="https://www.stg-projecttimbercom-staging.kinsta.cloud/wp-content/themes/theTimber/assets/images/social-logos/v2/insta-v2.svg" width="24" height="24" class="social-icon" style="font-size: 14px; font-weight: bold; text-transform: capitalize; vertical-align: middle; margin-right: 10px; max-width: 100%; text-decoration: none; display: block; border: 0; outline: none; width: 24px; height: 24px;" border="0">
														</a>
													</td>
												</tr>
											</table>

										</td>
									</tr>
								</table>

							</td>
						</tr>
						<!-- ===== END HEADER ROW ===== -->

						<!-- ===== BODY ROW ===== -->
						<tr>
							<td align="center" valign="top" style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px;'>
								<table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_body" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; max-width: 600px;">
									<tr>
										<td valign="top" id="body_content" style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; background-color: #fff;' bgcolor="#fff">
											<table border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
												<tr>
													<td valign="top" class="mobile-pad" style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 24px;'>
														<div id="body_content_inner" style="text-align: left; font-family: 'Work Sans', Arial, sans-serif; color: #3B333D; font-size: 16px; line-height: 1.6;" align="left">
<!--
    EMAIL COMPATIBILITY FIXES:
    1. <style> block removed — Gmail strips it entirely. All styles are inline.
    2. linear-gradient removed — not supported in Outlook. Replaced with solid
       hex fallback #efffcc (closest neutral to the original green-yellow blend).
    3. rgba() backgrounds replaced with solid hex equivalents.
    4. <div> layout replaced with <table> structure.
    5. Order label pill: <span> inside a centered <td> — reliable cross-client
       pill shape using padding + border-radius on the <td>.
    6. font-style: normal added on address-like content to prevent iOS Mail
       from auto-italicising detected address strings.
-->



<!-- <p>&nbsp;</p> -->


<!-- ===== INTRO CARD ===== -->
<table cellpadding="0" cellspacing="0" border="0" width="100%">
    <tr>
        <td style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 0;'>
            <table cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; background: linear-gradient(73deg, rgba(178, 255, 157, 0.30) 0.12%, rgba(255, 255, 157, 0.30) 54.74%, rgba(255, 255, 0, 0.30) 109.01%); border-radius: 20px;" bgcolor="linear-gradient(73deg,">
                <tr>
                    <td align="center" style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 24px 24px 0 24px;'>
                        <!-- Order number pill -->
                        <table cellpadding="0" cellspacing="0" border="0" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; margin: 0 auto; display: inline-table;" align="center">
                            <tr>
                                <td align="center" style="background-color: #ffff00; border-radius: 99px; padding: 4px 24px; font-family: 'Work Sans', Arial, sans-serif; font-size: 14px; font-weight: 700; color: #3B333D; line-height: 24px; white-space: nowrap;" bgcolor="#ffff00">
                                    ORDER <?php echo esc_html( $order->get_order_number() ); ?>  DELIVERY UPDATE
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 16px 24px 0 24px;'>
                        <h1 style="text-align: left; text-shadow: 0 1px 0 #6a6a59; margin: 0; font-family: 'Work Sans', Arial, sans-serif; font-size: 28px; font-weight: 700; color: #3B333D; line-height: 1.3;">
                           We're sorry about your delivery delay.
                        </h1>
                    </td>
                </tr>
                <tr>
                    <td style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 12px 24px 0 24px; min-height: 100px;'>
                        <p style="margin: 0 0 12px 0; font-family: 'Work Sans', Arial, sans-serif; font-size: 16px; color: #3B333D; line-height: 1.6; font-style: normal;">                 
                            <?php printf( __( 'We \'re truly sorry, <span>%s</span>.  The truck is taking a little longer than expected.', 'woocommerce' ), esc_html( $order->get_billing_first_name() ) ); ?>
                        </p>                                
                    </td> 
                </tr>
                
            </table>
        </td>
    </tr>
</table>
<!-- ===== END INTRO CARD ===== -->
 <!-- ===== Delivery Date Card ===== -->

<div style="width: 100%; text-align: center; padding: 24px 0; margin-top: 24px; background-color: #3B333D; border-radius: 20px; ">
    <p style="margin: 0 0 8px 0; font-family: 'Work Sans', Arial, sans-serif; font-size: 14px; font-weight: 700; color: #ffffff; line-height: 24px; letter-spacing: 0.05em;">
        YOUR NEW DELIVERY DATE
    </p>
    <p style="margin: 0 0 12px 0; font-family: 'Work Sans', Arial, sans-serif; font-size: 2.5rem; font-weight: 600; color: #ffff00; line-height: 1.2;">
        <?php echo $final_delivery_date ?? ''; ?>                        
    </p>
    <p style="margin: 0; font-family: 'Work Sans', Arial, sans-serif; font-size: 16px; color: #ffffff; line-height: 1.6;">
        Window: 8:00 AM – 6:00 PM
    </p>
</div>
 <!-- ===== End Delivery Date Card ===== -->
  
<table cellpadding="0" cellspacing="0" border="0" width="100%">
    <tr style="padding: 0px;">
        <td class="order_items_table_holder" style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 0px;'>
            <table cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: 0 auto; max-width: 600px; text-align: center; font-family: 'Work Sans', sans-serif; border-spacing: 0; border-collapse: collapse; padding:0;" align="center">
                    <!-- Apologize text -->
                <tr style="padding: 0px;"> 
                    <td style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 24px 0 0;'>
                        <table cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; background-color: rgba(255, 255, 0, 0.10); border-radius: 20px;" bgcolor="#ffff001a">
                            <tr>
                                <td style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 12px;'>
                                    <!-- Heading -->
                                    <p style="margin: 0 0 16px 0; font-family: 'Work Sans', Arial, sans-serif;  font-weight: 600; color: #3B333D; line-height: 28px; text-align: left;" align="left">
                                        <?php printf( __( 'Dear %s,', 'woocommerce' ), esc_html( $order->get_billing_first_name() ) ); ?>
                                    </p>
                                    <p style="font-family: 'Work Sans', Arial, sans-serif; color: #3B333D; line-height: 1.4; text-align: left; margin-bottom: 0;">
                                         Thank you again for your recent order. We are truly sorry to inform you that your delivery has been delayed.
                                        We understand how important this is to you and we sincerely apologise for the inconvenience this may have caused. 
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <!-- Reason text -->
                <tr>
                    <td style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 24px 0 0;'>
                        <table cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; background-color: rgba(59, 51, 61, 0.15); border-radius: 20px;" bgcolor="#ffff001a">
                            <tr>
                                <td style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 12px;'>
                                    <!-- Heading -->
                                    <p style="margin: 0 0 16px 0; font-family: 'Work Sans', Arial, sans-serif;  font-weight: 600; color: #3B333D; font-size: 20px; line-height: 28px; text-align: left;" align="left">
                                      <span>
                                        <img src="https://www.projecttimber.com/wp-content/uploads/2026/06/question_mark.png" alt="question-mark" width="18" height="18" style="margin-right: 4px; padding-top: 4px;">
                                      </span> What happened?
                                    </p>
                                    <p style="font-family: 'Work Sans', Arial, sans-serif; color: #3B333D; line-height: 1.4; text-align: left;">
                                        Due to a high volume of orders, your delivery has been rescheduled to <span style="font-weight: 600;"> <?php echo $final_delivery_date ?? ''; ?></span>. We apologise for any inconvenience this may cause.
                                    </p>
                                     <p style="font-family: 'Work Sans', Arial, sans-serif; color: #3B333D; line-height: 1.4; text-align: left; margin-bottom: 0;">
                                        Our buildings are proving very popular, and we're working hard to get every order delivered as quickly as possible. Rest assured, your order is confirmed and on its way.                                    
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <!-- Delivery Roadmap Card Row -->
                <tr>
                    <td style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 24px 0 0;'>
                        <table cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; background-color: #f4f3f4; border-radius: 20px;" bgcolor="#f4f3f4">
                            <tr>
                                <td style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 12px;'>
                                    <!-- Heading -->
                                    <p style="margin: 0 0 16px 0; font-family: 'Work Sans', Arial, sans-serif; font-size: 20px; font-weight: 600; color: #3B333D; line-height: 28px; text-align: left;" align="left">
                                       What Happens Next?
                                    </p>
                                    <!-- Item 1 — active -->
            
                                    <table cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; border-bottom: 1px solid #e0dde0;">
                                        <tr>
                                            <td valign="top" width="36" style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 12px 12px 12px 0; text-align: left;' align="left">
                                                <table cellpadding="0" cellspacing="0" border="0" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
                                                    <tr>
                                                        <td align="center" valign="middle" width="24" height="24" style="width: 24px; height: 24px; background-color: #ffff99; border-radius: 500px; font-family: 'Work Sans', Arial, sans-serif; font-size: 13px; font-weight: 700; color: #3B333D; line-height: 24px; text-align: center; padding: 0;" bgcolor="#ffff99">
                                                           1
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                            <td valign="top" style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 12px 0 12px 0; text-align: left;' align="left">
                                                <p style="margin: 0 0 4px 0; font-family: 'Work Sans', Arial, sans-serif; font-size: 16px; font-weight: 600; color: #3B333D; line-height: 1.4;">Delivery:</p>
                                                <p style="margin: 0 0 8px 0; font-family: 'Work Sans', Arial, sans-serif; font-size: 14px; color: #3B333D; line-height: 1.5;">All other delivery details remain the same — please refer to your original delivery instructions.</p>
                                            </td>
                                        </tr>
                                    </table>
                                    <!-- Item 2 — no border -->

                                       <table cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; border-bottom: 1px solid #e0dde0;">
                                        <tr>
                                            <td valign="top" width="36" style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 12px 12px 12px 0; text-align: left;' align="left">
                                                <table cellpadding="0" cellspacing="0" border="0" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
                                                    <tr>
                                                        <td align="center" valign="middle" width="24" height="24" style="width: 24px; height: 24px; background-color: #ffff99; border-radius: 500px; font-family: 'Work Sans', Arial, sans-serif; font-size: 13px; font-weight: 700; color: #3B333D; line-height: 24px; text-align: center; padding: 0;" bgcolor="#ffff99">
                                                           2
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                            <td valign="top" style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 12px 0 12px 0; text-align: left;' align="left">
                                                <p style="margin: 0 0 4px 0; font-family: 'Work Sans', Arial, sans-serif; font-size: 16px; font-weight: 600; color: #3B333D; line-height: 1.4;">Signature Required:</p>
                                                <p style="margin: 0 0 8px 0; font-family: 'Work Sans', Arial, sans-serif; font-size: 14px; color: #3B333D; line-height: 1.5;">If you are unable to receive your delivery, please inform us as soon as possible. It is important that the delivery is signed for.</p>
                                            </td>
                                        </tr>
                                    </table>
                              
                                      <!-- Item 3 — no border -->
                                    <table cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
                                        <tr>
                                            <td valign="top" width="36" style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 12px 12px 0 0; text-align: left;' align="left">
                                                <table cellpadding="0" cellspacing="0" border="0" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
                                                    <tr>
                                                        <td align="center" valign="middle" width="24" height="24" style="width: 24px; height: 24px; background-color: #ffff99; border-radius: 500px; font-family: 'Work Sans', Arial, sans-serif; font-size: 13px; font-weight: 700; color: #3B333D; line-height: 24px; text-align: center; padding: 0;" bgcolor="#ffff99">
                                                            3
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                            <td valign="top" style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 12px 0 0 0; text-align: left;' align="left">
                                                <p style="margin: 0 0 4px 0; font-family: 'Work Sans', Arial, sans-serif; font-size: 16px; font-weight: 600; color: #3B333D; line-height: 1.4;">Questions:</p>
                                                <p style="margin: 0; font-family: 'Work Sans', Arial, sans-serif; font-size: 14px; color: #3B333D; line-height: 1.5;">If you have any urgent questions, our customer care team is ready to help.</p>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <!-- Customer Order Number Row (Conditional) -->
                            </table>

                            </td>
                        </tr>
                    </table>
   
</div></td>
</tr>
<!-- Footer -->
<tr>
    <td width="100%" align="center" bgcolor="#ffffff" style="font-size: 15px; padding: 0px 24px 24px; background-color: #ffffff; font-family: 'Work Sans', Arial, sans-serif;">

        <table align="center" cellpadding="0" cellspacing="0" border="0" width="560" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
            <tr>
                <td align="center" bgcolor="#ffff00" style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; background-color: #ffff00; border-radius: 16px; padding: 30px 30px 24px;'>

                    <!-- Logo -->
                    <table cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
                        <tr>
                            <td align="center" style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 0 0 16px 0;'>
                                <img src="https://www.stg-projecttimbercom-staging.kinsta.cloud/wp-content/themes/theTimber/assets/images/social-logos/v2/logo-email-footer.svg" alt="Project Timber" border="0" style="font-size: 14px; font-weight: bold; text-transform: capitalize; vertical-align: middle; margin-right: 10px; text-decoration: none; display: block; height: auto; max-width: 100%; border: 0; outline: none;">
                            </td>
                        </tr>
                    </table>

                    <!-- Social icons -->
                    <table cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
                        <tr>
                            <td align="center" style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 0 0 20px 0;'>
                                <table cellpadding="0" cellspacing="0" border="0" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
                                    <tr>
                                        <td style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 0 6px;'>
                                            <a href="https://www.facebook.com/projecttimber" target="_blank" style="color: #454530; font-weight: normal; text-decoration: none;">
                                                <img src="https://www.stg-projecttimbercom-staging.kinsta.cloud/wp-content/themes/theTimber/assets/images/social-logos/v2/fb-v2.svg" alt="Facebook" width="24" height="24" border="0" style="font-size: 14px; font-weight: bold; text-transform: capitalize; vertical-align: middle; margin-right: 10px; max-width: 100%; outline: none; text-decoration: none; display: block; border: 0; width: 24px; height: 24px;">
                                            </a>
                                        </td>
                                        <td style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 0 6px;'>
                                            <a href="https://twitter.com/project_timber" target="_blank" style="color: #454530; font-weight: normal; text-decoration: none;">
                                                <img src="https://www.stg-projecttimbercom-staging.kinsta.cloud/wp-content/themes/theTimber/assets/images/social-logos/v2/x-v2-icon.svg" alt="X / Twitter" width="24" height="24" border="0" style="font-size: 14px; font-weight: bold; text-transform: capitalize; vertical-align: middle; margin-right: 10px; max-width: 100%; outline: none; text-decoration: none; display: block; border: 0; width: 24px; height: 24px;">
                                            </a>
                                        </td>
                                        <td style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 0 6px;'>
                                            <a href="https://www.pinterest.co.uk/projecttimberltd/" target="_blank" style="color: #454530; font-weight: normal; text-decoration: none;">
                                                <img src="https://www.stg-projecttimbercom-staging.kinsta.cloud/wp-content/themes/theTimber/assets/images/social-logos/v2/pinterest-v2.svg" alt="Pinterest" width="24" height="24" border="0" style="font-size: 14px; font-weight: bold; text-transform: capitalize; vertical-align: middle; margin-right: 10px; max-width: 100%; outline: none; text-decoration: none; display: block; border: 0; width: 24px; height: 24px;">
                                            </a>
                                        </td>
                                        <td style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 0 6px;'>
                                            <a href="https://www.instagram.com/projecttimber/" target="_blank" style="color: #454530; font-weight: normal; text-decoration: none;">
                                                <img src="https://www.stg-projecttimbercom-staging.kinsta.cloud/wp-content/themes/theTimber/assets/images/social-logos/v2/insta-v2.svg" alt="Instagram" width="24" height="24" border="0" style="font-size: 14px; font-weight: bold; text-transform: capitalize; vertical-align: middle; margin-right: 10px; max-width: 100%; outline: none; text-decoration: none; display: block; border: 0; width: 24px; height: 24px;">
                                            </a>
                                        </td>
                                        <td style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 0 6px;'>
                                            <a href="https://www.youtube.com/@projecttimber" target="_blank" style="color: #454530; font-weight: normal; text-decoration: none;">
                                                <img src="https://www.stg-projecttimbercom-staging.kinsta.cloud/wp-content/themes/theTimber/assets/images/social-logos/v2/youtube-v2.svg" alt="YouTube" width="24" height="24" border="0" style="font-size: 14px; font-weight: bold; text-transform: capitalize; vertical-align: middle; margin-right: 10px; max-width: 100%; outline: none; text-decoration: none; display: block; border: 0; width: 24px; height: 24px;">
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>

                    <!-- Support message -->
                    <table cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
                        <tr>
                            <td align="center" style="padding: 0 10px 20px; font-family: 'Work Sans', Arial, sans-serif; font-size: 14px; color: #333333; line-height: 1.6; text-align: center;">
                                If you have any questions, feedback, or concerns regarding your purchase or anything else, please don't hesitate to reach out to us. We're here to assist you every step of the way.
                            </td>
                        </tr>
                    </table>

                    <!--
                        Contact info row.
                        FIXED: removed display:flex from <tr> and display:inline-flex + gap
                        from <td> — both are ignored by Outlook and Gmail web, causing the
                        icon and text to stack vertically.
                        Each contact item is now a nested two-cell table (icon | text) so
                        they sit side by side reliably in every client.
                    -->
                    <table cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
                        <tr>

                            <!-- Email contact -->
                            <td align="center" valign="middle" width="50%" style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 0 8px 16px 0;'>
                                <table cellpadding="0" cellspacing="0" border="0" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; margin: 0 auto;" align="center">
                                    <tr>
                                        <td valign="middle" style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 0 8px 0 0;'>
                                            <img src="https://www.stg-projecttimbercom-staging.kinsta.cloud/wp-content/themes/theTimber/assets/images/social-logos/v2/envelope.svg" alt="" width="18" oncontextmenu="return false;" height="18" border="0" style="font-size: 14px; font-weight: bold; text-transform: capitalize; vertical-align: middle; margin-right: 10px; max-width: 100%; outline: none; text-decoration: none; display: block; border: 0; width: 18px; height: 18px;">
                                        </td>
                                        <td valign="middle" style="padding: 0 12px 0 0; font-family: 'Work Sans', Arial, sans-serif; font-size: 13px; color: #333333; white-space: nowrap;">
                                            <a href="mailto:care@projecttimber.co.uk" style="color: #333333; text-decoration: none; font-weight: bold;">
                                                care@projecttimber.co.uk
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                            </td>

                            <!-- Phone contact -->
                            <td align="center" valign="middle" width="50%" style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 0 0 16px 8px;'>
                                <table cellpadding="0" cellspacing="0" border="0" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; margin: 0 auto;" align="center">
                                    <tr>
                                        <td valign="middle" style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; padding: 0 8px 0 0;'>
                                        <a href="https://www.projecttimber.com" target="_blank" style="color: #333333; text-decoration: underline; font-weight: normal;">
                                            <img src="https://www.stg-projecttimbercom-staging.kinsta.cloud/wp-content/themes/theTimber/assets/images/social-logos/v2/phone.svg" alt="" width="18" height="18" border="0" style="font-size: 14px; font-weight: bold; text-transform: capitalize; vertical-align: middle; margin-right: 10px; max-width: 100%; outline: none; text-decoration: none; display: block; border: 0; width: 18px; height: 18px;">
                                        </a>
    
                                    </td>
                                        <td valign="middle" style="padding: 0 12px 0 0; font-family: 'Work Sans', Arial, sans-serif; font-size: 13px; color: #333333; font-weight: bold; white-space: nowrap;">
                                            01777 801215
                                        </td>
                                    </tr>
                                </table>
                            </td>

                        </tr>
                    </table>

                    <!-- Terms & copyright -->
                    <table cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
                        <tr>
                            <td align="center" style="padding: 12px; font-family: 'Work Sans', Arial, sans-serif; font-size: 13px; color: #333333; line-height: 1.6; text-align: center;">
                                <p style='font-family: "Work Sans",Arial,sans-serif; font-size: 15px; margin: 0 0 6px;'>
                                    Please review our
                                    <a href="https://www.projecttimber.com/terms/" target="_blank" style="color: #333333; text-decoration: underline; font-weight: normal;">
                                        Terms and Conditions
                                    </a>
                                    for more details about your purchase.
                                </p>
                                <p style='font-family: "Work Sans",Arial,sans-serif; margin: 0; font-size: 13px; color: #333333;'>
                                    Project Timber © 2026                                </p>
                            </td>
                        </tr>
                    </table>

                </td>
            </tr>
        </table>

    </td>
</tr>
<!-- / Footer --></table></td></tr></table></td></tr></tbody></table></td></tr></table></body></html>
