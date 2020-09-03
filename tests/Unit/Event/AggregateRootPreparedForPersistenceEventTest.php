<?php

declare(strict_types=1);

namespace AGluh\Bundle\OutboxBundle\Tests\Unit\Event;

use AGluh\Bundle\OutboxBundle\Event\AggregateRootPreparedForPersistenceEvent;
use PHPUnit\Framework\TestCase;

class AggregateRootPreparedForPersistenceEventTest extends TestCase
{
    public function test_aggregate_getter_returns_correct_value(): void
    {
        $aggregate = new \stdClass();
        $eventListener = new AggregateRootPreparedForPersistenceEvent($aggregate);

        self::assertSame($eventListener->aggregateRoot(), $aggregate);
        self::assertCount(0, $eventListener->collectedDomainEvents());
    }

    public function test_collected_domain_events_getter_returns_correct_value(): void
    {
        $aggregate = new \stdClass();
        $eventListener = new AggregateRootPreparedForPersistenceEvent($aggregate);

        $domainEvent = new \stdClass();
        $eventListener->addDomainEvent($domainEvent);
        $collectedDomainEvents = $eventListener->collectedDomainEvents();

        self::assertCount(1, $collectedDomainEvents);
        self::assertSame($collectedDomainEvents[0], $domainEvent);
    }
}
