<?php

/**
 * This file is part of the php-circuit-breaker package.
 * 
 * @link https://github.com/ejsmont-artur/php-circuit-breaker
 * @link http://artur.ejsmont.org/blog/circuit-breaker
 * @author Mario Bittencourt
 *
 * For the full copyright and license information, please view the LICENSE file.
 */

namespace Ejsmont\CircuitBreaker\Storage\Adapter;

use Ejsmont\CircuitBreaker\Storage\StorageException;

/**
 * Reasonably useful implementation if you needed to share circuit breaker across servers.
 * It incurs the network connection penalty so for optimal performance APC or shared 
 * memory is preferred but if extra milliseconds are not an issue this
 * adapter could work well. Consider using array adapter to minimise redis calls.
 * 
 * @see Ejsmont\CircuitBreaker\Storage\StorageInterface
 * @package Ejsmont\CircuitBreaker\Components
 */
class RedisAdapter extends BaseAdapter {

    /**
     * @var \Redis
     */
    private $redis;

    /**
     * Prepare instance
     *
     * @param \Redis $redis
     * @param int $ttl
     * @param bool|false $cachePrefix
     */
    public function __construct(\Redis $redis, $ttl = 3600, $cachePrefix = false) {
        parent::__construct($ttl, $cachePrefix);
        $this->redis = $redis;
    }

    /**
     * Helper method to make sure that redis extension is loaded
     * 
     * @throws Ejsmont\CircuitBreaker\Storage\StorageException if memcached is not loaded
     * @return void
     */
    protected function checkExtension() {
        // nothing to do as you would not have \Redis instance in constructor if extension was not loaded
    }

    /**
     * Loads item by cache key.
     * 
     * @param string $key
     * @return mixed
     * 
     * @throws StorageException if storage error occurs, handler can not be used
     */
    protected function load($key) {
        try {
            return $this->redis->get($key);
        } catch (\Exception $e) {
            throw new StorageException("Failed to load redis key: $key", 1, $e);
        }
    }

    /**
     * Save item in the cache.
     * 
     * @param string $key
     * @param string $value
     * @param int $ttl
     * @return void
     * 
     * @throws StorageException if storage error occurs, handler can not be used
     */
    protected function save($key, $value, $ttl) {
        try {
            $this->redis->set($key, $value, $ttl);
        } catch (\Exception $e) {
            throw new StorageException("Failed to save redis key: $key", 1, $e);
        }
    }

}
