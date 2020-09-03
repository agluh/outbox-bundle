<?php

declare(strict_types=1);

namespace AGluh\Bundle\OutboxBundle\Tests\Unit\Doctrine\EventListener;

use AGluh\Bundle\OutboxBundle\Doctrine\EventListener\PersistDomainEventsListener;
use AGluh\Bundle\OutboxBundle\Domain\Model\OutboxEvent;
use AGluh\Bundle\OutboxBundle\Domain\Model\OutboxEventRepository;
use AGluh\Bundle\OutboxBundle\Event\AggregateRootPreparedForPersistenceEvent;
use AGluh\Bundle\OutboxBundle\Event\DomainEventPreparedForPersistenceEvent;
use AGluh\Bundle\OutboxBundle\Serialization\SerializerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PersistDomainEventsListenerTest extends TestCase
{
    public function test_persist_domain_event(): void
    {
        $repository = $this->createMock(OutboxEventRepository::class);
        $repository->expects(self::once())->method('append')
            ->with(self::isInstanceOf(OutboxEvent::class));

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->expects(self::once())->method('encode')->willReturn('event data');

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects(self::once())->method('dispatch')
            ->with(self::isInstanceOf(DomainEventPreparedForPersistenceEvent::class));

        $eventListener = new PersistDomainEventsListener($eventDispatcher, $repository, $serializer);

        $reflection = new \ReflectionClass(get_class($eventListener));
        $method = $reflection->getMethod('persistDomainEvent');
        $method->setAccessible(true);

        $domainEvent = new \stdClass();

        $method->invokeArgs($eventListener, [$domainEvent]);
    }

    public function test_collect_domain_events(): void
    {
        $repository = $this->createMock(OutboxEventRepository::class);

        $serializer = $this->createMock(SerializerInterface::class);

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects(self::once())->method('dispatch')
            ->with(self::isInstanceOf(AggregateRootPreparedForPersistenceEvent::class));

        $eventListener = new PersistDomainEventsListener($eventDispatcher, $repository, $serializer);

        $reflection = new \ReflectionClass(get_class($eventListener));
        $method = $reflection->getMethod('collectDomainEvents');
        $method->setAccessible(true);

        $domainEvent = new \stdClass();

        $method->invokeArgs($eventListener, [$domainEvent]);
    }
}
