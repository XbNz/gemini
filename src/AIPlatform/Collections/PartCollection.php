<?php

declare(strict_types=1);

namespace XbNz\Gemini\AIPlatform\Collections;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use XbNz\Gemini\AIPlatform\Contracts\PartContract;
use XbNz\Gemini\AIPlatform\ValueObjects\TextPart;

/**
 * @template-extends  Collection<PartContract>
 */
class PartCollection extends Collection
{
    /**
     * @param  iterable<int, PartContract>  $items
     */
    public function __construct(iterable $items = [])
    {
        parent::__construct($items);
    }

    public static function ensureAtLeastOneTextPart(PartCollection $parts): void
    {
        $parts->filter(fn (PartContract $part) => $part instanceof TextPart)
            ->whenEmpty(fn () => throw new InvalidArgumentException('At least one text part is required'));
    }
}
