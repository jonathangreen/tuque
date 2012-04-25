<?php

class FoxmlDocument extends DOMDocument {
  const FOXML = 'info:fedora/fedora-system:def/foxml#';
  const xlink = 'http://www.w3.org/1999/xlink';
  const xsi = 'http://www.w3.org/2001/XMLSchema-instance';
  const xmlns = 'http://www.w3.org/2000/xmlns/';
  const rdf = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
  const rdfs = 'http://www.w3.org/2000/01/rdf-schema#';
  const fedora = 'info:fedora/fedora-system:def/relations-external#';
  const dc = 'http://purl.org/dc/elements/1.1/';
  const oai_dc = 'http://www.openarchives.org/OAI/2.0/oai_dc/';
  const fedora_model = 'info:fedora/fedora-system:def/model#';

  protected $root;
  protected $object;

  public function __construct(NewFedoraObject $object) {
    parent::__construct("1.0", "UTF-8"); // DomDocument
    $this->formatOutput = TRUE;
    $this->preserveWhiteSpace = FALSE;
    $this->object = $object;
    $this->root = $this->createRootElement();
    $this->createDocument();
  }

  private function createRootElement() {
    $root = $this->createElementNS(self::FOXML, 'foxml:digitalObject');
    $root->setAttribute('VERSION', '1.1');
    $root->setAttribute('PID', "{$this->object->id}");
    $root->setAttributeNS(self::xmlns, 'xmlns', self::FOXML);
    $root->setAttributeNS(self::xmlns, 'xmlns:foxml', self::FOXML);
    $root->setAttributeNS(self::xmlns, 'xmlns:xsi', self::xsi);
    $root->setAttributeNS(self::xsi, 'xsi:schemaLocation', self::FOXML . " http://www.fedora.info/definitions/1/0/foxml1-1.xsd");
    $this->appendChild($root);
    return $root;
  }

  private function createDocument() {
    /**
     * If DOMNodes are not appended in the corrected order root -> leaf, namespaces may break...
     * So be be cautious, add DOMNode's to thier parent element before adding child elements to them.
     */
    $this->createObjectProperties();
    //$this->createRelationships();
    //$this->createIngestFileDatastreams();
    //$this->createPolicy();
    //$this->createDocumentDatastream();
    //$this->createDublinCoreDatastream();
    //$this->createCollectionPolicy();
    //$this->createWorkflowStream();
  }

  /**
   *
   * @param DOMElement $root
   * @return DOMElement
   */
  private function createObjectProperties() {
    $object_properties = $this->createElementNS(self::FOXML, 'foxml:objectProperties');
    $this->root->appendChild($object_properties);

    $property = $this->createElementNS(self::FOXML, 'foxml:property');
    $property->setAttribute('NAME', 'info:fedora/fedora-system:def/model#state');
    $property->setAttribute('VALUE', $this->object->state);
    $object_properties->appendChild($property);

    $property = $this->createElementNS(self::FOXML, 'foxml:property');
    $property->setAttribute('NAME', 'info:fedora/fedora-system:def/model#label');
    $property->setAttribute('VALUE', $this->object->label);
    $object_properties->appendChild($property);

    if(isset($this->object->owner)) {
      $property = $this->createElementNS(self::FOXML, 'foxml:property');
      $property->setAttribute('NAME', 'info:fedora/fedora-system:def/model#ownerId');
      $property->setAttribute('VALUE', $this->object->owner);
      $object_properties->appendChild($property);
    }

    return $object_properties;
  }

