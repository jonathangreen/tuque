<?php

/**
 * @file
 * This file defines the classes that are used for manipulaing the fedora
 * relationships datastreams.
 */

define("XMLNS", "http://www.w3.org/2000/xmlns/");
define("RDF_URI", 'http://www.w3.org/1999/02/22-rdf-syntax-ns#');
define('FEDORA_RELS_EXT_URI', 'info:fedora/fedora-system:def/relations-external#');
define("FEDORA_MODEL_URI", 'info:fedora/fedora-system:def/model#');
define("ISLANDORA_RELS_EXT_URI", 'http://islandora.ca/ontology/relsext#');
define("ISLANDORA_RELS_INT_URI", "http://islandora.ca/ontology/relsint#");

require_once "RepositoryException.php";

/**
 * This is the base class for Fedora Relationships.
 *
 * @todo potentially we should validate the predicate URI
 */
class FedoraRelationships {

  /**
   * The domdocument we are manipulating.
   * @var DomDocument
   */
  protected $document;

  /**
   * The datastream this class is manipulating.
   * @var AbstractFedoraDatastream
   */
  protected $datastream = NULL;

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
   * @param AbstractFedoraDatastream $datastream
   *   The datastream that we are manipulating (relsint, relsext, etc).
   *
   * @param array $namespaces
   *   An array of default namespaces.
   */
  public function  __construct(AbstractFedoraDatastream $datastream, array $namespaces = NULL) {
    if ($namespaces) {
      $this->namespaces = array_merge($this->namespaces, $namespaces);
    }
    $this->datastream = $datastream;

    if (isset($datastream->content)) {
      $this->document = $this->loadDomDocument($datastream->content);
    }
    else {
      $this->document = new DomDocument("1.0", "UTF-8");
      $rootelement = $this->document->createElementNS(RDF_URI, 'RDF');
      $this->document->appendChild($rootelement);
    }

    // Setup the default namespace aliases.
    foreach ($this->namespaces as $alias => $uri) {
      $this->registerNamespaceAlias($alias, $uri);
    }
  }

  /**
   * Add a new namespace to the relationship xml. Doing this before adding new
   * predicates with differnt URIs makes the XML look a little prettier.
   *
   * @param string $alias
   *   The alias to add.
   * @param string $uri
   *   The URI to associate with the alias.
   */
  public function registerNamespaceAlias($alias, $uri) {
    $this->document->documentElement->setAttributeNS(XMLNS, "xmlns:$alias", $uri);
  }

  /**
   * This function returns a domXPath object with all the current namespaces
   * already registered.
   *
   * @return DomXPath
   *   The object
   */
  protected function getXpath() {
    $xpath = new DomXPath($this->document);
    foreach ($this->namespaces as $alias => $uri) {
      $xpath->registerNamespace($alias, $uri);
    }
    return $xpath;
  }

