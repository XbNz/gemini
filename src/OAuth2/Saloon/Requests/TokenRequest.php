<?php

declare(strict_types=1);

namespace XbNz\Gemini\OAuth2\Saloon\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;
use Saloon\Traits\Plugins\AcceptsJson;
use XbNz\Gemini\OAuth2\DataTransferObjects\Requests\TokenRequestDTO;

final class TokenRequest extends Request implements HasBody
{
    use AcceptsJson;
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly TokenRequestDTO $tokenRequestDTO,
        private readonly string $jwt,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return '/token';
    }

    /**
     * @return array<string, string>
     */
    protected function defaultBody(): array
    {
        return [
            'grant_type' => $this->tokenRequestDTO->grantType,
            'assertion' => $this->jwt,
        ];
    }
}
