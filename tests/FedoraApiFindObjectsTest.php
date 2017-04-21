<?php

namespace Islandora\Tuque\Tests;

use DOMDocument;
use Islandora\Tuque\Api\FedoraApiA;
use Islandora\Tuque\Api\FedoraApiM;
use Islandora\Tuque\Api\FedoraApiSerializer;
use Islandora\Tuque\Connection\GuzzleConnection;
use Islandora\Tuque\Exception\RepositoryException;
use PHPUnit_Framework_TestCase;

class FedoraApiFindObjectsTest extends PHPUnit_Framework_TestCase
{

    public $apim;
    public $apia;
    public $namespace;
    public $fixtures;
    public $display;
    public $pids;

    static $purge = true;
    static $saved;

    protected function sanitizeObjectProfile($profile)
    {
        $profile['objDissIndexViewURL'] = parse_url($profile['objDissIndexViewURL'], PHP_URL_PATH);
        $profile['objItemIndexViewURL'] = parse_url($profile['objItemIndexViewURL'], PHP_URL_PATH);
        return $profile;
    }

    protected function setUp()
    {
        $connection = new GuzzleConnection(FEDORAURL, FEDORAUSER, FEDORAPASS);
        $serializer = new FedoraApiSerializer();

        $this->apim = new FedoraApiM($connection, $serializer);
        $this->apia = new FedoraApiA($connection, $serializer);

        if (self::$purge == false) {
            $this->fixtures = self::$saved;
            return;
        }

        $this->namespace = TestHelpers::randomString(10);
        $pid1 = $this->namespace . ":" . TestHelpers::randomString(10);
        $pid2 = $this->namespace . ":" . TestHelpers::randomString(10);

        $this->fixtures = [];
        $this->pids = [];
        $this->pids[] = $pid1;
        $this->pids[] = $pid2;

        // Set up some arrays of data for the fixtures.
        $string = file_get_contents('tests/test_data/fixture1.xml');
        $string = preg_replace('/\%PID\%/', $pid1, $string);
        $pid = $this->apim->ingest(['string' => $string]);
        $urlpid = urlencode($pid);
        $this->fixtures[$pid] = [];
        $this->fixtures[$pid]['xml'] = $string;
        $this->fixtures[$pid]['findObjects'] = [ 'pid' => $pid1,
            'label' => 'label1', 'state' => 'I', 'ownerId' => 'owner1',
            'cDate' => '2012-03-12T15:22:37.847Z', 'dcmDate' => '2012-03-13T14:12:59.272Z',
            'title' => 'title1', 'creator' => 'creator1', 'subject' => 'subject1',
            'description' => 'description1', 'publisher' => 'publisher1',
            'contributor' => 'contributor1', 'date' => 'date1', 'type' => 'type1',
            'format' => 'format1',
            //'identifier' => $pid,
            'source' => 'source1',
            'language' => 'language1', 'relation' => 'relation1', 'coverage' => 'coverage1',
            'rights' => 'rights1',
        ];
        $this->fixtures[$pid]['getObjectHistory'] = ['2012-03-13T14:12:59.272Z',
            '2012-03-13T17:40:29.057Z', '2012-03-13T18:09:25.425Z',
            '2012-03-13T19:15:07.529Z'];
        $this->fixtures[$pid]['getObjectProfile'] = [
            'objLabel' => $this->fixtures[$pid]['findObjects']['label'],
            'objOwnerId' => $this->fixtures[$pid]['findObjects']['ownerId'],
            'objModels' => ['info:fedora/fedora-system:FedoraObject-3.0',
                'info:fedora/testnamespace:test'],
            'objCreateDate' => $this->fixtures[$pid]['findObjects']['cDate'],
            'objDissIndexViewURL' => "http://localhost:8080/fedora/objects/$urlpid/methods/fedora-system%3A3/viewMethodIndex",
            'objItemIndexViewURL' => "http://localhost:8080/fedora/objects/$urlpid/methods/fedora-system%3A3/viewItemIndex",
            'objState' => $this->fixtures[$pid]['findObjects']['state'],
        ];
        $this->fixtures[$pid]['listDatastreams'] = [
            '2012-03-13T14:12:59.272Z' => [
                'DC' => [
                    'label' => 'Dublin Core Record for this object',
                    'mimetype' => 'text/xml',
                ],
            ],
            '2012-03-13T17:40:29.057Z' => [
                'DC' => [
                    'label' => 'Dublin Core Record for this object',
                    'mimetype' => 'text/xml',
                ],
                'fixture' => [
                    'label' => 'label',
                    'mimetype' => 'image/png',
                ],
            ],
            '2012-03-13T18:09:25.425Z' => [
                'DC' => [
                    'label' => 'Dublin Core Record for this object',
                    'mimetype' => 'text/xml',
                ],
                'fixture' => [
                    'label' => 'label',
                    'mimetype' => 'image/png',
                ],
            ],
            '2012-03-13T19:15:07.529Z' => [
                'DC' => [
                    'label' => 'Dublin Core Record for this object',
                    'mimetype' => 'text/xml',
                ],
                'fixture' => [
                    'label' => 'label',
                    'mimetype' => 'image/png',
                ],
                'RELS-EXT' => [
                    'label' => 'Fedora Relationships Metadata',
                    'mimetype' => 'text/xml',
                ],
            ],
        ];
        $this->fixtures[$pid]['dsids'] = [
            'DC' => [
                'data' => [
                    'dsLabel' => 'Dublin Core Record for this object',
                    'dsVersionID' => 'DC.1',
                    'dsCreateDate' => '2012-03-13T14:12:59.272Z',
                    'dsState' => 'A',
                    'dsMIME' => 'text/xml',
                    'dsFormatURI' => 'http://www.openarchives.org/OAI/2.0/oai_dc/',
                    'dsControlGroup' => 'X',
                    'dsSize' => '860',
                    'dsVersionable' => 'true',
                    'dsLocation' => "$pid+DC+DC.1",
                    'dsLocationType' => '',
                    'dsChecksumType' => 'DISABLED',
                    'dsChecksum' => 'none',
                ],
                'count' => 1,
            ],
            'fixture' => [
                'data' => [
                    'dsLabel' => 'label',
                    'dsVersionID' => 'fixture.4',
                    'dsCreateDate' => '2012-03-13T18:09:25.425Z',
                    'dsState' => 'A',
                    'dsMIME' => 'image/png',
                    'dsFormatURI' => 'format',
                    'dsControlGroup' => 'M',
                    'dsSize' => '68524',
                    'dsVersionable' => 'true',
                    'dsLocation' => "$pid+fixture+fixture.4",
                    'dsLocationType' => 'INTERNAL_ID',
                    'dsChecksumType' => 'DISABLED',
                    'dsChecksum' => 'none',
                ],
                'count' => 2,
            ],
            'RELS-EXT' => [
                'data' => [
                    'dsLabel' => 'Fedora Relationships Metadata',
                    'dsVersionID' => 'RELS-EXT.0',
                    'dsCreateDate' => '2012-03-13T19:15:07.529Z',
                    'dsState' => 'A',
                    'dsMIME' => 'text/xml',
                    'dsFormatURI' => '',
                    'dsControlGroup' => 'X',
                    'dsSize' => '540',
                    'dsVersionable' => 'true',
                    'dsLocation' => "$pid+RELS-EXT+RELS-EXT.0",
                    'dsLocationType' => 'INTERNAL_ID',
                    'dsChecksumType' => 'DISABLED',
                    'dsChecksum' => 'none',
                ],
                'count' => 1,
            ],
        ];

        // second fixture
        $string = file_get_contents('tests/test_data/fixture2.xml');
        $pid = $this->apim->ingest(['pid' => $pid2, 'string' => $string]);
        $urlpid = urlencode($pid);
        $this->fixtures[$pid] = [];
        $this->fixtures[$pid]['xml'] = $string;
        $this->fixtures[$pid]['findObjects'] = [
            'pid' => $pid,
            'label' => 'label2',
            'state' => 'A',
            'ownerId' => 'owner2',
            'cDate' => '2000-03-12T15:22:37.847Z',
            'dcmDate' => '2010-03-13T14:12:59.272Z',
            'title' => 'title2',
            'creator' => 'creator2',
            'subject' => 'subject2',
            'description' => 'description2',
            'publisher' => 'publisher2',
            'contributor' => 'contributor2',
            'date' => 'date2',
            'type' => 'type2',
            'format' => 'format2',
            //'identifier' => array('identifier2', $pid),
            'source' => 'source2',
            'language' => 'language2',
            'relation' => 'relation2',
            'coverage' => 'coverage2',
            'rights' => 'rights2',
        ];
        $this->fixtures[$pid]['getObjectHistory'] = ['2010-03-13T14:12:59.272Z'];
        $this->fixtures[$pid]['getObjectProfile'] = [
            'objLabel' => $this->fixtures[$pid]['findObjects']['label'],
            'objOwnerId' => $this->fixtures[$pid]['findObjects']['ownerId'],
            'objModels' => ['info:fedora/fedora-system:FedoraObject-3.0'],
            'objCreateDate' => $this->fixtures[$pid]['findObjects']['cDate'],
            'objDissIndexViewURL' => "http://localhost:8080/fedora/objects/$urlpid/methods/fedora-system%3A3/viewMethodIndex",
            'objItemIndexViewURL' => "http://localhost:8080/fedora/objects/$urlpid/methods/fedora-system%3A3/viewItemIndex",
            'objState' => $this->fixtures[$pid]['findObjects']['state'],
        ];
        $this->fixtures[$pid]['listDatastreams'] = [
            '2010-03-13T14:12:59.272Z' => [
                'DC' => [
                    'label' => 'Dublin Core Record for this object',
                    'mimetype' => 'text/xml',
                ],
            ],
        ];
        $this->fixtures[$pid]['dsids'] = [
            'DC' => [
                'data' => [
                    'dsLabel' => 'Dublin Core Record for this object',
                    'dsVersionID' => 'DC.1',
                    'dsCreateDate' => '2010-03-13T14:12:59.272Z',
                    'dsState' => 'A',
                    'dsMIME' => 'text/xml',
                    'dsFormatURI' => 'http://www.openarchives.org/OAI/2.0/oai_dc/',
                    'dsControlGroup' => 'X',
                    'dsSize' => '905',
                    'dsVersionable' => 'true',
                    'dsLocation' => "$pid+DC+DC.1",
                    'dsLocationType' => '',
                    'dsChecksumType' => 'DISABLED',
                    'dsChecksum' => 'none',
                ],
                'count' => 1,
            ],
        ];

        $this->display = [ 'pid', 'label', 'state', 'ownerId', 'cDate', 'mDate',
            'dcmDate', 'title', 'creator', 'subject', 'description', 'publisher',
            'contributor', 'date', 'type', 'format', 'identifier', 'source',
            'language', 'relation', 'coverage', 'rights'
        ];
    }

