<?php
class Hoptoad
{
  public static function errorHandler($code, $message)
  {
    if ($code == E_STRICT) return;
    
    Hoptoad::notifyHoptoad(HOPTOAD_API_KEY, $message, null, 2);
  }
  
  public static function exceptionHandler($exception)
  {
    Hoptoad::notifyHoptoad(HOPTOAD_API_KEY, $exception->getMessage(), null, 2);
  }
  
  public static function notifyHoptoad($api_key, $message, $error_class=null, $offset=1)
  {
    $lines = array_slice(Hoptoad::tracer(), $offset);
 
    if (isset($_SESSION)) {
      $session = array('key' => session_id(), 'data' => $_SESSION);
    } else {
      $session = array();
    }
 
    $body = array(
      'api_key' => $api_key,
      'error_class' => $error_class,
      'error_message' => $message,
      'backtrace' => $lines,
      'request' => array("params" => $_REQUEST, "url" => "http://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}"),
      'session' => $session,
      'environment' => $_SERVER
    );
	require_once(dirname(__FILE__) . "/spyc.php");
	$yaml = Spyc::YAMLDump(array("notice" => $body),4,60);
    // $req->setBody($yaml);
    // $req->sendRequest();

	/////////////////////////////////////////
	
	$curlHandle = curl_init(); // init curl

    // cURL options
    curl_setopt($curlHandle, CURLOPT_URL, 'http://hoptoadapp.com/notices/'); // set the url to fetch
    curl_setopt($curlHandle, CURLOPT_POST, 1);	
    curl_setopt($curlHandle, CURLOPT_TIMEOUT, 10); // time to wait in seconds
	curl_setopt($curlHandle, CURLOPT_POSTFIELDS,  $yaml);
	curl_setopt($curlHandle, CURLOPT_HTTPHEADER, array("Accept: text/xml, application/xml", "Content-type: application/x-yaml"));
	

    $content = curl_exec($curlHandle); // Make the call for sending the SMS
    curl_close($curlHandle); // Close the connection 
    
	
	////////////////////////////////////////
  }
  
  public static function tracer()
  {
    $lines = Array();
 
    $trace = debug_backtrace();
    
    $indent = '';
    $func = '';
    
    foreach($trace as $val) {
      if (!isset($val['file']) || !isset($val['line'])) continue;
      
      $line = $val['file'] . ' on line ' . $val['line'];
    
      if ($func) $line .= ' in function ' . $func;
      $func = $val['function'];
      $lines[] = $line;
    }
    return $lines;
  }
}