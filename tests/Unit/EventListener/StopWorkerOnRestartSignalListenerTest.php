<?php

declare(strict_types=1);

namespace AGluh\Bundle\OutboxBundle\Tests\Unit\EventListener;

use AGluh\Bundle\OutboxBundle\Event\WorkerRunningEvent;
use AGluh\Bundle\OutboxBundle\EventListener\StopWorkerOnRestartSignalListener;
use AGluh\Bundle\OutboxBundle\Relay\Worker;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

class StopWorkerOnRestartSignalListenerTest extends TestCase
{
    /**
     * @dataProvider restartTimeProvider
     */
    public function test_worker_stops_when_memory_limit_exceeded(?int $lastRestartTimeOffset, bool $shouldStop): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($shouldStop ? self::once() : self::never())->method('info')
            ->with('Worker stopped because a restart was requested.');

        $cachePool = $this->createMock(CacheItemPoolInterface::class);
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->expects(self::once())->method('isHIt')->willReturn(true);
        $cacheItem->expects(self::once())->method('get')->willReturn(null === $lastRestartTimeOffset ? null : time() + $lastRestartTimeOffset);
        $cachePool->expects(self::once())->method('getItem')->willReturn($cacheItem);

        $worker = $this->createMock(Worker::class);
        $worker->expects($shouldStop ? self::once() : self::never())->method('stop');
        $event = new WorkerRunningEvent($worker, false);

        $stopOnSignalListener = new StopWorkerOnRestartSignalListener($cachePool, $logger);
        $stopOnSignalListener->onWorkerStarted();
        $stopOnSignalListener->onWorkerRunning($event);
    }

    /**
     * @return iterable<mixed>
     */
    public function restartTimeProvider(): iterable
    {
        yield [null, false]; // no cached restart time, do not restart
        yield [+10, true]; // 10 seconds after starting, a restart was requested
        yield [-10, false]; // a restart was requested, but 10 seconds before we started
    }

    public function test_worker_does_not_stop_if_restart_not_in_cache(): void
    {
        $cachePool = $this->createMock(CacheItemPoolInterface::class);
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->expects(self::once())->method('isHIt')->willReturn(false);
        $cacheItem->expects(self::never())->method('get');
        $cachePool->expects(self::once())->method('getItem')->willReturn($cacheItem);

        $worker = $this->createMock(Worker::class);
        $worker->expects(self::never())->method('stop');
        $event = new WorkerRunningEvent($worker, false);

        $stopOnSignalListener = new StopWorkerOnRestartSignalListener($cachePool);
        $stopOnSignalListener->onWorkerStarted();
        $stopOnSignalListener->onWorkerRunning($event);
    }
}
