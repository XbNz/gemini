<?php

declare(strict_types=1);

namespace XbNz\Gemini\AIPlatform\ValueObjects;

final readonly class GenerationConfig
{
    public function __construct(
        public float $temperature,
        public float $topP,
        public float $topK,
    ) {}
}
