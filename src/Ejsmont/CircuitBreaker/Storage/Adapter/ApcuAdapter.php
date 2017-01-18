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
 * RRecommended for soft migration to PHP7 from APC to APCu
 *
 * @see     StorageInterface
 * @package Ejsmont\CircuitBreaker\Components
 */
class ApcuAdapter extends BaseAdapter {

    /**
     * Configure instance
     * 
     * @param Integer $ttl          How long should circuit breaker data persist (between updates)
     * @param bool    $cachePrefix  Value has to be string. If empty default cache key prefix is used.
     */
    public function __construct($ttl = 3600, $cachePrefix = false) {
        parent::__construct($ttl, $cachePrefix);
    }

    /**
     * Helper method to make sure that APCu extension is loaded
     *
     * @throws StorageException
     */
    protected function checkExtension() {
        if (!function_exists("apcu_store")) {
            throw new StorageException("APCu extension not loaded.");
        }
    }

    /**
     * Loads item by cache key.
     * 
     * @param string $key
     * @return mixed
     * 
     * @throws StorageException
     */
    protected function load($key) {
        return apcu_fetch($key);
    }

    /**
     * Save item in the cache.
     *
     * @param string $key
     * @param string $value
     * @param int    $ttl
     * @throws StorageException
     */
    protected function save($key, $value, $ttl) {
        $result = apcu_store($key, $value, $ttl);
        if ($result === false) {
            throw new StorageException("Failed to save apc key: $key");
        }
    }

}