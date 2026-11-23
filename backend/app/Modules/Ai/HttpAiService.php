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
     * @param  array{name: string|null, license_no: string|null}|null  $dentist
     */
    public function mask(array $texts, array $allowlist = [], ?array $placeholderState = null, ?array $dentist = null): MaskedTexts
    {
        $body = $this->post('/v1/mask', ['texts' => $texts, 'allowlist' => $allowlist, 'placeholder_state' => $placeholderState, 'dentist' => $dentist]);
        if (! isset($body['masked']) || count($body['masked']) !== count($texts)) {
            throw new AiServiceException('ai-svc returned an incomplete masking result', 'masking_failed', false);
        }

        return new MaskedTexts($body['masked'], $body['entity_counts'] ?? [], $body['placeholder_state'] ?? []);
    }

    public function consult(string $maskedQuestion, array $history = []): array
    {
        return $this->post('/v1/consult', ['question' => $maskedQuestion, 'history' => $history], (int) config('dentlegal.ai_service.consult_timeout'));
    }

    public function analyzeCase(array $case, array $documents): array
    {
        return $this->post('/v1/case/analyze', ['case' => $case, 'documents' => $documents], (int) config('dentlegal.ai_service.case_timeout'));
    }

    public function draftCase(array $case, array $documents, array $analysis, array $sourceChunkIds): array
    {
        return $this->post('/v1/case/draft', [
            'case' => $case, 'documents' => $documents, 'analysis' => $analysis, 'source_chunk_ids' => $sourceChunkIds,
        ], (int) config('dentlegal.ai_service.case_timeout'));
    }

    public function extractDocument(string $contents, string $filename, array $options = []): array
    {
        return $this->send(
            fn (PendingRequest $r): Response => $r->attach('file', $contents, $filename)->post('/v1/extract', ['options' => json_encode((object) $options)]),
            (int) config('dentlegal.ai_service.extract_timeout'),
        );
    }

    public function ingestSource(string $contents, array $options): array
    {
        return $this->send(
            fn (PendingRequest $r): Response => $r->attach('file', $contents, 'source.pdf')->post('/v1/kb/ingest', ['options' => json_encode($options)]),
            (int) config('dentlegal.ai_service.ingest_timeout'),
        );
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
    private function post(string $path, array $payload, ?int $timeout = null): array
    {
        return $this->send(fn (PendingRequest $r): Response => $r->post($path, $payload), $timeout);
    }

    /**
     * @param  callable(PendingRequest): Response  $call
     * @return array<string, mixed>
     */
    private function send(callable $call, ?int $timeout = null): array
    {
        $cfg = config('dentlegal.ai_service');
        $request = Http::baseUrl((string) $cfg['url'])
            ->withHeaders(['X-Service-Token' => (string) $cfg['token']])
            ->acceptJson()
            ->connectTimeout($cfg['connect_timeout'])
            ->timeout($timeout ?? $cfg['timeout'])
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
