<?php
/**
 * Created by PhpStorm.
 * User: michael
 * Date: 2018-12-09
 * Time: 16:55
 */

namespace GaeUtil\Dtos;

class PartlyEncodedJson {

    var $attributes = [];
    var $secretFields = [];
    var $keyName;
    var $ciphertext;
    var $created;
    var $version;
}