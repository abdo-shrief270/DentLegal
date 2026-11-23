<?php

declare(strict_types=1);

namespace App\Modules\Pipeline\Jobs;

use App\Modules\Ai\AiServiceException;
use App\Modules\Pipeline\Actions\MarkPipelineFailed;
use App\Modules\Pipeline\Models\PipelineJob;
use App\Modules\Pipeline\PipelineFailure;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Base for the pipeline steps (PROJECT1_PLAN.md §4.4, §5.12). Retryable ai-svc failures (overload, network) are
 * retried with backoff; anything else fails the run at once. The chain's catch marks the run and the case failed.
 */
abstract class PipelineStepJob implements ShouldQueue
{
    use Batchable, Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 120];

    public int $timeout = 900;

    public function __construct(public string $pipelineJobId)
    {
        $this->onQueue('ai')->onConnection(config('dentlegal.queues.long_connection'));
    }

    public function handle(): void
    {
        $job = PipelineJob::query()->with('case.dentist.dentistProfile')->findOrFail($this->pipelineJobId);
        if ($job->status === 'failed') {
            return;
        }
        try {
            $this->run($job);
        } catch (AiServiceException $e) {
            if ($e->retryable && $this->attempts() < $this->tries) {
                throw $e;
            }
            $this->giveUp(new PipelineFailure($e->errorCode, $e->getMessage()));
        } catch (PipelineFailure $e) {
            $this->giveUp($e);
        }
    }

    abstract protected function run(PipelineJob $job): void;

    /** Definitive failure: mark the run now (also outside a worker), then stop the chain. Marking is idempotent. */
    private function giveUp(PipelineFailure $e): void
    {
        app(MarkPipelineFailed::class)($this->pipelineJobId, $e);
        $this->fail($e);
    }

    public function failed(?Throwable $e): void
    {
        app(MarkPipelineFailed::class)($this->pipelineJobId, $e);
    }
}
