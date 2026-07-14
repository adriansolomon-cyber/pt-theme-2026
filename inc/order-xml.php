<?php

include_once('../../../../wp-config.php');

$orderid = $order_id;

$invoice_num = get_post_meta( $orderid, '_order_number_formatted', true );
$totalCompositePrice = get_post_meta( $orderid, '_order_total', true );

// dear system API
function dear_system_authorization($url, $method, $fields = '') {
    $curl = curl_init();

    if($fields) {
        curl_setopt_array($curl, array(
          CURLOPT_URL => $url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => $method,
          CURLOPT_POSTFIELDS => $fields,
          CURLOPT_HTTPHEADER => array(
            "api-auth-accountid: " . ( defined('PT_DEAR_ACCOUNT_ID') ? PT_DEAR_ACCOUNT_ID : '' ) . "",
            "api-auth-applicationkey: " . ( defined('PT_DEAR_APP_KEY') ? PT_DEAR_APP_KEY : '' ) . "",
            "cache-control: no-cache",
            "content-type: application/json"
          ),
        ));
    } else {
        curl_setopt_array($curl, array(
          CURLOPT_URL => $url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => $method,
          CURLOPT_HTTPHEADER => array(
            "api-auth-accountid: " . ( defined('PT_DEAR_ACCOUNT_ID') ? PT_DEAR_ACCOUNT_ID : '' ) . "",
            "api-auth-applicationkey: " . ( defined('PT_DEAR_APP_KEY') ? PT_DEAR_APP_KEY : '' ) . "",
            "cache-control: no-cache",
            "content-type: application/json"
          ),
        ));
    }

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {

        $subject1 = 'Dear System Push Error';

        $headers1 = array('Content-Type: text/html; charset=UTF-8');
        $headers1[] = 'From: Project Timber <sales@projecttimber.com>';
        $headers1[] = 'Reply-To: <sales@projecttimber.com>';

       wp_mail( 'carlos.tandal@projecttimber.co.uk', $subject1, $fields.$err.$url, $headers1);

    }  else {

         $subject1 = 'Dear System Push';

         $headers1 = array('Content-Type: text/html; charset=UTF-8');
         $headers1[] = 'From: Project Timber <sales@projecttimber.com>';
         $headers1[] = 'Reply-To: <sales@projecttimber.com>';

         wp_mail( 'carlos.tandal@projecttimber.co.uk', $subject1, $response.$url, $headers1);

        return json_decode($response);
    }
}

global $wpdb;
$query_items = 'SELECT * FROM wp_woocommerce_order_items WHERE order_id = "'.$orderid.'"';
$meta_items = $wpdb->get_results($query_items, OBJECT);
$numItems = 0;

foreach ($meta_items as $index_items => $item) {

    $itemmetas = $wpdb->get_results('SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = "'.$item->order_item_id.'" ');

    foreach ($itemmetas as $index_itemmeta => $itemmeta) {

        if($itemmeta->meta_key == '_product_id') {

            $_product = wc_get_product( $itemmeta->meta_value);

            if( $_product->is_type( 'composite' ) ) {
                $productName[] = $_product->get_name();
            }

            if( $_product->is_type( 'bundle' ) ) {

                if(preg_match('/[0-9]{0,5} x [0-9]{0,5}/', $_product->get_name())) {
                    $productSize[] = $_product->get_name();
                    $productSizeItem = $_product->get_name();
                }
            }

            if(!$_product->is_type( 'bundle' ) && !$_product->is_type( 'composite' )) {

                $qty = $wpdb->get_results('SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = "'.$item->order_item_id.'" AND meta_key = "_qty"', OBJECT)[0];
                $productid = $wpdb->get_results('SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = "'.$item->order_item_id.'" AND meta_key = "_product_id"', OBJECT)[0];

                $partProduct = wc_get_product( $itemmeta->meta_value  );


                if($partProduct->get_sku() != '2571' && $partProduct->get_sku() != '2571-S' && $partProduct->get_sku() != '2571-A' &&
                   $partProduct->get_sku() != '2571-G' && $partProduct->get_sku() != '2571-WC' && $partProduct->get_sku() != '2571-TC' &&
                   $partProduct->get_sku() != '2571-RC' && $partProduct->get_sku() != '2571-F' && $partProduct->get_sku() != 'option-misc') {

                        $product_items[$productSizeItem][$partProduct->get_sku()]['qty'] = $qty->meta_value;
                        $product_items[$productSizeItem][$partProduct->get_sku()]['product_name'] = $partProduct->get_name();
                        $product_items[$productSizeItem][$partProduct->get_sku()]['price'] = $partProduct->get_price();
                        $product_items[$productSizeItem][$partProduct->get_sku()]['total'] = $qty->meta_value;

                    $numItems++;
                }
            }
        }
    }
}

