<?php
require_once "FedoraRelationships.php";

class FedoraRelationshipsTest extends PHPUnit_Framework_TestCase {

  function testRelationship() {
    $datastream = new NewFedoraDatastream('RELS-INT', 'M');


    $rel = new FedoraRelationships($datastream);

    $rel->registerNamespaceAlias('fuckyah', 'http://crazycool.com#');
    $rel->addRelationship('one', 'http://crazycool.com#', 'woot', 'test', TRUE);

    print_r($datastream->content);
    print_r($rel->getRelationships('one'));
  }

}