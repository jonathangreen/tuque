<?php

require_once 'implementations/fedora3/FedoraApiSerializer.php';
require_once 'implementations/fedora3/Object.php';
require_once 'implementations/fedora3/Repository.php';
require_once 'implementations/fedora3/RepositoryConnection.php';
require_once 'implementations/fedora3/FedoraApi.php';
require_once 'tests/implementations/RepositoryTestBase.php';

class Fedora3RepositoryTest extends RepositoryTestBase {

  protected function setUp() {
    $connection = new RepositoryConnection(FEDORAURL, FEDORAUSER, FEDORAPASS);
    $this->api = new FedoraApi($connection);
    $cache = new SimpleCache();
    $this->repository = new FedoraRepository($this->api, $cache);
  }
}