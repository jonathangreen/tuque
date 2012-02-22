<?php

/**
 * @file
 * Contains all of the exceptions thrown by Tuque.
 */

/**
 * The top level exception for the Islandora Fedora API
 */
class RepositoryException extends Exception {}

/**
 * Exception Caused by cURL
 */
class RepositoryCurlException extends RepositoryException {}

/**
 * Exception caused by a HTTP error 
 */
class RepositoryHttpErrorException extends RepositoryException {
  public $response;
  
  function __construct($message, $code, $response, $previous = NULL) {
    parent::__construct($message, $code, $previous);
    $this->response = $response;
  }
}

/**
 * This is thrown when there is an error parsing XML.
 */
class RepositoryXmlError extends RepositoryException {
  public $errors;
  
  function __construct($message, $code, $errors, $previous = NULL) {
    parent::__construct($message, $code, $previous);
    $this->errors = $errors;
  }
}

/**
 * This is thrown when a bad arguement is passed.
 */
class RepositoryBadArguementException extends RepositoryException {}