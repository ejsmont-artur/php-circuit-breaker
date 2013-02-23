<?php

require dirname(__FILE__).'/SplClassLoader.php';

$autoLoader = new SplClassLoader('Ejsmont', dirname(__FILE__).'/../src');
$autoLoader->register();