$reference_num = $wpdb->get_results('SELECT * FROM wp_postmeta WHERE post_id = "'.$orderid.'" AND meta_key = "_trade_order_reference_num"',  OBJECT)[0];
$load_code = $wpdb->get_results('SELECT * FROM wp_postmeta WHERE post_id = "'.$orderid.'" AND meta_key = "_pip_load_code"', OBJECT)[0];
$driver_name = $wpdb->get_results('SELECT * FROM wp_postmeta WHERE post_id = "'.$orderid.'" AND meta_key = "_pip_driver_name"', OBJECT)[0];
$drop = $wpdb->get_results('SELECT * FROM wp_postmeta WHERE post_id = "'.$orderid.'" AND meta_key = "_pip_drop"', OBJECT)[0];
$fromdev = get_post_meta( $orderid, '_final_delivery_date', true );
$expectedDate = get_post_meta( $orderid, '_from_delivery_date', true );
$extra_option = $wpdb->get_results('SELECT * FROM wp_postmeta WHERE post_id = "'.$orderid.'" AND meta_key = "_pip_extras_or_option"', OBJECT)[0];
$sales_rep = $wpdb->get_results('SELECT * FROM wp_postmeta WHERE post_id = "'.$orderid.'" AND meta_key = "sales_agent_name"', OBJECT)[0];

$expectedDate_ = date("Y-m-d", strtotime($expectedDate));

$order = wc_get_order($orderid);
$order_data = $order->get_data(); // The Order data

echo '1';
$order_shipping_name = $order_data['billing']['first_name'].' '.$order_data['billing']['last_name'];
$order_shipping_company = $order_data['billing']['company'];
$order_shipping_address_1 = $order_data['billing']['address_1'];
$order_shipping_address_2 = $order_data['billing']['address_2'];
$order_shipping_city = $order_data['billing']['city'];
$order_shipping_state = $order_data['billing']['state'];
$order_shipping_postcode = $order_data['billing']['postcode'];
$order_shipping_country = 'GB';

$order_billing_phone = $order_data['billing']['phone'];
$order_shipping_email = $order_data['billing']['email'];
$order_company = $order_data['billing']['first_name']." ".$order_data['billing']['last_name'];echo '2';

$order_date_created = $order_data['date_created']->date('Y-m-d H:i:s');echo '3';

$skuid_ = $invoice_num;
// add total qty order
add_post_meta($orderid, '_total_qty_order', 1, true);

$checkUser = dear_system_authorization('https://inventory.dearsystems.com/ExternalApi/v2/customer?name='.rawurlencode($order_shipping_name), 'GET');
$checkSale = dear_system_authorization('https://inventory.dearsystems.com/ExternalApi/v2/SaleList?search='.$skuid_.'&OrderStatus=AUTHORISED', 'GET');

$newCustomer = '
{
  "Addresses": [
    {
      "Line1": "'.$order_shipping_address_1.'",
      "Line2": "'.$order_shipping_address_2.'",
      "City": "'.$order_shipping_city.'",
      "State": "'.$order_shipping_city.'",
      "Postcode": "'.$order_shipping_postcode.'",
      "Country": "'.$order_shipping_country.'",
      "Type": "Shipping",
      "DefaultForType": true
    },
  ],
  "Contacts": [
    {
      "Name": "'.$order_shipping_name.'",
      "Phone": "'.$order_billing_phone.'",
      "Email": "'.$order_shipping_email.'",
      "Default": true
    }
  ],
   "Name": "'.$order_shipping_name.'",
   "Currency": "GBP",
   "PaymentTerm": "30 days",
   "Discount": 0,
   "TaxRule": "No VAT",
   "Carrier": "8 Working Days",
   "Location": "Sutton Warehouse",
   "Comments": "Customer",
   "AccountReceivable": "610",
   "RevenueAccount": "200",
   "PriceTier": "Tier 1",
   "Status": "Active",
   "CreditLimit": 0
}';

