<?php

namespace Islandora\Tuque\Object;

use ArrayAccess;
use Countable;
use Islandora\Tuque\MagicProperty\MagicProperty;
use IteratorAggregate;

/**
 * An abstract class defining a Object in the repository. This is the class
 * that needs to be implemented in order to create new repository backends
 * that can be accessed using Tuque.
 *
 * These classes implement the php object array interfaces so that the object
 * can be accessed as an array. This provides access to datastreams. The object
 * is also traversable with foreach, so that each datastream can be accessed.
 *
 * @code
 * $object = new AbstractObject()
 *
 * // access every object
 * foreach ($object as $dsid => $dsObject) {
 *   // print dsid and set contents to "foo"
 *   print($dsid);
 *   $dsObject->content = 'foo';
 * }
 *
 * // test if there is a datastream called 'DC'
 * if (isset($object['DC'])) {
 *   // if there is print its contents
 *   print($object['DC']->content);
 * }
 *
 * @endcode
 */
abstract class AbstractObject implements Countable, ArrayAccess, IteratorAggregate
{
    use MagicProperty;

    /**
     * The label for this object. Fedora limits the label to be 255 characters.
     * Anything after this amount is truncated.
     *
     * @var string
     */
    public $label;

    /**
     * The user who owns this object.
     *
     * @var string
     */
    public $owner;

    /**
     * The state of this object. Must be one of: A (Active), I (Inactive) or
     * D (Deleted). This is a required property and cannot be unset.
     *
     * @var string
     */
    public $state;

    /**
     * The identifier of the object.
     *
     * @var string
     */
    public $id;

    /**
     * The date that the object was created. Only valid for objects that have
     * been ingested.
     *
     * @var \Islandora\Tuque\Date\FedoraDate
     */
    public $createdDate;

    /**
     * The date the object was last modified.
     *
     * @var \Islandora\Tuque\Date\FedoraDate
     */
    public $lastModifiedDate;

    /**
     * Log message associated with the creation of the object in Fedora.
     *
     * @var string
     */
    public $logMessage;

    /**
     * An array of strings containing the content models of the object.
     *
     * @var array
     */
    public $models;

    /**
     * Set the state of the object to deleted.
     */
    abstract public function delete();

    /**
     * Get a datastream from the object.
     *
     * @param string $id
     *   The id of the datastream to retreve.
     *
     * @return \Islandora\Tuque\Datastream\AbstractDatastream
     *   Returns FALSE if the datastream could not be found. Otherwise it return
     *   an instantiated Datastream object.
     */
    abstract public function getDatastream($id);

    /**
     * Purges a datastream.
     *
     * @param string $id
     *   The id of the datastream to purge.
     *
     * @return boolean
     *   TRUE on success. FALSE on failure.
     */
    abstract public function purgeDatastream($id);

    /**
     * Factory to create new datastream objects. Creates a new datastream object,
     * this object is not ingested into the repository until you call
     * ingestDatastream.
     *
     * @param string $id
     *   The identifier of the new datastream.
     * @param string $control_group
     *   The control group the new datastream will be created in.
     *
     * @return \Islandora\Tuque\Datastream\AbstractDatastream
     *   Returns an instantiated Datastream object.
     */
    abstract public function constructDatastream($id, $control_group = 'M');

    /**
     * Ingests a datastream object into the repository.
     *
     * @param \Islandora\Tuque\Datastream\AbstractDatastream $ds
     */
    abstract public function ingestDatastream(&$ds);

    /**
     * Unsets public members.
     *
     * We only define the public members of the object for Doxygen, they aren't
     * actually accessed or used, and if they are not unset, they can cause
     * problems after deserialization.
     */
    public function __construct()
    {
        $this->unsetMembers();
    }

    /**
     * Upon unserialization unset any public members.
     */
    public function __wakeup()
    {
        $this->unsetMembers();
    }

    /**
     * Unsets public members, required for child classes to function properly with MagicProperties.
     */
    private function unsetMembers()
    {
        unset($this->id);
        unset($this->state);
        unset($this->createdDate);
        unset($this->lastModifiedDate);
        unset($this->label);
        unset($this->owner);
        unset($this->logMessage);
        unset($this->models);
    }
}
