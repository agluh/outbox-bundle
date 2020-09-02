<?php

declare(strict_types=1);

namespace AGluh\Bundle\OutboxBundle\Relay;

use AGluh\Bundle\OutboxBundle\Domain\Model\OutboxEvent;
use AGluh\Bundle\OutboxBundle\Domain\Model\OutboxEventRepository;
use AGluh\Bundle\OutboxBundle\Event\DomainEventEnqueuedForPublishingEvent;
use AGluh\Bundle\OutboxBundle\Event\DomainEventPublishedEvent;
use AGluh\Bundle\OutboxBundle\Event\WorkerRunningEvent;
use AGluh\Bundle\OutboxBundle\Event\WorkerStartedEvent;
use AGluh\Bundle\OutboxBundle\Event\WorkerStoppedEvent;
use AGluh\Bundle\OutboxBundle\Serialization\SerializerInterface;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Lock\LockFactory;

class Worker
{
    private EventDispatcherInterface $eventDispatcher;
    private LockFactory $lockFactory;
    private OutboxEventRepository $repository;
    private SerializerInterface $serializer;
    private LoggerInterface $logger;
    private bool $shouldStop = false;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        LockFactory $lockFactory,
        OutboxEventRepository $repository,
        SerializerInterface $serializer,
        LoggerInterface $logger
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->lockFactory = $lockFactory;
        $this->repository = $repository;
        $this->serializer = $serializer;
        $this->logger = $logger;
    }

    /**
     * @param array<mixed> $options
     */
    public function run(array $options = []): void
    {
        $options = array_merge([
            'daemonize' => false,
            'sleep' => 1000000, // in microseconds
            'batchSize' => 20,
        ], $options);

        $this->eventDispatcher->dispatch(new WorkerStartedEvent($this));

        if ($options['daemonize']) {
            while (false === $this->shouldStop) {
                $isEventsPublished = $this->publishDomainEvents($options);

                if (false === $isEventsPublished) {
                    $this->eventDispatcher->dispatch(new WorkerRunningEvent($this, true));
                }

                if (false === $this->shouldStop) {
                    usleep($options['sleep']);
                }
            }
        } else {
            $this->publishDomainEvents($options);
        }

        $this->eventDispatcher->dispatch(new WorkerStoppedEvent($this));
    }

    public function stop(): void
    {
        $this->shouldStop = true;
    }

    /**
     * @param array<mixed> $options
     */
    private function publishDomainEvents(array $options): bool
    {
        $isEventsPublished = false;

        $lock = $this->lockFactory->createLock('agluh_outbox_bundle.worker', 0, false);

        if ($lock->acquire()) {
            try {
                do {
                    $outboxEvents = $this->repository->getNextUnpublishedEvents($this->getDate(), $options['batchSize']);

                    foreach ($outboxEvents as $outboxEvent) {
                        $isEventsPublished = $this->publishDomainEvent($outboxEvent);
                        $this->eventDispatcher->dispatch(new WorkerRunningEvent($this, false));

                        // It make no sense to continue publishing events if some of them is not published
                        if ($this->shouldStop || false === $isEventsPublished) {
                            break 2;
                        }
                    }
                } while (count($outboxEvents) > 0);
            } finally {
                $lock->release();
            }
        } else {
            $this->logger->info('Another worker is in process of events publishing, skip the job.');
        }

        return $isEventsPublished;
    }

    private function publishDomainEvent(OutboxEvent $outboxEvent): bool
    {
        $domainEvent = $this->serializer->decode($outboxEvent->eventData());

        $event = new DomainEventEnqueuedForPublishingEvent(
            $outboxEvent->id(),
            $domainEvent,
            $outboxEvent->registrationDate(),
            $outboxEvent->expectedPublicationDate()
        );

        $this->eventDispatcher->dispatch($event);

        if (null === $event->publicationDate()) {
            return false;
        }

        $outboxEvent->markAsPublishedAt($event->publicationDate());
        $this->repository->save($outboxEvent);

        $this->eventDispatcher->dispatch(
            new DomainEventPublishedEvent(
                $outboxEvent->id(),
                $domainEvent,
                $outboxEvent->registrationDate(),
                $outboxEvent->expectedPublicationDate(),
                $event->publicationDate()
            )
        );

        return true;
    }

    protected function getDate(string $time = 'now'): DateTimeImmutable
    {
        return new DateTimeImmutable($time);
    }
}
