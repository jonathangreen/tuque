<?php

namespace Islandora\Tuque\Connection;

use GuzzleHttp\Client;
use Islandora\Tuque\Config\RepositoryConfigInterface;
use Psr\Http\Message\ResponseInterface;

class GuzzleConnection extends HttpConnection implements RepositoryConfigInterface
{
    protected $client;

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

    protected function request(
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

        $response = $this->client->request($verb, $url, $options);
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
            $response = $this->client->request('POST', $url, $options);
            return $this->translateResponse($response);
        } elseif ($type == 'file') {
            $headers = [];
            if ($content_type !== null) {
                $headers[] = ['Content-Type' => $content_type];
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
            $response = $this->client->request('POST', $url);
            return $this->translateResponse($response);
        }
    }

    public function patchRequest(
        $url,
        $type = 'none',
        $data = null,
        $content_type = null
    ) {
        return $this->request('PATCH', $url, $type, $data, $content_type);
    }


    public function putRequest($url, $type = 'none', $data = null)
    {
        return $this->request('PUT', $url, $type, $data);
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

        $response = $this->client->get($url, $options);
        return $this->translateResponse($response);
    }

    public function deleteRequest($url)
    {
        $url = ltrim($url, "/");
        $response = $this->client->delete($url);
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
}
