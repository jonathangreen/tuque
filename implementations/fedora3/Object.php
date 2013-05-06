<?php

/**
 * @file
 * This file contains all of the functionality for objects in the repository.
 */
require_once 'AbstractObject.php';
require_once 'implementations/fedora3/FedoraDate.php';
require_once 'implementations/fedora3/Datastream.php';
require_once 'implementations/fedora3/FedoraRelationships.php';

/**
 * This is the base class for a Fedora Object.
 */
abstract class AbstractFedoraObject extends AbstractObject {

  /**
   * This is an object for manipulating relationships related to this object.
   * @var FedoraRelsExt
   */
  public $relationships;
  /**
   * The repository this object belongs to.
   * @var FedoraRepository
   */
  public $repository;
  /**
   * The ID of this object.
   * @var string
   */
  protected $objectId;
  /**
   * The object profile from fedora for this object.
   * @var array
   * @see FedoraApiA::getObjectProfile
   */
  protected $objectProfile;
  /**
   * The name of the class that the Factory for FedoraDatastream should
   * produce. This allows us to override the factory in inhereted classes.
   *
   * @var string
   */
  protected $fedoraDatastreamClass = 'FedoraDatastream';
  /**
   * The name of the class that the Factory for NewFedoraDatastream should
   * produce. This allows us to override the factory in inhereted classes.
   *
   * @var string
   */
  protected $newFedoraDatastreamClass = 'NewFedoraDatastream';
  /**
   * The name of the class to use for RelsExt
   *
   * @var string
   */
  protected $fedoraRelsExtClass = 'FedoraRelsExt';

  /**
   * Constructosaurus.
   */
  public function __construct($id, FedoraRepository $repository) {
    parent::__construct();
    $this->repository = $repository;
    $this->objectId = $id;
    $this->relationships = new FedoraRelsExt($this);
  }

  /**
   * Implements Magic Method.  Returns PID of object when object is printed
   *
   * @return string
   */
  public function __toString() {
    return $this->id;
  }

  /**
   * @see AbstractObject::delete()
   */
  public function delete() {
    $this->state = 'D';
  }

  /**
   * @see AbstractObject::id
   */
  protected function idMagicProperty($function, $value) {
    switch ($function) {
      case 'get':
        return $this->objectId;
        break;

      case 'isset':
        return TRUE;
        break;

      case 'set':
      case 'unset':
        trigger_error("Cannot $function the readonly object->id property.", E_USER_WARNING);
        throw new Exception();
        break;
    }
  }

  /**
   * @see AbstractObject::state
   */
  protected function stateMagicProperty($function, $value) {
    switch ($function) {
      case 'get':
        return $this->objectProfile['objState'];
        break;

      case 'isset':
        return TRUE;
        break;

      case 'set':
        switch (strtolower($value)) {
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
            trigger_error("$value is not a valid value for the object->state property.", E_USER_WARNING);
            break;
        }
        break;

      case 'unset':
        trigger_error("Cannot unset the required object->state property.", E_USER_WARNING);
        break;
    }
  }

