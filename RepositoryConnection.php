<?php
/**
 * @file 
 * This file defines the classes used to connect to a repository.
 */
require_once('RepositoryException.php');

/**
 * This class defines three HTTP functions used to connect to the Repository.
 */
class RepositoryConnection {

  const COOKIE_LOCATION = 'fedora_cookie';
  private $config;
  private $cookieFile;
  private $curlContext = NULL;
  
  /**
   * The constructor for the repository class, it takes a configuration object.
   * 
   * @param RepositoryConfig $config 
   */
  public function __construct(RepositoryConfig $config) {
    $this->config = $config;
    $this->cookieFile = tempnam(sys_get_temp_dir(), 'curlcookie');
    $this->getCurlContext();
    
    // see if we have any cookies in the session already
    if(isset($_SESSION[$this::COOKIE_LOCATION])) {
      file_put_contents($this->cookieFile, $_SESSION[$this::COOKIE_LOCATION]);
    }
  }
  
  /**
   * Destructor for the repository connection. Its main purpose is to make sure 
   * that the JSESSION cookies from Fedora persist in the browsers session 
   * variable, so we maintain the same session between page loads. This also
   * closes the curl context.
   */
  public function __destruct() {
    // before we go, save our fedora session cookie to the browsers session
    if(isset($_SESSION)) {
      $SESSION[$this::COOKIE_LOCATION] = file_get_contents($this->cookieFile);
    }
    
    // close our curl context
    curl_close($this->curlContext);
    unlink($this->cookieFile);
  }
  
  /**
   * This function just sets up the context for curl, making sure it has the
   * correct options selected. Ones of interest are the SSL options. Because
   * we set them up securely, they will fail on the default Fedora SSL options. 
   * This can be changed in the config, we just use secure defaults.
   * 
   * @throws RepositoryCurlException
   */
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
      curl_setopt($this->curlContext, CURLOPT_VERBOSE, 1);
    }
    else {
      throw new RepositoryCurlException('cURL PHP Module must to enabled.', 0);
    }
  }
  
  /**
   * This takes in a relative URL and outputs a full URL to be used by cURL. It 
   * also sets up the username and password as part of the cURL context.
   * 
   * @param string $url
   *    The URL relative (to the fedora context) URL to use. 
   */
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
  
  /**
   * This function actually does the cURL request. It is a private function 
   * meant to be called by the public get, post and put methods.
   * 
   * @throws RepositoryCurlException
   * @throws RepositoryHttpErrorException
   *  
   * @return array(status, headers, content)
   */
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
    $response['status'] = $info['http_code'];
    $response['headers'] = substr($curl_response, 0, $info['header_size']-1);
    $response['content'] = substr($curl_response, $info['header_size']);
    
    // We do some ugly stuff here to strip the error string out
    // of the HTTP headers, since curl doesn't provide any helper.
    $http_error_string = explode("\n", $response['headers'], 2);
    $http_error_string = substr($http_error_string[0], 13);
    $http_error_string = trim($http_error_string);
    
    // throw an exception if this isn't a 2XX response
    if(!preg_match("/^2/",$info['http_code'])) {
      throw new RepositoryHttpErrorException($http_error_string, $info['http_code'], $response);
    }
    return $response;
  }
  
  /**
   * This sends a HTTP post request to the relative fedora URL specified.
   * 
   * @param string $url 
   *   The relative URL to post the request to.
   * @param string $post
   *   The data to POST.
   * 
   * @throws RepositoryCurlException
   * @throws RepositoryHttpErrorException
   * 
   * @return array(status, headers, content)
   */
  function httpPostRequest($url, $post, $type = 'string') {
    curl_setopt($this->curlContext, CURLOPT_POST, TRUE);
    switch(strtolower($type)) {
      case 'string':
        $headers = array("Content-Type: text/plain");
        curl_setopt($this->curlContext, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($this->curlContext, CURLOPT_POSTFIELDS, $post);
        break;
      case 'file':
        curl_setopt($this->curlContext, CURLOPT_POSTFIELDS, array('file' => "@$post"));
        break;
      default:
        throw new RepositoryBadArguementException('$type must be: string, file. ' . "($type).");
    }
    $this->buildUrl($url);
    $results = $this->doRequest(); 
    curl_setopt($this->curlContext, CURLOPT_POST, FALSE);
    curl_setopt($this->curlContext, CURLOPT_HTTPHEADER, array());
    return $results;
  }
  
  /**
   * This sends a HTTP PUT request to the relative fedora URL specified.
   * 
   * @param string $url 
   *   The relative URL to post the request to.
   * @param string $file
   *   The filename containing the data for the PUT request.
   * 
   * @throws RepositoryCurlException
   * @throws RepositoryHttpErrorException
   * 
   * @return array(status, headers, content)
   */
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
  
  /**
   * This sends a HTTP GET request to the relative fedora URL specified.
   * 
   * @param string $url 
   *   The relative URL to post the request to.
   * 
   * @throws RepositoryCurlException
   * @throws RepositoryHttpErrorException
   * 
   * @return array(status, headers, content)
   */
  function httpGetRequest($url) {
    curl_setopt($this->curlContext, CURLOPT_HTTPGET, TRUE);
    $this->buildUrl($url);
    $results = $this->doRequest();
    curl_setopt($this->curlContext, CURLOPT_HTTPGET, FALSE);
    return $results;
  }
}