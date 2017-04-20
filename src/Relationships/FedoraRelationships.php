<?php

namespace Islandora\Tuque\Relationships;

use Islandora\Tuque\MagicProperty\MagicProperty;
use DOMDocument;
use DOMXPath;

define("XMLNS", "http://www.w3.org/2000/xmlns/");
define('FEDORA_RELS_EXT_URI', 'info:fedora/fedora-system:def/relations-external#');
define("FEDORA_MODEL_URI", 'info:fedora/fedora-system:def/model#');
define("ISLANDORA_RELS_EXT_URI", 'http://islandora.ca/ontology/relsext#');
define("ISLANDORA_RELS_INT_URI", "http://islandora.ca/ontology/relsint#");

define("INIT_DS_FORMAT", "info:fedora/fedora-system:FedoraRELSExt-1.0");
define("INIT_DS_LABEL", "Fedora Object to Object Relationship Metadata.");
define("INIT_FEDORA_DS_LABEL", "Fedora Relationship Metadata.");
define("INIT_DS_MIME", "application/rdf+xml");

// This constant is being kept around for backwards compatibility.
define(
    "INIT_DS_CONTROL_GROUP",
    get_cfg_var('tuque.rels_ds_control_group') ?
    get_cfg_var('tuque.rels_ds_control_group') : 'X'
);

define("RDF_URI", 'http://www.w3.org/1999/02/22-rdf-syntax-ns#');

define("RELS_INT_NS", "http://www.w3.org/2001/XMLSchema#int");
define("RELS_STRING_NS", "http://www.w3.org/2001/XMLSchema#string");
define("RELS_DATETIME_NS", "http://www.w3.org/2001/XMLSchema#dateTime");

define("RELS_TYPE_URI", 0);
define("RELS_TYPE_PLAIN_LITERAL", 1);
define("RELS_TYPE_STRING", 2);
define("RELS_TYPE_INT", 3);
define("RELS_TYPE_DATETIME", 4);
define("RELS_TYPE_FULL_URI", 5);

/**
 * This is the base class for Fedora Relationships.
 *
 * @todo potentially we should validate the predicate URI
 */
abstract class FedoraRelationships extends MagicProperty
{

    /**
     * Whether or not the DS has yet to be ingested.
     *
     * @var bool
     */
    protected $new = false;

    /**
     * Whether or not to auto-commit RELS.
     *
     * @var bool
     *
     * Here be dragons. If autoCommit is FALSE Fedora and the local DS object
     * will not be immediately updated with RELS changes. Bad things may happen.
     * Defaults to TRUE in the constructor as var has to start NULL for
     * magicProperty.
     */
    public $autoCommit;

    /**
     * The cache used when $autoCommit is disabled.
     * @var DomDocument
     */
    protected $domCache = null;

    /**
     * The datastream this class is manipulating.
     * @var \Islandora\Tuque\Datastream\AbstractDatastream
     */
    public $datastream = null;

    /**
     * An array of namespaces that is used in the document.
     * @var array
     */
    protected $namespaces = array(
        'rdf' => RDF_URI,
    );

    /**
     * The constructor. This will usually be called by one of its subclasses.
     *
     * @param array $namespaces
     *   An array of default namespaces.
     */
    public function __construct(array $namespaces = null)
    {
        unset($this->autoCommit);
        $this->nonMagicAutoCommit = true;
        if ($namespaces) {
            $this->namespaces = array_merge($this->namespaces, $namespaces);
        }
    }

    /**
     * Upon deserialization unset any MagicProperty vars.
     */
    public function __wakeup()
    {
        unset($this->autoCommit);
    }

    /*
     * MagicProperty for autoCommit.
     */
    protected function autoCommitMagicProperty($function, $value)
    {
        switch ($function) {
            case 'get':
                return $this->nonMagicAutoCommit;
                break;

            case 'isset':
                return isset($this->nonMagicAutoCommit);
                break;

            case 'set':
                // Flush the cache if setting autoCommit.
                if ($value == true && !$this->nonMagicAutoCommit) {
                    $this->nonMagicAutoCommit = $value;
                    $this->saveRelationships($this->domCache);
                    $this->domCache = null;
                } // Set cache if un-setting autoCommit.
                elseif ($value == false) {
                    $this->initializeDatastream();
                    $this->domCache = $this->getDom();
                    $this->nonMagicAutoCommit = $value;
                }
                break;

            case 'unset':
                $this->nonMagicAutoCommit = null;
                break;
        }

        return null;
    }

