<?php
/**
 * Created by PhpStorm.
 * User: michael
 * Date: 24/11/2017
 * Time: 11:55
 */

namespace GaeUtil;


class Moment {

    const MYSQL_DATE_FORMAT = 'Y-m-d';
    const MYSQL_DATETIME_FORMAT = 'Y-m-d H:i:s';
    const ONEDAY = 86400;

    static function mysqlDateTime($time = null) {
        if (is_null($time)) {
            $time = time();
        }
        return date(self::MYSQL_DATETIME_FORMAT, $time);
    }

    static function mysqlDate($time = null) {
        if (is_null($time)) {
            $time = time();
        }
        return date(self::MYSQL_DATE_FORMAT, $time);
    }
}