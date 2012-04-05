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

abstract class AbstractFedoraDatastream extends AbstractDatastream {
  protected $datastreamId = NULL;
  protected $repository;
  protected $object;

  public function  __construct($id, FedoraObject $object, FedoraRepository $repository) {
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
    $this->datastreamId = $id;
    $this->repository = $repository;
    $this->object = $object;
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
        trigger_error("Cannot $function the readonly datastream->id property.", E_USER_WARNING);
        break;
    }
  }

  public function delete() {
    $this->state = 'd';
  }

  protected function isDatastreamProperySet($actual, $unsetVal) {
    if($actual === $unsetVal) {
      return FALSE;
    }
    else {
      return TRUE;
    }
  }

  protected function getDatastreamContent($version = NULL) {
    return $this->repository->api->a->getDatastreamDissemination($this->object->id, $this->id, $version);
  }

  protected function getDatastreamHistory() {
    return $this->repository->api->m->getDatastreamHistory($this->object->id, $this->id);
  }

  protected function modifyDatastream($args) {
    return $this->repository->api->m->modifyDatastream($this->object->id, $this->id, $args);
  }

  protected function purgeDatastream($version) {
    return $this->repository->api->m->purgeDatastream($this->object->id, $this->id, array('startDT' => $version, 'endDT' => $version));
  }

  protected function validateState($state) {
    switch(strtolower($state)) {
      case 'd':
      case 'deleted':
        return 'D';
        break;
      case 'a':
      case 'active':
        return 'A';
        break;
      case 'i':
      case 'inactive':
        return 'I';
        break;
      default:
        return FALSE;
        break;
    }
  }

  protected function validateVersionable($versionable) {
    return is_bool($versionable);
  }

  protected function validateChecksumType($type) {
    switch ($type) {
      case 'DEFAULT':
      case 'DISABLED':
      case 'MD5':
      case 'SHA-1':
      case 'SHA-256':
      case 'SHA-384':
      case 'SHA-512':
        return $type;
        break;
      default:
        return FALSE;
        break;
    }
  }
}

class FedoraDatastreamVersion extends AbstractFedoraDatastream {
  protected $datastreamInfo = NULL;
  protected $repository;
  public $parent;

  public function  __construct($id, array $datastreamInfo, FedoraDatastream $datastream, FedoraObject $object, FedoraRepository $repository) {
    parent::__construct($id, $object, $repository);
    $this->datastreamInfo = $datastreamInfo;
    $this->parent = $datastream;
  }

  protected function error() {
    trigger_error("All properties of previous datastream versions are read only. Please modify parent datastream object.", E_USER_WARNING);
  }

  protected function generalReadOnly($offset, $unsetVal, $function, $value) {
    switch($function) {
      case 'get':
        return $this->datastreamInfo[$offset];
        break;
      case 'isset':
        if($unsetVal === NULL) {
          // object cannot be unset
          return TRUE;
        }
        else {
          return $this->isDatastreamProperySet($this->datastreamInfo[$offset], $unsetVal);
        }
        break;
      case 'set':
      case 'unset':
        $this->error();
        break;
    }
  }

  protected function controlGroupMagicProperty($function, $value) {
    return $this->generalReadOnly('dsControlGroup', NULL, $function, $value);
  }

  protected function stateMagicProperty($function, $value) {
    return $this->generalReadOnly('dsState', NULL, $function, $value);
  }

  protected function labelMagicProperty($function, $value) {
    return $this->generalReadOnly('dsLabel', '', $function, $value);
  }

  protected function versionableMagicProperty($function, $value) {
    if(!is_bool($this->datastreamInfo['dsVersionable'])) {
      $this->datastreamInfo['dsVersionable'] = $this->datastreamInfo['dsVersionable'] == 'true' ? TRUE : FALSE;
    }
    return $this->generalReadOnly('dsVersionable', NULL, $function, $value);
  }

  protected function mimetypeMagicProperty($function, $value) {
    return $this->generalReadOnly('dsMIME', '', $function, $value);
  }

  protected function formatMagicProperty($function, $value) {
    return $this->generalReadOnly('dsFormatURI', '', $function, $value);
  }

  protected function sizeMagicProperty($function, $value) {
    return $this->generalReadOnly('dsSize', NULL, $function, $value);
  }

