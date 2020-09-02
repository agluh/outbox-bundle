<?php

declare(strict_types=1);

namespace AGluh\Bundle\OutboxBundle\Tests\Unit\EventListener;

use AGluh\Bundle\OutboxBundle\Event\WorkerRunningEvent;
use AGluh\Bundle\OutboxBundle\EventListener\StopWorkerOnTimeLimitListener;
use AGluh\Bundle\OutboxBundle\Relay\Worker;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class StopWorkerOnTimeLimitListenerTest extends TestCase
{
    public function test_worker_stops_when_time_timit_is_reached(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('info')
            ->with('Worker stopped due to time limit of {timeLimit}s exceeded', ['timeLimit' => 1]);

        $worker = $this->createMock(Worker::class);
        $worker->expects(self::once())->method('stop');
        $event = new WorkerRunningEvent($worker, false);

        $timeoutListener = new StopWorkerOnTimeLimitListener(1, $logger);
        $timeoutListener->onWorkerStarted();
        sleep(2);
        $timeoutListener->onWorkerRunning($event);
    }
}
