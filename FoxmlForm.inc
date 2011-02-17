<?php

// $Id$

/**
 * @file
 *
 */
module_load_include('inc', 'islandora_form_builder', 'Utilities');

class FoxmlDocument extends DOMDocument {

  /**
   *
   * @var <type>
   */
  protected $pid;
  protected $dsid;
  protected $collectionPid;
  protected $contentModelPid;
  protected $ingestFileLocation;
  protected $label;
  protected $user;
  protected $document;
  protected $transform;

  /**
   *
   * @global <type> $user
   * @param <type> $label
   * @param <type> $pid
   * @param <type> $dsid
   * @param <type> $content_model_pid
   * @param <type> $collection_pid
   * @param <type> $relationship
   * @param <type> $ingest_file_location
   * @param <type> $document 
   */
  public function __construct($label, $pid, $dsid, $content_model_pid, $collection_pid, $relationship, $ingest_file_location, $document, $transform) {
    global $user;
    parent::__construct("1.0", "UTF-8"); // DomDocument
    $this->formatOutput = TRUE;
    $this->label = $label;
    $this->user = $user->name;
    $this->pid = $pid;
    $this->dsid = $dsid;
    $this->contentModelPid = $content_model_pid;
    $this->collectionPid = $collection_pid;
    $this->relationship = isset($relationship) ? $relationship : 'isMemberOfCollection';
    $this->ingestFileLocation = $ingest_file_location;
    $this->document = $document;
    $this->tranform = $transform;
    $this->createDocument();
  }

  /**
   * 
   */
  private function createDocument() {
    $root = $this->createRootElement();
    $root = $this->appendChild($root);
    $children[] = $this->createObjectProperties();
    $children[] = $this->createRelationships();
    if (!empty($this->ingestFileLocation)) {
      $children = array_merge($children, $this->createIngestFileDatastreams());
    }
    $policy = $this->createPolicy();
    if (get_class($policy) == 'DOMElement') {
      $children[] = $policy;
    }
    $children[] = $this->createDocumentDatastream();
    $children[] = $this->createDublinCoreDatastream();
    $children[] = $this->createCollectionPolicy();
    $children[] = $this->createWorkflowStream();
    foreach ($children as $child) {
      if (isset($child)) {
        $root->appendChild($child);
      }
    }
  }

  /**
   * 
   */
  private function createRootElement() {
    $pid = $this->pid;
    $root = $this->createElementWithAttributes('foxml:digitalObject', array(
          'VERSION' => '1.1',
          'PID' => "{$this->pid}",
          'xmlns:foxml' => 'info:fedora/fedora-system:def/foxml#',
          'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
          'xsi:schemaLocation' => 'info:fedora/fedora-system:def/foxml# http://www.fedora.info/definitions/1/0/foxml1-1.xsd')
    );
    return $root;
  }

  /**
   *
   * @param string $name
   * @param array $attributes
   * @return DOMElement
   */
  private function &createElementWithAttributes($name, $attributes) {
    $element = $this->createElement($name);
    $this->setAttributes($element, $attributes);
    return $element;
  }

  /**
   *
   * @param DOMElement $element
   * @param array $attributes
   */
  private function setAttributes(&$element, &$attributes) {
    foreach ($attributes as $key => $val) {
      $element->setAttribute($key, $val);
    }
  }

  /**
   *
   * @return DOMElement
   */
  private function createObjectProperties() {
    $object_properties = $this->createElement('foxml:objectProperties');
    $properties[] = $this->createElementWithAttributes('foxml:property', array(
          "NAME" => "info:fedora/fedora-system:def/model#state",
          "VALUE" => "A")
    );
    $properties[] = $this->createElementWithAttributes('foxml:property', array(
          "NAME" => "info:fedora/fedora-system:def/model#label",
          "VALUE" => $this->label)
    );
    $properties[] = $this->createElementWithAttributes('foxml:property', array(
          "NAME" => "info:fedora/fedora-system:def/model#ownerId",
          "VALUE" => $this->user)
    );
    foreach ($properties as $property) {
      $object_properties->appendChild($property);
    }
    return $object_properties;
  }

