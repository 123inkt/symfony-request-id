<?php

declare(strict_types=1);

namespace DR\SymfonyTraceBundle\Tests\Functional;

use DR\SymfonyTraceBundle\Messenger\TraceStamp;
use DR\SymfonyTraceBundle\TraceContext;
use DR\SymfonyTraceBundle\TraceStorageInterface;
use DR\SymfonyTraceBundle\Tests\Functional\App\Messenger\TestMessage;
use DR\SymfonyTraceBundle\Tests\Functional\App\Service\TestTraceStorage;
use DR\Utils\Assert;
use Exception;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Symfony\Component\Messenger\Worker;

#[CoversNothing]
class MessengerTest extends AbstractKernelTestCase
{
    private TestTraceStorage $storage;
    private MessageBusInterface $bus;
    private EventDispatcherInterface $dispatcher;
    private InMemoryTransport $transport;
    private ClockInterface&MockObject $clock;

    protected function setUp(): void
    {
        $this->storage    = Assert::isInstanceOf(self::getContainer()->get(TraceStorageInterface::class), TestTraceStorage::class);
        $this->bus        = Assert::isInstanceOf(self::getContainer()->get(MessageBusInterface::class), MessageBusInterface::class);
        $this->dispatcher = Assert::isInstanceOf(self::getContainer()->get('event_dispatcher'), EventDispatcherInterface::class);
        $this->transport  = Assert::isInstanceOf(self::getContainer()->get('messenger.transport.test_transport'), InMemoryTransport::class);
        $this->clock      = $this->createMock(ClockInterface::class);
    }

    /**
     * @throws Exception
     */
    public function testMessageBusShouldAddAndHandlerStampTrace(): void
    {
        $this->storage->setTraceId('foobar');
        $this->storage->setTransactionId('barfoo');

        // ** dispatch **
        $this->bus->dispatch(new TestMessage());
        self::assertTransportHasEnvelopWithTraceStamp($this->transport);

        // ** consume ** (simulate worker)
        (new Worker([$this->transport], $this->bus, $this->dispatcher, clock: $this->clock))->run();
        self::assertStorageHasTrace($this->storage);
    }

    private static function assertTransportHasEnvelopWithTraceStamp(InMemoryTransport $transport): void
    {
        static::assertCount(1, $transport->getSent());

        $envelop = Assert::isArray($transport->get())[0];
        static::assertInstanceOf(Envelope::class, $envelop);

        $stamp = $envelop->last(TraceStamp::class);
        static::assertInstanceOf(TraceStamp::class, $stamp);

        $trace = new TraceContext();
        $trace->setTraceId('foobar');
        $trace->setParentTransactionId('barfoo');
        static::assertEquals($trace, $stamp->trace);
    }

    private static function assertStorageHasTrace(TestTraceStorage $storage): void
    {
        // expect 5: 1x dispatch, 1x receive, 1x handled, 1x dispatch, 1x receive
        static::assertSame(5, $storage->getTraceIdCount);

        // expect 3: 1x dispatch, 1x receive, 1x handled
        static::assertSame(3, $storage->setTraceIdCount);
    }
}
