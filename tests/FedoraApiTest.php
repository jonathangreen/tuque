<?php
/**
 * @file
 * A set of test classes that test the FedoraApi.php file
 */

require_once '../FedoraApi.php';
require_once '../FedoraApiSerializer.php';

/**
 * This class is injected into the FedoraApi to replace the default serializer.
 * Since it has access to the RAW XML coming back from Fedora we can test if the
 * response is what we expect.
 */
class FedoraXmlTest extends FedoraApiSerializer {

  /**
   * This tests the XML response from the describeRespository function. It
   * tests what tags come back, since the contents of the tag will be
   * dependant on where it is run.
   *
   * @param array $request
   *   HTTP request recieved from Fedora.
   *
   * @return string
   *   Response from parent
   */
  public function  describeRepository($request) {
    $this->test->assertEquals('200', $request['status']);
    
    $dom = new DOMDocument();
    $dom->loadXML($request['content']);
    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('x', 'http://www.fedora.info/definitions/1/0/access/');

    $actual = $xpath->query('/x:fedoraRepository/x:repositoryName');
    $this->test->assertEquals(1, $actual->length);
    $actual = $xpath->query('/x:fedoraRepository/x:repositoryBaseURL');
    $this->test->assertEquals(1, $actual->length);
    $actual = $xpath->query('/x:fedoraRepository/x:repositoryVersion');
    $this->test->assertEquals(1, $actual->length);
    $actual = $xpath->query('/x:fedoraRepository/x:repositoryPID/x:PID-namespaceIdentifier');
    $this->test->assertEquals(1, $actual->length);
    $actual = $xpath->query('/x:fedoraRepository/x:repositoryPID/x:PID-delimiter');
    $this->test->assertEquals(1, $actual->length);
    $actual = $xpath->query('/x:fedoraRepository/x:repositoryPID/x:PID-sample');
    $this->test->assertEquals(1, $actual->length);
    $actual = $xpath->query('/x:fedoraRepository/x:repositoryOAI-identifier/x:OAI-namespaceIdentifier');
    $this->test->assertEquals(1, $actual->length);
    $actual = $xpath->query('/x:fedoraRepository/x:repositoryOAI-identifier/x:OAI-delimiter');
    $this->test->assertEquals(1, $actual->length);
    $actual = $xpath->query('/x:fedoraRepository/x:repositoryOAI-identifier/x:OAI-sample');
    $this->test->assertEquals(1, $actual->length);
    $actual = $xpath->query('/x:fedoraRepository/x:sampleSearch-URL');
    $this->test->assertEquals(1, $actual->length);
    $actual = $xpath->query('/x:fedoraRepository/x:sampleAccess-URL');
    $this->test->assertEquals(1, $actual->length);
    $actual = $xpath->query('/x:fedoraRepository/x:sampleOAI-URL');
    $this->test->assertEquals(1, $actual->length);
    $actual = $xpath->query('/x:fedoraRepository/x:adminEmail');
    $this->test->assertGreaterThanOrEqual(1, $actual->length);

    return parent::describeRepository($request);
  }
}

/**
 * This class tests the response that we get back from Fedora. This is helpful
 * when new versions of Fedora come out to make sure we are still recieveing
 * the response that we are expecting.
 */
class FedoraApiResponseTest extends PHPUnit_Framework_TestCase {

  /**
   * Sets up an APIA and APIM object for testing.
   *
   * @todo Make this read from config file for username password and host.
   */
  protected function setUp() {
    $host = 'http://vm0:8080/fedora';
    $user = 'fedoraAdmin';
    $pass = 'password';

    $this->connection = new RepositoryConnection($host, $user, $pass);
    $this->serializer = new FedoraXmlTest();
    $this->serializer->test = $this;

    $this->apim = new FedoraApiM($this->connection, $this->serializer);
    $this->apia = new FedoraApiA($this->connection, $this->serializer);

    // Purge test objects from previous run.
    try {
      $this->apim->purgeObject('test:1');
    }
    catch (RepositoryException $e) {}
    try {
      $this->apim->purgeObject('test:2');
    }
    catch (RepositoryException $e) {}
    try {
      $this->apim->purgeObject('test:3');
    }
    catch (RepositoryException $e) {}

  }

  /**
   * Make sure describe repository returns the expected keys.
   */
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

  /**
   * Ingest some objects and make sure we get the correct response.
   */
  public function testIngest() {
    $ingest = $this->apim->ingest(array('pid' => 'test:1'));
    $this->assertEquals('test:1', $ingest);
    $ingest = $this->apim->ingest(array('pid' => 'test:2'));
    $this->assertEquals('test:2', $ingest);
    $ingest = $this->apim->ingest(array('pid' => 'test:3'));
    $this->assertEquals('test:3', $ingest);
  }

  /**
   * Find objects and make sure we get the expected response.
   *
   * @depends testIngest
   */
  public function testFindObjects() {
    $objects = $this->apia->findObjects('terms', 'test:*', 1);

  }
}