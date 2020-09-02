<?php

declare(strict_types=1);

namespace AGluh\Bundle\OutboxBundle\Tests\Unit\Command;

use AGluh\Bundle\OutboxBundle\Command\StopWorkersCommand;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Console\Tester\CommandTester;

class StopWorkersCommandTest extends TestCase
{
    public function test_execute(): void
    {
        $cachePool = $this->createMock(CacheItemPoolInterface::class);
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->expects(self::once())->method('set');
        $cachePool->expects(self::once())->method('getItem')->willReturn($cacheItem);
        $cachePool->expects(self::once())->method('save')->with($cacheItem);

        $command = new StopWorkersCommand($cachePool);

        $tester = new CommandTester($command);
        $tester->execute([]);
    }
}
