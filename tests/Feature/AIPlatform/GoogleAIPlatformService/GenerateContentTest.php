<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Saloon\Http\Auth\TokenAuthenticator;
use XbNz\Gemini\AIPlatform\DataTransferObjects\ContentDTO;
use XbNz\Gemini\AIPlatform\DataTransferObjects\Requests\GenerateContentRequestDTO;
use XbNz\Gemini\AIPlatform\Enums\HarmCategory;
use XbNz\Gemini\AIPlatform\Enums\Role;
use XbNz\Gemini\AIPlatform\Enums\SafetyThreshold;
use XbNz\Gemini\AIPlatform\GoogleAIPlatformService;
use XbNz\Gemini\AIPlatform\Saloon\Connectors\GoogleAIPlatformConnector;
use XbNz\Gemini\AIPlatform\ValueObjects\SafetySettings;
use XbNz\Gemini\AIPlatform\ValueObjects\TextPart;
use XbNz\Gemini\OAuth2\DataTransferObjects\Requests\TokenRequestDTO;
use XbNz\Gemini\OAuth2\GoogleOAuth2Service;
use XbNz\Gemini\OAuth2\ValueObjects\GoogleServiceAccount;

test('it generates content', function (): void {
    // Arrange
    $generateContentRequestDto = new GenerateContentRequestDTO(
        'publishers/google/models/gemini-experimental',
        Collection::make([
            new ContentDTO(
                Role::User,
                Collection::make([
                    new TextPart('This is a test'),
                ]),
            ),
        ]),
        Collection::make([
            new SafetySettings(
                HarmCategory::HarmCategoryHateSpeech,
                SafetyThreshold::BlockOnlyHigh
            ),
        ]),
        Collection::make([
            new TextPart('This is a test'),
        ])
    );

    $tokenRequestDto = new TokenRequestDTO(
        new GoogleServiceAccount(
            $_ENV['GOOGLE_CLIENT_EMAIL'],
            $_ENV['GOOGLE_PRIVATE_KEY']
        ),
        'https://www.googleapis.com/auth/cloud-platform',
        CarbonImmutable::now(),
        CarbonImmutable::now()->addHour()
    );

    $tokenResponse = (new GoogleOAuth2Service())->token($tokenRequestDto);

    $connector = new GoogleAIPlatformConnector(
        $_ENV['GOOGLE_PROJECT_ID'],
        $_ENV['GOOGLE_REGION'],
    );

    $service = new GoogleAIPlatformService(
        $connector->authenticate(
            new TokenAuthenticator($tokenResponse->accessToken)
        )
    );

    // Act
    $generationResponse = $service->generateContent($generateContentRequestDto);
});
