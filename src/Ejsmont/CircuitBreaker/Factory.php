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
use Ejsmont\CircuitBreaker\Storage\StorageInterface;
use Ejsmont\CircuitBreaker\Storage\Adapter\ApcAdapter;
use Ejsmont\CircuitBreaker\Storage\Adapter\DummyAdapter;


/**
 * Allows easy assembly of circuit breaker instances.
 * 
 * @see Ejsmont\CircuitBreaker\CircuitBreaker
 * @package Ejsmont\CircuitBreaker\PublicApi 
 */
class Factory {
    /**
     * Factory creates Circuit breaker for test purposes, dummy storage adapter
     */

    const DUMMY_ADAPTER = 'DummyAdapterNotSavingValues';
    /*
     * Facotry will create instance of APC storage adapter
     */
    const APC_ADAPTER = 'ApcAdapter';

    /**
     * @var Ejsmont\CircuitBreaker\Storage\StorageInterface used to load/save statistics
     */
    protected $storageAdapter;

    /**
     * $var array[] of settings per service name (how many failures to allow etc)
     */
    protected $settings;

    /**
     * Convinience method allowing you to create CircuitBreaker with different types of storage engine
     * Allowed parameters:
     * 		Zend_CircuitBreaker_Storage_Interface
     * 		Zend_Cache_Backend_Interface
     * 		Zend_Cache_Core
     *  	self::DUMMY_ADAPTER - will create dummy test adapter
     *  	self::APC_ADAPTER	- will create lightweight APC implementation with 1h TTL
     *  
     *  @throws \Exception if parameter does not allow to create working instance
     *  
     *  @todo - make sure all instances can be created, allow config objects, set config format, allow defaults
     */
    public static function getInstance($storage = false, $config = false) {
        if (!is_array($config)) {
            $config = array();
        }
        if (is_object($storage)) {
            if ($storage instanceof StorageInterface) {
                // provided storage implementing instance
                return new CircuitBreaker($storage, $config);
            }
        } elseif ($adapter == self::DUMMY_ADAPTER) {
            return new self(new DummyAdapter(), $config);
        } elseif ($adapter == self::APC_ADAPTER) {
            return new self(new ApcAdapter(), $config);
        }
        throw new \Exception("CircuitBreaker Incorrect argument.");
    }

    private function __construct(Storage $storageAdapter, $config) {
        $this->storageAdapter = $storageAdapter;
        // load settings for all configured service names
        foreach ($config as $serviceName => $settings) {
            $this->addServiceSettings($serviceName, $config);
        }
    }

    // -------------------------- PRIVATE HELPERS ---------------------------------
    // load value from config obj or array, if no config passed set defaults
    private function addServiceSettings($serviceName, $config = array()) {
        $newSet = array('maxFailures' => 20,
            'retryTimeout' => 60);

        // if passed array with settings
        if (is_array($config)) {
            if (isset($config[$serviceName]['maxFailures'])) {
                $newSet['maxFailures'] = $config[$serviceName]['maxFailures'];
            }
            if (isset($config[$serviceName]['retryTimeout'])) {
                $newSet['retryTimeout'] = $config[$serviceName]['retryTimeout'];
            }
        }

        // if passes config object with settings
        //FIXME - implement

        $this->settings[$serviceName] = $newSet;
    }

    private function getSetting($serviceName, $variable) {
        // make sure there are settings for the service
        if (!isset($this->settings[$serviceName])) {
            $this->addServiceSettings($serviceName);
        }
        return $this->settings[$serviceName][$variable];
    }

  


}
