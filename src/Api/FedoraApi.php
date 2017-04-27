<?php

namespace Islandora\Tuque\Api;

use Islandora\Tuque\Connection\GuzzleConnection;

/**
 * This is a simple class that brings FedoraApiM and FedoraApiA together.
 */
class FedoraApi
{

    /**
     * Fedora APIA Class
     * @var FedoraApiA
     */
    public $a;

    /**
     * Fedora APIM Class
     * @var FedoraApiM
     */
    public $m;

    public $connection;

    /**
     * Constructor for the FedoraApi object.
     *
     * @param GuzzleConnection $connection
     *   (Optional) If one isn't provided a default one will be used.
     * @param \Islandora\Tuque\Api\FedoraApiSerializer $serializer
     *   (Optional) If one isn't provided a default will be used.
     */
    public function __construct(
        GuzzleConnection $connection,
        FedoraApiSerializer $serializer = null
    ) {
        if (!$serializer) {
            $serializer = new FedoraApiSerializer();
        }

        $this->a = new FedoraApiA($connection, $serializer);
        $this->m = new FedoraApiM($connection, $serializer);

        $this->connection = $connection;
    }
}
