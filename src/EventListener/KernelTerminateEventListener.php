<?php

declare(strict_types=1);

namespace AGluh\Bundle\OutboxBundle\EventListener;

use AGluh\Bundle\OutboxBundle\Domain\Model\OutboxEventRepository;
use AGluh\Bundle\OutboxBundle\Relay\Worker;
use AGluh\Bundle\OutboxBundle\Serialization\SerializerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Lock\LockFactory;

class KernelTerminateEventListener implements EventSubscriberInterface
{
    private EventDispatcherInterface $eventDispatcher;
    private LockFactory $lockFactory;
    private OutboxEventRepository $repository;
    private SerializerInterface $serializer;
    private ?LoggerInterface $logger;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        LockFactory $lockFactory,
        OutboxEventRepository $repository,
        SerializerInterface $serializer,
        ?LoggerInterface $logger = null
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->lockFactory = $lockFactory;
        $this->repository = $repository;
        $this->serializer = $serializer;
        $this->logger = $logger;
    }

    public function onTerminateEvent(TerminateEvent $event): void
    {
        $this->publishEvents();
    }

    public function onConsoleTerminateEvent(ConsoleTerminateEvent $event): void
    {
        $this->publishEvents();
    }

    private function publishEvents(): void
    {
        $worker = new Worker($this->eventDispatcher, $this->lockFactory, $this->repository, $this->serializer, $this->logger);
        $worker->run();
    }

    /**
     * @return array<mixed>
     * @codeCoverageIgnore
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::TERMINATE => 'onTerminateEvent',
            ConsoleEvents::TERMINATE => 'onConsoleTerminateEvent',
        ];
    }
}