  /**
   *
   * @return DOMElement
   */
  private function createRelationships() {
    $drdf = $this->createElementWithAttributes('foxml:datastream', array(
          "ID" => "RELS-EXT",
          "CONTROL_GROUP" => "X")
    );
    $dvrdf = $this->createElementWithAttributes('foxml:datastreamVersion', array(
          "FORMAT_URI" => "info:fedora/fedora-system:FedoraRELSExt-1.0",
          "ID" => "RELS-EXT.0",
          "MIMETYPE" => "application/rdf+xml",
          "LABEL" => "RDF Statements about this Object")
    );
    $rdf = $this->createElementWithAttributes('rdf:RDF', array(
          "xmlns:rdf" => "http://www.w3.org/1999/02/22-rdf-syntax-ns#",
          "xmlns:rdfs" => "http://www.w3.org/2000/01/rdf-schema#",
          "xmlns:fedora" => "info:fedora/fedora-system:def/relations-external#",
          "xmlns:dc" => "http://purl.org/dc/elements/1.1/",
          "xmlns:oai_dc" => "http://www.openarchives.org/OAI/2.0/oai_dc/",
          "xmlns:fedora-model" => "info:fedora/fedora-system:def/model#")
    );
    $rdfdesc = $this->createElementWithAttributes('rdf:Description', array(
          "rdf:about" => "info:fedora/{$this->pid}")
    );
    $member = $this->createElementWithAttributes("fedora:{$this->relationship}", array(
          "rdf:resource" => "info:fedora/{$this->collectionPid}")
    );
    $has_rdf_model = $this->createElementWithAttributes('fedora-model:hasModel', array(
          "rdf:resource" => "info:fedora/{$this->contentModelPid}")
    );
    $dvcontent = $this->createElement("foxml:xmlContent");
    $drdf->appendChild($dvrdf);
    $dvrdf->appendChild($dvcontent);
    $dvcontent->appendChild($rdf);
    $rdf->appendChild($rdfdesc);
    $rdfdesc->appendChild($member);
    $rdfdesc->appendChild($has_rdf_model);
    return $drdf;
  }

  /**
   *
   * @global string $base_url
   * @return array
   */
  private function createIngestFileDatastreams() {
    module_load_include('inc', 'fedora_repository', 'MimeClass');
    $datastreams = array();
    global $base_url;
    $mime = new MimeClass();
    $server = NULL;
    $file = $this->ingestFileLocation;
    if (!empty($file)) {
      $mime_type = $mime->getType($file);
      $parts = explode('/', $file);
      foreach ($parts as $n => $part) {
        $parts[$n] = rawurlencode($part);
      }
      $path = implode('/', $parts);
      $file_url = $base_url . '/' . $path;
      $begin_index = strrpos($file_url, '/');
      $dtitle = substr($file_url, $begin_index + 1);
      $dtitle = urldecode($dtitle);
      $datastream = $this->createElementWithAttributes('foxml:datastream', array(
            "ID" => "OBJ",
            "STATE" => "A",
            "CONTROL_GROUP" => "M")
      );
      $datastream_version = $this->createElementWithAttributes('foxml:datastreamVersion', array(
            "ID" => "OBJ.0",
            "MIMETYPE" => $mime_type,
            "LABEL" => $dtitle)
      );
      $content = $this->createElementWithAttributes('foxml:contentLocation', array(
            "REF" => $file_url,
            "TYPE" => "URL")
      );
      $datastream->appendChild($datastream_version);
      $datastream_version->appendChild($content);
      $datastreams[] = $datastream;
    }
    if (!empty($_SESSION['fedora_ingest_files'])) {
      foreach ($_SESSION['fedora_ingest_files'] as $dsid => $created_file) {
        if (!empty($file)) {
          $found = strstr($created_file, $file);
          if ($found !== FALSE) {
            $created_file = $found;
          }
        }
        $mime_type = $mime->getType($created_file);
        $parts = explode('/', $created_file);
        foreach ($parts as $n => $part) {
          $parts[$n] = rawurlencode($part);
        }
        $path = implode('/', $parts);
        $file_url = $base_url . '/' . $path;
        $beginIndex = strrpos($file_url, '/');
        $dtitle = substr($file_url, $beginIndex + 1);
        $dtitle = urldecode($dtitle);
        $datastream = $this->createElementWithAttributes('foxml:datastream', array(
              "ID" => $dsid,
              "STATE" => "A",
              "CONTROL_GROUP" => "M")
        );
        $datastream_version = $this->createElementWithAttributes('foxml:datastreamVersion', array(
                  "ID" => "$dsid.0",
                  "MIMETYPE" => "$mime_type",
                  "LABEL" => "$dtitle")
        );
        $content = $this->createElementWithAttributes('foxml:contentLocation', array(
              "REF" => "$file_url",
              "TYPE" => "URL")
        );
        $datastream->appendChild($datastream_version);
        $datastream_version->appendChild($content);
        $datastreams[] = $datastream;
      }
    }
    return $datastreams;
  }

