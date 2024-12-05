<?php

declare(strict_types=1);

namespace XbNz\Gemini\OAuth2;

use Carbon\CarbonInterval;
use Firebase\JWT\JWT;
use Psl\Type;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Saloon\Exceptions\SaloonException;
use XbNz\Gemini\OAuth2\Contracts\GoogleOAuth2Interface;
use XbNz\Gemini\OAuth2\DataTransferObjects\Requests\TokenRequestDTO;
use XbNz\Gemini\OAuth2\DataTransferObjects\Responses\TokenResponseDTO;
use XbNz\Gemini\OAuth2\Exceptions\GoogleOAuthException;
use XbNz\Gemini\OAuth2\Saloon\Connectors\GoogleOAuthConnector;
use XbNz\Gemini\OAuth2\Saloon\Requests\TokenRequest;

final class GoogleOAuth2Service implements GoogleOAuth2Interface
{
    public function __construct(
        private readonly GoogleOAuthConnector $connector = new GoogleOAuthConnector(),
        private readonly LoggerInterface $logger = new NullLogger()
    ) {}

    /**
     * @throws ClientExceptionInterface
     */
    public function token(
        TokenRequestDTO $tokenRequestDTO
    ): TokenResponseDTO {
        $jwt = JWT::encode(
            $this->buildPayload($tokenRequestDTO),
            $tokenRequestDTO->googleServiceAccount->privateKey,
            'RS256'
        );

        try {
            $response = $this->connector->send(
                new TokenRequest($tokenRequestDTO, $jwt)
            )->throw();
        } catch (SaloonException $exception) {
            $this->logger->error(
                'An error occurred while attempting to retrieve a token from Google OAuth',
                ['exception' => $exception]
            );

            throw GoogleOAuthException::fromSaloon($exception);
        }

        $validatedResponse = Type\shape([
            'access_token' => Type\non_empty_string(),
            'expires_in' => Type\positive_int(),
            'token_type' => Type\non_empty_string(),
        ])->coerce($response->json());

        return new TokenResponseDTO(
            $validatedResponse['access_token'],
            CarbonInterval::seconds($validatedResponse['expires_in']),
            $validatedResponse['token_type']
        );
    }

    /**
     * @return array{iss: string, scope: string, aud: string, exp: int, iat: int}
     */
    private function buildPayload(TokenRequestDTO $tokenRequestDTO): array
    {
        return [
            'iss' => $tokenRequestDTO->googleServiceAccount->clientEmail,
            'scope' => $tokenRequestDTO->scope,
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $tokenRequestDTO->expiration->getTimestamp(),
            'iat' => $tokenRequestDTO->issuedAt->getTimestamp(),
        ];
    }
}
