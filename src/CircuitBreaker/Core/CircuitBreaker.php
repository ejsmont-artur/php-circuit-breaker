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

namespace Ejsmont\CircuitBreaker\Core;

use Ejsmont\CircuitBreaker\CircuitBreakerInterface;
use Ejsmont\CircuitBreaker\Storage\StorageInterface;
use Ejsmont\CircuitBreaker\TrippedHandlerInterface;

/**
 * Allows user code to track availability of any service by serviceName.
 * 
 * @see Ejsmont\CircuitBreaker\CircuitBreakerInterface
 * @package Ejsmont\CircuitBreaker\Components
 */
class CircuitBreaker implements CircuitBreakerInterface {

    /**
     * @var CircuitBreakerInterface used to load/save availability statistics
     */
    protected $storageAdapter;

    /**
     * @var int default threshold, if service fails this many times will be disabled 
     */
    protected $defaultMaxFailures;

    /**
     * @var int  how many seconds should we wait before retry 
     */
    protected $defaultRetryTimeout;

    /**
     * Array with configuration per service name, format:
     *  array(
     *      "serviceName1" => array('maxFailures' => X, 'retryTimeout => Y),
     *      "serviceName2" => array('maxFailures' => X, 'retryTimeout => Y),
     *  )
     * 
     * @var array[] settings per service name
     */
    protected $settings = array();

    /**
     * Array of TrippedHandlerInterfaces
     * @var array
     */
    protected $tripHandler = array();

    /**
     * @var string
     */
    protected $unavailableMessage = "Service No Longer Available";

    /**
     * @var string
     */
    protected $retryMessage = "Retrying Service";
    
    /**
     * Configure instance with storage implementation and default threshold and retry timeout.
     *
     * @param StorageInterface $storage      storage implementation
     * @param int              $maxFailures  default threshold, if service fails this many times will be disabled
     * @param int              $retryTimeout how many seconds should we wait before retry
     */
    public function __construct(StorageInterface $storage, $maxFailures = 20, $retryTimeout = 60) {
        $this->storageAdapter = $storage;
        $this->defaultMaxFailures = (int)$maxFailures;
        $this->defaultRetryTimeout = (int)$retryTimeout;
    }

    /**
     * Register a Handler for a Service
     * @param string $serviceName
     * @param TrippedHandlerInterface $handlerInterface
     */
    public function registerHandler($serviceName, TrippedHandlerInterface $handlerInterface) {
        $this->tripHandler[(string)$serviceName] = $handlerInterface;
    }

    /**
     * @return string
     */
    public function getUnavailableMessage()
    {
        return $this->unavailableMessage;
    }

    /**
     * @param string $unavailableMessage
     */
    public function setUnavailableMessage($unavailableMessage)
    {
        $this->unavailableMessage = $unavailableMessage;
    }

    /**
     * @return string
     */
    public function getRetryMessage()
    {
        return $this->retryMessage;
    }

    /**
     * @param string $retryMessage
     */
    public function setRetryMessage($retryMessage)
    {
        $this->retryMessage = $retryMessage;
    }



    /**
     * Use this method only if you want to add server specific threshold and retry timeout.
     *
     * @param String           $serviceName  service
     * @param int              $maxFailures  default threshold, if service fails this many times will be disabled
     * @param int              $retryTimeout how many seconds should we wait before retry
     * @return CircuitBreaker
     */
    public function setServiceSettings($serviceName, $maxFailures, $retryTimeout) {
        $this->settings[(string)$serviceName] = array(
            'maxFailures' => $maxFailures ? (int)$maxFailures : $this->defaultMaxFailures,
            'retryTimeout' => $retryTimeout ? (int)$retryTimeout : $this->defaultRetryTimeout,
        );
        return $this;
    }

    // ---------------------- HELPERS -----------------

    /**
     * Load setting or initialise service name with defaults for faster lookups
     * 
     * @param string $serviceName   what service to look for
     * @param string $variable      what setting to look for
     * @return int 
     */
    private function getSetting($serviceName, $variable) {
        // make sure there are settings for the service
        if (!isset($this->settings[(string)$serviceName])) {
            $this->settings[(string)$serviceName] = array(
                'maxFailures' => $this->defaultMaxFailures,
                'retryTimeout' => $this->defaultRetryTimeout,
            );
        }
        return $this->settings[(string)$serviceName][$variable];
    }

    // ---------------------- Directly accessed by interface methods -----------------

    protected function getMaxFailures($serviceName) {
        return $this->getSetting((string)$serviceName, 'maxFailures');
    }