    /**
     * Initialize the datastream that we are using. We use this function to
     * delay this as long as possible, in case it never has to be called.
     */
    abstract protected function initializeDatastream();

    /**
     * Add a new namespace to the relationship xml. Doing this before adding new
     * predicates with different URIs makes the XML look a little prettier.
     *
     * @param string $alias
     *   The alias to add.
     * @param string $uri
     *   The URI to associate with the alias.
     */
    public function registerNamespace($alias, $uri)
    {
        $this->namespaces[$alias] = $uri;
    }

    /**
     * Forces a commit of cached relationships.
     *
     * @param bool $set_auto_commit
     *   Determines exiting autoCommit state.
     *   Defaults to TRUE.
     */
    public function commitRelationships($set_auto_commit = true)
    {
        if ($this->autoCommit == false) {
            // Take advantage of magic.
            $this->autoCommit = true;
            if (!$set_auto_commit) {
                $this->autoCommit = false;
            }
        }
    }

    /**
     * This function returns a domXPath object with all the current namespaces
     * already registered.
     *
     * @param DOMDocument $document
     *   The processing Dom Document.
     *
     * @return DomXPath
     *   The object
     */
    protected function getXpath($document)
    {
        $xpath = new DomXPath($document);
        foreach ($this->namespaces as $alias => $uri) {
            $xpath->registerNamespace($alias, $uri);
        }
        return $xpath;
    }

    /**
     * Escapes strings for use in xpaths.
     *
     * @see http://stackoverflow.com/questions/4820067
     *
     * @param string $input
     *   The string to escape.
     *
     * @return string
     *   The escaped string.
     */
    protected function xpathEscape($input)
    {

        if (false === strpos($input, "'")) {
            return "'$input'";
        }

        if (false === strpos($input, '"')) {
            return "\"$input\"";
        }

        return 'concat("' . strtr($input, array('"' => "\", '\"', \"")) . '")';
    }

    /**
     * Sets up a domdocument for the functions.
     *
     * @return DomDocument
     *   The domdocument to modify
     */
    protected function getDom()
    {
        if (isset($this->datastream->content) && $this->autoCommit) {
            // @todo Proper exception handling.
            $document = new DomDocument();
            $document->preserveWhiteSpace = false;
            $document->loadXml($this->datastream->content);
        } elseif (!is_null($this->domCache) && !$this->autoCommit) {
            $document = $this->domCache;
        } else {
            $document = new DomDocument("1.0", "UTF-8");
            $rootelement = $document->createElement('rdf:RDF');
            $document->appendChild($rootelement);
        }

        // Setup the default namespace aliases.
        foreach ($this->namespaces as $alias => $uri) {
            // if we use setAttributeNS here we drop the rdf: from about which
            // breaks things, so we do this, then the hack below.
            $document->documentElement->setAttribute("xmlns:$alias", $uri);
        }

        // this is a hack, but it makes sure namespaces are properly registered
        $document_namespaces = new DomDocument();
        $document_namespaces->preserveWhiteSpace = false;
        $document_namespaces->loadXml($document->saveXML());

        return $document_namespaces;
    }

    /**
     * Saves relationships to Fedora or localy.
     *
     * This updates the associated datastreams content, or the cache if
     * autocommit is disabled.
     *
     * @param DOMDocument $document
     *   The DOMDocument to save.
     */
    protected function saveRelationships($document)
    {
        if ($this->autoCommit) {
            $document->formatOutput = true;
            $this->datastream->content = $document->saveXml();
            if ($this->new) {
                $this->datastream->parent->ingestDatastream($this->datastream);
            }
        } else {
            $this->domCache = $document;
        }
    }

