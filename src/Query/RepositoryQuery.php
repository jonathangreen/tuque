<?php

namespace Islandora\Tuque\Query;

use Islandora\Tuque\Connection\GuzzleConnection;
use XMLReader;

class RepositoryQuery
{

    public $connection;
    const SIMPLE_XML_NAMESPACE = "http://www.w3.org/2001/sw/DataAccess/rf1/result";

    /**
     * Construct a new RI object.
     *
     * @param GuzzleConnection $connection
     *   The connection to connect to the RI with.
     */
    public function __construct(GuzzleConnection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Parse the passed in Sparql XML string into a more easily usable format.
     *
     * @param string $sparql
     *   A string containing Sparql result XML.
     *
     * @return array
     *   Indexed (numerical) array, containing a number of associative arrays,
     *   with keys being the same as the variable names in the query.
     *   URIs beginning with 'info:fedora/' will have this beginning stripped
     *   off, to facilitate their use as PIDs.
     */
    public static function parseSparqlResults($sparql)
    {
        // Load the results into a XMLReader Object.
        $xmlReader = new XMLReader();
        $xmlReader->xml($sparql);

        // Storage.
        $results = [];
        // Build the results.
        while ($xmlReader->read()) {
            if ($xmlReader->localName === 'result') {
                if ($xmlReader->nodeType == XMLReader::ELEMENT) {
                    // Initialize a single result.
                    $r = [];
                } elseif ($xmlReader->nodeType == XMLReader::END_ELEMENT) {
                    // Add result to results
                    $results[] = $r;
                }
            } elseif ($xmlReader->nodeType == XMLReader::ELEMENT && $xmlReader->depth == 3) {
                $val = [];
                $uri = $xmlReader->getAttribute('uri');
                if ($uri !== null) {
                    $val['value'] = self::pidUriToBarePid($uri);
                    $val['uri'] = (string) $uri;
                    $val['type'] = 'pid';
                } else {
                    //deal with any other types
                    $val['type'] = 'literal';
                    $val['value'] = (string) $xmlReader->readString();
                }
                $r[$xmlReader->localName] = $val;
            }
        }

        $xmlReader->close();
        return $results;
    }

    /**
     * Performs the given Resource Index query and return the results.
     *
     * @param string $query
     *   A string containing the RI query to perform.
     * @param string $type
     *   The type of query to perform, as used by the risearch interface.
     * @param int $limit
     *   An integer, used to limit the number of results to return.
     * @param string $format
     *   A string indicating the type format desired, as supported by the
     *   underlying triple store.
     *
     * @return string
     *   The contents returned, in the $format specified.
     */
    protected function internalQuery($query, $type = 'itql', $limit = -1, $format = 'Sparql')
    {
        $url = 'risearch';
        $options = ['query' => [
            'type' => 'tuples',
            'flush' => 'true',
            'format' => $format,
            'lang' => $type,
            'query' => $query,
        ]];

        if ($limit > 0) {
            $options['query']['limit'] = $limit;
        }

        $result = $this->connection->getRequest($url, $options);
        return (string) $result->getBody();
    }

    /**
     * Performs the given Resource Index query and return the results.
     *
     * @param string $query
     *   A string containing the RI query to perform.
     * @param string $type
     *   The type of query to perform, as used by the risearch interface.
     * @param int $limit
     *   An integer, used to limit the number of results to return.
     *
     * @return array
     *   Indexed (numerical) array, containing a number of associative arrays,
     *   with keys being the same as the variable names in the query.
     *   URIs beginning with 'info:fedora/' will have this beginning stripped
     *   off, to facilitate their use as PIDs.
     */
    public function query($query, $type = 'itql', $limit = -1)
    {
        // Pass the query's results off to a decent parser.
        return self::parseSparqlResults($this->internalQuery($query, $type, $limit));
    }

    /**
     * Thin wrapper for self::query().
     *
     * @see self::query()
     */
    public function itqlQuery($query, $limit = -1)
    {
        return $this->query($query, 'itql', $limit);
    }

    /**
     * Thin wrapper for self::query().
     *
     * This function once took a 3rd parameter for an offset that did not work.
     * It has been eliminated.  If you wish to use an offset include it in the
     * query.
     *
     * @see self::query()
     */
    public function sparqlQuery($query, $limit = -1)
    {
        return $this->query($query, 'sparql', $limit);
    }

    /**
     * Utility function used in self::query().
     *
     * Strips off the 'info:fedora/' prefix from the passed in string.
     *
     * @param string $uri
     *   A string containing a URI.
     *
     * @return string
     *   The input string less the 'info:fedora/' prefix (if it has it).
     *   The original string otherwise.
     */
    protected static function pidUriToBarePid($uri)
    {
        $chunk = 'info:fedora/';
        $pos = strpos($uri, $chunk);
        // Remove info:fedora/ chunk.
        if ($pos === 0) {
            return substr($uri, strlen($chunk));
        } // Doesn't start with info:fedora/ chunk...
        else {
            return $uri;
        }
    }

    /**
     * Get the count of tuples a query selects.
     *
     * Given that some languages do not have a built-in construct for performing
     * counting/aggregation, a method to help with this is desirable.
     *
     * @param string $query
     *   A query for which to count the number tuples returned.
     * @param string $type
     *
     * @return int
     *   The number of tuples which were selected.
     */
    public function countQuery($query, $type = 'itql')
    {
        $content = $this->internalQuery($query, $type, -1, 'count');
        return intval($content);
    }
}
