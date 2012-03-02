<?php

require_once('RepositoryException.php');
require_once('RepositoryConnection.php');

class FedoraApiA{
  private $connection;
  private $serializer;
  
  public function __construct($connection, $serializer) {
    $this->connection = $connection;
    $this->serializer = $serializer;
  }
  
  public function describeRepository() {
    //this is weird and undocumented, but its what the web client does
    $request = "/describe?xml=true";
    $response = $this->connection->getRequest($request);
    $response = $this->serializer->describeRepository($response['content']);
    return $response;
  }
  
  public function findObjects($type, $query, $maxResults = NULL, $displayFields = array('pid', 'title')) {
    $request = "/objects?";
    switch($type) {
      case 'terms':
        $request .= "terms=";
      break;
      case 'query':
        $request .= "query="; 
      break;
      default:
        throw new RepositoryBadArguementException('$type must be either: terms or query.');
    }
    $request .= urlencode($query);
    $request .= "&resultFormat=xml";
    
    if($maxResults) {
      $request .= "&maxResults=$maxResults";
    }
    
    if($displayFields) {
      foreach($displayFields as $display) {
        $edisplay = urlencode($display);
        $request .= "&{$edisplay}=true";
      }
    }
    $response = $this->connection->getRequest($request);
    $response = $this->serializer->findObjects($response['content']);
    return $response;
  }
  
  public function resumeFindObjects($sessionToken) {
    $sessionToken = urlencode($sessionToken);
    $request = "/objects?resultFormat=xml&sessionToken={$sessionToken}";
    
    $response = $this->connection->getRequest($request);
    $response = $this->serializer->findObjects($response['content']);
    return $response;
  }
  
  public function getDatastreamDissemination($pid, $dsid, $asOfDateTime = NULL, $download = NULL) {
    $pid = urlencode($pid);
    $dsid = urlencode($dsid);
    $seperator = '?';
    
    $request = "/objects/$pid/datastreams/$dsid/content";
    if(isset($asOfDateTime)) {
      $edate = urlencode($asOfDateTime);
      $request .= "{$seperator}asOfDateTime={$edate}";
      $seperator = '&';
    }
    if(isset($download)) {
      //convert download to string
      $downloadString = $download ? 'true' : 'false';
      $request .= "{$seperator}download={$downloadString}";
    }
    $response = $this->connection->getRequest($request);
    return $response['content'];
  }
  
  public function getDissemination($pid, $sdefPid, $method, $methodParameters = NULL) {
    $pid = urlencode($pid);
    $sdefPid = urldecode($sdefPid);
    $method = urlencode($method);
    
    $request = "/objects/{$pid}/methods/{$sdefPid}/{$method}";
    
    if(isset($methodParameters)) {
      $seperator = "?";
      foreach($methodParameters as $key => $value) {
        $ekey = urlencode($key);
        $evalue = urlencode($value);
        $request .= "{$seperator}{$ekey}={$evalue}";
        $seperator = "&";
      }
    }
    
    $response = $this->connection->getRequest($request);
    return $response['content'];
  }
  
  public function getObjectHistory($pid) {
    $format = 'xml';
    $pid = urlencode($pid);

    $request = "/objects/$pid/versions?format=$format";
    $response = $this->connection->getRequest($request);
    $response = $this->serializer->getObjectHistory($response['content']);
    return $response;
  }
  
  public function getObjectProfile($pid, $asOfDateTime = NULL) {
    $format = 'xml';
    $pid = urlencode($pid);
    
    $request = "/objects/{$pid}?format={$format}";
    if($asOfDateTime) {
      $edate = urlencode($asOfDateTime);
      $request .= "&asOfDateTime={$edate}";
    }
    
    $response = $this->connection->getRequest($request);
    $response = $this->serializer->getObjectProfile($response['content']);
    return $response;
  }
  
