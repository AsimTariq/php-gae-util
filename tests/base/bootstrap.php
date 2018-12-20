<?php
/**
 * Any set-up needed to run the tests
 *
 * @author Tom Walder <twalder@gmail.com>
 */
// Time zone
date_default_timezone_set('UTC');
// Autoloader
require_once(dirname(__FILE__) . '/../../vendor/autoload.php');
// Base Test Files
require_once(dirname(__FILE__) . '/TestClassForWorkflows.php');

use GaeUtil\DataStore;

DataStore::changeToTestMode();

$datastore_emulator_host = getenv("DATASTORE_EMULATOR_HOST");
$emulator_started = false;
$attemts = 0;
echo "Waiting for datastore at $datastore_emulator_host...";
while (!$emulator_started) {
    $curlSession = curl_init();
    curl_setopt($curlSession, CURLOPT_URL, "http://" . $datastore_emulator_host);
    curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, true);
    $content = curl_exec($curlSession);
    curl_close($curlSession);
    if (trim($content) == "Ok") {
        $emulator_started = true;
        echo "Ready!" . PHP_EOL;
        break;
    } else {
        echo ".";
        sleep(1);
    }
    if ($attemts > 5) {
        echo "Giving up!" . PHP_EOL;
        break;
    } else {
        $attemts++;
    }
}
