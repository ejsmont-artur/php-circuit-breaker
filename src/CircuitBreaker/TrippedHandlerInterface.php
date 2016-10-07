<?php
/**
 * Created by PhpStorm.
 * User: Glenn
 * Date: 2016-02-18
 * Time: 11:53 AM
 */

namespace Ejsmont\CircuitBreaker;


interface TrippedHandlerInterface
{
    public function __invoke($serviceName, $count, $message);
}