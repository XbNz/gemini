<?php

declare(strict_types=1);

namespace XbNz\Gemini\OAuth2\DataTransferObjects\Responses;

use Carbon\CarbonInterval;

final class TokenResponseDTO
{
    /**
     * @param  non-empty-string  $accessToken
     * @param  non-empty-string  $tokenType
     */
    public function __construct(
        public string $accessToken,
        public CarbonInterval $expiresIn,
        public string $tokenType,
    ) {
    }
}
