<?php
/**
 * Created by PhpStorm.
 * User: michael
 * Date: 05/02/2018
 * Time: 16:39
 */

namespace GaeUtil;

use Noodlehaus\Config;

/**
 * This module is a wrapper around the Google Key Management Service and
 * provides a secure way to store secrets on Google App Engine.
 *
 * Class Secrets
 * @package GaeUtil
 */
class Secrets {

    const CONF_PROJECT_ID_NAME = "kms_project_id";
    const CONF_KEYRING_ID_NAME = "kms_keyring_id";
    const CONF_KEY_ID_NAME = "kms_cryptokey_id";

    static function getProjectId() {
        return Conf::get(self::CONF_PROJECT_ID_NAME);
    }

    static function getKeyRingId() {
        return Conf::get(self::CONF_KEYRING_ID_NAME);
    }

    static function getCryptoKeyId() {
        return Conf::get(self::CONF_KEY_ID_NAME);
    }

    /**
     * Support passing the config object directly.
     *
     * @param Config|null $conf
     * @return string
     */
    static function getDefaultKeyName(Config $conf = null) {
        $locationId = "global";
        if (is_null($conf)) {
            $projectId = self::getProjectId();
            $keyRingId = self::getKeyRingId();
            $cryptoKeyId = self::getCryptoKeyId();
        } else {
            $projectId = $conf->get(self::CONF_PROJECT_ID_NAME);
            $keyRingId = $conf->get(self::CONF_KEYRING_ID_NAME);
            $cryptoKeyId = $conf->get(self::CONF_KEY_ID_NAME);
        }

        // The resource name of the cryptokey.
        return sprintf('projects/%s/locations/%s/keyRings/%s/cryptoKeys/%s',
            $projectId,
            $locationId,
            $keyRingId,
            $cryptoKeyId
        );
    }


    static function config($projectId, $keyRingId, $cryptoKeyId) {
        Conf::getInstance()->set(self::CONF_PROJECT_ID_NAME, $projectId);
        Conf::getInstance()->set(self::CONF_KEYRING_ID_NAME, $keyRingId);
        Conf::getInstance()->set(self::CONF_KEY_ID_NAME, $cryptoKeyId);
    }

    /**
     * @return \Google_Service_CloudKMS
     */
    static function getService() {
        $client = new \Google_Client();
        $client->useApplicationDefaultCredentials();
        $client->addScope('https://www.googleapis.com/auth/cloud-platform');
        if (0) {
            $client->addScope('https://www.googleapis.com/auth/userinfo.email');
            $service = new \Google_Service_Oauth2($client);
            $user_info = $service->userinfo_v2_me->get();
            $user_obj = $user_info->toSimpleObject();
        }
        // Create the Cloud KMS client.
        $kms = new \Google_Service_CloudKMS($client);
        return $kms;
    }

    /**
     * Takes an input file and encrypts using the KMS service
     * and puts it to an outputfile.
     *
     * @param $plaintextFileName
     * @param $ciphertextFileName
     * @return bool
     */
    static function encrypt($plaintextFileName, $ciphertextFileName) {
        $kms = self::getService();
        $name = self::getDefaultKeyName();
        // Use the KMS API to encrypt the text.
        $encoded = base64_encode(file_get_contents($plaintextFileName));
        $request = new \Google_Service_CloudKMS_EncryptRequest();
        $request->setPlaintext($encoded);
        $response = $kms->projects_locations_keyRings_cryptoKeys->encrypt(
            $name,
            $request
        );

        // Write the encrypted text to a file.
        file_put_contents($ciphertextFileName, base64_decode($response['ciphertext']));
        Util::cmdline("\tSaved encrypted text to $ciphertextFileName with key $name");
        return true;
    }


    /**
     * @param $ciphertextFileName
     * @param Conf|null $conf
     * @return bool|string
     */
    static function decrypt($ciphertextFileName, Config $config = null) {
        // Instantiate the client, authenticate, and add scopes.
        $kms = self::getService();
        $name = self::getDefaultKeyName($config);
        // Use the KMS API to decrypt the text.
        $ciphertext = base64_encode(file_get_contents($ciphertextFileName));
        $request = new \Google_Service_CloudKMS_DecryptRequest();
        $request->setCiphertext($ciphertext);
        $response = $kms->projects_locations_keyRings_cryptoKeys->decrypt(
            $name,
            $request
        );
        return base64_decode($response['plaintext']);
    }


}