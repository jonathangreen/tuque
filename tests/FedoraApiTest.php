<?php
/**
 * @file
 * A set of test classes that test the FedoraApi.php file
 */

require_once 'FedoraApi.php';
require_once 'FedoraApiSerializer.php';

define('FEDORAURL', 'http://vm0:8080/fedora');
define('FEDORAUSER', 'fedoraAdmin');
define('FEDORAPASS', 'password');

class FedoraTestHelpers {
  static function randomString($length) {
    $length = 10;
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $string ='';

    for ($p = 0; $p < $length; $p++) {
        $string .= $characters[mt_rand(0, (strlen($characters)-1))];
    }

    return $string;
  }
}

class FedoraApiIngestTest extends PHPUnit_Framework_TestCase {
  protected $pids = array();
  protected $files = array();

  protected function setUp() {
    $this->connection = new RepositoryConnection(FEDORAURL, FEDORAUSER, FEDORAPASS);
    $this->serializer = new FedoraApiSerializer();

    $this->apim = new FedoraApiM($this->connection, $this->serializer);
    $this->apia = new FedoraApiA($this->connection, $this->serializer);
  }

  protected function tearDown() {
    if (isset($this->pids) && is_array($this->pids)) {
      while ($pid = array_pop($this->pids)) {
        try {
          $this->apim->purgeObject($pid);
        }
        catch (RepositoryException $e) {}
      }
    }

    if (isset($this->files) && is_array($this->files)) {
      while ($file = array_pop($this->files)) {
        unlink($file);
      }
    }
  }

