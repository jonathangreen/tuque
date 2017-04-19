<?php

namespace Islandora\Tuque\Repository;

use Islandora\Tuque\MagicProperty\MagicProperty;
use Islandora\Tuque\Object\NewFedoraObject;

/**
 * An abstract repository interface.
 *
 * This can be used to override the implementation of the Repository.
 */
abstract class AbstractRepository extends MagicProperty
{

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
     * @param boolean $create_uuid
     *   Indicates if the objects ID should contain a UUID.
     *
     * @return \Islandora\Tuque\Object\AbstractObject
     *   Returns an instantiated AbstractObject object that can be manipulated.
     *   This object will not actually be created in the repository until the
     *   ingest method is called.
     */
    abstract public function constructObject($id = null, $create_uuid = false);

    /**
     * This ingests a new object into the repository.
     *
     * @param \Islandora\Tuque\Object\NewFedoraObject &$object
     *   The instantiated AbstractObject to ingest into the repository. This
     *   object is passed by reference, and the reference will be replaced by
     *   an object representing the ingested AbstractObject.
     *
     * @return \Islandora\Tuque\Object\AbstractObject
     *   The ingested abstract object.
     */
    abstract public function ingestObject(NewFedoraObject &$object);

    /**
     * Gets a object from the repository.
     *
     * @param string $id
     *   The identifier of the object.
     *
     * @return \Islandora\Tuque\Object\AbstractObject
     *   The requested object.
     */
    abstract public function getObject($id);

    /**
     * Removes an object from the repository.
     *
     * This function removes an object from the repository permanency. It is a
     * dangerous function since it removes an object and all of its history from
     * the repository permanently.
     *
     * @param string $id
     *   The identifier of the object.
     *
     * @return boolean
     *   TRUE if object was purged.
     */
    abstract public function purgeObject($id);

    /**
     * Search the repository for objects.
     *
     * This function isn't implemented yet.
     *
     * @todo Flesh out the function definition for this.
     */
    abstract public function findObjects(array $search);

    /**
     * Will return an unused identifier for an object.
     *
     * @note
     *   It is not mathematically impossible to have collisions if the
     *   $create_uuid parameter is set to true.
     *
     * @param mixed $namespace
     *   NULL if we should use the default namespace.
     *   string the namespace to be used for the identifier.
     * @param boolean $create_uuid
     *   True if a V4 UUID should be used as part of the identifier.
     * @param integer $number_of_identifiers
     *   The number of identifers to return
     *   Defaults to 1.
     *
     * @return mixed
     *   string An identifier for an object.
     *   array  An array of identifiers for an object.
     *     @code
     *       Array
     *         (
     *           [0] => test:7
     *           [1] => test:8
     *         )
     *     @endcode
     */
    abstract public function getNextIdentifier($namespace = null, $create_uuid = false, $number_of_identifiers = 1);
}
