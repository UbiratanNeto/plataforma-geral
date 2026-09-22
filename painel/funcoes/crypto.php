<?php
/**
 * painel/funcoes/crypto.php
 * Criptografia reversível para dados sensíveis (AES-256-GCM)
 */

/**
 * Criptografa uma string usando AES-256-GCM
 */
function smtp_encrypt(string $plaintext): string {
    $plaintext = (string)$plaintext;
    if ($plaintext === '') return '';

    // Verifica se a chave foi definida
    $keyHex = defined('SMTP_SECRET_KEY') ? SMTP_SECRET_KEY : '';
    if ($keyHex === '' || strlen($keyHex) !== 64) {
        throw new RuntimeException('SMTP_SECRET_KEY inválida (precisa ter 64 chars hex).');
    }

    $key = hex2bin($keyHex);
    if ($key === false || strlen($key) !== 32) {
        throw new RuntimeException('SMTP_SECRET_KEY não pode ser convertida.');
    }

    // GCM recomenda IV de 12 bytes
    $iv = random_bytes(12);
    $tag = '';

    $cipher = openssl_encrypt(
        $plaintext,
        'aes-256-gcm',
        $key,
        OPENSSL_RAW_DATA,
        $iv,
        $tag,
        '',
        16
    );

    if ($cipher === false) {
        throw new RuntimeException('Falha ao criptografar.');
    }

    // Armazena no banco compactado em base64: iv (12) + tag (16) + cipher
    return base64_encode($iv . $tag . $cipher);
}

/**
 * Descriptografa a string codificada em AES-256-GCM
 */
function smtp_decrypt(string $encoded): string {
    if ($encoded === '') return '';

    $data = base64_decode($encoded, true);
    if ($data === false || strlen($data) < 28) {
        return ''; // Retorna vazio caso o dado não esteja criptografado no formato esperado
    }

    $keyHex = defined('SMTP_SECRET_KEY') ? SMTP_SECRET_KEY : '';
    if ($keyHex === '' || strlen($keyHex) !== 64) {
        throw new RuntimeException('SMTP_SECRET_KEY inválida.');
    }

    $key = hex2bin($keyHex);
    $iv = substr($data, 0, 12);
    $tag = substr($data, 12, 16);
    $cipher = substr($data, 28);

    $plain = openssl_decrypt(
        $cipher,
        'aes-256-gcm',
        $key,
        OPENSSL_RAW_DATA,
        $iv,
        $tag
    );

    if ($plain === false) {
        throw new RuntimeException('Falha ao descriptografar (tag/chave inválida).');
    }

    return $plain;
}