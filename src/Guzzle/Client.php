<?php

namespace Islandora\Tuque\Guzzle;

use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Client as GuzzleClient;
use Islandora\Tuque\Exception\RepositoryException;

class Client extends GuzzleClient
{
    protected $config;

    public function __construct(array $config = [])
    {
        parent::__construct($config);
    }

    public function request($method, $uri = '', array $options = [])
    {
        // Filter for boolean values
        if (isset($options['query'])) {
            foreach ($options['query'] as $key => $value) {
                if (is_bool($value)) {
                    $options['query'][$key] = $value ? 'true' : 'false';
                }
            }
        }

        try {
            return parent::request($method, $uri, $options);
        } catch (BadResponseException $e) {
            throw new RepositoryException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function __sleep()
    {
        return ['config'];
    }

    public function __wakeup()
    {
        parent::__construct($this->config);
    }

}
