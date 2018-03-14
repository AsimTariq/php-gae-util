<?php
/**
 * Created by PhpStorm.
 * User: michael
 * Date: 29/01/2018
 * Time: 12:02
 */

namespace GaeUtil;

use GuzzleHttp\Client;

class GoogleAccess {

    static function analyticsReadonly() {

    }

    static function webmastersReadonly() {
        $scope = Google_Service_Webmasters::WEBMASTERS_READONLY;
        $clients = Auth::getGoogleClientsByScope($scope);
        $accounts = [];
        foreach ($clients as $client) {
            $token = $client->getAccessToken();
            $sites = SearchConsole::getVerifiedSitesFromClient($client);
            foreach ($sites as $site) {
                $siteUrl = $site["siteUrl"];
                $accounts[$siteUrl]["siteUrl"] = $siteUrl;
                $accounts[$siteUrl]["access"][] = [
                    "access_token" => $token["access_token"],
                    "created" => $token["created"],
                    "expires_in" => $token["expires_in"],
                    "email" => $token["email"],
                    "picture" => $token["picture"],
                    "permissionLevel" => $site["permissionLevel"],
                ];
            }
        }
        $accounts = array_values($accounts);
    }

    /**
     * @return \Google_Client
     */
    static function get_google_client($logger_name = null) {
        if (is_null($logger_name)) {
            $logger_name = "Google_Client at " . Util::get_current_module();
        }
        $client = new \Google_Client();
        $client->useApplicationDefaultCredentials(true);
        $client->setApplicationName(Util::get_current_module() . "@" . Util::get_current_application());
        $client->setLogger(Logger::create($logger_name));
        if (Util::isDevServer()) {
            $http = self::createWindowsCompliantHttpClient();
            $client->setHttpClient($http);
        }
        return $client;
    }

    /**
     * @param $base_path
     * @return Client
     */
    static function createWindowsCompliantHttpClient($base_path = null) {
        // guzzle 6

        $options = [
            'exceptions' => false,
            'base_uri' => $base_path,
            'sink' => Util::get_tempfilename()
        ];

        return new Client($options);
    }
}