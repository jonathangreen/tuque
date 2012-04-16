<?php

require_once 'Datastream.php';
require_once 'FedoraApi.php';
require_once 'FedoraApiSerializer.php';
require_once 'Object.php';
require_once 'Repository.php';
require_once 'Cache.php';
require_once 'tests/TestHelpers.php';

$connection = new RepositoryConnection(FEDORAURL, FEDORAUSER, FEDORAPASS);
$connection->debug = FALSE;
$connection->reuseConnection = TRUE;
$api = new FedoraApi($connection);
$cache = new SimpleCache();
$repository = new FedoraRepository($api, $cache);
$pid = "lol:rofl";
$dsid = "lolerskates";
$api->m->ingest(array('pid' => $pid));
$api->m->addDatastream($pid, $dsid, 'string', '<test><xml/></test>', array('controlGroup' => 'X'));
$object = new FedoraObject($pid, $repository);
$ds = new FedoraDatastream($dsid, $object, $repository);
$ds->content = '<jesus/>';
print_r($api->a->getDatastreamDissemination($pid, $dsid));
$api->m->purgeObject($pid);
//test test test
