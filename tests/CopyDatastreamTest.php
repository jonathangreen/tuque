<?php

require_once 'Datastream.php';
require_once 'FedoraApi.php';
require_once 'FedoraApiSerializer.php';
require_once 'Object.php';
require_once 'Repository.php';
require_once 'Cache.php';
require_once 'TestHelpers.php';

class CopyDatastreamTest extends PHPUnit_Framework_TestCase {

  protected function setUp() {
    $connection = new RepositoryConnection(FEDORAURL, FEDORAUSER, FEDORAPASS);
    $this->api = new FedoraApi($connection);
    $cache = new SimpleCache();
    $this->repository = new FedoraRepository($this->api, $cache);

    // create an object
    $string1 = FedoraTestHelpers::randomString(10);
    $string2 = FedoraTestHelpers::randomString(10);
    $this->testPid = "$string1:$string2";
    $this->api->m->ingest(array('pid' => $this->testPid));


    $string3 = FedoraTestHelpers::randomString(10);
    $string4 = FedoraTestHelpers::randomString(10);
    $this->testPid2 = "$string3:$string4";
    $this->new_object = $this->repository->constructObject();
    $this->new_object->id = $this->testPid2;
    $this->new_object->label = 'Sommat';
    $this->new_object->owner = 'us';

    // create a DSID
    $this->testDsid = FedoraTestHelpers::randomCharString(10);
    $this->testDsContents = '<test><xml/></test>';
    $this->api->m->addDatastream($this->testPid, $this->testDsid, 'string', $this->testDsContents, array('controlGroup' => 'M'));
    $this->object = new FedoraObject($this->testPid, $this->repository);
    $this->ds = new FedoraDatastream($this->testDsid, $this->object, $this->repository);

    $temp_dir = sys_get_temp_dir();
    $this->tempfile1 = tempnam($temp_dir, 'test');
    $this->tempfile2 = tempnam($temp_dir, 'test');
  }

  protected function tearDown() {
    $this->api->m->purgeObject($this->testPid);
    $this->api->m->purgeObject($this->testPid2);
    unlink($this->tempfile1);
    unlink($this->tempfile2);
  }

  public function testExistingToExistingIngest() {
    $object = $this->repository->ingestObject($this->new_object);
    $copied_datastream = $this->new_object->ingestDatastream($this->object[$this->testDsid]);

    $this->assertNotEquals($this->object->id, $copied_datastream->parent->id, 'Datastream exists on new object.');
    $this->object[$this->testDsid]->getContent($this->tempfile1);
    $object[$this->testDsid]->getContent($this->tempfile2);
    $this->assertFileEquals($this->tempfile1, $this->tempfile2, 'Datastream contents are equal.');
  }

  public function testBaseIngest() {
    $this->assertTrue($this->new_object->ingestDatastream($this->object[$this->testDsid]), 'Create datastream entry on new object');
    $object = $this->repository->ingestObject($this->new_object);
    $this->object[$this->testDsid]->getContent($this->tempfile1);
    $object[$this->testDsid]->getContent($this->tempfile2);
    $this->assertFileEquals($this->tempfile1, $this->tempfile2, 'Datastream contents are equal.');
  }

  public function testCopiedIngest() {
    $this->assertTrue($this->new_object->ingestDatastream($this->object[$this->testDsid]), 'Create datastream entry on new object');
    $datastream = $this->new_object[$this->testDsid];
    $this->assertTrue($datastream instanceof CopyOnWriteFedoraDatastream, 'Datastream is a COW.');

    $new_label = strrev($this->new_object[$this->testDsid]->label);
    $new_label .= $new_label;

    $this->new_object[$this->testDsid]->label = $new_label;
    $this->assertEquals($datastream->label, $new_label, 'New label accessible through old wrapper.');
    $object = $this->repository->ingestObject($this->new_object);

    $this->object[$this->testDsid]->getContent($this->tempfile1);
    $object[$this->testDsid]->getContent($this->tempfile2);
    $this->assertFileEquals($this->tempfile1, $this->tempfile2, 'Datastream contents are equal.');
  }
}
