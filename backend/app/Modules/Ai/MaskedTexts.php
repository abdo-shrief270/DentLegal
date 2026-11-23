<?php

declare(strict_types=1);

namespace App\Modules\Ai;

final readonly class MaskedTexts
{
    /**
     * @param  list<string>  $texts
     * @param  array<string, int>  $entityCounts
     * @param  array<string, mixed>  $placeholderState  kept in memory for the next call of the same case; never stored
     */
    public function __construct(public array $texts, public array $entityCounts, public array $placeholderState) {}
}
