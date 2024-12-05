<?php

declare(strict_types=1);

namespace XbNz\Gemini\AIPlatform\DataTransferObjects\Responses;

use XbNz\Gemini\AIPlatform\DataTransferObjects\ContentDTO;
use XbNz\Gemini\AIPlatform\Enums\FinishReason;
use XbNz\Gemini\AIPlatform\ValueObjects\Usage;

/**
 * @see https://ai.google.dev/api/python/google/ai/generativelanguage/GenerateContentResponse/UsageMetadata
 */
final readonly class GenerateContentResponseDTO
{
    public function __construct(
        public FinishReason $finishReason,
        public Usage $usage,
        public ?ContentDTO $content = null,
    ) {}
}
