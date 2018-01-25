<?php
/**
 * Created by PhpStorm.
 * User: michael
 * Date: 24/01/2018
 * Time: 14:38
 */
use GaeUtil\Util;

class TestClassForWorkflows {

    public $start_date;

    public function run($param1, $param2) {
        return [Util::dateAfter($this->start_date)];
    }

    public function set_state($start_date) {
        $this->start_date = $start_date;
    }
}