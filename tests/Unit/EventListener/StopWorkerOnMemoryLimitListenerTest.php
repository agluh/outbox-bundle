<?php

declare(strict_types=1);

namespace AGluh\Bundle\OutboxBundle\Tests\Unit\EventListener;

use AGluh\Bundle\OutboxBundle\Event\WorkerRunningEvent;
use AGluh\Bundle\OutboxBundle\EventListener\StopWorkerOnMemoryLimitListener;
use AGluh\Bundle\OutboxBundle\Relay\Worker;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class StopWorkerOnMemoryLimitListenerTest extends TestCase
{
    /**
     * @dataProvider memoryProvider
     */
    public function test_worker_stops_when_memory_limit_exceeded(int $memoryUsage, int $memoryLimit, bool $shouldStop): void
    {
        $memoryResolver = fn () => $memoryUsage;

        $worker = $this->createMock(Worker::class);
        $worker->expects($shouldStop ? self::once() : self::never())->method('stop');
        $event = new WorkerRunningEvent($worker, false);

        $memoryLimitListener = new StopWorkerOnMemoryLimitListener($memoryLimit, null, $memoryResolver);
        $memoryLimitListener->onWorkerRunning($event);
    }

    /**
     * @return iterable<mixed>
     */
    public function memoryProvider(): iterable
    {
        yield [2048, 1024, true];
        yield [1024, 1024, false];
        yield [1024, 2048, false];
    }

    public function test_worker_logs_memory_exceeded_when_logger_is_given(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('info')
            ->with('Worker stopped due to memory limit of {limit} bytes exceeded ({memory} bytes used)', ['limit' => 64, 'memory' => 70]);

        $memoryResolver = fn () => 70;

        $worker = $this->createMock(Worker::class);
        $event = new WorkerRunningEvent($worker, false);

        $memoryLimitListener = new StopWorkerOnMemoryLimitListener(64, $logger, $memoryResolver);
        $memoryLimitListener->onWorkerRunning($event);
    }
}
