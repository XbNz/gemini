<?php

declare(strict_types=1);

namespace XbNz\Gemini\AIPlatform;

use Closure;
use Illuminate\Support\Collection;
use Psl\Type;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Saloon\Exceptions\SaloonException;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Webmozart\Assert\Assert;
use XbNz\Gemini\AIPlatform\Contracts\GoogleAIPlatformInterface;
use XbNz\Gemini\AIPlatform\DataTransferObjects\ContentDTO;
use XbNz\Gemini\AIPlatform\DataTransferObjects\Requests\GenerateContentRequestDTO;
use XbNz\Gemini\AIPlatform\DataTransferObjects\Responses\GenerateContentResponseDTO;
use XbNz\Gemini\AIPlatform\Enums\FinishReason;
use XbNz\Gemini\AIPlatform\Enums\Role;
use XbNz\Gemini\AIPlatform\Exceptions\GoogleAIPlatformException;
use XbNz\Gemini\AIPlatform\Saloon\Connectors\GoogleAIPlatformConnector;
use XbNz\Gemini\AIPlatform\Saloon\Requests\GenerateContentRequest;
use XbNz\Gemini\AIPlatform\ValueObjects\TextPart;
use XbNz\Gemini\AIPlatform\ValueObjects\Usage;

final class GoogleAIPlatformService implements GoogleAIPlatformInterface
{
    public function __construct(
        private GoogleAIPlatformConnector $connector,
        private readonly LoggerInterface $logger = new NullLogger()
    ) {}

    /**
     * @param  Closure(GenerateContentRequest): Request|null  $beforeRequest
     * @param  Closure(Response): Response|null  $afterResponse
     */
    public function generateContent(
        GenerateContentRequestDTO $requestDto,
        ?Closure $beforeRequest = null,
        ?Closure $afterResponse = null
    ): GenerateContentResponseDTO {
        $request = new GenerateContentRequest($requestDto);

        if ($beforeRequest !== null) {
            $request = $beforeRequest(clone $request);
        }

        Assert::isInstanceOf($request, Request::class);

        try {
            $response = $this->connector->send($request)->throw();
        } catch (SaloonException $exception) {
            $this->logger->error(
                'An error occurred while attempting to generate content from Google AI Platform',
                ['exception' => $exception]
            );

            throw GoogleAIPlatformException::fromSaloon($exception);
        }

        if ($afterResponse !== null) {
            $response = $afterResponse(clone $response);
        }

        Assert::isInstanceOf($response, Response::class);

        $validatedResponse = Type\shape([
            'candidates' => Type\shape([
                0 => Type\shape([
                    'finishReason' => Type\backed_enum(FinishReason::class),
                    'content' => Type\optional(Type\shape([
                        'role' => Type\backed_enum(Role::class),
                        'parts' => Type\shape([
                            0 => Type\shape([
                                'text' => Type\string(),
                            ]),
                        ]),
                    ])),
                ]),
            ]),
            'usageMetadata' => Type\shape([
                'promptTokenCount' => Type\int(),
                'totalTokenCount' => Type\int(),
                'candidatesTokenCount' => Type\optional(Type\int()),
            ]),
        ])->coerce($response->json());

        return new GenerateContentResponseDTO(
            $validatedResponse['candidates'][0]['finishReason'],
            new Usage(
                $validatedResponse['usageMetadata']['promptTokenCount'],
                $validatedResponse['usageMetadata']['totalTokenCount'],
                $validatedResponse['usageMetadata']['candidatesTokenCount'] ?? null
            ),
            array_key_exists('content', $validatedResponse['candidates'][0])
                ? new ContentDTO(
                    $validatedResponse['candidates'][0]['content']['role'],
                    new Collection([
                        new TextPart($validatedResponse['candidates'][0]['content']['parts'][0]['text']),
                    ])
                )
                : null
        );

    }
}
