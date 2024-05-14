<?php

declare(strict_types=1);

namespace XbNz\Gemini\OAuth2\Saloon\Connectors;

use Saloon\Http\Connector;
use Saloon\Traits\Body\HasJsonBody;
use Saloon\Traits\Plugins\AcceptsJson;

final class GoogleOAuthConnector extends Connector
{
    use AcceptsJson;
    use HasJsonBody;

    public function resolveBaseUrl(): string
    {
        return 'https://oauth2.googleapis.com';
    }
}
