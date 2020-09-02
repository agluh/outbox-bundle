<?php

declare(strict_types=1);

namespace AGluh\Bundle\OutboxBundle\Tests\Unit\Domain\Model;

use AGluh\Bundle\OutboxBundle\Domain\Model\OutboxEvent;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class OutboxEventTest extends TestCase
{
    private const EVENT_DATA = 'some event data';
    private const EVENT_ID = '2ffd5b4b-3f0f-48da-8a09-dd68b903b5f8';
    private const REGISTRATION_DATE = '2020-09-15 9:30:00.0000';
    private const EXPECTED_PUBLICATION_DATE = '2020-09-20 9:30:00.0000';
    private const PUBLICATION_DATE = '2020-09-20 9:40:00.0000';

    private DateTimeImmutable $registeredAt;
    private DateTimeImmutable $toBePublishedAt;
    private DateTimeImmutable $publishedAt;

    protected function setUp(): void
    {
        $this->registeredAt = new DateTimeImmutable(self::REGISTRATION_DATE);
        $this->toBePublishedAt = new DateTimeImmutable(self::EXPECTED_PUBLICATION_DATE);
        $this->publishedAt = new DateTimeImmutable(self::PUBLICATION_DATE);
    }

    public function test_event_id_getter_returns_correct_value(): void
    {
        $outboxEvent = $this->buildOutboxEvent();

        self::assertEquals(self::EVENT_ID, $outboxEvent->id());
    }

    public function test_event_data_getter_returns_correct_value(): void
    {
        $outboxEvent = $this->buildOutboxEvent();

        self::assertEquals(self::EVENT_DATA, $outboxEvent->eventData());
    }

    public function test_registration_date_getter_returns_correct_value(): void
    {
        $outboxEvent = $this->buildOutboxEvent();

        self::assertEquals($this->registeredAt, $outboxEvent->registrationDate());
    }

    public function test_expected_publication_date_getter_returns_correct_value(): void
    {
        $outboxEvent = $this->buildOutboxEvent();

        self::assertEquals($this->toBePublishedAt, $outboxEvent->expectedPublicationDate());
    }

    public function test_published_at_setter(): void
    {
        $outboxEvent = $this->buildOutboxEvent();

        $outboxEvent->markAsPublishedAt($this->publishedAt);

        self::assertEquals($this->publishedAt, $outboxEvent->publicationDate());
    }

    private function buildOutboxEvent(): OutboxEvent
    {
        return new OutboxEvent(
            Uuid::fromString(self::EVENT_ID),
            self::EVENT_DATA,
            $this->registeredAt,
            $this->toBePublishedAt
        );
    }
}
