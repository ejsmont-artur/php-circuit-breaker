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

namespace Ejsmont\CircuitBreaker;

use Ejsmont\CircuitBreaker\Core\CircuitBreaker;
use Ejsmont\CircuitBreaker\Storage\Adapter\ApcuAdapter;
use Ejsmont\CircuitBreaker\Storage\Adapter\DummyAdapter;
use Ejsmont\CircuitBreaker\Storage\Adapter\MemcachedAdapter;
use Ejsmont\CircuitBreaker\Storage\Decorator\ArrayDecorator;

/**
 * Allows easy assembly of circuit breaker instances.
 * 
 * @see Ejsmont\CircuitBreaker\CircuitBreakerInterface
 * @package Ejsmont\CircuitBreaker\PublicApi 
 */
class Factory {

    /**
     * Creates a circuit breaker with same settings for all services using raw APC cache key.
     * APC raw adapter is faster than when wrapped with array decorator as APC uses direct memory access.
     * 
     * @param int   $maxFailures    how many times do we allow service to fail before considering it offline
     * @param int   $retryTimeout   how many seconds should we wait before attempting retry
     * 
     * @return CircuitBreakerInterface 
     */
    public static function getSingleApcInstance($maxFailures = 20, $retryTimeout = 30) {
        $storage = new ApcuAdapter();
        return new CircuitBreaker($storage, $maxFailures, $retryTimeout);
    }

    /**
     * Creates a circuit breaker using php array() as storage.
     * This instance looses the state when script execution ends. 
     * Useful for testing and/or extremely long running backend scripts.
     *
     * @param int   $maxFailures    how many times do we allow service to fail before considering it offline
     * @param int   $retryTimeout   how many seconds should we wait before attempting retry
     * 
     * @return CircuitBreakerInterface 
     */
    public static function getDummyInstance($maxFailures = 20, $retryTimeout = 30) {
        $storage = new DummyAdapter();
        return new CircuitBreaker($storage, $maxFailures, $retryTimeout);
    }

    /**
     * Creates a circuit breaker with same settings for all services using memcached instance as a backend
     *
     * @param Memcached $memcached      instance of a connected Memcached object
     * @param int       $maxFailures    how many times do we allow service to fail before considering it offline
     * @param int       $retryTimeout   how many seconds should we wait before attempting retry
     * 
     * @return CircuitBreakerInterface 
     */
    public static function getMemcachedInstance(\Memcached $memcached, $maxFailures = 20, $retryTimeout = 30) {
        $storage = new ArrayDecorator(new MemcachedAdapter($memcached));
        return new CircuitBreaker($storage, $maxFailures, $retryTimeout);
    }

}