    protected function tearDown()
    {
        if (self::$purge) {
            foreach ($this->fixtures as $key => $value) {
                try {
                    $this->apim->purgeObject($key);
                } catch (RepositoryException $e) {
                }
            }
        } else {
            self::$saved = $this->fixtures;
        }
    }

    public function testDescribeRepository()
    {
        $describe = $this->apia->describeRepository();
        $this->assertArrayHasKey('repositoryName', $describe);
        $this->assertArrayHasKey('repositoryBaseURL', $describe);
        $this->assertArrayHasKey('repositoryPID', $describe);
        $this->assertArrayHasKey('PID-namespaceIdentifier', $describe['repositoryPID']);
        $this->assertArrayHasKey('PID-delimiter', $describe['repositoryPID']);
        $this->assertArrayHasKey('PID-sample', $describe['repositoryPID']);
        $this->assertArrayHasKey('retainPID', $describe['repositoryPID']);
        $this->assertArrayHasKey('repositoryOAI-identifier', $describe);
        $this->assertArrayHasKey('OAI-namespaceIdentifier', $describe['repositoryOAI-identifier']);
        $this->assertArrayHasKey('OAI-delimiter', $describe['repositoryOAI-identifier']);
        $this->assertArrayHasKey('OAI-sample', $describe['repositoryOAI-identifier']);
        $this->assertArrayHasKey('sampleSearch-URL', $describe);
        $this->assertArrayHasKey('sampleAccess-URL', $describe);
        $this->assertArrayHasKey('sampleOAI-URL', $describe);
        $this->assertArrayHasKey('adminEmail', $describe);
    }

