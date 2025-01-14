<?php

declare(strict_types=1);

namespace DR\SymfonyTraceBundle\Service;

use DR\SymfonyTraceBundle\Http\TraceAwareHttpClient;
use DR\SymfonyTraceBundle\TraceContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @phpstan-import-type HttpClientOptions from TraceAwareHttpClient
 */
interface TraceServiceInterface
{
    public function supports(Request $request): bool;

    public function createNewTrace(): TraceContext;

    public function createTraceFrom(string $traceId): TraceContext;

    public function getRequestTrace(Request $request): TraceContext;

    public function handleResponse(Response $response, TraceContext $context): void;

    /**
     * @param HttpClientOptions $options
     * @return HttpClientOptions
     */
    public function handleClientRequest(TraceContext $trace, string $method, string $url, array $options = []): array;
}
