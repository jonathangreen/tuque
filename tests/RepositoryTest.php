<?php

namespace Islandora\Tuque\Tests;

use Islandora\Tuque\Api\FedoraApi;
use Islandora\Tuque\Cache\SimpleCache;
use Islandora\Tuque\Connection\GuzzleConnection;
use Islandora\Tuque\Object\FedoraObject;
use Islandora\Tuque\Repository\FedoraRepository;
use PHPUnit_Framework_TestCase;

class RepositoryTest extends PHPUnit_Framework_TestCase
{

    protected function setUp()
    {
        $connection = new GuzzleConnection(FEDORAURL, FEDORAUSER, FEDORAPASS);
        $this->api = new FedoraApi($connection);
        $cache = new SimpleCache();
        $this->repository = new FedoraRepository($this->api, $cache);
    }

    public function testIngestNamespace()
    {
        $namespace = TestHelpers::randomString(10);
        $object = $this->repository->constructObject($namespace);
        $object->label = 'foo';
        $object->state = 'd';
        $object->owner = 'woot';
        $this->repository->ingestObject($object);

        $id_array = explode(':', $object->id);
        $this->assertEquals($namespace, $id_array[0]);

        $this->assertTrue($object instanceof FedoraObject);
        $this->assertEquals('foo', $object->label);
        $this->assertEquals('D', $object->state);
        $this->assertEquals('woot', $object->owner);

        $this->repository->purgeObject($object->id);
    }

    public function testIngestNoParams()
    {
        $object = $this->repository->constructObject();
        $id = $object->id;
        $object->label = 'foo';
        $object->state = 'd';
        $object->owner = 'woot';
        $this->repository->ingestObject($object);

        $this->assertTrue($object instanceof FedoraObject);
        $this->assertEquals($id, $object->id);
        $this->assertEquals('foo', $object->label);
        $this->assertEquals('D', $object->state);
        $this->assertEquals('woot', $object->owner);

        $this->repository->purgeObject($object->id);
    }

    public function testIngestFullPid()
    {
        $namespace = TestHelpers::randomString(10);
        $localid = TestHelpers::randomString(10);
        $id = "$namespace:$localid";
        $object = $this->repository->constructObject($id);
        $object->label = 'foo';
        $object->state = 'd';
        $object->owner = 'woot';
        $this->repository->ingestObject($object);

        $this->assertTrue($object instanceof FedoraObject);
        $this->assertEquals($id, $object->id);
        $this->assertEquals('foo', $object->label);
        $this->assertEquals('D', $object->state);
        $this->assertEquals('woot', $object->owner);

        $this->repository->purgeObject($object->id);
    }

    public function testIngestUuid()
    {
        $object = $this->repository->constructObject(null, true);
        $object->label = 'foo';
        $object->state = 'd';
        $object->owner = 'woot';
        $this->repository->ingestObject($object);

        $id_array = explode(':', $object->id);
        $is_uuid = preg_match(
            '/^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-(:?8|9|a|b)[a-f0-9]{3}-[a-f0-9]{12}\z/',
            $id_array[1]
        );

        $this->assertEquals(true, $is_uuid);
        $this->assertTrue($object instanceof FedoraObject);
        $this->assertEquals('foo', $object->label);
        $this->assertEquals('D', $object->state);
        $this->assertEquals('woot', $object->owner);

        $this->repository->purgeObject($object->id);
    }

    public function testIngestUuidNamespaced()
    {
        $namespace = TestHelpers::randomString(10);
        $object = $this->repository->constructObject($namespace, true);
        $object->label = 'foo';
        $object->state = 'd';
        $object->owner = 'woot';
        $this->repository->ingestObject($object);

        $id_array = explode(':', $object->id);
        $is_uuid = preg_match(
            '/^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-(:?8|9|a|b)[a-f0-9]{3}-[a-f0-9]{12}\z/',
            $id_array[1]
        );

        $this->assertEquals($namespace, $id_array[0]);
        $this->assertEquals(true, $is_uuid);
        $this->assertTrue($object instanceof FedoraObject);
        $this->assertEquals('foo', $object->label);
        $this->assertEquals('D', $object->state);
        $this->assertEquals('woot', $object->owner);

        $this->repository->purgeObject($object->id);
    }

    public function testNextIDUuidNamespaced()
    {
        $namespace = TestHelpers::randomString(10);
        $id = $this->repository->getNextIdentifier($namespace, true);

        $id_array = explode(':', $id);
        $is_uuid = preg_match(
            '/^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-(:?8|9|a|b)[a-f0-9]{3}-[a-f0-9]{12}\z/',
            $id_array[1]
        );

        $this->assertEquals($namespace, $id_array[0]);
        $this->assertEquals(true, $is_uuid);
    }

    public function testNextIDUuid()
    {
        $id = $this->repository->getNextIdentifier(null, true);

        $id_array = explode(':', $id);
        $is_uuid = preg_match(
            '/^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-(:?8|9|a|b)[a-f0-9]{3}-[a-f0-9]{12}\z/',
            $id_array[1]
        );

        $this->assertEquals(true, $is_uuid);
    }

    public function testNextIDNamespaced()
    {
        $namespace = TestHelpers::randomString(10);
        $id = $this->repository->getNextIdentifier($namespace, true);

        $id_array = explode(':', $id);

        $this->assertEquals($namespace, $id_array[0]);
    }

    public function testNextIDGetTwo()
    {
        $ids = $this->repository->getNextIdentifier(null, false, 2);
        $this->assertEquals(2, count($ids));
    }
}