  /*
   * @todo clean this up. we need relationships, but not yet.
  private function createRelationships() {
    $datastream = $this->createDatastreamElement('RELS-EXT', NULL, 'X');
    $version = $this->createDatastreamVersionElement('RELS-EXT.0', 'RDF Statements about this Object', 'application/rdf+xml', 'info:fedora/fedora-system:FedoraRELSExt-1.0');
    $content = $this->createDatastreamContentElement();

    $rdf = $this->createElementNS(self::rdf, 'rdf:RDF');
    $rdf->setAttributeNS(self::xmlns, 'xmlns:rdf', self::rdf);
    $rdf->setAttributeNS(self::xmlns, 'xmlns:rdfs', self::rdfs);
    $rdf->setAttributeNS(self::xmlns, 'xmlns:fedora', self::fedora);
    $rdf->setAttributeNS(self::xmlns, 'xmlns:dc', self::dc);
    $rdf->setAttributeNS(self::xmlns, 'xmlns:oai_dc', self::oai_dc);
    $rdf->setAttributeNS(self::xmlns, 'xmlns:fedora-model', self::fedora_model);

    $rdf_description = $this->createElementNS(self::rdf, 'rdf:Description');
    $rdf_description->setAttributeNS(self::rdf, 'rdf:about', "info:fedora/{$this->pid}");

    $member = $this->createElementNS(self::fedora, "fedora:{$this->relationship}");
    $member->setAttributeNS(self::rdf, 'rdf:resource', "info:fedora/{$this->collectionPid}");

    $has_rdf_model = $this->createElementNS(self::fedora_model, 'fedora-model:hasModel');
    $has_rdf_model->setAttributeNS(self::rdf, "rdf:resource", "info:fedora/{$this->contentModelPid}");

    $this->root->appendChild($datastream)->appendChild($version)->appendChild($content);
    $content->appendChild($rdf)->appendChild($rdf_description);
    $rdf_description->appendChild($member);
    $rdf_description->appendChild($has_rdf_model);
  }

  private function createIngestFileDatastreams() {
    if (empty($this->ingestFileLocation))
      return;

    list($label, $mime_type, $file_url) = $this->getFileAttributes($this->ingestFileLocation);
    $datastream = $this->createDatastreamElement('OBJ', 'A', 'M');
    $version = $this->createDatastreamVersionElement('OBJ.0', $label, $mime_type);
    $content = $this->createDatastreamContentLocationElement('URL', $file_url);
    $this->root->appendChild($datastream)->appendChild($version)->appendChild($content);

    if (!empty($_SESSION['fedora_ingest_files'])) {
      foreach ($_SESSION['fedora_ingest_files'] as $dsid => $created_file) {
        if (!empty($this->ingestFileLocation)) {
          $found = strstr($created_file, $this->ingestFileLocation);
          if ($found !== FALSE) {
            $created_file = $found;
          }
        }
        list($label, $mime_type, $file_url) = $this->getFileAttributes($created_file);
        $datastream = $this->createDatastreamElement($dsid, 'A', 'M');
        $version = $this->createDatastreamVersionElement("$dsid.0", $label, $mime_type);
        $content = $this->createDatastreamContentLocationElement('URL', $file_url);
        $this->root->appendChild($datastream)->appendChild($version)->appendChild($content);
      }
    }
  }

  private function getFileAttributes($file) {
    global $base_url;
    module_load_include('inc', 'fedora_repository', 'MimeClass');
    $mime = new MimeClass();
    $mime_type = $mime->getType($file);
    $parts = explode('/', $file);
    foreach ($parts as $n => $part) {
      $parts[$n] = rawurlencode($part);
    }
    $path = implode('/', $parts);
    $file_url = $base_url . '/' . $path;
    $beginIndex = strrpos($file_url, '/');
    $dtitle = substr($file_url, $beginIndex + 1);
    $dtitle = urldecode($dtitle);
    return array($dtitle, $mime_type, $file_url);
  }

  private function createPolicy() {
    $policy_element = $this->getPolicyStreamElement();
    if ($policy_element) {
      $datastream = $this->createDatastreamElement('POLICY', 'A', 'X');
      $version = $this->createDatastreamVersionElement('POLICY.0', 'POLICY', 'text/xml');
      $content = $this->createDatastreamContentElement();
      $this->root->appendChild($datastream)->appendChild($version)->appendChild($content)->appendChild($policy_element);
    }
  }

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
        $node = $this->importNode($collection_policy_template_root, TRUE);
        $datastream = $this->createDatastreamElement('COLLECTION_POLICY', 'A', 'X');
        $version = $this->createDatastreamVersionElement('COLLECTION_POLICY.0', 'Collection Policy', 'text/xml');
        $content = $this->createDatastreamContentElement();
        $this->root->appendChild($datastream)->appendChild($version)->appendChild($content)->appendChild($node);
      }
    }
  }

  private function createDatastreamElement($id = NULL, $state = NULL, $control_group = NULL) {
    $datastream = $this->createElementNS(self::FOXML, 'foxml:datastream');
    if (isset($id)) {
      $datastream->setAttribute('ID', $id);
    }
    if (isset($state)) {
      $datastream->setAttribute('STATE', $state);
    }
    if (isset($control_group)) {
      $datastream->setAttribute('CONTROL_GROUP', $control_group);
    }
    return $datastream;
  }

  private function createDatastreamVersionElement($id = NULL, $label = NULL, $mime_type = NULL, $format_uri = NULL) {
    $version = $this->createElementNS(self::FOXML, 'foxml:datastreamVersion');
    if (isset($id)) {
      $version->setAttribute('ID', $id);
    }
    if (isset($label)) {
      $version->setAttribute('LABEL', $label);
    }
    if (isset($mime_type)) {
      $version->setAttribute('MIMETYPE', $mime_type);
    }
    if (isset($format_uri)) {
      $version->setAttribute('FORMAT_URI', $format_uri);
    }
    return $version;
  }

  private function createDatastreamContentElement() {
    $content = $this->createElementNS(self::FOXML, 'foxml:xmlContent');
    return $content;
  }

  private function createDatastreamContentLocationElement($type = NULL, $ref = NULL) {
    $location = $this->createElementNS(self::FOXML, 'foxml:contentLocation');
    if (isset($type)) {
      $location->setAttribute('TYPE', $type);
    }
    if (isset($ref)) {
      $location->setAttribute('REF', $ref);
    }
    return $location;
  }

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
        $node = $this->importNode($work_flow_template_root, TRUE);
        $datastream = $this->createDatastreamElement('WORKFLOW', 'A', 'X');
        $version = $this->createDatastreamVersionElement("{$this->dsid}.0", "{$this->dsid} Record", 'text/xml');
        $content = $this->createDatastreamContentElement();
        $this->root->appendChild($datastream)->appendChild($version)->appendChild($content)->appendChild($node);
      }
    }
  }

  public function createDocumentDatastream() {
    $datastream = $this->createDatastreamElement($this->dsid, 'A', 'X');
    $version = $this->createDatastreamVersionElement("{$this->dsid}.0", "{$this->dsid} Record", 'text/xml');
    $content = $this->createDatastreamContentElement();
    $node = $this->importNode($this->document->documentElement, TRUE);
    $this->root->appendChild($datastream)->appendChild($version)->appendChild($content)->appendChild($node);
    // Once again god damn you libxml...
    $class = get_class($this->document);
    $namespaces = call_user_func(array($class, 'getRequiredNamespaces'));
    foreach($namespaces as $prefix => $uri) {
      $node->setAttributeNS(self::xmlns, "xmlns:$prefix", $uri);
    }
  }

  private function createDublinCoreDatastream() {
    $datastream = $this->createDatastreamElement('DC', 'A', 'X');
    $version = $this->createDatastreamVersionElement('DC.0', 'Dublic Core Record', 'text/xml');
    $content = $this->createDatastreamContentElement();
    $dublin_core = $this->applyTransformation();
    $this->root->appendChild($datastream)->appendChild($version)->appendChild($content)->appendChild($dublin_core);
    $dublin_core->setAttributeNS(self::xmlns, 'xmlns:xsi', self::xsi); // GOD Damn you libxml!
  }


  private function applyTransformation() {
    $xsl = new DOMDocument();
    $xsl->load($this->transform);
    $xslt = new XSLTProcessor();
    $xslt->importStyleSheet($xsl);
    $document = new DOMDocument();
    $document->loadXML($this->document->saveXML());
    $document = $xslt->transformToDoc($document->documentElement);
    return $this->importNode($document->documentElement, TRUE);
  }

 */

}