<?PHP

require_once 'FedoraApi.php';
require_once 'FedoraApiSerializer.php';

$connection = new RepositoryConnection('http://vm0:8080/fedora', 'fedoraAdmin', 'password');
//$connection = new RepositoryConnection('http://colorado:8080/fedora', 'fedoraAdmin', 'fedoraAdmin');
$serializer = new FedoraApiSerializer();

//$connection->debug = TRUE;
$a = new FedoraApiA($connection, $serializer);
$m = new FedoraApiM($connection, $serializer);

//print_r($a->getObjectProfile('islandora:strict_pdf'));
//print_r($a->listDatastreams('islandora:strict_pdf'));
//print_r($a->listMethods('islandora:strict_pdf'));
//print_r($a->findObjects('terms', 'islandora:*', 2));

try{
  //print_r($a->listMethods('codearl:9065', 'codearl:9055'));
  print_r($a->describeRepository());
  //print_r($m->addDatastream('islandora:strict_pdf', 'url', 'url', "http://www.albionresearch.com/images/albionwb75x75.png", array('controlGroup' => 'X', 'mimeType' => 'image/png')));
  //print_r($m->addDatastream('islandora:strict_pdf', 'test', 'string', "<woot><a><b><c></woot>", array('controlGroup' => 'M', 'mimeType' => 'text/xml')));
  //print_r($m->addDatastream('islandora:strict_pdf', 'file', 'file', '/home/jgreen/jon.jpg', array('controlGroup' => 'M', 'mimeType' => 'image/jpeg')));
  //print_r($a->listDatastreams("islandora:strict_pdf"));
  //print_r($m->addRelationship("islandora:strict_pdf", array('predicate' => 'http://woot/foo#bar', 'object' => 'thedude'), TRUE));
  //print_r($m->export('islandora:strict_pdf', array('context' => 'migrate')));
  //print_r($m->getDatastreamHistory('islandora:strict_pdf', 'test'));
  //print_r($m->getNextPid('test',2));
  //print_r($m->getObjectXml('islandora:strict_pdf'));
  //print_r($m->getRelationships('islandora:strict_pdf'));
  //print_r($m->getRelationships('islandora:strict_pdf'));
  //print_r($m->modifyDatastream('islandora:strict_pdf', 'test', array('dsState' => 'A')));
  //print_r($m->ingest(array('pid' => 'changeme:11','label' => 'woot')));
  //print_r($m->modifyObject('changeme:11', array('label' => 'test', 'state' => 'A')));
  //print_r($m->purgeDatastream('islandora:strict_pdf', 'test'));
  //print_r($m->purgeObject('changeme:11'));
}catch (RepositoryException $e) {
  print($e);
  if($e->getPrevious() instanceof HttpConnectionException) {
    print_r($e->getPrevious()->response);
  }
}