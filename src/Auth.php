<?php
/**
 * Created by PhpStorm.
 * User: michael
 * Date: 15/11/2017
 * Time: 16:42
 */

namespace GaeUtil;

use google\appengine\api\users\UserService;

class Auth {

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
            "signup_application" => Util::get_current_application(),
            "signup_service" => Util::get_current_module()
        ];
        $access_token = $client->getAccessToken();
        foreach (["access_token", "token_type", "expires_in", "refresh_token", "created"] as $key) {
            $user_data[$key] = $access_token[$key];
        }
        return $user_data;
    }

    static function getCallbackUrl() {
        return Util::get_home_url() . Conf::get("auth_callback_url", getenv('AUTH_CALLBACK_URL'));
    }

    static function getClientSecret() {

    }

    static function getAuthRedirectUrl() {
        $client = self::getClient();
        return $client->createAuthUrl();
    }

    static function getScopes() {
        $scopes = Conf::get("scopes", []);
        /**
         * Adding some default scopes. We probably should always know who the client is
         */
        $scopes[] = 'https://www.googleapis.com/auth/userinfo.email';
        $scopes[] = "https://www.googleapis.com/auth/userinfo.profile";
        $scopes = array_unique($scopes);
        return $scopes;
    }

    /**
     * @return \Google_Client
     */
    static protected function _getClient() {
        $client = new \Google_Client();
        $client->addScope(self::getScopes());
        $logName = "GoogleClientFor" . Util::get_current_module();
        $client->setLogger(Logger::create($logName));
        $client_json_path = Conf::getConfFilepath('client_secret.json');
        $client->setAuthConfig($client_json_path);
        return $client;
    }

    /**
     * @TODO Shoudl be renamed getClientByEmail
     *
     * @return \Google_Client
     */
    static function getClient($user_email = false) {
        $client = self::_getClient();
        $client->setRedirectUri(self::getCallbackUrl());
        $client->setAccessType('offline');        // offline access
        $client->setIncludeGrantedScopes(true);   // incremental auth
        $client->setApprovalPrompt('force');
        $current_user = UserService::getCurrentUser();
        if ($current_user) {
            $client->setLoginHint($current_user->getEmail());
        }
        if ($user_email) {
            $user_data = DataStore::retriveTokenByUser($user_email);
            if ($user_data && $user_data->access_token) {
                $user_data_content = $user_data->getData();
                $client = self::refreshTokenIfExpired($user_data_content, $client);
            }
        }
        return $client;
    }

    static function getCurrentUserEmail() {
        $current_user = UserService::getCurrentUser();
        $user_email = $current_user->getEmail();
        return $user_email;
    }

    static function getGoogleClientForCurrentUser() {
        $user_email = self::getCurrentUserEmail();
        return self::getClient($user_email);
    }

    static public function refreshTokenIfExpired($user_data_content, \Google_Client $client = null) {
        if (is_null($client)) {
            $client = self::_getClient();
        }
        $client->setAccessToken($user_data_content);
        $user_email = $user_data_content["email"];
        if ($client->isAccessTokenExpired()) {
            Syslog(LOG_INFO, "Refreshing token for $user_email.");
            $new_token = $client->fetchAccessTokenWithRefreshToken();
            if ($new_token) {
                foreach (["access_token", "token_type", "expires_in", "refresh_token", "created"] as $key) {
                    $user_data_content[$key] = $new_token[$key];
                }
                DataStore::saveToken($user_email, $user_data_content);
                $client->setAccessToken($user_data_content);
            } else {
                syslog(LOG_WARNING, "Token refresh failed for $user_email .");
            }
        }
        return $client;
    }

    static function createLoginURL() {
        if (Util::isDevServer()) {
            $root = Util::get_home_url();
        } else {
            $root = "";
        }
        $login_url = $root . UserService::createLoginURL(self::getCallbackUrl() . "?next=google");
        return $login_url;
    }

    static function createLogoutURL() {
        if (Util::isDevServer()) {
            return Util::get_home_url() . UserService::createLogoutURL("/");
        } else {
            return UserService::createLogoutURL("/");
        }
    }

    static function getCurrentUserSessionData($authorized_domains = []) {
        $data = [];
        $data["logged_in"] = false;
        $data["is_admin"] = false;
        $data["user_id"] = null;
        $data["user_email"] = null;
        $data["user_nick"] = null;
        $data["access_token"] = null;
        $data["user_domain"] = null;
        $data["logout_url"] = self::createLogoutURL();
        $data["login_url"] = self::createLoginURL();

        $current_user = UserService::getCurrentUser();
        if ($current_user) {
            $user_email = $current_user->getEmail();
            $user_is_admin = UserService::isCurrentUserAdmin();
            $data["is_admin"] = $user_is_admin;
            $data["user_id"] = $current_user->getUserId();
            $data["user_email"] = $user_email;
            $data["user_nick"] = $current_user->getNickname();
            $user_domain = Util::domain_from_email($user_email);
            $data["user_domain"] = $user_domain;
            if (in_array($user_domain, $authorized_domains) || $user_is_admin) {
                $data["logged_in"] = true;
                $data["jwt_token"] = JWT::getExternalToken($user_email);
                /**
                 * Getting data from the Google Client
                 */
                $client = self::getClient($user_email);
                $access_token = $client->getAccessToken();
                if (is_null($access_token["access_token"])) {
                    $data["logged_in"] = false;
                    $data["login_url"] = Auth::getAuthRedirectUrl();
                }

                /**
                 * Getting previous stored data from DataStore
                 */
                $user_data = DataStore::retriveTokenByUser($user_email);
                if ($user_data) {
                    $user_data = $user_data->getData();
                    foreach (["family_name", "given_name", "name", "gender", "picture", "name", "locale", "verified_email"] as $key) {
                        if (isset($user_data[$key])) {
                            $data[$key] = $user_data[$key];
                        } else {
                            $data[$key] = null;
                        }
                    }
                }
            } else {
                syslog(LOG_WARNING, "trying to access application with invalid credentials.");
                /** @TODO Here should 401 be handled. */
            }

        }
        return $data;
    }

    /**
     * @param $code
     * @return mixed
     */
    static function fetchAndSaveTokenByCode($code) {
        $client = self::getClient();
        $client->fetchAccessTokenWithAuthCode($code);
        $user_data = self::getUserDataFromClient($client);
        $user_email = $user_data["email"];
        DataStore::saveToken($user_email, $user_data);
        return $user_data;
    }

    /**
     * @param $scope
     * @return \Google_Client[]
     */
    static function getGoogleClientsByScope($scope, $domain = null) {
        $data = DataStore::retriveTokensByScope($scope, $domain);
        $clients = [];
        foreach ($data as $i => $user_data_content) {
            try {
                $clients[] = Auth::refreshTokenIfExpired($user_data_content);
            } catch (\Exception $e) {
                syslog(LOG_WARNING, $e->getMessage());
            }
        }
        return $clients;
    }

    static function callback_handler($get_request) {
        try {
            /**
             * Accepting multiple auth cycles.
             * Wrapping this all into a try catch.
             */
            if (isset($get_request["next"])) {
                switch ($get_request["next"]) {
                    case "google":
                        Util::redirect(Auth::getAuthRedirectUrl());
                        break;
                    default:
                        echo "Invalid Provider";
                        break;
                }
            } elseif (isset($get_request["code"])) {
                $code = $get_request["code"];
                $user_data = Auth::fetchAndSaveTokenByCode($code);
                $current_user_email = Auth::getCurrentUserEmail();
                if ($user_data) {
                    /**
                     * Checking if user is logged in with same user as autenticated. Problem on dev servers.
                     * And when user logging in with another account.
                     */
                    if ($user_data["email"] != $current_user_email) {
                        Util::redirect(Auth::getAuthRedirectUrl());
                    } else {
                        $redirect_back_to_front = Conf::get("frontend_url", "/");
                        Util::redirect($redirect_back_to_front);
                    }
                } else {
                    Util::cmdline("Error saving token");
                }
            } elseif (isset($get_request["error"])) {
                switch ($get_request["error"]) {
                    case "access_denied":
                        echo "Access Denied. ";
                        break;
                    default:
                        echo "Something Went Wrong. ";
                        break;
                }
                echo Util::link(Auth::getAuthRedirectUrl(), "RETRY");
            } else {
                Util::redirect(Auth::getAuthRedirectUrl());
            }
        } catch (\Exception $e) {
            Util::cmdline($e->getMessage());
            syslog(LOG_ALERT, $e->getMessage());
        }

    }

    static function get_current_user_email() {
        return UserService::getCurrentUser()->getEmail();

    }

    static function get_current_user_domain() {
        $email = self::get_current_user_email();
        return Util::domain_from_email($email);
    }
}