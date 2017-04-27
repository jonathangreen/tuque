<?php

namespace Islandora\Tuque\Api;

use GuzzleHttp\Exception\ServerException;
use Islandora\Tuque\Connection\GuzzleConnection;

/**
 * This class implements the Fedora API-M interface. This is a light wrapper
 * around the Fedora interface. Very little attempt is put into putting things
 * into native PHP data structures.
 *
 * See this page for more information:
 * https://wiki.duraspace.org/display/FEDORA35/REST+API
 */
class FedoraApiM
{

    /**
     * Constructor for the new FedoraApiM object.
     *
     * @param GuzzleConnection $connection
     *   Takes the Repository Connection object for the Repository this API
     *   should connect to.
     * @param FedoraApiSerializer $serializer
     *   Takes the serializer object to that will be used to serialize the XML
     *   Fedora returns.
     */
    public function __construct(GuzzleConnection $connection, FedoraApiSerializer $serializer)
    {
        $this->connection = $connection;
        $this->serializer = $serializer;
    }

    /**
     * Add a new datastream to a fedora object.
     *
     * The datastreams are sent to Fedora using a multipart post if a string
     * or file is provided otherwise Fedora will go out and fetch the URL
     *
     * @param string $pid
     *   Persistent identifier of the digital object.
     * @param string $dsid
     *   Datastream identifier.
     * @param string $type
     *   This parameter tells the function what type of arguement is given for
     *   file. It must be one of:
     *   - string: The datastream is passed as a string.
     *   - file: The datastream is contained in a file.
     *   - url: The datastream is located at a URL, which is passed as a string.
     *     this is the only option that can be used for R and E type datastreams.
     * @param string $file
     *   This parameter depends on what is selected for $type.
     *   - string: A string containing the datastream.
     *   - file: A string containing the file name that contains the datastream.
     *     The file name must be a full path.
     *   - url: A string containing the publically accessable URL that the
     *     datastream is located at.
     * @param array $params
     *   (optional) An array that can have one or more of the following elements:
     *   - controlGroup: one of "X", "M", "R", or "E" (Inline *X*ML, *M*anaged
     *     Content, *R*edirect, or *E*xternal Referenced). Default: X.
     *   - altIDs: alternate identifiers for the datastream. A space seperated
     *     list of alternate identifiers for the datastream.
     *   - dsLabel: the label for the datastream.
     *   - versionable: enable versioning of the datastream (boolean).
     *   - dsState: one of "A", "I", "D" (*A*ctive, *I*nactive, *D*eleted).
     *   - formatURI: the format URI of the datastream.
     *   - checksumType: the algorithm used to compute the checksum. One of
     *     DEFAULT, DISABLED, MD5, SHA-1, SHA-256, SHA-384, SHA-512.
     *   - checksum: the value of the checksum represented as a hexadecimal
     *     string.
     *   - mimeType: the MIME type of the content being added, this overrides the
     *     Content-Type request header.
     *   - logMessage: a message describing the activity being performed.
     *
     * @throws \Islandora\Tuque\Exception\RepositoryException
     *
     * @return array
     *   Returns an array describing the new datastream. This is the same array
     *   returned by getDatastream. This may also contain an dsAltID key, that
     *   contains any alternate ids if any are specified.
     *   @code
     *   Array
     *   (
     *       [dsLabel] =>
     *       [dsVersionID] => test.3
     *       [dsCreateDate] => 2012-03-07T18:03:38.679Z
     *       [dsState] => A
     *       [dsMIME] => text/xml
     *       [dsFormatURI] =>
     *       [dsControlGroup] => M
     *       [dsSize] => 22
     *       [dsVersionable] => true
     *       [dsInfoType] =>
     *       [dsLocation] => islandora:strict_pdf+test+test.3
     *       [dsLocationType] => INTERNAL_ID
     *       [dsChecksumType] => DISABLED
     *       [dsChecksum] => none
     *       [dsLogMessage] =>
     *   )
     *   @endcode
     *
     * @see FedoraApiM::getDatastream
     */
    public function addDatastream($pid, $dsid, $type, $file, $params)
    {
        $pid = urlencode($pid);
        $dsid = urlencode($dsid);
        $url = "objects/$pid/datastreams/$dsid";
        $options = ['query' => $params];

        if (strtolower($type) === 'url') {
            $options['query']['dsLocation'] = $file;
        } elseif (strtolower($type) == 'string') {
            $options['headers'] = ['Content-Type' => 'text/plain'];
            $options['body'] = $file;
        } elseif (strtolower($type) == 'file') {
            $options['multipart'] = [[
                'name'     => 'file',
                'contents' => fopen($file, 'r')
            ]];
        }

        $response = $this->connection->postRequest($url, $options);
        $response = $this->serializer->addDatastream($response);
        return $response;
    }

