<?php

declare(strict_types=1);

namespace DR\SymfonyTraceBundle\Tests\Unit\Http;

use DR\SymfonyTraceBundle\Http\TraceIdAwareHttpClient;
use DR\SymfonyTraceBundle\Service\TraceContextService;
use DR\SymfonyTraceBundle\TraceId;
use DR\SymfonyTraceBundle\TraceStorageInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\ScopingHttpClient;

#[CoversClass(TraceIdAwareHttpClient::class)]
class TraceIdAwareHttpClientTest extends TestCase
{
    private ScopingHttpClient&MockObject $client;
    private TraceStorageInterface&MockObject $storage;
    private TraceIdAwareHttpClient $traceAwareHttpClient;

    protected function setUp(): void
    {
        $this->client               = $this->createMock(ScopingHttpClient::class);
        $this->storage              = $this->createMock(TraceStorageInterface::class);
        $this->traceAwareHttpClient = new TraceIdAwareHttpClient($this->client, $this->storage, 'X-Trace-Id');
    }

    public function testRequest(): void
    {
        $this->storage->expects(self::once())->method('getTraceId')->willReturn('12345');
        $this->client->expects(self::once())->method('request')->with('GET', 'http://localhost', [
            'headers' => ['X-Trace-Id' => '12345'],
        ]);

        $this->traceAwareHttpClient->request('GET', 'http://localhost');
    }

    public function testStream(): void
    {
        $this->client->expects(self::once())->method('stream')->with([]);

        $this->traceAwareHttpClient->stream([]);
    }

    public function testReset(): void
    {
        $this->client->expects(self::once())->method('reset');

        $this->traceAwareHttpClient->reset();
    }

    public function testSetLogger(): void
    {
        $this->client->expects(self::once())->method('setLogger');

        $this->traceAwareHttpClient->setLogger($this->createMock(LoggerInterface::class));
    }

    public function testWithOptions(): void
    {
        $this->client->expects(self::once())->method('withOptions')->willReturn($this->client);

        $this->traceAwareHttpClient->withOptions([]);
    }
}
