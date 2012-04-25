<?php
/**
 * @file
 * This file defines an abstract repository that can be overridden and also
 * defines a concrete implementation for Fedora.
 */

require_once "FoxmlDocument.php";
require_once "Object.php";

/**
 * An abstract repository interface. This can be used to override the
 * implementation of the Repository.
 *
 * Instantantiated children of this abstract class allow objects to be accessed
 * as an array for example to get an object:
 * @code
 *   $object = $repository['objectid'];
 * @endcode
 *
 * To test if an object exists:
 * @code
 *   $exists = isset($repository['objectid']);
 * @endcode
 */
abstract class AbstractRepository extends MagicProperty implements ArrayAccess {

  /**
   * This method is a factory that will return a new repositoryobject object
   * that can be manipulated and then ingested into the repository.
   *
   * @param string $id
   *   The ID to assign to this object. There are three options:
   *   - NULL: An ID will be assigned.
   *   - A namespace: An ID will be assigned in this namespace.
   *   - A whole ID: The whole ID must contains a namespace and a identifier in
   *     the form NAMESPACE:IDENTIFIER
   *
   * @return AbstractObject
   *   Returns an instantiated AbstractObject object that can be manipulated.
   *   This object will not actually be created in the repository until the
   *   ingest method is called.
   */
  abstract public function constructNewObject($id = NULL);

  /**
   * This ingests a new object into the repository.
   *
   * @param AbstractObject &$object
   *   The instantiated AbstractObject to ingest into the repository. This
   *   object is passed by reference, and the reference will be replaced by
   *   an object representing the ingested AbstractObject.
   *
   * @return AbstractObject
   *   The ingested abstract object.
   */
  abstract public function ingestNewObject(&$object);

  //abstract public function getObject($pid);
  //abstract public function newObject($pid);
  abstract public function findObjects(array $search);
}

class FedoraRepository extends AbstractRepository {
  protected $cache;

  public function __construct(FedoraApi $api, AbstractCache $cache) {
    $this->api = $api;
    $this->cache = $cache;
  }

  public function findObjects(array $search) {
  }

  /**
   * @todo validate the ID
   * @todo catch the getNextPid errors
   */
  public function constructNewObject($id = NULL) {
    if($this->cache->get($id) !== FALSE) {
      return FALSE;
    }

    $exploded = explode(':', $id);

    if(!$id) {
      $id = $this->api->m->getNextPid();
    }
    elseif (count($exploded) == 1) {
      $id = $this->api->m->getNextPid($exploded[0]);
    }

    return new NewFedoraObject($id, $this);
  }

  /*
   * @todo error handling
   */
  public function ingestNewObject(NewFedoraObject &$object) {
    $dom = new FoxmlDocument($object);
    $xml = $dom->saveXml();
    $id = $this->api->m->ingest(array('string' => $xml));
    $object = new FedoraObject($id, $this);
    $this->cache->set($id, $object);
    return $object;
  }

  public function getObject($id) {
    $object = $this->cache->get($id);
    if($object !== FALSE) {
      return $object;
    }

    try {
      $object = new FedoraObject($id, $this);
      $this->cache->set($id, $object);
      return $object;
    }
    catch (RepositoryException $e) {
      // check to see if its a 401 or a 404
      $previous = $e->getPrevious();
      if($previous && ($previous->getCode == 404 || $previous->getCode == 401)) {
        return NULL;
      }
      else {
        // @todo fix this, it should throw something else.
        throw $e;
      }
    }
  }

  public function purgeObject($id) {
    $object = $this->cache->get($id);
    if($object !== FALSE) {
      $this->cache->delete($id);
    }

    try {
      $this->api->m->purgeObject($id);
    }
    catch (RepositoryException $e) {
      // @todo chain exceptions here.
      throw $e;
    }
  }

  public function offsetExists ( $offset ) {}
  public function offsetGet ( $offset ) {}
  public function offsetSet ( $offset , $value ) {}
  public function offsetUnset ( $offset ) {}
}
