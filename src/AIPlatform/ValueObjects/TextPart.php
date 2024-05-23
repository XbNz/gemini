<?php

declare(strict_types=1);

namespace XbNz\Gemini\AIPlatform\ValueObjects;

use XbNz\Gemini\AIPlatform\Contracts\PartContract;

final readonly class TextPart implements PartContract
{
    public function __construct(
        public string $text
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function toPartArray(): array
    {
        return [
            'text' => $this->text,
        ];
    }
}
