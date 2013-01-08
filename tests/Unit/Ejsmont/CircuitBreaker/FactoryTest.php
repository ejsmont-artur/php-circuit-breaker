<?php

namespace Tests\Unit\Ejsmont\CircuitBreaker;

use Ejsmont\CircuitBreaker\Factory;
use Ejsmont\CircuitBreaker\CircuitBreakerInterface;

class FactoryTest extends \PHPUnit_Framework_TestCase {

    protected function tearDown() {
        apc_clear_cache('user');
        parent::tearDown();
    }

    public function testThreshold() {
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

}