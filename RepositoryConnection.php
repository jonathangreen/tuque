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
class RepositoryConnection extends CurlConnection implements RepositoryConfigInterface{

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
  
  public function getRequest($url) {
    try {
      return parent::getRequest($this->buildUrl($url));
    }
    catch (HttpConnectionException $e) {
      throw new RepositoryException($e->getMessage(), $e->getCode(), $e);
    }
  }
  
  public function postRequest($url, $type = 'none', $data = NULL) {
    try {
      return parent::postRequest($this->buildUrl($url), $type, $data);
    }
    catch (HttpConnectionException $e) {
      throw new RepositoryException($e->getMessage(), $e->getCode(), $e);
    }
  }
  
  public function putRequest($url, $type = 'none', $file = NULL) {
    try {
      return parent::putRequest($this->buildUrl($url), $type, $file);
    }
    catch (HttpConnectionException $e) {
      throw new RepositoryException($e->getMessage(), $e->getCode(), $e);
    }
  }
  
  public function deleteRequest($url) {
    try {
      return parent::deleteRequest($this->buildUrl($url));
    }
    catch (HttpConnectionException $e) {
      throw new RepositoryException($e->getMessage(), $e->getCode(), $e);
    }
  }
}