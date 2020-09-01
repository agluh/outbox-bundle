<?php

namespace AGluh\Bundle\OutboxBundle\EventListener;

use AGluh\Bundle\OutboxBundle\Event\WorkerRunningEvent;
use AGluh\Bundle\OutboxBundle\Event\WorkerStartedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Webmozart\Assert\Assert;

class StopWorkerOnTimeLimitListener implements EventSubscriberInterface
{
    private int $timeLimitInSeconds;
    private LoggerInterface $logger;
    private float $endTime = 0;

    public function __construct(int $timeLimitInSeconds, LoggerInterface $logger)
    {
        Assert::greaterThan($timeLimitInSeconds, 0, 'Time limit must be greater than zero.');

        $this->timeLimitInSeconds = $timeLimitInSeconds;
        $this->logger = $logger;
    }

    public function onWorkerStarted(): void
    {
        $startTime = microtime(true);
        $this->endTime = $startTime + $this->timeLimitInSeconds;
    }

    public function onWorkerRunning(WorkerRunningEvent $event): void
    {
        if ($this->endTime < microtime(true)) {
            $event->worker()->stop();

            $this->logger->info('Worker stopped due to time limit of {timeLimit}s exceeded',
                ['timeLimit' => $this->timeLimitInSeconds]);
        }
    }

    /**
     * @return string[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            WorkerStartedEvent::class => 'onWorkerStarted',
            WorkerRunningEvent::class => 'onWorkerRunning',
        ];
    }
}