  public function testDescribeRepository() {
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

  public function testIngestNoPid() {
    $pid = $this->apim->ingest();
    $this->pids[] = $pid;
    $results = $this->apia->findObjects('query', "pid=$pid");
    $this->assertEquals(1, count($results['results']));
    $this->assertEquals($pid, $results['results'][0]['pid']);
  }
  
  public function testIngestRandomPid() {
    $string1 = FedoraTestHelpers::randomString(10);
    $string2 = FedoraTestHelpers::randomString(10);
    $expected_pid = "$string1:$string2";
    $actual_pid = $this->apim->ingest(array('pid' => $expected_pid));
    $this->pids[] = $actual_pid;
    $this->assertEquals($expected_pid, $actual_pid);
    $results = $this->apia->findObjects('query', "pid=$expected_pid");
    $this->assertEquals(1, count($results['results']));
    $this->assertEquals($expected_pid, $results['results'][0]['pid']);
  }

  public function testIngestStringFoxml() {
    $string1 = FedoraTestHelpers::randomString(10);
    $string2 = FedoraTestHelpers::randomString(10);
    $expected_pid = "$string1:$string2";
    $expected_label = FedoraTestHelpers::randomString(15);
    $foxml = <<<FOXML
<?xml version="1.0" encoding="UTF-8"?>
<foxml:digitalObject
  xmlns:foxml="info:fedora/fedora-system:def/foxml#"
  xmlns="info:fedora/fedora-system:def/foxml#"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  VERSION="1.1"
  PID="$expected_pid"
  xsi:schemaLocation="info:fedora/fedora-system:def/foxml#
  http://www.fedora.info/definitions/1/0/foxml1-1.xsd">
  <foxml:objectProperties>
    <foxml:property NAME="info:fedora/fedora-system:def/model#state" VALUE="A"/>
    <foxml:property NAME="info:fedora/fedora-system:def/model#label" VALUE="$expected_label"/>
  </foxml:objectProperties>
</foxml:digitalObject>
FOXML;

    $actual_pid = $this->apim->ingest(array('string' => $foxml));
    $this->pids[] = $actual_pid;
    $this->assertEquals($expected_pid, $actual_pid);
    $results = $this->apia->findObjects('query', "pid=$expected_pid", NULL, array('pid', 'label'));
    $this->assertEquals(1, count($results['results']));
    $this->assertEquals($expected_pid, $results['results'][0]['pid']);
    $this->assertEquals($expected_label, $results['results'][0]['label']);
  }

  public function testIngestFileFoxml() {
    $file_name = tempnam(sys_get_temp_dir(),'fedora_fixture');
    $string1 = FedoraTestHelpers::randomString(10);
    $string2 = FedoraTestHelpers::randomString(10);
    $expected_pid = "$string1:$string2";
    $expected_label = FedoraTestHelpers::randomString(15);
    $foxml = <<<FOXML
<?xml version="1.0" encoding="UTF-8"?>
<foxml:digitalObject
  xmlns:foxml="info:fedora/fedora-system:def/foxml#"
  xmlns="info:fedora/fedora-system:def/foxml#"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  VERSION="1.1"
  PID="$expected_pid"
  xsi:schemaLocation="info:fedora/fedora-system:def/foxml#
  http://www.fedora.info/definitions/1/0/foxml1-1.xsd">
  <foxml:objectProperties>
    <foxml:property NAME="info:fedora/fedora-system:def/model#label" VALUE="$expected_label"/>
  </foxml:objectProperties>
</foxml:digitalObject>
FOXML;
    file_put_contents($file_name, $foxml);
    $this->files[] = $file_name;

    $actual_pid = $this->apim->ingest(array('file' => $file_name));
    $this->pids[] = $actual_pid;
    $this->assertEquals($expected_pid, $actual_pid);
    $results = $this->apia->findObjects('query', "pid=$expected_pid", NULL, array('pid', 'label'));
    $this->assertEquals(1, count($results['results']));
    $this->assertEquals($expected_pid, $results['results'][0]['pid']);
    $this->assertEquals($expected_label, $results['results'][0]['label']);
  }

  public function testIngestLabel() {
    $string1 = FedoraTestHelpers::randomString(10);
    $string2 = FedoraTestHelpers::randomString(10);
    $pid = "$string1:$string2";
    $expected_label = FedoraTestHelpers::randomString(15);
    $pid = $this->apim->ingest(array('pid' => $pid, 'label' => $expected_label));
    $this->pids[] = $pid;
    $results = $this->apia->findObjects('query', "pid=$pid", NULL, array('pid', 'label'));
    $this->assertEquals(1, count($results['results']));
    $this->assertEquals($pid, $results['results'][0]['pid']);
    $this->assertEquals($expected_label, $results['results'][0]['label']);
  }
  
  public function testIngestLogMessage() {
    $string1 = FedoraTestHelpers::randomString(10);
    $string2 = FedoraTestHelpers::randomString(10);
    $pid = "$string1:$string2";
    $expected_log_message = FedoraTestHelpers::randomString(15);
    $pid = $this->apim->ingest(array('pid' => $pid, 'logMessage' => $expected_log_message));
    $this->pids[] = $pid;

    // Check the audit trail.
    $xml = $this->apim->export($pid);
    $dom = new DomDocument();
    $dom->loadXml($xml);
    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('audit', 'info:fedora/fedora-system:def/audit#');
    $result = $xpath->query('//audit:action[.="ingest"]/../audit:justification');
    $this->assertEquals(1, $result->length);
    $tag = $result->item(0);
    $this->assertEquals($expected_log_message, $tag->nodeValue);
  }

  public function testIngestNamespace() {
    $expected_namespace = FedoraTestHelpers::randomString(10);
    $pid = $this->apim->ingest(array('namespace' => $expected_namespace));
    $this->pids[] = $pid;
    $pid_parts = explode(':', $pid);
    $this->assertEquals($expected_namespace, $pid_parts[0]);
  }

  /**
   * @todo fix this test
   */
  public function testIngestOwnerId() {
    $this->markTestIncomplete();
    $string1 = FedoraTestHelpers::randomString(10);
    $string2 = FedoraTestHelpers::randomString(10);
    $pid = "$string1:$string2";
    $expected_owner = FedoraTestHelpers::randomString(15);
    $pid = $this->apim->ingest(array('pid' => $pid, 'ownerId' => $expected_owner));
    $this->pids[] = $pid;
    $results = $this->apia->findObjects('query', "pid=$pid", NULL, array('pid', 'ownerId'));
    $this->assertEquals(1, count($results['results']));
    $this->assertEquals($pid, $results['results'][0]['pid']);
    $this->assertEquals($expected_owner, $results['results'][0]['ownerId']);
  }

  /**
   * @todo finish this test
   * @todo we need some documents with different character encoding for this
   *   to work.
   */
  public function testIngestEncoding() {
    $this->markTestIncomplete();
    $string1 = FedoraTestHelpers::randomString(10);
    $string2 = FedoraTestHelpers::randomString(10);
    $expected_pid = "$string1:$string2";

    $actual_pid = $this->apim->ingest(array('string' => $foxml));
    $this->pids[] = $actual_pid;
    $this->assertEquals($expected_pid, $actual_pid);
  }

  /**
   * we need some files to ingest to test this
   */
  public function testIngestFormat() {
    $this->markTestIncomplete();
  }
}


class FedoraApiFindObjectsTest extends PHPUnit_Framework_TestCase {

