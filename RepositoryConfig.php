<?php

class RepositoryConfig {

  public $url;
  public $username;
  public $password;
  public $userAgent = "Mozilla/4.0 pp(compatible; MSIE 5.01; Windows NT 5.0)";
  /* see http://php.net/manual/en/function.curl-setopt.php */
  public $verifyHost = TRUE;
  public $verifyPeer = 2;
  public $timeout = 5;

  function __construct($url = 'http://localhost:8080/fedora', $username = 'anonymous', $password = 'anonymous') {
    $this->url = $url;
    $this->username = $username;
    $this->password = $password;
  }
}