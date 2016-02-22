<?php

/**
 * This file is part of the php-circuit-breaker package.
 * 
 * @link https://github.com/ejsmont-artur/php-circuit-breaker
 * @link http://artur.ejsmont.org/blog/circuit-breaker
 * @author Artur Ejsmont
 *
 * For the full copyright and license information, please view the LICENSE file.
 */

namespace Ejsmont\CircuitBreaker\Storage;

/**
 * Thrown when storage handler class can not be used any more.
 * Means there is a serious error like handler can not connect to
 * the storage or out of space or underlying PHP extension is missing etc.
 * 
 * @package Ejsmont\CircuitBreaker\Components
 */
class StorageException extends \Exception {
    
}