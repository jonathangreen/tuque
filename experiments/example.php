<?php

class FedoraApi {
  private $connection;
  private $serializer;
  
  public function __construct($connection, $serializer) {
    $this->connection = $connection;
    $this->serializer = $serializer;
  }
  
  public function changeLabel() {
    $request = "create/a/url";
    $response = $this->connection->makeRequest($request);
    $response = $this->serializer->changeLabel($response);
    return $response;
  }
}

class c {
  public function changeLabel($stuff) {
    return "$stuff serialized";
  }
}

class connection {
  public function makeRequest($input) {
    return 'response';
  }
}