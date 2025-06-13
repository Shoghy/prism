<?php

declare(strict_types=1);

namespace Prism\Prism\Providers\Anthropic;

use Closure;
use Generator;
use Illuminate\Http\Client\PendingRequest;
use Prism\Prism\Concerns\HasHttpClient;
use Prism\Prism\Contracts\Provider;
use Prism\Prism\Embeddings\Request as EmbeddingRequest;
use Prism\Prism\Embeddings\Response as EmbeddingResponse;
use Prism\Prism\Providers\Anthropic\Handlers\Stream;
use Prism\Prism\Providers\Anthropic\Handlers\Structured;
use Prism\Prism\Providers\Anthropic\Handlers\Text;
use Prism\Prism\Structured\Request as StructuredRequest;
use Prism\Prism\Structured\Response as StructuredResponse;
use Prism\Prism\Text\Request as TextRequest;
use Prism\Prism\Text\Response;

readonly class Anthropic implements Provider
{
    use HasHttpClient;

    public function __construct(
        #[\SensitiveParameter] public string $apiKey,
        public string $apiVersion,
        public ?string $betaFeatures = null
    ) {}

    #[\Override]
    public function text(TextRequest $request): Response
    {
        $handler = new Text(
            $this->client(
                $request->clientOptions(),
                $request->clientRetry()
            ),
            $request
        );

        return $handler->handle();
    }

    #[\Override]
    public function structured(StructuredRequest $request): StructuredResponse
    {
        $handler = new Structured(
            $this->client(
                $request->clientOptions(),
                $request->clientRetry()
            ),
            $request
        );

        return $handler->handle();
    }

    #[\Override]
    public function stream(TextRequest $request): Generator
    {
        $handler = new Stream($this->client(
            $request->clientOptions(),
            $request->clientRetry()
        ));

        return $handler->handle($request);
    }

    #[\Override]
    public function embeddings(EmbeddingRequest $request): EmbeddingResponse
    {
        throw new \Exception(sprintf('%s does not support embeddings', class_basename($this)));
    }

    /**
     * @param  array<string, mixed>  $options
     * @param  array{0: array<int, int>|int, 1?: Closure|int, 2?: ?callable, 3?: bool}|array{}  $retry
     */
    protected function client(array $options = [], array $retry = []): PendingRequest
    {
        return $this->createHttpClient(
            headers: array_filter([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => $this->apiVersion,
                'anthropic-beta' => $this->betaFeatures,
            ]),
            options: $options,
            retry: $retry
        )->baseUrl('https://api.anthropic.com/v1');
    }
}
