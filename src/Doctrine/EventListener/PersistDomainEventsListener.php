<?php

declare(strict_types=1);

namespace AGluh\Bundle\OutboxBundle\Doctrine\EventListener;

use AGluh\Bundle\OutboxBundle\Domain\Model\OutboxEvent;
use AGluh\Bundle\OutboxBundle\Domain\Model\OutboxEventRepository;
use AGluh\Bundle\OutboxBundle\Event\AggregateRootPreparedForPersistenceEvent;
use AGluh\Bundle\OutboxBundle\Event\DomainEventPreparedForPersistenceEvent;
use AGluh\Bundle\OutboxBundle\Serialization\SerializerInterface;
use DateTimeImmutable;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Ramsey\Uuid\Uuid;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PersistDomainEventsListener implements EventSubscriber
{
    private EventDispatcherInterface $eventDispatcher;
    private OutboxEventRepository $eventsRepository;
    private SerializerInterface $serializer;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        OutboxEventRepository $eventsRepository,
        SerializerInterface $serializer
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->eventsRepository = $eventsRepository;
        $this->serializer = $serializer;
    }

    /**
     * @return array<mixed>
     */
    public function getSubscribedEvents(): array
    {
        return [
            Events::onFlush,
        ];
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $this->persistEntityDomainEvents($args);
    }

    private function persistEntityDomainEvents(OnFlushEventArgs $args): void
    {
        $uow = $args->getEntityManager()->getUnitOfWork();

        $sources = [
            $uow->getScheduledEntityInsertions(),
            $uow->getScheduledEntityUpdates(),
            $uow->getScheduledEntityDeletions(),

            // TODO: what about collections?
            // $uow->getScheduledCollectionDeletions(),
            // $uow->getScheduledCollectionUpdates()
        ];

        foreach ($sources as $source) {
            foreach ($source as $entity) {
                $this->collectDomainEvents($entity);
            }
        }
    }

    private function collectDomainEvents(object $entity): void
    {
        $aggregatePreparedEvent = new AggregateRootPreparedForPersistenceEvent($entity);

        $this->eventDispatcher->dispatch($aggregatePreparedEvent);

        foreach ($aggregatePreparedEvent->collectedDomainEvents() as $domainEvent) {
            $this->persistDomainEvent($domainEvent);
        }
    }

    /**
     * @param mixed $domainEvent
     */
    private function persistDomainEvent($domainEvent): void
    {
        $eventPreparedEvent = new DomainEventPreparedForPersistenceEvent(
            $domainEvent,
            $this->getDate()
        );

        $this->eventDispatcher->dispatch($eventPreparedEvent);

        $outboxEvent = new OutboxEvent(
            Uuid::uuid4(),
            $this->serializer->encode($domainEvent),
            $this->getDate(),
            $eventPreparedEvent->expectedPublicationDate()
        );

        $this->eventsRepository->append($outboxEvent);
    }

    protected function getDate(string $time = 'now'): DateTimeImmutable
    {
        return new DateTimeImmutable($time);
    }
}
