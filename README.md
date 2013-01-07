# php-circuit-breaker

Library providing extremely simple circuit breaker component without external dependencies.

## Features


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
    phpunit Unit/PhpProxyBuilder/Proxy/ArrayCachingProxyTest.php

## Author

* Artur Esjmont (https://github.com/ejsmont-artur) via http://artur.ejsmont.org
