<?php

require_once 'FedoraApi.php';
require_once 'FedoraApiSerializer.php';
require_once 'Object.php';
require_once 'Repository.php';
require_once 'Cache.php';
require_once 'TestHelpers.php';

class ObjectTest extends PHPUnit_Framework_TestCase {

  protected function setUp() {
    $connection = new RepositoryConnection(FEDORAURL, FEDORAUSER, FEDORAPASS);
    $this->api = new FedoraApi($connection);
    $cache = new SimpleCache();
    $repository = new FedoraRepository($this->api, $cache);

    // create an object 
    $string1 = FedoraTestHelpers::randomString(10);
    $string2 = FedoraTestHelpers::randomString(10);
    $this->testPid = "$string1:$string2";
    $this->api->m->ingest(array('pid' => $this->testPid));
    $this->object = new FedoraObject($this->testPid, $repository);
  }

  protected function tearDown() {
    $this->api->m->purgeObject($this->testPid);
  }

  public function testObjectLabel() {
    $this->assertEquals($this->object->label, '');
    $this->object->label = 'foo';
    $this->assertEquals($this->object->label, 'foo');
    $this->assertTrue(isset($this->object->label));
    unset($this->object->label);
    $this->assertFalse(isset($this->object->label));
    $this->object->label = 'woot';
    $this->assertEquals($this->object->label, 'woot');
    $this->object->label = 'aboot';
    $this->assertEquals($this->object->label, 'aboot');
  }

  public function testObjectOwner() {
    $this->assertEquals($this->object->owner, FEDORAUSER);
    $this->object->owner = 'foo';
    $this->assertEquals($this->object->owner, 'foo');
    $this->assertTrue(isset($this->object->owner));
    unset($this->object->owner);
    $this->assertFalse(isset($this->object->owner));
    $this->object->owner = 'woot';
    $this->assertEquals($this->object->owner, 'woot');
    $this->object->owner = 'aboot';
    $this->assertEquals($this->object->owner, 'aboot');
  }

  public function testObjectId() {
    $this->assertEquals($this->object->id, $this->testPid);
    $this->assertTrue(isset($this->object->id));
  }

  public function testObjectIdUnsetException() {
    $this->markTestIncomplete();
    $this->setExpectedException('Exception');
    unset($this->object->id);
  }

  public function testObjectIdChangeException() {
    $this->markTestIncomplete();
    $this->setExpectedException('Exception');
    $this->object->id = 'foo';
  }

  /**
   * @depends testObjectIdChangeException
   */
  public function testObjectIdDidntChange() {
    $this->assertEquals($this->object->id, $this->testPid);
  }

  public function testObjectState() {
    $this->assertEquals($this->object->state, 'A');

    $this->object->state = 'I';
    $this->assertEquals($this->object->state, 'I');
    $this->object->state = 'A';
    $this->assertEquals($this->object->state, 'A');
    $this->object->state = 'D';
    $this->assertEquals($this->object->state, 'D');

    $this->object->state = 'i';
    $this->assertEquals($this->object->state, 'I');
    $this->object->state = 'a';
    $this->assertEquals($this->object->state, 'A');
    $this->object->state = 'd';
    $this->assertEquals($this->object->state, 'D');

    $this->object->state = 'inactive';
    $this->assertEquals($this->object->state, 'I');
    $this->object->state = 'active';
    $this->assertEquals($this->object->state, 'A');
    $this->object->state = 'deleted';
    $this->assertEquals($this->object->state, 'D');

    $this->object->state = 'foo';
    $this->assertEquals($this->object->state, 'D');
  }

  public function testObjectDelete() {
    $this->assertEquals($this->object->state, 'A');
    $this->object->delete();
    $this->assertEquals($this->object->state, 'D');
  }

}