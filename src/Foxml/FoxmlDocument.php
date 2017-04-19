<?php

namespace Islandora\Tuque\Foxml;

use DOMDocument;
use Islandora\Tuque\Exception\RepositoryXmlError;
use Islandora\Tuque\Object\NewFedoraObject;

class FoxmlDocument extends DOMDocument
{
    const FOXML = 'info:fedora/fedora-system:def/foxml#';
    const XLINK = 'http://www.w3.org/1999/xlink';
    const XSI = 'http://www.w3.org/2001/XMLSchema-instance';
    const XMLNS = 'http://www.w3.org/2000/xmlns/';
    const RDF = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
    const RFDS = 'http://www.w3.org/2000/01/rdf-schema#';
    const FEDORA = 'info:fedora/fedora-system:def/relations-external#';
    const DC = 'http://purl.org/dc/elements/1.1/';
    const OAI_DC = 'http://www.openarchives.org/OAI/2.0/oai_dc/';
    const FEDORA_MODEL = 'info:fedora/fedora-system:def/model#';

    protected $root;
    protected $object;

    public function __construct(NewFedoraObject $object)
    {
        parent::__construct("1.0", "UTF-8"); // DomDocument
        $this->formatOutput = true;
        $this->preserveWhiteSpace = false;
        $this->object = $object;
        $this->root = $this->createRootElement();
        $this->createDocument();
    }

    private function createRootElement()
    {
        $root = $this->createElementNS(self::FOXML, 'foxml:digitalObject');
        $root->setAttribute('VERSION', '1.1');
        $root->setAttribute('PID', "{$this->object->id}");
        $root->setAttributeNS(self::XMLNS, 'xmlns', self::FOXML);
        $root->setAttributeNS(self::XMLNS, 'xmlns:foxml', self::FOXML);
        $root->setAttributeNS(self::XMLNS, 'xmlns:xsi', self::XSI);
        $root->setAttributeNS(
            self::XSI,
            'xsi:schemaLocation',
            self::FOXML . " http://www.fedora.info/definitions/1/0/foxml1-1.xsd"
        );
        $this->appendChild($root);
        return $root;
    }

    private function createDocument()
    {
        /**
         * If DOMNodes are not appended in the corrected order root -> leaf, namespaces may break...
         * So be be cautious, add DOMNodes to their parent element before adding child elements to them.
         */
        $this->createObjectProperties();
        $this->createDocumentDatastreams();
    }

    private function createObjectProperties()
    {
        $object_properties = $this->createElementNS(
            self::FOXML,
            'foxml:objectProperties'
        );
        $this->root->appendChild($object_properties);

        $property = $this->createElementNS(
            self::FOXML,
            'foxml:property'
        );

        $property->setAttribute(
            'NAME',
            'info:fedora/fedora-system:def/model#state'
        );
        $property->setAttribute('VALUE', $this->object->state);
        $object_properties->appendChild($property);

        $property = $this->createElementNS(self::FOXML, 'foxml:property');
        $property->setAttribute(
            'NAME',
            'info:fedora/fedora-system:def/model#label'
        );
        $property->setAttribute('VALUE', $this->object->label);
        $object_properties->appendChild($property);

        if (isset($this->object->owner)) {
            $property = $this->createElementNS(self::FOXML, 'foxml:property');
            $property->setAttribute(
                'NAME',
                'info:fedora/fedora-system:def/model#ownerId'
            );
            $property->setAttribute('VALUE', $this->object->owner);
            $object_properties->appendChild($property);
        }

        return $object_properties;
    }

    private function createDatastreamElement(
        $id = null,
        $state = null,
        $control_group = null,
        $versionable = null
    ) {
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
        if (isset($versionable)) {
            $datastream->setAttribute(
                'VERSIONABLE',
                $versionable ? 'true' : 'false'
            );
        }
        return $datastream;
    }

