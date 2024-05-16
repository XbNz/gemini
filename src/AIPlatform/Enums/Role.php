<?php

declare(strict_types=1);

namespace XbNz\Gemini\AIPlatform\Enums;

enum Role: string
{
    case Model = 'model';
    case User = 'user';
}
