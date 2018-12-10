<?php

use GaeUtil\DataStore;
use PHPUnit\Framework\TestCase;

class UtilTest extends TestCase {

    var $testSchema = "someSchema";
    public function setUp() {
        DataStore::changeToTestMode();
        DataStore::deleteAll($this->testSchema);
    }

    function testDatastore() {
        $input_data = [
            "name" => "Hello world",
            "array" => ["with", "values"]
        ];
        DataStore::upsert($this->testSchema, __METHOD__, $input_data);
        $actual = DataStore::fetchAll($this->testSchema);
        $this->assertEquals([$input_data], $actual);
    }
}