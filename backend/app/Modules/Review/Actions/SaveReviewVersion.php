<?php

declare(strict_types=1);

namespace App\Modules\Review\Actions;

use App\Modules\Audit\AuditLogger;
use App\Modules\Identity\Models\User;
use App\Modules\Pipeline\Models\Draft;
use App\Modules\Pipeline\Models\DraftVersion;
use App\Modules\Review\Support\CitationChecker;
use App\Modules\Review\Support\ReviewContext;
use Illuminate\Support\Facades\DB;

/** «حفظ نسخة»: the lawyer's edit becomes a new immutable version with its citations checked. */
final class SaveReviewVersion
{
    public function __construct(private readonly AuditLogger $audit) {}

    /** @param array<string, mixed> $content validated DraftContent */
    public function __invoke(User $lawyer, Draft $draft, array $content, ?string $note): DraftVersion
    {
        return DB::transaction(function () use ($lawyer, $draft, $content, $note): DraftVersion {
            /** @var Draft $locked */
            $locked = Draft::query()->with('case')->lockForUpdate()->findOrFail($draft->id);
            ReviewGuard::assertClaimedBy($locked, $lawyer);

            $checked = CitationChecker::check($content, ReviewContext::for($locked, $locked->case));
            $version = $locked->versions()->create([
                'version_no' => (int) $locked->versions()->max('version_no') + 1,
                'author_type' => 'lawyer',
                'author_id' => $lawyer->id,
                'content_json' => $checked,
                'change_note' => $note,
            ]);
            $locked->update(['current_version_id' => $version->id]);
            $this->audit->record('review.version_saved', $lawyer, $locked, ['version_no' => $version->version_no]);

            return $version;
        });
    }
}
