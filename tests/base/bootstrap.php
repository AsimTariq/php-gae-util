<?php
/**
 * Any set-up needed to run the tests
 *
 */
// Time zone
date_default_timezone_set('UTC');
error_reporting(E_ALL ^ E_DEPRECATED);
// Autoloader
require_once(dirname(__FILE__) . '/../../vendor/autoload.php');
// Base Test Files
require_once(dirname(__FILE__) . '/TestClassForWorkflows.php');

use GaeUtil\DataStore;

DataStore::changeToTestMode("localhost:8282");
putenv("APPLICATION_ID=testapp");
putenv("CURRENT_MODULE_ID=default");


