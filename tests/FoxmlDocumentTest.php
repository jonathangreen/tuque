<?php

namespace Islandora\Tuque\Tests;

use Islandora\Tuque\Api\FedoraApi;
use Islandora\Tuque\Api\FedoraApiSerializer;
use Islandora\Tuque\Cache\SimpleCache;
use GuzzleHttp\Client;
use Islandora\Tuque\Datastream\FedoraDatastream;
use Islandora\Tuque\Object\FedoraObject;
use Islandora\Tuque\Repository\FedoraRepository;
use PHPUnit_Framework_TestCase;

class FoxmlDocumentTest extends PHPUnit_Framework_TestCase
{

    protected function setUp()
    {
        $guzzle = new Client(['base_uri' => FEDORAURL,'auth' => [FEDORAUSER, FEDORAPASS]]);
        $this->api = new FedoraApi($guzzle, new FedoraApiSerializer());
        $cache = new SimpleCache();
        $repository = new FedoraRepository($this->api, $cache);

        // create an object and populate datastreams
        $string1 = TestHelpers::randomString(10);
        $string2 = TestHelpers::randomString(10);
        $this->testPid = "$string1:$string2";
        $this->fedora_object = $repository->constructObject($this->testPid);
        $this->fedora_object->owner = 'Test';
        $this->fedora_object->label = 'Test label';
        $inline = $this->fedora_object->constructDatastream('INLINE', 'X');

        $this->mods_string = '
<mods xmlns="http://www.loc.gov/mods/v3" ID="TopTier/Breast/">
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
        </mods>
';
        $inline->label = 'MODS record';
        $inline->checksumType = 'MD5';
        $inline->setContentFromString($this->mods_string);
        $inline->versionable = false;
        $this->fedora_object->ingestDatastream($inline);
        $managed = $this->fedora_object->constructDatastream('MANAGED', 'M');
        $managed->label = 'Managed datastream';
        $managed->setContentFromUrl('http://localhost:8080/fedora/objects/fedora-system:FedoraObject-3.0/datastreams/DC/content');
        $managed->checksumType = 'MD5';
        $this->fedora_object->ingestDatastream($managed);
        $external = $this->fedora_object->constructDatastream('EXTERNAL', 'E');
        $external->label = 'Exernal datastream';
        $external->url = 'http://localhost:8080/fedora/objects/fedora-system:FedoraObject-3.0/datastreams/DC/content';
        $external->checksumType = 'MD5';
        $this->fedora_object->ingestDatastream($external);
        $redirect = $this->fedora_object->constructDatastream('REDIRECT', 'R');
        $redirect->label = 'Redirect datastream';
        $redirect->url = 'http://localhost:8080/fedora/objects/fedora-system:FedoraObject-3.0/datastreams/DC/content';
        $redirect->checksumType = 'MD5';
        $this->fedora_object->ingestDatastream($redirect);
        $repository->ingestObject($this->fedora_object);
        $this->object = new FedoraObject($this->testPid, $repository);
        $this->dc_content = '
<oai_dc:dc xmlns:oai_dc="http://www.openarchives.org/OAI/2.0/oai_dc/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/oai_dc/ http://www.openarchives.org/OAI/2.0/oai_dc.xsd">
  <dc:title>Content Model Object for All Objects</dc:title>
  <dc:identifier>fedora-system:FedoraObject-3.0</dc:identifier>
</oai_dc:dc>
';
    }

    protected function tearDown()
    {
        $this->api->m->purgeObject($this->testPid);
    }

    protected function getValue($data)
    {
        $values = $this->api->a->getObjectProfile($this->testPid);
        return $values[$data];
    }

    public function testFOXMLLabel()
    {
        $this->assertEquals('Test label', $this->object->label);
    }

    public function testFOXMLOwner()
    {

        $this->assertEquals('Test', $this->object->owner);
    }

    public function testFOXMLPid()
    {

        $this->assertEquals($this->object->id, $this->testPid);
        $this->assertTrue(isset($this->object->id));
    }

    public function testFOXMLState()
    {
        $this->assertEquals('A', $this->object->state);
    }

    public function testFOXMLDS()
    {
        $this->assertEquals(5, count($this->object));
    }

    public function testFOXMLDSDC()
    {
        $this->assertTrue(isset($this->object['DC']));
        $this->assertFalse(isset($this->object['foo']));
        $this->assertFalse($this->object['foo']);
        $this->assertInstanceOf(FedoraDatastream::class, $this->object['DC']);
        $this->assertEquals('DC', $this->object['DC']->id);
    }

    public function testFOXMLDSX()
    {
        $this->assertTrue(isset($this->object['INLINE']));
        $this->assertInstanceOf(FedoraDatastream::class, $this->object['INLINE']);
        $this->assertEquals('INLINE', $this->object['INLINE']->id);
        $this->assertEquals($this->mods_string, $this->object['INLINE']->content);
        $this->assertEquals('MD5', $this->object['INLINE']->checksumType);
    }

    public function testFOXMLDSM()
    {
        $this->assertTrue(isset($this->object['MANAGED']));
        $this->assertInstanceOf(FedoraDatastream::class, $this->object['MANAGED']);
        $this->assertEquals('MANAGED', $this->object['MANAGED']->id);
        $this->assertEquals($this->dc_content, $this->object['MANAGED']->content);
        $this->assertEquals('MD5', $this->object['MANAGED']->checksumType);
    }

    public function testFOXMLDSR()
    {
        $this->assertTrue(isset($this->object['REDIRECT']));
        $this->assertInstanceOf(FedoraDatastream::class, $this->object['REDIRECT']);
        $this->assertEquals('REDIRECT', $this->object['REDIRECT']->id);
        $this->assertEquals($this->dc_content, $this->object['REDIRECT']->content);
        $this->assertEquals('MD5', $this->object['REDIRECT']->checksumType);
    }

    public function testFOXMLDSE()
    {
        $this->assertTrue(isset($this->object['EXTERNAL']));
        $this->assertInstanceOf(FedoraDatastream::class, $this->object['EXTERNAL']);
        $this->assertEquals('EXTERNAL', $this->object['EXTERNAL']->id);
        $this->assertEquals($this->dc_content, $this->object['EXTERNAL']->content);
        $this->assertEquals('MD5', $this->object['EXTERNAL']->checksumType);
    }

    public function testFoxmlDsVersionable()
    {
        $this->assertTrue($this->object['MANAGED']->versionable);
    }

    public function testFoxmlDsNotVersionable()
    {
        $this->assertFalse($this->object['INLINE']->versionable);
    }

    public function testFoxmlDsLable()
    {
        $this->assertEquals('Managed datastream', $this->object['MANAGED']->label);
    }
}