  static $apim;
  static $apia;
  static $namespace;
  static $fixtures;
  static $display;
  static $pids;

  static function setUpBeforeClass() {
    $connection = new RepositoryConnection(FEDORAURL, FEDORAUSER, FEDORAPASS);
    $serializer = new FedoraApiSerializer();

    self::$apim = new FedoraApiM($connection, $serializer);
    self::$apia = new FedoraApiA($connection, $serializer);

    self::$namespace = FedoraTestHelpers::randomString(10);
    $pid1 = self::$namespace . ":" . FedoraTestHelpers::randomString(10);
    $pid2 = self::$namespace . ":" . FedoraTestHelpers::randomString(10);

    self::$fixtures = array();
    self::$pids = array();
    self::$pids[] = $pid1;
    self::$pids[] = $pid2;

    $string = file_get_contents('tests/test_data/fixture1.xml');
    $string = preg_replace('/\%PID\%/', $pid1, $string);
    $pid = self::$apim->ingest(array('string' => $string));
    self::$fixtures[$pid] = array(
      'pid' => $pid1,
      'label' => 'label1',
      'state' => 'I',
      'ownerId' => 'owner1',
      'cDate' => '2012-03-12T15:22:37.847Z',
      'dcmDate' => '2012-03-13T14:12:59.272Z',
      'title' => 'title1',
      'creator' => 'creator1',
      'subject' => 'subject1',
      'description' => 'description1',
      'publisher' => 'publisher1',
      'contributor' => 'contributor1',
      'date' => 'date1',
      'type' => 'type1',
      'format' => 'format1',
      'identifier' => $pid,
      'source' => 'source1',
      'language' => 'language1',
      'relation' => 'relation1',
      'coverage' => 'coverage1',
      'rights' => 'rights1',
    );
    $pid = self::$apim->ingest(array('pid' => $pid2, 'string' => file_get_contents('tests/test_data/fixture2.xml')));
    self::$fixtures[$pid] = array(
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
      'identifier' => $pid,
      'source' => 'source2',
      'language' => 'language2',
      'relation' => 'relation2',
      'coverage' => 'coverage2',
      'rights' => 'rights2',
    );
    self::$display = array( 'pid', 'label', 'state', 'ownerId', 'cDate', 'mDate',
      'dcmDate', 'title', 'creator', 'subject', 'description', 'publisher',
      'contributor', 'date', 'type', 'format', 'identifier', 'source',
      'language', 'relation', 'coverage', 'rights'
    );
  }

  static public function tearDownAfterClass()
  {
    foreach (self::$fixtures as $key => $value) {
      try {
        self::$apim->purgeObject($key);
      }
      catch (RepositoryException $e) {}
    }
  }

