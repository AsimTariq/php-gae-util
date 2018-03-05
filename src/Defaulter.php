<?php
/**
 * Created by PhpStorm.
 * User: michael
 * Date: 09/02/2018
 * Time: 10:44
 */

namespace GaeUtil;

/**
 * Class Defaulter
 * Class to hold code generators for faster initiation of projects.
 *
 * @package GaeUtil
 */
class Defaulter {

    /**
     * Function that sets some defaults in Composer.json
     * @param $project_directory
     * @param $app_engine_project
     */
    static function composer_json($project_directory, $app_engine_project) {

    }

    /**
     * Fixing gaeutil.json
     */
    static function gaeutil_json($project_directory, $app_engine_project, $gaeutil_defaults = []) {
        $config_dir_location = Conf::getConfFolderPath($project_directory);
        $gaeutil_json_location = Conf::getGaeUtilJsonPath($project_directory);
        Files::ensure_directory($config_dir_location);
        $gaeutil_json_content = Files::get_json($gaeutil_json_location, []);
        foreach ($gaeutil_defaults as $key => $val) {
            $gaeutil_json_content[$key] = $val;
        }
        /**
         * Just create a new key.
         */
        $project_key_name = $gaeutil_json_content[Secrets::CONF_PROJECT_KEY_NAME];
        $project_key_parts = Secrets::reverse_kms_key($project_key_name);
        list($project, $location, $keyRing, $cryptoKey) = array_values($project_key_parts);
        $cryptoKey = $app_engine_project;
        $gaeutil_json_content[Secrets::CONF_PROJECT_KEY_NAME] = Secrets::get_key_name($project, $location, $keyRing, $cryptoKey);

        // Write to disk
        if (Files::put_json($gaeutil_json_location, $gaeutil_json_content)) {
            Util::cmdline("Updated gaeutil.json $project_directory");
        }
    }

    /**
     * Fixing package.json
     */
    static function package_json($project_directory, $app_engine_project, $app_engine_service, $package_json_defaults = []) {
        $package_json_location = $project_directory . DIRECTORY_SEPARATOR . "package.json";
        $package_json_content = Files::get_json($package_json_location, []);
        $package_json_content = array_merge([
            "name" => null,
            "project" => null,
            "private" => null,
        ], $package_json_content);
        $project_name = $app_engine_project . '-' . $app_engine_service;
        $package_json_defaults["private"] = true;
        $package_json_content["name"] = $project_name;
        $package_json_content["project"] = $app_engine_project;
        foreach ($package_json_defaults as $key => $val) {
            $package_json_content[$key] = $val;
        }
        if ($app_engine_service == "default") {
            $package_json_content["scripts"]["deploy"] = 'gcloud app deploy app.yaml queue.yaml --project $npm_package_project --promote --quiet';
        } else {
            $package_json_content["scripts"]["deploy"] = 'gcloud app deploy app.yaml --project $npm_package_project --promote --quiet';
        }
        $gaeutil_json = Files::get_json(Conf::getGaeUtilJsonPath($project_directory), []);

        $project_key_name = $gaeutil_json[Secrets::CONF_PROJECT_KEY_NAME];
        $project_key = Secrets::reverse_kms_key($project_key_name);
        $crypt_params = [
            "ciphertext-file" => "config/secrets.cipher",
            "plaintext-file" => "secrets.json",
            "project" => $project_key["project"],
            "location" => $project_key["location"],
            "keyring" => $project_key["keyRing"],
            "key" => $project_key["cryptoKey"]
        ];
        $package_json_content["scripts"]["encrypt"] = Util::command_maker("gcloud kms encrypt", $crypt_params);
        $package_json_content["scripts"]["decrypt"] = Util::command_maker("gcloud kms decrypt", $crypt_params);
        $package_json_content["scripts"]["devserve"] = 'dev_appserver.py --port=5000 . -A $npm_package_project';
        if (Files::put_json($package_json_location, $package_json_content)) {
            Util::cmdline("Updated package.json in $project_name");
        }

    }

    static function init_secrets($project_directory, $force_reset = false) {
        $config_dir_location = $project_directory . DIRECTORY_SEPARATOR . Conf::CONFIG_DIR;
        $secret_cipher_location = $config_dir_location . DIRECTORY_SEPARATOR . Conf::CONF_GLOBAL_CONFIG_FILENAME;
        $gaeutil_json_location = $config_dir_location . DIRECTORY_SEPARATOR . Conf::GAEUTIL_FILENAME;
        $gaeutil_json = Files::get_json($gaeutil_json_location, []);

        $plaintext_filename = Util::get_tempfilename();

        $secrets = [
            JWT::CONF_INTERNAL_SECRET_NAME => JWT::generate_secret(),
            JWT::CONF_EXTERNAL_SECRET_NAME => JWT::generate_secret(),
            JWT::CONF_SCOPED_SECRET_NAME => JWT::generate_secret(),
        ];
        $projectId = $gaeutil_json[Secrets::CONF_PROJECT_ID_NAME];
        $keyRingId = $gaeutil_json[Secrets::CONF_KEYRING_ID_NAME];
        $keyId = $gaeutil_json[Secrets::CONF_KEY_ID_NAME];

        if ($projectId && $keyRingId && $keyId) {
            Files::ensure_directory($config_dir_location);
            Secrets::config($projectId, $keyRingId, $keyId);
            if (file_exists($secret_cipher_location)) {
                try {
                    $decrypted = Secrets::decrypt($secret_cipher_location);
                    $decoded = json_decode($decrypted, JSON_OBJECT_AS_ARRAY);
                    Util::is_array_or_fail("Decoded cipher", $decoded);
                    $secrets = array_merge($secrets, $decoded);
                } catch (\Exception $e) {
                    Util::cmdline("\tError decoding cipher with key. " . $e->getMessage());
                    if (!$force_reset) {
                        return false;
                    }

                }
            }
            if ($force_reset || count($secrets)) {
                Files::put_json($plaintext_filename, $secrets);
                Secrets::encrypt($plaintext_filename, $secret_cipher_location);
            }
        } else {
            Util::cmdline("\tProject $project_directory is not configured for KMS.");
            return false;
        }
    }


    static function rotate_global_secret($cipher_location, $key_name) {
        $plaintext_filename = Util::get_tempfilename();
        Files::put_json($plaintext_filename, [
            JWT::CONF_INTERNAL_SECRET_NAME => JWT::generate_secret(),
            "global_secret_created" => date("c")
        ]);

        Secrets::encrypt($plaintext_filename, $cipher_location, $key_name);
    }
}