<?php

require_once('RepositoryException.php');
require_once('RepositoryConnection.php');
require_once('RepositoryConfig.php');

class FedoraApiA342{
  private $connection;
  private $serializer;
  
  public function __construct($connection, $serializer) {
    $this->connection = $connection;
    $this->serializer = $serializer;
  }
  
  public function describeRepository() {
    //this is weird and undocumented, but its what the web client does
    $request = "/describe?xml=true";
    $response = $this->connection->httpGetRequest($request);
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
    $response = $this->connection->httpGetRequest($request);
    $response = $this->serializer->findObjects($response['content']);
    return $response;
  }
  
  public function resumeFindObjects($sessionToken) {
    $sessionToken = urlencode($sessionToken);
    $request = "/objects?resultFormat=xml&sessionToken={$sessionToken}";
    
    $response = $this->connection->httpGetRequest($request);
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
    $response = $this->connection->httpGetRequest($request);
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
    
    $response = $this->connection->httpGetRequest($request);
    return $response['content'];
  }
  
  public function getObjectHistory($pid) {
    $format = 'xml';
    $pid = urlencode($pid);

    $request = "/objects/$pid/versions?format=$format";
    $response = $this->connection->httpGetRequest($request);
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
    
    $response = $this->connection->httpGetRequest($request);
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
    
    $response = $this->connection->httpGetRequest($request);
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
    
    $response = $this->connection->httpGetRequest($request);
    $response = $this->serializer->listMethods($response['content']);
    return $response;
  }
  
}

class FedoraApiM {
  public function __construct($connection, $serializer) {
    $this->connection = $connection;
    $this->serializer = $serializer;
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
    return $this->connection->httpPostRequest($request, $file, $type);
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
  
  private function flattenDocument(&$data, $xml) {
    $data = array();
    foreach($xml as $key => $value) {
      if(isset($data[$key]) && !is_array($data[$key])) {
        $tmp = $data[$key];
        $data[$key]= array();
        $data[$key][] = $tmp;
      }
      if($value->count() == 0) {
        if(isset($data[$key]) && is_array($data[$key])) {
          $data[$key][] = (string)$value;
        }
        else {
          $data[$key] = (string)$value;
        }
      }
      else {
        if(isset($data[$key]) && is_array($data[$key])) {
          $this->flattenDocument($data[$key][], $value);
        }
        else {
          $this->flattenDocument($data[$key], $value);
        }
      }
    }
  }
  
  public function getObjectHistory($xml) {
    $objectHistory = $this->loadXml($xml);
    $this->flattenDocument($data, $objectHistory->objectChangeDate);
    return $data['objectChangeDate'];
  }
  
  public function describeRepository($xml) {
    $repository = $this->loadXml($xml);
    $this->flattenDocument($data, $repository);
    return $data;
  }
  
  /* TODO: This is bullshit and doesn't work at all*/
  public function findObjects($xml) {
    $data = array();
    $result = $this->loadXml($xml);
    //if($result->listSession) {
    //  $this->flattenDocument($data['session'], $result->listSession);
    //  $data['session'] = $data['session']['listSession'];
    //}
    $this->flattenDocument($data, $result);
    return $data;
  }
  
  public function getObjectProfile($xml) {
    $data = array();
    $result = $this->loadXml($xml);
    $this->flattenDocument($data, $result);
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
}

$a = new FedoraApiA342(new RepositoryConnection(new RepositoryConfig('http://localhost:8080/fedora', 'fedoraAdmin', 'password')), new FedoraApiSerializer);
$m = new FedoraApiM(new RepositoryConnection(new RepositoryConfig('http://localhost:8080/fedora', 'fedoraAdmin', 'password')), new FedoraApiSerializer);

//print_r($test->getObjectProfile('islandora:strict_pdf'));
//print_r($test->listDatastreams('islandora:strict_pdf'));
//print_r($test->listMethods('islandora:strict_pdf'));

try{
  
  //print_r($m->addDatastream('islandora:strict_pdf', 'url', 'url', "http://www.albionresearch.com/images/albionwb75x75.png", array('controlGroup' => 'M', 'mimeType' => 'image/png')));
  ($m->addDatastream('islandora:strict_pdf', 'string', 'string', "<woot><a><b><c></woot>", array('controlGroup' => 'M', 'mimeType' => 'text/xml')));
  ($m->addDatastream('islandora:strict_pdf', 'file', 'file', '/home/jgreen/jon.jpg', array('controlGroup' => 'M', 'mimeType' => 'image/jpeg')));
  //print_r($a->listDatastreams("islandora:strict_pdf"));
}catch (RepositoryHttpErrorException $e) {
  print_r($e->response);
}