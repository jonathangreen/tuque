<?php

define('FEDORAURL', 'http://vm0:8080/fedora');
define('FEDORAUSER', 'fedoraAdmin');
define('FEDORAPASS', 'password');

class FedoraTestHelpers {
  static function randomString($length) {
    $length = 10;
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $string = '';

    for ($p = 0; $p < $length; $p++) {
        $string .= $characters[mt_rand(0, (strlen($characters)-1))];
    }

    return $string;
  }
}