  /**
   * This updates the associated datastreams content.
   */
  protected function updateDatastream() {
    if ($this->dirty == TRUE) {
      $this->document->formatOutput = TRUE;
      $this->datastream->content = $this->document->saveXml();
      $this->dirty = FALSE;
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
   *   FedoraRelationships::registerNamespaceAlias() function first.
   * @param string $predicate
   *   The predicate tag to add.
   * @param string $object
   *   The object for the relationship that is being created.
   * @param boolean $literal
   *   Specifies if the object is a literal or not.
   */
  public function addRelationship($subject, $predicate_uri, $predicate, $object, $literal = FALSE) {
    $this->dirty = TRUE;
    $xpath = $this->getXpath();

    $description_upper = $xpath->query('/rdf:RDF/rdf:Description[@rdf:about="info:fedora/' . $subject . '"]');
    $description_lower = $xpath->query('/rdf:RDF/rdf:description[@rdf:about="info:fedora/' . $subject . '"]');

    if ($description_lower->length == 0 && $description_upper->length == 0) {
      $description = $this->document->createElementNS(RDF_URI, 'Description');
      $this->document->documentElement->appendChild($description);
      $description->setAttributeNS(RDF_URI, 'about', "info:fedora/$subject");
    }
    elseif ($description_lower->length) {
      // We have an element with a lower case description, we replace it so that
      // we don't have to deal with it in the future.
      $description = $this->document->createElementNS(RDF_URI, 'Description');
      $old_description = $description_upper->item(0);

      foreach ($old_description->attributes as $attribute) {
        $description->setAttribute($attribute->name, $attribute->value);
      }
      foreach ($old_description->childNodes as $child) {
        $description->appendChild($child->cloneNode(TRUE));
      }
      $old_description->parentNode->replaceChild($description, $old_description);
    }
    else {
      $description = $description_upper->item(0);
    }

    $relationship = $this->document->createElementNS($predicate_uri, $predicate);
    $description->appendChild($relationship);

    if ($literal) {
      $relationship->nodeValue = $object;
    }
    else {
      $relationship->setAttributeNS(RDF_URI, 'resource', 'info:fedora/' . $object);
    }

    $this->updateDatastream();
  }

  /**
   * This function is used to create an xpath expression based on the input.
   *
   * @return DOMNodeList
   *   The node list
   */
  protected function getXpathResults($subject, $predicate_uri, $predicate, $object, $literal) {
    $xpath_object = $this->getXpath();

    $xpath = '/rdf:RDF/rdf:Description[@rdf:about="info:fedora/' . $subject . '"]';

    // We do this to deal with the lowercase d.
    $result = $xpath_object->query($xpath);
    if ($result->length == 0) {
      $xpath = '/rdf:RDF/rdf:description[@rdf:about="info:fedora/' . $subject . '"]';
    }

    if ($predicate == NULL) {
      $xpath .= '/*';
    }
    else {
      $xpath_object->registerNamespace('pred_uri', $predicate_uri);
      $xpath .= '/pred_uri:' . $predicate;
    }

    if ($object) {
      if ($literal) {
        $xpath .= '[.="' . $object . '"]';
      }
      else {
        $xpath .= '[@rdf:resource="info:fedora/' . $object . '"]';
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
   * @param boolean $literal
   *   Defines if the $object is a literal or not.
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
   *                       [prefix] => thexmlprefix
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
  public function getRelationships($subject, $predicate_uri = NULL, $predicate = NULL, $object = NULL, $literal = FALSE) {
    $result_elements = $this->getXpathResults($subject, $predicate_uri, $predicate, $object, $literal);
    $results = array();
    foreach ($result_elements as $element) {
      $result = array();

      $result['predicate'] = array();
      $result['predicate']['value'] = $element->localName;
      if (isset($element->prefix)) {
        $result['predicate']['prefix'] = $element->prefix;
      }
      if (isset($element->namespaceURI)) {
        $result['predicate']['namespace'] = $element->namespaceURI;
      }

      $object = array();
      if ($element->hasAttributeNS($this->namespaces['rdf'], 'resource')) {
        $attrib = $element->getAttributeNS($this->namespaces['rdf'], 'resource');
        $attrib = explode('/', $attrib);
        unset($attrib[0]);
        $attrib = implode('/', $attrib);
        $object['literal'] = FALSE;
        $object['value'] = $attrib;
      }
      else {
        $object['literal'] = TRUE;
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
   * @param boolean $literal
   *   Defines if the $object is a literal or not.
   *
   * @return boolean
   *   TRUE if relationships were removed, FALSE otherwise.
   */
  public function removeRelationships($subject, $predicate_uri, $predicate, $object, $literal = FALSE) {
    $return = FALSE;
    $result_elements = $this->getXpathResults($subject, $predicate_uri, $predicate, $object, $literal = FALSE);

    if ($result_elements->length > 0) {
      $return = TRUE;
      $this->dirty = TRUE;
    }

    foreach ($result_elements as $element) {
      $parent = $element->parentNode;
      $parent->removeChild($element);

      if (!$parent->hasChildNodes()) {
        $parent->parentNode->removeChild($parent);
      }
    }
    $this->updateDatastream();
    return $return;
  }

}