  protected function checksumMagicProperty() {
    return $this->generalReadOnly('dsChecksum', 'none', $function, $value);
  }

  protected function createdDateMagicProperty($function, $value) {
    if (!$this->datastreamInfo['dsCreateDate'] instanceof FedoraDate) {
      $this->datastreamInfo['dsCreateDate'] = new FedoraDate($this->datastreamInfo['dsCreateDate']);
    }
    return $this->generalReadOnly('dsCreateDate', NULL, $function, $value);
  }

  protected function contentMagicProperty($function, $value) {
    switch($function) {
      case 'get':
        return $this->getDatastreamContent((string)$this->createdDate);
        break;
      case 'isset':
        return $this->isDatastreamProperySet($this->content, '');
        break;
      case 'set':
      case 'unset':
        $this->error();
        break;
    }
  }

  public function setContentFromFile($file) {
    $this->error();
  }

  public function setContentFromString($string) {
    $this->error();
  }

  public function setContentFromUrl($url) {
    $this->error();
  }
}

class FedoraDatastream extends AbstractFedoraDatastream implements Countable, ArrayAccess, IteratorAggregate{
  protected $datastreamInfo = NULL;
  protected $datastreamHistory = NULL;
  public $forceUpdate = FALSE;

  public function __construct($id, FedoraObject $object, FedoraRepository $repository, array $datastreamInfo = NULL) {
    parent::__construct($id, $object, $repository);
    $this->datastreamInfo = $datastreamInfo;
  }

  /*
   * @todo finish this
   */
  public static function createAndConstruct($id, $params, FedoraObject $object, FedoraRepository $repository) {
    
  }
  
  public function refresh() {
    $this->datastreamInfo = NULL;
    $this->datastreamHistory = NULL;
  }

  protected function populateDatastreamHistory() {
    if($this->datastreamHistory === NULL) {
      $this->datastreamHistory = $this->getDatastreamHistory();
    }
  }

  protected function populateDatastreamInfo() {
    if($this->datastreamInfo === NULL) {
      $this->datastreamHistory = $this->getDatastreamHistory();
      $this->datastreamInfo = $this->datastreamHistory[0];
    }
  }

  protected function modifyDatastream(array $args) {
    $versionable = $this->versionable;
    if(!$this->forceUpdate) {
      $args = array_merge($args, array('lastModifiedDate' => (string)$this->createdDate));
    }
    $this->datastreamInfo = parent::modifyDatastream($args);
    if($this->datastreamHistory !== NULL) {
      if($versionable) {
        array_unshift($this->datastreamHistory, $this->datastreamInfo);
      }
      else {
        $this->datastreamHistory[0] = $this->datastreamInfo;
      }
    }
  }

  protected function getDatastreamContent($version = NULL) {
    $this->populateDatastreamInfo();
    return parent::getDatastreamContent($version);
  }

  protected function controlGroupMagicProperty($function, $value) {
    $this->populateDatastreamInfo();
    switch($function) {
      case 'get':
        return $this->datastreamInfo['dsControlGroup'];
        break;
      case 'isset':
        return TRUE;
        break;
      case 'set':
      case 'unset':
        trigger_error("Cannot $function the readonly datastream->controlGroup property.", E_USER_WARNING);
        break;
    }
  }

  protected function stateMagicProperty($function, $value) {
    switch($function) {
      case 'get':
        $this->populateDatastreamInfo();
        return $this->datastreamInfo['dsState'];
        break;
      case 'isset':
        return TRUE;
        break;
      case 'set':
        $state = $this->validateState($value);
        if($state) {
          $this->modifyDatastream(array('dsState' => $state));
        }
        else {
          trigger_error("$value is not a valid value for the datastream->state property.", E_USER_WARNING);
        }
        break;
      case 'unset':
        trigger_error("Cannot unset the required datastream->state property.", E_USER_WARNING);
        break;
    }
  }

  protected function labelMagicProperty($function, $value) {
    switch($function) {
      case 'get':
        $this->populateDatastreamInfo();
        return $this->datastreamInfo['dsLabel'];
        break;
      case 'isset':
        $this->populateDatastreamInfo();
        return $this->isDatastreamProperySet($this->datastreamInfo['dsLabel'], '');
        break;
      case 'set':
        $this->modifyDatastream(array('dsLabel' => $value));
        break;
      case 'unset':
        $this->modifyDatastream(array('dsLabel' => ''));
        break;
    }
  }

