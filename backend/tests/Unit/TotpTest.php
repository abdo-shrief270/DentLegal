<?php

namespace Tests\Unit;

use App\Modules\Identity\TwoFactor\Totp;
use PHPUnit\Framework\TestCase;

class TotpTest extends TestCase
{
    // RFC 6238 appendix B, SHA-1 seed "12345678901234567890"; 6-digit codes are the last 6 of the 8-digit vectors.
    private const SEED = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

    public function test_rfc_6238_vectors(): void
    {
        $this->assertSame('287082', Totp::code(self::SEED, intdiv(59, 30)));
        $this->assertSame('081804', Totp::code(self::SEED, intdiv(1111111109, 30)));
        $this->assertSame('005924', Totp::code(self::SEED, intdiv(1234567890, 30)));
        $this->assertSame('279037', Totp::code(self::SEED, intdiv(2000000000, 30)));
    }

    public function test_base32_round_trip_and_secret_size(): void
    {
        $this->assertSame('12345678901234567890', Totp::base32Decode(self::SEED));
        $secret = Totp::newSecret();
        $this->assertSame(32, strlen($secret));
        $this->assertSame(20, strlen(Totp::base32Decode($secret)));
    }

    public function test_drift_window_and_replay(): void
    {
        $now = 1_800_000_000;
        $step = intdiv($now, 30);
        $previous = Totp::code(self::SEED, $step - 1);

        $this->assertSame($step - 1, Totp::verify(self::SEED, $previous, null, $now));
        $this->assertNull(Totp::verify(self::SEED, $previous, $step - 1, $now), 'a used step is refused');
        $this->assertNull(Totp::verify(self::SEED, Totp::code(self::SEED, $step - 2), null, $now), 'outside the window');
        $this->assertNull(Totp::verify(self::SEED, '12345', null, $now));
    }

    public function test_otpauth_uri(): void
    {
        $this->assertSame(
            'otpauth://totp/DentLegal:lawyer%40dentlegal.test?secret='.self::SEED.'&issuer=DentLegal&algorithm=SHA1&digits=6&period=30',
            Totp::uri(self::SEED, 'lawyer@dentlegal.test', 'DentLegal'),
        );
    }
}
