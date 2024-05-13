<?php

declare(strict_types=1);

namespace XbNz\Gemini\OAuth2\ValueObjects;

use SensitiveParameter;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class GoogleServiceAccount
{
    public function __construct(
        #[SensitiveParameter]
        #[Assert\NotBlank]
        public string $clientId,
    ) {
    }
}
