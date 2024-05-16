<?php

declare(strict_types=1);

namespace XbNz\Gemini\AIPlatform\Saloon\Connectors;

use Saloon\Http\Connector;

class GoogleAIPlatformConnector extends Connector
{
    public function __construct(
        private readonly string $projectId,
        private readonly string $region = 'us-central1'
    ) {
    }

    public function resolveBaseUrl(): string
    {
        return "https://{$this->region}-aiplatform.googleapis.com/v1/projects/{$this->projectId}/locations/{$this->region}";
    }
}
