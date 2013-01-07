<?php

namespace Tests\Unit\Ejsmont\CircuitBreaker\Adapter;

use Ejsmont\CircuitBreaker\Factory;
use Ejsmont\CircuitBreaker\Storage\Adapter\DummyAdapter;

class DummyAdapterTest extends \PHPUnit_Framework_TestCase {

    private $_adapter;

    protected function setUp() {
        parent::setUp();
        $this->_adapter = new DummyAdapter();
    }

    protected function tearDown() {
        $this->_adapter = null;
        parent::tearDown();
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