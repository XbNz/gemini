<?php

declare(strict_types=1);

namespace XbNz\Gemini\OAuth2;

use Carbon\CarbonInterval;
use Closure;
use PHPUnit\Framework\Assert;
use XbNz\Gemini\OAuth2\Contracts\GoogleOAuth2Interface;
use XbNz\Gemini\OAuth2\DataTransferObjects\Requests\TokenRequestDTO;
use XbNz\Gemini\OAuth2\DataTransferObjects\Responses\TokenResponseDTO;

final class GoogleOAuth2ServiceFake implements GoogleOAuth2Interface
{
    private ?TokenResponseDTO $returnTokenResponse = null;

    /**
     * @var array<int, TokenRequestDTO>
     */
    private array $tokenRequests = [];

    public function token(
        TokenRequestDTO $tokenRequestDTO
    ): TokenResponseDTO {
        $this->tokenRequests[] = $tokenRequestDTO;

        $this->returnTokenResponse ??= new TokenResponseDTO(
            'fake_access_token',
            CarbonInterval::seconds(3600),
            'Bearer'
        );

        return $this->returnTokenResponse;
    }

    public function alwaysReturnToken(TokenResponseDTO $tokenResponseDTO): void
    {
        $this->returnTokenResponse = $tokenResponseDTO;
    }

    /**
     * @param  Closure(TokenRequestDTO): bool  $closure
     */
    private function tokenRequest(Closure $closure): bool
    {
        foreach ($this->tokenRequests as $tokenRequest) {
            if ($closure($tokenRequest) === true) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  Closure(TokenRequestDTO): bool  $closure
     */
    public function assertTokenRequest(Closure $closure): void
    {
        Assert::assertTrue($this->tokenRequest($closure));
    }

    public function assertTokenRequestCount(int $expectedCount): void
    {
        Assert::assertCount($expectedCount, $this->tokenRequests);
    }
}
