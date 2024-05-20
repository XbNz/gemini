<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use PHPUnit\Framework\AssertionFailedError;
use XbNz\Gemini\AIPlatform\DataTransferObjects\ContentDTO;
use XbNz\Gemini\AIPlatform\DataTransferObjects\Requests\GenerateContentRequestDTO;
use XbNz\Gemini\AIPlatform\DataTransferObjects\Responses\GenerateContentResponseDTO;
use XbNz\Gemini\AIPlatform\Enums\FinishReason;
use XbNz\Gemini\AIPlatform\Enums\Role;
use XbNz\Gemini\AIPlatform\GoogleAIPlatformServiceFake;
use XbNz\Gemini\AIPlatform\ValueObjects\TextPart;
use XbNz\Gemini\AIPlatform\ValueObjects\Usage;

test('assert generate content request works', function (): void {
    // Arrange
    $generateContentRequestDTO = new GenerateContentRequestDTO(
        'gibberish-model',
        Collection::make([
            new ContentDTO(
                Role::User,
                Collection::make([
                    new TextPart('Hello, world!'),
                ])
            ),
        ])
    );

    $fake = new GoogleAIPlatformServiceFake();

    // Act
    $response = $fake->generateContent($generateContentRequestDTO);

    // Assert
    $fake->assertGenerateContentRequest(function (GenerateContentRequestDTO $generateContentRequestFromClosure) use ($generateContentRequestDTO): bool {
        return $generateContentRequestFromClosure === $generateContentRequestDTO;
    });

    try {
        $fake->assertGenerateContentRequest(function (GenerateContentRequestDTO $generateContentRequestFromClosure): bool {
            return $generateContentRequestFromClosure === 'gibberish';
        });
    } catch (AssertionFailedError $e) {
        return;
    }

    $this->fail('Expected AssertionFailedError to be thrown');
});

test('assert generate content request count works', function (): void {
    // Arrange
    $generateContentRequestDTO = new GenerateContentRequestDTO(
        'gibberish-model',
        Collection::make([
            new ContentDTO(
                Role::User,
                Collection::make([
                    new TextPart('Hello, world!'),
                ])
            ),
        ])
    );

    $fake = new GoogleAIPlatformServiceFake();

    // Act
    $fake->generateContent($generateContentRequestDTO);
    $fake->generateContent($generateContentRequestDTO);
    $fake->generateContent($generateContentRequestDTO);

    // Assert
    $fake->assertGenerateContentRequestCount(3);
});

test('always return generate content response works', function (): void {
    // Arrange
    $generateContentRequestDTO = new GenerateContentRequestDTO(
        'gibberish-model',
        Collection::make([
            new ContentDTO(
                Role::User,
                Collection::make([
                    new TextPart('Hello, world!'),
                ])
            ),
        ])
    );

    $fake = new GoogleAIPlatformServiceFake();

    // Act
    $fake->alwaysReturnGenerateContentResponse($responseDto = new GenerateContentResponseDTO(
        FinishReason::Stop,
        new Usage(
            1,
            1,
            1
        )
    ));
    $response = $fake->generateContent($generateContentRequestDTO);

    // Assert
    expect($responseDto)->toBe($response);
});
