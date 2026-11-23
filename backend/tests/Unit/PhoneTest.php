<?php

namespace Tests\Unit;

use App\Support\Phone;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PhoneTest extends TestCase
{
    /** @return array<string, array{string, ?string}> */
    public static function numbers(): array
    {
        return [
            'local 9 digits' => ['551234417', '+966551234417'],
            'with leading zero' => ['0551234417', '+966551234417'],
            'e164' => ['+966551234417', '+966551234417'],
            '00 prefix and spaces' => ['00966 55 123 4417', '+966551234417'],
            'arabic-indic digits' => ['٠٥٥١٢٣٤٤١٧', '+966551234417'],
            'landline' => ['0126543210', null],
            'too short' => ['55123441', null],
            'foreign' => ['+201001234567', null],
        ];
    }

    #[DataProvider('numbers')]
    public function test_normalizes_saudi_mobiles(string $input, ?string $expected): void
    {
        $this->assertSame($expected, Phone::normalizeSaudiMobile($input));
    }

    public function test_masks_for_display(): void
    {
        $this->assertSame('+966 55 *** 4417', Phone::mask('+966551234417'));
    }
}
