<?php
namespace selective\ORM\Tests;

define('PATH_TESTS', dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR);
define('PATH_LIB', dirname(PATH_TESTS) . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR);

set_include_path(PATH_LIB . PATH_SEPARATOR . PATH_TESTS . PATH_SEPARATOR . get_include_path());

function autoload($className)
{
    $classPath = implode('/', explode('\\', $className)) . '.php';

    if ($path = stream_resolve_include_path($classPath)) {
        return require_once($path);
    } else {
        return false;
    }
}

spl_autoload_register('selective\ORM\Tests\autoload');