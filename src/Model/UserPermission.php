<?php
/**
 * Created by PhpStorm.
 * User: michael
 * Date: 2018-12-24
 * Time: 14:09
 */

namespace GaeUtil\Model;

class UserPermission extends \Google_Model {

    public $resourceKey;
    public $permissions;
    public $userId;
    public $deleted;
    public $created;
}
