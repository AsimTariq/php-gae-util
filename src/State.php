<?php
/**
 * Created by PhpStorm.
 * User: michael
 * Date: 20/11/2017
 * Time: 23:25
 */

namespace GaeUtil;

use google\appengine\api\app_identity\AppIdentityService;
use google\appengine\api\users\UserService;

class State {

    static function isDevServer() {
        return (strpos(getenv('SERVER_SOFTWARE'), 'Development') === 0);
    }

    static function getRedirectUrl() {
        $user = UserService::getCurrentUser();
        if ($user) {
            $client = Auth::getClient($user->getEmail());
        } else {
            return Util::createLogoutURL();
        }
    }

    static function status($links = []) {
        $data = [
            "application_id" => AppIdentityService::getApplicationId(),
            "service" => getenv('CURRENT_MODULE_ID'),
            "is_dev" => self::isDevServer(),
            "default_hostname" => AppIdentityService::getDefaultVersionHostname(),
            "is_admin" => false,
        ];
        $user = UserService::getCurrentUser();
        if ($user) {
            $data["user"] = $user;
            $data["logout"] = Auth::createLogoutURL("/");
        } else {
            $data["login"] = Util::get_home_url() . UserService::createLoginURL("/");
        }
        if (UserService::isCurrentUserAdmin()) {
            $data["is_admin"] = true;
            $data["composer"] = Composer::getComposerData();
            $data["access_token"] = JWT::getTokenForCurrentUser();
        }
        $data["links"] = $links;

        return $data;
    }
}