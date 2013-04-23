<?php

require_once 'implementations/fedora3/FedoraApiSerializer.php';
require_once 'implementations/fedora3/Object.php';
require_once 'implementations/fedora3/Repository.php';
require_once 'Cache.php';
require_once 'tests/TestHelpers.php';
require_once 'implementations/fedora3/RepositoryConnection.php';
require_once 'implementations/fedora3/FedoraApi.php';

class RepositoryQueryTest extends PHPUnit_Framework_TestCase {

  protected function setUp() {
    $connection = new RepositoryConnection(FEDORAURL, FEDORAUSER, FEDORAPASS);
    $this->api = new FedoraApi($connection);
    $cache = new SimpleCache();
    $this->repository = new FedoraRepository($this->api, $cache);
  }

  public function testItql() {
    $query = 'select $pid $label from <#ri>
where $pid <fedora-model:label> $label';
    $results = $this->repository->ri->itqlQuery($query);
    $this->assertTrue(TRUE);
  }
}