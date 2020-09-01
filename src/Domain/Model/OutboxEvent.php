<?php

declare(strict_types=1);

namespace AGluh\Bundle\OutboxBundle\Domain\Model;

use DateTimeImmutable;
use Ramsey\Uuid\UuidInterface;
use Webmozart\Assert\Assert;

class OutboxEvent
{
    private UuidInterface $id;
    private string $eventData;
    private DateTimeImmutable $registeredAt;
    private DateTimeImmutable $toBePublishedAt;
    private ?DateTimeImmutable $publishedAt = null;

    public function __construct(UuidInterface $id, string $eventData,
                                DateTimeImmutable $registeredAt, DateTimeImmutable $toBePublishedAt)
    {
        Assert::stringNotEmpty($eventData, 'Event data can not be empty.');

        $this->id = $id;
        $this->eventData = $eventData;
        $this->registeredAt = $registeredAt;
        $this->toBePublishedAt = $toBePublishedAt;
    }

    public function id(): string
    {
        return $this->id->toString();
    }

    public function eventData(): string
    {
        return $this->eventData;
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

    public function markAsPublishedAt(DateTimeImmutable $date): void
    {
        $this->publishedAt = $date;
    }
}
