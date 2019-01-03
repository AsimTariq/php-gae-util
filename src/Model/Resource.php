<?php
/**
 * Created by PhpStorm.
 * User: michael
 * Date: 2019-01-03
 * Time: 14:03
 */

namespace GaeUtil\Model;

class Resource extends \Google_Model {

    public $id;
    public $displayName;
    public $kind;
    public $deleted;
    public $created;
    public $canBeManaged;
}