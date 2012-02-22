<?php
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
class RepositoryConfig implements RepositoryConfigInterface{

  public $url;
  public $username;
  public $password;
  public $userAgent = "Mozilla/4.0 pp(compatible; MSIE 5.01; Windows NT 5.0)";
  /* see http://php.net/manual/en/function.curl-setopt.php */
  public $verifyHost = TRUE;
  public $verifyPeer = 2;
  public $timeout = 5;

  function __construct($url = 'http://localhost:8080/fedora', $username = NULL, $password = NULL) {
    // make sure the url doesn't have a trailing slash 
    $this->url = rtrim($url,"/");
    $this->username = $username;
    $this->password = $password;
  }
}