    /**
     * Add a RDF relationship to a Fedora object.
     *
     * @param string $pid
     *   Persistent identifier of the digital object.
     * @param array $relationship
     *   An array containing the subject, predicate and object for the
     *   relationship.
     *   - subject: (optional) Subject of the relationship. Either a URI for the
     *     object or one of its datastreams. If none is given then the URI for
     *     the current object is used.
     *   - predicate: Predicate of the relationship.
     *   - object: Object of the relationship.
     * @param boolean $is_literal
     *   true if the object of the relationship is a literal, false if it is a URI
     * @param string $datatype
     *   (optional) if the object is a literal, the datatype of the literal.
     *
     * @throws \Islandora\Tuque\Exception\RepositoryException
     *
     * @return bool
     *
     * @see FedoraApiM::getRelationships
     * @see FedoraApiM::purgeRelationships
     */
    public function addRelationship($pid, $relationship, $is_literal, $datatype = null)
    {
        $pid = urlencode($pid);
        $url = "objects/$pid/relationships/new";
        $options = ['query' => $relationship + ['isLiteral' => $is_literal, 'datatype' => $datatype]];
        $response = $this->connection->postRequest($url, $options);
        $response = $this->serializer->addRelationship($response);
        return $response;
    }

    /**
     * Export a Fedora object with the given PID.
     *
     * @param string $pid
     *   Persistent identifier of the digital object.
     * @param array $params
     *   (optional) An array that can have one or more of the following elements:
     *   - format: The XML format to export. One of
     *     info:fedora/fedora-system:FOXML-1.1 (default),
     *     info:fedora/fedora-system:FOXML-1.0,
     *     info:fedora/fedora-system:METSFedoraExt-1.1,
     *     info:fedora/fedora-system:METSFedoraExt-1.0,
     *     info:fedora/fedora-system:ATOM-1.1,
     *     info:fedora/fedora-system:ATOMZip-1.1
     *   - context: The export context, which determines how datastream URLs and
     *     content are represented. Options: public (default), migrate, archive.
     *   - encoding: The preferred encoding of the exported XML.
     * @param string $file
     *   An optional writable filename to which to download the export.
     *
     * @throws \Islandora\Tuque\Exception\RepositoryException
     *
     * @return string|bool
     *   If $file was provided, boolean TRUE; otherwise, a string containing
     *   the response.
     */
    public function export($pid, $params = [], $file = null)
    {
        $pid = urlencode($pid);
        $url = "objects/$pid/export";
        $options = ['query' => $params];

        if ($file) {
            $options['sink'] = $file;
        }

        $response = $this->connection->getRequest($url, $options);
        $response = $this->serializer->export($response, $file);
        return $response;
    }

    /**
     * Returns information about the datastream.
     *
     * @param string $pid
     *   Persistent identifier of the digital object.
     * @param string $dsid
     *   Datastream identifier.
     * @param array $params
     *   (optional) An array that can have one or more of the following elements:
     *   - asOfDateTime: Indicates that the result should be relative to the
     *     digital object as it existed on the given date.
     *   - validateChecksum: verifies that the Datastream content has not changed
     *     since the checksum was initially computed.
     *
     * @throws \Islandora\Tuque\Exception\RepositoryException
     *
     * @return array
     *   An array containing information about the datastream. This may also
     *   contains a key dsAltID which contains alternate ids if any are specified.
     *   @code
     *   Array
     *   (
     *       [dsLabel] =>
     *       [dsVersionID] => test.3
     *       [dsCreateDate] => 2012-03-07T18:03:38.679Z
     *       [dsState] => A
     *       [dsMIME] => text/xml
     *       [dsFormatURI] =>
     *       [dsControlGroup] => M
     *       [dsSize] => 22
     *       [dsVersionable] => true
     *       [dsInfoType] =>
     *       [dsLocation] => islandora:strict_pdf+test+test.3
     *       [dsLocationType] => INTERNAL_ID
     *       [dsChecksumType] => DISABLED
     *       [dsChecksum] => none
     *       [dsChecksumValid] => true
     *   )
     *   @endcode
     */
    public function getDatastream($pid, $dsid, $params = [])
    {
        $pid = urlencode($pid);
        $dsid = urlencode($dsid);
        $url = "objects/$pid/datastreams/$dsid";
        $options = ['query' => ['format' => 'xml'] + $params];

        $response = $this->connection->getRequest($url, $options);
        $response = $this->serializer->getDatastream($response);
        return $response;
    }

