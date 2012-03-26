<?php
/**
 * @file
 * This file contains the implementation of a connection to Fedora. And the
 * interface for a repository configuration.
 */

require_once 'HttpConnection.php';
require_once 'RepositoryException.php';

/**
 * The general interface for a RepositoryConfig object.
 */
interface RepositoryConfigInterface {
  /**
   * Simple constructor defintion for the repository
   */
  function __construct($url, $username, $password);
}

/**
 * Specific RepositoryConfig implementation that extends the CurlConnection
 * class so that we can do specific processing on Curl requests for Fedora.
 * This also wraps the exceptions thrown by Curl, so that we keep our exception
 * encapsulation.
 */
class RepositoryConnection extends CurlConnection implements RepositoryConfigInterface {

  public $url;
  public $username;
  public $password;

  function __construct($url = 'http://localhost:8080/fedora', $username = NULL, $password = NULL) {
    // Make sure the url doesn't have a trailing slash.
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
      if(array_key_exists($name, $params)) {
        $this->addParam($request, $seperator, $name, $params[$name]);
      }
    }
  }
  
  public function addParam(&$request, &$seperator, $name, $value) {
    if($value !== NULL) {
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
        if(!$message || strpos($message,'Exception') !== FALSE) {
          $message = $e->getMessage();
        }
        break;

      case '500':
        // When setting an error 500 Fedora is usually returning a java stack
        // trace. This isn't great, but we can give a better message by return
        // the message set in that exception .
        $response = $e->getResponse();
        $message = preg_split('/$\R?^/m', $response['content']);
        $message = explode(':', $message[0]);
        $message = $message[count($message) - 1];
        if(strpos($message,'Exception') !== FALSE) {
          $message = $e->getMessage();
        }
        break;
      
      default:
        $message = $e->getMessage();
        break;
    }
    throw new RepositoryException($message, $code, $e);
  }
}