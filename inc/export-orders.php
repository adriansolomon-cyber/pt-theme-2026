<?php 

include_once('../../../../wp-config.php');
include_once('../../../../wp-includes/wp-db.php');

define('WP_DEBUG', true);


// Report simple running errors
error_reporting(E_ERROR | E_WARNING | E_PARSE);

global $wpdb;

$start_date = 'DATE_FORMAT( DATE_ADD(NOW(), INTERVAL -1 DAY), \'%Y-%m-%d 00:00:00\')';
$end_date 	= 'DATE_FORMAT( DATE_ADD(NOW(), INTERVAL -1 DAY), \'%Y-%m-%d 23:59:59\')';

$result = $wpdb->get_results("SELECT * FROM wp_posts WHERE post_status IN(
			        'wc-processing',
			        'wc-planned',
			        'wc-unplanned',
			        'wc-delivery-date'
			        ) AND post_type = 'shop_order' AND post_date >= {$start_date} AND post_date < {$end_date} ");
			        
			       

foreach($result as $id => $orderid) {
    
global $wpdb;
$query_items = 'SELECT * FROM wp_woocommerce_order_items WHERE order_id = "'.$orderid.'"';
$meta_items = $wpdb->get_results($query_items, OBJECT);
$product_extra = "";

foreach ($meta_items as $indxitem => $item) {

	if($indxitem == 0) {
		$product_name[] = $item->order_item_name;
	}

	if($indxitem == 1) {
		$product_name[] = $item->order_item_name;		
	}

	$itemmetas = $wpdb->get_results('SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = "'.$item->order_item_id.'" ');

	foreach ($itemmetas as $indxmeta => $itemmeta) {		
		
		if($indxitem != 0 && $indxitem != 1 && $item->order_item_name != 'None') {
		    
			if($itemmeta->meta_key == '_bundled_item_id') {	

				$qty = $wpdb->get_results('SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = "'.$item->order_item_id.'" AND meta_key = "_qty"', OBJECT)[0];	
				$productid = $wpdb->get_results('SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = "'.$item->order_item_id.'" AND meta_key = "_product_id"', OBJECT)[0];	

				$product = wc_get_product( $productid->meta_value  );				

				//$product_items[$product->get_sku()][] = $qty->meta_value;

			} else if ($itemmeta->meta_key == '_component_priced_individually') {
				$qty = $wpdb->get_results('SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = "'.$item->order_item_id.'" AND meta_key = "_qty"', OBJECT)[0];	
				$productid = $wpdb->get_results('SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = "'.$item->order_item_id.'" AND meta_key = "_product_id"', OBJECT)[0];	

				$product = wc_get_product( $productid->meta_value  );				

				$product_extra .= $item->order_item_name.", ";
				
			}  else if($itemmeta->meta_key == '_product_id') {	
		    
    		   	$qty = $wpdb->get_results('SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = "'.$item->order_item_id.'" AND meta_key = "_qty"', OBJECT)[0];	
    		    $productid = $wpdb->get_results('SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = "'.$item->order_item_id.'" AND meta_key = "_product_id"', OBJECT)[0];	
    				
    			$product = wc_get_product( $productid->meta_value  );				
                    
                    
    		    if(preg_match('/^[a-zA-Z0-9]+$/', $product->get_sku())) {  
    		        $product_items[$product->get_sku()][] = $qty->meta_value;
                }
    		    
		    }
			
		} else {
		    
		    if($itemmeta->meta_key == '_product_id') {	
		    
    		   	$qty = $wpdb->get_results('SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = "'.$item->order_item_id.'" AND meta_key = "_qty"', OBJECT)[0];	
    		    $productid = $wpdb->get_results('SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = "'.$item->order_item_id.'" AND meta_key = "_product_id"', OBJECT)[0];	
    				
    			$product = wc_get_product( $productid->meta_value  );				
                    
                    
    		    if(preg_match('/^[a-zA-Z0-9]+$/', $product->get_sku())) {  
    		        $product_items[$product->get_sku()][] = $qty->meta_value;
                }
		    }
		}
	}
}

$reference_num = $wpdb->get_results('SELECT * FROM wp_postmeta WHERE post_id = "'.$orderid.'" AND meta_key = "_trade_order_reference_num"',  OBJECT)[0];
$invoice_num = $wpdb->get_results('SELECT * FROM wp_postmeta WHERE post_id = "'.$orderid.'" AND meta_key = "_pip_invoice_number"', OBJECT)[0];	
$RDM = get_post_meta( $orderid, '_order_number_formatted', true );
$load_code = $wpdb->get_results('SELECT * FROM wp_postmeta WHERE post_id = "'.$orderid.'" AND meta_key = "_pip_load_code"', OBJECT)[0];
$driver_name = $wpdb->get_results('SELECT * FROM wp_postmeta WHERE post_id = "'.$orderid.'" AND meta_key = "_pip_driver_name"', OBJECT)[0];
$drop = $wpdb->get_results('SELECT * FROM wp_postmeta WHERE post_id = "'.$orderid.'" AND meta_key = "_pip_drop"', OBJECT)[0];			
$fromdev = $wpdb->get_results('SELECT * FROM wp_postmeta WHERE post_id = "'.$orderid.'" AND meta_key = "_final_delivery_date"', OBJECT)[0];		
$extra_option = $wpdb->get_results('SELECT * FROM wp_postmeta WHERE post_id = "'.$orderid.'" AND meta_key = "_pip_extras_or_option"', OBJECT)[0];	

$order = wc_get_order( $orderid );
$order_data = $order->get_data(); // The Order data

$order_shipping_first_name = $order_data['shipping']['first_name'];
$order_shipping_last_name = $order_data['shipping']['last_name'];
$order_shipping_company = $order_data['shipping']['company'];
$order_shipping_address_1 = $order_data['shipping']['address_1'];
$order_shipping_address_2 = $order_data['shipping']['address_2'];
$order_shipping_city = $order_data['shipping']['city'];
$order_shipping_state = $order_data['shipping']['state'];
$order_shipping_postcode = $order_data['shipping']['postcode'];
$order_shipping_country = $order_data['shipping']['country'];
$order_billing_phone = $order_data['billing']['phone'];
$order_company = $order_data['billing']['first_name']." ".$order_data['billing']['last_name'];
$order_date_created = $order_data['date_created']->date('Y-m-d H:i:s');

$doc = new DOMDocument();
$doc->formatOutput = true;

if($invoice_num->meta_value) {
    $skuid_ = get_post_meta( $orderid, '_pip_invoice_number', true);
} else {
    $skuid_ = $RDM;
}			

$r = $doc->createElement( "DATACOLLECTION" );
$doc->appendChild( $r );
 
	$b = $doc->createElement( "DATA" );
	 
		$orderid1 = $doc->createElement( "ORDERID" );
		$orderid1->appendChild(
			$doc->createTextNode($skuid_.'/'.get_post_meta( $orderid, '_trade_order_reference_num', true) )
		);
		$b->appendChild( $orderid1 );
		 
		$createdate = $doc->createElement( "CREATEDATE" );
		$createdate->appendChild(
			$doc->createTextNode( $order_date_created )
		);
		$b->appendChild( $createdate );
		 
		$notes = $doc->createElement( "NOTES" );
		$notes->appendChild(
			$doc->createTextNode( $product_name[1].' '.$product_name[0])
		);
		$b->appendChild( $notes );

		$skedate = $doc->createElement( "SCHEDULEDDATE" );
		$skedate->appendChild(
			$doc->createTextNode($fromdev->meta_value)
		);
		$b->appendChild( $skedate );

		$stopnum = $doc->createElement( "STOPNUMBER" );
		$stopnum->appendChild(
			$doc->createTextNode($drop->meta_value)
		);
		$b->appendChild( $stopnum);

		$extras = $doc->createElement( "EXTRAS" );
		$extras->appendChild(
			$doc->createTextNode($product_extra.$extra_option->meta_value)
		);
		$b->appendChild( $extras );

		$targetcomp = $doc->createElement( "TARGETCOMPANYNAME" );
		$targetcomp->appendChild(
			$doc->createTextNode($order_company)
		);
		$b->appendChild( $targetcomp );

		$loadid = $doc->createElement( "loadid" );
		$loadid->appendChild(
			$doc->createTextNode($load_code->meta_value)
		);
		$b->appendChild( $loadid );

		$driver = $doc->createElement( "DRIVER" );
		$driver->appendChild(
			$doc->createTextNode($driver_name->meta_value)
		);
		$b->appendChild( $driver );

		$drivenote = $doc->createElement( "DRIVERNOTES" );
		$drivenote->appendChild(
			$doc->createTextNode('')
		);
		$b->appendChild( $drivenote );

		$specialnote = $doc->createElement( "SPECIALNOTES" );
		$specialnote->appendChild(
			$doc->createTextNode('')
		);
		$b->appendChild( $specialnote );

		$contact = $doc->createElement( "CONTACT" );

			$contacta = $doc->createElement( "STREET1" );
			$contacta->appendChild(
				$doc->createTextNode( $order_shipping_address_1 )
			);
			$contact->appendChild($contacta);

			$contactb = $doc->createElement( "STREET2" );
			$contactb->appendChild(
				$doc->createTextNode( $order_shipping_address_2 )
			);
			$contact->appendChild($contactb);

			$contactb = $doc->createElement( "CITY" );
			$contactb->appendChild(
				$doc->createTextNode( $order_shipping_city )
			);
			$contact->appendChild($contactb);

			$contactb = $doc->createElement( "STATE" );
			$contactb->appendChild(
				$doc->createTextNode( $order_shipping_state )
			);
			$contact->appendChild($contactb);

			$contactb = $doc->createElement( "ZIP" );
			$contactb->appendChild(
				$doc->createTextNode( $order_shipping_postcode )
			);
			$contact->appendChild($contactb);

			$contactb = $doc->createElement( "CONTACT1NAME" );
			$contactb->appendChild(
				$doc->createTextNode( $order_shipping_first_name )
			);
			$contact->appendChild($contactb);

			$contactb = $doc->createElement( "CONTACT1PHONE" );
			$contactb->appendChild(
				$doc->createTextNode( $order_billing_phone )
			);
			$contact->appendChild($contactb);

		$b->appendChild( $contact );

		$lines = $doc->createElement( "LINES" );

		$cntItem = 0;
		foreach ($product_items as $key => $value) {

			if($value[1]) {

				foreach ($value as $key_index => $value_item) {

					$cntItem = $cntItem + 1;

					$line = $doc->createElement( "LINE" );

					$line1 = $doc->createElement( "ORDERLINE" );
					$line1->appendChild(
						$doc->createTextNode( $cntItem )
					);
					$line->appendChild($line1);

					$line2 = $doc->createElement( "SKU" );
					$line2->appendChild(
						$doc->createTextNode( $key )
					);
					$line->appendChild($line2);

					$line3 = $doc->createElement( "INPUTQTY" );
					$line3->appendChild(
						$doc->createTextNode( $value[0] )
					);
					$line->appendChild($line3);

					$line4 = $doc->createElement( "INPUTUOM" );
					$line4->appendChild(
						$doc->createTextNode( "EACH" )
					);
					$line->appendChild($line4);

					$lines->appendChild( $line );				
					
				}				

			} else {

			$cntItem = $cntItem + 1;

			$line = $doc->createElement( "LINE" );
			
				$line1 = $doc->createElement( "ORDERLINE" );
				$line1->appendChild(
					$doc->createTextNode( $cntItem )
				);
				$line->appendChild($line1);

				$line2 = $doc->createElement( "SKU" );
				$line2->appendChild(
					$doc->createTextNode( $key )
				);
				$line->appendChild($line2);

				$line3 = $doc->createElement( "INPUTQTY" );
				$line3->appendChild(
					$doc->createTextNode( $value[0] )
				);
				$line->appendChild($line3);

				$line4 = $doc->createElement( "INPUTUOM" );
				$line4->appendChild(
					$doc->createTextNode( "EACH" )
				);
				$line->appendChild($line4);

			$lines->appendChild( $line );

			}
		}

		$b->appendChild( $lines );
	 
	$r->appendChild( $b );
    
}

echo $doc->saveXML();


header('Content-disposition: attachment; filename="project-timber-'.$skuid_.date("d-j-Y-h-i-s").'.xml"');
header('Content-type: "text/xml"; charset="utf8"');
readfile('project-timber.xml');
exit();