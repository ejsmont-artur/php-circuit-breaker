<?php

namespace Tests\Unit\Ejsmont\CircuitBreaker\Adapter;

use Ejsmont\CircuitBreaker\Storage\Adapter\ApcAdapter;

class ApcAdapterTest extends \PHPUnit_Framework_TestCase {

    private $_adapter;

    protected function setUp() {
        parent::setUp();
        $this->_adapter = new ApcAdapter();
    }

    protected function tearDown() {
        $this->_adapter = null;
        parent::tearDown();
        apc_clear_cache('user');
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
        apc_clear_cache('user');

        $this->assertEquals("", $this->_adapter->loadStatus('AAA', 'BBB'));
    }

    public function testNonInstance() {
        $x = rand(1, 1000000);
        $this->_adapter->saveStatus('A', 'BBB', $x);
        // make separate instance of clien and check if you can read through it
        $inst = new ApcAdapter();
        $this->assertEquals($x, $inst->loadStatus('A', 'BBB'));
    }

    public function testLoadStatusSimple() {
        $this->assertEquals("", $this->_adapter->loadStatus('AAA', 'bbb'));
        $x = 'abcde';
        $this->_adapter->saveStatus('AAA', 'bbb', $x);
        $this->assertEquals("", $this->_adapter->loadStatus('AAa', 'bbb'));
        $this->assertEquals("", $this->_adapter->loadStatus('AA', 'bbb'));
        $this->assertEquals("", $this->_adapter->loadStatus('AAAA', 'bbb'));
        $this->assertEquals('abcde', $this->_adapter->loadStatus('AAA', 'bbb'));
    }

    public function testLoadStatusEmpty() {
        $this->assertEquals("", $this->_adapter->loadStatus('', 'bbb'));
        $this->assertEquals("", $this->_adapter->loadStatus('', ''));
        $this->assertEquals("", $this->_adapter->loadStatus('BBB', ''));
        $this->assertEquals("", $this->_adapter->loadStatus('AAA', 'bbb'));
        $this->assertEquals("", $this->_adapter->loadStatus('B', 'bbb'));
        $this->_adapter->saveStatus('B', 'bbb', "");
        $this->assertEquals("", $this->_adapter->loadStatus('A', 'bbb'));
        $this->assertEquals("", $this->_adapter->loadStatus('B', 'bbb'));
    }

}