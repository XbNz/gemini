<?php

declare(strict_types=1);

namespace XbNz\Gemini\AIPlatform\DataTransferObjects;

use Illuminate\Support\Collection;
use XbNz\Gemini\AIPlatform\Contracts\PartContract;
use XbNz\Gemini\AIPlatform\Enums\Role;

final readonly class ContentDTO
{
    /**
     * @param  Collection<int, PartContract>  $parts
     */
    public function __construct(
        public Role $role,
        public Collection $parts
    ) {
    }
}
