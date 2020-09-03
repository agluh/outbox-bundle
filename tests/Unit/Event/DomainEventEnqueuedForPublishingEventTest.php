<?php

declare(strict_types=1);

namespace AGluh\Bundle\OutboxBundle\Tests\Unit\Event;

use AGluh\Bundle\OutboxBundle\Event\DomainEventEnqueuedForPublishingEvent;
use PHPUnit\Framework\TestCase;

class DomainEventEnqueuedForPublishingEventTest extends TestCase
{
    private const EVENT_ID = '2ffd5b4b-3f0f-48da-8a09-dd68b903b5f8';
    private const REGISTRATION_DATE = '2020-09-03 10:00:00.000001';
    private const EXPECTED_PUBLICATION_DATE = '2020-09-03 10:00:00.000002';
    private const PUBLICATION_DATE = '2020-09-03 10:00:00.000003';

    private object $domainEvent;
    private \DateTimeImmutable $registeredAt;
    private \DateTimeImmutable $toBePublishedAt;
    private DomainEventEnqueuedForPublishingEvent $eventListener;

    public function setUp(): void
    {
        $this->domainEvent = new \stdClass();
        $this->registeredAt = new \DateTimeImmutable(self::REGISTRATION_DATE);
        $this->toBePublishedAt = new \DateTimeImmutable(self::EXPECTED_PUBLICATION_DATE);
        $this->eventListener = new DomainEventEnqueuedForPublishingEvent(self::EVENT_ID, $this->domainEvent, $this->registeredAt, $this->toBePublishedAt);
    }

    public function test_event_id_getter_returns_correct_value(): void
    {
        self::assertEquals(self::EVENT_ID, $this->eventListener->eventId());
    }

    public function test_domain_event_getter_returns_correct_value(): void
    {
        self::assertSame($this->domainEvent, $this->eventListener->domainEvent());
    }

    public function test_registration_date_getter_returns_correct_value(): void
    {
        self::assertEquals($this->registeredAt, $this->eventListener->registrationDate());
    }

    public function test_expected_publication_date_getter_returns_correct_value(): void
    {
        self::assertEquals($this->toBePublishedAt, $this->eventListener->expectedPublicationDate());
    }

    public function test_publication_date_getter_returns_correct_value(): void
    {
        self::assertNull($this->eventListener->publicationDate());
    }

    public function test_publication_date_setter(): void
    {
        $publishedAt = new \DateTimeImmutable(self::PUBLICATION_DATE);
        $this->eventListener->setPublicationDate($publishedAt);

        self::assertEquals($publishedAt, $this->eventListener->publicationDate());
    }
}
