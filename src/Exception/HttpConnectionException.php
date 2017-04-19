<?php

namespace Islandora\Tuque\Exception;

use Exception;

/**
 * HTTP Exception. This is thrown when a status code other then 2XX is returned.
 *
 * @param string $message
 *   A message describing the exception.
 * @param int $code
 *   The error code. These are often the HTTP status codes, however less then
 *   100 is defined by the class extending HttpConnection, for eample cURL.
 * @param array $response
 *   The array containing: status, headers, and content of the HTTP request
 *   causing the error. This is only set if there was a HTTP response sent.
 */
class HttpConnectionException extends Exception
{
    protected $response;

    /**
     * The constructor for the exception. Adds a response field.
     *
     * @param string $message
     *   The error message
     * @param int $code
     *   The error code
     * @param array $response
     *   The HTTP response
     * @param Exception $previous
     *   The previous exception in the chain
     */
    public function __construct($message, $code, $response = null, $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->response = $response;
    }

    /**
     * Get the HTTP response that caused the exception.
     *
     * @return array
     *   Array containing the HTTP response. It has three keys: status, headers
     *   and content.
     */
    public function getResponse()
    {
        return $this->response;
    }
}