  public function listDatastreams($pid, $asOfDateTime = NULL) {
    $format = 'xml';
    $pid = urlencode($pid);
    
    $request = "/objects/{$pid}/datastreams?format={$format}";
    
    if($asOfDateTime) {
      $edate = urlencode($asOfDateTime);
      $request .= "&asOfDateTime={$edate}";
    }
    
    $response = $this->connection->getRequest($request);
    $response = $this->serializer->listDatastreams($response['content']);
    return $response;
  }
  
  public function listMethods($pid, $sdefPid = NULL, $asOfDateTime = NULL) {
    $format = 'xml';
    $pid = urlencode($pid);
    
    $request = "/objects/{$pid}/methods";
    
    if($sdefPid) {
      $sdefPid = urlencode($sdefPid);
      $request .= "/{$sdefPid}";
    }
    
    $request .= "?format={$format}";
    
    if($asOfDateTime) {
      $asOfDateTime = urlencode($asOfDateTime);
      $request .= "&asOfDateTime={$asOfDateTime}";
    }
    
    $response = $this->connection->getRequest($request);
    $response = $this->serializer->listMethods($response['content']);
    return $response;
  }
  
}

class FedoraApiM {
  public function __construct($connection, $serializer) {
    $this->connection = $connection;
    $this->serializer = $serializer;
  }
  
  private function addRequestParam(&$request, &$seperator, $params, $name) {
    if(isset($params[$name])) {
      $this->addParam($request, $seperator, $params[$name], $name);
    }
  }
  
  private function addParam(&$request, &$seperator, $param, $name) {
    $parameter = urlencode($param);
    $request .= "{$seperator}{$name}={$parameter}";
    $seperator = '&';
  }
  
  public function addDatastream($pid, $dsid, $type, $file, $params) {
    $pid = urlencode($pid);
    $dsid = urlencode($dsid);
    
    $request = "/objects/$pid/datastreams/$dsid?";
    
    foreach ($params as $param_name => $param_value) {
      $value = urlencode($param_value);
      $request .= $param_value != NULL ? "$param_name=$value&" : '';
    }
    
    switch(strtolower($type)) {
      case 'file':
      case 'string':
        break;
      case 'url':
        $file = urlencode($file);
        $request .= "dsLocation=$file";
        $type = 'string';
        break;
      default:
        throw new RepositoryBadArguementException("Type must be one of: file, string, url. ($type)");
        break;
    }
    return $this->connection->postRequest($request, $type, $file);
  }
  
  public function addRelationship($pid, $relationship, $isLiteral, $datatype = NULL) {
    $pid = urlencode($pid);
    
    if(!isset($relationship['predicate'])) {
      throw new RepositoryBadArguementException('Relationship array must contain a predicate element');
    }
    if(!isset($relationship['object'])) {
      throw new RepositoryBadArguementException('Relationship array must contain a object element');
    }
    
    $subject = isset($relationship['subject']) ? urlencode($relationship['subject']) : FALSE ;
    $predicate = urlencode($relationship['predicate']);
    $object = urlencode($relationship['object']);
    $isLiteral = $isLiteral ? 'true' : 'false';
    
    $request = "/objects/$pid/relationships/new?predicate=$predicate&object=$object&isLiteral=$isLiteral";
    
    if($subject) {
      $request .= "&subject=$subject";
    }
    
    if($datatype) {
      $request .= "&datatype=$datatype";
    }
    
    return $this->connection->postRequest($request, 'string', '');
  }
  
  public function export($pid, $params = array()) {
    $pid = urlencode($pid);
    
    $seperator = '?';

    $request = "/objects/$pid/export";
    
    if(isset($params['context'])) {
      $context = urlencode($params['context']);
      $request .= "{$seperator}{$context}";
      $seperator = '&';
    }
    
    if(isset($params['format'])) {
      $format = urlencode($params['format']);
      $request .= "{$seperator}{$format}";
      $seperator = '&';
    }
    
    if(isset($params['encoding'])) {
      $encoding = urlencode($params['encoding']);
      $request .= "{$seperator}{$encoding}";
      $seperator = '&';
    }
    
    return $this->connection->getRequest($request);
  }
  
