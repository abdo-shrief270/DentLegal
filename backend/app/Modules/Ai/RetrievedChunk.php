<?php

declare(strict_types=1);

namespace App\Modules\Ai;

final readonly class RetrievedChunk
{
    public function __construct(
        public int $chunkId,
        public string $label,
        public string $text,
        public float $rerankScore,
        public ?int $denseRank,
        public ?int $sparseRank,
    ) {}

    /** @param array<string, mixed> $row */
    public static function fromArray(array $row): self
    {
        return new self(
            chunkId: (int) $row['chunk_id'],
            label: (string) $row['label'],
            text: (string) $row['text'],
            rerankScore: (float) $row['rerank_score'],
            denseRank: isset($row['dense_rank']) ? (int) $row['dense_rank'] : null,
            sparseRank: isset($row['sparse_rank']) ? (int) $row['sparse_rank'] : null,
        );
    }
}
