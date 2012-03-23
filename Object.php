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

class FedoraObject extends AbstractObject {
  protected $repository;
  protected $objectProfile;
  protected $objectId;

  public function  __construct($id, FedoraRepository $repository) {
    $this->repository = $repository;
    unset($this->id);
    unset($this->state);
    unset($this->createdDate);
    unset($this->lastModifiedDate);
    unset($this->label);
    unset($this->owner);

    $this->objectId = $id;
    $this->objectProfile = $this->repository->api->a->getObjectProfile($id);
    $this->objectProfile['objCreateDate'] = new FedoraDate($this->objectProfile['objCreateDate']);
    $this->objectProfile['objLastModDate'] = new FedoraDate($this->objectProfile['objLastModDate']);
  }

  public function delete() {
    $this->state = 'd';
  }

  public function getDatastream() {}
  public function newDatastream() {}

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
            $state = 'D';
            break;
          case 'a':
          case 'active':
            $state = 'A';
            break;
          case 'i':
          case 'inactive':
            $state = 'I';
            break;
          default:
          // @todo exception?
            $state = $this->objectProfile['objState'];
        }
        if ($this->objectProfile['objState'] != $state) {
          $this->repository->api->m->modifyObject($this->objectId, array('state' => $state));
          $this->objectProfile['objState'] = $state;
        }
        break;
      case 'unset':
        // @todo php warning? exception?
        break;
    }
  }

  protected function labelMagicProperty($function, $value) {
    $label = '';
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
        $label = $value;
      case 'unset':
        if ($this->objectProfile['objLabel'] != $label) {
          $this->repository->api->m->modifyObject($this->objectId, array('label' => $label));
          $this->objectProfile['objLabel'] = $label;
        }
        break;
    }
  }

  protected function ownerMagicProperty($function, $value) {
    $owner = '';
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
        $owner = $value;
      case 'unset':
        if ($this->objectProfile['objOwnerId'] != $owner) {
          $this->repository->api->m->modifyObject($this->objectId, array('ownerId' => $owner));
          $this->objectProfile['objOwnerId'] = $owner;
        }
        break;
    }
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