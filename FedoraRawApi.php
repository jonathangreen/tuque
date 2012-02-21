<?php

abstract class AbstractFedoraApi {
  public $FedoraApiA;
  public $FedoraApiM;
  public function __construct($connection = NULL, $serializer = NULL); 
}

interface FedoraApiAInterface {
  public function __construct($connection = NULL, $serializer = NULL);
  public function describeRepository();
  public function findObjects($type = '', $query = '', $maxResults = '', $displayFields = array('pid', 'title'));
  public function resumeFindObjects($sessionToken, $options = array());
  public function getDatastreamDissemination($pid, $dsID, $asOfDateTime = NULL, $download = NULL);
  public function getDissemination($pid, $sdefPid, $method, $methodParameters = array());
  public function getObjectHistory($pid);
  public function getObjectProfile($pid, $asOfDateTime = '');
  public function listDatastreams($pid, $asOfDateTime = '');
  public function listMethods($pid, $sdefPid = '', $asOfDateTime = '');
}

interface FedoraApiMInterface {
  public function __construct($connection = NULL, $serializer = NULL);
  public function addDatastream($pid, $id, $type, $file, $params);
  public function addRelationship($pid, $isLiteral, $datatype);
  public function export($pid, $format = 'info:fedora/fedora-system:FOXML-1.1', $context = 'public', $encoding = 'UTF-8');
  public function getDatastream($pid, $dsID, $format = 'xml', $asOfDateTime = NULL, $validateChecksum = FALSE);
  public function getDatastreamHistory($pid, $dsid);
  public function getNextPID($namespace = NULL, $numPIDS = 1);
  public function getObjectXML($pid);
  public function getRelationships($pid, $subject, $predicate);
  public function ingest($type = NULL, $file = NULL, $pid = NULL, $params = array());
  public function modifyDatastream( $pid, $dsID, $type = NULL, $file = NULL, $params = array());
  public function modifyObject($pid, $label = NULL, $ownerId = NULL, $state = NULL, $logMessage = NULL);
  public function purgeDatastream($pid, $dsID, $startDT = '', $endDT = '', $logMessage = '', $force = 'false');
  public function purgeObject($pid, $logMessage = '', $force = 'false');
}