  public function testDescribeRepository() {
    $describe = self::$apia->describeRepository();
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

  function testFindObjectsTerms() {
    // Test all of the possible values.
    $namespace = self::$namespace;
    $result = self::$apia->findObjects('terms', "{$namespace}:*", 1, self::$display);
    $this->assertEquals(1,count($result['results']));
    $pid = $result['results'][0]['pid'];
    // Make sure we have the modified date key. But we unset it because we can't
    // test it, since it changes every time.
    $this->assertArrayHasKey('mDate', $result['results'][0]);
    unset($result['results'][0]['mDate']);
    $this->assertEquals(self::$fixtures[$pid],$result['results'][0]);

    // Test that we have a session key
    $this->assertArrayHasKey('session', $result);
    $this->assertArrayHasKey('token', $result['session']);
    return $result['session']['token'];
  }

  /**
   * @depends testFindObjectsTerms
   */
  function testFindObjectsTermsResume($token) {
    $result = self::$apia->resumeFindObjects($token);
    $this->assertEquals(1,count($result['results']));
    $this->assertArrayNotHasKey('session', $result);
    $pid = $result['results'][0]['pid'];
    // Make sure we have the modified date key. But we unset it because we can't
    // test it, since it changes every time.
    $this->assertArrayHasKey('mDate', $result['results'][0]);
    unset($result['results'][0]['mDate']);
    $this->assertEquals(self::$fixtures[$pid],$result['results'][0]);
  }

  function testFindObjectsQueryWildcard() {
    $namespace = self::$namespace;
    $result = self::$apia->findObjects('query', "pid~{$namespace}:*", NULL, self::$display);
    $this->assertEquals(2,count($result['results']));
    foreach($result['results'] as $results) {
      $this->assertArrayHasKey('mDate', $results);
      unset($results['mDate']);
      $this->assertEquals(self::$fixtures[$results['pid']], $results);
    }
  }

  function testFindObjectsQueryEquals() {
    $display = array_diff(self::$display, array('mDate'));
    foreach(self::$fixtures as $pid => $data) {
      foreach($data as $key => $value) {
        switch($key) {
          case 'cDate':
          case 'mDate':
          case 'dcmDate':
            $query = "pid=$pid ${key}=$value";
            break;
          default:
            $query = "pid=$pid ${key}~$value";
        }
        $result = self::$apia->findObjects('query', $query, NULL, $display);
        $this->assertEquals(1,count($result['results']));
        $this->assertEquals(self::$fixtures[$pid], $result['results'][0]);
      }
    }
  }

  function testGetDatastreamDissemination() {
    $expected = file_get_contents('tests/test_data/fixture1_fixture_newest.png');
    $actual = self::$apia->getDatastreamDissemination(self::$pids[0], 'fixture');
    $this->assertEquals($expected, $actual);
  }

  function testGetDatastreamDisseminationAsOfDate() {
    $expected = file_get_contents('tests/test_data/fixture1_fixture_oldest.png');
    $actual = self::$apia->getDatastreamDissemination(self::$pids[0], 'fixture', '2012-03-13T17:40:29.057Z');
    $this->assertEquals($expected, $actual);
  }

  function testGetDissemination() {
    $this->markTestIncomplete();
  }

  function testGetObjectHistory() {
    $expected = array('2012-03-13T14:12:59.272Z', '2012-03-13T17:40:29.057Z',
      '2012-03-13T18:09:25.425Z', '2012-03-13T19:15:07.529Z');
    $actual = self::$apia->getObjectHistory(self::$pids[0]);
    $this->assertEquals($expected, $actual);

    $expected = array('2010-03-13T14:12:59.272Z');
    $actual = self::$apia->getObjectHistory(self::$pids[1]);
    $this->assertEquals($expected, $actual);
  }

  // This one is interesting because the flattendocument function doesn't
  // work on it. So we have to handparse it. So we test to make sure its okay.
  function testGetObjectProfile() {
    foreach ( self::$pids as $pid) {
      $data = self::$fixtures[$pid];
      $actual = self::$apia->getObjectProfile($pid);
      $this->assertArrayHasKey('objCreateDate', $actual);
      $this->assertEquals($data['label'], $actual['objLabel']);
      $this->assertEquals($data['ownerId'], $actual['objOwnerId']);
      $this->assertEquals($data['state'], $actual['objState']);
      $this->assertEquals($data['cDate'], $actual['objCreateDate']);
    }
  }
}