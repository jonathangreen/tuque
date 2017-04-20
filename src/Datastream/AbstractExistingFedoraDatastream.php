<?php

namespace Islandora\Tuque\Datastream;

use Islandora\Tuque\Object\FedoraObject;
use Islandora\Tuque\Repository\FedoraRepository;

/**
 * This abstract class defines some shared functionality between all classes
 * that implement exising fedora datastreams.
 */
abstract class AbstractExistingFedoraDatastream extends AbstractFedoraDatastream
{

    /**
     * Class constructor.
     *
     * @param string $id
     *   Unique identifier for the DS.
     * @param FedoraObject $object
     *   The FedoraObject that this DS belongs to.
     * @param FedoraRepository $repository
     *   The FedoraRepository that this DS belongs to.
     */
    public function __construct(
        $id,
        FedoraObject $object,
        FedoraRepository $repository
    ) {
        parent::__construct($id, $object, $repository);
    }

    /**
     * Wrapper for the APIA getDatastreamDissemination function.
     *
     * @param string $version
     *   The version of the content to retreve.
     * @param string $file
     *   The file to put the content into.
     *
     * @return string
     *   String containing the content.
     */
    protected function getDatastreamContent($version = null, $file = null)
    {
        return $this->repository->api->a->getDatastreamDissemination(
            $this->parent->id,
            $this->id,
            $version,
            $file
        );
    }

    /**
     * Wrapper around the APIM getDatastreamHistory function.
     *
     * @return array
     *   Array containing datastream history.
     */
    protected function getDatastreamHistory()
    {
        return $this->repository->api->m->getDatastreamHistory(
            $this->parent->id,
            $this->id
        );
    }

    /**
     * Wrapper around the APIM modifyDatastream function.
     *
     * @param array $args
     *   Args to pass to the function.
     *
     * @return array
     *   Datastream history array.
     */
    protected function modifyDatastream(array $args)
    {
        return $this->repository->api->m->modifyDatastream(
            $this->parent->id,
            $this->id,
            $args
        );
    }

    /**
     * Wrapper around the APIM Purge function.
     *
     * @param string $version
     *   The version to purge.
     *
     * @return array
     *   The versions purged.
     */
    protected function purgeDatastream($version)
    {
        return $this->repository->api->m->purgeDatastream(
            $this->parent->id,
            $this->id,
            ['startDT' => $version, 'endDT' => $version]
        );
    }
}
