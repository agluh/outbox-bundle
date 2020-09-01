<?php

namespace AGluh\Bundle\OutboxBundle\Event;

use AGluh\Bundle\OutboxBundle\Relay\Worker;

final class WorkerStartedEvent
{
    private Worker $worker;

    public function __construct(Worker $worker)
    {
        $this->worker = $worker;
    }

    public function worker(): Worker
    {
        return $this->worker;
    }
}
