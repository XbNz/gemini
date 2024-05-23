<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use ColinODell\PsrTestLogger\TestLogger;
use GuzzleRetry\GuzzleRetryMiddleware;
use Illuminate\Support\Collection;
use Psl\Type\Exception\CoercionException;
use Saloon\Exceptions\SaloonException;
use Saloon\Http\Auth\TokenAuthenticator;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Request;
use Saloon\Http\Response;
use XbNz\Gemini\AIPlatform\DataTransferObjects\ContentDTO;
use XbNz\Gemini\AIPlatform\DataTransferObjects\Requests\GenerateContentRequestDTO;
use XbNz\Gemini\AIPlatform\DataTransferObjects\Responses\GenerateContentResponseDTO;
use XbNz\Gemini\AIPlatform\Enums\HarmCategory;
use XbNz\Gemini\AIPlatform\Enums\Role;
use XbNz\Gemini\AIPlatform\Enums\SafetyThreshold;
use XbNz\Gemini\AIPlatform\Exceptions\GoogleAIPlatformException;
use XbNz\Gemini\AIPlatform\GoogleAIPlatformService;
use XbNz\Gemini\AIPlatform\Saloon\Connectors\GoogleAIPlatformConnector;
use XbNz\Gemini\AIPlatform\Saloon\Requests\GenerateContentRequest;
use XbNz\Gemini\AIPlatform\ValueObjects\BlobPart;
use XbNz\Gemini\AIPlatform\ValueObjects\SafetySettings;
use XbNz\Gemini\AIPlatform\ValueObjects\TextPart;
use XbNz\Gemini\OAuth2\DataTransferObjects\Requests\TokenRequestDTO;
use XbNz\Gemini\OAuth2\GoogleOAuth2Service;
use XbNz\Gemini\OAuth2\ValueObjects\GoogleServiceAccount;

use function Pest\Faker\fake;

test('it can hit the real endpoint and return a dto', function (): void {
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
            getenv('GOOGLE_CLIENT_EMAIL'),
            getenv('GOOGLE_PRIVATE_KEY')
        ),
        'https://www.googleapis.com/auth/cloud-platform',
        CarbonImmutable::now(),
        CarbonImmutable::now()->addHour()
    );

    $tokenResponse = (new GoogleOAuth2Service())->token($tokenRequestDto);

    $connector = new GoogleAIPlatformConnector(
        getenv('GOOGLE_PROJECT_ID'),
        getenv('GOOGLE_REGION'),
    );

    $connector->authenticate(
        new TokenAuthenticator($tokenResponse->accessToken)
    );

    $connector->sender()->getHandlerStack()->push(
        GuzzleRetryMiddleware::factory([
            'max_retry_attempts' => 5,
            'retry_on_status' => [429],
        ])
    );

    $service = new GoogleAIPlatformService(
        $connector
    );

    // Act
    $generationResponse = $service->generateContent($generateContentRequestDto);

    // Assert
    expect($generationResponse)->toBeInstanceOf(GenerateContentResponseDTO::class);
})->group('online');

