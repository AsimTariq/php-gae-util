<?php
/**
 * Created by PhpStorm.
 * User: michael
 * Date: 2019-01-04
 * Time: 00:57
 */

use GaeUtil\Conf;
use GaeUtil\Fetch;
use GaeUtil\JWT;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class FetchTest extends TestCase {

    /** @var Process */
    private static $process;

    public static function setUpBeforeClass() {
        $killProcess = new Process("kill -9 $(lsof -t -i:49231)");
        $attemts = 0;
        while ($killProcess->isRunning()) {
            sleep(1);
            $attemts++;
            if ($attemts > 4) {
                exit("Can't start dummy server.");
            }
        }
        $serverFile = dirname(__FILE__) . DIRECTORY_SEPARATOR . "base" . DIRECTORY_SEPARATOR . "polly.php";
        self::$process = new Process("php -S localhost:49231 $serverFile");
        self::$process->start();
        sleep(1);
    }

    public static function tearDownAfterClass() {
        self::$process->stop();
    }

    public function testSecureUrl() {
        $internalSecret = JWT::generateSecret();
        Conf::getInstance()->set(JWT::CONF_INTERNAL_SECRET_NAME, $internalSecret);
        $token = "Bearer " . JWT::getInternalToken();
        $testResponseData = Fetch::secureUrl("http://localhost:49231");
        $this->assertArrayHasKey("DOCUMENT_ROOT", $testResponseData);
        $this->assertSame($token, $testResponseData["HTTP_AUTHORIZATION"]);
    }

}
