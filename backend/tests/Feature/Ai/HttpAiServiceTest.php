<?php

namespace Tests\Feature\Ai;

use App\Modules\Ai\AiService;
use App\Modules\Ai\AiServiceException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HttpAiServiceTest extends TestCase
{
    public function test_retrieve_sends_the_service_token_and_maps_chunks(): void
    {
        Http::fake(['ai-svc.test/v1/retrieve' => Http::response(['chunks' => [
            ['chunk_id' => 412, 'label' => 'المادة 19', 'text' => 'نص', 'rerank_score' => 0.91, 'dense_rank' => 1, 'sparse_rank' => 3],
        ]])]);

        $chunks = app(AiService::class)->retrieve('الموافقة المستنيرة', 8, ['source_codes' => ['HPPL']]);

        $this->assertCount(1, $chunks);
        $this->assertSame(412, $chunks[0]->chunkId);
        $this->assertSame(0.91, $chunks[0]->rerankScore);
        Http::assertSent(fn (Request $r) => $r->hasHeader('X-Service-Token', 'test-token') && $r['top_k'] === 8);
    }

    public function test_server_errors_are_retried_then_reported_as_retryable(): void
    {
        Http::fakeSequence('ai-svc.test/*')->push([], 503)->push([], 503)->push([], 503);

        try {
            app(AiService::class)->retrieve('سؤال');
            $this->fail('Expected AiServiceException');
        } catch (AiServiceException $e) {
            $this->assertTrue($e->retryable);
        }
        Http::assertSentCount(3);
    }

    public function test_server_errors_recover_when_a_retry_succeeds(): void
    {
        Http::fakeSequence('ai-svc.test/*')->push([], 502)->push(['chunks' => []], 200);

        $this->assertSame([], app(AiService::class)->retrieve('سؤال'));
    }

    public function test_client_errors_are_not_retried(): void
    {
        Http::fake(['ai-svc.test/*' => Http::response(['error' => ['code' => 'invalid_request']], 422)]);

        try {
            app(AiService::class)->retrieve('سؤال');
            $this->fail('Expected AiServiceException');
        } catch (AiServiceException $e) {
            $this->assertFalse($e->retryable);
            $this->assertSame('invalid_request', $e->errorCode);
        }
        Http::assertSentCount(1);
    }

    public function test_masking_fails_closed_on_incomplete_results(): void
    {
        Http::fake(['ai-svc.test/v1/mask' => Http::response(['masked' => ['نص واحد'], 'entity_counts' => []])]);

        $this->expectException(AiServiceException::class);
        app(AiService::class)->mask(['نص أول', 'نص ثان']);
    }
}
