<?php
/**
 * Class MSMemcache
 *
 * @author PhpGame
 */

class MSMemcache
{
    private $keyPrefix;
    private $memcache;
    private $smartForceRefresh;

    public function __construct($address, $port, $keyPrefix)
    {
        $this->memcache = new Memcache();
        $this->memcache->pconnect($address, $port);
        $this->keyPrefix = $keyPrefix;
        $this->smartForceRefresh = false;
    }

    protected function makeRealKey($key)
    {
        if(is_array($key))
            $key = http_build_query ($key);
        if(strlen($key) > 250)
            SystemErrorLog (MS_LOG_WARNING, 'memcache', "Key [$key] is longer than 250");
        return $this->keyPrefix . $key;
    }

    /**
     * Retrieve item from the server
     * @param key string
     * The key or array of keys to fetch.
     * @param flags int[optional]
     * If present, flags fetched along with the values will be written to this parameter. These
     * flags are the same as the ones given to for example Memcache::set.
     * The lowest byte of the int is reserved for pecl/memcache internal usage (e.g. to indicate
     * compression and serialization status).
     * @return string the string associated with the key or
     * false on failure or if such key was not found.
     */
    public function get($key)
    {
        return $this->memcache->get($this->makeRealKey($key));
    }

    public function setKeyPrefix($keyPrefix)
    {
        $this->keyPrefix = $keyPrefix;
    }

    /**
     * Store data at the server
     * @param key string
     * The key that will be associated with the item.
     * @param value mixed
     * The variable to store. Strings and integers are stored as is, other
     * types are stored serialized.
     * @param flag int[optional]
     * Use MEMCACHE_COMPRESSED to store the item
     * compressed (uses zlib).
     * @param expire int[optional]
     * Expiration time of the item. If it's equal to zero, the item will never
     * expire. You can also use Unix timestamp or a number of seconds starting
     * from current time, but in the latter case the number of seconds may not
     * exceed 2592000 (30 days).
     * @return bool Returns true on success or false on failure.
     */
    public function set($key, $value, $ttl)
    {
        $done = $this->memcache->set($this->makeRealKey($key), $value, 0, $ttl);
        return $done;
    }

    /**
     * Delete item from the server
     * @param key string
     * The key associated with the item to delete.
     * @param timeout int[optional]
     * This deprecated parameter is not supported, and defaults to 0 seconds.
     * Do not use this parameter.
     * @return bool Returns true on success or false on failure.
     */
    public function delete($key)
    {
        return $this->memcache->delete($this->makeRealKey($key));
    }

    /**
     * Increment item's value
     * @param key string
     * Key of the item to increment.
     * @param value int[optional]
     * Increment the item by value.
     * @return int new items value on success &return.falseforfailure;.
     */
    public function increase($key, $value)
    {
        return $this->memcache->increment($this->makeRealKey($key), $value);
    }

    /**
     * Decrement item's value
     * @param key string
     * Key of the item do decrement.
     * @param value int[optional]
     * Decrement the item by value.
     * @return int item's new value on success&return.falseforfailure;.
     */
    public function decrease($key, $value)
    {
        return $this->memcache->decrement($this->makeRealKey($key), $value);
    }

    /**
     * Flush all existing items at the server
     * @return bool Returns true on success or false on failure.
     */
    public function clear()
    {
        $this->memcache->flush();
    }

    static public function GetInstance($serverName = 'default', $keyPrefix = '')
    {
        $config = Registry::get('serverConfig');
        return new MSMemcache(
                $config['MemcachedServer'][$serverName]['host'],
                $config['MemcachedServer'][$serverName]['port'],
                $keyPrefix
        );
    }
}
