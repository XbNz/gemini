<?php

declare(strict_types=1);

namespace XbNz\Gemini\OAuth2\Contracts;

use XbNz\Gemini\OAuth2\DataTransferObjects\Requests\TokenRequestDTO;
use XbNz\Gemini\OAuth2\DataTransferObjects\Responses\TokenResponseDTO;

interface GoogleOAuth2Interface
{
    public function token(
        TokenRequestDTO $tokenRequestDTO
    ): TokenResponseDTO;
}
