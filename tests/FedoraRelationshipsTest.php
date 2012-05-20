<?php
require_once "FedoraRelationships.php";

class FedoraRelationshipsTest extends PHPUnit_Framework_TestCase {

  function testRelationship() {
    $rel = new FedoraRelationship();
    $rel->addRelationship('one', 'two', 'three');
    print_r($rel->toString());

    print_r($rel->getRelationships());
  }

}