$newSales = '
{
   "Customer": "'.$order_shipping_name.'",
   "Contact": "'.$order_shipping_name.'",
   "Phone": "'.$order_billing_phone.'",
   "OrderDate":"'.$order_date_created.'",
   "SaleOrderDate" : "'.$order_date_created.'",
   "SaleAccount":"711",
   "BillingAddress":{
   "Line1": "'.$order_shipping_address_1.'",
   "Line2": "'.$order_shipping_address_2.'",
   "City": "'.$order_shipping_city.'",
   "State": "'.$order_shipping_city.'",
   "Postcode": "'.$order_shipping_postcode.'",
   "Country": "'.$order_shipping_country.'"
   },
   "ShippingAddress":{
      "Line1": "'.$order_shipping_address_1.'",
    "Line2": "'.$order_shipping_address_2.'",
    "City": "'.$order_shipping_city.'",
    "State": "'.$order_shipping_city.'",
    "Postcode": "'.$order_shipping_postcode.'",
    "Country": "'.$order_shipping_country.'"
   },
   "ShippingNotes": "Expected Delivery '.$expectedDate_.'",
   "TaxRule":"No VAT",
   "TaxInclusive": "false",
   "Terms":"30 Days",
   "PriceTier":"Tier 1",
   "ShipBy" : "'.$expectedDate_.'",
   "Location":"Sutton Warehouse",
   "Note":"Extra Option '.$extra_option->meta_value.' ",
   "CustomerReference" : "'.$skuid_.'",
   "AutoPickPackShipMode":"NOPICK",
   "SalesRepresentative": "None",
   "Carrier": "8 Working Days",
   "CurrencyRate": "1",
   "SaleType" : "Advanced"
}';

