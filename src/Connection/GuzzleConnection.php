<?php

namespace Islandora\Tuque\Connection;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Islandora\Tuque\Config\RepositoryConfigInterface;
use Islandora\Tuque\Exception\RepositoryException;
use Psr\Http\Message\ResponseInterface;

class GuzzleConnection extends HttpConnection implements RepositoryConfigInterface
{
    protected $client;
    protected $url;

    public function __construct(
        $url,
        $username = null,
        $password = null
    ) {
        $url = rtrim($url, "/") . '/';

        // @todo: make this not suck. do some constructor injection.
        $this->client = new Client([
            'base_uri' => $url,
            'auth' => [$username, $password]
        ]);

        $this->url = $url;
        $this->password = $password;
        $this->username = $username;
    }

    protected function translateResponse(ResponseInterface $response, $full = true)
    {
        $return = [];
        $return['status'] = $response->getStatusCode();
        $return['headers'] = $response->getHeaders();

        if ($full) {
            $return['content'] = (string) $response->getBody();
        }

        return $return;
    }

    protected function putPatchRequest(
        $verb,
        $url,
        $type = 'none',
        $data = null,
        $content_type = null
    ) {
        $url = ltrim($url, "/");
        $options = [];

        if ($type == 'string') {
            $options['body'] = $data;
            if ($content_type === null) {
                $options['headers'] = ['Content-Type' => 'text/plain'];
            }
        } elseif ($type == 'file') {
            $resource = fopen($data, 'r');
            $options['body'] = $resource;
            if ($content_type === null) {
                $options['headers'] = ['Content-Type' => 'application/octet-stream'];
            }
        }

        if ($content_type !== null) {
            $options['headers'] = ['Content-Type' => $content_type];
        }

        $response = $this->request($verb, $url, $options);
        return $this->translateResponse($response);
    }

    public function postRequest(
        $url,
        $type = 'none',
        $data = null,
        $content_type = null
    ) {
        $url = ltrim($url, "/");
        $options = [];

        if ($type == 'string') {
            $options['body'] = $data;
            if ($content_type === null) {
                $options['headers'] = ['Content-Type' => 'text/plain'];
            } else {
                $options['headers'] = ['Content-Type' => $content_type];
            }
            $response = $this->request('POST', $url, $options);
            return $this->translateResponse($response);
        } elseif ($type == 'file') {
            $headers = [];
            if ($content_type !== null) {
                $headers = ['Content-Type' => $content_type];
            }
            $response = $this->client->request('POST', $url, [
                'multipart' => [
                    [
                        'name'     => 'file',
                        'contents' => fopen($data, 'r'),
                        'filename' => basename($data),
                        'headers' => $headers,
                    ],
                ]
            ]);
            return $this->translateResponse($response);
        } else {
            $response = $this->request('POST', $url);
            return $this->translateResponse($response);
        }
    }

    public function patchRequest(
        $url,
        $type = 'none',
        $data = null,
        $content_type = null
    ) {
        return $this->putPatchRequest('PATCH', $url, $type, $data, $content_type);
    }


    public function putRequest($url, $type = 'none', $data = null)
    {
        return $this->putPatchRequest('PUT', $url, $type, $data);
    }

    public function getRequest(
        $url,
        $file = false
    ) {
        $url = ltrim($url, "/");

        $options = [];

        if ($file !== false) {
            $options['sink'] = $file;
        }

        $response = $this->request('GET', $url, $options);
        return $this->translateResponse($response);
    }

    public function deleteRequest($url)
    {
        $url = ltrim($url, "/");
        $response = $this->request('DELETE', $url);
        return $this->translateResponse($response);
    }

    /**
     * These functions are used a lot when connecting to Fedora to create the
     * correct arguments for REST calls. This will encode and add an array
     * of arguments to a request URL.
     *
     * @param string $request
     *   The request that is being built.
     * @param string $separator
     *   This is a helper to make sure that the first argument gets a ? and the
     *   rest of them get a &.
     * @param array $params
     *   An array of parameters.
     * @param string $name
     *   The name of the parameter that we are adding.
     */
    public function addParamArray(&$request, &$separator, $params, $name)
    {
        if (is_array($params)) {
            if (array_key_exists($name, $params)) {
                $this->addParam($request, $separator, $name, $params[$name]);
            }
        }
    }

    /**
     * This function adds a specific parameter to a RESTful request. It makes
     * sure that PHP booleans are changes into true and false and that the
     * parameters are properly URL encoded.
     *
     * @param string $request
     *   The request that is being built.
     * @param string $separator
     *   This is a helper to make sure that the first arguement gets a ? and the
     *   rest of them get a &.
     * @param string $name
     *   The name of the parameter that is being added
     * @param string $value
     *   the value of hte parameter.
     */
    public function addParam(&$request, &$separator, $name, $value)
    {
        if ($value !== null) {
            if (is_bool($value)) {
                $parameter = $value ? 'true' : 'false';
            } else {
                $parameter = urlencode($value);
            }
            $request .= "{$separator}{$name}={$parameter}";
            $separator = '&';
        }
    }

    protected function request($verb, $url, $args = [])
    {
        try {
            return $this->client->request($verb, $url, $args);
        } catch (ClientException $e) {
            throw new RepositoryException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function __sleep()
    {
        return ['url', 'username', 'password'];
    }

    public function __wakeup()
    {
        $this->client = new Client([
            'base_uri' => $this->url,
            'auth' => [$this->username, $this->password]
        ]);
    }
}
