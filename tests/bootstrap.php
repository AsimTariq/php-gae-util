
<?php
/**
 * Any set-up needed to run the tests
 *
 * @author Tom Walder <twalder@gmail.com>
 */
// Time zone
date_default_timezone_set('UTC');
// Autoloader
require_once(dirname(__FILE__) . '/../vendor/autoload.php');
// Base Test Files
require_once(dirname(__FILE__) . '/base/GaeUtilTest.php');
require_once(dirname(__FILE__) . '/base/TestClassForWorkflows.php');