    /**
     * Get information on the different datastream versions.
     *
     * @param string $pid
     *   Persistent identifier of the digital object.
     * @param string $dsid
     *   Datastream identifier.
     *
     * @throws \Islandora\Tuque\Exception\RepositoryException
     *
     * @return array
     *   Returns a indexed array with the same keys as getDatastream.
     *   @code
     *   Array
     *   (
     *       [0] => Array
     *           (
     *               [dsLabel] =>
     *               [dsVersionID] => test.3
     *               [dsCreateDate] => 2012-03-07T18:03:38.679Z
     *               [dsState] => A
     *               [dsMIME] => text/xml
     *               [dsFormatURI] =>
     *               [dsControlGroup] => M
     *               [dsSize] => 22
     *               [dsVersionable] => true
     *               [dsInfoType] =>
     *               [dsLocation] => islandora:strict_pdf+test+test.3
     *               [dsLocationType] => INTERNAL_ID
     *               [dsChecksumType] => DISABLED
     *               [dsChecksum] => none
     *           )
     *
     *       [1] => Array
     *           (
     *               [dsLabel] =>
     *               [dsVersionID] => test.2
     *               [dsCreateDate] => 2012-03-07T18:03:13.722Z
     *               [dsState] => A
     *               [dsMIME] => text/xml
     *               [dsFormatURI] =>
     *               [dsControlGroup] => M
     *               [dsSize] => 22
     *               [dsVersionable] => true
     *               [dsInfoType] =>
     *               [dsLocation] => islandora:strict_pdf+test+test.2
     *               [dsLocationType] => INTERNAL_ID
     *               [dsChecksumType] => DISABLED
     *               [dsChecksum] => none
     *           )
     *
     *   )
     *   @endcode
     *
     * @see FedoraApiM::getDatastream
     */
    public function getDatastreamHistory($pid, $dsid)
    {
        $pid = urlencode($pid);
        $dsid = urlencode($dsid);
        $url = "objects/{$pid}/datastreams/{$dsid}/history";
        $options = ['query' => ['format' => 'xml']];

        $response = $this->connection->getRequest($url, $options);
        $response = $this->serializer->getDatastreamHistory($response);

        return $response;
    }

    /**
     * Get a new unused PID.
     *
     * @param string $namespace
     *   The namespace to get the PID in. This defaults to default namespace of
     *   the repository. This should not contain the PID seperator, for example
     *   it should be islandora not islandora:.
     * @param int $numpids
     *   The number of pids being requested.
     *
     * @throws \Islandora\Tuque\Exception\RepositoryException
     *
     * @return array/string
     *   If one pid is requested it is returned as a string. If multiple pids are
     *   requested they they are returned in an array containg strings.
     *   @code
     *   Array
     *   (
     *       [0] => test:7
     *       [1] => test:8
     *   )
     *   @endcode
     */
    public function getNextPid($namespace = null, $numpids = null)
    {
        $url = "objects/nextPID";
        $options = ['query' => [
            'format' => 'xml',
            'namespace' => $namespace,
            'numPIDs' => $numpids
        ]];

        $response = $this->connection->postRequest($url, $options);
        $response = $this->serializer->getNextPid($response);
        return $response;
    }

    /**
     * Get the Fedora Objects XML (Foxml).
     *
     * @param string $pid
     *   Persistent identifier of the digital object.
     * @param string $file
     *   An optional writable filename to which to download the FOXML.
     *
     * @throws \Islandora\Tuque\Exception\RepositoryException
     *
     * @return string|bool
     *   If $file was provided, boolean TRUE; otherwise, a string containing
     *   the objects FOXML.
     *
     * @see FedoraApiM::export
     */
    public function getObjectXml($pid, $file = null)
    {
        $pid = urlencode($pid);
        $url = "objects/{$pid}/objectXML";
        $options = [];
        if ($file) {
            $options['sink'] = $file;
        }
        $response = $this->connection->getRequest($url, $options);
        $response = $this->serializer->getObjectXml($response, $file);
        return $response;
    }

