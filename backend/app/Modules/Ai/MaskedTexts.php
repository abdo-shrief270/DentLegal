<?php

declare(strict_types=1);

namespace App\Modules\Ai;

final readonly class MaskedTexts
{
    /**
     * @param  list<string>  $texts
     * @param  array<string, int>  $entityCounts
     * @param  array<string, mixed>  $placeholderState  numbering for the next call of the same case. Holds keyed hashes,
     *                                                  never the masked values, so it is safe in queue payloads
     */
    public function __construct(public array $texts, public array $entityCounts, public array $placeholderState) {}
}
