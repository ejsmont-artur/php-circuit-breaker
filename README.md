# What is php-circuit-breaker

[![Build Status](https://travis-ci.org/ejsmont-artur/php-circuit-breaker.png?branch=master)](https://travis-ci.org/ejsmont-artur/php-circuit-breaker)

A component helping you gracefully handle outages and timeouts of external services (usually remote, 3rd party services).

It is a library providing extremely easy to use circuit breaker component. It does not require external dependencies and it has default storage
implementations for APC and Memcached but can be extended multiple ways.

# Frameworks support

This library does not require any particular PHP framework, all you need is PHP 5.6 or higher.

# Motivation & Benefits

* Allow application to detect failures and adapt its behaviour without human intervention.
* Increase robustness of services by addinf fail-safe functionality into modules.

# Installation

You can download sources and use them with your autoloader or you can use composer in which case all you nees is a require like this:

    "require": {
        "geggleto/php-circuit-breaker": "*"
    },

After that you should update composer dependencies and you are good to go.

## Use Case - Non-Critical Feature

* Your application has an Non-Critical Feature like: user tracking, stats, recommendations etc
* The optional feature uses remote service that causes outages of your application.
* You want to keep applicaton and core processes available when "Non-Critical Feature" fails.

Code of your application could look something like:
<pre>
    $factory = new Ejsmont\CircuitBreaker\Factory();
    $circuitBreaker = $factory->getSingleApcInstance(30, 300);

    $userProfile = null;
    if( $circuitBreaker->isAvailable("UserProfileService") ){
        try{
            $userProfile = $userProfileService->loadProfileOrWhatever();
            $circuitBreaker->reportSuccess("UserProfileService");
        }catch( UserProfileServiceConnectionException $e ){
            // network failed - report it as failure
            $circuitBreaker->reportFailure("UserProfileService");
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

Backend talking to the payment service could look like this:
<pre>
    $factory = new Ejsmont\CircuitBreaker\Factory();
    $circuitBreaker = $factory->getSingleApcInstance(30, 300);

    try{
        // try to process the payment
        // then tell circuit breaker that it went well
        $circuitBreaker->reportSuccess("PaymentOptionOne");
    }catch( SomePaymentConnectionException $e ){
        // If you get network error report it as failure
        $circuitBreaker->reportFailure("PaymentOptionOne");
    }catch( Exception $e ){
        // in case of your own error handle it however it makes sense but
        // dont tell circuit breaker it was 3rd party service failure
    }
</pre>

Since you are recording failed and successful operations you can now use them in the front end as well 
to hide payment options that are failing.

Frontend rendering the available payment options could look like this:
<pre>
    $factory = new Ejsmont\CircuitBreaker\Factory();
    $circuitBreaker = $factory->getSingleApcInstance(30, 300);

    if ($circuitBreaker->isAvailable("PaymentOptionOne")) {
        // display the option
    }
</pre>

# Features

* Track multiple services through a single Circuit Breaker instance.
* Pluggable backend adapters, provided APC and Memcached by default.
* Customisable service thresholds. You can define how many failures are necessary for service to be considered down.
* Customisable retry timeout. You do not want to disable the service forever. After provided timeout 
circuit breaker will allow a single process to attempt 

# Performance Impact

Overhead of the Circuit Breaker is negligible. 

APC implementation takes roughly 0.0002s to perform isAvailable() and then reportSuccess() or reportFailure().

Memcache adapter is in range of 0.0005s when talking to the local memcached process. 

The only potential performance impact is network connection time. If you chose to use remote memcached server or
implement your own custom StorageAdapter.

## Details

Before documentation gets updated you can read more on the concept of circuit breaker and
its implementation on my blog http://artur.ejsmont.org/blog/circuit-breaker

Some implementation details has changed but the core logic is still the same.

(Update) You can read my blog on what I do with this package, http://bolt.tamingtheelephpant.com/page/circuit-breakers-failing-gracefully

## Unit Testing
Windows: `call vendor/bin/phpunit tests`

## Author

* Artur Esjmont (https://github.com/ejsmont-artur) via http://artur.ejsmont.org
* Glenn Eggleton (https://github.com/geggleto)
