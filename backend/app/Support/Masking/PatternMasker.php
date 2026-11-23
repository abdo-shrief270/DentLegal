<?php

declare(strict_types=1);

namespace App\Support\Masking;

/**
 * Layer 2 of the PII masking pipeline (PROJECT1_PLAN.md §5.10), run in PHP on free text the dentist types,
 * so no national ID, phone, email or IBAN is ever stored. Names and addresses are masked later by ai-svc (NER).
 * Placeholders are numbered per call and the same value always gets the same placeholder.
 */
final class PatternMasker
{
    /** Order matters: longer and more specific patterns first. */
    private const PATTERNS = [
        'IBAN' => '/\bSA\d{2}[ ]?(?:\d{4}[ ]?){4}\d{4}\b/i',
        'EMAIL' => '/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i',
        // Also written in groups: «+966 50 111 2233», «055-123-4567» (bidi marks allowed between groups).
        'PHONE' => '/(?<![\d+])(?:(?:\+|00)?966[\s\-\x{200E}\x{200F}]*|0)?5\d(?:[\s\-\x{200E}\x{200F}]?\d){7}(?!\d)/u',
        'ID' => '/(?<!\d)[12]\d{9}(?!\d)/',
        'LANDLINE' => '/(?<!\d)(?:\+?966|0)1[1-7]\d{7}(?!\d)/',
    ];

    public function mask(string $text): MaskResult
    {
        $normalized = self::asciiDigits($text);
        $counts = [];
        $seen = [];

        foreach (self::PATTERNS as $label => $pattern) {
            $normalized = preg_replace_callback($pattern, function (array $m) use ($label, &$counts, &$seen): string {
                $key = $label.'|'.preg_replace('/\D/', '', $m[0]).'|'.strtolower($m[0]);
                if (! isset($seen[$key])) {
                    $counts[$label] = ($counts[$label] ?? 0) + 1;
                    $seen[$key] = sprintf('[%s_%d]', $label === 'LANDLINE' ? 'PHONE' : $label, $counts[$label]);
                }

                return $seen[$key];
            }, $normalized) ?? $normalized;
        }

        return new MaskResult($normalized, $counts);
    }

    /** Arabic-Indic and Persian digits → ASCII, so patterns also catch «٠٥٥١٢٣٤٥٦٧». */
    private static function asciiDigits(string $text): string
    {
        return strtr($text, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ]);
    }
}
