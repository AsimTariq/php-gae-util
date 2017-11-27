<?php
/**
 * Created by PhpStorm.
 * User: michael
 * Date: 22/11/2017
 * Time: 10:05
 */


use GaeUtil\Auth;
use GaeUtil\Conf;
use GaeUtil\Util;

$autoloader_locations = implode(DIRECTORY_SEPARATOR, ["..", "..", 'autoload.php']);

require $autoloader_locations;
try {
    /**
     * Accepting multiple auth cycles.
     * Wrapping this all into a try catch.
     */
    if (isset($_GET["next"])) {
        switch ($_GET["next"]) {
            case "google":
                Util::redirect(Auth::getAuthRedirectUrl());
                break;
            default:
                echo "Invalid Provider";
                break;
        }
    } elseif (isset($_GET["code"])) {
        $code = $_GET["code"];
        if (Auth::fetchAndSaveTokenByCode($code)) {
            $redirect_back_to_front = Conf::get("frontend_url", "/");
            Util::redirect($redirect_back_to_front);
        } else {
            Util::cmdline("Error saving token");
        }
    } elseif (isset($_GET["error"])) {
        switch ($_GET["error"]) {
            case "access_denied":
                echo "Access Denied. ";
                break;
            default:
                echo "Something Went Wrong. ";
                break;
        }
        echo Util::link(Auth::getAuthRedirectUrl(), "RETRY");
    } else {
        Util::redirect(Auth::getAuthRedirectUrl());
    }
} catch (Exception $e) {
    Util::cmdline($e->getMessage());
    syslog(LOG_ALERT,$e->getMessage());
}