    /**
     * Query relationships for a particular fedora object.
     *
     * @param string $pid
     *   Persistent identifier of the digital object.
     * @param array $relationship
     *   (Optional) An array defining the relationship:
     *   - subject: subject of the relationship(s). Either a URI for the object
     *     or one of its datastreams. defaults to the URI of the object.
     *   - predicate: predicate of the relationship(s), if missing returns all
     *     predicates.
     *
     * @throws \Islandora\Tuque\Exception\RepositoryException
     *
     * @return array
     *   An indexed array with all the relationships.
     *   @code
     *   Array
     *   (
     *       [0] => Array
     *           (
     *               [subject] => islandora:strict_pdf
     *               [predicate] => Array
     *                   (
     *                       [predicate] => hasModel
     *                       [uri] => info:fedora/fedora-system:def/model#
     *                       [alias] =>
     *                   )
     *
     *               [object] => Array
     *                   (
     *                       [literal] => FALSE
     *                       [value] => fedora-system:FedoraObject-3.0
     *                   )
     *
     *           )
     *
     *       [1] => Array
     *           (
     *               [subject] => islandora:strict_pdf
     *               [predicate] => Array
     *                   (
     *                       [predicate] => bar
     *                       [uri] => http://woot/foo#
     *                       [alias] =>
     *                   )
     *
     *               [object] => Array
     *                   (
     *                       [literal] => TRUE
     *                       [value] => thedude
     *                   )
     *
     *           )
     *
     *   )
     *   @endcode
     */
    public function getRelationships($pid, $relationship = [])
    {
        $pid = urlencode($pid);
        $url = "objects/$pid/relationships";
        $options = ['query' => ['format' => 'xml'] + $relationship];

        $response = $this->connection->getRequest($url, $options);
        $response = $this->serializer->getRelationships($response);
        return $response;
    }

    /**
     * Create a new object in Fedora.
     *
     * This could be ingesting a XML file as a
     * string or a file. Executing this request with no XML file content will
     * result in the creation of a new, empty object (with either the specified
     * PID or a system-assigned PID). The new object will contain only a minimal
     * DC datastream specifying the dc:identifier of the object.
     *
     * @param array $params
     *   (optional) An array that can have one or more of the following elements:
     *   - pid: persistent identifier of the object to be created. If this is not
     *     supplied then either a new PID will be created for this object or the
     *     PID to be used is encoded in the XML included as the body of the
     *     request
     *   - string: The XML file defining the new object as a string
     *   - file: The XML file defining the new object as a string containing the
     *     full path to the XML file. This must not be used with the string
     *     parameter
     *   - label: the label of the new object
     *   - format: the XML format of the object to be ingested. One of
     *     info:fedora/fedora-system:FOXML-1.1,
     *     info:fedora/fedora-system:FOXML-1.0,
     *     info:fedora/fedora-system:METSFedoraExt-1.1,
     *     info:fedora/fedora-system:METSFedoraExt-1.0,
     *     info:fedora/fedora-system:ATOM-1.1,
     *     info:fedora/fedora-system:ATOMZip-1.1
     *   - encoding:    the encoding of the XML to be ingested.  If this is
     *     specified, and given as anything other than UTF-8, you must ensure
     *     that the same encoding is declared in the XML.
     *   - namespace: The namespace to be used to create a PID for a new empty
     *     object: if a 'string' parameter is included with the request, the
     *     namespace parameter is ignored.
     *   - ownerId: the id of the user to be listed at the object owner.
     *   - logMessage: a message describing the activity being performed.
     *
     * @throws \Islandora\Tuque\Exception\RepositoryException
     *
     * @return string
     *   The PID of the newly created object.
     *
     * @todo This function is a problem in Fedora < 3.5 where ownerId does not
     *   properly get set. https://jira.duraspace.org/browse/FCREPO-963. We should
     *   deal with this.
     */
    public function ingest($params = [])
    {
        $url = "objects/";
        $options = [];

        if (isset($params['pid'])) {
            $pid = urlencode($params['pid']);
            $url .= "$pid";
        } else {
            $url .= "new";
        }

        if (isset($params['string'])) {
            $options['body'] = $params['string'];
            $options['headers'] = ['Content-Type' => 'text/xml'];
        } elseif (isset($params['file'])) {
            $options['multipart'] = [[
                'name' => 'file',
                'contents' => fopen($params['file'], 'r'),
                'headers' => ['Content-Type' => 'text/xml']
            ]];
        }

        unset($params['pid']);
        unset($params['string']);
        unset($params['file']);
        $options['query'] = $params;

        $response = $this->connection->postRequest($url, $options);
        $response = $this->serializer->ingest($response);
        return $response;
    }

