<?php

namespace Islandora\Tuque\Repository;

use Islandora\Tuque\Api\FedoraApi;
use Islandora\Tuque\Cache\AbstractCache;
use Islandora\Tuque\Exception\RepositoryException;
use Islandora\Tuque\Foxml\FoxmlDocument;
use Islandora\Tuque\Object\FedoraObject;
use Islandora\Tuque\Object\NewFedoraObject;
use Islandora\Tuque\Query\RepositoryQuery;

/**
 * Concrete implementation of the AbstractRepository for Fedora.
 *
 * The parent class has more detailed documentation about how this class can
 * be called as an Array.
 *
 * @see AbstractRepository
 */
class FedoraRepository extends AbstractRepository
{

    /**
     * This is an instantiated AbstractCache that we use to make sure we aren't
     * instantiating the same objects over and over.
     *
     * @var \Islandora\Tuque\Cache\AbstractCache
     */
    protected $cache;

    /**
     * This provides some convenient methods for searching the resource index.
     *
     * @var \Islandora\Tuque\Query\RepositoryQuery
     */
    public $ri;

    /**
     * @var \Islandora\Tuque\Api\FedoraApi
     */
    public $api;

    // todo: these should all be replaced with factories.
    protected $queryClass = RepositoryQuery::class;
    protected $newObjectClass = NewFedoraObject::class;
    protected $objectClass = FedoraObject::class;

    /**
     * Constructor for the FedoraRepository Object.
     *
     * @param \Islandora\Tuque\Api\FedoraApi $api
     *   An instantiated FedoraAPI which will be used to connect to the
     *   repository.
     * @param \Islandora\Tuque\Cache\AbstractCache $cache
     *   An instantiated AbstractCache which will be used to cache fedora objects.
     */
    public function __construct(FedoraApi $api, AbstractCache $cache)
    {
        $this->api = $api;
        $this->cache = $cache;
        $this->ri = new $this->queryClass($this->api->guzzleClient);
    }

    /**
     * {@inheritdoc}
     * @todo this needs to be implemented!
     */
    public function findObjects(array $search)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function constructObject($id = null, $create_uuid = false)
    {
        $exploded_id = explode(':', $id);
        // If no namespace or PID provided.
        if (!$id) {
            $id = $this->getNextIdentifier(null, $create_uuid);
        } // If namespace is provided.
        elseif (count($exploded_id) == 1) {
            $id = $this->getNextIdentifier($exploded_id[0], $create_uuid);
        }
        // If a full PID is provided we fall through to this.
        return new $this->newObjectClass($id, $this);
    }

    /**
     * @todo validate the ID
     * @todo catch the getNextPid errors
     *
     * {@inheritdoc}
     */
    public function getNextIdentifier($namespace = null, $create_uuid = false, $number_of_identifiers = 1)
    {
        $pids = [];

        if ($create_uuid) {
            if (is_null($namespace)) {
                $repository_info = $this->api->a->describeRepository();
                $namespace = $repository_info['repositoryPID']['PID-namespaceIdentifier'];
            }
            if ($number_of_identifiers > 1) {
                for ($i = 1; $i <= $number_of_identifiers; $i++) {
                    $pids[] = $namespace . ':' . $this->getUuid();
                }
            } else {
                $pids = $namespace . ':' . $this->getUuid();
            }
        } else {
            $pids = $this->api->m->getNextPid($namespace, $number_of_identifiers);
        }

        return $pids;
    }

    /**
     * This method will return a valid UUID based on V4 methods.
     *
     * @return string
     *   A valid V4 UUID.
     */
    protected function getUuid()
    {
        $bytes = openssl_random_pseudo_bytes(2);
        $add_mask = hex2bin('4000');
        $negate_mask = hex2bin('C000');
        // Make start with 11.
        $manipulated_bytes = $bytes | $negate_mask;
        // Make start with 01.
        $manipulated_bytes = $manipulated_bytes ^ $add_mask;
        $hex_string_10 = bin2hex($manipulated_bytes);

        return sprintf(
            '%08s-%04s-4%03s-%s-%012s',
            bin2hex(openssl_random_pseudo_bytes(4)),
            bin2hex(openssl_random_pseudo_bytes(2)),
            // Four most significant bits holds version number 4.
            substr(bin2hex(openssl_random_pseudo_bytes(2)), 1),
            // Two most significant bits holds zero and one for variant DCE1.1
            $hex_string_10,
            bin2hex(openssl_random_pseudo_bytes(6))
        );
    }

    /**
     * {@inheritdoc}
     * @todo error handling
     */
    public function ingestObject(NewFedoraObject &$object)
    {
        // We want all the managed datastreams to be uploaded.
        foreach ($object as $ds) {
            if ($ds->controlGroup == 'M') {
                $temp = tempnam(sys_get_temp_dir(), 'tuque');
                $return = $ds->getContent($temp);
                if ($return === true) {
                    $url = $this->api->m->upload($temp);
                    $ds->setContentFromUrl($url);
                }
                unlink($temp);
            }
        }

        $dom = new FoxmlDocument($object);
        $xml = $dom->saveXml();
        $id = $this->api->m->ingest(['string' => $xml, 'logMessage' => $object->logMessage]);
        $object = new $this->objectClass($id, $this);
        $this->cache->set($id, $object);
        return $object;
    }

    /**
     * {@inheritdoc}
     * @todo perhaps we should check if an object exists instead of catching the exception
     */
    public function getObject($id)
    {
        $object = $this->cache->get($id);
        if ($object !== false) {
            return $object;
        }

        try {
            $object = new $this->objectClass($id, $this);
            $this->cache->set($id, $object);
            return $object;
        } catch (RepositoryException $e) {
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function purgeObject($id)
    {
        try {
            $this->api->m->purgeObject($id);
            $object = $this->cache->get($id);
            if ($object !== false) {
                return $this->cache->delete($id);
            }
        } catch (RepositoryException $e) {
            // @todo chain exceptions here.
            throw $e;
        }
        return null;
    }
}
