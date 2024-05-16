<?php

declare(strict_types=1);

namespace XbNz\Gemini\AIPlatform\DataTransferObjects;

use XbNz\Gemini\AIPlatform\Collections\PartCollection;
use XbNz\Gemini\AIPlatform\Enums\Role;
use XbNz\Gemini\AIPlatform\ValueObjects\TextPart;

final readonly class ContentDTO
{
    /**
     * @param  PartCollection<int, TextPart>  $parts
     */
    public function __construct(
        public Role $role,
        public PartCollection $parts
    ) {
    }
}
