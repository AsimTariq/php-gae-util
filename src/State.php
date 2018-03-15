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
            $client = Auth::getGoogleClientByEmail($user->getEmail());
        } else {
            return Auth::createLogoutURL();
        }
    }

    static function status($links = []) {
        $data = [
            "application_id" => Util::getApplicationId(),
            "service" => Util::getModuleId(),
            "is_dev" => self::isDevServer(),
            "default_hostname" => AppIdentityService::getDefaultVersionHostname(),
            "is_admin" => false,
        ];
        $user = UserService::getCurrentUser();
        if ($user) {
            $data["user"] = $user;
            $data["logout"] = Auth::createLogoutURL("/");
        } else {
            $data["login"] = Auth::createLoginURL();
        }

        if (UserService::isCurrentUserAdmin()) {
            $data["is_admin"] = true;
            $data["composer"] = Composer::getComposerData();
            $data["internal_token"] = "Bearer ".JWT::getInternalToken();
            $data["external_token"] = "Bearer ".JWT::getExternalToken(Auth::getCurrentUserEmail(),Moment::ONEDAY);
        }
        $data["links"] = $links;

        return $data;
    }
}