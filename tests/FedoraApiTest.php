<?php
/**
 * @file
 * A set of test classes that test the FedoraApi.php file
 */

require_once '../FedoraApi.php';
require_once '../FedoraApiSerializer.php';

define('FEDORAURL', 'http://vm0:8080/fedora');
define('FEDORAUSER', 'fedoraAdmin');
define('FEDORAPASS', 'password');


class FedoraApiADescribeRespositoryTest extends PHPUnit_Framework_TestCase {
  protected $pids = array();
  protected $files = array();

  protected function setUp() {
    $this->connection = new RepositoryConnection(FEDORAURL, FEDORAUSER, FEDORAPASS);
    $this->serializer = new FedoraApiSerializer();

    $this->connection->debug = TRUE;
    $this->connection->reuseConnection = TRUE;

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

  protected function randomString($length) {
    $length = 10;
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $string ='';

    for ($p = 0; $p < $length; $p++) {

        $string .= $characters[mt_rand(0, (strlen($characters)-1))];
    }

    return $string;
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
    $string1 = $this->randomString(10);
    $string2 = $this->randomString(10);
    $expected_pid = "$string1:$string2";
    $actual_pid = $this->apim->ingest(array('pid' => $expected_pid));
    $this->pids[] = $actual_pid;
    $this->assertEquals($expected_pid, $actual_pid);
    $results = $this->apia->findObjects('query', "pid=$expected_pid");
    $this->assertEquals(1, count($results['results']));
    $this->assertEquals($expected_pid, $results['results'][0]['pid']);
  }

  public function testIngestStringFoxml() {
    $string1 = $this->randomString(10);
    $string2 = $this->randomString(10);
    $expected_pid = "$string1:$string2";
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
  </foxml:objectProperties>
</foxml:digitalObject>
FOXML;

    $actual_pid = $this->apim->ingest(array('string' => $foxml));
    $this->assertEquals($expected_pid, $actual_pid);
    $results = $this->apia->findObjects('query', "pid=$expected_pid");
    $this->assertEquals(1, count($results['results']));
    $this->assertEquals($expected_pid, $results['results'][0]['pid']);
  }

  /*
  public function testIngestFileFoxml() {
    $file_name = tempnam(sys_get_temp_dir(),'fedora_fixture');
    $string1 = $this->randomString(10);
    $string2 = $this->randomString(10);
    $expected_pid = "$string1:$string2";
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
    <foxml:property NAME="info:fedora/fedora-system:def/model#label" VALUE="foo"/>
  </foxml:objectProperties>
</foxml:digitalObject>
FOXML;
    file_put_contents($file_name, $foxml);
    $this->files[] = $file_name;

    $actual_pid = $this->apim->ingest(array('file' => $file_name));
    $this->assertEquals($expected_pid, $actual_pid);
  }
   *
   */


}