    function testFindObjectsTerms()
    {
        // Test all of the possible values.
        $namespace = $this->namespace;
        $result = $this->apia->findObjects('terms', "{$namespace}:*", 1, $this->display);
        $this->assertEquals(1, count($result['results']));
        $pid = $result['results'][0]['pid'];
        // Make sure we have the modified date key. But we unset it because we can't
        // test it, since it changes every time.
        $this->assertArrayHasKey('mDate', $result['results'][0]);
        unset($result['results'][0]['mDate']);
        unset($result['results'][0]['identifier']);
        $this->assertEquals($this->fixtures[$pid]['findObjects'], $result['results'][0]);

        // Test that we have a session key
        $this->assertArrayHasKey('session', $result);
        $this->assertArrayHasKey('token', $result['session']);
        self::$purge = false;
        return $result['session']['token'];
    }

    /**
     * @depends testFindObjectsTerms
     */
    function testFindObjectsTermsResume($token)
    {
        self::$purge = true;
        $result = $this->apia->resumeFindObjects($token);
        $this->assertEquals(1, count($result['results']));
        $this->assertArrayNotHasKey('session', $result);
        $pid = $result['results'][0]['pid'];
        // Make sure we have the modified date key. But we unset it because we can't
        // test it, since it changes every time.
        $this->assertArrayHasKey('mDate', $result['results'][0]);
        unset($result['results'][0]['mDate']);
        unset($result['results'][0]['identifier']);
        $this->assertEquals($this->fixtures[$pid]['findObjects'], $result['results'][0]);
    }

