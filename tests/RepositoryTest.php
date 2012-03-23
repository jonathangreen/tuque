<?php

include_once 'Repository.php';

$repository = new FedoraRepository();

$object = $repository->getNewObject($pid);
$object->addDatastream();
$object->label = 'foo';
$repository->ingestNewObject($object);