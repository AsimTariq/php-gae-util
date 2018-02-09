<?php

namespace GaeUtil;

/**
 * Description of GaeUtil
 *
 * @author michael
 */
class Files {

    function check() {
        return "Hello Gae";
    }

    static function downloadUrlToTempFile($url) {
        $DownloadPath = Util::get_tempfilename();
        $fp = fopen($DownloadPath, 'w');
        fwrite($fp, file_get_contents($url));
        fclose($fp);
        return $DownloadPath;
    }

    static function ensure_directory($directory) {
        if (!file_exists($directory)) {
            mkdir($directory);
            syslog(LOG_INFO, "Created new directory: $directory");
        }
    }

    static function get_json($filename, $default = null) {
        if (file_exists($filename)) {
            $json = file_get_contents($filename);
            $data = json_decode($json, JSON_OBJECT_AS_ARRAY);
        } else {
            $data = $default;
        }
        return $data;
    }

    static function put_json($filename, $data) {
        return file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
    }
}
