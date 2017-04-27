<?php

namespace Islandora\Tuque\Api;

use GuzzleHttp\Client;

/**
 * This is a simple class that brings FedoraApiM and FedoraApiA together.
 */
class FedoraApi
{

    /**
     * @var FedoraApiA
     */
    public $a;

    /**
     * @var FedoraApiM
     */
    public $m;

    /**
     * @var \GuzzleHttp\Client
     */
    public $guzzleClient;

    /**
     * Constructor for the FedoraApi object.
     *
     * @param Client $guzzleClient
     *   If one isn't provided a default one will be used.
     * @param \Islandora\Tuque\Api\FedoraApiSerializer $serializer
     *   If one isn't provided a default will be used.
     */
    public function __construct(
        Client $guzzleClient,
        FedoraApiSerializer $serializer = null
    ) {
        $this->a = new FedoraApiA($guzzleClient, $serializer);
        $this->m = new FedoraApiM($guzzleClient, $serializer);
        $this->guzzleClient = $guzzleClient;
    }
}
