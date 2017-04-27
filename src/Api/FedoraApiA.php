<?php

namespace Islandora\Tuque\Api;

use Islandora\Tuque\Connection\GuzzleConnection;

/**
 * This class implements the Fedora API-A interface. This is a light wrapper
 * around the Fedora interface. Very little attempt is put into putting things
 * into native PHP data structures.
 *
 * See this page for more information:
 * https://wiki.duraspace.org/display/FEDORA35/REST+API
 */
class FedoraApiA
{

    protected $connection;
    protected $serializer;

    /**
     * Constructor for the new FedoraApiA object.
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
     * Returns basic information about the Repository.
     *
     * This is listed as an unimplemented function in the official API for Fedora.
     * However other libraries connecting to the Fedora REST interaface use this
     * so we are including it here. It may change in the future.
     *
     * @throws \Islandora\Tuque\Exception\RepositoryException
     *
     * @return array
     *   An array describing the repository.
     *   @code
     *   Array
     *   (
     *       [repositoryName] => Fedora Repository
     *       [repositoryBaseURL] => http://localhost:8080/fedora
     *       [repositoryVersion] => 3.4.1
     *       [repositoryPID] => Array
     *           (
     *               [PID-namespaceIdentifier] => changeme
     *               [PID-delimiter] => :
     *               [PID-sample] => changeme:100
     *               [retainPID] => *
     *           )
     *
     *       [repositoryOAI-identifier] => Array
     *           (
     *               [OAI-namespaceIdentifier] => example.org
     *               [OAI-delimiter] => :
     *               [OAI-sample] => oai:example.org:changeme:100
     *           )
     *
     *       [sampleSearch-URL] => http://localhost:8080/fedora/objects
     *       [sampleAccess-URL] => http://localhost:8080/fedora/objects/demo:5
     *       [sampleOAI-URL] => http://localhost:8080/fedora/oai?verb=Identify
     *       [adminEmail] => Array
     *           (
     *               [0] => bob@example.org
     *               [1] => sally@example.org
     *           )
     *
     *   )
     *   @endcode
     */
    public function describeRepository()
    {
        // This is weird and undocumented, but its what the web client does.
        $url = "describe";
        $options = ['query' => ['xml' => 'true']];

        $response = $this->connection->getRequest($url, $options);
        $response = $this->serializer->describeRepository($response);
        return $response;
    }

    /**
     * Authenticate and provide information about a user's fedora attributes.
     *
     * Please note that calling this method
     * with an unauthenticated (i.e. anonymous) user will throw
     * an 'HttpConnectionException' with the message 'Unauthorized'.
     *
     * @return array
     *   Returns an array containing user attributes (i.e. fedoraRole).
     *    @code
     *    Array
     *    (
     *        [fedoraRole] => Array
     *            (
     *                [0] => authenticated user
     *            )
     *        [role] => Array
     *            (
     *                [0] => authenticated user
     *            )
     *    )
     *    @endcode
     */
    public function userAttributes()
    {
        $url = "user";
        $options = ['query' => ['xml' => 'true']];

        $response = $this->connection->getRequest($url, $options);
        $response = $this->serializer->userAttributes($response);
        return $response;
    }

