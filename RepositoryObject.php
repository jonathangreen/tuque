<?php

/**
 * @file
 * This file contains the abstract defintion of a repository object as well
 * as some implementations.
 */

include_once('MagicProperty.php');

/**
 * This abstract base class defines a RespoitoryObject.
 * 
 * This is used to manipulate objects in the repository. It includes several PHP built in interfaces 
 * to add some syntactic sugar for accessing the object. 
 * 
 * @todo Should we implement addObject and getObject?
 * @todo Should the constructor be defined here?
 */
abstract class AbstractRepositoryObject extends MagicProperty implements ArrayAccess, Countable, Iterator {
  
  /**
   * @var $id 
   * This is the identifier of the Object. 
   * 
   * It is a read only property and an exception will be
   * thrown if the property is modified.
   */
  abstract protected function idMagicProperty($function, $value);
  
  /**
   * @var $state
   * The state of the object. Valid states are Active, Inactive or Deleted. These can also be referenced
   * using A, I, or D. This isn't case sensitive. An exception will be thrown if the state is invalid or
   * cannot be changed.
   */
  abstract protected function stateMagicProperty($function, $value);
  
  /**
   * @var $label
   * The label of the object. 
   */
  abstract protected function labelMagicProperty($function, $value);
  
  /**
   * @var $owner
   * The owner of the object.
   */
  abstract protected function ownerMagicProperty($function, $value);
  
  /**
   * @var $createdDate
   * The date that the object was created. Throws exception if you try to modify it.
   */
  abstract protected function createdDateMagicProperty($function, DateTime $value = NULL);
  
  /**
   * @var $lastModifiedDate
   * The date that the object was last known to be modified. Throws an exception if this is
   * later then now.
   */
  abstract protected function lastModifiedDateMagicProperty($function, DateTime $value = NULL);
  
  /**
   * Purge the object from the repository. This action cannot be undone. The object is permenently 
   * removed from the repository.
   */
  abstract public function purge();
  
  /**
   * The object is deleted from the repository. This will set the objects state to deleted. It is
   * equivelent to calling $object->state = 'd';
   */
  abstract public function delete();
  
  /**
   * Add a new datastream
   */
  abstract public function addDatastream(FedoraDatastream $datastream);
  
  /**
   * Get a datastream.
   */
  abstract public function getDatastream($id, $asOf);
}  

class TestRepositoryObject extends AbstractRepositoryObject {
  private $datastreams = array();
  private $properties = array();
  private $valid = TRUE;
  
  public function __construct($id) {
    $this->properties['id'] = $id;
    $this->properties['createdDate'] = new DateTime();
    $this->properties['state'] = 'A';
  }
  
  protected function idMagicProperty($function, $value) {
    switch($function) {
      case 'get': 
        return $this->properties['id'];
        break;
      case 'isset':
        return TRUE;
        break;
      case 'set':
      case 'unset':
        throw new Exception('read only');
        break;
    }
  }
  
  protected function stateMagicProperty($function, $value) {
    switch($function) {
      case 'get': 
        if(isset($this->properties['state']))
          return $this->properties['state'];
        else
          return NULL;
        break;
      case 'isset':
        return isset($this->properties['state']);
        break;
      case 'set':
        $newstate = NULL;
        switch(strtolower($value)) {
          case 'a':
          case 'active':
            $newstate = 'A';
            break;
          case 'i':
          case 'inactive':
            $newstate = 'I';
            break;
          case 'd':
          case 'deleted':
            $newstate = 'D';
            break;
          default:
            throw new Exception('message');
        }
        $this->properties['state'] = $newstate;
        break;
      case 'unset':
        unset($this->properties['state']);
        break;
    }
  }
  