    function testFindObjectsQueryWildcard()
    {
        $namespace = $this->namespace;
        $result = $this->apia->findObjects('query', "pid~{$namespace}:*", null, $this->display);
        $this->assertEquals(2, count($result['results']));
        foreach ($result['results'] as $results) {
            $this->assertArrayHasKey('mDate', $results);
            unset($results['mDate']);
            unset($results['identifier']);
            $this->assertEquals($this->fixtures[$results['pid']]['findObjects'], $results);
        }
    }

    function testFindObjectsQueryEquals()
    {
        $display = array_diff($this->display, ['mDate']);
        foreach ($this->fixtures as $pid => $fixtures) {
            $data = $fixtures['findObjects'];
            foreach ($data as $key => $array) {
                if (!is_array($array)) {
                    $array = [$array];
                }
                foreach ($array as $value) {
                    switch ($key) {
                        case 'cDate':
                        case 'mDate':
                        case 'dcmDate':
                            $query = "pid=$pid ${key}=$value";
                            break;
                        default:
                            $query = "pid=$pid ${key}~$value";
                    }
                    $result = $this->apia->findObjects('query', $query, null, $display);
                    $this->assertEquals(1, count($result['results']));
                    unset($result['results'][0]['identifier']);
                    $this->assertEquals($this->fixtures[$pid]['findObjects'], $result['results'][0]);
                }
            }
        }
    }

    function testGetDatastreamDissemination()
    {
        $expected = file_get_contents('tests/test_data/fixture1_fixture_newest.png');
        $actual = $this->apia->getDatastreamDissemination($this->pids[0], 'fixture');
        $this->assertEquals($expected, $actual);
    }

