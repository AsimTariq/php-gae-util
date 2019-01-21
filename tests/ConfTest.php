<?php
/**
 * Created by PhpStorm.
 * User: michael
 * Date: 2019-01-04
 * Time: 00:07
 */

use GaeUtil\Conf;
use Noodlehaus\Config;
use PHPUnit\Framework\TestCase;

class ConfTest extends TestCase {

    public function tearDown() {
        Conf::getInstance()->remove("testKey");
        Conf::getInstance()->remove("extraKey");

    }
    public function testGet() {
        $testKey = "testKey";
        $expected = "testVal";
        Conf::getInstance()->set($testKey, $expected);
        $actual = Conf::get($testKey);
        $this->assertEquals($expected, $actual);
    }

    public function testGetInstance() {
        $actual = Conf::getInstance();
        $this->assertInstanceOf(Config::class, $actual);
        $expected = Conf::getInstance();
        $this->assertSame($expected, $actual);
    }

    public function testGetGaeUtilJsonPath() {
        $project_directory = dirname(__FILE__);
        $string = Conf::getGaeUtilJsonPath($project_directory);
        $this->assertStringEndsWith(".json", $string);
    }

    public function testAddConfigFile() {
        $filename = Conf::getConfFolderPath(dirname(__FILE__)) . DIRECTORY_SEPARATOR . "extra.json";
        Conf::addConfigFile($filename);
        $actual = Conf::get("extraKey");
        $this->assertEquals("extraValue", $actual);
    }

}