  /**
   *
   * @return DOMElement
   */
  private function createPolicy() {
    $policy_element = $this->getPolicyStreamElement();
    if ($policy_element === FALSE || $policy_element === NULL) {
      return $policy_element;
    }
    $dsl = $this->createElementWithAttributes('foxml:datastream', array(
          "ID" => "POLICY",
          "STATE" => "A",
          "CONTROL_GROUP" => "X")
    );
    $ds1v = $ds1->appendChild($this->createElementWithAttributes('foxml:datastreamVersion', array(
              "ID" => "POLICY.0",
              "MIMETYPE" => "text/xml",
              "LABEL" => "POLICY")
            )
    );
    $content = $ds1v->appendChild($this->createElement("foxml:xmlContent"));
    $content->appendChild($policy_element);
    return $dsl;
  }

  /**
   *
   * @return DOMElement
   */
  private function getPolicyStreamElement() {
    module_load_include('inc', 'fedora_repository', 'ObjectHelper');
    $object_helper = new ObjectHelper();
    $policy_stream = $object_helper->getStream($this->collectionPid, 'CHILD_SECURITY', FALSE);
    if (!isset($policy_stream)) {
      return NULL; //there is no policy stream so object will not have a policy stream
    }
    try {
      $xml = new SimpleXMLElement($policy_stream);
    } catch (Exception $exception) {
      watchdog(t("Fedora_Repository"), t("Problem getting security policy."), NULL, WATCHDOG_ERROR);
      drupal_set_message(t('Problem getting security policy: !e', array('!e' => $exception->getMessage())), 'error');
      return FALSE;
    }
    $policy_element = $this->createDocumentFragment();
    if (!$policy_element) {
      drupal_set_message(t('Error parsing security policy stream.'));
      watchdog(t("Fedora_Repository"), t("Error parsing security policy stream, could not parse policy stream."), NULL, WATCHDOG_NOTICE);
      return FALSE;
    }
    $this->importNode($policy_element, TRUE);
    $value = $policy_element->appendXML($policy_stream);
    if (!$value) {
      drupal_set_message(t('Error creating security policy stream.'));
      watchdog(t("Fedora_Repository"), t("Error creating security policy stream, could not parse collection policy template file."), NULL, WATCHDOG_NOTICE);
      return FALSE;
    }
    return $policy_element;
  }

  /**
   * Creates Collection policy data stream from a template stored within the
   * Content Model.
   *
   * @return DOMElement
   */
  private function createCollectionPolicy() {
    module_load_include('inc', 'fedora_repository', 'api/fedora_item');
    $fedora_item = new fedora_item($this->contentModelPid);
    $datastreams = $fedora_item->get_datastreams_list_as_array();
    if (isset($datastreams['COLLECTION_POLICY_TMPL'])) {
      $collection_policy_template = $fedora_item->get_datastream_dissemination('COLLECTION_POLICY_TMPL');
      $collection_policy_template_dom = DOMDocument::loadXML($collection_policy_template);
      $collection_policy_template_root = $collection_policy_template_dom->getElementsByTagName('collection_policy');
      if ($collection_policy_template_root->length > 0) {
        $collection_policy_template_root = $collection_policy_template_root->item(0);
        $new_node = $this->importNode($collection_policy_template_root, TRUE);
        $attributes = array('ID' => 'COLLECTION_POLICY', 'STATE' => 'A', 'CONTROL_GROUP' => 'X');
        $version_attributes = array('ID' => 'COLLECTION_POLICY.0', 'LABEL' => 'Collection Policy', 'MIMETYPE' => 'text/xml');
        list($datastream, $content) = $this->createXMLDatastream($attributes, $version_attributes);
        $content->appendChild($new_node);
        return $datastream;
      }
    }
  }

