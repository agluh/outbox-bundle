<?php

declare(strict_types=1);

namespace AGluh\Bundle\OutboxBundle\Event;

use DateTimeImmutable;
use Symfony\Contracts\EventDispatcher\Event;

final class DomainEventPublishedEvent extends Event
{
    private string $eventId;
    private object $domainEvent;
    private DateTimeImmutable $registeredAt;
    private DateTimeImmutable $toBePublishedAt;
    private DateTimeImmutable $publishedAt;

    public function __construct(string $eventId, object $domainEvent,
                                DateTimeImmutable $registeredAt, DateTimeImmutable $toBePublishedAt,
                                DateTimeImmutable $publishedAt)
    {
        $this->eventId = $eventId;
        $this->domainEvent = $domainEvent;
        $this->registeredAt = $registeredAt;
        $this->toBePublishedAt = $toBePublishedAt;
        $this->publishedAt = $publishedAt;
    }

    public function eventId(): string
    {
        return $this->eventId;
    }

    public function domainEvent(): object
    {
        return $this->domainEvent;
    }

    public function registrationDate(): DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function expectedPublicationDate(): DateTimeImmutable
    {
        return $this->toBePublishedAt;
    }

    public function publicationDate(): DateTimeImmutable
    {
        return $this->publishedAt;
    }
}
