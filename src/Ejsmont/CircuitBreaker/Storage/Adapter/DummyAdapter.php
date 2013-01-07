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

use Ejsmont\CircuitBreaker\Storage\StorageInterface;

/**
 * Does not really persist stats between requests!
 * 
 * Can only be used for tests and as fallback instance. 
 * 
 * When real storage handler throws exception it means it cant be used any more.
 * Then storage user can safely fallback to this dummy instance.
 * 
 * @see Ejsmont\CircuitBreaker\Storage\StorageInterface
 * @package Ejsmont\CircuitBreaker\Components
 */
class DummyAdapter implements StorageInterface {

    /**
     * @var mixed[] Array of all the values (transient)
     */
    protected $data = array();

    /**
     * Loads circuit breaker service status value.
     * 
     * @param 	string  $serviceName   name of service to load stats for
     * @param 	string  $attributeName name of attribute to load
     * @return 	string  value stored or '' if value was not found
     */
    public function loadStatus($serviceName, $attributeName) {
        if (isset($this->data[$serviceName][$attributeName])) {
            return $this->data[$serviceName][$attributeName];
        }
        return "";
    }

    /**
     * Saves circuit breaker service status value.
     * 
     * @param 	string  $serviceName   name of service to load stats for
     * @param 	string  $attributeName name of the attribute to load 
     * @param 	string  $value         string value loaded or '' if nothing found 
     * @param   boolean $flush         set to true will force immediate save, false does not guaranteed saving at all.
     * @return 	void
     */
    public function saveStatus($serviceName, $attributeName, $value, $flush = false) {
        $this->data[$serviceName][$attributeName] = $value;
    }

}