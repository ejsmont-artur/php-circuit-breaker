<?php

// for some reason travisci was showing that apc is loaded but function_exists('apc_clear_cache') would still fail
if (!extension_loaded('apc')) {
    dl('apc.so');
}

require dirname(__FILE__).'/SplClassLoader.php';

$autoLoader = new SplClassLoader('Ejsmont', dirname(__FILE__).'/../src');
$autoLoader->register();