<?php

declare(strict_types=1);

namespace XbNz\Gemini\AIPlatform\Contracts;

use XbNz\Gemini\AIPlatform\DataTransferObjects\Requests\GenerateContentRequestDTO;
use XbNz\Gemini\AIPlatform\DataTransferObjects\Responses\GenerateContentResponseDTO;

interface GoogleAIPlatformInterface
{
    public function generateContent(GenerateContentRequestDTO $requestDto): GenerateContentResponseDTO;
}
