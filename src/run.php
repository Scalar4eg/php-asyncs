<?php
/**
 * @author Mike Ovchinnikov <ovc.mike@gmail.com>
 */

use PhpAsyncs\Base\IExample;

require __DIR__ . '/../vendor/autoload.php';

$class_name = $argv[1] ?? false;
if (!$class_name) {
    die("Usage: php src/run.php <class_name>\n");
}

$class_name = ltrim($class_name, '\\');
$namespace = "PhpAsyncs";
if (substr($class_name, 0, strlen($namespace)) != $namespace) {
    $class_name = $namespace . '\\' . $class_name;
}

if (!class_exists($class_name)) {
    die("Class $class_name does not exists\n");
}

$Instance = new $class_name();
if ($Instance instanceof IExample) {
    $Instance->run();
} else {
    die("Wrong class\n");
}

