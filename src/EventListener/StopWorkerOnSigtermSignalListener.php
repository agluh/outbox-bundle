<?php

namespace AGluh\Bundle\OutboxBundle\EventListener;

use AGluh\Bundle\OutboxBundle\Event\WorkerStartedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class StopWorkerOnSigtermSignalListener implements EventSubscriberInterface
{
    public function onWorkerStarted(WorkerStartedEvent $event): void
    {
        pcntl_signal(SIGTERM, static function () use ($event): void {
            $event->worker()->stop();
        });
    }

    /**
     * @return array<mixed>
     * @codeCoverageIgnore
     */
    public static function getSubscribedEvents(): array
    {
        if (false === \function_exists('pcntl_signal')) {
            return [];
        }

        return [
            WorkerStartedEvent::class => ['onWorkerStarted', 100],
        ];
    }
}