if($checkSale->Total == 1) {

    $voidSales = dear_system_authorization('https://inventory.dearsystems.com/ExternalApi/v2/sale?ID='.$checkSale->SaleList[0]->SaleID.'&Void=True', 'DELETE');

    $createNewSales = dear_system_authorization('https://inventory.dearsystems.com/ExternalApi/v2/sale', 'POST', $newSales);

    // Sales Items
    $ProductNameItem = '';

    foreach($productName as $ProdIndex => $Prodname) {
        $ProductNameItem .= $Prodname .'-'. $ProdIndex[$ProdIndex]."; ";
    }

    $cntItem = 0;
    $lines_item = '';

    foreach ($product_items as $index_size => $size) {
        foreach($size as $index_item => $item) {
            $lines_item .= '{"SKU":"'.$index_item.'",';
            $lines_item .= '"Name": "'.addslashes($item['product_name']).'",';
            $lines_item .= '"Quantity": "'.$item['qty'].'",';
            $lines_item .= '"Price": "0",';
            $lines_item .= '"Tax": "0",';
            $lines_item .= '"TaxRule": "No VAT",';
            $lines_item .= '"Total" : "0" }';

            if($cntItem++ === $numItems) {
              $lines_item .= '';
            } else {
              $lines_item .= ',';
            }
        }
    }

     $newOrderItems = '
          {
            "SaleID": "'.$createNewSales->ID.'",
              "Memo": "'.$extra_option->meta_value.'",
              "Status": "AUTHORISED",
            "Lines": ['.$lines_item.'],
              "AdditionalCharges": [
                  {
                        "Description": "'.$ProductNameItem.' Total Price: '.$totalCompositePrice.'",
                        "Price": '.$totalCompositePrice.',
                        "Quantity": 1,
                        "Discount": 0,
                        "Tax": 0,
                        "Total": '.$totalCompositePrice.',
                        "TaxRule": "No VAT",
                        "Comment": ""
                    }
              ],
              "TotalBeforeTax": 0,
              "Tax": 0,
              "Total": 0
          }';



    $createNewOrderItems = dear_system_authorization('https://inventory.dearsystems.com/ExternalApi/v2/sale/order', 'POST', $newOrderItems);


    $cntItemv = 0;
    $lines_itemv = '';

    foreach ($product_items as $index_size => $size) {
        foreach($size as $index_item => $item) {
            $lines_itemv .= '{"SKU":"'.$index_item.'",';
            $lines_itemv .= '"Name": "'.addslashes($item['product_name']).'",';
            $lines_itemv .= '"Quantity": "'.$item['qty'].'",';
            $lines_itemv .= '"Price": "0",';
            $lines_itemv .= '"Tax": "0",';
            $lines_itemv .= '"TaxRule": "No VAT",';
            $lines_itemv .= '"Account": "200",';
            $lines_itemv .= '"Total" : "0" }';

            if($cntItemv++ === $numItems) {
              $lines_itemv .= '';
            } else {
              $lines_itemv .= ',';
            }
        }
    }

    $newInvoice = '
        {
           "SaleID": "'.$createNewSales->ID.'",
           "TaskID" : "00000000-0000-0000-0000-000000000000",
           "CombineAdditionalCharges": false,
           "Memo":"",
           "Status":"AUTHORISED",
           "InvoiceDate" : "'.$order_date_created.'",
           "InvoiceDueDate" : "'.$order_date_created.'",
           "CurrencyConversionRate": 1,
           "BillingAddressLine1": "'.$order_shipping_address_1.'",
           "BillingAddressLine2": "'.$order_shipping_address_2.'",
           "Lines":['.$lines_itemv.'],
           "AdditionalCharges":[
              {
                 "Description":"'.$ProductNameItem.' Total Price: '.$totalCompositePrice.'",
                 "Price":'.$totalCompositePrice.',
                 "Quantity":1,
                 "Discount":0,
                 "Tax":0,
                 "Total": '.$totalCompositePrice.',
                 "TaxRule":"No VAT",
                 "Account": "200",
                 "Comment":""
              }
           ],
           "TotalBeforeTax":"'.$totalCompositePrice.'",
           "Tax":0.0000,
           "Total":"'.$totalCompositePrice.'"
        }
      ';

      $createNewInvoicetems = dear_system_authorization('https://inventory.dearsystems.com/ExternalApi/v2/sale/invoice', 'POST', $newInvoice);

      $paymentInvoice = '
        {
          "TaskID": "'.$createNewInvoicetems->Invoices[0]->TaskID.'",
          "Type": "Payment",
          "Reference": "'.$skuid_.'",
          "Amount": "'.$totalCompositePrice.'",
          "DatePaid": "'.$order_date_created.'",
          "Account": "701",
          "CurrencyRate": 1
        }
      ';

      $createPaymentInvoice = dear_system_authorization('https://inventory.dearsystems.com/ExternalApi/v2/sale/payment', 'POST', $paymentInvoice);
}

