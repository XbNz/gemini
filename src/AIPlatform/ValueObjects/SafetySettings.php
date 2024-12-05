<?php

declare(strict_types=1);

namespace XbNz\Gemini\AIPlatform\ValueObjects;

use XbNz\Gemini\AIPlatform\Enums\HarmCategory;
use XbNz\Gemini\AIPlatform\Enums\SafetyThreshold;

final readonly class SafetySettings
{
    public function __construct(
        public HarmCategory $harmCategory,
        public SafetyThreshold $safetyThreshold,
    ) {}
}