    /**
     * Add a new relationship.
     *
     * @param string $subject
     *   The subject. This can be a PID, or a PID/DSID combo. This string does
     *   not contain the info:fedora/ part of the URI this is added automatically.
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
     *   with RELS_TYPE_.
     */
    protected function internalAdd($subject, $predicate_uri, $predicate, $object, $type = RELS_TYPE_URI)
    {
        $type = intval($type);
        $document = $this->getDom();
        $xpath = $this->getXpath($document);

        $description_upper = $xpath->query('/rdf:RDF/rdf:Description[@rdf:about="info:fedora/' . $subject . '"]');
        $description_lower = $xpath->query('/rdf:RDF/rdf:description[@rdf:about="info:fedora/' . $subject . '"]');

        if ($description_lower->length == 0 && $description_upper->length == 0) {
            $description = $document->createElementNS(RDF_URI, 'Description');
            $document->documentElement->appendChild($description);
            $description->setAttributeNS(RDF_URI, 'rdf:about', "info:fedora/$subject");
        } elseif ($description_lower->length) {
            $description = $description_lower->item(0);
        } else {
            $description = $description_upper->item(0);
        }

        $relationship = $document->createElementNS($predicate_uri, $predicate);
        $description->appendChild($relationship);
        if (!in_array($type, array(RELS_TYPE_URI, RELS_TYPE_FULL_URI))) {
            $relationship->nodeValue = $object;
        }

        switch ($type) {
            case RELS_TYPE_FULL_URI:
                $relationship->setAttributeNS(RDF_URI, 'rdf:resource', $object);
                break;
            case RELS_TYPE_URI:
                $relationship->setAttributeNS(RDF_URI, 'rdf:resource', 'info:fedora/' . $object);
                break;

            case RELS_TYPE_STRING:
                $relationship->setAttribute('rdf:datatype', 'http://www.w3.org/2001/XMLSchema#string');
                break;

            case RELS_TYPE_INT:
                $relationship->setAttribute('rdf:datatype', 'http://www.w3.org/2001/XMLSchema#int');
                break;

            case RELS_TYPE_DATETIME:
                $relationship->setAttribute('rdf:datatype', 'http://www.w3.org/2001/XMLSchema#dateTime');
                break;
        }

        $this->saveRelationships($document);
    }

    /**
     * This function is used to create an xpath expression based on the input.
     *
     * @remarks FedoraRelationships::getXpathResults can have a predicate without
     *   a predicate_uri. This is potentially dangerous behaviour.
     * @param DOMXPath $xpath_object
     *   The current xpath object.
     * @param string $subject
     *   The subject. This can be a PID, or a PID/DSID combo. This string does
     *   not contain the info:fedora/ part of the URI this is added automatically.
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
     *   with RELS_TYPE_.
     *
     * @return \DOMNodeList
     *   The node list
     */
    protected function getXpathResults($xpath_object, $subject, $predicate_uri, $predicate, $object, $type)
    {
        $xpath = '/rdf:RDF/rdf:Description[@rdf:about="info:fedora/' . $subject . '"]';

        // We do this to deal with the lowercase d.
        $result = $xpath_object->query($xpath);
        if ($result->length == 0) {
            $xpath = '/rdf:RDF/rdf:description[@rdf:about="info:fedora/' . $subject . '"]';
        }

        if ($predicate == null) {
            if ($predicate_uri != null) {
                $xpath_object->registerNamespace('pred_uri', $predicate_uri);
                $xpath .= '/pred_uri:*';
            } else {
                $xpath .= '/*';
            }
        } else {
            if ($predicate_uri != null) {
                $xpath_object->registerNamespace('pred_uri', $predicate_uri);
                $xpath .= '/pred_uri:' . $predicate;
            } else {
                $xpath .= "/*[local-name()='{$predicate}']";
            }
        }

        if ($object) {
            if ($type == RELS_TYPE_FULL_URI) {
                $xpath .= '[@rdf:resource="' . $object . '"]';
            } elseif ($type == RELS_TYPE_URI) {
                $xpath .= '[@rdf:resource="info:fedora/' . $object . '"]';
            } else {
                $xpath .= '[.=' . $this->xpathEscape($object) . ']';
            }
        }
        return $xpath_object->query($xpath);
    }