test('it can work with non-text input', function (): void {
    // Arrange
    $image = base64_encode(file_get_contents(fake()->imageUrl(gray: true, word: 'Hey, fellow friends', randomize: false)));

    $generateContentRequestDto = new GenerateContentRequestDTO(
        'publishers/google/models/gemini-experimental',
        Collection::make([
            new ContentDTO(
                Role::User,
                Collection::make([
                    new BlobPart(
                        'image/png',
                        $image
                    ),
                    new TextPart('Rewrite the text in this image word for word. Respect the original formatting and punctuation.'),
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
            new TextPart("Output format should be: 'The text in the image is is: [extracted text]'. Do not include the square brackets, obviously."),
        ])
    );

    $tokenRequestDto = new TokenRequestDTO(
        new GoogleServiceAccount(
            getenv('GOOGLE_CLIENT_EMAIL'),
            getenv('GOOGLE_PRIVATE_KEY')
        ),
        'https://www.googleapis.com/auth/cloud-platform',
        CarbonImmutable::now(),
        CarbonImmutable::now()->addHour()
    );

    $tokenResponse = (new GoogleOAuth2Service())->token($tokenRequestDto);

    $connector = new GoogleAIPlatformConnector(
        getenv('GOOGLE_PROJECT_ID'),
        getenv('GOOGLE_REGION'),
    );

    $connector->authenticate(
        new TokenAuthenticator($tokenResponse->accessToken)
    );

    $connector->sender()->getHandlerStack()->push(
        GuzzleRetryMiddleware::factory([
            'max_retry_attempts' => 5,
            'retry_on_status' => [429],
        ])
    );

    $service = new GoogleAIPlatformService(
        $connector
    );

    // Act
    $generationResponse = $service->generateContent($generateContentRequestDto);

    // Assert
    expect($generationResponse->content->parts->sole())->toBeInstanceOf(TextPart::class);
    expect($generationResponse->content->parts->sole())->text->toContain('The text in the image is: Hey, fellow friends');
})->group('online');

test('it throws & logs client, server, and connect level errors that occur using the provided logger interface', function (
    MockResponse $mockResponse
): void {
    // Arrange
    $mockClient = new MockClient([
        GenerateContentRequest::class => $mockResponse,
    ]);

    $fakeLogger = new TestLogger();

    $generateContentRequestDto = new GenerateContentRequestDTO(
        'gibberish-model',
        Collection::make([
            new ContentDTO(
                Role::User,
                Collection::make([
                    new TextPart('This is a test'),
                ]),
            ),
        ]),
    );

    $service = new GoogleAIPlatformService(
        (new GoogleAIPlatformConnector('gibberish', 'gibberish'))->withMockClient($mockClient),
        $fakeLogger
    );

    // Act & Assert
    try {
        $service->generateContent($generateContentRequestDto);
    } catch (GoogleAIPlatformException $exception) {
        expect($fakeLogger->hasErrorRecords())->toBeTrue();
        expect($exception->getMessage())->toBe('Fake Saloon exception');

        return;
    }

    $this->fail('An exception was not thrown');
})->with([
    SaloonException::class => MockResponse::make()->throw(new SaloonException('Fake Saloon exception')),
]);

test('before request hook works', function (): void {
    // Arrange
    $mockClient = new MockClient([
        GenerateContentRequest::class => MockResponse::make(status: 200),
    ]);

    $generateContentRequestDto = new GenerateContentRequestDTO(
        'gibberish-model',
        Collection::make([
            new ContentDTO(
                Role::User,
                Collection::make([
                    new TextPart('This is a test'),
                ]),
            ),
        ]),
    );

    $service = new GoogleAIPlatformService(
        (new GoogleAIPlatformConnector('gibberish', 'gibberish'))->withMockClient($mockClient),
    );

    // Act
    try {
        $service->generateContent(
            $generateContentRequestDto,
            beforeRequest: function (GenerateContentRequest $request) {
                $request->headers()->add('X-Test', 'test');

                return $request;
            }
        );
    } catch (Throwable) {
    }

    // Assert
    $mockClient->assertSentCount(1);
    $mockClient->assertSent(function (Request $request) {
        return $request->headers()->get('X-Test') === 'test';
    });
});

test('before request hook must return request', function (): void {
    // Arrange
    $mockClient = new MockClient([
        GenerateContentRequest::class => MockResponse::make(status: 200),
    ]);

    $generateContentRequestDto = new GenerateContentRequestDTO(
        'gibberish-model',
        Collection::make([
            new ContentDTO(
                Role::User,
                Collection::make([
                    new TextPart('This is a test'),
                ]),
            ),
        ]),
    );

    $service = new GoogleAIPlatformService(
        (new GoogleAIPlatformConnector('gibberish', 'gibberish'))->withMockClient($mockClient),
    );

    // Act
    try {
        $service->generateContent(
            $generateContentRequestDto,
            beforeRequest: function (GenerateContentRequest $request) {
                return 'gibberish';
            }
        );

    } catch (InvalidArgumentException) {
        expect(true)->toBeTrue();

        return;
    }

    $this->fail('An exception was not thrown');
});

test('after response hook works', function (): void {
    // Arrange
    $mockClient = new MockClient([
        GenerateContentRequest::class => MockResponse::make(
            body: [
                'test_key' => 'test_value',
            ],
            status: 200
        ),
    ]);

    $generateContentRequestDto = new GenerateContentRequestDTO(
        'gibberish-model',
        Collection::make([
            new ContentDTO(
                Role::User,
                Collection::make([
                    new TextPart('This is a test'),
                ]),
            ),
        ]),
    );

    $service = new GoogleAIPlatformService(
        (new GoogleAIPlatformConnector('gibberish', 'gibberish'))->withMockClient($mockClient),
    );

    // Act & Assert

    try {
        $service->generateContent(
            $generateContentRequestDto,
            afterResponse: function (Response $response) {
                expect($response->json('test_key'))->toBe('test_value');

                return $response;
            }
        );
    } catch (CoercionException) {
    }
});

test('after response hook must return response', function (): void {
    // Arrange
    $mockClient = new MockClient([
        GenerateContentRequest::class => MockResponse::make(
            status: 200
        ),
    ]);

    $generateContentRequestDto = new GenerateContentRequestDTO(
        'gibberish-model',
        Collection::make([
            new ContentDTO(
                Role::User,
                Collection::make([
                    new TextPart('This is a test'),
                ]),
            ),
        ]),
    );

    $service = new GoogleAIPlatformService(
        (new GoogleAIPlatformConnector('gibberish', 'gibberish'))->withMockClient($mockClient),
    );

    // Act
    try {
        $service->generateContent(
            $generateContentRequestDto,
            afterResponse: function (Response $response) {
                return 'gibberish';
            }
        );
    } catch (InvalidArgumentException) {
        expect(true)->toBeTrue();

        return;
    }

    $this->fail('An exception was not thrown');
});