if($checkSale->Total == 0) {

    if($checkUser->Total == 1) {

      $createNewSales = dear_system_authorization('https://inventory.dearsystems.com/ExternalApi/v2/sale', 'POST', $newSales);

       // Sales Items
    $ProductNameItem = '';

    foreach($productName as $ProdIndex => $Prodname) {
        $ProductNameItem .= $Prodname .'-'. $ProdIndex[$ProdIndex]."; ";
    }

    $cntItem = 0;
    $lines_item = '';

    foreach ($product_items as $index_size => $size) {
        foreach($size as $index_item => $item) {
            $lines_item .= '{"SKU":"'.$index_item.'",';
            $lines_item .= '"Name": "'.addslashes($item['product_name']).'",';
            $lines_item .= '"Quantity": "'.$item['qty'].'",';
            $lines_item .= '"Price": "0",';
            $lines_item .= '"Tax": "0",';
            $lines_item .= '"TaxRule": "No VAT",';
            $lines_item .= '"Total" : "0" }';

            if(++$cntItem === $numItems) {
              $lines_item .= '';
            } else {
              $lines_item .= ',';
            }
        }
    }

     $newOrderItems = '
          {
            "SaleID": "'.$createNewSales->ID.'",
              "Memo": "'.$extra_option->meta_value.'",
              "Status": "AUTHORISED",
            "Lines": ['.$lines_item.'],
              "AdditionalCharges": [
                  {
                        "Description": "'.$ProductNameItem.' Total Price: '.$totalCompositePrice.'",
                        "Price": '.$totalCompositePrice.',
                        "Quantity": 1,
                        "Discount": 0,
                        "Tax": 0,
                        "Total": '.$totalCompositePrice.',
                        "TaxRule": "No VAT",
                        "Comment": ""
                    }
              ],
              "TotalBeforeTax": 0,
              "Tax": 0,
              "Total": 0
          }';

    $createNewOrderItems = dear_system_authorization('https://inventory.dearsystems.com/ExternalApi/v2/sale/order', 'POST', $newOrderItems);

    $cntItemv = 0;
    $lines_itemv = '';

    foreach ($product_items as $index_size => $size) {
        foreach($size as $index_item => $item) {
            $lines_itemv .= '{"SKU":"'.$index_item.'",';
            $lines_itemv .= '"Name": "'.addslashes($item['product_name']).'",';
            $lines_itemv .= '"Quantity": "'.$item['qty'].'",';
            $lines_itemv .= '"Price": "0",';
            $lines_itemv .= '"Tax": "0",';
            $lines_itemv .= '"TaxRule": "No VAT",';
            $lines_itemv .= '"Account": "200",';
            $lines_itemv .= '"Total" : "0" }';

            if($cntItemv++ === $numItems) {
              $lines_itemv .= '';
            } else {
              $lines_itemv .= ',';
            }
        }
    }

    $newInvoice = '
        {
           "SaleID": "'.$createNewSales->ID.'",
           "TaskID" : "00000000-0000-0000-0000-000000000000",
           "CombineAdditionalCharges": false,
           "Memo":"",
           "Status":"AUTHORISED",
           "InvoiceDate" : "'.$order_date_created.'",
           "InvoiceDueDate" : "'.$order_date_created.'",
           "CurrencyConversionRate": 1,
           "BillingAddressLine1": "'.$order_shipping_address_1.'",
           "BillingAddressLine2": "'.$order_shipping_address_2.'",
           "Lines":['.$lines_itemv.'],
           "AdditionalCharges":[
              {
                 "Description":"'.$ProductNameItem.' Total Price: '.$totalCompositePrice.'",
                 "Price":'.$totalCompositePrice.',
                 "Quantity":1,
                 "Discount":0,
                 "Tax":0,
                 "Total": '.$totalCompositePrice.',
                 "TaxRule":"No VAT",
                 "Account": "200",
                 "Comment":""
              }
           ],
           "TotalBeforeTax":"'.$totalCompositePrice.'",
           "Tax":0.0000,
           "Total":"'.$totalCompositePrice.'"
        }
      ';

      $createNewInvoicetems = dear_system_authorization('https://inventory.dearsystems.com/ExternalApi/v2/sale/invoice', 'POST', $newInvoice);

      $paymentInvoice = '
        {
          "TaskID": "'.$createNewInvoicetems->Invoices[0]->TaskID.'",
          "Type": "Payment",
          "Reference": "'.$skuid_.'",
          "Amount": "'.$totalCompositePrice.'",
          "DatePaid": "'.$order_date_created.'",
          "Account": "701",
          "CurrencyRate": 1
        }
      ';

      $createPaymentInvoice = dear_system_authorization('https://inventory.dearsystems.com/ExternalApi/v2/sale/payment', 'POST', $paymentInvoice);

    } else {

      $createNewUser = dear_system_authorization('https://inventory.dearsystems.com/ExternalApi/v2/customer', 'POST', $newCustomer);
      $createNewSales = dear_system_authorization('https://inventory.dearsystems.com/ExternalApi/v2/sale', 'POST', $newSales);

       // Sales Items
        $ProductNameItem = '';

        foreach($productName as $ProdIndex => $Prodname) {
            $ProductNameItem .= $Prodname .'-'. $ProdIndex[$ProdIndex]."; ";
        }

        $cntItem = 0;
        $lines_item = '';

        foreach ($product_items as $index_size => $size) {
            foreach($size as $index_item => $item) {
                $lines_item .= '{"SKU":"'.$index_item.'",';
                $lines_item .= '"Name": "'.addslashes($item['product_name']).'",';
                $lines_item .= '"Quantity": "'.$item['qty'].'",';
                $lines_item .= '"Price": "0",';
                $lines_item .= '"Tax": "0",';
                $lines_item .= '"TaxRule": "No VAT",';
                $lines_item .= '"Total" : "0" }';

                if($cntItem++ === $numItems) {
                    $lines_item .= '';
                } else {
                    $lines_item .= ',';
                }
            }
        }

         $newOrderItems = '
              {
                "SaleID": "'.$createNewSales->ID.'",
                  "Memo": "'.$extra_option->meta_value.'",
                  "Status": "AUTHORISED",
                "Lines": ['.$lines_item.'],
                  "AdditionalCharges": [
                      {
                            "Description": "'.$ProductNameItem.' Total Price: '.$totalCompositePrice.'",
                            "Price": '.$totalCompositePrice.',
                            "Quantity": 1,
                            "Discount": 0,
                            "Tax": 0,
                            "Total": '.$totalCompositePrice.',
                            "TaxRule": "No VAT",
                            "Comment": ""
                        }
                  ],
                  "TotalBeforeTax": 0,
                  "Tax": 0,
                  "Total": 0
              }';


        $createNewOrderItems = dear_system_authorization('https://inventory.dearsystems.com/ExternalApi/v2/sale/order', 'POST', $newOrderItems);

        $cntItemv = 0;
        $lines_itemv = '';

        foreach ($product_items as $index_size => $size) {
            foreach($size as $index_item => $item) {
                $lines_itemv .= '{"SKU":"'.$index_item.'",';
                $lines_itemv .= '"Name": "'.addslashes($item['product_name']).'",';
                $lines_itemv .= '"Quantity": "'.$item['qty'].'",';
                $lines_itemv .= '"Price": "0",';
                $lines_itemv .= '"Tax": "0",';
                $lines_itemv .= '"TaxRule": "No VAT",';
                $lines_itemv .= '"Account": "200",';
                $lines_itemv .= '"Total" : "0" }';

                if($cntItemv++ === $numItems) {
                  $lines_itemv .= '';
                } else {
                  $lines_itemv .= ',';
                }
            }
        }


        $newInvoice = '
            {
               "SaleID": "'.$createNewSales->ID.'",
               "TaskID" : "00000000-0000-0000-0000-000000000000",
               "CombineAdditionalCharges": false,
               "Memo":"",
               "Status":"AUTHORISED",
               "InvoiceDate" : "'.$order_date_created.'",
               "InvoiceDueDate" : "'.$order_date_created.'",
               "CurrencyConversionRate": 1,
               "BillingAddressLine1": "'.$order_shipping_address_1.'",
               "BillingAddressLine2": "'.$order_shipping_address_2.'",
               "Lines":['.$lines_itemv.'],
               "AdditionalCharges":[
                  {
                     "Description":"'.$ProductNameItem.' Total Price: '.$totalCompositePrice.'",
                     "Price":'.$totalCompositePrice.',
                     "Quantity":1,
                     "Discount":0,
                     "Tax":0,
                     "Total": '.$totalCompositePrice.',
                     "TaxRule":"No VAT",
                     "Account": "200",
                     "Comment":""
                  }
               ],
               "TotalBeforeTax":"'.$totalCompositePrice.'",
               "Tax":0.0000,
               "Total":"'.$totalCompositePrice.'"
            }
          ';


          $createNewInvoicetems = dear_system_authorization('https://inventory.dearsystems.com/ExternalApi/v2/sale/invoice', 'POST', $newInvoice);

          $paymentInvoice = '
            {
              "TaskID": "'.$createNewInvoicetems->Invoices[0]->TaskID.'",
              "Type": "Payment",
              "Reference": "'.$skuid_.'",
              "Amount": "'.$totalCompositePrice.'",
              "DatePaid": "'.$order_date_created.'",
              "Account": "701",
              "CurrencyRate": 1
            }
          ';

          $createPaymentInvoice = dear_system_authorization('https://inventory.dearsystems.com/ExternalApi/v2/sale/payment', 'POST', $paymentInvoice);
    }
}