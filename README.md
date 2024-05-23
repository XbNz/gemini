![Test suite](https://img.shields.io/github/actions/workflow/status/XbNz/gemini/run-tests.yml?label=Tests&logo=github&style=flat-square)
![Test suite](https://img.shields.io/github/actions/workflow/status/XbNz/gemini/phpstan.yml?label=PHPStan&logo=github&style=flat-square)
![Release](https://img.shields.io/github/v/release/XbNz/gemini?style=flat-square)
![License](https://img.shields.io/github/license/XbNz/gemini?style=flat-square)

# Google Gemini API client with OAuth2 authentication
## Motivation
Other libraries rely on the `generativelanguage.googleapis.com` API, which misses some models. For example, the newly-introduced "Gemini Experimental" model, which is free (!) cannot be accessed through that API. This library solves that issue. 
> [!NOTE]
> The Gemini Experimental API is not guaranteed to last forever. If you are using a model that is available on the `generativelanguage.googleapis.com` API, you can use this library to access it as well.

## Installation
```bash
composer require xbnz/gemini
```

## Prerequisites
### Creating a service account on Google Cloud
1. Go to the [Google Cloud Console](https://console.cloud.google.com/)
2. Go to [IAM & Admin](https://console.cloud.google.com/iam-admin/)
3. Go to [Service Accounts](https://console.cloud.google.com/iam-admin/serviceaccounts)
4. Click on your project
5. Click on "Create Service Account" if you don't have one yet
6. Give it a name and click on "Create"
7. Click on "Done"
8. Go back into the service account you just created
9. Click on "Add Key" and create a JSON key. The file will be downloaded to your computer
10. Grab the necessary information from the JSON file:
    - `client_email`
    - `private_key`

## Getting started

### Sample application
Here is a [sample Laravel command](https://github.com/XbNz/gemini-example/blob/main/app/Console/Commands/GeminiCommand.php) that uses this library. The [service provider](https://github.com/XbNz/gemini-example/blob/main/app/Providers/AppServiceProvider.php) is where the interfaces are bound to concretes.

### Registering to the dependency injection container (optional)

```php
// Laravel example

namespace App\Providers;

clsss AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(GoogleOAuth2Interface::class, function (Application $app) {
            return new GoogleOAuth2Service(
                logger: $app->make(LoggerInterface::class)
            );
        });

        $this->app->bind(GoogleAIPlatformInterface::class, function (Application $app) {
            return new GoogleAIPlatformService(
                (new GoogleAIPlatformConnector(
                    $app->make('config')->get('services.google_ai_platform.project_id'),
                    $app->make('config')->get('services.google_ai_platform.region'),
                ))->authenticate(
                    new TokenAuthenticator(
                        $app->make(GoogleOAuth2Interface::class)->token(
                            new TokenRequestDTO(
                                googleServiceAccount: new GoogleServiceAccount(
                                    $app->make('config')->get('services.google_ai_platform.client_email'),
                                    $app->make('config')->get('services.google_ai_platform.private_key'),
                                ),
                                scope: 'https://www.googleapis.com/auth/cloud-platform',
                                issuedAt: CarbonImmutable::now(),
                                expiration: CarbonImmutable::now()->addHour()
                            )
                        )->accessToken
                    )
                ),
                $app->make('log')
            );
        });
    }
}
```

### Using the AIPlatform service for single or multi-turn conversations 

```php
namespace App\Console\Commands;

class SomeCommand
{
    public function __construct(
        private readonly AIPlatformInterface $aiPlatformService
    ) {}

    public function handle(): void
    {
        $response = $this->aiPlatformService->generateContent(
            new GenerateContentRequestDTO(
                'publishers/google/models/gemini-experimental',
                Collection::make([
                    new ContentDTO(
                        Role::User,
                        Collection::make([
                            new TextPart('Explain what is happening in the image'),
                            new BlobPart(
                                'image/jpeg',
                                'base64 image...',
                            )
                        ])
                    ),
                    new ContentDTO(
                        Role::Model,
                        Collection::make([
                            new TextPart('Sure! This is an image of a cat.'),
                        ]),
                    ),
                    new ContentDTO(
                        Role::User,
                        Collection::make([
                            new TextPart('What color is the cat?'),
                        ]),
                    ),
                    // and so on...
                ])
                Collection::make([
                    new SafetySettings(HarmCategory::HarmCategoryHarassment, SafetyThreshold::BlockOnlyHigh),
                    new SafetySettings(HarmCategory::HarmCategoryHateSpeech, SafetyThreshold::BlockOnlyHigh),
                    new SafetySettings(HarmCategory::HarmCategorySexuallyExplicit, SafetyThreshold::BlockOnlyHigh),
                    new SafetySettings(HarmCategory::HarmCategoryDangerousContent, SafetyThreshold::BlockOnlyHigh),
                ]),
                systemInstructions: Collection::make([
                    new TextPart('Your instructions here...'),
                ]),
                generationConfig: new GenerationConfig(...) // Optional
            )
        );
        
        if ($response->finishReason->consideredSuccessful() === false) {
            // Handle the model not being able to generate content (probably due to unsafe content)
        }
        
        // Do something with the healthy response
        $response->usage->promptTokenCount;
        $modelResponse = $response
            ->content
            ->parts
            ->sole(fn(PartContract $part) => $part instanceof TextPart) // There should only be one TextPart::class in a model response (for now)
            ->text;
    }
}
````

> [!NOTE]
> You may new up the classes directly if you don't want to use a dependency injection container

> [!NOTE]
> You may serialize and store the TextPart::class responses from the model in your database. This will allow you to conduct a persistent chat session with the model, resembling something like teh ChatGPT interface.

## Core concepts
### Extensible authentication
The Google `aiplatform.googleapis.com` API allows several forms of authentication. This library is opinionated toward using the `access_token` derived from your service account. However, the underlying HTTP client of this library (SaloonPHP) allows "Connector" classes to be extended with any authenticator before being newed up. As a result, you may create your own Saloon authentication strategy and pass it to the constructor:
```php
namespace App\Providers;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(GoogleAIPlatformInterface::class, function (Application $app) {
            return new GoogleAIPlatformService(
                (new GoogleAIPlatformConnector(...))->authenticate(new MyCustomAuthenticator),
            );
        });
    }
}
```

### Bearer token authentication
The provided `GoogleOAuthInterface::token()` method allows you to fetch a new token for bearer authentication. Do keep in mind that if you use this tool for authenticating the AIPlatform, the process generates a new token every time the method is called which may or may not be desirable.
 #### Caching your token until expiry (Laravel example)
This package does not provide a caching mechanism – for example using the PSR cache interface – due to the dead simple cache integration of most modern frameworks. Here is a Laravel example:

```php
namespace App\Providers;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
          $this->app->bind(GoogleAIPlatformInterface::class, function (Application $app) {
            return new GoogleAIPlatformService(
                (new GoogleAIPlatformConnector(...))->authenticate(
                    new TokenAuthenticator(
                        $app->make('cache')->remember(
                            'google_api_platform_token',
                            CarbonImmutable::now()->addMinutes(60),
                            function () use ($app) {
                                return $app->make(GoogleOAuth2Interface::class)->token(
                                    new TokenRequestDTO(
                                        googleServiceAccount: new GoogleServiceAccount(
                                            $app->make('config')->get('services.google_ai_platform.client_email'),
                                            $app->make('config')->get('services.google_ai_platform.private_key'),
                                        ),
                                        scope: 'https://www.googleapis.com/auth/cloud-platform',
                                        issuedAt: CarbonImmutable::now(),
                                        expiration: CarbonImmutable::now()->addHour()
                                    )
                                )->accessToken;
                            }
                        ),
                    )
                ),
                $app->make('log')
            );
        });
    }
}
```

### Logging and exception handling

### Providing your own PSR-compatible logger
This library uses the PSR logger interface for logging. You may provide your own logger by passing it to the constructor of the service:

```php
namespace App\Console\Commands;

class SomeCommand
{
    public function handle(): void
    {
        $service = new GoogleAIPlatformService(
            new GoogleAIPlatformConnector(...),
            new MyCustomLogger
        );
        
        try {
            $response = $service->generateContent(...);
        } catch (GoogleAIPlatformException $e) {
            // Handle the exception
        }
        
        // At this point, the logger will have logged the exception
    }
}
````
The same functionality exists on the OAuth2 service.

### Lifecycle hooks for requests and responses
This is not recommended for basic use cases. However, APIs change and maintainers are lazy. If Google adds a new field to a response which I cannot work on, you are free to hook into the response and create your own DTOs if you don't want to make a PR.

```php
namespace App\Console\Commands;

class SomeCommand
{
    public function __construct(
        private readonly GoogleAIPlatformInterface $aiPlatform
    ) {}

    public function handle(): void
    {
        $this->aiPlatform->generateContent(
            requestDto: ...,
            beforeRequest: function (Request $request) {
                $request->headers()->merge([
                    'X-Custom-Header' => 'Value'
                ]);
                
                return $request;
            },
            afterResponse: function (Response $response) {
                // Return your own DTO or do something with the response
                
                return $response;
            }
        )
    }
}
````

### Retrying failed requests
When using free Gemini models, strict rate limits are imposed. You may decide to retry when 429 responses are encountered.
#### Prerequisites
A Guzzle retry middleware is not provided. You may implement your own or use a library of your choice. Example:
```bash
composer require caseyamcl/guzzle_retry_middleware
```

```php
namespace App\Providers;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(GoogleAIPlatformInterface::class, function (Application $app) {
            $connector = new GoogleAIPlatformConnector(...);
            $connector->authenticate(...)
            $connector->sender()->getHandlerStack()->push(
                GuzzleRetryMiddleware::factory([
                    'max_retry_attempts' => 5,
                    'retry_on_status' => [429],
                ])
            )
        
            return new GoogleAIPlatformService(
                $connector
            );
        });
    }
}
```

## Testing
This library provides fake implementations for testing purposes. For example, you may use the `GoogleOAuth2ServiceFake::class` like so:
### Calling code
```php

namespace App\Console\Commands;

use XbNz\Gemini\OAuth2\GoogleOAuth2Interface;

class SomeCommand
{
    public function __construct(
        private readonly GoogleOAuth2Interface $googleOAuth2Service
    ) {}

    public function handle(): void
    {
        $response = $this->googleOAuth2Service->token(...);
    }
}
```

### Test code

```php
namespace Tests\Feature;

use Tests\TestCase;
use XbNz\Gemini\OAuth2\DataTransferObjects\Requests\TokenRequestDTO;
use XbNz\Gemini\OAuth2\GoogleOAuth2ServiceFake;

class SomeCommandTest extends TestCase
{
    public function test_it_works(): void
    {
        // Swapping the real service with the fake one in the Laravel container
        $this->app->swap(GoogleOAuth2Interface::class, $fake = new GoogleOAuth2ServiceFake);
        
        // Basic testing helpers
        $fake->alwaysReturnToken(...);
        $fake->assertTokenRequestCount(...);
        
        // Asserting against requests
        $fake->assertTokenRequest(function (TokenRequestDTO) {
            return $dto->grantType === 'urn:ietf:params:oauth:grant-type:jwt-bearer';
        });
    }
}
```

> [!NOTE]
> The same concept can be applied to the AIPlatform service. The fake implementation is called `GoogleAIPlatformServiceFake::class` and provides similar test assertions