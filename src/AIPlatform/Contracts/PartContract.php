<?php

declare(strict_types=1);

namespace XbNz\Gemini\AIPlatform\Contracts;

interface PartContract
{
    /**
     * @return array<non-empty-string, mixed>
     */
    public function toPartArray(): array;
}
