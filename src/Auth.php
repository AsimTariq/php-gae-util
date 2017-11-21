<?php
/**
 * Created by PhpStorm.
 * User: michael
 * Date: 15/11/2017
 * Time: 16:42
 */

namespace GaeUtil;

use google\appengine\api\app_identity\AppIdentityService;
use google\appengine\api\users\UserService;
use JBZoo\Utils\Url;

class Auth {

    static function javascriptLocalStorageRedirectHtml($token, $redirect_uri) {
        $jwt_variable_name = Conf::get("jwt_variable_name", 'JWT_TOKEN');
        return SlimHelper::javascriptLocalStorageRedirectHtml($jwt_variable_name, $token, $redirect_uri);
    }

    static function getUserDataFromClient(\Google_Client $client) {
        $service = new \Google_Service_Oauth2($client);
        $user_info = $service->userinfo_v2_me->get();
        $user_data = [
            "id" => $user_info->getId(),
            "name" => $user_info->getName(),
            "given_name" => $user_info->getGivenName(),
            "family_name" => $user_info->getFamilyName(),
            "email" => $user_info->getEmail(),
            "verified_email" => $user_info->getVerifiedEmail(),
            "gender" => $user_info->getGender(),
            "picture" => $user_info->getPicture(),
            "locale" => $user_info->getLocale(),
            "scopes" => $client->getScopes(),
            "signup_service" => AppIdentityService::getApplicationId()
        ];
        $access_token = $client->getAccessToken();
        foreach (["access_token", "token_type", "expires_in", "refresh_token", "created"] as $key) {
            $user_data[$key] = $access_token[$key];
        }
        return $user_data;
    }

    static function getInitUrl() {
        return Conf::get("auth_init_url", getenv('AUTH_INIT_URL'));
    }

    static function getCallbackUrl() {
        return Conf::get("auth_callback_url", getenv('AUTH_CALLBACK_URL'));
    }

    static function getGuiUrl() {
        return Conf::get("frontend_url", getenv('FRONTEND_URL'));
    }

    static function getClientSecret() {

    }

    static function getAuthRedirectUrl() {
        $client = self::getClient();
        return $client->createAuthUrl();
    }

    /**
     * @return \Google_Client
     */
    static function getClient($user_email = false) {
        $scopes = Conf::get("scopes");
        $scopes[] = 'https://www.googleapis.com/auth/userinfo.email';
        $scopes = array_unique($scopes);
        $client_json_path = Conf::getConfFilepath('client_secret.json');
        $client = new \Google_Client();
        $client->setAuthConfig($client_json_path);
        $client->addScope($scopes);
        $client->setRedirectUri(Url::root() . Conf::get("auth_callback_url"));
        $client->setAccessType('offline');        // offline access
        $client->setIncludeGrantedScopes(true);   // incremental auth
        $client->setApprovalPrompt('force');
        $client->setLogger(SlimHelper::getLogger("nexus-gsc-client"));
        $current_user = UserService::getCurrentUser();
        if ($current_user) {
            $client->setLoginHint($current_user->getEmail());
        }
        if ($user_email) {
            $user_data = DataStore::retriveTokenByUser($user_email);
            if ($user_data && $user_data->access_token) {
                $user_data_content = $user_data->getData();
                $client->setAccessToken($user_data_content);
                if ($client->isAccessTokenExpired()) {
                    Syslog(LOG_INFO, "Refreshing token for $user_email.");
                    $new_token = $client->fetchAccessTokenWithRefreshToken();
                    foreach (["access_token", "token_type", "expires_in", "refresh_token", "created"] as $key) {
                        $user_data_content[$key] = $new_token[$key];
                    }
                    DataStore::saveToken($user_email, $user_data_content);
                }
            }
        }
        return $client;
    }

    static function fetchAndSaveTokenByCode($code) {
        $client = AuthFlow::getClient();
        $client->fetchAccessTokenWithAuthCode($code);
        $user_data = AuthFlow::getUserDataFromClient($client);
        $user_email = $user_data["email"];
        DataStore::saveToken($user_email, $user_data);
        $app_access_token = JWT::get($user_email);
        return $app_access_token;
    }


}