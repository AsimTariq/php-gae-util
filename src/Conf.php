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
            $config_file_path = self::getConfFilepath("auth_config.json");
            $instance = new Config($config_file_path);
        }
        return $instance;
    }

    static function get($key, $default = null) {
        return self::getInstance()->get($key, $default);
    }

    static function getConfFilepath($filename) {
        $conf_filepath = implode(DIRECTORY_SEPARATOR, [
            dirname(__FILE__),
            "..",
            "config",
            $filename
        ]);
        $conf_filepath_real = realpath($conf_filepath);
        return $conf_filepath_real;
    }
}