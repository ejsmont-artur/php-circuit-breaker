<?php

/**
 * Simple script calling circuit breaker thousands of times reporting success or error and checking statuses
 * 
 * This can be used to see how circuit breaker can be instantinated and used easily, it also gives gauge 
 * of the approximate performance.
 * 
 * Memcached to local memcached: ~0.0005s per check+report 
 */

namespace Tests\Manual\Performance;

use Ejsmont\CircuitBreaker\Factory;

$callCount = 10000;

$connection = new \Memcached();
$connection->addServer("localhost", 11211);
$connection->delete("EjsmontCircuitBreakerCircuitBreakerStatsAggregatedStats");

$factory = new Factory();
$cb = $factory->getMemcachedInstance($connection, 30, 3600);

$start = microtime(true);
for ($i = 0; $i < $callCount; $i++) {
    $serviceName = "someServiceName" . ($i % 5);
    $cb->isAvailable($serviceName);
    if (mt_rand(1, 1000) > 700) {
        $cb->reportSuccess($serviceName);
    } else {
        $cb->reportFailure($serviceName);
    }
}
$stop = microtime(true);

print_r(array(
    sprintf("Total time for %d calls: %.4f", $callCount, $stop - $start),
    unserialize($connection->get("EjsmontCircuitBreakerCircuitBreakerStatsAggregatedStats")),
));
