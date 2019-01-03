<?php
/**
 * Created by PhpStorm.
 * User: michael
 * Date: 09/11/2018
 * Time: 01:12
 */

namespace GaeUtil\Model;

class WorkflowConfig extends \Google_Model {

    public $handler;
    public $params;
    public $initialState;
    public $name;
    public $active;
    public $maxAge;
    public $application;
    public $service;
    public $created;

}