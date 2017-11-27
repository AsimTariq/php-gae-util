<?php

namespace GaeUtil;

use google\appengine\api\users\UserService;

/**
 * Description of JWT
 *
 * @author michael
 */
class JWT {

    /**
     * Returns a valid JWT token for this account
     */
    static public function get($username = null) {
        static $token;
        if (is_null($username)) {
            $username_cached = "_";
        } else {
            $username_cached = $username;
        }
        if (is_null($token[$username_cached])) {
            $payload = [
                "exp" => time() + 3.154e+7,
            ];
            if (!is_null($username)) {
                $payload["usr"] = $username;
            }
            $token[$username_cached] = \Firebase\JWT\JWT::encode($payload, self::getSecret(), "HS256");
        }
        return $token[$username_cached];

    }

    static public function check($jwt_token) {
        $decoded = \Firebase\JWT\JWT::decode($jwt_token, self::getSecret(), ["HS256"]);
        return $decoded;
    }

    static public function getSecret() {
        $jwt_secret = Conf::get("jwt_secret");
        return base64_decode($jwt_secret);
    }

    public static function acceptJWTTokenInUrl() {
        $token = (isset($_GET['token']) && !empty($_GET['token'])) ? trim($_GET['token']) : false;
        if ($token) {
            $_SERVER["HTTP_AUTHORIZATION"] = "Bearer " . $token;
        }
    }

    static public function getSecureUrl($path, $query_data = []) {
        $query_data["token"] = self::getTokenForCurrentUser();

        return Util::get_home_url() . $path . "?" . http_build_query($query_data);
    }

    static public function getTokenForCurrentUser() {
        $user = UserService::getCurrentUser();
        return self::get($user->getEmail());
    }

}