  protected function versionableMagicProperty($function, $value) {
    switch($function) {
      case 'get':
        $this->populateDatastreamInfo();
        // Convert to a boolean.
        $versionable = $this->datastreamInfo['dsVersionable'] == 'true' ? TRUE : FALSE;
        return $versionable;
        break;
      case 'isset':
        return TRUE;
        break;
      case 'set':
        if($this->validateVersionable($value)) {
          $this->modifyDatastream(array('versionable' => $value));
        }
        else {
          trigger_error("Datastream->versionable must be a boolean.", E_USER_WARNING);
        }
        break;
      case 'unset':
        trigger_error("Cannot unset the required datastream->versionable property.", E_USER_WARNING);
        break;
    }
  }

  /**
   * @todo add some checking aorund mimetype
   */
  protected function mimetypeMagicProperty($function, $value) {
    switch($function) {
      case 'get':
        $this->populateDatastreamInfo();
        return $this->datastreamInfo['dsMIME'];
        break;
      case 'isset':
        $this->populateDatastreamInfo();
        return $this->isDatastreamProperySet($this->datastreamInfo['dsMIME'], '');
        break;
      case 'set':
        // @todo handle parsing errors
        $this->modifyDatastream(array('mimeType' => $value));
        break;
      case 'unset':
        trigger_error("Cannot unset the required datastream->mimetype property.", E_USER_WARNING);
        break;
    }
  }

  protected function formatMagicProperty($function, $value) {
    switch($function) {
      case 'get':
        $this->populateDatastreamInfo();
        return $this->datastreamInfo['dsFormatURI'];
        break;
      case 'isset':
        $this->populateDatastreamInfo();
        return $this->isDatastreamProperySet($this->datastreamInfo['dsFormatURI'], '');
        break;
      case 'set':
        $this->modifyDatastream(array('formatURI' => $value));
        break;
      case 'unset':
        $this->modifyDatastream(array('formatURI' => ''));
        break;
    }
  }

  protected function sizeMagicProperty($function, $value) {
    switch($function) {
      case 'get':
        $this->populateDatastreamInfo();
        return $this->datastreamInfo['dsSize'];
        break;
      case 'isset':
        return TRUE;
        break;
      case 'set':
      case 'unset':
        trigger_error("Cannot $function the readonly datastream->size property.", E_USER_WARNING);
        break;
    }
  }

  /**
   * @todo maybe add functionality to set it to auto
   */
  protected function checksumMagicProperty($function, $value) {
    switch($function) {
      case 'get':
        $this->populateDatastreamInfo();
        return $this->datastreamInfo['dsChecksum'];
        break;
      case 'isset':
        $this->populateDatastreamInfo();
        return $this->isDatastreamProperySet($this->datastreamInfo['dsChecksum'], 'none');
        break;
      case 'set':
      case 'unset':
        trigger_error("Cannot $function the readonly datastream->checksum property.", E_USER_WARNING);
        break;
    }
  }

  protected function checksumTypeMagicProperty($function, $value) {
    switch($function) {
      case 'get':
        $this->populateDatastreamInfo();
        return $this->datastreamInfo['dsChecksumType'];
        break;
      case 'isset':
        $this->populateDatastreamInfo();
        return $this->isDatastreamProperySet($this->datastreamInfo['dsChecksumType'], 'DISABLED');
        break;
      case 'set':
        $type = $this->validateChecksumType($value);
        if($type) {
          $this->modifyDatastream(array('checksumType' => $type));
        }
        else {
          trigger_error("$value is not a valid value for the datastream->checksumType property.", E_USER_WARNING);
        }
        break;
      case 'unset':
        $this->modifyDatastream(array('checksumType' => 'DISABLED'));
        break;
    }
  }

  protected function createdDateMagicProperty($function, $value) {
    switch($function) {
      case 'get':
        $this->populateDatastreamInfo();
        return new FedoraDate($this->datastreamInfo['dsCreateDate']);
        break;
      case 'isset':
        return TRUE;
      case 'set':
      case 'unset':
        trigger_error("Cannot $function the readonly datastream->createdDate property.", E_USER_WARNING);
        break;
    }
  }

