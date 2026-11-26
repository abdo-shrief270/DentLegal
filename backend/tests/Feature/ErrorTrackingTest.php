<?php

namespace Tests\Feature;

use App\Support\Api\ApiException;
use App\Support\ErrorTracking\ErrorReporter;
use Illuminate\Database\QueryException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

/** Error tracking (§8.6): unexpected errors reach Sentry/GlitchTip with metadata only and personal data masked. */
class ErrorTrackingTest extends TestCase
{
    private const DSN = 'https://publickey123@errors.example.test/7';

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(['errors.example.test/*' => Http::response(['id' => 'x'])]);
        Route::post('/_test/boom', fn () => throw new RuntimeException('خطأ للمريض 0551234567 هوية 1098765432 patient@example.com'))->name('test.boom');
        Route::post('/_test/expected', fn () => throw new ApiException(409, 'conflict', 'متوقع'));
    }

    public function test_dsns_map_to_the_store_endpoint(): void
    {
        $this->assertSame(['url' => 'https://errors.example.test/api/7/store/', 'key' => 'publickey123'], ErrorReporter::parse(self::DSN));
        $this->assertSame('https://gt.example.test:8443/sub/api/3/store/', ErrorReporter::parse('https://k@gt.example.test:8443/sub/3')['url'] ?? null);
        $this->assertNull(ErrorReporter::parse(''));
        $this->assertNull(ErrorReporter::parse('not a dsn'));
    }

    public function test_an_unexpected_error_is_sent_with_masked_message_and_no_request_data(): void
    {
        Config::set('dentlegal.error_tracking.dsn', self::DSN);
        Config::set('dentlegal.error_tracking.release', 'abc123');

        $this->postJson('/_test/boom?phone=0559999999', ['facts' => 'secret case text'])->assertStatus(500);

        Http::assertSentCount(1);
        Http::assertSent(function (Request $r): bool {
            $event = $r->data();
            $json = json_encode($event, JSON_UNESCAPED_UNICODE);
            $exception = $event['exception']['values'][0];

            return $r->url() === 'https://errors.example.test/api/7/store/'
                && str_contains($r->header('X-Sentry-Auth')[0], 'sentry_key=publickey123')
                && $exception['type'] === RuntimeException::class
                && ! str_contains($json, '0551234567') && ! str_contains($json, '1098765432') && ! str_contains($json, 'patient@example.com')
                && ! str_contains($json, 'secret case text') && ! str_contains($json, '0559999999')
                && $event['transaction'] === 'test.boom' && $event['release'] === 'abc123' && $event['environment'] === 'testing'
                && ! isset($event['request']) && ! empty($exception['stacktrace']['frames'])
                && collect($exception['stacktrace']['frames'])->every(fn ($f) => ! isset($f['vars']) && ! str_starts_with($f['filename'], '/'));
        });
    }

    public function test_sql_errors_keep_only_the_sqlstate(): void
    {
        $e = new QueryException('pgsql', 'select * from users where phone = ?', ['0551234567'], new \PDOException('SQLSTATE[23505]: duplicate'));
        $event = app(ErrorReporter::class)->event($e);
        $values = json_encode($event['exception']['values'], JSON_UNESCAPED_UNICODE);
        $this->assertStringNotContainsString('0551234567', $values);
        $this->assertStringNotContainsString('select', $values);
    }

    public function test_expected_errors_and_an_empty_dsn_send_nothing(): void
    {
        Config::set('dentlegal.error_tracking.dsn', self::DSN);
        $this->postJson('/_test/expected')->assertStatus(409);
        $this->getJson('/api/v1/cases/00000000-0000-0000-0000-000000000000')->assertStatus(401);

        Config::set('dentlegal.error_tracking.dsn', '');
        $this->postJson('/_test/boom')->assertStatus(500);

        Http::assertNothingSent();
    }

    public function test_a_tracking_outage_does_not_break_the_response(): void
    {
        Config::set('dentlegal.error_tracking.dsn', self::DSN);
        Http::fake(['errors.example.test/*' => fn () => throw new ConnectionException('down')]);
        $this->postJson('/_test/boom')->assertStatus(500)->assertJsonPath('error.code', 'server_error');
    }
}
