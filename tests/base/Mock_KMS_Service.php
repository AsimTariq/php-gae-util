<?php

class Mock_KMS_Service {

    var $cipher = "AES-128-CBC";

    private function getKey() {
        return file_get_contents(dirname(__FILE__) . "/test_key.bin");
    }

    public function encrypt($key_name = null, \Google_Service_CloudKMS_EncryptRequest $request) {
        $plaintext = base64_decode($request->getPlaintext());
        $ciphertext = openssl_encrypt($plaintext, $this->cipher, $this->getKey(), $options = 0, str_repeat("d",16));
        $response = new \Google_Service_CloudKMS_EncryptResponse();
        $response->setCiphertext($ciphertext);
        return $response;
    }

    public function decrypt($key_name = null, \Google_Service_CloudKMS_DecryptRequest $request) {
        $ciphertext = $request->getCiphertext();
        $decrypted = openssl_decrypt($ciphertext, $this->cipher, $this->getKey(), $options = 0, str_repeat("d",16));
        $response = new \Google_Service_CloudKMS_DecryptResponse();
        $response->setPlaintext(base64_encode($decrypted));
        return $response;
    }
}