    /**
     * This function queries the relationships in the assocaited datastream. Any
     * parameter except for $subject can be set to NULL to act as a wildcard.
     * Calling with just $subject will return all relationships.
     *
     * @param string $subject
     *   The subject. This can be a PID, or a PID/DSID combo. This string does
     *   not contain the info:fedora/ part of the URI this is added automatically.
     * @param string $predicate_uri
     *   The URI to use as the namespace of the predicate. This is ignored if
     *   predicate is NULL.
     * @param string $predicate
     *   The predicate tag to filter by.
     * @param string $object
     *   The object for the relationship to filter by.
     * @param int $type
     *   What the attribute type should be. One of the defined literals beginning
     *   with RELS_TYPE_.
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
    protected function internalGet(
        $subject,
        $predicate_uri = null,
        $predicate = null,
        $object = null,
        $type = RELS_TYPE_URI
    ) {
        $document = $this->getDom();
        $xpath = $this->getXpath($document);

        $result_elements = $this->getXpathResults($xpath, $subject, $predicate_uri, $predicate, $object, $type);
        $results = array();
        foreach ($result_elements as $element) {
            $result = array();

            $result['predicate'] = array();
            $result['predicate']['value'] = $element->localName;
            if (isset($element->prefix)) {
                $result['predicate']['alias'] = $element->prefix;
            }
            if (isset($element->namespaceURI)) {
                $result['predicate']['namespace'] = $element->namespaceURI;
            }

            $object = array();
            if ($element->hasAttributeNS($this->namespaces['rdf'], 'resource')) {
                $attrib = $element->getAttributeNS($this->namespaces['rdf'], 'resource');
                $attrib_array = explode('/', $attrib);
                if ($attrib_array[0] == 'info:fedora') {
                    unset($attrib_array[0]);
                    $attrib = implode('/', $attrib_array);
                }
                $object['literal'] = false;
                $object['value'] = $attrib;
            } else {
                $object['literal'] = true;
                $object['value'] = $element->nodeValue;
            }
            $result['object'] = $object;

            $results[] = $result;
        }

        return $results;
    }

    /**
     * This function removes relationships that match the pattern from the
     * datastream. Any parameter can be given as NULL which will make it a
     * wildcard.
     *
     * @param string $subject
     *   The subject. This can be a PID, or a PID/DSID combo. This string does
     *   not contain the info:fedora/ part of the URI this is added automatically.
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
    protected function internalRemove($subject, $predicate_uri, $predicate, $object, $type = RELS_TYPE_URI)
    {
        $return = false;
        $document = $this->getDom();
        $xpath = $this->getXpath($document);

        $result_elements = $this->getXpathResults($xpath, $subject, $predicate_uri, $predicate, $object, $type);

        if ($result_elements->length > 0) {
            $return = true;
        }

        foreach ($result_elements as $element) {
            $parent = $element->parentNode;
            $parent->removeChild($element);

            if (!$parent->hasChildNodes()) {
                $parent->parentNode->removeChild($parent);
            }
        }

        if ($return) {
            $this->saveRelationships($document);
        }

        return $return;
    }

    /**
     * This function allows you to change the ID referenced in the rdf:about
     * attribute. This allows the updating of all the about attributes if the
     * datastream is being attached to another object.
     *
     * @param string $id
     *   The new ID
     */
    public function changeObjectID($id)
    {
        $document = $this->getDom();
        $xpath = $this->getXpath($document);
        $results = $xpath->query('/rdf:RDF/rdf:Description/@rdf:about | /rdf:RDF/rdf:description/@rdf:about');
        $count = $results->length;
        if ($count > 0) {
            for ($i = 0; $i < $count; $i++) {
                $about = $results->item($i);
                $uri = explode('/', $about->value);
                $uri[1] = $id;
                $about->value = implode('/', $uri);
            }
            $this->saveRelationships($document);
        }
    }
}
