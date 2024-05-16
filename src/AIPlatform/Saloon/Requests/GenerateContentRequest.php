<?php

declare(strict_types=1);

namespace XbNz\Gemini\AIPlatform\Saloon\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;
use Saloon\Traits\Plugins\AcceptsJson;

class GenerateContentRequest extends Request implements HasBody
{
    use AcceptsJson;
    use HasJsonBody;

    protected Method $method = Method::POST;
}