    function testGetDatastreamDisseminationAsOfDate()
    {
        $expected = file_get_contents('tests/test_data/fixture1_fixture_oldest.png');
        $actual = $this->apia->getDatastreamDissemination($this->pids[0], 'fixture', '2012-03-13T17:40:29.057Z');
        $this->assertEquals($expected, $actual);
    }

    function testGetDatastreamDisseminationToFile()
    {
        $expected = file_get_contents('tests/test_data/fixture1_fixture_newest.png');
        $file = tempnam(sys_get_temp_dir(), "test");
        $return = $this->apia->getDatastreamDissemination($this->pids[0], 'fixture', null, $file);
        $this->assertTrue($return);
        $this->assertEquals($expected, file_get_contents($file));
        unlink($file);
    }

    function testGetDissemination()
    {
        $this->markTestIncomplete();
    }

    function testGetObjectHistory()
    {
        foreach ($this->fixtures as $pid => $fixture) {
            $actual = $this->apia->getObjectHistory($pid);
            $this->assertEquals($fixture['getObjectHistory'], $actual);
        }
    }

    // This one is interesting because the flattendocument function doesn't
    // work on it. So we have to handparse it. So we test to make sure its okay.
    // @todo Test the second arguement to this
    function testGetObjectProfile()
    {
        foreach ($this->fixtures as $pid => $fixture) {
            $expected = $fixture['getObjectProfile'];
            $actual = $this->apia->getObjectProfile($pid);
            $this->assertArrayHasKey('objLastModDate', $actual);
            unset($actual['objLastModDate']);
            // The content models come back in an undefined order, so we need
            // to test them individually.
            $this->assertArrayHasKey('objModels', $actual);
            $this->assertEquals(count($expected['objModels']), count($actual['objModels']));
            foreach ($actual['objModels'] as $model) {
                $this->assertTrue(in_array($model, $actual['objModels']));
            }
            unset($actual['objModels']);
            unset($expected['objModels']);
            $expected = $this->sanitizeObjectProfile($expected);
            $actual = $this->sanitizeObjectProfile($actual);
            $this->assertEquals($expected, $actual);
        }
    }

    function testListDatastreams()
    {
        foreach ($this->fixtures as $pid => $fixture) {
            foreach ($fixture['getObjectHistory'] as $datetime) {
                $actual = $this->apia->listDatastreams($pid, $datetime);
                $this->assertEquals($fixture['listDatastreams'][$datetime], $actual);
            }
            $revisions = count($fixture['getObjectHistory']);
            $date = $fixture['getObjectHistory'][$revisions-1];
            $acutal = $this->apia->listDatastreams($pid);
            $this->assertEquals($fixture['listDatastreams'][$date], $actual);
        }
    }

    function testListMethods()
    {
        $this->markTestIncomplete();
    }

    function testExport()
    {
        $this->markTestIncomplete();
        // One would think this would work, but there are a few problems
        // a number of tags change on ingest, so we need to do a more in
        // depth comparison.
        foreach ($this->fixtures as $pid => $fixture) {
            $actual = $this->apim->export($pid, ['context' => 'archive']);
            $dom = [];
            $dom[] = new DOMDocument();
            $dom[] = new DOMDocument();
            $dom[0]->loadXML($actual);
            $dom[1]->loadXML($fixture['xml']);

            $this->assertEquals($dom[1], $dom[0]);
        }
    }

