<?php

declare(strict_types=1);

namespace AGluh\Bundle\OutboxBundle\Tests\Unit\Relay;

use AGluh\Bundle\OutboxBundle\Domain\Model\OutboxEvent;
use AGluh\Bundle\OutboxBundle\Domain\Model\OutboxEventRepository;
use AGluh\Bundle\OutboxBundle\Event\DomainEventEnqueuedForPublishingEvent;
use AGluh\Bundle\OutboxBundle\Event\DomainEventPublishedEvent;
use AGluh\Bundle\OutboxBundle\Event\WorkerRunningEvent;
use AGluh\Bundle\OutboxBundle\Event\WorkerStartedEvent;
use AGluh\Bundle\OutboxBundle\Event\WorkerStoppedEvent;
use AGluh\Bundle\OutboxBundle\EventListener\StopWorkerOnEventLimitListener;
use AGluh\Bundle\OutboxBundle\Relay\Worker;
use AGluh\Bundle\OutboxBundle\Serialization\SerializerInterface;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Ramsey\Uuid\Uuid;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;

class WorkerTest extends TestCase
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

    public function test_basic_run_without_unpublished_events(): void
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $eventDispatcher->expects(self::exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [self::isInstanceOf(WorkerStartedEvent::class)],
                [self::isInstanceOf(WorkerStoppedEvent::class)]
            )->willReturnCallback(static function ($event) {
                if ($event instanceof WorkerRunningEvent) {
                    $event->worker()->stop();
                }

                return $event;
            });

        $lock = $this->createMock(LockInterface::class);
        $lock->expects(self::once())->method('acquire')->willReturn(true);

        $lockFactory = $this->createMock(LockFactory::class);
        $lockFactory->expects(self::once())->method('createLock')->willReturn($lock);

        $repository = $this->createMock(OutboxEventRepository::class);
        $repository->expects(self::once())->method('getNextUnpublishedEvents')->willReturn([]);

        $serializer = $this->createMock(SerializerInterface::class);

        $worker = new Worker($eventDispatcher, $lockFactory, $repository, $serializer, new NullLogger());
        $worker->run();
    }

    public function test_basic_run_with_unpublished_event(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $dispatcher->expects(self::exactly(5))
            ->method('dispatch')
            ->withConsecutive(
                [self::isInstanceOf(WorkerStartedEvent::class)],
                [self::isInstanceOf(DomainEventEnqueuedForPublishingEvent::class)],
                [self::isInstanceOf(DomainEventPublishedEvent::class)],
                [self::isInstanceOf(WorkerRunningEvent::class)],
                [self::isInstanceOf(WorkerStoppedEvent::class)]
            )->willReturnCallback(function ($event) {
                if ($event instanceof WorkerRunningEvent) {
                    $event->worker()->stop();
                }

                if ($event instanceof DomainEventEnqueuedForPublishingEvent) {
                    $event->setPublicationDate($this->publishedAt);
                }

                return $event;
            });

        $lock = $this->createMock(LockInterface::class);
        $lock->expects(self::once())->method('acquire')->willReturn(true);

        $lockFactory = $this->createMock(LockFactory::class);
        $lockFactory->expects(self::once())->method('createLock')->willReturn($lock);

        $repository = $this->createMock(OutboxEventRepository::class);
        $repository->expects(self::once())->method('getNextUnpublishedEvents')->willReturn([$this->buildOutboxEvent()]);

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->expects(self::once())->method('decode')->willReturnArgument(0);

        $worker = new Worker($dispatcher, $lockFactory, $repository, $serializer, new NullLogger());
        $worker->run();
    }

    public function test_sleep_timeout_for_daemonized_worker(): void
    {
        $events = [
            [$this->buildOutboxEvent(), $this->buildOutboxEvent()],
            [], // will cause a wait
            [], // will cause a wait
            [$this->buildOutboxEvent()],
            [$this->buildOutboxEvent()],
            [], // will cause a wait
            [$this->buildOutboxEvent()],
        ];

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new StopWorkerOnEventLimitListener(5, new NullLogger()));
        $dispatcher->addSubscriber(new DummyDomainEventEnqueuedForPublishingEventListener());

        $lock = $this->createMock(LockInterface::class);
        $lock->method('acquire')->willReturn(true);

        $lockFactory = $this->createMock(LockFactory::class);
        $lockFactory->method('createLock')->willReturn($lock);

        $repository = $this->createMock(OutboxEventRepository::class);
        $repository->method('getNextUnpublishedEvents')->willReturnOnConsecutiveCalls(...$events);

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->method('decode')->willReturnArgument(0);

        $worker = new Worker($dispatcher, $lockFactory, $repository, $serializer, new NullLogger());
        $startTime = microtime(true);
        // sleep 0.1 after each idle
        $worker->run(['daemonize' => true, 'sleep' => 100000]);

        $duration = microtime(true) - $startTime;
        // wait time should be .3 seconds
        // use .29 & .31 for timing "wiggle room"
        self::assertGreaterThanOrEqual(.29, $duration);
        self::assertLessThan(.31, $duration);
    }

    public function test_worker_skip_job_if_lock_is_not_acquired(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $dispatcher->expects(self::exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [self::isInstanceOf(WorkerStartedEvent::class)],
                [self::isInstanceOf(WorkerStoppedEvent::class)]
            );

        $lock = $this->createMock(LockInterface::class);
        $lock->expects(self::once())->method('acquire')->willReturn(false);

        $lockFactory = $this->createMock(LockFactory::class);
        $lockFactory->expects(self::once())->method('createLock')->willReturn($lock);

        $repository = $this->createMock(OutboxEventRepository::class);
        $repository->method('getNextUnpublishedEvents')->willReturn([$this->buildOutboxEvent()]);

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->method('decode')->willReturnArgument(0);

        $worker = new Worker($dispatcher, $lockFactory, $repository, $serializer, new NullLogger());
        $worker->run();
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

class DummyDomainEventEnqueuedForPublishingEventListener implements EventSubscriberInterface
{
    public function onDomainEventEnqueuedForPublishing(DomainEventEnqueuedForPublishingEvent $event): void
    {
        $event->setPublicationDate(new \DateTimeImmutable());
    }

    /**
     * @return array<mixed>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            DomainEventEnqueuedForPublishingEvent::class => 'onDomainEventEnqueuedForPublishing',
        ];
    }
}
