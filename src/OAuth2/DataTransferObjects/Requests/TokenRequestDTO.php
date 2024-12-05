<?php

declare(strict_types=1);

namespace XbNz\Gemini\OAuth2\DataTransferObjects\Requests;

use Carbon\CarbonImmutable;
use XbNz\Gemini\OAuth2\ValueObjects\GoogleServiceAccount;

final readonly class TokenRequestDTO
{
    public function __construct(
        public readonly GoogleServiceAccount $googleServiceAccount,
        public readonly string $scope,
        public readonly CarbonImmutable $issuedAt,
        public readonly CarbonImmutable $expiration,
        public readonly string $grantType = 'urn:ietf:params:oauth:grant-type:jwt-bearer'
    ) {}
}
