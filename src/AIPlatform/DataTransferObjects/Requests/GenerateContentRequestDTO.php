<?php

declare(strict_types=1);

namespace XbNz\Gemini\AIPlatform\DataTransferObjects\Requests;

use Illuminate\Support\Collection;
use XbNz\Gemini\AIPlatform\Contracts\PartContract;
use XbNz\Gemini\AIPlatform\DataTransferObjects\ContentDTO;
use XbNz\Gemini\AIPlatform\ValueObjects\GenerationConfig;
use XbNz\Gemini\AIPlatform\ValueObjects\SafetySettings;

/**
 * @see https://ai.google.dev/api/python/google/ai/generativelanguage/GenerateContentRequest
 */
final readonly class GenerateContentRequestDTO
{
    /**
     * @param  non-empty-string  $model  The model to generate content for. For example "publishers/google/models/gemini-1.5"
     * @param  Collection<int, ContentDTO>  $contents
     * @param  Collection<int, SafetySettings>  $safetySettings
     * @param  Collection<int, PartContract>  $systemInstructions
     */
    public function __construct(
        public string $model,
        public Collection $contents,
        public Collection $safetySettings = new Collection(),
        public Collection $systemInstructions = new Collection(),
        public ?GenerationConfig $generationConfig = null,
    ) {
    }
}
