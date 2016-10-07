<?php

namespace Tests\Unit\Ejsmont\CircuitBreaker;

use Ejsmont\CircuitBreaker\Factory;
use Ejsmont\CircuitBreaker\CircuitBreakerInterface;

class FactoryTest extends \PHPUnit_Framework_TestCase {

    public function testThreshold() {
        if(!function_exists('apc_clear_cache')){
            $this->markTestSkipped("APC not installed");
        }

        if(ini_get('apc.enable_cli') === "0") {
            $this->markTestSkipped("APC not enabled for CLI");
        }
        apc_clear_cache('user');
    
        $factory = new Factory();
        $cb = $factory->getSingleApcInstance(3);

        $this->assertTrue($cb instanceof CircuitBreakerInterface);

        $this->assertEquals(true, $cb->isAvailable('someSample'));
        $cb->reportFailure('someSample');
        $this->assertEquals(true, $cb->isAvailable('someSample'));
        $cb->reportFailure('someSample');
        $this->assertEquals(true, $cb->isAvailable('someSample'));

        $cb->reportFailure('someSample');
        $this->assertEquals(false, $cb->isAvailable('someSample'));

        $cb->reportFailure('someSample');
        $this->assertEquals(false, $cb->isAvailable('someSample'));
    }

    public function testDummy() {
        $factory = new Factory();
        $cb = $factory->getDummyInstance(3, 44);

        $this->assertTrue($cb instanceof CircuitBreakerInterface);
    }

    public function testMemcachedArray() {
        if (!class_exists('\Memcached')) {
            $this->markTestSkipped("extension not loaded");
        }
        $this->_connection = new \Memcached();
        $this->_connection->addServer("localhost", 11211);

        $factory = new Factory();
        $cb = $factory->getMemcachedInstance($this->_connection, 3, 1);

        $this->assertTrue($cb instanceof CircuitBreakerInterface);

        // service with multiple failures
        $this->assertTrue($cb->isAvailable("serviceOne"));
        $cb->reportFailure("serviceOne");
        $cb->reportFailure("serviceOne");
        $cb->reportFailure("serviceOne");
        $cb->reportFailure("serviceOne");
        $this->assertFalse($cb->isAvailable("serviceOne"));

        // ervice seen first time = ok
        $this->assertTrue($cb->isAvailable("serviceUnknown"));

        // service with one failure is ok
        $cb->reportSuccess("serviceTwo");
        $this->assertTrue($cb->isAvailable("serviceTwo"));

        // service with successes is ok
        $cb->reportFailure("serviceFour");
        $cb->reportFailure("serviceFour");
        $cb->reportFailure("serviceFour");
        $cb->reportFailure("serviceFour");
        $cb->reportSuccess("serviceFour");
        $cb->reportSuccess("serviceFour");
        $this->assertTrue($cb->isAvailable("serviceFour"));
    }

}