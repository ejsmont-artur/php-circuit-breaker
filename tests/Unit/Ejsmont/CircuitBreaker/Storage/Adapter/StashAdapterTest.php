<?php

namespace Tests\Unit\Ejsmont\CircuitBreaker\Adapter;

use Ejsmont\CircuitBreaker\Storage\Adapter\StashAdapter;

class StashAdapterTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var StashAdapter
     */
    private $_adapter;

    /**
     * @var \Stash\Pool
     */
    private $_pool;

    protected function setUp()
    {
        parent::setUp();
        if (!class_exists('\Stash\Pool')) {
            $this->markTestSkipped("Stash not available.");
        }

        $this->_pool = new \Stash\Pool(new \Stash\Driver\Ephemeral());
        $this->_adapter = new StashAdapter($this->_pool);
    }

    protected function tearDown()
    {
        $this->_adapter = null;
        parent::tearDown();
    }

    public function testSave()
    {
        $x = "val";
        $this->_adapter->saveStatus('AAA', 'BBB', $x);
        $this->assertEquals("val", $this->_adapter->loadStatus('AAA', 'BBB'));
    }

    public function testSaveEmpty()
    {
        $x = "";
        $this->_adapter->saveStatus('X', 'BBB', $x);
        $this->assertEquals("", $this->_adapter->loadStatus('X', 'BBB'));
    }

    public function testSaveClear()
    {
        $x = "valB";
        $this->_adapter->saveStatus('AAA', 'BBB', $x);

        $this->_pool->clear();

        $this->assertEquals("", $this->_adapter->loadStatus('AAA', 'BBB'));
    }

    public function testNonInstance()
    {
        $x = rand(1, 1000000);
        $this->_adapter->saveStatus('A', 'BBB', $x);
        // make separate instance of clien and check if you can read through it
        $inst = new StashAdapter($this->_pool);
        $this->assertEquals($x, $inst->loadStatus('A', 'BBB'));
    }

    public function testLoadStatusSimple()
    {
        $x = 'abcde';
        $this->_adapter->saveStatus('AAA', 'bbb', $x);
        $this->assertEquals("", $this->_adapter->loadStatus('AAa', 'bbb'));
        $this->assertEquals("", $this->_adapter->loadStatus('AA', 'bbb'));
        $this->assertEquals("", $this->_adapter->loadStatus('AAAA', 'bbb'));
        $this->assertEquals('abcde', $this->_adapter->loadStatus('AAA', 'bbb'));
    }

    public function testLoadStatusEmpty()
    {
        $this->_pool->clear();
        $this->assertEquals("", $this->_adapter->loadStatus('GGG', ''));
        $this->assertEquals("", $this->_adapter->loadStatus('AAA', 'bbb'));
        $this->_adapter->saveStatus('B', 'bbb', "");
        $this->assertEquals("", $this->_adapter->loadStatus('A', 'bbb'), 6);
        $this->assertEquals("", $this->_adapter->loadStatus('B', 'bbb'), 7);
    }

    public function testPrefix()
    {
        $adapter1 = new StashAdapter($this->_pool);
        $adapter2 = new StashAdapter($this->_pool, 1000, 'EjsmontCircuitBreaker');
        $adapter3 = new StashAdapter($this->_pool, 1000, 'EjsmontCircuitWrong');

        $adapter1->saveStatus('abc', 'def', 951);

        $this->assertEquals(951, $adapter2->loadStatus('abc', 'def'));
        $this->assertEquals("", $adapter3->loadStatus('abc', 'def'));
    }

    /**
     * @expectedException Ejsmont\CircuitBreaker\Storage\StorageException 
     */
    public function testFailSave()
    {
        $stashMock = $this->getMock('\Stash\Pool', array('getItem', 'setItem'), array(), "", false);
        $stashMock->expects($this->once())->method("getItem")->will($this->throwException(new \Exception("some error")));

        $adapter = new StashAdapter($stashMock);
        $adapter->saveStatus('someService', 'someValue', 951);
    }

    /**
     * @expectedException Ejsmont\CircuitBreaker\Storage\StorageException 
     */
    public function testFailLoad()
    {
        $stashMock = $this->getMock('\Stash\Pool', array('getItem', 'setItem'), array(), "", false);
        $stashMock->expects($this->once())->method("getItem")->will($this->throwException(new \Exception("some error")));

        $adapter = new StashAdapter($stashMock);
        $adapter->loadStatus('someService', 'someValue');
    }
}
