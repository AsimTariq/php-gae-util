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
    static public function get($email = null) {
        static $cache;
        if (is_null($email)) {
            /**
             * Creating a token with the current module identity for reference.
             */
            $email = Util::get_current_module() . "@" . Util::get_current_application();
        }
        if (is_null($cache[$email])) {
            $payload = [
                "exp" => time() + 3.154e+7,
                "sub" => $email
            ];
            $cache[$email] = \Firebase\JWT\JWT::encode($payload, self::getSecret(), "HS256");
        }
        return $cache[$email];

    }

    static public function check($jwt_token) {
        $decoded = \Firebase\JWT\JWT::decode($jwt_token, self::getSecret(), ["HS256"]);
        return $decoded;
    }

    static public function getSecret() {
        $jwt_secret = Conf::get("jwt_secret");
        if (is_null($jwt_secret)) {
            throw new \Exception("Trying to use JWT functions without a secret. This has to be set.");
        }
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
        return Util::get_full_path($path, $query_data);
    }

    static public function getTokenForCurrentUser() {
        $user = UserService::getCurrentUser();
        return self::get($user->getEmail());
    }

    static function generate_secret() {
        $random_pseudo_bytes = openssl_random_pseudo_bytes(32);
        return base64_encode($random_pseudo_bytes);
    }



}
