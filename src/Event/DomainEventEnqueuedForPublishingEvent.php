<?php

declare(strict_types=1);

namespace AGluh\Bundle\OutboxBundle\Event;

use DateTimeImmutable;
use Symfony\Contracts\EventDispatcher\Event;

final class DomainEventEnqueuedForPublishingEvent extends Event
{
    private string $eventId;
    /** @var mixed */
    private $domainEvent;
    private DateTimeImmutable $registeredAt;
    private DateTimeImmutable $toBePublishedAt;
    private ?DateTimeImmutable $publishedAt = null;

    /**
     * @param string            $eventId
     * @param mixed             $domainEvent
     * @param DateTimeImmutable $registeredAt
     * @param DateTimeImmutable $toBePublishedAt
     */
    public function __construct(string $eventId, $domainEvent,
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

    /**
     * @return mixed
     */
    public function domainEvent()
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
