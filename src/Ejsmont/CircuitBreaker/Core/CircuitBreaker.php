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

/**
 * Allows user code to track avability of any service by serviceName.
 * 
 * @see Ejsmont\CircuitBreaker\CircuitBreakerInterface
 * @package Ejsmont\CircuitBreaker\Components
 */
class CircuitBreaker implements CircuitBreakerInterface {
    /*
     * @var Zend_CircuitBreaker_Storage_Interface used to load/save statistics
     */

    protected $storageAdapter;

    /*
     * array of settings per service name (how many failures to allow etc)
     */
    protected $settings;

    public function __construct(StorageInterface $storageAdapter, $config) {
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

    // ---------------------- Directly accessed by interface methods -----------------

    protected function getMaxFailures($serviceName) {
        return $this->getSetting($serviceName, 'maxFailures');
    }

    protected function getRetryTimeout($serviceName) {
        return $this->getSetting($serviceName, 'retryTimeout');
    }

    protected function getFailures($serviceName) {
        //FIXME - catch exceptions and replace inplementation if occur
        return (int) $this->storageAdapter->loadStatus($serviceName, 'failures');
    }

    protected function getLastTest($serviceName) {
        //FIXME - catch exceptions and replace inplementation if occur
        return (int) $this->storageAdapter->loadStatus($serviceName, 'lastTest');
    }

    protected function setFailures($serviceName, $newValue) {
        //FIXME - catch exceptions and replace inplementation if occur
        $this->storageAdapter->saveStatus($serviceName, 'failures', $newValue, false);
        // make sure storage adapter flushes changes this time
        $this->storageAdapter->saveStatus($serviceName, 'lastTest', time(), true);
    }

    // ----------------------- INTERFACE IMPLEMENTATION -----------------------------

    /*
     * @see Zend_CircuitBreaker_Interface
     */
    public function isAvailable($serviceName) {
        $failures = $this->getFailures($serviceName);
        $maxFailures = $this->getMaxFailures($serviceName);
        if ($failures < $maxFailures) {
            // this is what happens most of the time so we evaluate first
            return true;
        } else {
            $lastTest = $this->getLastTest($serviceName);
            $retryTimeout = $this->getRetryTimeout($serviceName);
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
                $this->setFailures($serviceName, $failures);
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
        $this->setFailures($serviceName, $this->getFailures($serviceName) + 1);
    }

    /*
     * @see Zend_CircuitBreaker_Interface
     */

    public function reportSuccess($serviceName) {
        $failures = $this->getFailures($serviceName);
        $maxFailures = $this->getMaxFailures($serviceName);
        if ($failures > $maxFailures) {
            // there were more failures than max failures
            // we have to reset failures count to max-1
            $this->setFailures($serviceName, $maxFailures - 1);
        } elseif ($failures > 0) {
            // if we are between max and 0 we decrease by 1 on each
            // success so we will go down to 0 after some time
            // but we are still more sensitive to failures
            $this->setFailures($serviceName, $failures - 1);
        } else {
            // if there are no failures reported we do not
            // have to do anything on success (system operational)
        }
    }

}

?>