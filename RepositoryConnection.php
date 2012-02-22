<?php
include_once('RepositoryException.php');

class RepositoryConnection {

  const COOKIE_LOCATION = 'fedora_cookie';
  private $config;
  private $cookiefile;

  public function __destruct() {
    // before we go, save our fedora session cookie to the browsers session
    if(isset($_SESSION)) {
      $SESSION[$this::COOKIE_LOCATION] = file_get_contents($this->cookiefile);
    }
  }
  
  public function __construct(RepositoryConfig $config) {
    $this->config = $config;
    $this->cookiefile = tempnam(sys_get_temp_dir(), 'curlcookie');
    
    // see if we have any cookies in the session already
    if(isset($_SESSION[$this::COOKIE_LOCATION])) {
      file_put_contents($this->cookiefile, $_SESSION[$this::COOKIE_LOCATION]);
    }
  }
  
  private function getCurlContext(&$ch) {
    if (function_exists("curl_init") && function_exists("curl_setopt")) {
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->config->verifyPeer);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $this->config->verifyHost);
      curl_setopt($ch, CURLOPT_FAILONERROR, FALSE);
      curl_setopt($ch, CURLOPT_TIMEOUT, $this->config->timeout);
      curl_setopt($ch, CURLOPT_USERAGENT, $this->config->userAgent);
      curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookiefile);
      curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookiefile);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // allow redirects
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); // return to variable
      curl_setopt($ch, CURLOPT_HEADER, TRUE);
    }
    else {
      throw new RepositoryCurlException('cURL PHP Module must to enabled.', 0);
    }
  }
  
  private function buildUrl($url, &$ch) {
    $user = $this->config->username;
    $pass = $this->config->password;
    
    if(isset($this->config->username)) {
      // set the username and password (should we have an option to not send any?)
      curl_setopt($ch, CURLOPT_USERPWD, "$user:$pass");
    }
    
    $fullurl = $this->config->url . $url;
    curl_setopt($ch, CURLOPT_URL, $fullurl);
  }
  
  private function doRequest(&$ch) {
    $data = array();
    $curl_response = curl_exec($ch);
    
    // since we are using exceptions we trap curl error 
    // codes and toss an exception, here is a good error
    // code reference.
    // http://curl.haxx.se/libcurl/c/libcurl-errors.html
    $error_code = curl_errno($ch);
    $error_string = curl_error($ch);
    if($error_code != 0) {
      throw new RepositoryCurlException($error_string, $error_code);
    }
    
    $info = curl_getinfo($ch);
    
    $response = array();
    $response['code'] = $info['http_code'];
    $response['headers'] = substr($curl_response, 0, $info['header_size']-1);
    $response['content'] = substr($curl_response, $info['header_size']);
    
    // We do some ugly stuff here to strip the error string out
    // of the HTTP headers, since curl doesn't provide any helper.
    $http_error_string = explode("\n", $response['headers'],2);
    $http_error_string = substr($http_error_string[0], 13);
    $http_error_string = trim($http_error_string);
    
    curl_close($ch);
    
    // throw an exception if this isn't a 2XX response
    if(!preg_match("/^2/",$info['http_code'])) {
      throw new RepositoryHttpErrorException($http_error_string, $info['http_code']);
    }
    
    return $response;
  }
  
  function httpPostRequest($url, $post) {
    $this->getCurlContext($ch);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "$post");
    $this->buildUrl($url, $ch);
    return $results = $this->doRequest($ch);
  }
  
  function httpPutRequest($url, $file) {
    $this->getCurlContext($ch);
    curl_setopt($ch, CURLOPT_PUT, TRUE);
    curl_setopt($ch, CURLOPT_INFILE, $file);
    /* TODO this might bite us in the ass for files over 2gb */
    curl_setopt($ch, CURLOPT_INFILESIZE, filesize($file));
    $this->buildUrl($url, $ch);
    return $results = $this->doRequest($ch);
  }
  
  function httpGetRequest($url) {
    $this->getCurlContext($ch);
    curl_setopt($ch, CURLOPT_HTTPGET, TRUE);
    $this->buildUrl($url, $ch);
    return $results = $this->doRequest($ch);
  }
}