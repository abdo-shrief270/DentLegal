<?php

declare(strict_types=1);

namespace App\Support\Masking;

final readonly class MaskResult
{
    /** @param array<string, int> $entityCounts e.g. ['ID' => 1, 'PHONE' => 2] */
    public function __construct(public string $text, public array $entityCounts) {}

    public function maskedAnything(): bool
    {
        return $this->entityCounts !== [];
    }
}
