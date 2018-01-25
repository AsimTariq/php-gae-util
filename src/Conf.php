<?php
/**
 * Created by PhpStorm.
 * User: michael
 * Date: 15/11/2017
 * Time: 17:42
 */

namespace GaeUtil;


use Noodlehaus\Config;

class Conf {

    /**
     * @return Config
     */
    static function getInstance() {
        static $instance;
        if (is_null($instance)) {
            $config_file_path = self::getConfFilepath("gaeutil.json");
            $config_file_path_alt = self::getConfFilepath("app_config.json");
            if(file_exists($config_file_path)){
                $instance = new Config($config_file_path);
            } elseif(file_exists($config_file_path_alt)) {
                $instance = new Config($config_file_path_alt);
            } else {
                $path = Util::resolveFilePath(dirname(__FILE__), "..", "gaeutil.json");
                $instance = new Config($path);
            }
        }
        return $instance;
    }

    static function get($key, $default = null) {
        $env_var = getenv(strtoupper($key));
        if ($env_var) {
            return $env_var;
        } else {
            return self::getInstance()->get($key, $default);
        }
    }

    static function getConfFilepath($filename) {
        $vendorDir = Composer::getVendorDir();
        $conf_filepath_real = Util::resolveFilePath($vendorDir, "..", "config", $filename);
        if (!file_exists($conf_filepath_real)) {
            $conf_filepath_real = Util::resolveFilePath(dirname(__FILE__), "..", "config", $filename);
        }
        return $conf_filepath_real;
    }
}