    private function createDatastreamVersionElement(
        $id = null,
        $label = null,
        $mime_type = null,
        $format_uri = null
    ) {
        $version = $this->createElementNS(
            self::FOXML,
            'foxml:datastreamVersion'
        );
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

    private function createDatastreamDigestElement(
        $type = null,
        $checksum = null
    ) {
        $digest = $this->createElementNS(self::FOXML, 'foxml:contentDigest');
        if (isset($type)) {
            $digest->setAttribute('TYPE', $type);
        }
        if (isset($digest)) {
            $digest->setAttribute('DIGEST', $checksum);
        }
        return $digest;
    }

    private function createDatastreamContentElement()
    {
        $content = $this->createElementNS(self::FOXML, 'foxml:xmlContent');
        return $content;
    }

    private function createDatastreamContentLocationElement(
        $type = null,
        $ref = null
    ) {
        $location = $this->createElementNS(
            self::FOXML,
            'foxml:contentLocation'
        );
        if (isset($type)) {
            $location->setAttribute('TYPE', $type);
        }
        if (isset($ref)) {
            $location->setAttribute('REF', $ref);
        }
        return $location;
    }

    /**
     * Passes each datastream to the appropriate ds create function.
     */
    public function createDocumentDatastreams()
    {
        foreach ($this->object as $ds) {
            switch ($ds->controlGroup) {
                case 'X':
                    $this->createInlineDocumentDatastream($ds);
                    break;

                default:
                    $this->createDocumentDatastream($ds);
                    break;
            }
        }
    }

    /**
     * Creates FOXML for any inline datastreams based on the information passed in the $ds object.
     *
     * @param \Islandora\Tuque\Datastream\AbstractDatastream $ds
     *   The datastream object
     *
     * @throws RepositoryXmlError
     */
    private function createInlineDocumentDatastream($ds)
    {
        $datastream = $this->createDatastreamElement(
            $ds->id,
            $ds->state,
            $ds->controlGroup,
            $ds->versionable
        );
        $version = $this->createDatastreamVersionElement(
            "{$ds->id}.0",
            $ds->label,
            $ds->mimetype,
            $ds->format
        );
        $content = $this->createDatastreamContentElement();
        $xml_dom = new DOMDocument();
        if (!$xml_dom->loadXML($ds->content)) {
            throw new RepositoryXmlError(
                "{$ds->id} on {$ds->parent->id} contains invalid XML",
                null,
                null
            );
        }
        $child = $this->importNode($xml_dom->documentElement, true);
        $version_node = $this->root->appendChild($datastream)
        ->appendChild($version);
        if (isset($ds->checksumType)) {
            $digest = $this->createDatastreamDigestElement(
                $ds->checksumType,
                $ds->checksum
            );
            $version_node->appendChild($digest);
        }
        $version_node->appendChild($content)->appendChild($child);
        $simple_dom = simplexml_import_dom($xml_dom);
        $namespaces = $simple_dom->getDocNamespaces(true);
        foreach ($namespaces as $prefix => $uri) {
            if ($prefix) {
                $child->setAttributeNS(self::XMLNS, "xmlns:$prefix", $uri);
            }
        }
    }

    /**
     * Creates FOXML for any managed, externally referenced or redirect
     * datastreams bases on the $ds object
     *
     * @param \Islandora\Tuque\Datastream\AbstractDatastream $ds
     *   The datastream object
     */
    private function createDocumentDatastream($ds)
    {
        $datastream = $this->createDatastreamElement(
            $ds->id,
            $ds->state,
            $ds->controlGroup,
            $ds->versionable
        );
        $version = $this->createDatastreamVersionElement(
            $ds->id . '.0',
            $ds->label,
            $ds->mimetype,
            $ds->format
        );
        $content = $this->createDatastreamContentLocationElement(
            'URL',
            $ds->content
        );
        $version_node = $this
            ->root
            ->appendChild($datastream)
            ->appendChild($version);
        if (isset($ds->checksumType)) {
            $digest = $this->createDatastreamDigestElement(
                $ds->checksumType,
                $ds->checksum
            );
            $version_node->appendChild($digest);
        }
        $version_node->appendChild($content);
    }
}
