<?php
include_once('RepositoryException.php');

class RepositoryConnection {

  const COOKIE_LOCATION = 'fedora_cookie';
  private $config;
  private $cookieFile;
  private $curlContext = NULL;
  
  public function __construct(RepositoryConfig $config) {
    $this->config = $config;
    $this->cookieFile = tempnam(sys_get_temp_dir(), 'curlcookie');
    $this->getCurlContext();
    
    // see if we have any cookies in the session already
    if(isset($_SESSION[$this::COOKIE_LOCATION])) {
      file_put_contents($this->cookieFile, $_SESSION[$this::COOKIE_LOCATION]);
    }
  }
  
  public function __destruct() {
    // before we go, save our fedora session cookie to the browsers session
    if(isset($_SESSION)) {
      $SESSION[$this::COOKIE_LOCATION] = file_get_contents($this->cookieFile);
    }
    
    // close our curl context
    curl_close($this->curlContext);
  }
  
  private function getCurlContext() {
    if (function_exists("curl_init") && function_exists("curl_setopt")) {
      $this->curlContext = curl_init();
      curl_setopt($this->curlContext, CURLOPT_SSL_VERIFYPEER, $this->config->verifyPeer);
      curl_setopt($this->curlContext, CURLOPT_SSL_VERIFYHOST, $this->config->verifyHost);
      curl_setopt($this->curlContext, CURLOPT_FAILONERROR, FALSE);
      curl_setopt($this->curlContext, CURLOPT_TIMEOUT, $this->config->timeout);
      curl_setopt($this->curlContext, CURLOPT_USERAGENT, $this->config->userAgent);
      curl_setopt($this->curlContext, CURLOPT_COOKIEFILE, $this->cookieFile);
      curl_setopt($this->curlContext, CURLOPT_COOKIEJAR, $this->cookieFile);
      curl_setopt($this->curlContext, CURLOPT_FOLLOWLOCATION, 1); // allow redirects
      curl_setopt($this->curlContext, CURLOPT_RETURNTRANSFER, TRUE); // return to variable
      curl_setopt($this->curlContext, CURLOPT_HEADER, TRUE);
    }
    else {
      throw new RepositoryCurlException('cURL PHP Module must to enabled.', 0);
    }
  }
  
  private function buildUrl($url) {
    $user = $this->config->username;
    $pass = $this->config->password;
    
    if(isset($this->config->username)) {
      // set the username and password (should we have an option to not send any?)
      curl_setopt($this->curlContext, CURLOPT_USERPWD, "$user:$pass");
    }
    
    $fullurl = $this->config->url . $url;
    curl_setopt($this->curlContext, CURLOPT_URL, $fullurl);
  }
  
  private function doRequest() {
    $curl_response = curl_exec($this->curlContext);
    
    // since we are using exceptions we trap curl error 
    // codes and toss an exception, here is a good error
    // code reference.
    // http://curl.haxx.se/libcurl/c/libcurl-errors.html
    $error_code = curl_errno($this->curlContext);
    $error_string = curl_error($this->curlContext);
    if($error_code != 0) {
      throw new RepositoryCurlException($error_string, $error_code);
    }
    
    $info = curl_getinfo($this->curlContext);
    
    $response = array();
    $response['code'] = $info['http_code'];
    $response['headers'] = substr($curl_response, 0, $info['header_size']-1);
    $response['content'] = substr($curl_response, $info['header_size']);
    
    // We do some ugly stuff here to strip the error string out
    // of the HTTP headers, since curl doesn't provide any helper.
    $http_error_string = explode("\n", $response['headers'],2);
    $http_error_string = substr($http_error_string[0], 13);
    $http_error_string = trim($http_error_string);
    
    // throw an exception if this isn't a 2XX response
    if(!preg_match("/^2/",$info['http_code'])) {
      throw new RepositoryHttpErrorException($http_error_string, $info['http_code']);
    }
    return $response;
  }
  
  function httpPostRequest($url, $post) {
    curl_setopt($this->curlContext, CURLOPT_POST, TRUE);
    curl_setopt($this->curlContext, CURLOPT_POSTFIELDS, "$post");
    $this->buildUrl($url);
    $results = $this->doRequest();
    curl_setopt($this->curlContext, CURLOPT_POST, FALSE);
    return $results;
  }
  
  function httpPutRequest($url, $file) {
    curl_setopt($this->curlContext, CURLOPT_PUT, TRUE);
    curl_setopt($this->curlContext, CURLOPT_INFILE, $file);
    /* TODO this might bite us in the ass for files over 2gb */
    curl_setopt($this->curlContext, CURLOPT_INFILESIZE, filesize($file));
    $this->buildUrl($url);
    $results = $this->doRequest();
    curl_setopt($this->curlContext, CURLOPT_PUT, FALSE);
    return $results;
  }
  
  function httpGetRequest($url) {
    curl_setopt($this->curlContext, CURLOPT_HTTPGET, TRUE);
    $this->buildUrl($url);
    $results = $this->doRequest();
    curl_setopt($this->curlContext, CURLOPT_HTTPGET, FALSE);
    return $results;
  }
}