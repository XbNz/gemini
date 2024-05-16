<?php

declare(strict_types=1);

namespace XbNz\Gemini\AIPlatform\ValueObjects;

use XbNz\Gemini\AIPlatform\Contracts\PartContract;

final readonly class TextPart implements PartContract
{
    /**
     * @param  non-empty-string  $text
     */
    public function __construct(
        public string $text
    ) {
    }

    public function toPartArray(): array
    {
        // TODO: Implement toPartArray() method.
    }
}
