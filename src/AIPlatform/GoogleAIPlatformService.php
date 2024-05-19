<?php

declare(strict_types=1);

namespace XbNz\Gemini\AIPlatform;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Saloon\Exceptions\SaloonException;
use XbNz\Gemini\AIPlatform\Contracts\GoogleAIPlatformInterface;
use XbNz\Gemini\AIPlatform\DataTransferObjects\Requests\GenerateContentRequestDTO;
use XbNz\Gemini\AIPlatform\Saloon\Connectors\GoogleAIPlatformConnector;
use XbNz\Gemini\AIPlatform\Saloon\Requests\GenerateContentRequest;

final class GoogleAIPlatformService implements GoogleAIPlatformInterface
{
    public function __construct(
        private GoogleAIPlatformConnector $connector,
        private readonly LoggerInterface $logger = new NullLogger()
    ) {
    }

    public function generateContent(GenerateContentRequestDTO $requestDto): void
    {
        try {
            $response = $this->connector->send(
                new GenerateContentRequest($requestDto)
            )->throw();

            dd($response->json());
        } catch (SaloonException $exception) {
            $this->logger->error(
                'An error occurred while attempting to generate content from Google AI Platform',
                ['exception' => $exception]
            );

            //            throw GoogleOAuthException::fromSaloon($exception);

            throw $exception;
        }
    }
}
