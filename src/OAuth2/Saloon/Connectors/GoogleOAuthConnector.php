<?php

declare(strict_types=1);

namespace XbNz\Gemini\OAuth2\Saloon\Connectors;

use Saloon\Http\Connector;

final class GoogleOAuthConnector extends Connector
{
    public function resolveBaseUrl(): string
    {
        return 'https://oauth2.googleapis.com';
    }
}
