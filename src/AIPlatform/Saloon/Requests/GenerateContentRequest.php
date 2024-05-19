<?php

declare(strict_types=1);

namespace XbNz\Gemini\AIPlatform\Saloon\Requests;

use CuyZ\Valinor\MapperBuilder;
use CuyZ\Valinor\Normalizer\Format;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;
use Saloon\Traits\Plugins\AcceptsJson;
use XbNz\Gemini\AIPlatform\DataTransferObjects\Requests\GenerateContentRequestDTO;

class GenerateContentRequest extends Request implements HasBody
{
    use AcceptsJson;
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly GenerateContentRequestDTO $generateContentRequestDTO,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/{$this->generateContentRequestDTO->model}:generateContent";
    }

    /**
     * @return array<string, string>
     */
    protected function defaultBody(): array
    {
        return (new MapperBuilder())
            ->registerTransformer(function (object $object, callable $next) {
                return method_exists($object, 'normalize')
                    ? $object->normalize()
                    : $next();
            })
            ->normalizer(Format::array())
            ->normalize($this->generateContentRequestDTO);
    }
}
