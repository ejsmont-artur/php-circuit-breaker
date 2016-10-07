<?php

namespace Tests\Unit\Ejsmont\CircuitBreaker;

use Ejsmont\CircuitBreaker\Storage\Adapter\DummyAdapter;
use Ejsmont\CircuitBreaker\Core\CircuitBreaker;

class CircuitBreakerTest extends \PHPUnit_Framework_TestCase {

    /** @var DummyAdapter */
    private $_adapter;

    /** @var  CircuitBreaker */
    private $_cb;

    /** @var array  */
    private $conf = array(
        "dbKnown" => array('maxFailures' => 5, 'retryTimeout' => 5),
        "dbWrong" => array('maxFailures' => 0, 'retryTimeout' => 0),
    );

    protected function setUp() {

        parent::setUp();
        $this->_adapter = new DummyAdapter();
        $this->_cb = new CircuitBreaker($this->_adapter);

        foreach ($this->conf as $serviceName => $config) {
            $this->_cb->setServiceSettings($serviceName, $config['maxFailures'], $config['retryTimeout']);
        }
    }

    protected function tearDown() {
        $this->_adapter = null;
        $this->_cb = null;
        parent::tearDown();
    }

    public function testOk() {
        $this->assertEquals(true, $this->_cb->isAvailable('dbKnown'));
        $this->assertEquals(true, $this->_cb->isAvailable('dbWrong'));
        $this->assertEquals(true, $this->_cb->isAvailable('dbNew'));
    }

    public function testKnown() {
        for ($i = 0; $i < 5; $i++) {
            $this->assertEquals(true, $this->_cb->isAvailable('dbKnown'), 1);
            $this->_cb->reportFailure('dbKnown');
        }
        for ($i = 0; $i < 20; $i++) {
            $this->assertEquals(false, $this->_cb->isAvailable('dbKnown'), "2:" . $i);
            $this->_cb->reportFailure('dbKnown');
        }
        $this->assertEquals(25, $this->_adapter->loadStatus('dbKnown', 'failures'));
        $this->assertTrue(time() - $this->_adapter->loadStatus('dbKnown', 'lastTest') < 2);
    }

    public function testWrong() {
        for ($i = 0; $i < 20; $i++) {
            $this->assertEquals(true, $this->_cb->isAvailable('dbWrong'));
            $this->_cb->reportFailure('dbWrong');
        }
        for ($i = 0; $i < 20; $i++) {
            $this->assertEquals(false, $this->_cb->isAvailable('dbWrong'));
            $this->_cb->reportFailure('dbWrong');
        }
        $this->assertEquals(40, $this->_adapter->loadStatus('dbWrong', 'failures'));
    }

    public function testNew() {
        for ($i = 0; $i < 20; $i++) {
            $this->assertEquals(true, $this->_cb->isAvailable('dbNew'));
            $this->_cb->reportFailure('dbNew');
        }
        for ($i = 0; $i < 25; $i++) {
            $this->assertEquals(false, $this->_cb->isAvailable('dbNew'));
            $this->_cb->reportFailure('dbNew');
        }
        $this->assertEquals(45, $this->_adapter->loadStatus('dbNew', 'failures'));
    }

    public function testAllOk() {
        $this->_cb->reportSuccess('dbKnown');
        $value = $this->_adapter->loadStatus('dbNew', 'failures');
        $this->assertTrue(empty($value));
    }

    public function testAllZero() {
        $this->_cb->reportFailure('dbKnown');
        $this->_cb->reportSuccess('dbKnown');
        $this->assertEquals(0, $this->_adapter->loadStatus('dbKnown', 'failures'));
    }

    public function testAllOne() {
        $this->_cb->reportFailure('dbKnown');
        $this->_cb->reportSuccess('dbKnown');
        $this->_cb->reportFailure('dbKnown');
        $this->assertEquals(1, $this->_adapter->loadStatus('dbKnown', 'failures'));
    }

    public function testAllSix() {
        $this->_cb->reportSuccess('dbKnown');
        $this->_cb->reportSuccess('dbKnown');
        $this->_cb->reportSuccess('dbKnown');
        $this->_cb->reportFailure('dbKnown');
        $this->_cb->reportFailure('dbKnown');
        $this->_cb->reportFailure('dbKnown');
        $this->_cb->reportFailure('dbKnown');
        $this->_cb->reportFailure('dbKnown');
        $this->_cb->reportFailure('dbKnown');
        $this->assertEquals(6, $this->_adapter->loadStatus('dbKnown', 'failures'));
        $this->assertEquals(false, $this->_cb->isAvailable('dbKnown'));
    }

    public function testAllStacking() {
        for ($i = 0; $i < 5; $i++) {
            $this->assertEquals(true, $this->_cb->isAvailable('dbKnown'));
            $this->_cb->reportFailure('dbKnown');
        }
        $this->assertEquals(false, $this->_cb->isAvailable('dbKnown'));
        $this->_cb->reportFailure('dbKnown');
        $this->_cb->reportFailure('dbKnown');
        $this->_cb->reportFailure('dbKnown');
        $this->_cb->reportFailure('dbKnown');
        $this->assertEquals(false, $this->_cb->isAvailable('dbKnown'));
        // reset to max-1
        $this->_cb->reportSuccess('dbKnown');
        $this->assertEquals(true, $this->_cb->isAvailable('dbKnown'));
        // go over max again
        $this->_cb->reportFailure('dbKnown');
        $this->assertEquals(false, $this->_cb->isAvailable('dbKnown'));
        $this->_cb->reportSuccess('dbKnown');
        $this->_cb->reportSuccess('dbKnown');
        $this->assertEquals(true, $this->_cb->isAvailable('dbKnown'));
    }

    public function testAllRetry() {
        // initiate with failure over 3s ago
        $last = time() - 6;
        $this->_adapter->saveStatus('dbKnown', 'failures', 10);
        $this->_adapter->saveStatus('dbKnown', 'lastTest', $last);

        // its 10 failures
        $this->assertEquals(10, $this->_adapter->loadStatus('dbKnown', 'failures'));

        // retry timer elapsed so should allow us
        $this->assertEquals(true, $this->_cb->isAvailable('dbKnown'), 2);
        // no update so still valid
        $this->assertEquals(false, $this->_cb->isAvailable('dbKnown'), 3);
        $this->_cb->reportFailure('dbKnown');
        $this->assertEquals(false, $this->_cb->isAvailable('dbKnown'), 4);

        // its 11 now
        $this->assertEquals(11, $this->_adapter->loadStatus('dbKnown', 'failures'));
    }

    public function testAllNoRetry() {
        // initiate with failure over 3s ago
        $last = time() - 4;
        $this->_adapter->saveStatus('dbKnown', 'failures', 10);
        $this->_adapter->saveStatus('dbKnown', 'lastTest', $last);
        $this->assertEquals(false, $this->_cb->isAvailable('dbKnown'), 2);
    }

}