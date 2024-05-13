<?php

declare(strict_types=1);

namespace XbNz\Gemini\OAuth2\Saloon\Connectors;

use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AcceptsJson;

final class GoogleOAuthConnector extends Connector
{
    use AcceptsJson;

    public function resolveBaseUrl(): string
    {
        return 'https://www.googleapis.com/oauth2/v4/token';
    }

    protected function defaultHeaders(): array
    {
    }
}
