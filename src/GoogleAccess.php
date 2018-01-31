<?php
/**
 * Created by PhpStorm.
 * User: michael
 * Date: 29/01/2018
 * Time: 12:02
 */

namespace GaeUtil;


class GoogleAccess {

    static function analyticsReadonly() {

    }
    static function webmastersReadonly(){
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
}