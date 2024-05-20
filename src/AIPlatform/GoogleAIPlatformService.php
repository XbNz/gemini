<?php

declare(strict_types=1);

namespace XbNz\Gemini\AIPlatform;

use Illuminate\Support\Collection;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Saloon\Exceptions\SaloonException;
use XbNz\Gemini\AIPlatform\Contracts\GoogleAIPlatformInterface;
use XbNz\Gemini\AIPlatform\DataTransferObjects\ContentDTO;
use XbNz\Gemini\AIPlatform\DataTransferObjects\Requests\GenerateContentRequestDTO;
use XbNz\Gemini\AIPlatform\DataTransferObjects\Responses\GenerateContentResponseDTO;
use XbNz\Gemini\AIPlatform\Enums\FinishReason;
use XbNz\Gemini\AIPlatform\Enums\Role;
use XbNz\Gemini\AIPlatform\Exceptions\GoogleAIPlatformException;
use XbNz\Gemini\AIPlatform\Saloon\Connectors\GoogleAIPlatformConnector;
use XbNz\Gemini\AIPlatform\Saloon\Requests\GenerateContentRequest;
use XbNz\Gemini\AIPlatform\ValueObjects\TextPart;
use XbNz\Gemini\AIPlatform\ValueObjects\Usage;

final class GoogleAIPlatformService implements GoogleAIPlatformInterface
{
    public function __construct(
        private GoogleAIPlatformConnector $connector,
        private readonly LoggerInterface $logger = new NullLogger()
    ) {
    }

    public function generateContent(GenerateContentRequestDTO $requestDto): GenerateContentResponseDTO
    {
        try {
            $response = $this->connector->send(
                new GenerateContentRequest($requestDto)
            )->throw();
        } catch (SaloonException $exception) {
            $this->logger->error(
                'An error occurred while attempting to generate content from Google AI Platform',
                ['exception' => $exception]
            );

            throw GoogleAIPlatformException::fromSaloon($exception);
        }

        return new GenerateContentResponseDTO(
            FinishReason::from($response->json('candidates.0.finishReason')),
            new Usage(
                $response->json('usageMetadata.promptTokenCount'),
                $response->json('usageMetadata.totalTokenCount'),
                $response->json('usageMetadata.candidatesTokenCount') ?? null,
            ),
            $response->json('candidates.0.content', null) !== null
                ? new ContentDTO(
                    Role::from($response->json('candidates.0.content.role')),
                    new Collection([
                        new TextPart($response->json('candidates.0.content.parts.0.text')),
                    ])
                )
                : null
        );

    }
}
