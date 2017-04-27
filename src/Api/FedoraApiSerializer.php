<?php

namespace Islandora\Tuque\Api;

use Islandora\Tuque\Exception\RepositoryXmlError;
use SimpleXMLElement;
use DOMDocument;
use DOMXPath;
use DOMElement;

/**
 * A class to Serialize the XML responses from Fedora into PHP arrays.
 */
class FedoraApiSerializer
{

    const RDF_NAMESPACE = "http://www.w3.org/1999/02/22-rdf-syntax-ns#";

    /**
     * Simple function that takes an XML string and returns a SimpleXml object.
     * It makes sure no PHP errors or warnings are issued and instead throws an
     * exception if the XML parse failed.
     *
     * @param string $xml
     *   The XML as a string
     *
     * @throws RepositoryXmlError
     *
     * @return SimpleXmlElement
     *   Return an instantiated xml object
     */
    protected function loadSimpleXml($xml)
    {
        // We use the shutup operator so that we don't get a warning as well
        // as throwing an exception.
        $simplexml = @simplexml_load_string($xml);
        if ($simplexml === false) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            throw new RepositoryXmlError(
                'Failed to parse XML response from Fedora.',
                0,
                $errors
            );
        }
        return $simplexml;
    }

    /**
     * This is a simple exception handler, that will throw an exception if there
     * is a problem parsing XML from Fedora. This is nice so we can catch it.
     *
     * @param int $errno
     *   The error number
     * @param string $errstr
     *   String describing an error.
     * @param string $errfile
     *   (optional) The third parameter is optional, errfile, which contains the
     *   filename that the error was raised in, as a string.
     * @param int $errline
     *   (optional) The fourth parameter is optional, errline, which contains the
     *   line number the error was raised at, as an integer.
     * @param array $errcontext
     *   (optional) The fifth parameter is optional, errcontext, which is an
     *   array that points to the active symbol table at the point the error
     *   occurred. In other words, errcontext will contain an array of every
     *   variable that existed in the scope the error was triggered in. User
     *   error handler must not modify error context.
     *
     * @throws RepositoryXmlError
     *
     * @return boolean
     *   TRUE if we through an exception FALSE otherwise
     *
     * @see php.net/manual/en/function.set-error-handler.php
     */
    public function domDocumentExceptionHandler(
        $errno,
        $errstr,
        $errfile = '',
        $errline = null,
        $errcontext = null
    ) {
        if ($errno == E_WARNING &&
            strpos($errstr, "DOMDocument::loadXML()") !== false
        ) {
            throw new RepositoryXmlError(
                "Failed to parse XML response from Fedora.",
                0,
                $errstr
            );
        }

        return false;
    }

    /**
     * Simple function that takes an XML string and returns a DomDocument object.
     * It makes sure no PHP errors or warnings are issued and instead throws an
     * exception if the XML parse failed.
     *
     * @param string $xml
     *   The XML as a string
     *
     * @throws RepositoryXmlError
     *
     * @return DomDocument
     *   Return an istantiated DomDocument
     */
    protected function loadDomDocument($xml)
    {
        set_error_handler([$this, 'domDocumentExceptionHandler']);
        $dom = new DOMDocument();
        $dom->loadXml($xml);
        restore_error_handler();
        return $dom;
    }

    /**
     * Flatten a simplexml object returning an array containing the text from
     * the XML. This is often used to get data back from fedora. It also makes
     * sure to cast everything to string.
     *
     * @param SimpleXmlElement|SimpleXMLElement[] $xml
     *   The SimpleXml element to be processed.
     * @param array $make_array
     *   (optional) This parameter specifies tags that should become an array
     *   instead of an element in an array. This is used to get consistant values
     *   for things that are multivalued when there is only one value returned.
     *
     * @return array|string
     *   An array representation of the XML.
     */
    protected function flattenDocument($xml, $make_array = [])
    {
        if (!is_object($xml)) {
            return '';
        }

        if ($xml->count() == 0) {
            return (string)$xml;
        }

        $initialized = [];
        $return = [];

        foreach ($xml->children() as $name => $child) {
            $value = $this->flattenDocument($child, $make_array);

            if (in_array($name, $make_array)) {
                $return[] = $value;
            } elseif (isset($return[$name])) {
                if (isset($initialized[$name])) {
                    $return[$name][] = $value;
                } else {
                    $tmp = $return[$name];
                    $return[$name] = [];
                    $return[$name][] = $tmp;
                    $return[$name][] = $value;
                    $initialized[$name] = true;
                }
            } else {
                $return[$name] = $value;
            }
        }

        return $return;
    }

    /**
     * Serializes the data returned in FedoraApiA::describeRepository()
     *
     * @param array $request
     * @return array
     */
    public function describeRepository($request)
    {
        $repository = $this->loadSimpleXml((string) $request->getBody());
        $data = $this->flattenDocument($repository);
        return $data;
    }

    /**
     * Serializes the data returned in FedoraApiA::userAttributes()
     *
     * @param array $request
     * @return array
     */
    public function userAttributes($request)
    {
        $user_attributes = $this->loadSimpleXml((string) $request->getBody());
        $data = [];
        foreach ($user_attributes->attribute as $attribute) {
            $values = [];
            foreach ($attribute->value as $value) {
                array_push($values, (string)$value);
            }
            $data[(string)$attribute['name']] = $values;
        }
        return $data;
    }

    /**
     * Serializes the data returned in FedoraApiA::findObjects()
     *
     * @param array $request
     * @return array
     */
    public function findObjects($request)
    {
        $results = $this->loadSimpleXml((string) $request->getBody());
        $data = [];

        if (isset($results->listSession)) {
            $data['session'] = $this->flattenDocument($results->listSession);
        }
        if (isset($results->resultList)) {
            $data['results'] = $this->flattenDocument(
                $results->resultList,
                ['objectFields']
            );
        }

        return $data;
    }

    /**
     * Serializes the data returned in FedoraApiA::resumeFindObjects()
     *
     * @param array $request
     * @return array
     */
    public function resumeFindObjects($request)
    {
        return $this->findObjects($request);
    }

    /**
     * Serializes the data returned in FedoraApiA::getDatastreamDissemination()
     *
     * @param array $request
     * @param string|null $file
     *
     * @return string|bool
     */
    public function getDatastreamDissemination($request, $file)
    {
        if ($file) {
            return true;
        } else {
            return (string) $request->getBody();
        }
    }

    /**
     * Serializes the data returned in FedoraApiA::getDissemination()
     *
     * @param array $request
     * @return array
     */
    public function getDissemination($request)
    {
        return (string) $request->getBody();
    }

    /**
     * Serializes the data returned in FedoraApiA::getObjectHistory()
     *
     * @param array $request
     * @return array
     */
    public function getObjectHistory($request)
    {
        $object_history = $this->loadSimpleXml((string) $request->getBody());
        $data = $this->flattenDocument(
            $object_history,
            ['objectChangeDate']
        );
        return $data;
    }

    /**
     * Serializes the data returned in FedoraApiA::getObjectProfile()
     *
     * @param array $request
     * @return array
     */
    public function getObjectProfile($request)
    {
        $result = $this->loadSimpleXml((string) $request->getBody());
        $data = $this->flattenDocument($result, ['model']);
        return $data;
    }

    /**
     * Serializes the data returned in FedoraApiA::listDatastreams()
     *
     * @param array $request
     * @return array
     */
    public function listDatastreams($request)
    {
        $result = [];
        $datastreams = $this->loadSimpleXml((string) $request->getBody());
        // We can't use flattenDocument here, since everything is an attribute.
        foreach ($datastreams->datastream as $datastream) {
            $result[(string)$datastream['dsid']] = [
                'label' => (string)$datastream['label'],
                'mimetype' => (string)$datastream['mimeType'],
            ];
        }
        return $result;
    }

    /**
     * Serializes the data returned in FedoraApiA::listMethods()
     *
     * @param array $request
     * @return array
     */
    public function listMethods($request)
    {
        $result = [];
        $object_methods = $this->loadSimpleXml((string) $request->getBody());
        // We can't use flattenDocument here because of the atrtibutes.
        if (isset($object_methods->sDef)) {
            foreach ($object_methods->sDef as $sdef) {
                $methods = [];
                if (isset($sdef->method)) {
                    foreach ($sdef->method as $method) {
                        $methods[] = (string)$method['name'];
                    }
                }
                $result[(string)$sdef['pid']] = $methods;
            }
        }
        return $result;
    }

    /**
     * Serializes the data returned in FedoraApiM::addDatastream()
     *
     * @param array $request
     * @return array
     */
    public function addDatastream($request)
    {
        return $this->getDatastream($request);
    }

    /**
     * Serializes the data returned in FedoraApiM::addRelationship()
     *
     * @param array $request
     * @return bool
     */
    public function addRelationship($request)
    {
        return true;
    }

    /**
     * Serializes the data returned in FedoraApiM::export()
     *
     * @param array $request
     * @param string|null $file
     *
     * @return string|bool
     */
    public function export($request, $file = null)
    {
        return $file ?
            true :
            (string) $request->getBody();
    }

    /**
     * Serializes the data returned in FedoraApiM::getDatastream()
     *
     * @param array $request
     *
     * @return array
     */
    public function getDatastream($request)
    {
        $result = $this->loadSimpleXml((string) $request->getBody());
        $data = $this->flattenDocument($result);
        return $data;
    }

    /**
     * Serializes the data returned in FedoraApiM::getDatastreamHistory()
     *
     * @param array $request
     * @return array
     */
    public function getDatastreamHistory($request)
    {
        $result = $this->loadSimpleXml((string) $request->getBody());
        $result = $this->flattenDocument($result, ['datastreamProfile']);

        return $result;
    }

    /**
     * Serializes the data returned in FedoraApiM::getNextPid()
     *
     * @param array $request
     * @return array
     */
    public function getNextPid($request)
    {
        $result = $this->loadSimpleXml((string) $request->getBody());
        $result = $this->flattenDocument($result);
        $result = $result['pid'];

        return $result;
    }

    /**
     * Serializes the data returned in FedoraApiM::getObjectXml()
     *
     * @param array $request
     * @param string|null $file
     *
     * @return string|bool
     */
    public function getObjectXml($request, $file = null)
    {
        return $file ?
            true :
            (string) $request->getBody();
    }

    /**
     * Seralizes the element component of the relationship.
     *
     * @param DOMElement $element
     * @return array
     */
    protected function getRelationship($element)
    {
        $relationship = [];
        $parent = $element->parentNode;

        // Remove the 'info:fedora/' from the subject.
        $subject = $parent->getAttributeNS(RDF_NAMESPACE, 'about');
        $subject = explode('/', $subject);
        unset($subject[0]);
        $subject = implode('/', $subject);
        $relationship['subject'] = $subject;

        // This section parses the predicate.
        $predicate = explode(':', $element->tagName);
        $predicate = count($predicate) == 1 ? $predicate[0] : $predicate[1];
        $predicate = ['predicate' => $predicate];
        $predicate['uri'] = $element->namespaceURI;
        $predicate['alias'] = $element->lookupPrefix($predicate['uri']);
        $relationship['predicate'] = $predicate;

        // This section parses the object.
        if ($element->hasAttributeNS(RDF_NAMESPACE, 'resource')) {
            $attribute = $element->getAttributeNS(RDF_NAMESPACE, 'resource');
            $attribute = explode('/', $attribute);
            unset($attribute[0]);
            $attribute = implode('/', $attribute);
            $object['literal'] = false;
            $object['value'] = $attribute;
        } else {
            $object['literal'] = true;
            $object['value'] = $element->nodeValue;
        }
        $relationship['object'] = $object;

        return $relationship;
    }

    /**
     * Serializes the data returned in FedoraApiM::getRelationships()
     *
     * @param array $request
     * @return array
     */
    public function getRelationships($request)
    {
        $relationships = [];

        $dom = $this->loadDomDocument((string) $request->getBody());
        $xpath = new DomXPath($dom);
        $results = $xpath->query('/rdf:RDF/rdf:Description/*');

        foreach ($results as $element) {
            $relationships[] = $this->getRelationship($element);
        }

        return $relationships;
    }

    /**
     * Serializes the data returned in FedoraApiM::ingest()
     *
     * @param array $request
     * @return string
     */
    public function ingest($request)
    {
        return (string) $request->getBody();
    }

    /**
     * Serializes the data returned in FedoraApiM::modifyDatastream()
     *
     * @param array $request
     * @return array
     */
    public function modifyDatastream($request)
    {
        $result = $this->loadSimpleXml((string) $request->getBody());
        return $this->flattenDocument($result);
    }

    /**
     * Serializes the data returned in FedoraApiM::modifyObject()
     *
     * @param array $request
     * @return string
     */
    public function modifyObject($request)
    {
        return (string) $request->getBody();
    }

    /**
     * Serializes the data returned in FedoraApiM::purgeDatastream()
     *
     * @param array $request
     * @return array
     */
    public function purgeDatastream($request)
    {
        return json_decode((string) $request->getBody());
    }

    /**
     * Serializes the data returned in FedoraApiM::purgeObject()
     *
     * @param array $request
     * @return string
     */
    public function purgeObject($request)
    {
        return (string) $request->getBody();
    }

    /**
     * Serializes the data returned in FedoraApiM::validate()
     *
     * @param array $request
     * @return array
     */
    public function validate($request)
    {
        $result = $this->loadSimpleXml((string) $request->getBody());
        $doc = $this->flattenDocument($result);
        $doc['valid'] = (string)$result['valid'] == "true" ? true : false;
        return $doc;
    }

    /**
     * Serializes the call to FedoraApiM::upload()
     *
     * @param array $request
     * @return string
     */
    public function upload($request)
    {
        return (string) $request->getBody();
    }
}