    protected function getRetryTimeout($serviceName) {
        return $this->getSetting((string)$serviceName, 'retryTimeout');
    }

    protected function getFailures($serviceName) {
        //FIXME - catch exceptions and replace inplementation if occur
        return (int) $this->storageAdapter->loadStatus((string)$serviceName, 'failures');
    }

    protected function getLastTest($serviceName) {
        //FIXME - catch exceptions and replace inplementation if occur
        return (int) $this->storageAdapter->loadStatus((string)$serviceName, 'lastTest');
    }

    /**
     * TODO Remove reference to time() replace with DateTime object
     * @param $serviceName
     * @param $newValue
     */
    protected function setFailures($serviceName, $newValue) {
        //FIXME - catch exceptions and replace inplementation if occur
        $this->storageAdapter->saveStatus((string)$serviceName, 'failures', (int)$newValue, false);
        // make sure storage adapter flushes changes this time
        $this->storageAdapter->saveStatus((string)$serviceName, 'lastTest', time(), true);
    }

    // ----------------------- INTERFACE IMPLEMENTATION -----------------------------

    /*
     * @see Zend_CircuitBreaker_Interface
     */
    public function isAvailable($serviceName) {
        $failures = (int)$this->getFailures((string)$serviceName);
        $maxFailures = (int)$this->getMaxFailures((string)$serviceName);
        if ($failures < $maxFailures) {
            // this is what happens most of the time so we evaluate first
            return true;
        } else {

            // This code block will execute a handler for tripping
            // Like the code block below there is still a race condition present so it will be possible for this code to
            // execute twice or more on extremely busy systems so please keep this in mind.
            if ($failures == $maxFailures && isset($this->tripHandler[(string)$serviceName])) {
                /** @var $handler TrippedHandlerInterface */
                $handler = $this->tripHandler[(string)$serviceName];
                $handler($serviceName, $failures, $this->unavailableMessage);
            }

            $lastTest = $this->getLastTest((string)$serviceName);
            $retryTimeout = $this->getRetryTimeout((string)$serviceName);
            if ($lastTest + $retryTimeout < time()) {
                // Once we wait $retryTimeout, we have to allow one 
                // thread to try to connect again. To prevent all other threads
                // from flooding, the potentially dead db, we update the time first
                // and then try to connect. If db is dead only one thread will hang
                // waiting for the connection. Others will get updated timeout from stats.
                // 
                // 'Race condition' is between first thread getting into this line and
                // time it takes to store the settings. In that time other threads will
                // also be entering this statement. Even on very busy servers it 
                // wont allow more than a few requests to get through before stats are updated.
                //
                // updating lastTest
                $this->setFailures((string)$serviceName, $failures);

                //Lets handle the retry
                if (isset($this->tripHandler[(string)$serviceName])) {
                    $handler = $this->tripHandler[(string)$serviceName];
                    $handler($serviceName, $failures, $this->retryMessage);
                }

                // allowing this thread to try to connect to the resource
                return true;
            } else {
                return false;
            }
        }
    }

    /*
     * @see Zend_CircuitBreaker_Interface
     */

    public function reportFailure($serviceName) {
        // there is no science here, we always increase failures count
        $this->setFailures((string)$serviceName, $this->getFailures((string)$serviceName) + 1);
    }

    /*
     * @see Zend_CircuitBreaker_Interface
     */

    public function reportSuccess($serviceName) {
        $failures = $this->getFailures((string)$serviceName);
        $maxFailures = $this->getMaxFailures((string)$serviceName);
        if ($failures > $maxFailures) {
            // there were more failures than max failures
            // we have to reset failures count to max-1
            $this->setFailures((string)$serviceName, $maxFailures - 1);
        } elseif ($failures > 0) {
            // if we are between max and 0 we decrease by 1 on each
            // success so we will go down to 0 after some time
            // but we are still more sensitive to failures
            $this->setFailures((string)$serviceName, $failures - 1);
        } else {
            // if there are no failures reported we do not
            // have to do anything on success (system operational)
        }
    }

    /**
     * Quick and dirty way to use the breaker
     * 
     * @param $serviceName
     * @param \Closure $code
     * @param \Closure $failed
     */
    public function attempt($serviceName, \Closure $code, \Closure $failed) {
        if ($this->isAvailable((string)$serviceName)) {
            try {
                $code();
                $this->reportSuccess((string)$serviceName);
            } catch (\Exception $e) {
                $this->reportFailure((string)$serviceName);
                $failed();
            }
        } else {
            $failed();
        }
    }
}
