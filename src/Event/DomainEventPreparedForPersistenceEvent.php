<?php

declare(strict_types=1);

namespace AGluh\Bundle\OutboxBundle\Event;

use DateTimeImmutable;
use Symfony\Contracts\EventDispatcher\Event;

final class DomainEventPreparedForPersistenceEvent extends Event
{
    private object $domainEvent;
    private DateTimeImmutable $toBePublishedAt;

    public function __construct(object $domainEvent, DateTimeImmutable $registrationDate)
    {
        $this->domainEvent = $domainEvent;
        $this->toBePublishedAt = $registrationDate;
    }

    public function domainEvent(): object
    {
        return $this->domainEvent;
    }

    public function expectedPublicationDate(): DateTimeImmutable
    {
        return $this->toBePublishedAt;
    }

    public function changeExpectedPublicationDate(DateTimeImmutable $date): void
    {
        $this->toBePublishedAt = $date;
    }
}
