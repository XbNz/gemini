<?php

declare(strict_types=1);

namespace XbNz\Gemini\OAuth2\Exceptions;

use Exception;
use Saloon\Exceptions\SaloonException;

final class GoogleOAuthException extends Exception
{
    public function __construct(
        string $message = 'An error occurred while reaching out to Google OAuth.',
        int $code = 0,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public static function fromSaloon(SaloonException $exception): self
    {
        return new self(
            $exception->getMessage(),
            previous: $exception
        );
    }
}
