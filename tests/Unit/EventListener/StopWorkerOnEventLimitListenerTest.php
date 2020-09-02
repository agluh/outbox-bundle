<?php

declare(strict_types=1);

namespace AGluh\Bundle\OutboxBundle\Tests\Unit\EventListener;

use AGluh\Bundle\OutboxBundle\Event\WorkerRunningEvent;
use AGluh\Bundle\OutboxBundle\EventListener\StopWorkerOnEventLimitListener;
use AGluh\Bundle\OutboxBundle\Relay\Worker;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class StopWorkerOnEventLimitListenerTest extends TestCase
{
    /**
     * @dataProvider countProvider
     */
    public function test_worker_stops_when_maximum_count_exceeded(int $max, bool $shouldStop): void
    {
        $worker = $this->createMock(Worker::class);
        $worker->expects($shouldStop ? self::atLeastOnce() : self::never())->method('stop');
        $event = new WorkerRunningEvent($worker, false);

        $maximumCountListener = new StopWorkerOnEventLimitListener($max);
        // simulate three events published
        $maximumCountListener->onWorkerRunning($event);
        $maximumCountListener->onWorkerRunning($event);
        $maximumCountListener->onWorkerRunning($event);
    }

    /**
     * @return iterable<mixed>
     */
    public function countProvider(): iterable
    {
        yield [1, true];
        yield [2, true];
        yield [3, true];
        yield [4, false];
    }

    public function test_worker_logs_maximum_count_exceeded_when_logger_is_given(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('info')
            ->with(
                self::equalTo('Worker stopped due to maximum count of {count} events published'),
                self::equalTo(['count' => 1])
            );

        $worker = $this->createMock(Worker::class);
        $event = new WorkerRunningEvent($worker, false);

        $maximumCountListener = new StopWorkerOnEventLimitListener(1, $logger);
        $maximumCountListener->onWorkerRunning($event);
    }
}
