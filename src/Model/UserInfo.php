<?php
/**
 * Created by PhpStorm.
 * User: michael
 * Date: 07/11/2018
 * Time: 00:10
 */

namespace GaeUtil\Model;

class UserInfo extends \Google_Model {

    public $accessToken;
    public $created;
    public $email;
    public $expiresIn;
    public $familyName;
    public $gender;
    public $givenName;
    public $id;
    public $locale;
    public $name;
    public $picture;
    public $refreshToken;
    public $scopes;
    public $signupApplication;
    public $signupService;
    public $tokenType;
    public $verifiedEmail;
}