<?php

declare(strict_types=1);

namespace XbNz\Gemini\AIPlatform\ValueObjects;

use XbNz\Gemini\AIPlatform\Contracts\PartContract;

final readonly class BlobPart implements PartContract
{
    /**
     * @param  non-empty-string  $mimeType
     * @param  non-empty-string  $data
     */
    public function __construct(
        public string $mimeType,
        public string $data
    ) {}

    /**
     * @return array<non-empty-string, array<non-empty-string, non-empty-string>>
     */
    public function toPartArray(): array
    {
        return [
            'inline_data' => [
                'mime_type' => $this->mimeType,
                'data' => $this->data,
            ],
        ];
    }
}
