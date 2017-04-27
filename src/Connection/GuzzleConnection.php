<?php

namespace Islandora\Tuque\Connection;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Islandora\Tuque\Exception\RepositoryException;
use Psr\Http\Message\ResponseInterface;

class GuzzleConnection
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

    public function postRequest($url, $options = [])
    {
        return $this->request('POST', $url, $options);
    }

    public function putRequest($url, $options = [])
    {
        return $this->request('PUT', $url, $options);
    }

    public function getRequest($url, $options = [])
    {
        return $this->request('GET', $url, $options);
    }

    public function deleteRequest($url, $options)
    {
        return $this->request('DELETE', $url, $options);
    }

    protected function request($verb, $url, $options = [])
    {
        if (isset($options['query'])) {
            foreach ($options['query'] as $key => $value) {
                if (is_bool($value)) {
                    $options['query'][$key] = $value ? 'true' : 'false';
                }
            }
        }
        try {
            return $this->client->request($verb, $url, $options);
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
