<?php

declare(strict_types=1);

namespace App\Modules\Pipeline\Jobs;

use App\Modules\Ai\AiService;
use App\Modules\Documents\DocumentVault;
use App\Modules\Documents\Models\Document;
use App\Modules\Pipeline\Enums\PipelineStep;
use App\Modules\Pipeline\Models\PipelineJob;
use Illuminate\Support\Facades\DB;

/**
 * Steps «ocr» and «masking»: each document not yet extracted goes to ai-svc, which returns masked page text only;
 * then the typed facts get the full masking pass (NER) with the same placeholder numbering as the documents.
 * Documents already extracted are skipped, so a retried run resumes where it failed.
 */
final class ExtractCaseDocuments extends PipelineStepJob
{
    protected function run(PipelineJob $job): void
    {
        $ai = app(AiService::class);
        $vault = app(DocumentVault::class);
        $case = $job->case;
        $dentist = ['name' => $case->dentist->name, 'license_no' => $case->dentist->dentistProfile?->scfhs_license_no];
        $allowlist = array_values(array_filter([$case->authority, $case->dentist->dentistProfile?->facility_name]));

        $started = hrtime(true);
        $job->stepStarted(PipelineStep::Ocr);
        $documents = Document::query()->where('case_id', $case->id)->whereIn('ocr_status', ['pending', 'failed'])->orderBy('created_at')->get();
        foreach ($documents as $document) {
            $result = $ai->extractDocument($vault->read($document->id, $document->storage_key, $document->wrapped_key), $document->original_name, [
                'allowlist' => $allowlist, 'dentist' => $dentist, 'placeholder_state' => $job->placeholder_state,
            ]);
            DB::transaction(function () use ($document, $result, $job): void {
                $document->pages()->delete();
                $methods = [];
                foreach ($result['pages'] as $page) {
                    $document->pages()->create([
                        'page_no' => $page['page_no'], 'text_masked' => $page['text'], 'confidence' => $page['confidence'], 'method' => $page['method'],
                        'masked_entity_counts' => [],
                    ]);
                    $methods[] = $page['method'];
                }
                $document->update([
                    'page_count' => $result['page_count'],
                    'ocr_status' => 'done',
                    'ocr_method' => array_key_first(array_count_values($methods) ?: ['ocr' => 1]),
                    'ocr_confidence' => $result['ocr_confidence'],
                    'low_quality' => $result['low_quality'],
                ]);
                $job->update(['placeholder_state' => $result['placeholder_state']]);
            });
        }
        $job->stepDone(PipelineStep::Ocr, $this->ms($started));

        $started = hrtime(true);
        $job->stepStarted(PipelineStep::Masking);
        $masked = $ai->mask([$case->facts_masked], $allowlist, $job->placeholder_state, $dentist);
        $counts = $case->facts_entity_counts;
        foreach ($masked->entityCounts as $kind => $n) {
            $counts[$kind] = ($counts[$kind] ?? 0) + $n;
        }
        $case->forceFill(['facts_masked' => $masked->texts[0], 'facts_entity_counts' => $counts])->save();
        $job->update(['placeholder_state' => $masked->placeholderState]);
        $job->stepDone(PipelineStep::Masking, $this->ms($started));
    }

    private function ms(int|float $started): int
    {
        return (int) ((hrtime(true) - $started) / 1_000_000);
    }
}
