<?php

namespace Islandora\Tuque\Relationships;

use Islandora\Tuque\Datastream\AbstractDatastream;

class FedoraRelsInt extends FedoraRelationships
{

    protected $aboutDs;

    /**
     * Objects Construct!
     *
     * @param AbstractDatastream $datastream
     *   The datastream whose relationships we are manipulating
     */
    public function __construct(AbstractDatastream $datastream)
    {
        $this->aboutDs = $datastream;

        $namespaces = array(
            'islandora' => ISLANDORA_RELS_INT_URI,
        );

        parent::__construct($namespaces);
    }

    /**
     * Delay initialization by waiting to set datastream with this function.
     */
    protected function initializeDatastream()
    {
        if ($this->datastream === null) {
            if (isset($this->aboutDs->parent['RELS-INT'])) {
                $ds = $this->aboutDs->parent['RELS-INT'];
            } else {
                $ds = $this->aboutDs->parent->constructDatastream('RELS-INT', INIT_DS_CONTROL_GROUP);
                $ds->label = INIT_FEDORA_DS_LABEL;
                $ds->format = INIT_DS_FORMAT;
                $ds->mimetype = INIT_DS_MIME;
                $this->new = true;
            }
            $this->datastream = $ds;
        }
    }

    /**
     * Add a new relationship.
     *
     * @param string $predicate_uri
     *   The URI to use as the namespace of the predicate. If you would like the
     *   XML to use a prefix instead of the full predicate call the
     *   FedoraRelationships::registerNamespace() function first.
     * @param string $predicate
     *   The predicate tag to add.
     * @param string $object
     *   The object for the relationship that is being created.
     * @param int $type
     *   What the attribute type should be. One of the defined literals beginning
     *   with RELS_TYPE_. Defaults to RELS_TYPE_URI.
     */
    public function add($predicate_uri, $predicate, $object, $type = RELS_TYPE_URI)
    {
        $this->initializeDatastream();
        parent::internalAdd(
            "{$this->aboutDs->parent->id}/{$this->aboutDs->id}",
            $predicate_uri,
            $predicate,
            $object,
            $type
        );
    }

    /**
     * This function removes relationships that match the pattern from the
     * datastream. Any parameter can be given as NULL which will make it a
     * wildcard.
     *
     * @param string $predicate_uri
     *   The URI to use as the namespace of the predicate. This is ignored if
     *   predicate is NULL.
     * @param string $predicate
     *   The predicate tag to filter removed results by.
     * @param string $object
     *   The object for the relationship to filter by.
     * @param int $type
     *   What the attribute type should be. One of the defined literals beginning
     *   with RELS_TYPE_. Defaults to RELS_TYPE_URI.
     *
     * @return boolean
     *   TRUE if relationships were removed, FALSE otherwise.
     */
    public function remove($predicate_uri = null, $predicate = null, $object = null, $type = RELS_TYPE_URI)
    {
        $this->initializeDatastream();
        $return = parent::internalRemove(
            "{$this->aboutDs->parent->id}/{$this->aboutDs->id}",
            $predicate_uri,
            $predicate,
            $object,
            $type
        );


        return $return;
    }

    /**
     * This function queries the relationships in the assocaited datastream. Any
     * parameter except for $subject can be set to NULL to act as a wildcard.
     * Calling with just $subject will return all relationships.
     *
     * @param string $predicate_uri
     *   The URI to use as the namespace of the predicate. This is ignored if
     *   predicate is NULL.
     * @param string $predicate
     *   The predicate tag to filter by.
     * @param string $object
     *   The object for the relationship to filter by.
     * @param int $type
     *   What the attribute type should be. One of the defined literals beginning
     *   with RELS_TYPE_. Defaults to RELS_TYPE_URI.
     *
     * @return array
     *   This returns an indexed array with all the matching relationships. The
     *   array is of the form:
     *   @code
     *   Array
     *   (
     *       [0] => Array
     *           (
     *               [predicate] => Array
     *                   (
     *                       [value] => thepredicate
     *                       [alias] => thexmlprefix
     *                       [namespace] => http://crazycool.com#
     *                   )
     *
     *               [object] => Array
     *                   (
     *                       [literal] => TRUE
     *                       [value] => test
     *                   )
     *
     *           )
     *
     *   )
     *   @endcode
     */
    public function get($predicate_uri = null, $predicate = null, $object = null, $type = RELS_TYPE_URI)
    {
        // NOTE: Attempting to initialize RELS-INT without writing it (as happens
        // with get() calls) across different datastreams leads to multiple RELS-INT
        // datastreams being constructed... Should one then attempt to make
        // modifications to more than one, each tries to write their own datastream.
        // By avoiding "initializing", we avoid this issue.
        if (!isset($this->aboutDs->parent['RELS-INT'])) {
            return array();
        }
        $this->initializeDatastream();
        return parent::internalGet(
            "{$this->aboutDs->parent->id}/{$this->aboutDs->id}",
            $predicate_uri,
            $predicate,
            $object,
            $type
        );
    }

    public function changeObjectID($id)
    {
        $this->initializeDatastream();
        return parent::changeObjectID($id);
    }
}
