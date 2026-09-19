<?php

function ucEncryptionKey(){
    $secret = getenv('SESSION_SECRET');
    if($secret === false || $secret === ''){
        throw new RuntimeException('SESSION_SECRET configure nahi hai.');
    }
    return hash('sha256', $secret, true);
}

function ucEncrypt($value){
    $iv = random_bytes(12);
    $tag = '';
    $ciphertext = openssl_encrypt($value, 'aes-256-gcm', ucEncryptionKey(), OPENSSL_RAW_DATA, $iv, $tag);
    if($ciphertext === false){
        throw new RuntimeException('Sensitive data encrypt nahi ho saka.');
    }
    return base64_encode($iv . $tag . $ciphertext);
}

function ucDecrypt($encoded){
    $payload = base64_decode($encoded, true);
    if($payload === false || strlen($payload) < 29){
        throw new RuntimeException('Encrypted data invalid hai.');
    }

    $iv = substr($payload, 0, 12);
    $tag = substr($payload, 12, 16);
    $ciphertext = substr($payload, 28);
    $plaintext = openssl_decrypt($ciphertext, 'aes-256-gcm', ucEncryptionKey(), OPENSSL_RAW_DATA, $iv, $tag);
    if($plaintext === false){
        throw new RuntimeException('Encrypted data read nahi ho saka.');
    }
    return $plaintext;
}

function ucNormalizeMac($macId){
    return strtoupper(trim((string)$macId));
}
