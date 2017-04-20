<?php

namespace Islandora\Tuque\Connection;

use Islandora\Tuque\Exception\HttpConnectionException;
use CURLFile;

/**
 * This class defines a abstract HttpConnection using the PHP cURL library.
 */
class CurlConnection extends HttpConnection
{
    const COOKIE_LOCATION = 'curl_cookie';
    protected $cookieFile = null;
    protected static $curlContext = null;

    /**
     * Constructor for the connection.
     *
     * @throws HttpConnectionException
     */
    public function __construct()
    {
        if (!function_exists("curl_init")) {
            throw new HttpConnectionException(
                'cURL PHP Module must to enabled.',
                0
            );
        }
        $this->createCookieFile();
    }

    /**
     * Save the cookies to the sessions and remember all of the parents members.
     */
    public function __sleep()
    {
        $this->saveCookiesToSession();
        return parent::__sleep();
    }

    /**
     * Restore the cookies file and initialize curl.
     */
    public function __wakeup()
    {
        $this->createCookieFile();
        $this->getCurlContext();
    }

    /**
     * Destructor for the connection.
     *
     * Save the cookies to the session unallocate curl, and free the cookies file.
     */
    public function __destruct()
    {
        $this->saveCookiesToSession();
        $this->unallocateCurlContext();
        unlink($this->cookieFile);
    }

    /**
     * Determines if the server operating system is Windows.
     *
     * @return bool
     *   TRUE if Windows, FALSE otherwise.
     */
    protected function isWindows()
    {
        // Determine if PHP is currently running on Windows.
        if (strpos(strtolower(php_uname('s')), 'windows') !== false) {
            return true;
        }
        return false;
    }

    /**
     * Returns the file size (in bytes) as a string (for 32-bit PHP).
     *
     * 32-bit PHP can't handle file sizes larger than 2147483647 bytes (2.15GB),
     * since that's the PHP_INT_MAX. In order to compensate, this function
     * uses exec() to retrieve the file size from the operating system and return
     * it as a string. Note that converting the value back into an integer
     * will reintroduce the same max-integer problems.
     *
     * Based on the function sizeExec() from https://github.com/jkuchar/BigFileTools
     *
     * @return string | bool (FALSE upon failure or when exec() is disabled)
     */
    protected function filesizePhp32bit($file)
    {
        $disabled_functions = explode(',', ini_get('disable_functions'));

        // Ensure PHP is capable of executing an external program.
        if ((function_exists("exec")) ||
            (!in_array('exec', $disabled_functions))
        ) {
            $escaped_path = escapeshellarg($file);

            if ($this->isWindows()) {
                // Use a Windows command to find the file size.
                $size = trim(exec("for %F in ($escaped_path) do @echo %~zF"));
            } else {
                // Otherwise, use the stat command (*nix and MacOS).
                $size = trim(exec("stat -Lc%s $escaped_path"));
            }

            // Ensure a number was returned.
            if ($size and ctype_digit($size)) {
                // Return the file size as a string.
                return (string)$size;
            }
        }
        return false;
    }

    /**
     * Create a file to store cookies.
     */
    protected function createCookieFile()
    {
        $this->cookieFile = tempnam(sys_get_temp_dir(), 'curlcookie');
        // If we didn't get a place to store cookies in a temporary
        // file, we cannot continue.
        if (!$this->cookieFile) {
            throw new HttpConnectionException(
                'Could not open temporary file at ' . sys_get_temp_dir(),
                0
            );
        }
        // See if we have any cookies in the session already
        // this makes sure SESSION ids persist.
        if (isset($_SESSION[self::COOKIE_LOCATION])) {
            file_put_contents(
                $this->cookieFile,
                $_SESSION[self::COOKIE_LOCATION]
            );
        }
    }

    /**
     * Save the contents of the cookie file to the session.
     */
    protected function saveCookiesToSession()
    {
        // Before we go, save our fedora session cookie to the browsers session.
        if (isset($_SESSION)) {
            $_SESSION[self::COOKIE_LOCATION] = file_get_contents($this->cookieFile);
        }
    }

    /**
     * This function sets up the context for curl.
     */
    protected function getCurlContext()
    {
        if (!isset(self::$curlContext)) {
            self::$curlContext = curl_init();
        }
    }

    /**
     * Unallocate curl context
     */
    protected function unallocateCurlContext()
    {
        if (self::$curlContext) {
            curl_close(self::$curlContext);
            self::$curlContext = null;
        }
    }