  /**
   * @see AbstractObject::label
   */
  protected function labelMagicProperty($function, $value) {
    switch ($function) {
      case 'get':
        return $this->objectProfile['objLabel'];
        break;

      case 'isset':
        if ($this->objectProfile['objLabel'] === '') {
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

  /**
   * @see AbstractObject::owner
   */
  protected function ownerMagicProperty($function, $value) {
    switch ($function) {
      case 'get':
        return $this->objectProfile['objOwnerId'];
        break;

      case 'isset':
        if ($this->objectProfile['objOwnerId'] === '') {
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

  /**
   * @see AbstractObject::logMessage
   */
  protected function logMessageMagicProperty($function, $value) {
    switch ($function) {
      case 'get':
        return $this->objectProfile['objLogMessage'];
        break;

      case 'isset':
        if ($this->objectProfile['objLogMessage'] === '') {
          return FALSE;
        }
        else {
          return isset($this->objectProfile['objLogMessage']);
        }
        break;

      case 'set':
        $this->objectProfile['objLogMessage'] = $value;
        break;

      case 'unset':
        $this->objectProfile['objLogMessage'] = '';
        break;
    }
  }

  /**
   * @see AbstractObject::models
   */
  protected function modelsMagicProperty($function, $value) {
    switch ($function) {
      case 'get':
        $models = array();
        $rels_models = $this->relationships->get(FEDORA_MODEL_URI, 'hasModel');
        foreach ($rels_models as $model) {
          $models[] = $model['object']['value'];
        }
        if (!in_array('fedora-system:FedoraObject-3.0', $models)) {
          $models[] = 'fedora-system:FedoraObject-3.0';
        }
        return $models;
        break;

      case 'isset':
        $rels_models = $this->relationships->get(FEDORA_MODEL_URI, 'hasModel');
        return (count($rels_models) > 0);
        break;

      case 'set':
        if (!is_array($value)) {
          $models = array($value);
        }
        else {
          $models = $value;
        }

        if (!in_array('fedora-system:FedoraObject-3.0', $models)) {
          $models[] = 'fedora-system:FedoraObject-3.0';
        }
        foreach ($models as $model) {
          if (!in_array($model, $this->models)) {
            $this->relationships->add(FEDORA_MODEL_URI, 'hasModel', $model);
          }
        }
        break;

      case 'unset':
        $this->relationships->remove(FEDORA_MODEL_URI, 'hasModel');
        break;
    }
  }

  /**
   * @see AbstractObject::constructDatastream()
   */
  public function constructDatastream($id, $control_group = 'M') {
    return new $this->newFedoraDatastreamClass($id, $control_group, $this, $this->repository);
  }

}

/**
 * This represents a new fedora object that hasn't been ingested yet. It lets
 * us pass around the object and add datastreams before we ingest it. This
 * shouldn't be constructed on its own, it should only be created from a
 * factory class.
 */
class NewFedoraObject extends AbstractFedoraObject {

  /**
   * An array of cached datastream objects.
   * @var array
   */
  protected $datastreams = array();

  /**
   * Constructoman!
   */
  public function __construct($id, FedoraRepository $repository) {
    parent::__construct($id, $repository);
    $this->ingested = FALSE;
    $this->objectProfile = array();
    $this->objectProfile['objState'] = 'A';
    $this->objectProfile['objOwnerId'] = $this->repository->api->connection->username;
    $this->objectProfile['objLabel'] = '';
    $this->objectProfile['objLogMessage'] = '';
  }

  /**
   * We override this as the object may need to manipulate its ID before ingestion.
   *
   * @see AbstractObject::id
   */
  protected function idMagicProperty($function, $value) {
    switch ($function) {
      case 'get':
        return isset($this->objectId) ? $this->objectId : NULL;
      case 'isset':
        return isset($this->objectId);
      case 'set':
        $this->objectId = $value;
        if (isset($this['RELS-EXT'])) {
          $this->relationships->changeObjectID($value);
        }
        if (isset($this['RELS-INT'])) {
          $this['RELS-INT']->relationships->changeObjectID($value);
        }
        break;
      case 'unset':
        unset($this->objectId);
        break;
    }
  }

  /**
   * @see AbstractObject::constructDatastream()
   */
  public function constructDatastream($id, $control_group = 'M') {
    return parent::constructDatastream($id, $control_group);
  }


  /**
   * Create a NewFedoraDatastream copy, and wrap it instead of what we had.
   *
   * This is necessary to avoid the possibility of changing a datastream for
   * another object, when copying datastreams between objects.
   */
  private function createNewDatastreamCopy(AbstractDatastream &$datastream) {
    $old_datastream = $datastream;

    $datastream = $this->constructDatastream($old_datastream->id, $old_datastream->controlGroup);

    // Copying the datastream particulars...
    $properties = array('checksumType', 'checksum', 'format', 'mimetype', 'versionable', 'label', 'state');
    if (in_array($old_datastream->controlGroup, array('R', 'E'))) {
      $properties[] = 'url';
    }
    else {
      // Get the content into a file, and add the file.
      $temp_file = tempnam(sys_get_temp_dir(), 'tuque');
      $old_datastream->getContent($temp_file);
      $datastream->setContentFromFile($temp_file);
      unlink($temp_file);
    }
    foreach ($properties as $property) {
      $datastream->$property = $old_datastream->$property;
    }

    $datastream->logMessage = 'Datastream contents copied.';

    unset($this[$datastream->id]);
  }

  /**
   * This function doesn't actually ingest the datastream, it just adds it to
   * the queue to be ingested whenever this object is ingested.
   *
   * @see AbstractObject::ingestDatastream()
   *
   * @param NewFedoraDatastream $ds
   *   the datastream to be ingested
   *
   * @return mixed
   *   FALSE if the datastream already exists; TRUE otherwise.
   */
  public function ingestDatastream(&$ds) {
    if (!isset($this->datastreams[$ds->id])) {
      if ($ds->ingested) {
        // Create a NewFedoraDatastream copy.
        $this->createNewDatastreamCopy($ds);
      }
      $this->datastreams[$ds->id] = $ds;
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * @see AbstractObject::purgeDatastream()
   */
  public function purgeDatastream($id) {
    if (isset($this->datastreams[$id])) {
      unset($this->datastreams[$id]);
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * @see AbstractObject::getDatastream()
   */
  public function getDatastream($id) {
    if (isset($this->datastreams[$id])) {
      return $this->datastreams[$id];
    }
    else {
      return NULL;
    }
  }

  /**
   * @see Countable::count
   */
  public function count() {
    return count($this->datastreams);
  }

  /**
   * @see ArrayAccess::offsetExists
   */
  public function offsetExists($offset) {
    return isset($this->datastreams[$offset]);
  }

  /**
   * @see ArrayAccess::offsetGet
   */
  public function offsetGet($offset) {
    if ($this->offsetExists($offset)) {
      return $this->datastreams[$offset];
    }
    else {
      return FALSE;
    }
  }

  /**
   * @see ArrayAccess::offsetSet
   */
  public function offsetSet($offset, $value) {
    trigger_error("Datastreams must be added though the NewFedoraObect->ingestDatastream() function.", E_USER_WARNING);
  }

  /**
   * @see ArrayAccess::offsetUnset
   */
  public function offsetUnset($offset) {
    $this->purgeDatastream($offset);
  }

  /**
   * @see IteratorAggregate::getIterator()
   */
  public function getIterator() {
    return new ArrayIterator($this->datastreams);
  }

}

/**
 * Represents a Fedora Object. Should be created by the factory method in the
 * repository. Respects object locking. Will throw an exception if an object
 * is modified under us.
 */
class FedoraObject extends AbstractFedoraObject {

  /**
   * Instantiated list of datastream objects.
   * @var array
   */
  protected $datastreams = NULL;
  /**
   * If this is true we won't respect object locking, we will clobber anything
   * that has been changed.
   * @var boolean
   */
  public $forceUpdate = FALSE;

  /**
   * The class constructor. Should be instantiated by the repository.
   */
  public function __construct($id, FedoraRepository $repository) {
    parent::__construct($id, $repository);
    $this->refresh();
    $this->ingested = TRUE;
  }

  /**
   * This function clears the object cache, so everything will be
   * requested directly from fedora again.
   */
  public function refresh() {
    $this->objectProfile = $this->repository->api->a->getObjectProfile($this->id);
    $this->objectProfile['objCreateDate'] = new FedoraDate($this->objectProfile['objCreateDate']);
    $this->objectProfile['objLastModDate'] = new FedoraDate($this->objectProfile['objLastModDate']);
    $this->objectProfile['objLogMessage'] = '';
  }

  /**
   * this will populate the datastream list the first time it is needed.
   */
  protected function populateDatastreams() {
    if (!isset($this->datastreams)) {
      $datastreams = $this->repository->api->a->listDatastreams($this->id);
      $this->datastreams = array();
      foreach ($datastreams as $key => $value) {
        $this->datastreams[$key] = new $this->fedoraDatastreamClass($key, $this, $this->repository, array("dsLabel" => $value['label'], "dsMIME" => $value['mimetype']));
      }
    }
  }

  /**
   * This is a wrapper on APIM modify object to make sure we respect locking.
   */
  protected function modifyObject($params) {
    if (!$this->forceUpdate) {
      $params['lastModifiedDate'] = (string) $this->lastModifiedDate;
    }
    $moddate = $this->repository->api->m->modifyObject($this->id, $params);
    $this->objectProfile['objLastModDate'] = new FedoraDate($moddate);
  }

  /**
   * Purge a datastream.
   *
   * @param string $id
   *   The id of hte datastream to purge.
   *
   * @return boolean
   *   Returns TRUE on success and FALSE on failure.
   */
  public function purgeDatastream($id) {
    $this->populateDatastreams();

    if (!array_key_exists($id, $this->datastreams)) {
      return FALSE;
    }

    $this->repository->api->m->purgeDatastream($this->id, $id);
    unset($this->datastreams[$id]);
    return TRUE;
  }

  /**
   * @see AbstractObject::getDatastream()
   */
  public function getDatastream($id) {
    $this->populateDatastreams();

    if (!array_key_exists($id, $this->datastreams)) {
      return FALSE;
    }

    return $this->datastreams[$id];
  }

  /**
   * @see AbstractObject::state
   */
  protected function stateMagicPropertySet($value) {
    if ($this->objectProfile['objState'] != $value) {
      parent::stateMagicProperty('set', $value);
      $this->modifyObject(array('state' => $this->objectProfile['objState']));
    }
  }

  /**
   * @see AbstractObject::label
   */
  protected function labelMagicPropertySet($value) {
    if ($this->objectProfile['objLabel'] != $value) {
      $this->modifyObject(array('label' => $value));
      parent::labelMagicProperty('set', $value);
    }
  }

  /**
   * @see AbstractObject::owner
   */
  protected function ownerMagicPropertySet($value) {
    if ($this->objectProfile['objOwnerId'] != $value) {
      $this->modifyObject(array('ownerId' => $value));
      parent::ownerMagicProperty('set', $value);
    }
  }

  /**
   * @see AbstractObject::createdDate
   */
  protected function createdDateMagicProperty($function, $value) {
    switch ($function) {
      case 'get':
        return $this->objectProfile['objCreateDate'];
        break;

      case 'isset':
        return TRUE;
        break;

      case 'set':
      case 'unset':
        trigger_error("Cannot $function the readonly object->createdDate property.", E_USER_WARNING);
        break;
    }
  }

  /**
   * @see AbstractObject::lastModifiedDate
   */
  protected function lastModifiedDateMagicProperty($function, $value) {
    switch ($function) {
      case 'get':
        return $this->objectProfile['objLastModDate'];
        break;

      case 'isset':
        return TRUE;
        break;

      case 'set':
      case 'unset':
        trigger_error("Cannot $function the readonly object->lastModifiedDate property.", E_USER_WARNING);
        break;
    }
  }

  /**
   * @see AbstractObject::logMessage
   */
  protected function logMessageMagicPropertySet($value) {
    if ($this->objectProfile['objLogMessage'] != $value) {
      $this->modifyObject(array('logMessage' => $value));
      parent::logMessageMagicProperty('set', $value);
    }
  }

  /**
   * @see AbstractObject::constructDatastream()
   */
  public function constructDatastream($id, $control_group = 'M') {
    return parent::constructDatastream($id, $control_group);
  }

  /**
   * @see AbstractObject::ingestDatastream()
   */
  public function ingestDatastream(&$ds) {
    $this->populateDatastreams();
    if (!isset($this->datastreams[$ds->id])) {
      $params = array(
        'controlGroup' => $ds->controlGroup,
        'dsLabel' => $ds->label,
        'versionable' => $ds->versionable,
        'dsState' => $ds->state,
        'formatURI' => $ds->format,
        'checksumType' => $ds->checksumType,
        'mimeType' => $ds->mimetype,
        'logMessage' => isset($ds->logMessage) ? $ds->logMessage : "Copied datastream from {$ds->parent->id}.",
      );
      $temp = tempnam(sys_get_temp_dir(), 'tuque');
      $return = $ds->getContent($temp);
      if ($return === TRUE) {
        $type = 'file';
        $content = $temp;
      }
      else {
        $type = 'url';
        $content = $ds->content;
      }
      $dsinfo = $this->repository->api->m->addDatastream($this->id, $ds->id, $type, $content, $params);
      unlink($temp);
      $ds = new $this->fedoraDatastreamClass($ds->id, $this, $this->repository, $dsinfo);
      $this->datastreams[$ds->id] = $ds;
      return $ds;
    }
    else {
      return FALSE;
    }
  }

  /**
   * @see Countable::count
   */
  public function count() {
    $this->populateDatastreams();
    return count($this->datastreams);
  }

  /**
   * @see ArrayAccess::offsetExists
   */
  public function offsetExists($offset) {
    $this->populateDatastreams();
    return isset($this->datastreams[$offset]);
  }

  /**
   * @see ArrayAccess::offsetGet
   */
  public function offsetGet($offset) {
    $this->populateDatastreams();
    return $this->getDatastream($offset);
  }

  /**
   * @see ArrayAccess::offsetSet
   */
  public function offsetSet($offset, $value) {
    trigger_error("Datastreams must be added using the FedoraObject->ingestDatastream function.", E_USER_WARNING);
  }

  /**
   * @see ArrayAccess::offsetUnset
   */
  public function offsetUnset($offset) {
    $this->populateDatastreams();
    if (isset($this->datastreams[$offset])) {
      $this->purgeDatastream($offset);
    }
  }

  /**
   * IteratorAggregate::getIterator()
   */
  public function getIterator() {
    $this->populateDatastreams();
    return new ArrayIterator($this->datastreams);
  }

  /**
   * Returns IDs of collections of which object is a member
   *
   * @return array
   */
  public function getParents() {
    $collections = array_merge(
            $this->relationships->get(FEDORA_RELS_EXT_URI, 'isMemberOfCollection'),
            $this->relationships->get(FEDORA_RELS_EXT_URI, 'isMemberOf'));
    $collection_ids = array();
    foreach ($collections as $collection) {
      $collection_ids[] = $collection['object']['value'];
    }
    return $collection_ids;
  }

}