  public function getDatastream($pid, $dsID, $asOfDateTime = NULL, $validateChecksum = FALSE) {
    $pid = urlencode($pid);
    $dsID = urlencode($dsID);
    $format = 'xml';
    
    $request = "/objects/$pid/datastreams/$dsID?format=$format";
    
    if($asOfDateTime) {
      $request .= "&asOfDateTime=$asOfDateTime";
    }
    
    if($validateChecksum) {
      $request .= "&validateChecksum=true";
    }
    
    $response = $this->connection->getRequest($request);
    $response = $this->serializer->getDatastream($response['content']);
    return $response;
  }
  
  public function getDatastreamHistory($pid, $dsid) {
    $pid = urlencode($pid);
    $dsid = urlencode($dsid);
    $format = 'xml';

    $request = "/objects/{$pid}/datastreams/{$dsid}/history?format={$format}";
    $response = $this->connection->getRequest($request);
    $response = $this->serializer->getDatastreamHistory($response['content']);
    return $response;
  }
  
  public function getNextPid($namespace = NULL, $numPIDS = NULL) {
    $format = 'xml';
    $request = "/objects/nextPID?format=$format";
    
    if($namespace) {
      $namespace = urlencode($namespace);
      $request .= "&namespace=$namespace";
    }
    
    if($numPIDS) {
      $request .= "&numPIDs=$numPIDS";
    }
    
    $response = $this->connection->postRequest($request, 'string', '');
    $response = $this->serializer->getNextPid($response['content']);
    return $response;
  }
  
  public function getObjectXml($pid) {
    $pid = urlencode($pid);

    $request = "/objects/{$pid}/objectXML";
    $response = $this->connection->getRequest($request);
    return $response['content'];
  }
  
  public function getRelationships($pid, $subject = NULL, $predicate = NULL){
    $pid = urlencode($pid);
    $format = 'xml';
    
    $request = "/objects/$pid/relationships?format=$format";
    
    if($subject) {
      $subject = urlencode($subject);
      $request .= "&subject=$subject";
    }
    
    if($predicate) {
      $predicate = urlencode($predicate);
      $predicate = "&predicate=$predicate";
    }
    $response = $this->connection->getRequest($request);
    return $response['content'];
  }
  
  public function ingest($params = array()) {
    $request = "/objects/";
    $seperator = '?';
    
    if(isset($params['pid'])) {
      $pid = urlencode($params['pid']);
      $request .= "$pid";
    }
    else {
      $request .= "new";
    }
    
    if(isset($params['objString'])) {
      $type = 'string';
      $data = $params['objString'];
      $this->addParam($request, $seperator, 'true', 'ignoreMime');
    }
    elseif(isset($params['objFile'])) {
      $type = 'file';
      $data = $params['objFile'];
      $this->addParam($request, $seperator, 'true', 'ignoreMime');
    }
    else {
      $type = 'none';
      $data = NULL;
    }
    
    $this->addRequestParam($request, $seperator, $params, 'label');
    $this->addRequestParam($request, $seperator, $params, 'format');
    $this->addRequestParam($request, $seperator, $params, 'encoding');
    $this->addRequestParam($request, $seperator, $params, 'namespace');
    $this->addRequestParam($request, $seperator, $params, 'ownerId');
    $this->addRequestParam($request, $seperator, $params, 'logMessage');
    
    $response = $this->connection->postRequest($request,$type,$data);
    //$response = $this->serializer->ingest($response['content']);
    return $response['content'];
  }
  
