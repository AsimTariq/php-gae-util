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
}
