<?php

declare(strict_types=1);

namespace AGluh\Bundle\OutboxBundle\Event;

use DateTimeImmutable;
use Symfony\Contracts\EventDispatcher\Event;

final class DomainEventEnqueuedForPublishingEvent extends Event
{
    private string $eventId;
    private object $domainEvent;
    private DateTimeImmutable $registeredAt;
    private DateTimeImmutable $toBePublishedAt;
    private ?DateTimeImmutable $publishedAt = null;

    public function __construct(string $eventId, object $domainEvent,
                                DateTimeImmutable $registeredAt, DateTimeImmutable $toBePublishedAt)
    {
        $this->eventId = $eventId;
        $this->domainEvent = $domainEvent;
        $this->registeredAt = $registeredAt;
        $this->toBePublishedAt = $toBePublishedAt;
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

    public function publicationDate(): ?DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublicationDate(DateTimeImmutable $publishedAt): void
    {
        $this->publishedAt = $publishedAt;
    }
}
