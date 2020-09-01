<?php

namespace AGluh\Bundle\OutboxBundle\Event;

use AGluh\Bundle\OutboxBundle\Relay\Worker;

/**
 * Dispatched after the worker published an event or didn't processed an event at all.
 */
final class WorkerRunningEvent
{
    private Worker $worker;
    private bool $isWorkerIdle;

    public function __construct(Worker $worker, bool $isWorkerIdle)
    {
        $this->worker = $worker;
        $this->isWorkerIdle = $isWorkerIdle;
    }

    public function worker(): Worker
    {
        return $this->worker;
    }

    /**
     * Returns true when no events has been processed by the worker.
     */
    public function isWorkerIdle(): bool
    {
        return $this->isWorkerIdle;
    }
}