    /**
     * Query fedora to return a list of objects.
     *
     * @param string $type
     *   The type of query. Decides the format of the next parameter. Valid
     *   options are:
     *   - query: specific query on certain fields
     *   - terms: search in any field
     * @param string $query
     *   The format of this parameter depends on what was passed to type. The
     *   formats are:
     *   - query: A sequence of space-separated conditions. A condition consists
     *     of a metadata element name followed directly by an operator, followed
     *     directly by a value. Valid element names are (pid, label, state,
     *     ownerId, cDate, mDate, dcmDate, title, creator, subject, description,
     *     publisher, contributor, date, type, format, identifier, source,
     *     language, relation, coverage, rights). Valid operators are:
     *     contains (~), equals (=), greater than (>), less than (<), greater than
     *     or equals (>=), less than or equals (<=). The contains (~) operator
     *     may be used in combination with the ? and * wildcards to query for
     *     simple string patterns. Values may be any string. If the string
     *     contains a space, the value should begin and end with a single quote
     *     character ('). If all conditions are met for an object, the object is
     *     considered a match.
     *   - terms: A phrase represented as a sequence of characters (including the
     *     ? and * wildcards) for the search. If this sequence is found in any of
     *     the fields for an object, the object is considered a match.
     * @param int $max_results
     *   (optional) Default: 25. The maximum number of results that the server
     *   should provide at once.
     * @param array $display_fields
     *   (optional) Default: array('pid', 'title'). The fields to be returned as
     *   an indexed array. Valid element names are the same as the ones given for
     *   the query parameter.
     *
     * @throws \Islandora\Tuque\Exception\RepositoryException
     *
     * @return array
     *   The results are returned in an array key called 'results'. If there
     *   are more results that aren't returned then the search session information
     *   is contained in a key called 'session'. Note that it is possible for
     *   some display fields to be multivalued, such as identifier (DC allows
     *   multiple DC identifier results) in the case there are multiple results
     *   an array is returned instread of a string, this indexed array contains
     *   all of the values.
     *   @code
     *   Array
     *   (
     *      [session] => Array
     *          (
     *              [token] => 96b2604f040067645f45daf029062d6e
     *              [cursor] => 0
     *              [expirationDate] => 2012-03-07T14:28:24.886Z
     *          )
     *
     *      [results] => Array
     *          (
     *              [0] => Array
     *                  (
     *                      [pid] => islandora:collectionCModel
     *                      [title] => Islandora Collection Content Model
     *                      [identifier] => Contents of DC:Identifier
     *                  )
     *
     *              [1] => Array
     *                  (
     *                      [pid] => islandora:testCModel
     *                      [title] => Test content model for Ari
     *                      [identifier] => Array
     *                          (
     *                              [0] => Contents of first DC:Identifier
     *                              [1] => Contents of seconds DC:Identifier
     *                          )
     *
     *                  )
     *
     *          )
     *
     *    )
     *    @endcode
     */
    public function findObjects($type, $query, $max_results = null, $display_fields = ['pid', 'title'])
    {
        $url = "objects";
        $options = ['query' => [
            'resultFormat' => 'xml',
            'maxResults' => $max_results
        ]];

        switch ($type) {
            case 'terms':
                $options['query']['terms'] = $query;
                break;
            case 'query':
                $options['query']['query'] = $query;
                break;
        }

        if (is_array($display_fields)) {
            foreach ($display_fields as $display) {
                $options['query'][$display] = 'true';
            }
        }

        $response = $this->connection->getRequest($url, $options);
        $response = $this->serializer->findObjects($response);
        return $response;
    }

    /**
     * Returns next set of objects when given session key.
     *
     * @param string $session_token
     *   Session token returned from previous search call.
     *
     * @throws \Islandora\Tuque\Exception\RepositoryException
     *
     * @return array
     *   The result format is the same as findObjects.
     *
     * @see FedoraApiA::findObjects
     */
    public function resumeFindObjects($session_token)
    {
        $url = "objects";
        $options = ['query' => [
            'resultFormat' => 'xml',
            'sessionToken' => $session_token
        ]];

        $response = $this->connection->getRequest($url, $options);
        $response = $this->serializer->resumeFindObjects($response);
        return $response;
    }

