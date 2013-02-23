<?php

namespace Tests\Unit\Ejsmont\CircuitBreaker\Adapter;

use Ejsmont\CircuitBreaker\Storage\Adapter\ApcAdapter;
use Ejsmont\CircuitBreaker\Storage\Decorator\ArrayDecorator;
use Ejsmont\CircuitBreaker\Storage\StorageException;

class ArrayDecoratorTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var ArrayDecorator 
     */
    private $adapter;

    protected function setUp() {
        parent::setUp();

        if(!function_exists('apc_clear_cache')){
            $this->markTestSkipped("APC not installed");
        }
        apc_clear_cache('user');

        $this->adapter = new ArrayDecorator(new ApcAdapter());
    }

    protected function tearDown() {
        $this->adapter = null;
        parent::tearDown();
    }

    // ================================================= TESTS ========================================================

    public function testSave() {
        $x = "val";
        $this->adapter->saveStatus('AAA', 'BBB', $x);
        $this->assertEquals("val", $this->adapter->loadStatus('AAA', 'BBB'));
    }

    public function testSecondInstanceNoFlush() {
        // make a separate instance of client and check if you can read through it
        $secondary = new ArrayDecorator(new ApcAdapter());
        $x = rand(1, 100000);

        // no flush no data
        $this->adapter->saveStatus('AN', 'BBB1', $x);
        $this->assertEquals($x, $this->adapter->loadStatus('AN', 'BBB1'));
        $this->assertEquals("", $secondary->loadStatus('AN', 'BBB1'));
    }

    public function testSecondInstanceFlush() {
        // make a separate instance of client and check if you can read through it
        $secondary = new ArrayDecorator(new ApcAdapter());
        $x = rand(1, 100000);

        // flush = data
        $this->adapter->saveStatus('AN', 'BBB2', $x, true);
        $this->assertEquals($x, $this->adapter->loadStatus('AN', 'BBB2'));
        $this->assertEquals($x, $secondary->loadStatus('AN', 'BBB2'));
    }

    public function testSaveEmpty() {
        $x = "";
        $this->adapter->saveStatus('X', 'BBB', $x);
        $this->assertEquals("", $this->adapter->loadStatus('X', 'BBB'));
    }

    public function testClearCacheDoesNotClearArray() {
        $this->adapter->saveStatus('AAAC', 'BBBC', "valB", true);
        // value loaded from array
        $this->assertEquals("valB", $this->adapter->loadStatus('AAAC', 'BBBC'));
        apc_clear_cache('user');
        $this->assertEquals("valB", $this->adapter->loadStatus('AAAC', 'BBBC'));
    }

    public function testSaveThenLoadNoFlush() {
        $this->adapter->saveStatus('AAANF', 'BBBC', "nf1");
        $this->assertEquals("nf1", $this->adapter->loadStatus('AAANF', 'BBBC'));
    }

    public function testSaveThenLoadFlush() {
        $this->adapter->saveStatus('AAANF', 'BBBC', "nf2", true);
        $this->assertEquals("nf2", $this->adapter->loadStatus('AAANF', 'BBBC'));
    }

    public function testLoadStatusSimple() {
        $this->assertEquals("", $this->adapter->loadStatus('AAA', 'bbb'));

        $this->adapter->saveStatus('AAA', 'bbb', 'abcde1');

        $this->assertEquals("", $this->adapter->loadStatus('AAa', 'bbb'));
        $this->assertEquals("", $this->adapter->loadStatus('AA', 'bbb'));
        $this->assertEquals("", $this->adapter->loadStatus('AAAA', 'bbb'));
        $this->assertEquals('abcde1', $this->adapter->loadStatus('AAA', 'bbb'));
    }

    public function testLoadStatusEmpty() {
        $this->assertEquals("", $this->adapter->loadStatus('', 'bbb'));
        $this->assertEquals("", $this->adapter->loadStatus('', ''));
        $this->assertEquals("", $this->adapter->loadStatus('BBB', ''));
        $this->assertEquals("", $this->adapter->loadStatus('AAA', 'bbb'));
        $this->assertEquals("", $this->adapter->loadStatus('B', 'bbb'));
        $this->adapter->saveStatus('B', 'bbb', "");
        $this->assertEquals("", $this->adapter->loadStatus('A', 'bbb'));
        $this->assertEquals("", $this->adapter->loadStatus('B', 'bbb'));
    }

    public function testPrefix() {
        $adapter1 = new ApcAdapter();
        $adapter2 = new ApcAdapter(1000, 'EjsmontCircuitBreaker');
        $adapter3 = new ApcAdapter(1000, 'EjsmontCircuitWrong');

        $adapter1->saveStatus('abc', 'def', 951);

        $this->assertEquals(951, $adapter2->loadStatus('abc', 'def'));
        $this->assertEquals("", $adapter3->loadStatus('abc', 'def'));
    }

    // just to get coverage and class loading
    public function testExceptionSanity() {
        $e = new StorageException();
        $this->assertTrue($e instanceof \Exception);
    }

}