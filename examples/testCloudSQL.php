<?php

use GaeUtil\CloudSQL;

require_once dirname(__FILE__)."/../vendor/autoload.php";

CloudSQL::cloneProdDatabase(
    "red-tools",
    "redperformance",
    "nexus",
    "red-nexus.appspot.com"
);