    /**
     * Get the default dissemination of a datastream. (Get the contents).
     *
     * @param String $pid
     *   Persistent identifier of the digital object.
     * @param String $dsid
     *   Datastream identifier.
     * @param array $as_of_date_time
     *   (optional) Indicates that the result should be relative to the
     *     digital object as it existed at the given date and time. Defaults to
     *     the most recent version.
     * @param array $file
     *   (optional) A file to retrieve the dissemination into.
     *
     * @throws \Islandora\Tuque\Exception\RepositoryException
     *
     * @return string
     *   The response from Fedora with the contents of the datastream if file
     *   isn't set. Returns TRUE if the file parameter is passed.
     */
    public function getDatastreamDissemination($pid, $dsid, $as_of_date_time = null, $file = null)
    {
        $pid = urlencode($pid);
        $dsid = urlencode($dsid);
        $url = "objects/$pid/datastreams/$dsid/content";
        $options = ['query' => ['asOfDateTime' => $as_of_date_time]];

        if ($file) {
            $options['sink'] = $file;
        }

        $response = $this->connection->getRequest($url, $options);
        $response = $this->serializer->getDatastreamDissemination($response, $file);
        return $response;
    }

    /**
     * Get a datastream dissemination from Fedora.
     *
     * @param String $pid
     *   Persistent identifier of the digital object.
     * @param String $sdef_pid
     *   Persistent identifier of the sDef defining the methods.
     * @param String $method
     *   Method to invoke.
     * @param String $method_parameters
     *   A key-value paired array of parameters required by the method.
     *
     * @throws \Islandora\Tuque\Exception\RepositoryException
     *
     * @return string
     *   The response from Fedora.
     */
    public function getDissemination($pid, $sdef_pid, $method, $method_parameters = null)
    {
        $pid = urlencode($pid);
        $sdef_pid = urlencode($sdef_pid);
        $method = urlencode($method);
        $url = "objects/$pid/methods/$sdef_pid/$method";
        $options = [];

        if (isset($method_parameters) && is_array($method_parameters)) {
            $options['query'] = $method_parameters;
        }

        $response = $this->connection->getRequest($url);
        $response = $this->serializer->getDissemination($response);
        return $response;
    }

    /**
     * Get the change history for the object.
     *
     * @param String $pid
     *   Persistent identifier of the digital object.
     *
     * @throws \Islandora\Tuque\Exception\RepositoryException
     *
     * @return array
     *   An array containing the different revisions of the object.
     *   @code
     *   Array
     *   (
     *       [0] => 2011-07-08T18:01:40.384Z
     *       [1] => 2011-07-08T18:01:40.464Z
     *       [2] => 2011-07-08T18:01:40.552Z
     *       [3] => 2011-07-08T18:01:40.694Z
     *       [4] => 2012-02-22T15:07:15.305Z
     *       [5] => 2012-02-29T14:20:28.857Z
     *       [6] => 2012-02-29T14:22:18.239Z
     *       [7] => 2012-02-29T14:22:46.545Z
     *       [8] => 2012-02-29T20:52:33.069Z
     *   )
     *   @endcode
     */
    public function getObjectHistory($pid)
    {
        $pid = urlencode($pid);
        $url = "objects/$pid/versions";
        $options = ['query' => ['format' => 'xml']];

        $response = $this->connection->getRequest($url, $options);
        $response = $this->serializer->getObjectHistory($response);
        return $response;
    }

    /**
     * Implements the getObjectProfile Fedora API-A method.
     *
     * @param String $pid
     *   Persistent identifier of the digital object.
     * @param String $as_of_date_time
     *   (Optional) Indicates that the result should be relative to the digital
     *   object as it existed on the given date. Date Format: yyyy-MM-dd or
     *   yyyy-MM-ddTHH:mm:ssZ
     *
     * @throws \Islandora\Tuque\Exception\RepositoryException
     *
     * @return array
     *   Returns information about the digital object.
     *   @code
     *   Array
     *   (
     *       [objLabel] => Islandora strict PDF content model
     *       [objOwnerId] => fedoraAdminnnn
     *       [objModels] => Array
     *           (
     *               [0] => info:fedora/fedora-system:ContentModel-3.0
     *               [1] => info:fedora/fedora-system:FedoraObject-3.0
     *           )
     *
     *       [objCreateDate] => 2011-07-08T18:01:40.384Z
     *       [objLastModDate] => 2012-03-02T20:50:13.534Z
     *       [objDissIndexViewURL] => http://localhost:8080/fedora/objects/
     *         islandora%3Astrict_pdf/methods/fedora-system%3A3/viewMethodIndex
     *       [objItemIndexViewURL] => http://localhost:8080/fedora/objects/
     *         islandora%3Astrict_pdf/methods/fedora-system%3A3/viewItemIndex
     *       [objState] => A
     *   )
     *   @endcode
     */
    public function getObjectProfile($pid, $as_of_date_time = null)
    {
        $pid = urlencode($pid);
        $url = "objects/{$pid}";
        $options = ['query' => ['format' => 'xml', 'asOfDateTime' => $as_of_date_time]];

        $response = $this->connection->getRequest($url, $options);
        $response = $this->serializer->getObjectProfile($response);
        return $response;
    }

