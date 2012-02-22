<?php

include('RepositoryConnection.php');
include('RepositoryConfig.php');

class FedoraApiA342{
  private $connection;
  private $serializer;
  
  public function __construct($connection, $serializer) {
    $this->connection = $connection;
    $this->serializer = $serializer;
  }
  
  public function getObjectHistory($pid) {
    $format = 'xml';
    $pid = urlencode($pid);

    $request = "/objects/$pid/versions?format=$format";
    $response = $this->connection->httpGetRequest($request);
    print_r($response);
    return $response;
  }
}

$test = new FedoraApiA342(new RepositoryConnection(new RepositoryConfig()), NULL);
$test->getObjectHistory('islandora:strict_pdf');