    /**
     * Update a datastream's metadata, contents, or both.
     *
     * @param string $pid
     *   Persistent identifier of the digital object.
     * @param string $dsid
     *   Datastream identifier.
     * @param array $params
     *   (optional) An array that can have one or more of the following elements:
     *   - dsFile: String containing the full path to a file that will be used
     *     as the new contents of the datastream.
     *   - dsString: String containing the new contents of the datastream.
     *   - dsLocation: String containing a URL to fetch the new datastream from.
     *     Only ONE of dsFile, dsString or dsLocation should be used.
     *   - altIDs:  alternate identifiers for the datastream. This is a space
     *     seperated string of alternate identifiers for the datastream.
     *   - dsLabel:     the label for the datastream.
     *   - versionable: enable versioning of the datastream.
     *   - dsState: one of "A", "I", "D" (*A*ctive, *I*nactive, *D*eleted)
     *   - formatURI: the format URI of the datastream
     *   - checksumType: the algorithm used to compute the checksum. This has to
     *     be one of: DEFAULT, DISABLED, MD5, SHA-1, SHA-256, SHA-384, SHA-512.
     *     If this parameter is given and no checksum is given the checksum will
     *     be computed.
     *   - checksum:    the value of the checksum represented as a hexadecimal
     *     string. This checksum must be computed by the algorithm defined above.
     *   - mimeType:    the MIME type of the content being added, this overrides
     *     the Content-Type request header.
     *   - logMessage: a message describing the activity being performed
     *   - lastModifiedDate:    date/time of the last (known) modification to the
     *     datastream, if the actual last modified date is later, a 409 response
     *     is returned. This can be used for opportunistic object locking.
     *
     * @throws \Islandora\Tuque\Exception\RepositoryException
     *
     * @return array
     *   An array contianing information about the updated datastream. This array
     *   is the same as the array returned by getDatastream.
     *
     * @see FedoraApiM::getDatastream
     */
    public function modifyDatastream($pid, $dsid, $params = [])
    {
//        $pid = urlencode($pid);
//        $dsid = urlencode($dsid);
//        $url = "objects/{$pid}/datastreams/{$dsid}";
//        $options = [];
//
//        if (isset($params['dsString'])) {
//            $options['body'] = $params['dsString'];
//            $options['headers'] = ['Content-Type' => 'text/plain'];
//        } elseif (isset($params['dsFile'])) {
//            $options['body'] = fopen($params['dsFile'], 'r');
//            $options['headers'] = ['Content-Type' => 'application/octet-stream'];
//        }
//        unset($params['dsFile']);
//        unset($params['dsString']);
//        $options['query'] = $params;
//
//        $response = $this->connection->putRequest($url, $options);
//        $response = $this->serializer->modifyDatastream($response);
//
//        return $response;
        $pid = urlencode($pid);
        $dsid = urlencode($dsid);
        $request = "objects/{$pid}/datastreams/{$dsid}";

        // Setup the file.
        if (isset($params['dsFile'])) {
            $type = 'file';
            $data = $params['dsFile'];
        } elseif (isset($params['dsString'])) {
            $type = 'string';
            $data = $params['dsString'];
        } else {
            $type = 'none';
            $data = null;
        }
        unset($params['dsString']);
        unset($params['dsFile']);
        $options['query'] = $params;

        if ($type == 'string') {
            $options['body'] = $data;
            $options['headers'] = ['Content-Type' => 'text/plain'];
        } elseif ($type == 'file') {
            $resource = fopen($data, 'r');
            $options['body'] = $resource;
            $options['headers'] = ['Content-Type' => 'application/octet-stream'];
        }

        $response = $this->connection->putRequest($request, $options);
        $response = $this->serializer->modifyDatastream($response);
        return $response;
    }

