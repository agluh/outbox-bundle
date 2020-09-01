<?php

namespace AGluh\Bundle\OutboxBundle\EventListener;

use AGluh\Bundle\OutboxBundle\Event\WorkerRunningEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Webmozart\Assert\Assert;

class StopWorkerOnEventLimitListener implements EventSubscriberInterface
{
    private int $maximumNumberOfEvents;
    private LoggerInterface $logger;
    private int $publishedEvents = 0;

    public function __construct(int $maximumNumberOfEvents, LoggerInterface $logger)
    {
        Assert::greaterThan($maximumNumberOfEvents, 0, 'Event limit must be greater than zero.');

        $this->maximumNumberOfEvents = $maximumNumberOfEvents;
        $this->logger = $logger;
    }

    public function onWorkerRunning(WorkerRunningEvent $event): void
    {
        if (false === $event->isWorkerIdle() && ++$this->publishedEvents >= $this->maximumNumberOfEvents) {
            $this->publishedEvents = 0;
            $event->worker()->stop();

            $this->logger->info('Worker stopped due to maximum count of {count} events published',
                ['count' => $this->maximumNumberOfEvents]);
        }
    }

    /**
     * @return string[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            WorkerRunningEvent::class => 'onWorkerRunning',
        ];
    }
}
