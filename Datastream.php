<?php

require_once 'MagicProperty.php';
require_once 'FedoraDate.php';

abstract class AbstractDatastream extends MagicProperty {
  
  /* functions */
  abstract public function delete();
  abstract public function setContentFromFile($file);
  abstract public function setContentFromUrl($url);
  abstract public function setContentFromString($string);

  public $id;
  public $label;
  public $controlGroup;
  public $versionable;
  public $state;
  public $mimetype;
  public $format;
  public $size;
  public $checksum;
  public $checksumType;
  public $createdDate;
  public $content;
  public $url;
}

/**
 * @todo versioning
 * @todo altids
 * @todo opportunistic locking
 */
class FedoraDatastream extends AbstractDatastream {
  protected $repository;
  protected $object;
  protected $datastream = NULL;

  public function __construct($id, FedoraObject $object, FedoraRepository $repository) {
    unset($this->id);
    unset($this->label);
    unset($this->controlGroup);
    unset($this->versionable);
    unset($this->state);
    unset($this->mimetype);
    unset($this->format);
    unset($this->size);
    unset($this->checksum);
    unset($this->checksumType);
    unset($this->createdDate);
    unset($this->content);
    unset($this->url);

    $this->object = $object;
    $this->repository = $repository;
    $this->datastreamId = $id;
  }

  public function delete() {
    $this->state = 'd';
  }

  protected function populateDatastream() {
    if(!$this->datastream) {
      $this->datastream = $this->repository->api->m->getDatastream($this->object->id, $this->id);
    }
  }

