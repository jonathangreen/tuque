<?php

namespace Islandora\Tuque\Cache;

/**
 * Simple abstract Cache defintion providing basic key value caching
 * functionality.
 */
abstract class AbstractCache
{
    /**
     * Add data to the cache.
     *
     * @param string $key
     *   The key to add to the cache.
     * @param mixed $data
     *   The data to store with the key.
     *
     * @return boolean
     *   TRUE if the data was added to the cache. FALSE if $key already exists in
     *   the cache or if there was an error.
     */
    abstract public function add($key, $data);

    /**
     * Retrieve data from the cache.
     *
     * @param string $key
     *   They key to retrieve from the cache.
     *
     * @return mixed
     *   FALSE if the data wasn't found in the cache. Otherwise it returns the
     *   data assoctiated with the key.
     */
    abstract public function get($key);

    /**
     * Set data in the cache.
     *
     * This will create new keys if they don't already exist, or update existing
     * keys.
     *
     * @param string $key
     *   The key to add/update.
     * @param mixed $data
     *   The data to store with the key.
     *
     * @return boolean
     *   TRUE on success. FALSE on failure.
     */
    abstract public function set($key, $data);

    /**
     * Delete key from the cache.
     *
     * @param string $key
     *   The key to delete.
     *
     * @return boolean
     *   TRUE if they key existed and was deleted. FALSE otherwise.
     */
    abstract public function delete($key);
}
