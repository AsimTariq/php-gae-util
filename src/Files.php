<?php

namespace GaeUtil;

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

    static function ensure_gs_streamwrapper_registered($filename) {
        $scheme = parse_url($filename, PHP_URL_SCHEME);
        if ($scheme == "gs" && !in_array('gs', stream_get_wrappers())) {
            $client = new StorageClient();
            $client->registerStreamWrapper();
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
            $full_path = $from_directory.DIRECTORY_SEPARATOR.$filename;
            if(is_file($full_path)){
                $filenames[] = $filename;
            }
        }
        return $filenames;
    }
}