    /**
     * List all the datastreams that are associated with this PID.
     *
     * @param string $pid
     *   Persistent identifier of the digital object.
     * @param string $as_of_date_time
     *   (optional) Indicates that the result should be relative to the digital
     *   object as it existed on the given date. Date Format: yyyy-MM-dd or
     *   yyyy-MM-ddTHH:mm:ssZ.
     *
     * @throws \Islandora\Tuque\Exception\RepositoryException
     *
     * @return array
     *   An associative array with the dsid of the datastreams as the key and
     *   the mimetype and label as the value.
     *   @code
     *   Array
     *   (
     *       [DC] => Array
     *           (
     *               [label] => Dublin Core Record for this object
     *               [mimetype] => text/xml
     *           )
     *
     *       [RELS-EXT] => Array
     *           (
     *               [label] => Fedora Object-to-Object Relationship Metadata
     *               [mimetype] => text/xml
     *           )
     *
     *       [ISLANDORACM] => Array
     *           (
     *               [label] => ISLANDORACM
     *               [mimetype] => text/xml
     *           )
     *
     *   )
     *   @endcode
     */
    public function listDatastreams($pid, $as_of_date_time = null)
    {
        $pid = urlencode($pid);
        $url = "objects/{$pid}/datastreams";
        $options = ['query' => ['format' => 'xml', 'asOfDateTime' => $as_of_date_time]];

        $response = $this->connection->getRequest($url, $options);
        $response = $this->serializer->listDatastreams($response);
        return $response;
    }

    /**
     * Implements the listMethods Fedora API-A method.
     *
     * @param String $pid
     *   Persistent identifier of the digital object.
     * @param String $sdef_pid
     *   (Optional) Persistent identifier of the SDef defining the methods.
     * @param String $as_of_date_time
     *   (Optional) Indicates that the result should be relative to the digital
     *   object as it existed on the given date. Date Format: yyyy-MM-dd or
     *   yyyy-MM-ddTHH:mm:ssZ.
     *
     * @throws \Islandora\Tuque\Exception\RepositoryException
     *
     * @return array
     *   An array containing data about the methods that can be called. The result
     *   array is an associative array where the sdef pid is the key and the value
     *   is a indexed array of methods.
     *   @code
     *   Array
     *   (
     *       [ilives:viewerSdef] => Array
     *           (
     *               [0] => getViewer
     *           )
     *
     *       [ilives:jp2Sdef] => Array
     *           (
     *               [0] => getMetadata
     *               [1] => getRegion
     *           )
     *
     *       [fedora-system:3] => Array
     *          (
     *               [0] => viewObjectProfile
     *               [1] => viewMethodIndex
     *               [2] => viewItemIndex
     *               [3] => viewDublinCore
     *           )
     *
     *   )
     *   @endcode
     */
    public function listMethods($pid, $sdef_pid = '', $as_of_date_time = null)
    {
        $pid = urlencode($pid);
        $sdef_pid = urlencode($sdef_pid);
        $url = "objects/{$pid}/methods/{$sdef_pid}";
        $options = ['query' => ['format' => 'xml', 'asOfDateTime' => $as_of_date_time]];

        $response = $this->connection->getRequest($url, $options);
        $response = $this->serializer->listMethods($response);
        return $response;
    }
}
