<?php

declare(strict_types=1);

namespace XbNz\Gemini\OAuth2;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SensitiveParameter;
use XbNz\Gemini\OAuth2\Saloon\Connectors\GoogleOAuthConnector;
use XbNz\Gemini\OAuth2\ValueObjects\GoogleServiceAccount;

final class GoogleOAuth2Service
{
    public function __construct(
        private readonly GoogleOAuthConnector $connector = new GoogleOAuthConnector,
        private readonly LoggerInterface $logger = new NullLogger()
    ) {
    }

    public function token(
        // TODO: Take request dto
        //        #[SensitiveParameter]
        //        private readonly GoogleServiceAccount $googleServiceAccount,
    ): void {
    }
}
