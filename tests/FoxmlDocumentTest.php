<?php

require_once 'FedoraApi.php';
require_once 'FedoraApiSerializer.php';
require_once 'Object.php';
require_once 'Repository.php';
require_once 'Cache.php';
require_once 'TestHelpers.php';
require_once 'FoxmlDocument.php';

class FoxmlDocumentTest extends PHPUnit_Framework_TestCase {

  protected function setUp() {
    $connection = new RepositoryConnection(FEDORAURL, FEDORAUSER, FEDORAPASS);
    $this->api = new FedoraApi($connection);
    $cache = new SimpleCache();
    $repository = new FedoraRepository($this->api, $cache);

    // create an object and populate datastreams
    $string1 = FedoraTestHelpers::randomString(10);
    $string2 = FedoraTestHelpers::randomString(10);
//    $this->testPid = "$string1:$string2";
    $this->testPid = 'test:islandora';
    $this->fedora_object = $repository->constructObject($this->testPid);
    $this->fedora_object->owner = 'Test';
    $this->fedora_object->label = 'Test label';
    $mods = $this->fedora_object->constructDatastream('MODS', 'X', $this->fedora_object, $repository);

    $mods_string = '<mods xmlns="http://www.loc.gov/mods/v3" ID="TopTier/Breast/">
          <titleInfo>
            <title>Selective chemical probe
                        inhibitor of Stat3, identified through structure-based virtual screening,
                        induces antitumor activity</title>
          </titleInfo>
          <name type="personal">
            <namePart type="given">K</namePart>
            <namePart type="family">Siddiquee</namePart>
            <role>
              <roleTerm authority="marcrelator" type="text">author</roleTerm>
            </role>
          </name>
        </mods>';
    $mods->label = 'MODS record';
    $mods->setContentFromString($mods_string);
    $this->fedora_object->ingestDatastream($mods);
//    $this->datastream2 = new NewFedoraDatastream('MADS', 'M', $this->fedora_object, $repository);
//    $this->datastream2->label = 'Managed datastream';
//    $this->datastream2->setContentFromUrl('http://localhost/:8080/fedora/objects/fedora-system:FedoraObject-3.0/datastreams/DC/content');
//    $this->datastream2->checksumType = 'MD5';
//    $this->datastream3 = new NewFedoraDatastream('MADS', 'E', $this->fedora_object, $repository);
//    $this->datastream3->label = 'Exernal datastream';
//    $this->datastream3->url = 'http://localhost/:8080/fedora/objects/fedora-system:FedoraObject-3.0/datastreams/DC/content';
//    $this->datastream4 = new NewFedoraDatastream('MADS', 'R', $this->fedora_object, $repository);
//    $this->datastream4->label = 'Redirect datastream';
//    $this->datastream4->url = 'http://localhost/:8080/fedora/objects/fedora-system:FedoraObject-3.0/datastreams/DC/content';
    $repository->ingestObject($this->fedora_object);
    $this->object = new FedoraObject($this->testPid, $repository);
  }

  protected function tearDown() {
//    $this->api->m->purgeObject($this->testPid);
  }

  protected function getValue($data) {
    $values = $this->api->a->getObjectProfile($this->testPid);
    return $values[$data];
  }

  public function testFOXMLObject() {
    $this->assertEquals('Test label', $this->object->label);
    
    $this->assertEquals('Test', $this->object->owner);
    
    $this->assertEquals($this->object->id, $this->testPid);
    $this->assertTrue(isset($this->object->id));
    
    $this->assertEquals('A', $this->object->state);
    
    $this->assertEquals(2, count($this->object));
    $this->assertTrue(isset($this->object['DC']));
    $this->assertTrue(isset($this->object[$this->testDsid]));
    $this->assertFalse(isset($this->object['foo']));
    $this->assertFalse($this->object['foo']);
    $this->assertInstanceOf('FedoraDatastream', $this->object['DC']);
    $this->assertEquals('DC', $this->object['DC']->id);
    foreach ($this->object as $id => $ds) {
      $this->assertTrue(in_array($id, array('DC', $this->testDsid)));
      $this->assertTrue(in_array($ds->id, array('DC', $this->testDsid)));
    }
    $this->assertEquals("\n<test> test </test>\n", $this->object[$this->testDsid]->content);
  }

}