    /**
     * This sets the curl options
     */
    protected function setupCurlContext($url)
    {
        $this->getCurlContext();
        curl_setopt(self::$curlContext, CURLOPT_URL, $url);
        curl_setopt(
            self::$curlContext,
            CURLOPT_SSL_VERIFYPEER,
            $this->verifyPeer
        );
        curl_setopt(
            self::$curlContext,
            CURLOPT_SSL_VERIFYHOST,
            $this->verifyHost ? 2 : 1
        );
        if ($this->sslVersion !== null) {
            curl_setopt(
                self::$curlContext,
                CURLOPT_SSLVERSION,
                $this->sslVersion
            );
        }
        if ($this->timeout) {
            curl_setopt(self::$curlContext, CURLOPT_TIMEOUT, $this->timeout);
        }
        curl_setopt(
            self::$curlContext,
            CURLOPT_CONNECTTIMEOUT,
            $this->connectTimeout
        );
        if ($this->userAgent) {
            curl_setopt(
                self::$curlContext,
                CURLOPT_USERAGENT,
                $this->userAgent
            );
        }
        if ($this->cookies) {
            curl_setopt(
                self::$curlContext,
                CURLOPT_COOKIEFILE,
                $this->cookieFile
            );
            curl_setopt(
                self::$curlContext,
                CURLOPT_COOKIEJAR,
                $this->cookieFile
            );
        }
        curl_setopt(self::$curlContext, CURLOPT_FAILONERROR, false);
        curl_setopt(self::$curlContext, CURLOPT_FOLLOWLOCATION, 1);

        curl_setopt(self::$curlContext, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(self::$curlContext, CURLOPT_HEADER, true);

        if ($this->debug) {
            curl_setopt(self::$curlContext, CURLOPT_VERBOSE, 1);
        }
        if ($this->username) {
            $user = $this->username;
            $pass = $this->password;
            curl_setopt(self::$curlContext, CURLOPT_USERPWD, "$user:$pass");
        }
    }

    /**
     * This function actually does the cURL request. It is a private function
     * meant to be called by the public get, post and put methods.
     *
     * @throws HttpConnectionException
     *
     * @return array
     *   Array has keys: (status, headers, content).
     */
    protected function doCurlRequest($file = null, $file_handle = null)
    {
        $remaining_attempts = 3;
        $http_error_string = '';
        $response = [];
        $info = [];

        while ($remaining_attempts > 0) {
            $curl_response = curl_exec(self::$curlContext);
            // Since we are using exceptions we trap curl error
            // codes and toss an exception, here is a good error
            // code reference.
            // http://curl.haxx.se/libcurl/c/libcurl-errors.html
            $error_code = curl_errno(self::$curlContext);
            $error_string = curl_error(self::$curlContext);
            if ($error_code != 0) {
                throw new HttpConnectionException($error_string, $error_code);
            }

            $info = curl_getinfo(self::$curlContext);

            $response['status'] = $info['http_code'];
            if ($file == null) {
                $response['headers'] = substr(
                    $curl_response,
                    0,
                    $info['header_size'] - 1
                );
                $response['content'] = substr(
                    $curl_response,
                    $info['header_size']
                );

                // We do some ugly stuff here to strip the error string out
                // of the HTTP headers, since curl doesn't provide any helper.
                $http_error_string = explode("\r\n\r\n", $response['headers']);
                $http_error_string = $http_error_string[count($http_error_string) - 1];
                $http_error_string = explode("\r\n", $http_error_string);
                $http_error_string = substr($http_error_string[0], 13);
                $http_error_string = trim($http_error_string);
            }
            $blocked = $info['http_code'] == 409;
            $remaining_attempts = $blocked ? --$remaining_attempts : 0;
            if (!is_null($file_handle)) {
                rewind($file_handle);
            }
        }
        // Throw an exception if this isn't a 2XX response.
        $success = preg_match("/^2/", $info['http_code']);
        if (!$success) {
            throw new HttpConnectionException(
                $http_error_string,
                $info['http_code'],
                $response
            );
        }
        return $response;
    }

    protected function postPatchRequest(
        $type = 'none',
        $data = null,
        $content_type = null
    ) {
        switch (strtolower($type)) {
            case 'string':
                if ($content_type) {
                    $headers = ["Content-Type: $content_type"];
                } else {
                    $headers = ["Content-Type: text/plain"];
                }
                curl_setopt(self::$curlContext, CURLOPT_HTTPHEADER, $headers);
                curl_setopt(self::$curlContext, CURLOPT_POSTFIELDS, $data);
                break;

            case 'file':
                if (version_compare(phpversion(), '5.5.0', '>=')) {
                    if ($content_type) {
                        $cfile = new CURLFile($data, $content_type, $data);
                        curl_setopt(
                            self::$curlContext,
                            CURLOPT_POSTFIELDS,
                            ['file' => $cfile]
                        );
                    } else {
                        $cfile = new CURLFile($data);
                        curl_setopt(
                            self::$curlContext,
                            CURLOPT_POSTFIELDS,
                            ['file' => $cfile]
                        );
                    }
                } else {
                    if ($content_type) {
                        curl_setopt(
                            self::$curlContext,
                            CURLOPT_POSTFIELDS,
                            ['file' => "@$data;type=$content_type"]
                        );
                    } else {
                        curl_setopt(
                            self::$curlContext,
                            CURLOPT_POSTFIELDS,
                            ['file' => "@$data"]
                        );
                    }
                }
                break;

            case 'none':
                curl_setopt(self::$curlContext, CURLOPT_POSTFIELDS, []);
                break;

            default:
                throw new HttpConnectionException(
                    '$type must be: string, file. ' . "($type).",
                    0
                );
        }

        // Ugly substitute for a try catch finally block.
        $exception = null;
        $results = [];
        try {
            $results = $this->doCurlRequest();
        } catch (HttpConnectionException $e) {
            $exception = $e;
        }

        if ($this->reuseConnection) {
            curl_setopt(self::$curlContext, CURLOPT_POST, false);
            curl_setopt(self::$curlContext, CURLOPT_HTTPHEADER, []);
        } else {
            $this->unallocateCurlContext();
        }

        if ($exception) {
            throw $exception;
        }

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function patchRequest(
        $url,
        $type = 'none',
        $data = null,
        $content_type = null
    ) {
        $this->setupCurlContext($url);
        curl_setopt(self::$curlContext, CURLOPT_CUSTOMREQUEST, 'PATCH');
        return $this->postPatchRequest($type, $data, $content_type);
    }

    /**
     * {@inheritdoc}
     */
    public function postRequest(
        $url,
        $type = 'none',
        $data = null,
        $content_type = null
    ) {
        $this->setupCurlContext($url);
        curl_setopt(self::$curlContext, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt(self::$curlContext, CURLOPT_POST, true);
        return $this->postPatchRequest($type, $data, $content_type);
    }

    /**
     * {@inheritdoc}
     */
    public function putRequest($url, $type = 'none', $file = null)
    {
        $this->setupCurlContext($url);
        curl_setopt(self::$curlContext, CURLOPT_CUSTOMREQUEST, 'PUT');
        switch (strtolower($type)) {
            case 'string':
                // When using 'php://memory' in Windows, the following error
                // occurs when trying to ingest a page into the Book Solution Pack:
                // "Warning: curl_setopt(): cannot represent a stream of type
                // MEMORY as a STDIO FILE* in CurlConnection->putRequest()"
                // Reference: http://bit.ly/18Qym02
                $file_stream = (($this->isWindows()) ? 'php://temp' : 'php://memory');
                $fh = fopen($file_stream, 'rw');
                fwrite($fh, $file);
                rewind($fh);
                $size = strlen($file);
                curl_setopt(self::$curlContext, CURLOPT_PUT, true);
                curl_setopt(self::$curlContext, CURLOPT_INFILE, $fh);
                curl_setopt(self::$curlContext, CURLOPT_INFILESIZE, $size);
                break;

            case 'file':
                clearstatcache(true, $file);
                $fh = fopen($file, 'r');
                $size = filesize($file);
                // Determine if this is Windows, plus 32-bit PHP (based on the integer size).
                if (($this->isWindows()) && (PHP_INT_SIZE === 4)) {
                    // Retrieve the file size as a string.
                    $size = $this->filesizePhp32bit($file);
                    if ($size !== false) {
                        // When the file size is set using CURLOPT_INFILESIZE, the value
                        // is automatically converted into an integer. Unfortunately,
                        // 32-bit PHP can't handle file sizes (in bytes) larger than
                        // 2.15GB. To get around this, update the cURL header directly
                        // instead. The size remains a string when added to the header.
                        // cURL is then able to process the file correctly later on.
                        curl_setopt(
                            self::$curlContext,
                            CURLOPT_HTTPHEADER,
                            [
                                'Content-Length: ' . $size,
                            ]
                        );
                    }
                } else {
                    curl_setopt(self::$curlContext, CURLOPT_INFILESIZE, $size);
                }
                curl_setopt(self::$curlContext, CURLOPT_PUT, true);
                curl_setopt(self::$curlContext, CURLOPT_INFILE, $fh);
                break;

            case 'none':
                break;

            default:
                throw new HttpConnectionException(
                    '$type must be: string, file. ' . "($type).",
                    0
                );
        }

        // Ugly substitute for a try catch finally block.
        $results = [];
        $exception = null;
        try {
            $results = isset($fh) ?
                $this->doCurlRequest(null, $fh) :
                $this->doCurlRequest(null);
        } catch (HttpConnectionException $e) {
            $exception = $e;
        }

        if ($this->reuseConnection) {
            //curl_setopt(self::$curlContext, CURLOPT_PUT, FALSE);
            //curl_setopt(self::$curlContext, CURLOPT_INFILE, 'default');
            //curl_setopt(self::$curlContext, CURLOPT_CUSTOMREQUEST, FALSE);
            // We can't unallocate put requests becuase CURLOPT_INFILE can't be undone
            // this is ugly, but it gets the job done for now.
            $this->unallocateCurlContext();
        } else {
            $this->unallocateCurlContext();
        }

        if (isset($fh)) {
            fclose($fh);
        }

        if ($exception) {
            throw $exception;
        }

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequest($url, $headers_only = false, $file = null)
    {
        // Need this as before we were opening a new file pointer for std for each
        // request. When the ulimit was reached this would make things blow up.
        static $stdout = null;

        if ($stdout === null) {
            $stdout = fopen('php://stdout', 'w');
        }
        $this->setupCurlContext($url);

        if ($headers_only) {
            curl_setopt(self::$curlContext, CURLOPT_NOBODY, true);
            curl_setopt(self::$curlContext, CURLOPT_HEADER, true);
        } else {
            curl_setopt(self::$curlContext, CURLOPT_CUSTOMREQUEST, 'GET');
            curl_setopt(self::$curlContext, CURLOPT_HTTPGET, true);
        }

        if ($file) {
            $file_original_path = $file;
            // In Windows, using 'temporary://' with curl_setopt 'CURLOPT_FILE'
            // results in the following error: "Warning: curl_setopt():
            // DrupalTemporaryStreamWrapper::stream_cast is not implemented!"
            if ($this->isWindows()) {
                $file = str_replace(
                    'temporary://',
                    sys_get_temp_dir() . '/',
                    $file
                );
            }
            $file = fopen($file, 'w+');
            // Determine if the current operating system is Windows.
            // Also check whether the output buffer is being utilized.
            if (($this->isWindows()) && ($file_original_path == 'php://output')) {
                // In Windows, ensure the image can be displayed onscreen. Just using
                // 'CURLOPT_FILE' results in a broken image and the following error:
                // "Warning: curl_setopt(): cannot represent a stream of type
                // Output as a STDIO FILE* in CurlConnection->getRequest()"
                // Resource: http://www.php.net/manual/en/function.curl-setopt.php#58074
                curl_setopt(self::$curlContext, CURLOPT_RETURNTRANSFER, false);
            } else {
                curl_setopt(self::$curlContext, CURLOPT_FILE, $file);
            }
            curl_setopt(self::$curlContext, CURLOPT_HEADER, false);
        }

        // Ugly substitute for a try catch finally block.
        $exception = null;
        $results = [];
        try {
            $results = $this->doCurlRequest($file);
        } catch (HttpConnectionException $e) {
            $exception = $e;
        }

        if ($this->reuseConnection) {
            curl_setopt(self::$curlContext, CURLOPT_HTTPGET, false);
            curl_setopt(self::$curlContext, CURLOPT_NOBODY, false);
            curl_setopt(self::$curlContext, CURLOPT_HEADER, false);
        } else {
            $this->unallocateCurlContext();
        }

        if ($file) {
            fclose($file);
            curl_setopt(self::$curlContext, CURLOPT_FILE, $stdout);
        }

        if ($exception) {
            throw $exception;
        }

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteRequest($url)
    {
        $this->setupCurlContext($url);

        curl_setopt(self::$curlContext, CURLOPT_CUSTOMREQUEST, 'DELETE');

        // Ugly substitute for a try catch finally block.
        $exception = null;
        $results = [];
        try {
            $results = $this->doCurlRequest();
        } catch (HttpConnectionException $e) {
            $exception = $e;
        }

        if ($this->reuseConnection) {
            curl_setopt(self::$curlContext, CURLOPT_CUSTOMREQUEST, null);
        } else {
            $this->unallocateCurlContext();
        }

        if ($exception) {
            throw $exception;
        }

        return $results;
    }
}
