<?php

declare(strict_types=1);

namespace XbNz\Gemini\AIPlatform\Saloon\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;
use Saloon\Traits\Plugins\AcceptsJson;
use XbNz\Gemini\AIPlatform\Contracts\PartContract;
use XbNz\Gemini\AIPlatform\DataTransferObjects\ContentDTO;
use XbNz\Gemini\AIPlatform\DataTransferObjects\Requests\GenerateContentRequestDTO;
use XbNz\Gemini\AIPlatform\ValueObjects\SafetySettings;

class GenerateContentRequest extends Request implements HasBody
{
    use AcceptsJson;
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly GenerateContentRequestDTO $generateContentRequestDTO,
    ) {}

    public function resolveEndpoint(): string
    {
        return "/{$this->generateContentRequestDTO->model}:generateContent";
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return [
            'model' => $this->generateContentRequestDTO->model,
            'contents' => $this->generateContentRequestDTO->contents->map(function (ContentDTO $contentDTO) {
                return [
                    'role' => $contentDTO->role->value,
                    'parts' => $contentDTO->parts->map(fn (PartContract $part) => $part->toPartArray()),
                ];
            })->toArray(),
            'safety_settings' => $this->generateContentRequestDTO->safetySettings->map(fn (SafetySettings $safetySettings) => [
                'category' => $safetySettings->harmCategory->value,
                'threshold' => $safetySettings->safetyThreshold->value,
            ])->toArray(),
            'system_instruction' => [
                'parts' => $this->generateContentRequestDTO->systemInstructions->map(fn (PartContract $part) => $part->toPartArray())->toArray(),
            ],
        ];
    }
}
