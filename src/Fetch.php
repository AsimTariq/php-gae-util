<?php
/**
 * Created by PhpStorm.
 * User: michael
 * Date: 07/02/2018
 * Time: 19:28
 */

namespace GaeUtil;


class Fetch {

    /**
     * Fetching an url secured by the Internal accesstoken.
     *
     * @param $url
     * @param array $params
     * @return mixed
     */
    static public function secure_url($url, $params = []) {
        $headers = [
            "Authorization: Bearer " . JWT::getInternalToken()
        ];
        $opts = [
            "http" => [
                "method" => "GET",
                "header" => implode("\r\n", $headers)
            ]
        ];
        $stream_context = stream_context_create($opts);
        if (count($params)) {
            $url = $url . "?" . http_build_query($params);
        }
        syslog(LOG_INFO, "fetching: " . $url);
        $content = file_get_contents($url, false, $stream_context);
        $result = json_decode($content, JSON_OBJECT_AS_ARRAY);
        return $result;
    }

    static public function secure_url_cached($url, $params = []) {
        $cacheKey = Cached::keymaker(__METHOD__, $url);
        $cached = new Cached($cacheKey, false);
        if (!$cached->exists()) {
            $result = self::secure_url($url);
            syslog(LOG_INFO, "Returned " . count($result) . " rows.");
            $cached->set($result);
        }
        return $cached->get();
    }
}