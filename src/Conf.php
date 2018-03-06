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
    const CONF_GLOBAL_CONFIG_FILENAME = "global_config_location";
    const CONFIG_DIR = "config";

    /**
     * @return Config
     */
    static function getInstance() {
        static $instance;

        if (is_null($instance)) {
            /**
             * Reads the default config-path into the config instance.
             * Trying from several locations. Fallback to liberary.
             */
            $alternative_paths = [
                self::getConfFilepath(self::GAEUTIL_FILENAME),
                self::getConfFilepath("app_config.json"),
                Util::resolveFilePath(dirname(__FILE__), "..", self::GAEUTIL_FILENAME)
            ];
            foreach ($alternative_paths as $config_file_path) {
                if (file_exists($config_file_path)) {
                    $instance = new Config($config_file_path);
                    break;
                }
            }

            $cached = new Cached(__METHOD__);
            if (!$cached->exists()) {
                $secret_data = [
                    "global_config_is_loaded" => false
                ];
                /**
                 * Fetching encoded microservice secrets and storing them in cache.
                 */
                $global_config_file = $instance->get(self::CONF_GLOBAL_CONFIG_FILENAME);
                syslog(LOG_INFO, "Fetching... " . $global_config_file);
                if (Util::isDevServer()) {
                    $array_w_secrets = Files::get_storage_json($global_config_file, false);
                } else {
                    $array_w_secrets = Files::get_json($global_config_file, false);
                }
                if ($array_w_secrets) {
                    try {
                        $data = Secrets::decrypt_dot_secrets($array_w_secrets);
                        $secret_data = array_merge_recursive($secret_data, $data);
                    } catch (\Exception $e) {
                        syslog(LOG_WARNING, "Decrpytion of $global_config_file failed with message: " . $e->getMessage());
                    }
                }
                /**
                 * Creating internal secret for service to frontend communication.
                 */
                $secret_data[JWT::CONF_EXTERNAL_SECRET_NAME] = JWT::generate_secret();
                $cached->set($secret_data);
            }
            foreach ($cached->get() as $key => $value) {
                $key = trim($key, ".");
                $instance->set($key, $value);
            }
        }
        return $instance;
    }

    static function get($key, $default = null) {
        $env_var = getenv(strtoupper($key));
        if ($env_var) {
            return $env_var;
        } else {
            $instance = self::getInstance();

            return $instance->get($key, $default);
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

    static function getGaeUtilJsonPath($project_directory) {
        return Util::resolveFilePath($project_directory, Conf::CONFIG_DIR, Conf::GAEUTIL_FILENAME);
    }

    static function getConfFolderPath($project_directory) {
        return Util::resolveFilePath($project_directory, Conf::CONFIG_DIR);
    }
}