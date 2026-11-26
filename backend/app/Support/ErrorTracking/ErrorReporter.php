<?php

declare(strict_types=1);

namespace App\Support\ErrorTracking;

use App\Support\Masking\PatternMasker;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sends unexpected exceptions to Sentry or GlitchTip (PROJECT1_PLAN.md §8.6, D14) through the store endpoint both
 * accept. Written in-house rather than with the SDK so that exactly this leaves the server: exception type, a masked
 * and shortened message, the stack (file, line, function; no arguments or variables), the route name, the HTTP
 * method, the user id and the environment. Never request bodies, headers, query strings, IPs or SQL bindings.
 *
 * Off when ERROR_TRACKING_DSN is empty. Never throws: a tracking outage must not turn into a second error.
 */
final class ErrorReporter
{
    private const MESSAGE_MAX = 300;

    private const FRAMES_MAX = 40;

    public function __construct(private readonly PatternMasker $masker) {}

    public function enabled(): bool
    {
        return self::parse((string) config('dentlegal.error_tracking.dsn')) !== null;
    }

    public function capture(Throwable $e): void
    {
        $target = self::parse((string) config('dentlegal.error_tracking.dsn'));
        if ($target === null) {
            return;
        }
        try {
            Http::timeout(3)->connectTimeout(2)
                ->withHeaders(['X-Sentry-Auth' => "Sentry sentry_version=7, sentry_client=dentlegal-php/1.0, sentry_key={$target['key']}"])
                ->post($target['url'], $this->event($e));
        } catch (Throwable $failure) {
            Log::warning('error_tracking.unavailable', ['reason' => $failure::class]);
        }
    }

    /** @return array<string, mixed> */
    public function event(Throwable $e): array
    {
        // An HTTP request has a matched route; console commands and queue jobs have none.
        $route = app()->bound('request') ? request()->route() : null;
        $request = is_object($route) ? request() : null;
        $exceptions = [];
        for ($x = $e, $depth = 0; $x !== null && $depth < 3; $x = $x->getPrevious(), $depth++) {
            $exceptions[] = ['type' => $x::class, 'value' => $this->message($x), 'stacktrace' => ['frames' => $this->frames($x)]];
        }

        return array_filter([
            'event_id' => bin2hex(random_bytes(16)),
            'timestamp' => now()->toIso8601ZuluString(),
            'platform' => 'php',
            'level' => 'error',
            'logger' => 'laravel',
            'environment' => (string) config('app.env'),
            'release' => config('dentlegal.error_tracking.release') ?: null,
            'server_name' => 'api',
            'transaction' => is_object($route) ? ($route->getName() ?? $route->uri()) : 'console',
            'tags' => array_filter(['method' => $request?->method(), 'context' => $request === null ? 'console' : 'http']),
            'user' => ($id = $request?->user()?->getAuthIdentifier()) !== null ? ['id' => (string) $id] : null,
            // Oldest cause first, as the protocol expects.
            'exception' => ['values' => array_reverse($exceptions)],
        ], static fn (mixed $v): bool => ! in_array($v, [null, '', []], true));
    }

    private function message(Throwable $e): string
    {
        // SQL errors carry the statement and its bindings (possibly personal data): keep only the SQLSTATE.
        $text = $e instanceof QueryException ? 'SQLSTATE '.($e->errorInfo[0] ?? $e->getCode()) : $e->getMessage();
        $masked = $this->masker->mask($text)->text;

        return mb_strlen($masked) > self::MESSAGE_MAX ? mb_substr($masked, 0, self::MESSAGE_MAX).'…' : $masked;
    }

    /** @return list<array<string, mixed>> */
    private function frames(Throwable $e): array
    {
        $base = base_path().DIRECTORY_SEPARATOR;
        $frames = [];
        foreach (array_slice($e->getTrace(), 0, self::FRAMES_MAX) as $t) {
            $file = str_replace($base, '', (string) ($t['file'] ?? '[internal]'));
            $frames[] = [
                'filename' => $file,
                'lineno' => $t['line'] ?? 0,
                'function' => isset($t['class']) ? $t['class'].($t['type'] ?? '::').$t['function'] : $t['function'],
                'in_app' => ! str_starts_with($file, 'vendor/'),
            ];
        }
        $frames[] = ['filename' => str_replace($base, '', $e->getFile()), 'lineno' => $e->getLine(), 'in_app' => ! str_contains($e->getFile(), '/vendor/')];

        return array_reverse($frames); // the protocol lists frames oldest first
    }

    /** @return array{url: string, key: string}|null */
    public static function parse(string $dsn): ?array
    {
        $p = parse_url(trim($dsn));
        if ($dsn === '' || $p === false || ! isset($p['scheme'], $p['host'], $p['user'], $p['path']) || ! in_array($p['scheme'], ['https', 'http'], true)) {
            return null;
        }
        $segments = explode('/', trim($p['path'], '/'));
        $project = (string) array_pop($segments);
        if ($project === '') {
            return null;
        }
        $prefix = $segments === [] ? '' : '/'.implode('/', $segments);
        $port = isset($p['port']) ? ':'.$p['port'] : '';

        return ['url' => "{$p['scheme']}://{$p['host']}{$port}{$prefix}/api/{$project}/store/", 'key' => $p['user']];
    }
}
