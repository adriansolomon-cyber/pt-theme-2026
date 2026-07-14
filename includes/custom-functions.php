<?php 

function promo_cacl($number, $percent) {	
	return number_format(($number - ($percent / 100) * $number)); 
}

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
            "api-auth-accountid: 4cd885bc-f1ee-41ca-9a22-e192599d5920",
            "api-auth-applicationkey: eb149c74-a32a-764c-84e8-ce42b719c02c",
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
            "api-auth-accountid: 4cd885bc-f1ee-41ca-9a22-e192599d5920",
            "api-auth-applicationkey: eb149c74-a32a-764c-84e8-ce42b719c02c",
            "cache-control: no-cache",
            "content-type: application/json"
          ),
        ));
    }

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {

        $subject = 'Dear System Push Error';

        $headers = array('Content-Type: text/html; charset=UTF-8');
        $headers[] = 'From: Project Timber <sales@projecttimber.com>';
        $headers[] = 'Reply-To: <sales@projecttimber.com>';

        wp_mail( 'davecanilao@projecttimber.co.uk, laurence@projecttimber.co.uk, carlos.tandal@projecttimber.co.uk, william.walton@projecttimber.co.uk', $subject, $fields.$err.$url.print($fields), $headers); 

        return 'error';

    }  else {

      $subject = 'Dear System Push';

      $headers = array('Content-Type: text/html; charset=UTF-8');
      $headers[] = 'From: Project Timber <sales@projecttimber.com>';
      $headers[] = 'Reply-To: <sales@projecttimber.com>';

    // wp_mail( 'carlos.tandal@projecttimber.co.uk,laurence@projecttimber.co.uk', $subject, $fields.$err.$url.print($fields), $headers); 
      
        return json_decode($response);
    }
} 