  /**
   *
   * @param array $attributes
   * @param array $version_attributes
   * @return array
   */
  private function createXMLDatastream($attributes, $version_attributes) {
    $datastream = $this->createElementWithAttributes("foxml:datastream", $attributes);
    $version = $this->createElementWithAttributes('foxml:datastreamVersion', $version_attributes);
    $datastream->appendChild($version);
    $content = $this->createElement("foxml:xmlContent");
    $version->appendChild($content);
    return array(&$datastream, &$content);
  }

  /**
   * Creates WorkFlow datastream from a template stored within the Content Model.
   *
   * @return DOMElement
   */
  private function createWorkflowStream() {
    module_load_include('inc', 'fedora_repository', 'api/fedora_item');
    $fedora_item = new fedora_item($this->contentModelPid);
    $datastreams = $fedora_item->get_datastreams_list_as_array();
    if (isset($datastreams['WORKFLOW_TMPL'])) {
      $work_flow_template = $fedora_item->get_datastream_dissemination('WORKFLOW_TMPL');
      $work_flow_template_dom = DOMDocument::loadXML($work_flow_template);
      $work_flow_template_root = $work_flow_template_dom->getElementsByTagName('workflow');
      if ($work_flow_template_root->length > 0) {
        $work_flow_template_root = $work_flow_template_root->item(0);
        $new_node = $this->importNode($work_flow_template_root, TRUE);
        $attributes = array('ID' => 'WORKFLOW', 'STATE' => 'A', 'CONTROL_GROUP' => 'X');
        $version_attributes = array('ID' => "{$this->dsid}.0", 'LABEL' => "{$this->dsid} Record", 'MIMETYPE' => 'text/xml');
        list($datastream, $content) = $this->createXMLDatastream($this, $attributes, $version_attributes);
        $content->appendChild($new_node);
        return $datastream;
      }
    }
  }

  /**
   * Creates an xml based datastream by importing the root node from $this->document.
   *
   * @return DOMElement
   */
  public function createDocumentDatastream() {
    $attributes = array('ID' => $this->dsid, 'STATE' => 'A', 'CONTROL_GROUP' => 'X');
    $version_attributes = array('ID' => "{$this->dsid}.0", 'LABEL' => "{$this->dsid} Record", 'MIMETYPE' => 'text/xml');
    list($datastream, $content) = $this->createXMLDatastream($attributes, $version_attributes);
    $node = $this->importNode($this->document->documentElement, TRUE);
    $content->appendChild($node);
    return $datastream;
  }

  /**
   * Creates a Dublin Core document by transforming $this->document.
   *
   * @return DOMElement
   */
  private function createDublinCoreDatastream() {
    $attributes = array('ID' => 'DC', 'STATE' => 'A', 'CONTROL_GROUP' => 'X');
    $version_attributes = array('ID' => 'DC.0', 'LABEL' => 'Dublin Core Record', 'MIMETYPE' => 'text/xml');
    list($datastream, $content) = $this->createXMLDatastream($attributes, $version_attributes);
    $dublin_core = $this->applyTransformation();
    $content->appendChild($dublin_core);
    return $datastream;
  }

  /**
   * Transforms a document via an XSL.
   *
   * @return DOMElement
   *   A DOMDocumentFragment that contains the transformed Dublin Core
   *   Document.
   */
  private function applyTransformation() {
    $xsl = new DOMDocument();
    $xsl->load($this->tranform);
    $xslt = new XSLTProcessor();
    $xslt->importStyleSheet($xsl);
    $transformed_document = $xslt->transformToDoc($this->document);
    return $this->importNode($transformed_document->documentElement);
  }

  /**
   * Ingests this Foxml document into fedora.
   */
  public function ingest() {
    $ret = FALSE;
    try {
      $object = Fedora_Item::ingest_from_FOXML($this);
      if (!empty($object->pid)) {
        drupal_set_message(t("Item !pid created successfully.", array('!pid' => l($object->pid, 'fedora/repository/' . $object->pid))), "status");
        $ret = TRUE;
      }
      if (!empty($_SESSION['fedora_ingest_files'])) {
        foreach ($_SESSION['fedora_ingest_files'] as $dsid => $created_file) {
          file_delete($created_file);
        }
      }
      file_delete($this->ingestFileLocation);
    } catch (exception $exception) {
      drupal_set_message(t('Error ingesting object: !e', array('!e' => $exception->getMessage())), 'error');
      watchdog(t("Fedora_Repository"), t("Error ingesting object: !e", array('!e' => $exception->getMessage())), NULL, WATCHDOG_ERROR);
    }
    return $ret;
  }

}