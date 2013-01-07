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

/**
 * Allows user code to track avability/unavailability of any service by serviceName.
 * 
 * Circuit breaker counts each failure and once you reach limit it will skipp connection attempt with instant failure.
 * You can also set retry timeout per service. Then after retry timeout seconds CircuitBreaker will allow one
 * thread to try to connect to the service again. 
 *      - If thread fails CircuitBreaker waits till next retry timeout. 
 *      - If thread succeeds, more threads will be allowed to connect.
 * 
 * Main use case is to shield from remote service failures and be able to recover quickly with meaningful message.
 * 
 * Typical simplified user code would look like this:
 *      $result = false;
 *      if( $cb->isAvailable('myServiceName') ){
 *          if( HoweverYouConnectToTheService() ){
 *              $result = true;
 *              $cb->reportSuccess('myServiceName');
 *          }else{
 *              $cb->reportFailure('myServiceName');
 *          }
 *      }
 * 
 * @package Ejsmont\CircuitBreaker\PublicApi 
 */
interface CircuitBreakerInterface {

    /**
     * Check if service is available (according to CB knowledge)
     * 
     * @param string $serviceName - arbitrary service name 
     * @return boolean true if service is available, false if service is down
     */
    public function isAvailable($serviceName);

    /**
     * Use this method to let CB know that you failed to connect to the 
     * service of particular name.
     * 
     * Allows CB to update its stats accordingly for future HTTP requests.
     * 
     * @param string $serviceName - arbitrary service name 
     * @return void
     */
    public function reportFailure($serviceName);

    /**
     * Use this method to let CB know that you successfully connected to the 
     * service of particular name.
     * 
     * Allows CB to update its stats accordingly for future HTTP requests.
     * 
     * @param string $serviceName - arbitrary service name 
     * @return void
     */
    public function reportSuccess($serviceName);
}
