<?php

use GaeUtil\Dtos\PartlyEncodedJson;
use GaeUtil\Secrets;
use PHPUnit\Framework\TestCase;

require_once "base/Mock_KMS_Service.php";

class SecretsTest extends TestCase {

    public function setUp() {
        $service = new stdClass();
        $service->projects_locations_keyRings_cryptoKeys = new Mock_KMS_Service();
        Secrets::setService($service);
    }

    public function testDotSecretsEncryptDecrypt() {
        $array_with_secrets = [
            "type" => "dafs",
            ".password" => "asøldfhjalkshdflkas jasdf jlkasdfj asjdfkl klsadfj klasdfj"
        ];
        $encrypted = Secrets::encryptDotSecrets($array_with_secrets, null);
        $decrypted = Secrets::decryptDotSecrets($encrypted);
        $this->assertEquals($array_with_secrets[".password"], $decrypted[".password"]);
    }

    public function testPartlyEncryptedJson() {
        $partly = new PartlyEncodedJson();
        $partly->attributes = [
            "username" => "not encrypted",
            "password" => "sadfasf sadfasdfasad"
        ];
        $partly->secureFields = ["password"];
        $partly->keyName = "dummy";
        $encrypted_json = Secrets::encryptPartlyEncJson($partly);
        $this->assertEquals($encrypted_json->attributes["password"], Secrets::SECRET_DUMMY_VALUE);
        $decrypted_json = Secrets::decyptPartlyEncJson($encrypted_json);
        $this->assertEquals($partly->attributes, $decrypted_json->attributes);
    }
}