<?php

use GaeUtil\Conf;
use GaeUtil\JWT;
use PHPUnit\Framework\TestCase;

/**
 * Created by PhpStorm.
 * User: michael
 * Date: 2018-12-22
 * Time: 15:32
 */
class JwtTest extends TestCase {

    function setUp() {
        Conf::getInstance()->set(JWT::CONF_INTERNAL_SECRET_NAME, JWT::generateSecret());
        Conf::getInstance()->set(JWT::CONF_SCOPED_SECRET_NAME, JWT::generateSecret());
    }

    function testGetToken() {
        $email = "test@example.com";
        $jwt_token = JWT::get($email, JWT::CONF_INTERNAL_SECRET_NAME);
        $token = JWT::check($jwt_token, JWT::CONF_INTERNAL_SECRET_NAME);
        $this->assertGreaterThan(time(), $token->exp);
        $this->assertEquals($email, $token->sub);
    }

    function testScopedToken() {
        $scope = "my-dummy-scope";
        $jwt_token = JWT::getScopedToken($scope);
        $token = JWT::check($jwt_token, JWT::CONF_SCOPED_SECRET_NAME);
        $this->assertEquals($scope, $token->scope);

    }
}