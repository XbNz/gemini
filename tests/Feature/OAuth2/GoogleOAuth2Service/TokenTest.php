<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use ColinODell\PsrTestLogger\TestLogger;
use Saloon\Exceptions\SaloonException;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use XbNz\Gemini\OAuth2\DataTransferObjects\Requests\TokenRequestDTO;
use XbNz\Gemini\OAuth2\Exceptions\GoogleOAuthException;
use XbNz\Gemini\OAuth2\GoogleOAuth2Service;
use XbNz\Gemini\OAuth2\Saloon\Connectors\GoogleOAuthConnector;
use XbNz\Gemini\OAuth2\Saloon\Requests\TokenRequest;
use XbNz\Gemini\OAuth2\ValueObjects\GoogleServiceAccount;

test('it retrieves a token from google oauth when supplied a service account', function (): void {
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

    // Act
    $response = (new GoogleOAuth2Service())->token($tokenRequestDto);

    // Assert
    expect($response->accessToken)->toBeString();
    expect($response->expiresIn)
        ->seconds
        ->toBeGreaterThan(3590)
        ->toBeLessThan(3600);
    expect($response->tokenType)->toBe('Bearer');
})->group('online');

test('it throws & logs client, server, and connect level errors that occur using the provided logger interface', function (
    MockResponse $mockResponse
): void {
    // Arrange
    $mockClient = new MockClient([
        TokenRequest::class => $mockResponse,
    ]);

    $fakeLogger = new TestLogger();

    $tokenRequestDto = new TokenRequestDTO(
        new GoogleServiceAccount(
            getenv('GOOGLE_CLIENT_EMAIL'),
            getenv('GOOGLE_PRIVATE_KEY')
        ),
        'gibberish-scope',
        CarbonImmutable::now(),
        CarbonImmutable::now()->addHour()
    );

    // Act & Assert
    try {
        (new GoogleOAuth2Service(
            (new GoogleOAuthConnector())->withMockClient($mockClient),
            $fakeLogger
        ))->token($tokenRequestDto);
    } catch (GoogleOAuthException $exception) {
        expect($fakeLogger->hasErrorRecords())->toBeTrue();
        expect($exception->getMessage())->toBe('Fake Saloon exception');

        return;
    }

    $this->fail('An exception was not thrown');
})
    ->with([
        SaloonException::class => MockResponse::make()->throw(new SaloonException('Fake Saloon exception')),
    ]);

test('example', function () {
    expect(true)->toBeTrue();
});