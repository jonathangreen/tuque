<?php

namespace Islandora\Tuque\Connection;

/**
 * Abstract class defining functions for HTTP connections
 */
abstract class HttpConnection
{
    /**
     * This determines if the HTTP connection should use cookies. (Default: TRUE)
     * @var boolean
     */
    public $cookies = true;

    /**
     * The username to connect with. If no username is desired then use NULL.
     * (Default: NULL)
     * @var string
     */
    public $username = null;

    /**
     * The password to connect with. Used if a username is set.
     * @var string
     */
    public $password = null;

    /**
     * TRUE to check the existence of a common name and also verify that it
     * matches the hostname provided. (Default: TRUE)
     * @var boolean
     */
    public $verifyHost = true;

    /**
     * FALSE to stop cURL from verifying the peer's certificate. (Default: TRUE)
     * @var boolean
     */
    public $verifyPeer = true;

    /**
     * The maximum number of seconds to allow cURL functions to execute. (Default:
     * cURL default)
     * @var int
     */
    public $timeout = null;

    /**
     * The number of seconds to wait while trying to connect. Use 0 to wait
     * indefinitely. (Default: 5)
     * @var int
     */
    public $connectTimeout = 5;

    /**
     * The useragent to use. (Default: cURL default)
     * @var string
     */
    public $userAgent = null;

    /**
     * If this is set to true, the connection will be recycled, so that cURL will
     * try to use the same connection for multiple requests. If this is set to
     * FALSE a new connection will be used each time.
     * @var boolean
     */
    public $reuseConnection = true;

    /**
     * Some servers require the version of ssl to be set.
     * We set it to NULL which will allow php to try and figure out what
     * version to use.  in some cases you may have to set this to 2 or 3
     * @var int
     */
    public $sslVersion = null;

    /**
     * Turn on to print debug infotmation to stderr.
     * @var boolean
     */
    public $debug = false;

    public function __sleep()
    {
        return array(
            'url',
            'cookies',
            'username',
            'password',
            'verifyHost',
            'verifyPeer',
            'timeout',
            'connectTimeout',
            'userAgent',
            'reuseConnection',
            'sslVersion',
        );
    }

    /**
     * Post a request to the server. This is primarily used for
     * sending files.
     *
     * @todo Test this for posting general form data. (Other then files.)
     *
     * @param string $url
     *   The URL to post the request to. Should start with the
     *   protocol. For example: http://.
     * @param string $type
     *   This parameter must be one of: string, file.
     * @param string $data
     *   What this parameter contains is decided by the $type parameter.
     * @param string $content_type
     *   The content type header to set for the post request.
     *
     * @throws \Islandora\Tuque\Exception\HttpConnectionException
     *
     * @return array
     *   Associative array containing:
     *   * $return['status'] = The HTTP status code
     *   * $return['headers'] = The HTTP headers of the reply
     *   * $return['content'] = The body of the HTTP reply
     */
    abstract public function postRequest(
        $url,
        $type = 'none',
        $data = null,
        $content_type = null
    );

    /**
     * Do a patch request, used for partial updates of a resource
     *
     *
     * @param string $url
     *   The URL to post the request to. Should start with the
     *   protocol. For example: http://.
     * @param string $type
     *   This paramerter must be one of: string, file.
     * @param string $data
     *   What this parameter contains is decided by the $type parameter.
     * @param string|null
     *
     * @throws \Islandora\Tuque\Exception\HttpConnectionException
     *
     * @return array
     *   Associative array containing:
     *   * $return['status'] = The HTTP status code
     *   * $return['headers'] = The HTTP headers of the reply
     *   * $return['content'] = The body of the HTTP reply
     */
    abstract public function patchRequest(
        $url,
        $type = 'none',
        $data = null,
        $content_type = null
    );

    /**
     * Send a HTTP GET request to URL.
     *
     * @param string $url
     *   The URL to post the request to. Should start with the
     *   protocol. For example: http://.
     * @param boolean $headers_only
     *   This will cause curl to only return the HTTP headers.
     * @param string|bool $file
     *   A file to output the content of request to. If this is set then headers
     *   are not returned and the 'content' and 'headers' keys of the return isn't
     *   set.
     *
     * @throws \Islandora\Tuque\Exception\HttpConnectionException
     *
     * @return array
     *   Associative array containing:
     *   * $return['status'] = The HTTP status code
     *   * $return['headers'] = The HTTP headers of the reply
     *   * $return['content'] = The body of the HTTP reply
     */
    abstract public function getRequest(
        $url,
        $headers_only = false,
        $file = false
    );

    /**
     * Send a HTTP PUT request to URL.
     *
     * @param string $url
     *   The URL to post the request to. Should start with the
     *   protocol. For example: http://.
     * @param string $type
     *   This paramerter must be one of: string, file, none.
     * @param string $file
     *   What this parameter contains is decided by the $type parameter.
     *
     * @throws \Islandora\Tuque\Exception\HttpConnectionException
     *
     * @return array
     *   Associative array containing:
     *   * $return['status'] = The HTTP status code
     *   * $return['headers'] = The HTTP headers of the reply
     *   * $return['content'] = The body of the HTTP reply
     */
    abstract public function putRequest($url, $type = 'none', $file = null);

    /**
     * Send a HTTP DELETE request to URL.
     *
     * @param string $url
     *   The URL to post the request to. Should start with the
     *   protocol. For example: http://.
     *
     * @throws \Islandora\Tuque\Exception\HttpConnectionException
     *
     * @return array
     *   Associative array containing:
     *   * $return['status'] = The HTTP status code
     *   * $return['headers'] = The HTTP headers of the reply
     *   * $return['content'] = The body of the HTTP reply
     */
    abstract public function deleteRequest($url);
}