  protected function labelMagicProperty($function, $value) {
    switch($function) {
      case 'get': 
        if(isset($this->properties['label']))
          return $this->properties['label'];
        else
          return NULL;
        break;
      case 'isset':
        return isset($this->properties['label']);
        break;
      case 'set':
        $this->properties['label'] = $value;
        break;
      case 'unset':
        unset($this->properties['label']);
        break;
    }
  }
  
  protected function ownerMagicProperty($function, $value) {
    switch($function) {
      case 'get': 
        if(isset($this->properties['owner']))
          return $this->properties['owner'];
        else
          return NULL;
        break;
      case 'isset':
        return isset($this->properties['owner']);
        break;
      case 'set':
        $this->properties['owner'] = $value;
        break;
      case 'unset':
        unset($this->properties['owner']);
        break;
    }
  }
  
  protected function createdDateMagicProperty($function, DateTime $value = NULL) {
    switch($function) {
      case 'get': 
        return $this->properties['createdDate'];
        break;
      case 'isset':
        return isset($this->properties['createdDate']);
        break;
      case 'set':
      case 'unset':
        throw new Exception('read only');
        break;
    }
  }
  
  protected function lastModifiedDateMagicProperty($function, DateTime $value = NULL) {
    switch($function) {
      case 'get': 
        if(isset($this->properties['lastModifiedDate']))
          return $this->properties['lastModifiedDate'];
        else
          return NULL;
        break;
      case 'isset':
        return isset($this->properties['lastModifiedDate']);
        break;
      case 'set':
        if($value instanceof DateTime && $value <= new DateTime()) {
          $this->properties['lastModifiedDate'] = $value; 
        } else {
          throw new Exception ('must be instance of date time');
        }
        break;
      case 'unset':
        unset($this->properties['lastModifiedDate']);
        break;
    }
  }
  
  public function delete() {
    $this->state = 'deleted';
  }
  
  /**
   * @todo this needs to be checked elsewhere
   */
  public function purge() {
    $this->valid = FALSE;
  }
  
  public function addDatastream(FedoraDatastream $datastream) {
    if(!$isset($this->datastreams[$datastream->id])) {
      $this->datastreams[$datastream->id] = $datastream;
    }
    else {
      throw new Exception('Datastream id collision');
    }
  }
  
  public function getDatastream($id, $asOf) {
    if(isset($this->datastreams[$id])) {
      return $this->datastreams[$id];
    }
    else {
      throw new Exception('Eoot');
    }
  }
  
  public function offsetExists($offset) {
    return isset($this->datastreams[$offset]);
  }
  
  public function offsetUnset($offset) {
    if(isset($this->datastreams[$offset])) {
      $this->datastreams[$offset]->delete();
    }
  }
  
  /**
   * @todo this should be throwing a warning if the offset is undefined, like php would.
   */
  public function offsetGet($offset) {
    if(isset($this->datastreams[$offset])) {
      return $this->datastreams[$offset];
    }
    else {
      return NULL;
    }
  }
  
  /**
   * @todo What do we do when $offset is NULL?
   */
  public function offsetSet($offset, $value) {
    if($value instanceof FedoraDatastream) {
      $this->datastreams[$offset] = $value;
    }
    else {
      throw new Exception('test');
    }
  }
  
  public function count() {
    return count($this->datastreams);
  }
  
  private $iteratorPosition;
  private $iteratorKeys;
  
  public function rewind() {
    $this->iteratorKeys = array_keys($this->datastreams);
    $this->iteratorPosition = 0;
  }
  
  public function current() {
    $this->datastreams[$this->iteratorKeys[$this->iteratorPosition]];
  }
  
  public function valid() {
    isset($this->iteratorKeys[$this->iteratorPosition]);
  }
  
  public function key() {
    $this->iteratorKeys[$this->iteratorPosition];
  }
  
  public function next() {
    $this->iteratorPosition++;
  }
}

$test = new TestObject('woot:boot');
$test->delete();
print count($test) . "\n";
$test['zap'] = new FedoraDatastream('42');
print count($test) . "\n";
print count($test) . "\n";
