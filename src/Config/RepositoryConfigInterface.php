<?php

namespace Islandora\Tuque\Config;

/**
 * The general interface for a RepositoryConfig object.
 */
interface RepositoryConfigInterface
{
    /**
     * Simple constructor definition for the repository
     *
     * @param string $url
     * @param string $username
     * @param string $password
     */
    public function __construct($url, $username, $password);
}
