<?php

declare(strict_types=1);

namespace XbNz\Gemini\OAuth2\ValueObjects;

use SensitiveParameter;

final readonly class GoogleServiceAccount
{
    public function __construct(
        #[SensitiveParameter]
        public string $clientEmail,
        #[SensitiveParameter]
        public string $privateKey,
    ) {
    }
}
