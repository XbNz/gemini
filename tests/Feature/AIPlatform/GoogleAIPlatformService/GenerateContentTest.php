<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use ColinODell\PsrTestLogger\TestLogger;
use CuyZ\Valinor\Mapper\Source\Source;
use CuyZ\Valinor\MapperBuilder;
use Illuminate\Support\Collection;
use Saloon\Exceptions\SaloonException;
use Saloon\Http\Auth\TokenAuthenticator;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use XbNz\Gemini\AIPlatform\Contracts\PartContract;
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

//test('example', function (): void {
//
//    $json = '';
//
//    $t = (new MapperBuilder())
//        ->allowSuperfluousKeys()
//        ->infer(
//            PartContract::class,
//            fn () => TextPart::class
//        )
//        ->mapper()
//        ->map(
//            GenerateContentResponseDTO::class,
//            Source::array(json_decode($json, true))
//                ->map([
//                    'candidates.*.content' => 'content',
//                    'candidates.*.content.role' => 'content.role',
//                    'candidates.*.content.parts' => 'content.parts',
//                    'finishReason' => 'candidates.*.content.parts',
//                    'usageMetadata' => 'usage',
//                ])
//        );
//
//    dd($t);
//});

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
