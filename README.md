![Test suite](https://img.shields.io/github/workflow/status/XbNz/gemini/CI/main?style=flat-square)
![Release](https://img.shields.io/github/v/release/XbNz/gemini?style=flat-square)
![License](https://img.shields.io/github/license/XbNz/gemini?style=flat-square)

# Google Gemini API client with OAuth2 authentication
## Motivation
Other libraries relied on the generativelanguage.googleapis.com API, which misses some models. For example, the newly-introduced "Gemini Experimental" model, which is free (!) cannot be accessed through that API. This library solves that issue. 
> [!NOTE]
> The Gemini Experimental API is not guaranteed to last forever. If you are using a model that is available using the generativelanguage.googleapis.com API, you should use one of the other PHP libraries that are based on that

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

### Registering to the dependency injection container (optional)

```php
// Laravel example

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;use XbNz\Gemini\OAuth2\Saloon\Connectors\GoogleOAuthConnector;

clsss AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(GoogleOAuth2Interface::class, function (Application $app) {
            return new GoogleOAuth2Service(
                new GoogleOAuthConnector
                $app->make(LoggerInterface::class)
            );
        });
        
        $this->app->bind(AIPlatformInterface::class, function (Application $app) {
            return new AIPlatformService(
                (new AIPlatformConnector)
                $app->make(LoggerInterface::class)
            );
        });
    }
}
```

### Using the AIPlatform service
```php
namespace App\Http\Commands;

use XbNz\Gemini\AIPlatform\AIPlatformInterface;

class SomeCommand
{
    public function __construct(
        private readonly AIPlatformInterface $aiPlatformService
    ) {}

    public function handle(): void
    {
        $response = $this->aiPlatformService->generateContent(
            
        )
    }
}
````

> [!NOTE]
> You may new up the classes directly if you don't want to use a dependency injection container

## Core concepts
### Extensible authentication
The Google `aiplatform.googleapis.com` API allows several forms of authentication. This library is opinionated toward using the `access_token` derived from your service account. However, the underlying HTTP client of this library (SaloonPHP) allows "Connector" classes to be extended with any authenticator before being newed up. As a result, you may create your own Saloon authentication strategy and pass it to the constructor:
```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AIPlatformInterface::class, function (Application $app) {
            return new AIPlatformService(
                (new AIPlatformConnector)->setAuthenticator(new MyCustomAuthenticator)
                $app->make(LoggerInterface::class)
            );
        });
    }
}
```

### Bearer token authentication
The provided `GoogleOAuthInterface::token()` method allows you to fetch a new token for bearer authentication. Do keep in mind that if you use this tool for authenticating the AIPlatform, the process generates a new token every time the method is called which may or may not be desirable.
#### Use your service container's singleton to prevent multiple token requests
```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(GoogleOAuth2Interface::class, function (Application $app) {
            return new GoogleOAuth2Service(
                new GoogleOAuthConnector
                $app->make(LoggerInterface::class)
            );
        });
    }
}
```

> [!WARNING]
> When using persistent processes such as Laravel Octane, pay attention to register the singleton properly so that it does not persist across requests. This may lead to token expiration issues.

## Testing
This library provides fake implementations for testing purposes. For example, you may use the `GoogleOAuth2ServiceFake::class` like so:
### Calling code
```php
// Laravel example
namespace App\Http\Commands;

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