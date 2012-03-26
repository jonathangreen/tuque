<?php

require_once 'Datastream.php';
require_once 'FedoraApi.php';
require_once 'FedoraApiSerializer.php';
require_once 'Object.php';
require_once 'Repository.php';
require_once 'Cache.php';
require_once 'TestHelpers.php';

class DatastreamTest extends PHPUnit_Framework_TestCase {

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

    // create a DSID
    $this->testDsid = FedoraTestHelpers::randomCharString(10);
    $this->api->m->addDatastream($this->testPid, $this->testDsid, 'string', '<test><xml/></test>', array('controlGroup' => 'M'));

    $this->object = new FedoraObject($this->testPid, $repository);
    $this->ds = new FedoraDatastream($this->testDsid, $this->object, $repository);
  }

  protected function tearDown() {
    $this->api->m->purgeObject($this->testPid);
  }

  public function testId() {
    $this->assertEquals($this->testDsid, $this->ds->id);
  }

  public function testControlGroup() {
    $this->assertEquals('M', $this->ds->controlGroup);
  }

  public function testObjectLabel() {
    $this->assertEquals('', $this->ds->label);
    $this->assertFalse(isset($this->ds->label));
    $this->ds->label = 'foo';
    $this->assertEquals('foo', $this->ds->label);
    $this->assertTrue(isset($this->ds->label));
    unset($this->ds->label);
    $this->assertEquals('', $this->ds->label);
    $this->assertFalse(isset($this->ds->label));
    $this->ds->label = 'woot';
    $this->assertEquals('woot', $this->ds->label);
    $this->ds->label = 'aboot';
    $this->assertEquals('aboot', $this->ds->label);
  }

}