<?php

namespace Islandora\Tuque\Relationships;

use Islandora\Tuque\Object\AbstractFedoraObject;

class FedoraRelsExt extends FedoraRelationships
{
    /**
     * Objects Construct!
     *
     * @param AbstractFedoraObject $object
     *   The object whose relationships we are manipulating
     */
    public function __construct(AbstractFedoraObject $object)
    {
        $this->object = $object;

        $namespaces = array(
            'fedora' => FEDORA_RELS_EXT_URI,
            'fedora-model' => FEDORA_MODEL_URI,
            'islandora' => ISLANDORA_RELS_EXT_URI,
        );

        parent::__construct($namespaces);
    }

    /**
     * Initialize the datastream that we are using. We use this function to
     * delay this as long as possible, in case it never has to be called.
     */
    protected function initializeDatastream()
    {
        if ($this->datastream === null) {
            if (isset($this->object['RELS-EXT'])) {
                $ds = $this->object['RELS-EXT'];
            } else {
                $ds = $this->object->constructDatastream('RELS-EXT', INIT_DS_CONTROL_GROUP);
                $ds->label = INIT_DS_LABEL;
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
        parent::internalAdd($this->object->id, $predicate_uri, $predicate, $object, $type);
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
        $return = parent::internalRemove($this->object->id, $predicate_uri, $predicate, $object, $type);

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
     * @param mixed $type
     *   What the attribute type should be. One of the defined literals beginning
     *   with RELS_TYPE_.  For backwards compatibility we support TRUE as
     *   RELS_TYPE_PLAIN_LITERAL and FALSE as RELS_TYPE_URI.
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
        $this->initializeDatastream();

        // This method once accepted only booleans.
        if ($type === true) {
            $type = RELS_TYPE_PLAIN_LITERAL;
        } elseif ($type == false) {
            $type = RELS_TYPE_URI;
        }

        return parent::internalGet($this->object->id, $predicate_uri, $predicate, $object, $type);
    }

    public function changeObjectID($id)
    {
        $this->initializeDatastream();
        return parent::changeObjectID($id);
    }
}
