<?php

declare(strict_types=1);

namespace App\Modules\Ai;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * HTTP adapter for ai-svc on the private Docker network. Authenticated with a shared service token.
 * Retries connection failures and 5xx with backoff; 4xx are reported immediately as non-retryable.
 * Request and response bodies are never logged (they may hold case text).
 */
final class HttpAiService implements AiService
{
    public function retrieve(string $query, int $topK = 8, array $filters = []): array
    {
        $body = $this->post('/v1/retrieve', ['query' => $query, 'top_k' => $topK, 'filters' => (object) $filters]);

        return array_map(RetrievedChunk::fromArray(...), $body['chunks'] ?? []);
    }

    /**
     * @param  list<string>  $texts
     * @param  list<string>  $allowlist
     * @param  array<string, mixed>|null  $placeholderState
     */
    public function mask(array $texts, array $allowlist = [], ?array $placeholderState = null): MaskedTexts
    {
        $body = $this->post('/v1/mask', ['texts' => $texts, 'allowlist' => $allowlist, 'placeholder_state' => $placeholderState]);
        if (! isset($body['masked']) || count($body['masked']) !== count($texts)) {
            throw new AiServiceException('ai-svc returned an incomplete masking result', 'masking_failed', false);
        }

        return new MaskedTexts($body['masked'], $body['entity_counts'] ?? [], $body['placeholder_state'] ?? []);
    }

    public function health(): array
    {
        /** @var array{status: string, models: array<string, bool>, db: bool} $body */
        $body = $this->send(fn (PendingRequest $r): Response => $r->get('/v1/health'));

        return $body;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function post(string $path, array $payload): array
    {
        return $this->send(fn (PendingRequest $r): Response => $r->post($path, $payload));
    }

    /**
     * @param  callable(PendingRequest): Response  $call
     * @return array<string, mixed>
     */
    private function send(callable $call): array
    {
        $cfg = config('dentlegal.ai_service');
        $request = Http::baseUrl((string) $cfg['url'])
            ->withHeaders(['X-Service-Token' => (string) $cfg['token']])
            ->acceptJson()
            ->connectTimeout($cfg['connect_timeout'])
            ->timeout($cfg['timeout'])
            ->retry(
                $cfg['retries'] + 1,
                fn (int $attempt): int => 200 * 2 ** ($attempt - 1),
                fn (\Throwable $e): bool => $e instanceof ConnectionException
                    || ($e instanceof RequestException && $e->response->serverError()),
                throw: false,
            );

        try {
            $response = $call($request);
        } catch (ConnectionException $e) {
            throw new AiServiceException('ai-svc unreachable', previous: $e);
        }

        if ($response->failed()) {
            $code = (string) ($response->json('error.code') ?? 'ai_unavailable');
            throw new AiServiceException("ai-svc answered {$response->status()} ({$code})", $code, $response->serverError());
        }

        return (array) $response->json();
    }
}
