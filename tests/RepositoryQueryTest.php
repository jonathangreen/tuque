<?php

namespace Islandora\Tuque\Tests;

use Islandora\Tuque\Api\FedoraApi;
use Islandora\Tuque\Api\FedoraApiSerializer;
use Islandora\Tuque\Cache\SimpleCache;
use Islandora\Tuque\Guzzle\Client;
use Islandora\Tuque\Repository\FedoraRepository;
use PHPUnit_Framework_TestCase;

class RepositoryQueryTest extends PHPUnit_Framework_TestCase
{

    protected function setUp()
    {
        $guzzle = new Client(['base_uri' => FEDORAURL,'auth' => [FEDORAUSER, FEDORAPASS]]);
        $this->api = new FedoraApi($guzzle, new FedoraApiSerializer());
        $cache = new SimpleCache();
        $this->repository = new FedoraRepository($this->api, $cache);
    }

    public function testItql()
    {
        $query = 'select $pid $label from <#ri>
where $pid <fedora-model:label> $label';
        $results = $this->repository->ri->itqlQuery($query);
        $this->assertTrue(true, 'The query did not throw an exception.');
    }

    public function testCount()
    {
        $query = 'select $pid $label from <#ri>
where $pid <fedora-model:label> $label';
        $results = count($this->repository->ri->itqlQuery($query));
        $number = $this->repository->ri->countQuery($query, 'itql');

        $this->assertEquals($results, $number, 'The number of tuples returned was equal.');
    }
}
