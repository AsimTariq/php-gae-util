<?php
/**
 * Created by PhpStorm.
 * User: michael
 * Date: 23/01/2018
 * Time: 14:12
 */

use GaeUtil\Util;

header('Content-Type:text/plain');
$current_directory = dirname(__FILE__);
require_once $current_directory . "/tests/bootstrap.php";
require_once $current_directory . "/tests/WorkflowTest.php";
$arguments = [];
$xmlfilepath = realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . "phpunit.xml");

$printer = new PHPUnit_Util_Printer();

$suite = new PHPUnit_Framework_TestSuite();
$suite->addTestSuite("WorkflowTest");

if (Util::isDevServer()) {

    PHPUnit_TextUI_TestRunner::run($suite, $arguments);
} else {
    echo "Don't want to run tests in production.";
}

