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

namespace Ejsmont\CircuitBreaker\Storage;

use Ejsmont\CircuitBreaker\Storage\StorageException;

/**
 * Interface defines methods for storage adapters/decorators for the circuit breaker persistance.
 *
 * Minimalistic interface to allow easy integration with any persistance backend.
 * 
 * 
 * @package Ejsmont\CircuitBreaker\Components
 */
interface StorageInterface {

    /**
     * Loads circuit breaker service status value.
     * For example failures count or last retry time.
     * Method does not care what are the attribute names. They are not inspected.
     * Any string can be passed as service name and attribute name.
     * 
     * @param 	string  $serviceName   name of service to load stats for
     * @param 	string  $attributeName name of attribute to load
     * @return 	string  value stored or '' if value was not found
     *  
     * @throws Ejsmont\CircuitBreaker\Storage\StorageException if storage error occurs, handler can not be used
     */
    public function loadStatus($serviceName, $attributeName);

    /**
     * Saves circuit breaker service status value.
     * Method does not care what are the attribute names. They are not inspected.
     * Any string can be passed as service name and attribute name, value can be int/string.
     * 
     * Saving in storage is not guaranteed unless flush is set to true.
     * Use calls without flush if you know you will update more than one value and you want to
     * improve performance of the calls.
     * 
     * @param 	string  $serviceName   name of service to load stats for
     * @param 	string  $attributeName name of the attribute to load 
     * @param 	string  $value         string value loaded or '' if nothing found 
     * @param   boolean $flush         set to true will force immediate save, false does not guaranteed saving at all.
     * @return 	void
     * 
     * @throws Ejsmont\CircuitBreaker\Storage\StorageException if storage error occurs, handler can not be used
     */
    public function saveStatus($serviceName, $attributeName, $value, $flush = false);
}
