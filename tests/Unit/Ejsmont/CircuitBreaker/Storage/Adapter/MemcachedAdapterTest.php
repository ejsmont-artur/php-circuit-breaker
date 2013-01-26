<?php

namespace Tests\Unit\Ejsmont\CircuitBreaker\Adapter;

use Ejsmont\CircuitBreaker\Storage\Adapter\MemcachedAdapter;

class MemcachedAdapterTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var MemcachedAdapter 
     */
    private $_adapter;

    /**
     * @var \Memcached
     */
    private $_connection;

    protected function setUp() {
        parent::setUp();
        if (!class_exists('\Memcached')) {
            $this->markTestSkipped("extension not loaded");
        }
        $this->_connection = new \Memcached();
        $this->_connection->addServer("localhost", 11211);
        $this->_adapter = new MemcachedAdapter($this->_connection);
    }

    protected function tearDown() {
        $this->_adapter = null;
        parent::tearDown();
    }

    public function testSave() {
        $x = "val";
        $this->_adapter->saveStatus('AAA', 'BBB', $x);
        $this->assertEquals("val", $this->_adapter->loadStatus('AAA', 'BBB'));
    }

    public function testSaveEmpty() {
        $x = "";
        $this->_adapter->saveStatus('X', 'BBB', $x);
        $this->assertEquals("", $this->_adapter->loadStatus('X', 'BBB'));
    }

    public function testSaveClear() {
        $x = "valB";
        $this->_adapter->saveStatus('AAA', 'BBB', $x);
        $this->_connection->flush();

        $this->assertEquals("", $this->_adapter->loadStatus('AAA', 'BBB'));
    }

    public function testNonInstance() {
        $x = rand(1, 1000000);
        $this->_adapter->saveStatus('A', 'BBB', $x);
        // make separate instance of clien and check if you can read through it
        $inst = new MemcachedAdapter($this->_connection);
        $this->assertEquals($x, $inst->loadStatus('A', 'BBB'));
    }

    public function testLoadStatusSimple() {
        $x = 'abcde';
        $this->_adapter->saveStatus('AAA', 'bbb', $x);
        $this->assertEquals("", $this->_adapter->loadStatus('AAa', 'bbb'));
        $this->assertEquals("", $this->_adapter->loadStatus('AA', 'bbb'));
        $this->assertEquals("", $this->_adapter->loadStatus('AAAA', 'bbb'));
        $this->assertEquals('abcde', $this->_adapter->loadStatus('AAA', 'bbb'));
    }

    public function testLoadStatusEmpty() {
        $this->_connection->delete('EjsmontCircuitBreakerAAAbbb');
        $this->assertEquals("", $this->_adapter->loadStatus('GGG', ''));
        $this->assertEquals("", $this->_adapter->loadStatus('AAA', 'bbb'));
        $this->_adapter->saveStatus('B', 'bbb', "");
        $this->assertEquals("", $this->_adapter->loadStatus('A', 'bbb'), 6);
        $this->assertEquals("", $this->_adapter->loadStatus('B', 'bbb'), 7);
    }

    public function testPrefix() {
        $adapter1 = new MemcachedAdapter($this->_connection);
        $adapter2 = new MemcachedAdapter($this->_connection, 1000, 'EjsmontCircuitBreaker');
        $adapter3 = new MemcachedAdapter($this->_connection, 1000, 'EjsmontCircuitWrong');

        $adapter1->saveStatus('abc', 'def', 951);

        $this->assertEquals(951, $adapter2->loadStatus('abc', 'def'));
        $this->assertEquals("", $adapter3->loadStatus('abc', 'def'));
    }

    /**
     * @expectedException Ejsmont\CircuitBreaker\Storage\StorageException 
     */
    public function testFailSave() {
        $memcachedMock = $this->getMock("Memcached", array('get', 'set'), array(), "", false);
        $memcachedMock->expects($this->once())->method("set")->will($this->throwException(new \Exception("some error")));
        
        $adapter = new MemcachedAdapter($memcachedMock);
        $adapter->saveStatus('someService', 'someValue', 951);
    }

    /**
     * @expectedException Ejsmont\CircuitBreaker\Storage\StorageException 
     */
    public function testFailLoad() {
        $memcachedMock = $this->getMock("Memcached", array('get', 'set'), array(), "", false);
        $memcachedMock->expects($this->once())->method("get")->will($this->throwException(new \Exception("some error")));
        
        $adapter = new MemcachedAdapter($memcachedMock);
        $adapter->loadStatus('someService', 'someValue');
    }
    
}