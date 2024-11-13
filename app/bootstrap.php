<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');
umask(0);
if (version_compare(phpversion(), '7.0.1', '<') === true) {
    echo 'Redseanet E-Commerce Suite v2 supports PHP 8.1.1 or later. (Current: ', phpversion(), ')';
    exit(1);
}

define('DS', DIRECTORY_SEPARATOR);
define('BP', dirname(__DIR__) . DS);
require BP . 'vendor/autoload.php';