  public function modifyDatastream( $pid, $dsid, $params = array()) {
    $pid = urlencode($pid);
    $dsid = urlencode($dsid);
    
    $request = "/objects/{$pid}/datastreams/{$dsid}";
    $seperator = '?';
    
    // setup the file
    if(isset($params['dsFile'])) {
      $type = 'file';
      $data = $params['dsFile'];
    }
    elseif(isset($params['dsString'])) {
      $type = 'string';
      $data = $params['dsString'];
    }
    elseif(isset($params['dsLocation'])) {
      $type = 'string';
      $data = 'islandorarocks';
      $this->addRequestParam($request, $seperator, $params, 'dsLocation');
      $this->addParam($request, $seperator, 'true', 'ignoreContent');
    }
    else {
      $type = 'string';
      $data = 'islandorarocks';
      $this->addParam($request, $seperator, 'true', 'ignoreContent');
    }
   
    $this->addRequestParam($request, $seperator, $params, 'altIDs');
    $this->addRequestParam($request, $seperator, $params, 'dsLabel');
    $this->addRequestParam($request, $seperator, $params, 'versionable');
    $this->addRequestParam($request, $seperator, $params, 'dsState');
    $this->addRequestParam($request, $seperator, $params, 'formatURI');
    $this->addRequestParam($request, $seperator, $params, 'checksumType');
    $this->addRequestParam($request, $seperator, $params, 'mimeType');
    $this->addRequestParam($request, $seperator, $params, 'logMessage');
    $this->addRequestParam($request, $seperator, $params, 'lastModifiedDate');
    
    $response = $this->connection->putRequest($request, $type, $data);
    $response = $this->serializer->modifyDatastream($response['content']);
    return $response;
  }
  
  public function modifyObject($pid, $params = NULL) {
    $pid = urlencode($pid);
    $request = "/objects/$pid";
    $seperator = '?';
    
    // fake data
    $type = 'string';
    $data = 'islandorarocks';
    
    $this->addRequestParam($request, $seperator, $params, 'label');
    $this->addRequestParam($request, $seperator, $params, 'ownerId');
    $this->addRequestParam($request, $seperator, $params, 'state');
    $this->addRequestParam($request, $seperator, $params, 'logMessage');
    $this->addRequestParam($request, $seperator, $params, 'lastModifiedDate');
   
    $response = $this->connection->putRequest($request, $type, $data);
    return $response['content'];
  }
  
  public function purgeDatastream($pid, $dsid, $params = array()) {
    $pid = urlencode($pid);
    $dsid = urlencode($dsid);
    
    $request = "/objects/$pid/datastreams/$dsid";
    $seperator = '?';
    
    $this->addRequestParam($request, $seperator, $params, 'startDT');
    $this->addRequestParam($request, $seperator, $params, 'endDT');
    $this->addRequestParam($request, $seperator, $params, 'logMessage');
    
    $response = $this->connection->deleteRequest($request);
    return $response;
  }
  
}

class FedoraApiSerializer {
  
  private function loadXml($xml) {
    // use the shutup operator so that we don't get a warning as well
    // as throwing an exception
    $simplexml = @simplexml_load_string($xml);
    if($simplexml === FALSE) {
      $errors = libxml_get_errors();
      libxml_clear_errors();
      throw new RepositoryXmlError('Failed to parse XML response from Fedora.', 0, $errors);
    }
    return $simplexml;
  }
  
  private function flattenDocument($xml) {
    if($xml->count() == 0) {
      return (string)$xml;
    }
    
    $initialized = array();
    $return = array();
    
    foreach($xml->children() as $name => $child) {
      $value = $this->flattenDocument($child);
      
      if(isset($return[$name])) {
        if(isset($initialized[$name])) {
          $return[$name][] = $value;
        }
        else {
          $tmp = $return[$name];
          $return[$name] = array();
          $return[$name][] = $tmp;
          $return[$name][] = $value;
          $initialized[$name] = TRUE;
        }
      }
      else {
        $return[$name] = $value;
      }
    }
    
    return $return;
  }
  
  public function getObjectHistory($xml) {
    $objectHistory = $this->loadXml($xml);
    $data = $this->flattenDocument($objectHistory->objectChangeDate);
    return $data['objectChangeDate'];
  }
  
  public function describeRepository($xml) {
    $repository = $this->loadXml($xml);
    $data = $this->flattenDocument($repository);
    return $data;
  }
  
