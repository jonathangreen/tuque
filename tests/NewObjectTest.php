<?php

namespace Islandora\Tuque\Tests;

use DOMDocument;
use DOMXPath;
use Islandora\Tuque\Api\FedoraApi;
use Islandora\Tuque\Api\FedoraApiSerializer;
use Islandora\Tuque\Cache\SimpleCache;
use GuzzleHttp\Client;
use Islandora\Tuque\Datastream\FedoraDatastream;
use Islandora\Tuque\Datastream\NewFedoraDatastream;
use Islandora\Tuque\Repository\FedoraRepository;

class NewObjectTest extends ObjectTest
{

    protected function setUp()
    {
        $guzzle = new Client(['base_uri' => FEDORAURL,'auth' => [FEDORAUSER, FEDORAPASS]]);
        $this->api = new FedoraApi($guzzle, new FedoraApiSerializer());
        $cache = new SimpleCache();
        $repository = new FedoraRepository($this->api, $cache);

        // create an object
        $string1 = TestHelpers::randomString(10);
        $string2 = TestHelpers::randomString(10);
        $this->testDsid = TestHelpers::randomCharString(10);
        $this->testPid = "$string1:$string2";
        $string3 = TestHelpers::randomString(9);
        $string4 = TestHelpers::randomString(9);
        $this->testDsid2 = TestHelpers::randomCharString(9);
        $this->testPid2 = "$string3:$string4";

        $this->object = $repository->constructObject($this->testPid);
        $ds = $this->object->constructDatastream($this->testDsid);
        $ds->content = "\n<test> test </test>\n";
        $this->object->ingestDatastream($ds);

        $ds = $this->object->constructDatastream('DC');
        $ds->content = '<test> test </test>';
        $this->object->ingestDatastream($ds);

        $this->existing_object = $repository->constructObject($this->testPid2);
        $ds2 = $this->existing_object->constructDatastream($this->testDsid2);
        $ds2->label = 'asdf';
        $ds2->mimetype = 'text/plain';
        $ds2->content = TestHelpers::randomString(10);
        $this->existing_object->ingestDatastream($ds2);
        $repository->ingestObject($this->existing_object);
    }

    protected function tearDown()
    {
    }


    public function testObjectIngestXmlDs()
    {
        $newds = $this->object->constructDatastream('test', 'X');
        $newds->content = '<xml/>';
        $this->object->ingestDatastream($newds);
        $this->assertEquals("<xml/>", $newds->content);
    }

    public function testValuesInFedora()
    {
    }

    public function testChangeId()
    {
        $newid = 'new:id';
        $this->object->id = $newid;
        $this->assertEquals($newid, $this->object->id);
    }

    public function testChangeIdWithRelsExt()
    {
        $newid = 'new:id';
        $this->object->relationships->add(FEDORA_MODEL_URI, 'hasModel', 'pid:woot');
        $this->object->id = $newid;
        $this->assertEquals($newid, $this->object->id);

        $dom = new DOMDocument();
        $dom->loadXml($this->object['RELS-EXT']->content);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('rdf', RDF_URI);

        $results = $xpath->query('/rdf:RDF/rdf:Description/@rdf:about');
        $this->assertEquals(1, $results->length);

        $value = $results->item(0);
        $uri = explode('/', $value->value);

        $this->assertEquals($newid, $uri[1]);
    }

    public function testChangeIdWithRelsInt()
    {
        $newid = 'new:id';
        $this->object['DC']->relationships->add(ISLANDORA_RELS_INT_URI, 'hasPage', 'some:otherpid');
        $this->object[$this->testDsid]->relationships->add(ISLANDORA_RELS_INT_URI, 'hasWoot', 'awesome:sauce');

        $this->object->id = $newid;
        $this->assertEquals($newid, $this->object->id);

        $dom = new DOMDocument();
        $dom->loadXml($this->object['RELS-INT']->content);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('rdf', RDF_URI);

        $results = $xpath->query('/rdf:RDF/rdf:Description/@rdf:about');
        $this->assertEquals(2, $results->length);

        foreach ($results as $result) {
            $value = $result->value;
            $uri = explode('/', $value);
            $this->assertEquals($newid, $uri[1]);
        }
    }

    public function testDatastreamMutation()
    {
        $datastream = $this->existing_object[$this->testDsid2];

        $this->assertTrue($datastream instanceof FedoraDatastream, 'Datastream exists.');
        $this->assertTrue($this->object->ingestDatastream($datastream) !== false, 'Datastream ingest succeeded.');
        $this->assertTrue($datastream instanceof NewFedoraDatastream, 'Datastream mutated on ingestion.');
    }
}
