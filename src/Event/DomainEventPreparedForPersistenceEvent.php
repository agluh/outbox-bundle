<?php

declare(strict_types=1);

namespace AGluh\Bundle\OutboxBundle\Event;

use DateTimeImmutable;
use Symfony\Contracts\EventDispatcher\Event;

final class DomainEventPreparedForPersistenceEvent extends Event
{
    /** @var mixed */
    private $domainEvent;
    private DateTimeImmutable $toBePublishedAt;

    /**
     * @param mixed             $domainEvent
     * @param DateTimeImmutable $registrationDate
     */
    public function __construct($domainEvent, DateTimeImmutable $registrationDate)
    {
        $this->domainEvent = $domainEvent;
        $this->toBePublishedAt = $registrationDate;
    }

    /**
     * @return mixed
     */
    public function domainEvent()
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
