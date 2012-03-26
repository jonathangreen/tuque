<?php

require_once 'MagicProperty.php';
require_once 'FedoraDate.php';

abstract class AbstractObject extends MagicProperty {
  public $label;
  public $owner;
  public $state;
  public $id;
  public $createdDate;
  public $lastModifiedDate;

  abstract public function delete();
  abstract public function getDatastream();
  abstract public function newDatastream();
}

abstract class AbstractFedoraObject extends AbstractObject {
  protected $repository;
  protected $objectId;
  protected $objectProfile;

  public function  __construct($id, FedoraRepository $repository) {
    $this->repository = $repository;
    $this->objectId = $id;
    unset($this->id);
    unset($this->state);
    unset($this->createdDate);
    unset($this->lastModifiedDate);
    unset($this->label);
    unset($this->owner);
  }

  protected function idMagicProperty($function, $value) {
    switch($function) {
      case 'get':
        return $this->objectId;
        break;
      case 'isset':
        return TRUE;
        break;
      case 'set':
      case 'unset':
        // @todo fix this shiznat
        throw new Exception();
        break;
    }
  }

  protected function stateMagicProperty($function, $value) {
    switch($function) {
      case 'get':
        return $this->objectProfile['objState'];
        break;
      case 'isset':
        return TRUE;
        break;
      case 'set':
        switch(strtolower($value)) {
          case 'd':
          case 'deleted':
            $this->objectProfile['objState'] = 'D';
            break;
          case 'a':
          case 'active':
            $this->objectProfile['objState'] = 'A';
            break;
          case 'i':
          case 'inactive':
            $this->objectProfile['objState'] = 'I';
            break;
          default:
          // @todo exception?
            break;
        }
        break;
      case 'unset':
        // @todo php warning? exception?
        break;
    }
  }

  protected function labelMagicProperty($function, $value) {
    switch($function) {
      case 'get':
        return $this->objectProfile['objLabel'];
        break;
      case 'isset':
        if($this->objectProfile['objLabel'] === '') {
          return FALSE;
        }
        else {
          return isset($this->objectProfile['objLabel']);
        }
        break;
      case 'set':
        $this->objectProfile['objLabel'] = $value;
        break;
      case 'unset':
        $this->objectProfile['objLabel'] = '';
        break;
    }
  }

  protected function ownerMagicProperty($function, $value) {
    switch($function) {
      case 'get':
        return $this->objectProfile['objOwnerId'];
        break;
      case 'isset':
        if($this->objectProfile['objOwnerId'] === '') {
          return FALSE;
        }
        else {
          return isset($this->objectProfile['objOwnerId']);
        }
        break;
      case 'set':
        $this->objectProfile['objOwnerId'] = $value;
        break;
      case 'unset':
        $this->objectProfile['objOwnerId'] = '';
        break;
    }
  }
}

class NewFedoraObject extends AbstractFedoraObject {

  public function  __construct($id, FedoraRepository $repository) {
    parent::__construct($id, $repository);
    $this->objectProfile = array();
    $this->objectProfile['objState'] = 'A';
    $this->objectProfile['objOwnerId'] = '';
    $this->objectProfile['objLabel'] = '';
  }

  public function delete() {
    $this->state = 'D';
  }

  public function getDatastream() {}
  public function newDatastream() {}
}

class FedoraObject extends AbstractFedoraObject {

  public function  __construct($id, FedoraRepository $repository) {
    parent::__construct($id, $repository);
    
    $this->objectProfile = $this->repository->api->a->getObjectProfile($id);
    $this->objectProfile['objCreateDate'] = new FedoraDate($this->objectProfile['objCreateDate']);
    $this->objectProfile['objLastModDate'] = new FedoraDate($this->objectProfile['objLastModDate']);
  }

  public function delete() {
    $this->state = 'd';
  }

  public function getDatastream() {}
  public function newDatastream() {}

  protected function stateMagicProperty($function, $value) {
    $state = $this->objectProfile['objState'];
    $return = parent::stateMagicProperty($function, $value);

    if ($function == 'set' && $state != $this->objectProfile['objState']) {
      $this->repository->api->m->modifyObject($this->objectId, array('state' => $state));
    }
    return $return;
  }

  protected function labelMagicProperty($function, $value) {
    $label = $this->objectProfile['objLabel'];
    $return = parent::labelMagicProperty($function, $value);

    if ($function == 'set' && $label != $this->objectProfile['objLabel']) {
        $this->repository->api->m->modifyObject($this->objectId, array('label' => $label));
    }
    return $return;
  }

  protected function ownerMagicProperty($function, $value) {
    $owner = $this->objectProfile['objOwnerId'];
    $return = parent::ownerMagicProperty($function, $value);

    if ($function == 'set' && $owner != $this->objectProfile['objOwnerId']) {
        $this->repository->api->m->modifyObject($this->objectId, array('ownerId' => $owner));
    }
    return $return;
  }

  protected function createdDateMagicProperty($function, $value) {
    switch($function) {
      case 'get':
        return $this->objectProfile['objCreateDate'];
        break;
      case 'isset':
        return TRUE;
        break;
      case 'set':
      case 'unset':
        throw new Exception();
        break;
    }
  }

  protected function lastModifiedDateMagicProperty($function, $value) {
    switch($function) {
      case 'get':
        return $this->objectProfile['objLastModDate'];
        break;
      case 'isset':
        return TRUE;
        break;
      case 'set':
        if (!($value instanceof FedoraDate)) {
          $value = new FedoraDate($value);
        }
        $this->api->m->modifyObject($this->objectId, array('lastModifiedDate' => (string)$value));
        $this->objectProfile['objLastModDate'] = $value;
        break;
      case 'unset':
        throw new InvalidArgumentException();
        break;
    }
  }
}