    function testGetDatastream()
    {
        foreach ($this->fixtures as $pid => $fixture) {
            $listDatastreams = $fixture['listDatastreams'];

            // Do a test with the data we have.
            foreach ($listDatastreams as $time => $datastreams) {
                foreach ($datastreams as $dsid => $data) {
                    $actual = $this->apim->getDatastream($pid, $dsid, ['asOfDateTime' => $time]);
                    $this->assertEquals($data['label'], $actual['dsLabel']);
                    $this->assertEquals($data['mimetype'], $actual['dsMIME']);
                    $this->assertArrayHasKey('dsVersionID', $actual);
                    $this->assertArrayHasKey('dsCreateDate', $actual);
                    $this->assertArrayHasKey('dsState', $actual);
                    $this->assertArrayHasKey('dsMIME', $actual);
                    $this->assertArrayHasKey('dsFormatURI', $actual);
                    $this->assertArrayHasKey('dsControlGroup', $actual);
                    $this->assertArrayHasKey('dsSize', $actual);
                    $this->assertArrayHasKey('dsVersionable', $actual);
                    $this->assertArrayHasKey('dsInfoType', $actual);
                    $this->assertArrayHasKey('dsLocation', $actual);
                    $this->assertArrayHasKey('dsLocationType', $actual);
                    $this->assertArrayHasKey('dsChecksumType', $actual);
                    $this->assertArrayHasKey('dsChecksum', $actual);
                }
            }

            // Test with the more detailed current data.
            foreach ($fixture['dsids'] as $dsid => $data) {
                $actual = $this->apim->getDatastream($pid, $dsid);
                unset($actual['dsInfoType']);
                $this->assertEquals($data['data'], $actual);
            }
        }
    }

    function testGetDatastreamHistory()
    {
        foreach ($this->fixtures as $pid => $fixture) {
            foreach ($fixture['dsids'] as $dsid => $data) {
                $actual = $this->apim->getDatastreamHistory($pid, $dsid);
                // we should at least make sure we get the right count here
                $this->assertEquals($data['count'], count($actual));
                unset($actual[0]['dsInfoType']);
                $this->assertEquals($data['data'], $actual[0]);
            }
        }
    }

    function testGetNextPid()
    {
        $pid = $this->apim->getNextPid();
        $this->assertInternalType('string', $pid);

        $namespace = TestHelpers::randomString(10);
        $pids = $this->apim->getNextPid($namespace, 5);
        $this->assertInternalType('array', $pids);
        $this->assertEquals(5, count($pids));

        foreach ($pids as $pid) {
            $pid = explode(':', $pid);
            $this->assertEquals($namespace, $pid[0]);
        }
    }

    /**
     * @depends testGetObjectProfile
     */
    function testModifyObjectLabel()
    {
        foreach ($this->fixtures as $pid => $fixture) {
            $this->apim->modifyObject($pid, ['label' => 'wallawalla']);
            $expected = $fixture['getObjectProfile'];
            $actual = $this->apia->getObjectProfile($pid);
            $this->assertEquals('wallawalla', $actual['objLabel']);
            unset($actual['objLabel']);
            unset($actual['objLastModDate']);
            unset($actual['objModels']);
            unset($expected['objModels']);
            unset($expected['objLabel']);
            $expected = $this->sanitizeObjectProfile($expected);
            $actual = $this->sanitizeObjectProfile($actual);
            $this->assertEquals($expected, $actual);
        }
    }

    /**
     * @depends testGetObjectProfile
     */
    function testModifyObjectOwnerId()
    {
        foreach ($this->fixtures as $pid => $fixture) {
            $this->apim->modifyObject($pid, ['ownerId' => 'wallawalla']);
            $expected = $fixture['getObjectProfile'];
            $actual = $this->apia->getObjectProfile($pid);
            $this->assertEquals('wallawalla', $actual['objOwnerId']);
            unset($actual['objOwnerId']);
            unset($actual['objLastModDate']);
            unset($actual['objModels']);
            unset($expected['objModels']);
            unset($expected['objOwnerId']);
            $expected = $this->sanitizeObjectProfile($expected);
            $actual = $this->sanitizeObjectProfile($actual);
            $this->assertEquals($expected, $actual);
        }
    }

    /**
     * @depends testGetObjectProfile
     */
    function testModifyObjectState()
    {
        foreach ($this->fixtures as $pid => $fixture) {
            foreach (['D', 'I', 'A'] as $state) {
                $this->apim->modifyObject($pid, ['state' => $state]);
                $expected = $fixture['getObjectProfile'];
                $actual = $this->apia->getObjectProfile($pid);
                $this->assertEquals($state, $actual['objState']);
                unset($actual['objState']);
                unset($actual['objLastModDate']);
                unset($actual['objModels']);
                unset($expected['objModels']);
                unset($expected['objState']);
                $expected = $this->sanitizeObjectProfile($expected);
                $actual = $this->sanitizeObjectProfile($actual);
                $this->assertEquals($expected, $actual);
            }
        }
    }

