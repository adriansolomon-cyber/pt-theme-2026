<?php
include_once('../../../../wp-config.php');

$orderid = $order_id;

//$orderid = $_GET['orderid'];

// dear system API
function dear_system_authorization($url, $method, $fields = '')
{
    $curl = curl_init();

    if ($fields) {
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

        $subject = 'Dear System Push Error - Cancelled';

        $headers = array('Content-Type: text/html; charset=UTF-8');
        $headers[] = 'From: Project Timber <sales@projecttimber.com>';
        $headers[] = 'Reply-To: <sales@projecttimber.com>';

        wp_mail('davecanilao@projecttimber.co.uk', $subject, $err, $headers);
    } else {

        $subject = 'Dear System Push - Cancelled';

        $headers = array('Content-Type: text/html; charset=UTF-8');
        $headers[] = 'From: Project Timber <sales@projecttimber.com>';
        $headers[] = 'Reply-To: <sales@projecttimber.com>';

        wp_mail('davecanilao@projecttimber.co.uk', $subject, $response, $headers);

        return json_decode($response);
    }
}


// $invoice_num = $wpdb->get_results('SELECT * FROM wp_postmeta WHERE post_id = "' . $orderid . '" AND meta_key = "_pip_invoice_number"', OBJECT)[0];
$skuid_ = get_post_meta( $orderid, '_order_number_formatted', true );
// $RDM         = get_post_meta( $orderid, '_order_number_formatted', true );

// if ($invoice_num) {
//     $skuid_ = get_post_meta($orderid, '_pip_invoice_number', true);
// } else {
//     $skuid_ = $RDM;
// }

$checkSale = dear_system_authorization('https://inventory.dearsystems.com/ExternalApi/v2/SaleList?search=' . $skuid_ . '&OrderStatus=AUTHORISED', 'GET');

if ($checkSale->Total == 1) {

    $voidSales = dear_system_authorization('https://inventory.dearsystems.com/ExternalApi/v2/sale?ID=' . $checkSale->SaleList[0]->SaleID . '&Void=True', 'DELETE');
}

// header('Content-disposition: attachment; filename="Sales-Already-delete-'.$skuid_.'-'.date("d-j-Y-h-i-s").'.xml"');
// header('Content-type: "text/xml"; charset="utf8"');
// readfile('project-timber.xml');
// exit();