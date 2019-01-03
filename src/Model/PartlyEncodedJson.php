<?php
/**
 * Created by PhpStorm.
 * User: michael
 * Date: 2018-12-09
 * Time: 16:55
 */

namespace GaeUtil\Model;

class PartlyEncodedJson extends \Google_Model {

    public $attributes = [];
    public $secretFields = [];
    public $keyName;
    public $ciphertext;
    public $created;
    public $version;
}