    /**
     * @depends testGetDatastream
     */
    function testModifyDatastreamLabel()
    {
        foreach ($this->fixtures as $pid => $fixture) {
            foreach ($fixture['dsids'] as $dsid => $data) {
                $this->apim->modifyDatastream($pid, $dsid, ['dsLabel' => 'testtesttest']);
                $actual = $this->apim->getDatastream($pid, $dsid);
                $expected = $data['data'];
                $this->assertEquals('testtesttest', $actual['dsLabel']);
                foreach (['dsLabel', 'dsCreateDate', 'dsVersionID', 'dsLocation', 'dsInfoType'] as $unset) {
                    unset($actual[$unset]);
                    unset($expected[$unset]);
                }
                $this->assertEquals($expected, $actual);
            }
        }
    }

    /**
     * @depends testGetDatastream
     */
    function testModifyDatastreamVersionable()
    {
        foreach ($this->fixtures as $pid => $fixture) {
            foreach ($fixture['dsids'] as $dsid => $data) {
                foreach ([false, true] as $versionable) {
                    $this->apim->modifyDatastream($pid, $dsid, ['versionable' => $versionable]);
                    $actual = $this->apim->getDatastream($pid, $dsid);
                    $expected = $data['data'];
                    $this->assertEquals($versionable ? 'true' : 'false', $actual['dsVersionable']);
                    foreach (['dsVersionable', 'dsCreateDate', 'dsVersionID', 'dsLocation', 'dsInfoType'] as $unset) {
                        unset($actual[$unset]);
                        unset($expected[$unset]);
                    }
                    $this->assertEquals($expected, $actual);
                }
            }
        }
    }

    /**
     * @depends testGetDatastream
     */
    function testModifyDatastreamVersionableOldVersions()
    {
        $this->markTestIncomplete();
        $pid = $this->pids[0];
        $dsid = 'fixture';

        $before_history = $this->apim->getDatastreamHistory($pid, $dsid);
        print_r($before_history);
        $this->apim->modifyDatastream($pid, $dsid, ['versionable' => false]);
        $after_history = $this->apim->getDatastreamHistory($pid, $dsid);
        print_r($after_history);
        $this->apim->modifyDatastream($pid, $dsid, ['dsLabel' => 'goo']);
        $after_history = $this->apim->getDatastreamHistory($pid, $dsid);
        print_r($after_history);
    }

    /**
     * @depends testGetDatastream
     */
    function testModifyDatastreamState()
    {
        foreach ($this->fixtures as $pid => $fixture) {
            foreach ($fixture['dsids'] as $dsid => $data) {
                foreach (['I', 'D', 'A'] as $state) {
                    $this->apim->modifyDatastream($pid, $dsid, ['dsState' => $state]);
                    $actual = $this->apim->getDatastream($pid, $dsid);
                    $expected = $data['data'];
                    $this->assertEquals($state, $actual['dsState']);
                    foreach (['dsState', 'dsCreateDate', 'dsVersionID', 'dsLocation', 'dsInfoType'] as $unset) {
                        unset($actual[$unset]);
                        unset($expected[$unset]);
                    }
                    $this->assertEquals($expected, $actual);
                }
            }
        }
    }

