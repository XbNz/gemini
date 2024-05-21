<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Carbon\CarbonInterval;
use PHPUnit\Framework\AssertionFailedError;
use XbNz\Gemini\OAuth2\DataTransferObjects\Requests\TokenRequestDTO;
use XbNz\Gemini\OAuth2\DataTransferObjects\Responses\TokenResponseDTO;
use XbNz\Gemini\OAuth2\GoogleOAuth2ServiceFake;
use XbNz\Gemini\OAuth2\ValueObjects\GoogleServiceAccount;

test('assert token request works', function (): void {
    // Arrange
    $tokenRequestDto = new TokenRequestDTO(
        new GoogleServiceAccount(
            getenv('GOOGLE_CLIENT_EMAIL'),
            getenv('GOOGLE_PRIVATE_KEY')
        ),
        'https://www.googleapis.com/auth/cloud-platform',
        CarbonImmutable::now(),
        CarbonImmutable::now()->addHour()
    );

    $fake = new GoogleOAuth2ServiceFake();

    // Act
    $response = $fake->token($tokenRequestDto);

    // Assert
    $fake->assertTokenRequest(function (TokenRequestDTO $tokenRequestDtoFromClosure) use ($tokenRequestDto): bool {
        return $tokenRequestDtoFromClosure === $tokenRequestDto;
    });

    try {
        $fake->assertTokenRequest(function (TokenRequestDTO $tokenRequestDtoFromClosure): bool {
            return $tokenRequestDtoFromClosure === 'gibberish';
        });
    } catch (AssertionFailedError $e) {
        return;
    }

    $this->fail('Expected AssertionFailedError to be thrown');
});

test('assert token request count works', function (): void {
    // Arrange
    $tokenRequestDto = new TokenRequestDTO(
        new GoogleServiceAccount(
            getenv('GOOGLE_CLIENT_EMAIL'),
            getenv('GOOGLE_PRIVATE_KEY')
        ),
        'https://www.googleapis.com/auth/cloud-platform',
        CarbonImmutable::now(),
        CarbonImmutable::now()->addHour()
    );

    $fake = new GoogleOAuth2ServiceFake();

    // Act
    $fake->token($tokenRequestDto);
    $fake->token($tokenRequestDto);
    $fake->token($tokenRequestDto);

    // Assert
    $fake->assertTokenRequestCount(3);
});

test('always return token works', function (): void {
    // Arrange
    $tokenRequestDto = new TokenRequestDTO(
        new GoogleServiceAccount(
            getenv('GOOGLE_CLIENT_EMAIL'),
            getenv('GOOGLE_PRIVATE_KEY')
        ),
        'https://www.googleapis.com/auth/cloud-platform',
        CarbonImmutable::now(),
        CarbonImmutable::now()->addHour()
    );

    $fake = new GoogleOAuth2ServiceFake();

    // Act
    $fake->alwaysReturnToken($responseDto = new TokenResponseDTO(
        'fake_access_token',
        CarbonInterval::seconds(3600),
        'Bearer'
    ));

    // Assert
    expect($fake->token($tokenRequestDto))->toBe($responseDto);
});
