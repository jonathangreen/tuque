<?php

class RepositoryConnection {

  private $config;

  public function __construct($config) {
    $this->config = $config;
  }
  
  private function getCurlContext() {
    if (function_exists("curl_init") && function_exists("curl_setopt")) {
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->config->verifyPeer);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $this->config->verifyHost);
      curl_setopt($ch, CURLOPT_FAILONERROR, TRUE);
      curl_setopt($ch, CURLOPT_TIMEOUT, $this->config->timeout);
      curl_setopt($ch, CURLOPT_USERAGENT, $this->config->userAgent);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // allow redirects
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); // return to variable
    }
    else {
      throw new Exception('cUrl PHP Module must to enabled.');
    }
    
    return $ch;
  }
  
  private function buildUrl($url, $ch) {
    $user = $this->config->username;
    $pass = $this->config->password;
    
    // set the username and password (should we have an option to not send any?)
    curl_setopt($ch, CURLOPT_USERPWD, "$user:$pass");
    
    $fullurl = $this->config->url . $url;
    curl_setopt($ch, CURLOPT_URL, $fullurl);
  }
  
  private function doRequest() {
    $data = array();
    $data['body'] = curl_exec($ch);
    $error_code = curl_errno($ch);
    $error_string = curl_error($ch);
    curl_close($ch);
    return array($ret_val, $error_code, $error_string);
  }
  
  function httpPostRequest($url, $post) {
    $ch = $this->getCurlContext();
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "$post");
    $this->buildUrl($url, $ch);
    $results = $this->doRequest();
  }
  
  function httpPutRequest($url, $file) {
    $ch = $this->getCurlContext();
    curl_setopt($ch, CURLOPT_PUT, TRUE);
    curl_setopt($ch, CURLOPT_INFILE, $file);
    /* TODO this might bite us in the ass for files over 2gb */
    curl_setopt($ch, CURLOPT_INFILESIZE, filesize($file));
    $this->buildUrl($url, $ch);
    $results = $this->doRequest();
  }
  
  function httpGetRequest($url) {
    $ch = $this->getCurlContext();
    curl_setopt($ch, CURLOPT_HTTPGET, TRUE);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    $this->buildUrl($url, $ch);
    $results = $this->doRequest();
  }
}