<?php

declare(strict_types=1);

namespace App\Support;

/** Saudi mobile numbers in E.164 form (+9665XXXXXXXX), the only phones the app accepts in P1. */
final class Phone
{
    /** Returns +9665XXXXXXXX, or null when the input is not a Saudi mobile number. */
    public static function normalizeSaudiMobile(string $input): ?string
    {
        $digits = strtr(preg_replace('/[\s\-().]/u', '', $input) ?? '', [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
        $digits = preg_replace('/^(?:\+|00)?966/', '', $digits) ?? '';
        $digits = preg_replace('/^0(?=5)/', '', $digits) ?? '';

        return preg_match('/^5\d{8}$/', $digits) === 1 ? '+966'.$digits : null;
    }

    /** +966 55 *** 4417, for messages and logs. */
    public static function mask(string $e164): string
    {
        return substr($e164, 0, 4).' '.substr($e164, 4, 2).' *** '.substr($e164, -4);
    }
}
