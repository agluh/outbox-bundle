<?php

namespace AGluh\Bundle\OutboxBundle\EventListener;

use AGluh\Bundle\OutboxBundle\Event\WorkerRunningEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DispatchPcntlSignalListener implements EventSubscriberInterface
{
    public function onWorkerRunning(): void
    {
        pcntl_signal_dispatch();
    }

    /**
     * @return array<mixed>
     * @codeCoverageIgnore
     */
    public static function getSubscribedEvents(): array
    {
        if (false === \function_exists('pcntl_signal_dispatch')) {
            return [];
        }

        return [
            WorkerRunningEvent::class => ['onWorkerRunning', 100],
        ];
    }
}
