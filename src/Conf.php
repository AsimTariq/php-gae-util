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

    const GAEUTIL_FILENAME = "gaeutil.json";
    const SECRETS_CIPHER_FILENAME = "secrets.cipher";
    const CONFIG_DIR = "config";

    /**
     * @return Config
     */
    static function getInstance() {
        static $instance;

        if (is_null($instance)) {

            $config_file_path = self::getConfFilepath(self::GAEUTIL_FILENAME);
            $config_file_path_alt = self::getConfFilepath("app_config.json");
            $config_secrets_file_path = self::getConfFilepath(self::SECRETS_CIPHER_FILENAME);
            if (file_exists($config_file_path)) {
                $instance = new Config($config_file_path);
            } elseif (file_exists($config_file_path_alt)) {
                $instance = new Config($config_file_path_alt);
            } else {
                $path = Util::resolveFilePath(dirname(__FILE__), "..", self::GAEUTIL_FILENAME);
                $instance = new Config($path);
            }
            /**
             * Fetching encoded secrets and storing them in cache.
             */

            if (file_exists($config_secrets_file_path)) {
                $cached = new Cached($config_secrets_file_path, !Util::isDevServer());
                if (!$cached->exists()) {
                    try {
                        $content = Secrets::decrypt($config_secrets_file_path, $instance);
                        $data = json_decode($content, JSON_OBJECT_AS_ARRAY);
                        Util::is_array_or_fail("Encrypted secrets", $data);
                        $cached->set($data);
                    } catch (\Exception $e) {
                        syslog(LOG_WARNING, "Decrpytion failed: " . $e->getMessage());
                        $cached->set([]);
                    }
                }
                foreach ($cached->get() as $key => $value) {
                    $instance->set($key, $value);
                }
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
        $conf_filepath_real = Util::resolveFilePath($vendorDir, "..", self::CONFIG_DIR, $filename);
        if (!file_exists($conf_filepath_real)) {
            $conf_filepath_real = Util::resolveFilePath(dirname(__FILE__), "..", self::CONFIG_DIR, $filename);
        }
        return $conf_filepath_real;
    }
}