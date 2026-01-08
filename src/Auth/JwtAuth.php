<?php

namespace Velocix\Auth;

class JwtAuth
{
    protected static $secret = null;
    protected static $algorithm = 'HS256';

    public static function setSecret($secret)
    {
        static::$secret = $secret;
    }

    public static function generate($payload, $expiresIn = 3600)
    {
        $header = [
            'alg' => static::$algorithm,
            'typ' => 'JWT'
        ];

        $payload['iat'] = time();
        $payload['exp'] = time() + $expiresIn;

        $headerEncoded = static::base64UrlEncode(json_encode($header));
        $payloadEncoded = static::base64UrlEncode(json_encode($payload));

        $signature = hash_hmac(
            'sha256',
            $headerEncoded . '.' . $payloadEncoded,
            static::$secret,
            true
        );

        $signatureEncoded = static::base64UrlEncode($signature);

        return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
    }

    public static function verify($token)
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return false;
        }

        list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;

        $signature = hash_hmac(
            'sha256',
            $headerEncoded . '.' . $payloadEncoded,
            static::$secret,
            true
        );

        $signatureCheck = static::base64UrlEncode($signature);

        if ($signatureEncoded !== $signatureCheck) {
            return false;
        }

        $payload = json_decode(static::base64UrlDecode($payloadEncoded), true);

        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return false;
        }

        return $payload;
    }

    public static function getTokenFromRequest()
    {
        $headers = getallheaders();
        $authorization = $headers['Authorization'] ?? $headers['authorization'] ?? null;

        if (!$authorization) {
            return null;
        }

        if (preg_match('/Bearer\s+(.*)$/i', $authorization, $matches)) {
            return $matches[1];
        }

        return null;
    }

    protected static function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    protected static function base64UrlDecode($data)
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}