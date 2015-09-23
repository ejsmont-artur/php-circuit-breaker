<?php

/**
 * This file is part of the php-circuit-breaker package.
 * 
 * @link https://github.com/ejsmont-artur/php-circuit-breaker
 * @link http://artur.ejsmont.org/blog/circuit-breaker
 * @author Artur Ejsmont
 *
 * For the full copyright and license information, please view the LICENSE file.
 */

namespace Ejsmont\CircuitBreaker\Storage\Adapter;

use Ejsmont\CircuitBreaker\Storage\Adapter\BaseAdapter;
use Ejsmont\CircuitBreaker\Storage\StorageException;

/**
 * Reasonably useful implementation if you needed to share circuit breaker across servers.
 * It incurs the network connection penalty so for optimal performance APC or shared 
 * memeory is preferred but if extra millisecods are not an issue this 
 * adapter could work well. Consider using array adapter to minimise memcache calls.
 * 
 * @see Ejsmont\CircuitBreaker\Storage\StorageInterface
 * @package Ejsmont\CircuitBreaker\Components
 */
class MemcachedAdapter extends BaseAdapter {

    /**
     * @var Memcached
     */
    private $memcached;

    /**
     * Prepare instance
     * 
     * @param Memcached $memcached
     */
    public function __construct(\Memcached $memcached, $ttl = 3600, $cachePrefix = false) {
        parent::__construct($ttl, $cachePrefix);
        $this->memcached = $memcached;
    }

    /**
     * Helper method to make sure that memcached extension is loaded
     * 
     * @throws Ejsmont\CircuitBreaker\Storage\StorageException if memcached is not loaded
     * @return void
     */
    protected function checkExtension() {
        // nothing to do as you would not have \Memcached instance in constructor if extension was not loaded
    }

    /**
     * Loads item by cache key.
     * 
     * @param string $key
     * @return mixed
     * 
     * @throws Ejsmont\CircuitBreaker\Storage\StorageException if storage error occurs, handler can not be used
     */
    protected function load($key) {
        try {
            return $this->memcached->get($key);
        } catch (\Exception $e) {
            throw new StorageException("Failed to load memcached key: $key", 1, $e);
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
     * @throws Ejsmont\CircuitBreaker\Storage\StorageException if storage error occurs, handler can not be used
     */
    protected function save($key, $value, $ttl) {
        try {
            $this->memcached->set($key, $value, $ttl);
        } catch (\Exception $e) {
            throw new StorageException("Failed to save memcached key: $key", 1, $e);
        }
    }

}
