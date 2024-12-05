<?php

declare(strict_types=1);

namespace XbNz\Gemini\AIPlatform\ValueObjects;

/**
 * @see https://ai.google.dev/api/python/google/ai/generativelanguage/GenerateContentResponse/UsageMetadata
 */
final readonly class Usage
{
    public function __construct(
        public int $promptTokenCount,
        public int $totalTokenCount,
        public ?int $candidatesTokenCount,
    ) {}
}