  /**
   * @todo Modify caching depending on size.
   */
  protected function contentMagicProperty($function, $value) {
    switch($function) {
      case 'get':
        return $this->getDatastreamContent();
        break;
      case 'isset':
        return $this->isDatastreamProperySet($this->getDatastreamContent(), '');
        break;
      case 'set':
        if($this->controlGroup == 'M' || $this->controlGroup == 'X') {
          $this->modifyDatastream(array('dsString' => $value));
        }
        else {
          trigger_error("Cannot set content of a {$this->controlGroup} datastream, please use datastream->url.", E_USER_WARNING);
        }
        break;
      case 'unset':
        if($this->controlGroup == 'M' || $this->controlGroup == 'X') {
          $this->modifyDatastream(array('dsString' => ''));
        }
        else {
          trigger_error("Cannot unset content of a {$this->controlGroup} datastream, please use datastream->url.", E_USER_WARNING);
        }
        break;
    }
  }

  protected function urlMagicProperty($function, $value) {
    switch($function) {
      case 'get':
        $this->populateDatastreamInfo();
        if($this->controlGroup == 'E' || $this->controlGroup == 'R') {
          return $this->datastreamInfo['dsLocation'];
        }
        else {
          trigger_error("Datastream->url property is undefined for a {$this->controlGroup} datastream.", E_USER_WARNING);
          return NULL;
        }
        break;
      case 'isset':
        $this->populateDatastreamInfo();
        if($this->controlGroup == 'E' || $this->controlGroup == 'R') {
          return TRUE;
        }
        else {
          return FALSE;
        }
        break;
      case 'set':
        if($this->controlGroup == 'E' || $this->controlGroup == 'R') {
          $this->modifyDatastream(array('formatURI' => $value));
        }
        else {
          trigger_error("Cannot set url of a {$this->controlGroup} datastream, please use datastream->content.", E_USER_WARNING);
        }
        break;
      case 'unset':
        trigger_error("Cannot unset the required datastream->url property.", E_USER_WARNING);
        break;
    }
  }

  public function setContentFromFile($file) {
    if ($this->controlGroup == 'E' || $this->controlGroup == 'R') {
      trigger_error("Function cannot be called on a {$this->controlGroup} datastream. Please use datastream->url.", E_USER_WARNING);
      return;
    }
    $this->modifyDatastream(array('dsFile' => $file));
  }

  public function setContentFromUrl($url) {
    if ($this->controlGroup == 'E' || $this->controlGroup == 'R') {
      trigger_error("Function cannot be called on a {$this->controlGroup} datastream. Please use datastream->url.", E_USER_WARNING);
      return;
    }
    $this->modifyDatastream(array('dsLocation' => $url));
  }

  public function setContentFromString($string) {
    if ($this->controlGroup == 'E' || $this->controlGroup == 'R') {
      trigger_error("Function cannot be called on a {$this->controlGroup} datastream. Please use datastream->url.", E_USER_WARNING);
      return;
    }
    $this->modifyDatastream(array('dsString' => $string));
  }

  public function count() {
    $this->populateDatastreamHistory();
    return count($this->datastreamHistory);
  }

  public function offsetExists ( $offset ) {
    $this->populateDatastreamHistory();
    return isset($this->datastreamHistory);
  }

  public function offsetGet ( $offset ) {
    $this->populateDatastreamHistory();
    return new FedoraDatastreamVersion($this->id, $this->datastreamHistory[$offset], $this, $this->object, $this->repository);
  }

  public function offsetSet ( $offset, $value ) {
    trigger_error("Datastream versions are read only and cannot be set.", E_USER_WARNING);
  }

  public function offsetUnset ( $offset ) {
    $this->populateDatastreamHistory();
    if($this->count() == 1) {
      trigger_error("Cannot unset the last version of a datastream. To delete the datastream use the object->purgeDatastream() function.", E_USER_WARNING);
      return;
    }
    $this->purgeDatastream($this->datastreamHistory[$offset]['dsCreateDate']);
    unset($this->datastreamHistory[$offset]);
    $this->datastreamHistory = array_values($this->datastreamHistory);
    $this->datastreamInfo = $this->datastreamHistory[0];
  }

  public function getIterator() {
    return new ArrayIterator($this);
  }
}