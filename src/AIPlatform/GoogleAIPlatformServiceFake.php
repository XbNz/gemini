<?php

declare(strict_types=1);

namespace XbNz\Gemini\AIPlatform;

use Closure;
use PHPUnit\Framework\Assert;
use XbNz\Gemini\AIPlatform\Contracts\GoogleAIPlatformInterface;
use XbNz\Gemini\AIPlatform\DataTransferObjects\Requests\GenerateContentRequestDTO;
use XbNz\Gemini\AIPlatform\DataTransferObjects\Responses\GenerateContentResponseDTO;
use XbNz\Gemini\AIPlatform\Enums\FinishReason;
use XbNz\Gemini\AIPlatform\ValueObjects\Usage;

class GoogleAIPlatformServiceFake implements GoogleAIPlatformInterface
{
    public ?GenerateContentResponseDTO $returnGenerateContentResponse = null;

    /**
     * @var array<int, GenerateContentRequestDTO>
     */
    private array $generateContentRequests = [];

    public function generateContent(GenerateContentRequestDTO $requestDto): GenerateContentResponseDTO
    {
        $this->generateContentRequests[] = $requestDto;

        $this->returnGenerateContentResponse ??= new GenerateContentResponseDTO(
            FinishReason::Stop,
            new Usage(
                1,
                1,
                1
            )
        );

        return $this->returnGenerateContentResponse;
    }

    public function alwaysReturnGenerateContentResponse(GenerateContentResponseDTO $generateContentResponseDTO): void
    {
        $this->returnGenerateContentResponse = $generateContentResponseDTO;
    }

    /**
     * @param  Closure(GenerateContentRequestDTO): bool  $closure
     */
    private function generateContentRequest(Closure $closure): bool
    {
        foreach ($this->generateContentRequests as $generateContentRequest) {
            if ($closure($generateContentRequest) === true) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  Closure(GenerateContentRequestDTO): bool  $closure
     */
    public function assertGenerateContentRequest(Closure $closure): void
    {
        Assert::assertTrue($this->generateContentRequest($closure));
    }

    public function assertGenerateContentRequestCount(int $expectedCount): void
    {
        Assert::assertCount($expectedCount, $this->generateContentRequests);
    }
}
