<?php
/**
 * Created by PhpStorm.
 * User: michael
 * Date: 07/11/2018
 * Time: 00:15
 */

namespace GaeUtil\Model;

class AppStatusDto extends \Google_Model {

    public $applicationId;
    public $service;
    public $isDevServer;
    public $defaultHostname;
    public $isAdmin;
    public $user;
    public $links;
    public $errors;
    public $internalToken;
    public $externalToken;
    public $composer;
}