    /**
     * @depends testGetDatastream
     */
    function testModifyDatastreamChecksum()
    {
        foreach ($this->fixtures as $pid => $fixture) {
            foreach ($fixture['dsids'] as $dsid => $data) {
                foreach (['MD5', 'SHA-1', 'SHA-256', 'SHA-384', 'SHA-512', 'DISABLED'] as $type) {
                    $this->apim->modifyDatastream($pid, $dsid, ['checksumType' => $type]);
                    $actual = $this->apim->getDatastream($pid, $dsid);
                    $expected = $data['data'];
                    $this->assertEquals($type, $actual['dsChecksumType']);
                    foreach (['dsChecksumType', 'dsChecksum', 'dsCreateDate', 'dsVersionID', 'dsLocation', 'dsInfoType'] as $unset) {
                        unset($actual[$unset]);
                        unset($expected[$unset]);
                    }
                    $this->assertEquals($expected, $actual);

                    if ($actual['dsControlGroup'] == "M") {
                        $dscontent = $this->apia->getDatastreamDissemination($pid, $dsid);
                        switch ($type) {
                            case 'MD5':
                                $hash = hash('md5', $dscontent);
                                break;
                            case 'SHA-1':
                                $hash = hash('sha1', $dscontent);
                                break;
                            case 'SHA-256':
                                $hash = hash('sha256', $dscontent);
                                break;
                            case 'SHA-384':
                                $hash = hash('sha384', $dscontent);
                                break;
                            case 'SHA-512':
                                $hash = hash('sha512', $dscontent);
                                break;
                            case 'DISABLED':
                                $hash = 'none';
                                break;
                        }

                        $this->apim->modifyDatastream($pid, $dsid, ['checksumType' => $type, 'checksum' => $hash]);
                        $actual = $this->apim->getDatastream($pid, $dsid);
                        $this->assertEquals($hash, $actual['dsChecksum']);
                    }
                }
            }
        }
    }

    /**
     * @depends testGetDatastream
     */
    function testModifyDatastreamFormatURI()
    {
        foreach ($this->fixtures as $pid => $fixture) {
            foreach ($fixture['dsids'] as $dsid => $data) {
                $this->apim->modifyDatastream($pid, $dsid, ['formatURI' => 'testtesttest']);
                $actual = $this->apim->getDatastream($pid, $dsid);
                $expected = $data['data'];
                $this->assertEquals('testtesttest', $actual['dsFormatURI']);
                foreach (['dsFormatURI', 'dsCreateDate', 'dsVersionID', 'dsLocation', 'dsInfoType'] as $unset) {
                    unset($actual[$unset]);
                    unset($expected[$unset]);
                }
                $this->assertEquals($expected, $actual);
            }
        }
    }

    /**
     * @depends testGetDatastream
     */
    function testModifyDatastreamMimeType()
    {
        foreach ($this->fixtures as $pid => $fixture) {
            foreach ($fixture['dsids'] as $dsid => $data) {
                $this->apim->modifyDatastream($pid, $dsid, ['mimeType' => 'application/super-fucking-cool']);
                $actual = $this->apim->getDatastream($pid, $dsid);
                $expected = $data['data'];
                $this->assertEquals('application/super-fucking-cool', $actual['dsMIME']);
                foreach (['dsMIME', 'dsCreateDate', 'dsVersionID', 'dsLocation', 'dsInfoType'] as $unset) {
                    unset($actual[$unset]);
                    unset($expected[$unset]);
                }
                $this->assertEquals($expected, $actual);
            }
        }
    }

    /**
     * @depends testGetDatastream
     */
    function testModifyDatastreamAltIds()
    {
        foreach ($this->fixtures as $pid => $fixture) {
            foreach ($fixture['dsids'] as $dsid => $data) {
                $this->apim->modifyDatastream($pid, $dsid, ['altIDs' => "one two three"]);
                $actual = $this->apim->getDatastream($pid, $dsid);
                $expected = $data['data'];
                $this->assertArrayHasKey('dsAltID', $actual);
                $this->assertEquals(['one', 'two', 'three'], $actual['dsAltID']);
                unset($actual['dsAltID']);
                foreach (['dsCreateDate', 'dsVersionID', 'dsLocation', 'dsInfoType'] as $unset) {
                    unset($actual[$unset]);
                    unset($expected[$unset]);
                }
                $this->assertEquals($expected, $actual);
            }
        }
    }
}
