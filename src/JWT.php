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
     * Project-to-project secret.
     * Internal do not expose.
     */
    const CONF_INTERNAL_SECRET_NAME = "jwt_internal_secret";

    /**
     * Frontend Secret.
     * Used to communicate frontend to backend.
     */
    const CONF_EXTERNAL_SECRET_NAME = "jwt_external_secret";

    /**
     * Persistent secret token
     */
    const CONF_SCOPED_SECRET_NAME = "jwt_scoped_secret";

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

    static public function getInternalToken() {
        static $token;
        if(is_null($token)){
            $payload = [
                "exp" => time() + 3.154e+7,
                "sub" => $email
            ];
            $secret = self::getSecret(self::CONF_INTERNAL_SECRET_NAME);
            $token = \Firebase\JWT\JWT::encode($payload, $secret, "HS256");
        }
        $token;
    }

    static public function getExternalToken() {
        $secret = self::getSecret(self::CONF_EXTERNAL_SECRET_NAME);
    }

    static public function getScopedToken($scope) {
        $secret = self::getSecret(self::CONF_SCOPED_SECRET_NAME);

    }

    static public function check($jwt_token) {
        $decoded = \Firebase\JWT\JWT::decode($jwt_token, self::getSecret(), ["HS256"]);
        return $decoded;
    }

    static public function getSecret($type = "jwt_secret") {
        $jwt_secret = Conf::get($type);
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
