<?php

declare(strict_types=1);

namespace App\Modules\Identity\TwoFactor;

/**
 * RFC 6238 TOTP (HMAC-SHA1, 6 digits, 30 s steps), the form every authenticator app reads. Small enough to audit,
 * so the dashboard needs no extra package. Codes are accepted one step either side for clock drift.
 */
final class Totp
{
    public const PERIOD = 30;

    public const DIGITS = 6;

    public const WINDOW = 1;

    private const BASE32 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /** 160-bit secret, base32 without padding. */
    public static function newSecret(): string
    {
        return self::base32Encode(random_bytes(20));
    }

    public static function code(string $secret, int $step): string
    {
        $hash = hash_hmac('sha1', pack('J', $step), self::base32Decode($secret), true);
        $offset = ord($hash[19]) & 0x0F;
        $value = ((ord($hash[$offset]) & 0x7F) << 24) | (ord($hash[$offset + 1]) << 16) | (ord($hash[$offset + 2]) << 8) | ord($hash[$offset + 3]);

        return str_pad((string) ($value % 10 ** self::DIGITS), self::DIGITS, '0', STR_PAD_LEFT);
    }

    /** The step the code matches, or null. Steps at or before $lastStep are refused, so a code can't be replayed. */
    public static function verify(string $secret, string $code, ?int $lastStep = null, ?int $now = null): ?int
    {
        if (preg_match('/^\d{6}$/', $code) !== 1) {
            return null;
        }
        $current = intdiv($now ?? time(), self::PERIOD);
        for ($step = $current - self::WINDOW; $step <= $current + self::WINDOW; $step++) {
            if (($lastStep === null || $step > $lastStep) && hash_equals(self::code($secret, $step), $code)) {
                return $step;
            }
        }

        return null;
    }

    public static function uri(string $secret, string $account, string $issuer): string
    {
        return sprintf('otpauth://totp/%s:%s?%s', rawurlencode($issuer), rawurlencode($account), http_build_query([
            'secret' => $secret, 'issuer' => $issuer, 'algorithm' => 'SHA1', 'digits' => self::DIGITS, 'period' => self::PERIOD,
        ], '', '&', PHP_QUERY_RFC3986));
    }

    public static function base32Encode(string $bytes): string
    {
        $bits = '';
        foreach (str_split($bytes) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 5) as $chunk) {
            $out .= self::BASE32[bindec(str_pad($chunk, 5, '0'))];
        }

        return $out;
    }

    public static function base32Decode(string $text): string
    {
        $bits = '';
        foreach (str_split(strtoupper(rtrim($text, '='))) as $char) {
            $pos = strpos(self::BASE32, $char);
            if ($pos === false) {
                throw new \InvalidArgumentException('invalid base32');
            }
            $bits .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $out .= chr((int) bindec($byte));
            }
        }

        return $out;
    }
}
