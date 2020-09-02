<?php

declare(strict_types=1);

namespace AGluh\Bundle\OutboxBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class AggregateRootPreparedForPersistenceEvent extends Event
{
    private object $aggregateRoot;

    /** @var array<mixed> */
    private array $domainEvents = [];

    public function __construct(object $aggregateRoot)
    {
        $this->aggregateRoot = $aggregateRoot;
    }

    public function aggregateRoot(): object
    {
        return $this->aggregateRoot;
    }

    /**
     * @return mixed[]
     */
    public function collectedDomainEvents(): array
    {
        return $this->domainEvents;
    }

    /**
     * @param mixed $domainEvent
     */
    public function addDomainEvent($domainEvent): void
    {
        $this->domainEvents[] = $domainEvent;
    }
}
