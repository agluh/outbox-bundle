<?php

declare(strict_types=1);

namespace AGluh\Bundle\OutboxBundle\Domain\Model;

use DateTimeImmutable;

interface OutboxEventRepository
{
    public function append(OutboxEvent $event): void;

    public function getBy(string $id): ?OutboxEvent;

    /**
     * @return array<OutboxEvent>
     */
    public function getNextUnpublishedEvents(DateTimeImmutable $now, int $limit): array;

    public function save(OutboxEvent $event): void;

    public function prunePublishedEvents(): void;
}
