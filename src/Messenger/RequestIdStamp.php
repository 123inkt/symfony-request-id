<?php

declare(strict_types=1);

namespace DR\SymfonyRequestId\Messenger;

use Symfony\Component\Messenger\Stamp\StampInterface;

class RequestIdStamp implements StampInterface
{
    /**
     * @codeCoverageIgnore - Simple DTO
     */
    public function __construct(public readonly string $requestId)
    {
    }
}
