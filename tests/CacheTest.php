<?php

namespace Islandora\Tuque\Tests;

use Islandora\Tuque\Cache\SimpleCache;
use \PHPUnit\Framework\TestCase;

class CacheTest extends TestCase
{

    public function testAdd()
    {
        SimpleCache::resetCache();
        $cache = new SimpleCache();
        $result = $cache->add('test', 'data');
        $this->assertTrue($result);
        return $cache;
    }

    /**
     * @depends testAdd
     */
    public function testGet(SimpleCache $cache)
    {
        $result = $cache->get('test');
        $this->assertEquals('data', $result);
        return $cache;
    }

    /**
     * @depends testGet
     */
    public function testSetAlreadySet(SimpleCache $cache)
    {
        $result = $cache->set('test', 'woot');
        $this->assertTrue($result);
        $result = $cache->get('test');
        $this->assertEquals('woot', $result);
        return $cache;
    }

    /**
     * @depends testSetAlreadySet
     */
    public function testSetNotSet(SimpleCache $cache)
    {
        $result = $cache->set('test2', 'awesomesauce');
        $this->assertTrue($result);
        $result = $cache->get('test2');
        $this->assertEquals('awesomesauce', $result);
        return $cache;
    }

    /**
     * @depends testSetNotSet
     */
    public function testDeleteDoesntExist(SimpleCache $cache)
    {
        $result = $cache->delete('nothing');
        $this->assertFalse($result);
        return $cache;
    }

    /**
     * @depends testDeleteDoesntExist
     */
    public function testDeleteDoesExist(SimpleCache $cache)
    {
        $result = $cache->delete('test');
        $this->assertTrue($result);
        $result = $cache->get('test');
        $this->assertFalse($result);
        $result = $cache->get('test2');
        $this->assertEquals('awesomesauce', $result);
        return $cache;
    }

    public function testAddNull()
    {
        SimpleCache::resetCache();
        $cache = new SimpleCache();
        $result = $cache->add('test', null);
        $this->assertTrue($result);
        return $cache;
    }

    /**
     * @depends testAddNull
     */
    public function testAddNullAgain(SimpleCache $cache)
    {
        $result = $cache->add('test', 'NULL');
        $this->assertFalse($result);
        return $cache;
    }

    /**
     * @depends testAddNullAgain
     */
    public function testGetNull(SimpleCache $cache)
    {
        $result = $cache->get('test');
        $this->assertEquals(null, $result);
        return $cache;
    }

    /**
     * @depends testGetNull
     */
    public function testDeleteNull(SimpleCache $cache)
    {
        $result = $cache->delete('test');
        $this->assertTrue($result);
    }

    public function testCacheSize()
    {
        SimpleCache::resetCache();
        SimpleCache::setCacheSize(2);
        $cache = new SimpleCache();
        $cache->add('test1', 'woot1');
        $cache->add('test2', 'woot2');
        $cache->add('test3', 'woot3');
        $cache->add('test4', 'woot4');
        $this->assertFalse($cache->get('test1'));
        $this->assertFalse($cache->get('test2'));
        $this->assertEquals('woot3', $cache->get('test3'));
        $this->assertEquals('woot4', $cache->get('test4'));
        return $cache;
    }

    /**
     * @depends testCacheSize
     */
    public function testCacheSizeDelete(SimpleCache $cache)
    {
        $this->assertFalse($cache->delete('test1'));
        $this->assertFalse($cache->delete('test2'));
        $this->assertTrue($cache->delete('test3'));
        $this->assertTrue($cache->delete('test4'));
        $this->assertFalse($cache->get('test3'));
        $this->assertFalse($cache->get('test4'));
        return $cache;
    }

    /**
     * @depends testCacheSizeDelete
     */
    public function testCacheAfterEviction(SimpleCache $cache)
    {
        $cache->add('test1', 'woot1');
        $cache->add('test2', 'woot2');
        $cache->add('test3', 'woot3');
        $cache->add('test4', 'woot4');
        $this->assertFalse($cache->get('test1'));
        $this->assertFalse($cache->get('test2'));
        $this->assertEquals('woot3', $cache->get('test3'));
        $this->assertEquals('woot4', $cache->get('test4'));
        return $cache;
    }
}
