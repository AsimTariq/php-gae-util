<?php
/**
 * Created by PhpStorm.
 * User: michael
 * Date: 22/11/2017
 * Time: 08:59
 */

namespace GaeUtil;

class PostInstall {

    static function rrmdir($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . "/" . $object))
                        self::rrmdir($dir . "/" . $object);
                    else
                        unlink($dir . "/" . $object);
                }
            }
            rmdir($dir);
        }
    }

    static function get_used_services(Array $services) {
        $stufftoignore = [".", ".."];
        foreach ($services as $service) {
            $stufftoignore[] = $service;
            $stufftoignore[] = $service . ".php";
        }
        return $stufftoignore;
    }

    static function cleanGoogleApiClasses($event) {
        $vendorDir = $event->getComposer()->getConfig()->get("vendor-dir");
        define("COMPOSER_VENDOR_DIR", $vendorDir);
        require_once $vendorDir . DIRECTORY_SEPARATOR . "autoload.php";

        $service_directory = implode(DIRECTORY_SEPARATOR, [
            $vendorDir,
            "google",
            "apiclient-services",
            "src",
            "Google",
            "Service"
        ]);


        /**
         * Deleting Files that are not used.
         */
        $removed_files = 0;
        $services = Conf::get("used_services", []);
        $used_services = self::get_used_services($services);
        foreach (scandir($service_directory) as $file) {
            $filepath = $service_directory . DIRECTORY_SEPARATOR . $file;
            if (in_array($file, $used_services)) {
            } else {
                if (is_dir($filepath)) {
                    $removed_files++;
                    self::rrmdir($filepath);
                } else {
                    unlink($filepath);
                }
            }
        }
        Util::cmdline("> Removed $removed_files unused services and files from google/apiclient-services.");
    }
}