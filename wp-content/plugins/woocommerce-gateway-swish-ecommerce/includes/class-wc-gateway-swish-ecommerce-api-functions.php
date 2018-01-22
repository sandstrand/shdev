<?php
 define('CONTENT_TYPE', 'application/json');
 define('ENDPOINT', 'https://swicpc.bankgirot.se/swish-cpcapi/api/v1/');
function apiCall ($requestMethod, $entity, $body = null) { 
 $curl = curl_init(ENDPOINT . $entity);
 $options = array(
  'Content-Type: '. CONTENT_TYPE .'',
 );
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
  curl_setopt($curl, CURLOPT_HTTPHEADER, $options);
  curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $requestMethod);
  curl_setopt($curl, CURLOPT_CAINFO, dirname(__DIR__) . "/certificates/SwishTLS.pem");
  curl_setopt($curl, CURLOPT_SSLCERT, SWISH_SSL_PATH);  
  curl_setopt($curl, CURLOPT_HEADER, true);
 if ($requestMethod == 'POST' || $requestMethod == 'PUT') {
  curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
 } 
 $curlResponse = curl_exec($curl);
 if(curl_error($curl)){
  WC_Gateway_Swish_Ecommerce::log("curl returned error: (".curl_errno($curl).")". curl_error($curl));
  }
 $responseCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
 $header_len = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
 $header = substr($curlResponse, 0, $header_len);
 $headers = array("plain_text"=>$header,"http_code"=>$responseCode);
 $body = substr($curlResponse, $header_len);
 $result = array("headers"=>$headers, "body"=>json_decode($body,true));
 return json_encode($result);
}
function apiCallNSS ($requestMethod, $entity, $body = null) { 
 $curl = curl_init(ENDPOINT . $entity);
 $options = array(
  'Content-Type: '. CONTENT_TYPE .'',
 );
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
  curl_setopt($curl, CURLOPT_HTTPHEADER, $options);
  curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $requestMethod);
  curl_setopt($curl, CURLOPT_SSLCERT, SWISH_SSL_PATH);
  curl_setopt($curl, CURLOPT_HEADER, true);
 if ($requestMethod == 'POST' || $requestMethod == 'PUT') {
  curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
 } 
 $curlResponse = curl_exec($curl);
  if(curl_error($curl)){
  WC_Gateway_Swish_Ecommerce::log("curl returned error: (".curl_errno($curl).")". curl_error($curl));
  }
 $responseCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
 $header_len = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
 $header = substr($curlResponse, 0, $header_len);
 $headers = array("plain_text"=>$header,"http_code"=>$responseCode);
 $body = substr($curlResponse, $header_len);
 $result = array("headers"=>$headers, "body"=>json_decode($body,true));
 return json_encode($result);
}
function get_headers_from_curl_response($response)
{
    $headers = array();
    $header_text = substr($response, 0, strpos($response, "\r\n\r\n"));
    foreach (explode("\r\n", $header_text) as $i => $line)
        if ($i === 0)
            $headers['http_code'] = $line;
        else
        {
            list ($key, $value) = explode(': ', $line);
            $headers[$key] = $value;
        }
    return $headers;
}