    /**
     * Update Fedora Object parameters.
     *
     * @param string $pid
     *   Persistent identifier of the digital object.
     * @param array $params
     *   (optional) An array that can have one or more of the following elements:
     *   - label: object label.
     *   - ownerId: the id of the user to be listed at the object owner.
     *   - state: the new object state - *A*ctive, *I*nactive, or *D*eleted.
     *   - logMessage: a message describing the activity being performed.
     *   - lastModifiedDate: date/time of the last (known) modification to the
     *     datastream, if the actual last modified date is later, a 409 response
     *     is returned. This can be used for opportunistic object locking.
     *
     * @throws \Islandora\Tuque\Exception\RepositoryException
     *
     * @return string
     *   A string containg the timestamp of the object modification.
     */
    public function modifyObject($pid, $params = null)
    {
        $pid = urlencode($pid);
        $url = "objects/$pid";
        $options = ['query' => $params];
        $response = $this->connection->putRequest($url, $options);
        $response = $this->serializer->modifyObject($response);
        return $response;
    }

    /**
     * Permanently removes a datastream and all its associated data.
     *
     * @param string $pid
     *   Persistent identifier of the digital object.
     * @param string $dsid
     *   Datastream identifier
     * @param array $params
     *   (optional) An array that can have one or more of the following elements:
     *   - startDT: the (inclusive) start date-time stamp of the range. If not
     *     specified, this is taken to be the lowest possible value, and thus,
     *     the entire version history up to the endDT will be purged.
     *   - endDT: the (inclusive) ending date-time stamp of the range. If not
     *     specified, this is taken to be the greatest possible value, and thus,
     *     the entire version history back to the startDT will be purged.
     *   - logMessage: a message describing the activity being performed.
     *
     * @throws \Islandora\Tuque\Exception\RepositoryException
     *
     * @return array
     *   An array containing the timestamps of the datastreams that were removed.
     *   @code
     *   Array
     *   (
     *       [0] => 2012-03-08T18:44:15.214Z
     *       [1] => 2012-03-08T18:44:15.336Z
     *   )
     *   @endcode
     */
    public function purgeDatastream($pid, $dsid, $params = [])
    {
        $pid = urlencode($pid);
        $dsid = urlencode($dsid);
        $url = "objects/$pid/datastreams/$dsid";
        $options = ['query' => $params];

        $response = $this->connection->deleteRequest($url, $options);
        $response = $this->serializer->purgeDatastream($response);
        return $response;
    }

    /**
     * Purge an object.
     *
     * @param string $pid
     *   Persistent identifier of the digital object.
     * @param string $log_message
     *   (optional)  A message describing the activity being performed.
     *
     * @throws \Islandora\Tuque\Exception\RepositoryException
     *
     * @return string
     *   Timestamp when object was deleted.
     */
    public function purgeObject($pid, $log_message = null)
    {
        $pid = urlencode($pid);
        $url = "objects/{$pid}";
        $options = ['query' => ['logMessage' => $log_message]];
        $response = $this->connection->deleteRequest($url, $options);
        $response = $this->serializer->purgeObject($response);
        return $response;
    }

    /**
     * Validate an object.
     *
     * @param string $pid
     *   Persistent identifier of the digital object.
     * @param array $as_of_date_time
     *   (optional) Indicates that the result should be relative to the
     *     digital object as it existed at the given date and time. Defaults to
     *     the most recent version.
     *
     * @throws \Islandora\Tuque\Exception\RepositoryException
     *
     * @return array
     *   An array containing the validation results.
     *   @code
     *   Array
     *   (
     *       [valid] => false
     *       [contentModels] => Array
     *           (
     *               [0] => "info:fedora/fedora-system:FedoraObject-3.0"
     *           )
     *       [problems] => Array
     *           (
     *               [0] => "Problem description"
     *           )
     *       [datastreamProblems] => Array
     *           (
     *               [dsid] => Array
     *               (
     *                   [0] => "Problem description"
     *               )
     *           )
     *   )
     *   @endcode
     */
    public function validate($pid, $as_of_date_time = null)
    {
        $url = "objects/{$pid}/validate";
        $options = ['asOfDateTime' => $as_of_date_time];

        $response = $this->connection->getRequest($url, $options);
        $response = $this->serializer->validate($response);
        return $response;
    }

    /**
     * Uploads file.
     *
     * @param string $file
     *   Path to uploaded file on server
     *
     * @return string
     *   url to uploaded file
     */
    public function upload($file)
    {
        $url = "upload";
        $options = ['multipart' => [[
            'name' => 'file',
            'contents' => fopen($file, 'r')
        ]]];

        $response = $this->connection->postRequest($url, $options);
        $response = $this->serializer->upload($response);
        return $response;
    }
}
