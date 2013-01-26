# php-circuit-breaker

Library providing extremely simple circuit breaker component without external dependencies.

## Motivation & Benefits

* Allow application to detect failures and adapt its behaviour without human intervention.
* Increase robustness of services by addinf fail-safe functionality into modules.

## Use Case - Optional Feature

* Your application has an Optional Feature like: user tracking, stats, recommendations etc
* The optional feature uses remote service that causes outages of your application.
* You want to keep applicaton and core processes available when "Optional Feature" services fail.

Code of your application could look something like:
<pre>
    $factory = new Ejsmont\CircuitBreaker\Factory();
    $circuitBreaker = $factory->getSingleApcInstance(30, 300);

    $userProfile = null;
    if( $cb->isAvailable("UserProfileService") ){
        try{
            $userProfile = $userProfileService->loadProfileOrWhatever();
            $cb->reportSuccess("UserProfileService");
        }catch( UserProfileServiceConnectionException $e ){
            // network failed - report it as failure
            $cb->reportFailure("UserProfileService");
        }catch( Exception $e ){
            // something went wrong but it is not service's fault, dont report as failure
        }
    }
    if( $userProfile === null ){
        // for example, show 'System maintenance, you cant login now.' message
        // but still let people buy as logged out customers.
    }
</pre>

## Use Case - Payment Gateway

* Web application depends on third party service (for example a payment gateway).
* Web application needs to keep track when 3rd party service is unavailable.
* Application can not become slow/unavailable, it has to tell user that features are limited or just hide them.
* Application uses circuit breaker before checkout page rendering and if particular payment gateway is unavailable 
payment option is hidden from the user.

As you can see that is a very powerful concept of selectively disabling feautres at runtime but still allowing the
core business processes to be uninterrupted.

Backend that is talking to the payment service could have the following code:
<pre>
    $factory = new Ejsmont\CircuitBreaker\Factory();
    $circuitBreaker = $factory->getSingleApcInstance(30, 300);

    try{
        // try to process the payment
        // then tell circuit breaker that it went well
        $cb->reportSuccess("PaymentOptionOne");
    }catch( SomePaymentConnectionException $e ){
        // If you get network error report it as failure
        $cb->reportFailure("PaymentOptionOne");
    }catch( Exception $e ){
        // in case of your own error handle it however it makes sense but
        // dont tell circuit breaker it was 3rd party service failure
    }
</pre>

Since you are recording failed and successful operations you can now use them in the front end as well 
to hide payment options that are failing.

Example code could look like this:
<pre>
    $factory = new Ejsmont\CircuitBreaker\Factory();
    $circuitBreaker = $factory->getSingleApcInstance(30, 300);

    if ($cb->isAvailable("PaymentOptionOne")) {
        // display the option
    }
</pre>

## Features

* Track multiple services through a single Circuit Breaker instance.
* Pluggable backend adapters, provided APC and Memcached by default.
* Customisable service thresholds. You can define how many failures are necessary for service to be considered down.
* Customisable retry timeout. You do not want to disable the service forever. After provided timeout 
circuit breaker will allow a single process to attempt 

## Performance Impact

Overhead of the Circuit Breaker is negligible. 

APC implementation takes roughly 0.0002s to perform isAvailable() and then reportSuccess() or reportFailure().

Memcache adapter is in range of 0.0005s when talking to the local memcached process. 

The only potential performance impact is network connection time. If you chose to use remote memcached server or
implement your own custom StorageAdapter.

## Running tests

* Tests are run via PHPUnit It is assumed to be installed via PEAR.
* Tests can be ran using phpunit alone or via ant build targets.
* The "ci" target generate code coverage repor, "phpunit" target does not.

You can run all tests by any of the following:

    ant
    ant phpunit
    ant ci

You can run selected test case by running:

    cd tests
    phpunit Unit/Ejsmont/CircuitBreaker/Storage/Adapter/DummyAdapterTest.php

## Details

Before documentation gets updated you can read more on the concept of circuit breaker and
its implementation on my blog http://artur.ejsmont.org/blog/circuit-breaker

Some implementation details has changed but the core logic is still the same.

## Author

* Artur Esjmont (https://github.com/ejsmont-artur) via http://artur.ejsmont.org
