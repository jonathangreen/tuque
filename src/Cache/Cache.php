<?php

namespace Islandora\Tuque\Cache;

/**
 * This is a simple cache that uses a static array to hold the cached values.
 * This means that it will cache across instantiations in the same PHP runtime
 * but not across runtimes. The cache has 100 slots and uses a simple LIFO
 * caching strategy.
 *
 * @todo Replace this with something more interesting like memcached
 * @todo Try some other intersting caching strategies like LRU.
 */
class SimpleCache extends AbstractCache
{
    const CACHESIZE = 100;

    protected static $cache = array();
    protected static $entries = array();
    protected static $size = SimpleCache::CACHESIZE;

    /**
     * Set the cache size for the cache. If the size if bigger the cache size
     * is just made bigger. If its smaller, the cache is flushed and the cache
     * size is made smaller.
     *
     * @param int $size
     *   The new size of the cache.
     */
    public static function setCacheSize($size)
    {
        if ($size > self::$size) {
            self::$size = $size;
        } else {
            self::$cache = array();
            self::$entries = array();
            self::$size = $size;
        }
    }

    /**
     * Reset the cache flushing it and returning it to its default size.
     */
    public static function resetCache()
    {
        self::$cache = array();
        self::$entries = array();
        self::$size = self::CACHESIZE;
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function add($key, $data)
    {
        if ($this->get($key) !== false) {
            return false;
        }
        self::$cache[$key] = $data;
        $num = array_push(self::$entries, $key);

        if ($num > self::$size) {
            $evictedkey = array_shift(self::$entries);
            unset(self::$cache[$evictedkey]);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $data)
    {
        if ($this->add($key, $data) === false) {
            self::$cache[$key] = $data;
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        if (!array_key_exists($key, self::$cache)) {
            return false;
        }
        $entrykey = array_search($key, self::$entries);
        unset(self::$cache[$key]);
        unset(self::$entries[$entrykey]);
        return true;
    }
}
