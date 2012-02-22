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
class RepositoryHttpErrorException extends RepositoryException {}