  protected function idMagicProperty($function, $value) {
    switch($function) {
      case 'get':
        return $this->datastreamId;
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

  protected function controlGroupMagicProperty($function, $value) {
    $this->populateDatastream();
    switch($function) {
      case 'get':
        return $this->datastream['dsControlGroup'];
        break;
      case 'isset':
        return TRUE;
        break;
      case 'set':
      case 'unset':
        // @todo php warning? exception?
        break;
    }
  }

  protected function stateMagicProperty($function, $value) {
    switch($function) {
      case 'get':
        $this->populateDatastream();
        return $this->datastream['dsState'];
        break;
      case 'isset':
        return TRUE;
        break;
      case 'set':
        switch(strtolower($value)) {
          case 'd':
          case 'deleted':
            $this->datastream['dsState'] = 'D';
            break;
          case 'a':
          case 'active':
            $this->datastream['dsState'] = 'A';
            break;
          case 'i':
          case 'inactive':
            $this->datastream['dsState'] = 'I';
            break;
          default:
          // @todo exception?
            return;
            break;
        }
        $this->datastream = $this->repository->api->m->modifyDatastream($this->object->id, $this->id, array('dsState' => $this->datastream['dsState']));
        break;
      case 'unset':
        // @todo php warning? exception?
        break;
    }
  }

  protected function labelMagicProperty($function, $value) {
    switch($function) {
      case 'get':
        $this->populateDatastream();
        return $this->datastream['dsLabel'];
        break;
      case 'isset':
        $this->populateDatastream();
        if($this->datastream['dsLabel'] == '') {
          return FALSE;
        }
        else {
          return isset($this->datastream['dsLabel']);
        }
        break;
      case 'set':
        $this->datastream = $this->repository->api->m->modifyDatastream($this->object->id, $this->id, array('dsLabel' => $value));
        break;
      case 'unset':
        $this->datastream = $this->repository->api->m->modifyDatastream($this->object->id, $this->id, array('dsLabel' => ''));
        break;
    }
  }

  protected function versionableMagicProperty($function, $value) {
    switch($function) {
      case 'get':
        $this->populateDatastream();
        // Convert to a boolean.
        $versionable = $this->datastream['dsVersionable'] == 'true' ? TRUE : FALSE;
        return $versionable;
        break;
      case 'isset':
        return TRUE;
        break;
      case 'set':
        if(!is_bool($value)) {
          // @todo error handling
          return;
        }
        $this->datastream = $this->repository->api->m->modifyDatastream($this->object->id, $this->id, array('versionable' => $value));
        break;
      case 'unset':
        // @todo php warning? exception?
        break;
    }
  }

  protected function mimetypeMagicProperty($function, $value) {
    switch($function) {
      case 'get':
        $this->populateDatastream();
        return $this->datastream['dsMIME'];
        break;
      case 'isset':
        return TRUE;
        break;
      case 'set':
        // @todo handle parsing errors
        $this->datastream = $this->repository->api->m->modifyDatastream($this->object->id, $this->id, array('mimeType' => $value));
        break;
      case 'unset':
        // @todo php warning? exception?
        break;
    }
  }

  protected function formatMagicProperty($function, $value) {
    switch($function) {
      case 'get':
        $this->populateDatastream();
        return $this->datastream['dsFormatURI'];
        break;
      case 'isset':
        $this->populateDatastream();
        if($this->datastream['dsFormatURI'] == '') {
          return FALSE;
        }
        else {
          return isset($this->datastream['dsFormatURI']);
        }
        break;
      case 'set':
        $this->datastream = $this->repository->api->m->modifyDatastream($this->object->id, $this->id, array('formatURI' => $value));
        break;
      case 'unset':
        $this->datastream = $this->repository->api->m->modifyDatastream($this->object->id, $this->id, array('formatURI' => ''));
        break;
    }
  }

  protected function sizeMagicProperty($function, $value) {
    switch($function) {
      case 'get':
        $this->populateDatastream();
        return $this->datastream['dsSize'];
        break;
      case 'isset':
        return TRUE;
        break;
      case 'set':
      case 'unset':
        // @todo decide on a strategy here
        break;
    }
  }

  protected function checksumMagicProperty($function, $value) {
    switch($function) {
      case 'get':
        $this->populateDatastream();
        return $this->datastream['dsChecksum'];
        break;
      case 'isset':
        $this->populateDatastream();
        if($this->datastream['dsChecksum'] == '') {
          return FALSE;
        }
        else {
          return isset($this->datastream['dsChecksum']);
        }
        break;
      case 'set':
        $this->datastream = $this->repository->api->m->modifyDatastream($this->object->id, $this->id, array('checksum' => $value));
        break;
      case 'unset':
        $this->datastream = $this->repository->api->m->modifyDatastream($this->object->id, $this->id, array('checksum' => ''));
        break;
    }
  }

  protected function checksumTypeMagicProperty($function, $value) {
    switch($function) {
      case 'get':
        $this->populateDatastream();
        return $this->datastream['dsChecksumType'];
        break;
      case 'isset':
        $this->populateDatastream();
        if($this->datastream['dsChecksum'] == 'DISABLED') {
          return FALSE;
        }
        else {
          return TRUE;
        }
        break;
      case 'set':
        switch ($value) {
          case 'DEFAULT':
          case 'DISABLED':
          case 'MD5':
          case 'SHA-1':
          case 'SHA-256':
          case 'SHA-384':
          case 'SHA-512':
            break;
          default:
            // @todo throw exception or something
            return;
        }
        $this->datastream = $this->repository->api->m->modifyDatastream($this->object->id, $this->id, array('checksumType' => $value));
        break;
      case 'unset':
        $this->datastream = $this->repository->api->m->modifyDatastream($this->object->id, $this->id, array('checksumType' => 'DISABLED'));
        break;
    }
  }

  protected function createdDateMagicProperty($function, $value) {
    switch($function) {
      case 'get':
        $this->populateDatastream();
        return new FedoraDate($this->datastream['dsCreateDate']);
        break;
      case 'isset':
        return TRUE;
      case 'set':
      case 'unset':
        // @todo fix
        break;
    }
  }

  public function setContentFromFile($file) {}
  public function setContentFromUrl($url) {}
  public function setContentFromString($string) {}
}