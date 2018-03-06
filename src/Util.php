<?php

namespace GaeUtil;

use DateInterval;
use DateTime;
use google\appengine\api\app_identity\AppIdentityService;
use google\appengine\api\users\UserService;

class Util {

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

    static public function quoteArray($array) {
        $quotedvalues = [];
        foreach ($array as $value) {
            $quotedvalues[] = "'" . $value . "'";
        }
        return implode(",", $quotedvalues);
    }

    static function ymdDate($time = null) {
        return date("Y-m-d", $time);
    }

    static function todayYmd() {
        return date("Y-m-d");
    }

    static function yesterday() {
        return Util::dateBefore(Util::todayYmd());
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

    /**
     * Used to inspect shit
     *
     * @param type $filename
     */
    static function save_included_files($filename, $fromFile) {
        if (file_exists($filename)) {
            $all_included_files = json_decode(file_get_contents($filename), JSON_OBJECT_AS_ARRAY);
        } else {
            $all_included_files = array();
        }
        foreach (get_included_files() as $includedFile) {
            if (!isset($all_included_files[$includedFile])) {
                $all_included_files[$includedFile] = array();
            }
            if (!in_array($fromFile, $all_included_files[$includedFile])) {
                array_push($all_included_files[$includedFile], $fromFile);
            }
        }
        file_put_contents($filename, json_encode($all_included_files));
    }

    static function shortdate($DateTime) {
        if (!is_a($DateTime, "DateTime")) {
            $DateTime = new DateTime($DateTime);
        }
        $month = $DateTime->format("n");
        $nor = [
            1 => "JAN",
            2 => "FEB",
            3 => "MAR",
            4 => "APR",
            5 => "MAI",
            6 => "JUN",
            7 => "JUL",
            8 => "AUG",
            9 => "SEP",
            10 => "OKT",
            11 => "NOV",
            12 => "DES",
        ];
        return "'" . $nor[$month] . " " . $DateTime->format("y");
    }

    static function dump($variable) {
        echo("<pre>");
        print_r($variable);
        echo("</pre>");
    }


    static function getProtocol() {
        return "http" . ($_SERVER["HTTPS"] === "on" ? "s" : "") . "://";
    }

    static function get_home_url() {
        return self::getProtocol() . $_SERVER["HTTP_HOST"];
    }

    static function get_full_path($path, $query = "") {
        if (is_array($query)) {
            $query = "?" . http_build_query($query);
        }
        return self::get_home_url() . $path . $query;
    }

    static function offset_array($data, $offset, $limit) {
        $record_count = count($data);
        $offset_data = [];
        for ($i = 0; $i <= $record_count; $i++) {
            if ($i >= $offset && count($offset_data) < $limit && isset($data[$i])) {
                $offset_data[] = $data[$i];
            }
        }
        return $offset_data;
    }

    static function sort_by_key(&$data, $key) {
        if ($key[0] === "-") {
            $asc = false;
            $key = ltrim($key, '-');
        } else {
            $asc = true;
        }
        return usort($data, function ($a, $b) use ($key, $asc) {
            if ($asc) {
                return strcmp($a[$key], $b[$key]);
            } else {
                return strcmp($b[$key], $a[$key]);
            }
        });
    }

    /**
     * StartsWith
     * Tests if a text starts with an given string.
     *
     * @param     string
     * @param     string
     * @return    bool
     */
    static function starts_with($haystack, $needle) {
        return strpos($haystack, $needle) === 0;
    }

    static function cmdline($message) {
        echo $message . PHP_EOL;
    }

    static function get_random_array_elements($input_array, $min = 1, $max = 3) {
        $number_for_client = rand($min, $max);
        $random = array_rand($input_array, $number_for_client);
        $output_array = [];
        if ($number_for_client === 1) {
            $output_array[] = $input_array[$random];
        } else {
            foreach ($random as $key) {
                $output_array[] = $input_array[$key];
            }
        }
        return $output_array;
    }

    static function get_root_domain($siteurl) {
        $url_host = parse_url($siteurl, PHP_URL_HOST);
        $parts = explode(".", $url_host);
        return $parts[count($parts) - 2];
    }

    static function isDevServer() {
        return (strpos(getenv('SERVER_SOFTWARE'), 'Development') === 0);
    }

    static function getVendorDir() {
        if (defined("COMPOSER_VENDOR_DIR")) {
            $vendorDir = COMPOSER_VENDOR_DIR;
        } else {
            $reflection = new \ReflectionClass(\Composer\Autoload\ClassLoader::class);
            $vendorDir = dirname(dirname($reflection->getFileName()));
        }
        return $vendorDir;
    }

    static function redirect($url) {
        header('Location: ' . $url);
        exit;
    }

    static function link($url, $text) {
        return "<a href='$url'>$text</a>";
    }

    /**
     * @return bool|string
     */
    static function resolveFilePath() {

        $filepath = implode(DIRECTORY_SEPARATOR, func_get_args());
        $filepath_real = realpath($filepath);
        return $filepath_real;
    }

    static function get_current_module() {
        return getenv("CURRENT_MODULE_ID");
    }

    static function get_current_application() {
        return AppIdentityService::getApplicationId();

    }

    static function get_current_user_email() {
        return UserService::getCurrentUser()->getEmail();

    }

    /**
     * @param $array_name
     * @param $array
     * @param $required_key
     * @throws \Exception
     */
    static function key_exist_or_fail($array_name, $array, $required_key) {
        if (is_array($required_key)) {
            foreach ($required_key as $key) {
                self::key_exist_or_fail($array_name, $array, $key);
            }
        } else {
            if (!isset($array[$required_key])) {
                throw new \Exception("$array_name need the '$required_key' parameter");
            }
        }

    }

    static function is_array_or_fail($array_name, $array) {
        if (!is_array($array)) {
            throw new \Exception("$array_name should be an array.");
        }
    }

    static function get_tempfilename() {
        $module = self::get_current_module();
        return tempnam(sys_get_temp_dir(), $module);
    }

    /**
     * @return bool|string
     */
    static function path_maker() {
        $path_parts = [];
        foreach (func_get_args() as $string) {
            $string = str_replace("\\", "/", $string);
            $parts = explode("/", $string);
            $path_parts = array_merge($path_parts, $parts);
        }
        $path = implode(DIRECTORY_SEPARATOR, $path_parts);
        $path = realpath($path);
        return $path;
    }


    static function command_maker($command, $params = []) {
        $parts = [$command];
        foreach ($params as $key => $val) {
            $parts[] = "--$key=$val";
        }
        return implode(" ", $parts);
    }

    static function domain_from_email($email) {
        // make sure we've got a valid email
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // split on @ and return last value of array (the domain)
            $parts = explode('@', $email);
            return array_pop($parts);
        } else {
            return false;
        }

    }

    static function local_part_from_email($email) {
        // make sure we've got a valid email
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // split on @ and return last value of array (the domain)
            $parts = explode('@', $email);
            return array_shift($parts);
        } else {
            return false;
        }
    }
}


