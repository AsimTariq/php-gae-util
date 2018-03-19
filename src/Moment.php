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
    const ONEHOUR = 3600;

    static function mysqlDatetime($time = null) {
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

    /**
     * Finner neste timestamp basert på input tid.
     * @param type $time
     * @return type
     */
    static public function nextSameWeekday($time) {
        $originalTime = strtotime($time);
        $nextWeekdayText = date("l");
        $nextWeekday = strtotime("next " . $nextWeekdayText);
        return strtotime(date("Y-m-d", $nextWeekday) . " " . date("H:i:s", $originalTime));
    }

    static function ymdDate($time = null) {
        return date("Y-m-d", $time);
    }

    static function strtoYdate($string) {
        return self::ymdDate(strtotime($string));
    }

    static function todayYmd() {
        return date("Y-m-d");
    }

    static function yesterday() {
        return self::dateBefore(self::todayYmd());
    }

    static function strtodate($str) {
        return date("Y-m-d", strtotime($str));
    }

    static function timetodate($time) {
        return date("Y-m-d", $time);
    }

    static function dateBefore($date) {
        return date("Y-m-d", strtotime($date . " -1 day"));
    }

    static function dateAfter($date) {
        return date("Y-m-d", strtotime($date . " +1 day"));
    }

    static function getLastDay($first_period, $length) {
        if (is_string($first_period)) {
            $thisDateTime = new DateTime($first_period);
        } else {
            $thisDateTime = $first_period;
        }
        $thisDateTime->add(new DateInterval("P" . $length . "M"));
        $thisDateTime->sub(new DateInterval("P1D"));
        return $thisDateTime;
    }

    static function getPeriods($first_period, $length) {
        $first_month = new DateTime($first_period);
        $output = array();
        for ($i = 1; $i <= $length; $i++) {
            $output[] = clone $first_month;
            $first_month->add(new DateInterval("P1M"));
        }
        return $output;
    }
}