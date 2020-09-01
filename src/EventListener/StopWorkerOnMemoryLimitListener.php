<?php

namespace AGluh\Bundle\OutboxBundle\EventListener;

use AGluh\Bundle\OutboxBundle\Event\WorkerRunningEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Webmozart\Assert\Assert;

class StopWorkerOnMemoryLimitListener implements EventSubscriberInterface
{
    private int $memoryLimit;
    private LoggerInterface $logger;

    /** @var callable */
    private $memoryResolver;

    public function __construct(int $memoryLimit, LoggerInterface $logger, ?callable $memoryResolver = null)
    {
        Assert::greaterThan($memoryLimit, 0, 'Memory limit must be greater than zero.');

        $this->memoryLimit = $memoryLimit;
        $this->logger = $logger;
        $this->memoryResolver = $memoryResolver ?: static function () {
            return memory_get_usage(true);
        };
    }

    public function onWorkerRunning(WorkerRunningEvent $event): void
    {
        $memoryResolver = $this->memoryResolver;
        $usedMemory = $memoryResolver();

        if ($usedMemory > $this->memoryLimit) {
            $event->worker()->stop();

            $this->logger->info('Worker stopped due to memory limit of {limit} bytes exceeded ({memory} bytes used)',
                ['limit' => $this->memoryLimit, 'memory' => $usedMemory]);
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
