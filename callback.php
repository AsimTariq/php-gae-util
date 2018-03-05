<?php
/**
 * Created by PhpStorm.
 * User: michael
 * Date: 22/11/2017
 * Time: 10:05
 */


use GaeUtil\Auth;

$autoloader_locations = implode(DIRECTORY_SEPARATOR, ["..", "..", 'autoload.php']);

require $autoloader_locations;

Auth::callback_handler($_GET);


