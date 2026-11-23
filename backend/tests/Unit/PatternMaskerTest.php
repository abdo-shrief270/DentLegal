<?php

namespace Tests\Unit;

use App\Support\Masking\PatternMasker;
use PHPUnit\Framework\TestCase;

/** Synthetic identifiers only. Target from §5.10: 100% recall on IDs, phones and emails. */
class PatternMaskerTest extends TestCase
{
    public function test_masks_ids_phones_emails_and_iban(): void
    {
        $text = 'المريض صاحب الهوية 1023456789 وجواله 0551234567 والبريد patient@example.com والآيبان SA0380000000608010167519.';
        $result = (new PatternMasker)->mask($text);

        $this->assertStringNotContainsString('1023456789', $result->text);
        $this->assertStringNotContainsString('0551234567', $result->text);
        $this->assertStringNotContainsString('patient@example.com', $result->text);
        $this->assertStringNotContainsString('SA0380000000608010167519', $result->text);
        $this->assertSame(['IBAN' => 1, 'EMAIL' => 1, 'PHONE' => 1, 'ID' => 1], $result->entityCounts);
        $this->assertStringContainsString('[ID_1]', $result->text);
    }

    public function test_masks_arabic_indic_digits_and_international_format(): void
    {
        $result = (new PatternMasker)->mask('اتصل من ٠٥٥١٢٣٤٥٦٧ ثم من +966551234567 وهوية مقيم ٢٣٤٥٦٧٨٩٠١');

        $this->assertDoesNotMatchRegularExpression('/\d{9,}/', $result->text);
        $this->assertSame(2, $result->entityCounts['PHONE']);
        $this->assertSame(1, $result->entityCounts['ID']);
    }

    public function test_same_value_gets_the_same_placeholder(): void
    {
        $result = (new PatternMasker)->mask('الرقم 0551234567 ثم الرقم 0551234567 ثم 0559876543');

        $this->assertSame('الرقم [PHONE_1] ثم الرقم [PHONE_1] ثم [PHONE_2]', $result->text);
    }

    public function test_leaves_dates_codes_and_complaint_numbers(): void
    {
        $text = 'الشكوى رقم 02871/1448 بتاريخ 2026-10-19، رمز المريض P-7731، الضرس 36.';
        $result = (new PatternMasker)->mask($text);

        $this->assertSame($text, $result->text);
        $this->assertFalse($result->maskedAnything());
    }

    public function test_does_not_mask_parts_of_longer_numbers(): void
    {
        $text = 'المرجع 10234567891234 للملف';
        $this->assertSame($text, (new PatternMasker)->mask($text)->text);
    }
}
