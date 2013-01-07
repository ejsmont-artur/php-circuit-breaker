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

namespace Ejsmont\CircuitBreaker\Storage\Decorator;

use Ejsmont\CircuitBreaker\Storage\StorageInterface;

/**
 * Decorator decorating Zend_CircuitBreaker_Storage_Interface
 * So that all the service status data can be aggregated into one array.
 * Especially useful if you are using remote storage like memcache.
 * Otherwise every service counter/time would have to be loaded separately.
 * Decorator will group updates until flush is requested.
 * On save flush decorator will load stats again and update them with new values.
 * 
 * @package PublicApi 
 */
class ArrayDecorator implements StorageInterface {
    /*
     * Storage handler that will be used to load and save the aggregated array.
     * @var Zend_CircuitBreaker_Storage_Interface
     */

    protected $instance;

    /*
     * Array of agregated service stats loaded from storage handler
     * @var array
     */
    protected $stats = false;

    /*
     * Array of service stats that have been updated since last flush
     * or since script processing began.
     * @var array
     */
    protected $dirtyStats = array();

    /*
     * @param Zend_CircuitBreaker_Storage_Interface $wrappedInstance instance of storage interface that will be used
     */

    public function __construct(Storage $wrappedInstance) {
        $this->instance = $wrappedInstance;
    }

    /*
     * Method that actually loads the stats array from wrapped instance
     * Loads stats only if need to be loaded.
     * @return void
     */

    protected function loadStatsArray() {
        if (!is_array($this->stats)) {
            $stats = $this->instance->loadStatus("CircuitBreakerStats", "AggregatedStats");
            if (!empty($stats)) {
                $this->stats = unserialize($stats);
            }
            // make sure unserialize and load were successfull and we have array
            if (!is_array($this->stats)) {
                $this->stats = array();
            }
        }
    }

    /*
     * Method that actually saves the stats array in wrapped instance
     * @return void
     */

    protected function saveStatsArray() {
        if (is_array($this->stats)) {
            $this->instance->saveStatus("CircuitBreakerStats", "AggregatedStats", serialize($this->stats), true);
        }
    }

    /*
     * @see Zend_CircuitBreaker_Storage_Interface
     */

    public function loadStatus($serviceName, $attributeName) {
        // make sure we have the values loaded (request time cache of all service stats)
        $this->loadStatsArray();
        // return 
        if (isset($this->stats[$serviceName][$attributeName])) {
            return $this->stats[$serviceName][$attributeName];
        } else {
            return "";
        }
    }

    /*
     * We reload values just before saving to reduce race condition time.
     * The updated values are merged with previous values.
     * Then everything is stored back into the storage
     *
     * @see Zend_CircuitBreaker_Storage_Interface
     */

    public function saveStatus($serviceName, $attributeName, $value, $flush = false) {
        // if this service is unknown add it to dirty array
        $this->dirtyStats[$serviceName][$attributeName] = $value;

        if ($flush) {
            // reload stats
            $this->stats = false;
            $this->loadStatsArray();

            // merge all dirty stats into the oryginal array
            foreach ($this->dirtyStats as $service => $values) {
                foreach ($values as $name => $value) {
                    $this->stats[$service][$name] = $value;
                }
            }

            // force save
            $this->saveStatsArray();

            // next time we wont override these any more (they could change in the mean time)
            $this->dirtyStats = array();
        }
    }

}