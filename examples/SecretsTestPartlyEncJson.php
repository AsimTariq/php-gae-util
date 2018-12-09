<?php
/**
 * Created by PhpStorm.
 * User: michael
 * Date: 02/03/2018
 * Time: 11:31
 */

use GaeUtil\Secrets;

require_once dirname(__FILE__)."/../vendor/autoload.php";

putenv("SUPPRESS_GCLOUD_CREDS_WARNING=true");
$content = Secrets::decyptPartlyEncFile("gs://beste-adm.appspot.com/test4.json");

print_r($content);