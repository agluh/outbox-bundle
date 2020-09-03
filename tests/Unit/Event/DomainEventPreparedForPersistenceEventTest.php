<?php

declare(strict_types=1);

namespace AGluh\Bundle\OutboxBundle\Tests\Unit\Event;

use AGluh\Bundle\OutboxBundle\Event\DomainEventPreparedForPersistenceEvent;
use PHPUnit\Framework\TestCase;

class DomainEventPreparedForPersistenceEventTest extends TestCase
{
    public function test_domain_event_getter_returns_correct_value(): void
    {
        $domainEvent = new \stdClass();
        $eventListener = new DomainEventPreparedForPersistenceEvent($domainEvent, new \DateTimeImmutable());

        self::assertSame($eventListener->domainEvent(), $domainEvent);
    }

    public function test_expected_publication_date_getter_returns_correct_value(): void
    {
        $domainEvent = new \stdClass();
        $now = new \DateTimeImmutable();
        $eventListener = new DomainEventPreparedForPersistenceEvent($domainEvent, $now);

        self::assertEquals($now, $eventListener->expectedPublicationDate());
    }

    public function test_expected_publication_date_setter(): void
    {
        $domainEvent = new \stdClass();
        $now = new \DateTimeImmutable();
        $newDate = $now->modify('+1 day');

        $eventListener = new DomainEventPreparedForPersistenceEvent($domainEvent, $now);
        $eventListener->changeExpectedPublicationDate($newDate);

        self::assertEquals($newDate, $eventListener->expectedPublicationDate());
    }
}
