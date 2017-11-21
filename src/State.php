<?php
/**
 * Created by PhpStorm.
 * User: michael
 * Date: 20/11/2017
 * Time: 23:25
 */

namespace GaeUtil;


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
}