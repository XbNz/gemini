<?php

declare(strict_types=1);

namespace XbNz\Gemini\AIPlatform\Enums;

/**
 * @see https://ai.google.dev/gemini-api/docs/safety-settings
 */
enum SafetyThreshold: string
{
    case BlockNone = 'BLOCK_NONE';
    case BlockOnlyHigh = 'BLOCK_ONLY_HIGH';
    case BlockMediumAndAbove = 'BLOCK_MEDIUM_AND_ABOVE';
    case BlockLowAndAbove = 'BLOCK_LOW_AND_ABOVE';

}
