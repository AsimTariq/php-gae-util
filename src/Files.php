<?php

namespace GaeUtil;

use Google\Auth\HttpHandler\Guzzle6HttpHandler;
use Google\Cloud\Storage\StorageClient;

/**
 * Description of GaeUtil
 *
 * @author michael
 */
class Files {

    static function downloadUrlToTempFile($url) {
        $DownloadPath = Util::get_tempfilename();
        $fp = fopen($DownloadPath, 'w');
        fwrite($fp, file_get_contents($url));
        fclose($fp);
        return $DownloadPath;
    }

    static function get_storage_client() {
        $options = [];
        if (Util::isDevServer()) {
            $httpClient = GoogleAccess::createWindowsCompliantHttpClient();
            $options["httpHandler"] = new Guzzle6HttpHandler($httpClient);
        }
        $storageClient = new StorageClient($options);
        return $storageClient;
    }

    static function ensure_gs_streamwrapper_registered($filename) {
        $scheme = parse_url($filename, PHP_URL_SCHEME);
        if ($scheme == "gs" && !in_array('gs', stream_get_wrappers())) {
            $client = self::get_storage_client();
            $client->registerStreamWrapper();
        }
    }

    /**
     * Objects are the individual pieces of data that you store in Google Cloud Storage.
     * This function let you fetch object from Cloud Storage from the devserver.
     *
     * @param $filename
     * @return bool|\Google\Cloud\Storage\StorageObject
     */
    static function get_storage_object($filename) {
        $scheme = parse_url($filename, PHP_URL_SCHEME);
        $bucket = parse_url($filename, PHP_URL_HOST);
        $path = parse_url($filename, PHP_URL_PATH);
        $object_name = trim($path, "/");
        if ($scheme == "gs") {
            $client = self::get_storage_client();
            $bucket = $client->bucket($bucket);
            $object = $bucket->object($object_name);
            return $object;
        } else {
            syslog(LOG_WARNING, "Trying to get storage object on invalid url: $filename");
            return false;
        }
    }

    static function get_storage_json($filename, $default = null) {
        $object = self::get_storage_object($filename);
        if ($object) {
            $json_string = $object->downloadAsString();
            $array_with_content = json_decode($json_string, JSON_OBJECT_AS_ARRAY);
            return $array_with_content;
        } else {
            return $default;
        }
    }

    static function ensure_directory($directory) {
        if (!file_exists($directory)) {
            mkdir($directory);
            syslog(LOG_INFO, "Created new directory: $directory");
        }
    }

    static function get_json($filename, $default = null) {
        self::ensure_gs_streamwrapper_registered($filename);
        if (file_exists($filename)) {
            $json = file_get_contents($filename);
            $data = json_decode($json, JSON_OBJECT_AS_ARRAY);
        } else {
            $data = $default;
        }
        return $data;
    }

    static function put_json($filename, $data) {
        self::ensure_gs_streamwrapper_registered($filename);
        return file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
    }

    static function get_filenames($from_directory) {
        $filenames = [];
        foreach (scandir($from_directory) as $filename) {
            $full_path = $from_directory . DIRECTORY_SEPARATOR . $filename;
            if (is_file($full_path)) {
                $filenames[] = $filename;
            }
        }
        return $filenames;
    }
}