  public function findObjects($xml) {
    $result = $this->loadXml($xml);
    $data = $this->flattenDocument($result);
    return $data;
  }
  
  public function getObjectProfile($xml) {
    $result = $this->loadXml($xml);
    $data = $this->flattenDocument($result);
    $data['objModels'] = $data['objModels']['model'];
    return $data;
  }
  
  public function listDatastreams($xml) {
    $data = array();
    $datastreams = $this->loadXml($xml);
    
    foreach($datastreams->datastream as $datastream) {
      $data[(string)$datastream['dsid']] = array(
        'label' => (string)$datastream['label'], 
        'mimetype' => (string)$datastream['mimeType']
      );
    }
    
    return $data;
  }
  
  /* figure out how to parse this */
  public function listMethods($xml) {
    return $xml;
  }
  
  public function getDatastream($xml) {
    $result = $this->loadXml($xml);
    $data = $this->flattenDocument($result);
    return $data;
  }
  
  public function getDatastreamHistory($xml) {
    $result = $this->loadXml($xml);
    return $this->flattenDocument($result);
  }
  
  public function getNextPid($xml) {
    $result = $this->loadXml($xml);
    return $this->flattenDocument($result);
  }
  
  public function getRelationships($xml){
    $result = $this->loadXml($xml);
    return $this->flattenDocument($result);
  }
  
  public function modifyDatastream($xml) {
    $result = $this->loadXml($xml);
    return $this->flattenDocument($result);
  }
}

$connection = new RepositoryConnection('http://localhost:8080/fedora', 'fedoraAdmin', 'password');
$serializer = new FedoraApiSerializer();
$connection->debug = TRUE;
$a = new FedoraApiA($connection, $serializer);
$m = new FedoraApiM($connection, $serializer);

//print_r($a->getObjectProfile('islandora:strict_pdf'));
//print_r($a->listDatastreams('islandora:strict_pdf'));
//print_r($a->listMethods('islandora:strict_pdf'));
//print_r($a->findObjects('terms', 'islandora:*', 2));

try{
  //print_r($m->addDatastream('islandora:strict_pdf', 'url', 'url', "http://www.albionresearch.com/images/albionwb75x75.png", array('controlGroup' => 'X', 'mimeType' => 'image/png')));
  //print_r($m->addDatastream('islandora:strict_pdf', 'string', 'string', "<woot><a><b><c></woot>", array('controlGroup' => 'M', 'mimeType' => 'text/xml')));
  //print_r($m->addDatastream('islandora:strict_pdf', 'file', 'file', '/home/jgreen/jon.jpg', array('controlGroup' => 'M', 'mimeType' => 'image/jpeg')));
  //print_r($a->listDatastreams("islandora:strict_pdf"));
  //print_r($m->addRelationship("islandora:strict_pdf", array('subject' => 'info:fedora/islandora:demos/RELS-EXT', 'predicate' => 'http://woot/rels#jesusstarfish', 'object' => 'god'), TRUE));
  //print_r($m->export('islandora:strict_pdf', array('context' => 'migrate')));
  //print_r($m->getDatastreamHistory('islandora:strict_pdf', 'RELS-EXT'));
  //print_r($m->getNextPid('jesus:',5));
  //print_r($m->getObjectXml('islandora:strict_pdf'));
  //print_r($m->getRelationships('islandora:strict_pdf'));
  //print_r($m->getRelationships('islandora:strict_pdf'));
  //print_r($m->modifyDatastream('islandora:strict_pdf', 'file', array('dsState' => 'A')));
  //print_r($m->ingest(array('label' => 'woot')));
  //print_r($m->modifyObject('changeme:12', array('label' => 'test', 'state' => 'I')));
  print_r($m->purgeDatastream('islandora:strict_pdf', 'file'));
}catch (RepositoryException $e) {
  if($e->getPrevious() instanceof HttpConnectionException) {
    print_r($e->getPrevious()->response);
  }
}