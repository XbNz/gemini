<?php

declare(strict_types=1);

namespace XbNz\Gemini\AIPlatform\Enums;

/**
 * @see https://ai.google.dev/api/python/google/ai/generativelanguage/Candidate/FinishReason
 */
enum FinishReason: string
{
    case Stop = 'STOP';
    case MaxTokens = 'MAX_TOKENS';
    case Safety = 'SAFETY';
    case Recitation = 'RECITATION';
    case Other = 'OTHER';

    public function consideredSuccessful(): bool
    {
        return match ($this) {
            self::Stop => true,
            default => false,
        };
    }
}
