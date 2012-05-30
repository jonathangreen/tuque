<?php

class UploadTest extends PHPUnit_Framework_TestCase {

  protected function setUp() {
    $connection = new RepositoryConnection(FEDORAURL, FEDORAUSER, FEDORAPASS);
    $this->api = new FedoraApi($connection);
  }

  public function testUploadString() {
    $this->markTestIncomplete();
    $filepath = getcwd() . '/tests/test_data/test.png';
    $return = $this->api->m->upload('string', 'string string string');
    print_r($return);
  }
}