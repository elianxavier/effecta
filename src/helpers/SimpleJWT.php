<?php
class SimpleJWT
{
    private static $secret = "effecta_super_secret_key_123456_amazing_jwt_key";

    public static function encode($payload)
    {
        $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
        $base64UrlHeader = self::base64UrlEncode($header);
        $base64UrlPayload = self::base64UrlEncode(json_encode($payload));
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, self::$secret, true);
        $base64UrlSignature = self::base64UrlEncode($signature);
        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    public static function decode($jwt)
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) return null;
        list($header, $payload, $signature) = $parts;
        $sig = hash_hmac('sha256', $header . "." . $payload, self::$secret, true);
        if (!hash_equals(self::base64UrlDecode($signature), $sig)) {
            return null;
        }
        $payloadData = json_decode(self::base64UrlDecode($payload), true);
        if (isset($payloadData['exp']) && $payloadData['exp'] < time()) {
            return null; // Token Expirado
        }
        return $payloadData;
    }

    private static function base64UrlEncode($data)
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    private static function base64UrlDecode($data)
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $data .= str_repeat('=', $padlen);
        }
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
    }
}
