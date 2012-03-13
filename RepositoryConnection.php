<?php
require_once('HttpConnection.php');
require_once('RepositoryException.php');

/**
 * @file 
 * This file contains both the Abstract version of a configuration file and an implementation.
 */

/**
 * The general interface for a RepositoryConfig object.
 */
interface RepositoryConfigInterface {
  function __construct($url, $username, $password);
}

/**
 * Specific RepositoryConfig implementation to be used with the cURL RepositoryConnection Object.
 */
class RepositoryConnection extends CurlConnection implements RepositoryConfigInterface {

  public $url;
  public $username;
  public $password;

  function __construct($url = 'http://localhost:8080/fedora', $username = NULL, $password = NULL) {
    // make sure the url doesn't have a trailing slash 
    $this->url = rtrim($url,"/");
    $this->username = $username;
    $this->password = $password;
    
    try {
      parent::__construct();
    }
    catch (HttpConnectionException $e) {
      throw new RepositoryException($e->getMessage(), $e->getCode(), $e);
    }
  }
  
  private function buildUrl($url) {
    $url = ltrim($url,"/");
    return "{$this->url}/$url";
  }
  
  public function addParamArray(&$request, &$seperator, $params, $name) {
    if(is_array($params)) {
      if(isset($params[$name])) {
        $this->addParam($request, $seperator, $name, $params[$name]);
      }
    }
  }
  
  public function addParam(&$request, &$seperator, $name, $value) {
    if($value) {
      if(is_bool($value)) {
        $parameter = $value ? 'true' : 'false';
      }
      else {
        $parameter = urlencode($value);
      }
      $request .= "{$seperator}{$name}={$parameter}";
      $seperator = '&';
    }
  }
  
  public function getRequest($url) {
    try {
      return parent::getRequest($this->buildUrl($url));
    }
    catch (HttpConnectionException $e) {
      $this->parseFedoraExceptions($e);
    }
  }
  
  public function postRequest($url, $type = 'none', $data = NULL, $content_type = NULL) {
    try {
      return parent::postRequest($this->buildUrl($url), $type, $data, $content_type);
    }
    catch (HttpConnectionException $e) {
      $this->parseFedoraExceptions($e);
    }
  }
  
  public function putRequest($url, $type = 'none', $file = NULL) {
    try {
      return parent::putRequest($this->buildUrl($url), $type, $file);
    }
    catch (HttpConnectionException $e) {
      $this->parseFedoraExceptions($e);
    }
  }
  
  public function deleteRequest($url) {
    try {
      return parent::deleteRequest($this->buildUrl($url));
    }
    catch (HttpConnectionException $e) {
      $this->parseFedoraExceptions($e);
    }
  }

  private function parseFedoraExceptions($e) {
    $code = $e->getCode();

    switch($code) {
      case '400':
        // When setting an error 400 often Fedora puts useful error messages
        // in the message body, we might as well expose them.
        $response = $e->getResponse();
        $message = $response['content'];
        break;

      case '500':
        // When setting an error 500 Fedora is usually returning a java stack
        // trace. This isn't great, but we can give a better message by return
        // the message set in that exception .
        $response = $e->getResponse();
        $message = preg_split('/$\R?^/m', $response['content']);
        $message = explode(':', $message[0]);
        $message = $message[count($message) - 1];
        break;
      
      default:
        $message = $e->getMessage();
        break;
    }
    throw new RepositoryException($message, $code, $e);
  }
}